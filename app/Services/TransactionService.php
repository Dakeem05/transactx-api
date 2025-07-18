<?php

namespace App\Services;

use App\Dtos\Utilities\ServiceProviderDto;
use App\Enums\Subscription\ModelPaymentStatusEnum;
use App\Events\User\Banking\ManualBankTransactionSyncEvent;
use App\Events\User\Subscription\SubscriptionEvent;
use App\Events\User\Transactions\TransferFailed;
use App\Events\User\Transactions\TransferMoney;
use App\Events\User\Transactions\TransferSuccessful;
use App\Events\User\Wallet\FundWalletSuccessful;
use App\Models\Business\SubscriptionModel;
use App\Models\MoneyRequestStyles;
use App\Models\Service;
use App\Models\Settings;
use App\Models\User;
use App\Models\Transaction;
use App\Models\User\Wallet;
use Illuminate\Support\Str;
use App\Models\User\Wallet\WalletTransaction;
use App\Models\VirtualBankAccount;
use App\Notifications\User\Transactions\MoneyRequestReceivedNotification;
use App\Notifications\User\Transactions\MoneyRequestSentNotification;
use App\Services\External\SafehavenService;
use App\Services\User\WalletService;
use App\Services\Utilities\PaymentService;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TransactionService
{
    public $payment_service_provider;

    public function __construct ()
    {
        $payment_service = Service::where('name', 'payments')->first();

        
        if (!$payment_service) {
            throw new Exception('Payment service not found');
        }
        
        if ($payment_service->status === false) {
            throw new Exception('Payment service is currently unavailable');
        }
        $this->payment_service_provider = $payment_service->providers->where('status', true)->first();
        
        if (is_null($this->payment_service_provider)) {
            throw new Exception('Payment service provider not found');
        }
    }

    private function getPaymentServiceProvider()
    {

        if (!$this->payment_service_provider) {
            throw new Exception('Payment service provider not found');
        }
    
        $provider = ServiceProviderDto::from($this->payment_service_provider);

        if (!$provider instanceof ServiceProviderDto) {
            $provider = new ServiceProviderDto(
                name: $provider->name ?? null,
                description: $provider->description ?? null,
                status: $provider->status ?? false,
                percentage_charge: $provider->percentage_charge ?? 0.00,
                fixed_charge: $provider->fixed_charge ?? 0.00,
            );
        }
        return $provider;
    }

    public function queryUsers(string $username, string $id) 
    {
        // Get users matching the username search
        $users = User::where('username', 'LIKE', '%' . $username . '%')->where('id', '!=', $id)->get();
    
        // Modify each user's data (e.g., hide sensitive fields or add computed fields)
        $modifiedUsers = $users->map(function ($user) {
            return [
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'account_number' => isset($user->wallet) ? $user->wallet->virtualBankAccount->account_number : null,
                'bank_name' => isset($user->wallet) ? $user->wallet->virtualBankAccount->bank_name : null,
                'account_name' => isset($user->wallet) ? $user->wallet->virtualBankAccount->account_name : null,
            ];
        });
    
        return $modifiedUsers;
    }

    public function transactionHistory($request, User $user) 
    {
        if(isset($request->type)) {
            $histories = Transaction::where('user_id', $user->id)
            ->latest()
            ->whereMonth('created_at', isset($request->month) ? $request->month : now()->month)
            ->whereYear('created_at', isset($request->year) ? $request->year : now()->year)
            ->whereType(strtoupper($request->type))
            ->wherePrincipalTransactionId(null)
            ->with(['feeTransactions'])
            ->get();
        }else {
            $histories = Transaction::where('user_id', $user->id)
            ->latest()
            ->whereMonth('created_at', isset($request->month) ? $request->month : now()->month)
            ->whereYear('created_at', isset($request->year) ? $request->year : now()->year)
            ->wherePrincipalTransactionId(null)
            ->with(['feeTransactions'])
            ->get();
        }

        $in = $histories->where('type', 'FUND_WALLET')
        ->where('status', '!=', 'REVERSED')
        ->where('status', '!=', 'FAILED')
        ->sum(function ($transaction) {
            return $transaction->amount->getAmount()->toFloat();
        });
        
        $out = $histories->where('type', '!=', 'FUND_WALLET')
        ->where('type', '!=', 'REQUEST_MONEY')
        ->where('status', '!=', 'REVERSED')
        ->where('status', '!=', 'FAILED')
        ->sum(function ($transaction) {
            return $transaction->amount->getAmount()->toFloat();
        });
        
        $groupedHistories = $histories->map(function ($history) {
            return [
                'type' => $history->type,
                'amount' => $history->amount->getAmount()->toFloat(),
                'currency' => $history->currency,
                'status' => $history->status,
                'reference' => $history->reference,
                'description' => $history->description,
                'narration' => $history->narration,
                'date' => $history->created_at->format('F j, Y g:i:s A'),
                'payload' => $history->payload,
                'fee' => $history->feeTransactions->sum(function ($feeTransaction) {
                    return $feeTransaction->amount->getAmount()->toFloat(); 
                }),
            ];
        })->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item['date'])->format('F j');
        });

        return [
            'in' => $in,
            'out' => $out,
            'data' => $groupedHistories
        ];
    }
    
    public function sendMoneyToUsername(array $data, User $user, string $ip_address) 
    {
        $recipient = User::where('username', $data['username'])->first();

        $this->verifyTransaction($data, $user);

        if (is_null($recipient)) {
            throw new InvalidArgumentException('Recipient not found');
        }
        if (!$recipient->kycVerified()) {
            throw new InvalidArgumentException('Recipient account not verifed');
        }
        if (is_null($recipient->wallet)) {
            throw new InvalidArgumentException('Recipient does not have a wallet');
        }
        if (is_null($recipient->wallet->virtualBankAccount)) {
            throw new InvalidArgumentException('Recipient does not have a virtual bank account');
        }

        $provider = $this->getPaymentServiceProvider();
        

        if ($provider->name === 'safehaven') {
            $safehavenService = resolve(SafehavenService::class);
            $resolvedAccount = $safehavenService->resolveAccount(
                $recipient->wallet->virtualBankAccount->account_number, 
                $recipient->wallet->virtualBankAccount->bank_code
            );
            if (isset($resolvedAccount['data']['account_name'])) {
                $recipient->wallet->virtualBankAccount->account_name = $resolvedAccount['data']['account_name'];
                $recipient->wallet->virtualBankAccount->save();
            } else {
                throw new InvalidArgumentException('Failed to resolve recipient account name');
            }
        }

        if ($data['add_beneficiary']) {
            $beneficiaryService = resolve(BeneficiaryService::class);
            $payload = [
                'name' => $recipient->name,
                'avatar' => $recipient->avatar,
                'username' => $recipient->username,
                'email' => $recipient->email,
                'account_number' => $recipient->wallet->virtualBankAccount->account_number,
                'bank_code' => $recipient->wallet->virtualBankAccount->bank_code,
                'bank_name' => $recipient->wallet->virtualBankAccount->bank_name,
            ];
            $beneficiaryService->addBeneficiary($user->id, 'payment', $payload);
        }
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $recipient->wallet->virtualBankAccount->account_number, $recipient->wallet->virtualBankAccount->bank_code, $resolvedAccount['data']['session_id'], $data['narration'] ?? null, $ip_address ?? null);
    }
    
    public function sendMoneyToEmail(array $data, User $user, string $ip_address) 
    {
        $recipient = User::where('email', $data['email'])->first();
        
        $this->verifyTransaction($data, $user);
        
        if (is_null($recipient)) {
            throw new InvalidArgumentException('Recipient not found');
        }
        if (!$recipient->kycVerified()) {
            throw new InvalidArgumentException('Recipient account not verifed');
        }
        if (is_null($recipient->wallet)) {
            throw new InvalidArgumentException('Recipient does not have a wallet');
        }
        if (is_null($recipient->wallet->virtualBankAccount)) {
            throw new InvalidArgumentException('Recipient does not have a virtual bank account');
        }
        
        $provider = $this->getPaymentServiceProvider();
        
        if ($provider->name === 'safehaven') {
            $safehavenService = resolve(SafehavenService::class);
            $resolvedAccount = $safehavenService->resolveAccount(
                $recipient->wallet->virtualBankAccount->account_number, 
                $recipient->wallet->virtualBankAccount->bank_code
            );
            if (isset($resolvedAccount['data']['account_name'])) {
                $recipient->wallet->virtualBankAccount->account_name = $resolvedAccount['data']['account_name'];
                $recipient->wallet->virtualBankAccount->save();
            } else {
                throw new InvalidArgumentException('Failed to resolve recipient account name');
            }
        }

        if ($data['add_beneficiary']) {
            $beneficiaryService = resolve(BeneficiaryService::class);
            $payload = [
                'name' => $recipient->name,
                'avatar' => $recipient->avatar,
                'username' => $recipient->username,
                'email' => $recipient->email,
                'account_number' => $recipient->wallet->virtualBankAccount->account_number,
                'bank_code' => $recipient->wallet->virtualBankAccount->bank_code,
                'bank_name' => $recipient->wallet->virtualBankAccount->bank_name,
            ];
            $beneficiaryService->addBeneficiary($user->id, 'payment', $payload);
        }
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $recipient->wallet->virtualBankAccount->account_number, $recipient->wallet->virtualBankAccount->bank_code, $resolvedAccount['data']['session_id'], $data['narration'] ?? null, $ip_address ?? null);
    }

    public function sendMoneyToBeneficiary(array $data, User $user, string $ip_address) 
    {
        $beneficiary = resolve(BeneficiaryService::class)->getBeneficiary($user->id, $data['beneficiary_id']);
        $this->verifyTransaction($data, $user);
        
        $provider = $this->getPaymentServiceProvider();
        
        if ($provider->name === 'safehaven') {
            $safehavenService = resolve(SafehavenService::class);
            $resolvedAccount = $safehavenService->resolveAccount(
                $beneficiary->payload['account_number'], 
                $beneficiary->payload['bank_code']
            );
            if (!isset($resolvedAccount['data']['account_name'])) {
                throw new InvalidArgumentException('Failed to resolve recipient account name');
            }
        }
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $beneficiary->payload['account_number'], $beneficiary->payload['bank_code'], $resolvedAccount['data']['session_id'], $data['narration'] ?? null, $ip_address ?? null);
    }
    
    public function sendMoney(array $data, User $user, string $ip_address) 
    {
        $this->verifyTransaction($data, $user);

        if ($data['add_beneficiary']) {
            $beneficiaryService = resolve(BeneficiaryService::class);
            $payload = [
                'name' => $data['account_name'],
                'avatar' => null,
                'username' => null,
                'email' => null,
                'account_number' => $data['account_number'],
                'bank_code' => $data['bank_code'],
                'bank_name' => $data['bank_name'],
            ];
            $beneficiaryService->addBeneficiary($user->id, 'payment', $payload);
        }
        
        return $this->transfer($user->wallet->virtualBankAccount, $data['amount'], $data['account_number'], $data['bank_code'], $data['session_id'], $data['narration'] ?? null, $ip_address ?? null, $data['account_name'], $data['bank_name']);
    }

    public function getRecentRecipients(User $user, int $limit = 10)
    {
        // Get the user's wallet
        $wallet = $user->wallet;
    
        if (is_null($wallet)) {
            return collect();
        }
    
        // Fetch recent transactions and process for unique recipients
        return Transaction::where('wallet_id', $wallet->id)
            ->where('type', 'SEND_MONEY')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'account_number' => $transaction->payload['account_number'] ?? null,
                    'bank_code' => $transaction->payload['bank_code'] ?? null,
                    'bank_name' => $transaction->payload['bank_name'] ?? null,
                    'account_name' => $transaction->payload['account_name'] ?? null,
                    'created_at' => $transaction->created_at // Keep for sorting
                ];
            })
            ->filter() // Remove null entries
            ->unique(function ($item) {
                return $item['account_number'].$item['bank_code'];
            })
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(function ($item) {
                // Remove created_at before returning
                unset($item['created_at']);
                return $item;
            })
            ->values(); // Reset array keys
    }
    
    private function transfer (VirtualBankAccount $virtualBankAccount, int $amount, string $account_number, string $bank_code, string $session_id, string $narration = null, string $ip_address = null, string $name = null, string $bank_name = null)
    {
        try {
            $reference = uuid_create();
            $currency = Settings::where('name', 'currency')->first()->value;
            
            $data = [
                'debit_account_number' => $virtualBankAccount->account_number,
                'amount' => $amount,
                'account_number' => $account_number,
                'bank_code' => $bank_code,
                'currency' => $currency,
                'narration' => $narration,
                'reference' => $reference,
                'session_id' => $session_id,
            ];
            
            $paymentService = resolve(PaymentService::class);
            $response = $paymentService->transfer($data);
            if (isset($response['statusCode']) && $response['statusCode'] != 200) {
                Log::error('transfer: Failed to get Transfer. Reason: ' . $response['message']);
                return;
            }
            
            $recipient_wallet = Wallet::whereHas('virtualBankAccount', function ($query) use ($account_number, $currency) {
                $query->where([
                    ['account_number', $account_number],
                    ['currency', $currency],
                ]);
            })->where('currency', $currency)
            ->first();
            
            if (!is_null($recipient_wallet)) {
                $payload = [
                    'account_number' => $account_number,
                    'bank_code' => $bank_code,
                    'bank_name' => $recipient_wallet->virtualBankAccount->bank_name,
                    'account_name' => $recipient_wallet->virtualBankAccount->account_name,
                ];
                event(new TransferMoney($virtualBankAccount->wallet, $data['amount'], $response['data']['fees'], $data['currency'], $data['reference'], $response['data']['sessionId'], $data['narration'], $ip_address, $recipient_wallet->virtualBankAccount->account_name, $payload));
            } else {
                $payload = [
                    'account_number' => $account_number,
                    'bank_code' => $bank_code,
                    'bank_name' => $bank_name,
                    'account_name' => $name,
                ];
                event(new TransferMoney($virtualBankAccount->wallet, $data['amount'], $response['data']['fees'], $data['currency'], $data['reference'], $response['data']['sessionId'], $data['narration'], $ip_address, $name, $payload));
            }
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function pendingTransfers(Transaction $transaction)
    {
        $paymentService = resolve(PaymentService::class);
        $response = $paymentService->getTransaction($transaction->external_transaction_reference);
        if (strtolower($response['data']['status']) === "completed" && !$response['data']['isReversed']) {
            event(new TransferSuccessful($transaction, $transaction->payload['account_number'], $transaction->currency, $transaction->payload['account_name']));
        } else if (strtolower($response['data']['status']) === "completed" && $response['data']['isReversed'])  {
            event(new TransferFailed($transaction, $transaction->payload['account_name']));
        }
    }

    public function subscribe (User $user, VirtualBankAccount $virtualBankAccount, SubscriptionModel $model, int $amount, string $narration, bool $renewal, array $request_data = [])
    {
        $this->verifyTransaction(['amount' => $amount], $user);

        $provider = $this->getPaymentServiceProvider();

        if ($provider->name === 'safehaven') {
            try {
                $reference = uuid_create();
                $currency = Settings::where('name', 'currency')->first()->value;
                $safehavenService = resolve(SafehavenService::class);
                $resolvedAccount = $safehavenService->resolveAccount(
                    config('services.safehaven.account_number'), 
                    '090286'
                );
                
                $data = [
                    'debit_account_number' => $virtualBankAccount->account_number,
                    'amount' => $amount,
                    'account_number' => config('services.safehaven.account_number'),
                    'bank_code' => '090286',
                    'currency' => $currency,
                    'narration' => $narration,
                    'reference' => $reference,
                    'session_id' => $resolvedAccount['data']['session_id'],
                ];
                
                $paymentService = resolve(PaymentService::class);
                $response = $paymentService->transfer($data);
                if (isset($response['statusCode']) && $response['statusCode'] != 200) {
                    Log::error('transfer: Failed to get Transfer. Reason: ' . $response['message']);
                    throw new Exception('transfer: Failed to get Transfer. Reason: ' . $response['message']);
                }

                $payment = $user->subscription->payments()->create([
                    'subscription_id' => $user->subscription->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => ModelPaymentStatusEnum::PENDING,
                    'payment_reference' => $data['reference'],
                    'external_reference' => $response['data']['paymentReference'],
                    'method' => $request_data['method'],
                ]);
                
                $payload = [
                    'plan' => ucfirst($model->name->value),
                    'billed_at' => $model->amount->getAmount()->toFloat(),
                    'renewal' => $renewal,
                    'subscription_payment_id' => $payment->id,
                ];
                event(new SubscriptionEvent($virtualBankAccount->wallet, $data['amount'], $response['data']['fees'], $data['currency'], $data['reference'], $response['data']['paymentReference'], $data['narration'], $payload));
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }
    }

    public function bankTransactionRequest (User $user, VirtualBankAccount $virtualBankAccount, int $amount, string $narration, array $payload)
    {
        $this->verifyTransaction(['amount' => $amount], $user);

        $provider = $this->getPaymentServiceProvider();

        if ($provider->name === 'safehaven') {
            try {
                $reference = uuid_create();
                $currency = Settings::where('name', 'currency')->first()->value;
                $safehavenService = resolve(SafehavenService::class);
                $resolvedAccount = $safehavenService->resolveAccount(
                    config('services.safehaven.account_number'), 
                    '090286'
                );
                
                $data = [
                    'debit_account_number' => $virtualBankAccount->account_number,
                    'amount' => $amount,
                    'account_number' => config('services.safehaven.account_number'),
                    'bank_code' => '090286',
                    'currency' => $currency,
                    'narration' => $narration,
                    'reference' => $reference,
                    'session_id' => $resolvedAccount['data']['session_id'],
                ];
                
                $paymentService = resolve(PaymentService::class);
                $response = $paymentService->transfer($data);
                if (isset($response['statusCode']) && $response['statusCode'] != 200) {
                    Log::error('transfer: Failed to get Transfer. Reason: ' . $response['message']);
                    throw new Exception('transfer: Failed to get Transfer. Reason: ' . $response['message']);
                }
                
                event(new ManualBankTransactionSyncEvent($virtualBankAccount->wallet, $data['amount'], $response['data']['fees'], $data['currency'], $data['reference'], $response['data']['paymentReference'], $data['narration'], $payload));
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }
    }
    
    public function getRequestStyles ()
    {
        return MoneyRequestStyles::where('status', true)->select(['id', 'name', 'content', 'picture'])->get();
        
    }
    
    public function requestMoneyFromUsername(array $data, User $user, string $ip_address) 
    {
        $requestee = User::where('username', $data['username'])->first();
        $this->verifyRequest($data, $user);
        
        if (is_null($requestee)) {
            throw new InvalidArgumentException('Requestee not found');
        }
        if (!$requestee->kycVerified()) {
            throw new InvalidArgumentException('Requestee account not verifed');
        }
        if (is_null($requestee->wallet)) {
            throw new InvalidArgumentException('Requestee does not have a wallet');
        }
        if (is_null($requestee->wallet->virtualBankAccount)) {
            throw new InvalidArgumentException('Requestee does not have a virtual bank account');
        }
        
        return $this->request($data['amount'], $data['request_style_id'], $user, $requestee, $data['content'] ?? null, $ip_address ?? null);
    }

    public function requestMoneyFromEmail(array $data, User $user, string $ip_address) 
    {
        $requestee = User::where('email', $data['email'])->first();
        $this->verifyRequest($data, $user);
        
        Log::info('requestMoneyFromEmail', [
            'requestee' => $requestee,
            'amount' => $data['amount'],
            'request_style_id' => $data['request_style_id'],
            'content' => $data['content'] ?? null,
            'ip_address' => $ip_address
        ]);
        if (is_null($requestee)) {
            throw new InvalidArgumentException('Requestee not found');
        }
        if (!$requestee->kycVerified()) {
            throw new InvalidArgumentException('Requestee account not verifed');
        }
        if (is_null($requestee->wallet)) {
            throw new InvalidArgumentException('Requestee does not have a wallet');
        }
        if (is_null($requestee->wallet->virtualBankAccount)) {
            throw new InvalidArgumentException('Requestee does not have a virtual bank account');
        }
        
        return $this->request($data['amount'], $data['request_style_id'], $user, $requestee, $data['content'] ?? null, $ip_address ?? null);
    }

    private function request (int $amount, string $request_style_id, User $user,  User $requestee, string $request_content = null, string $ip_address = null)
    {
        try {
            DB::beginTransaction();

            $currency = Settings::where('name', 'currency')->first()->value;
            
            $request_style = MoneyRequestStyles::find($request_style_id);

            if (is_null($request_style)) {
                throw new InvalidArgumentException('Invalid request style id provided');
            }
            
            $content = '';
            $amount = Money::of($amount, Settings::where('name', 'currency')->first()->value);

            Log::info('request', [
                'amount' => $amount->getAmount()->toFloat(),
                'currency' => $currency,
                'request_style' => $request_style->name,
                'content' => $request_content,
            ]);

            if (strtolower($request_style->name) !== 'custom') {
                $values = [
                    'amount' => $amount->getAmount()->toFloat(),
                    'currency' => $currency
                ];
                
                $content = preg_replace_callback('/{{(\w+)}}/', 
                    function($matches) use ($values) {
                        return $values[$matches[1]] ?? $matches[0];
                    }, 
                    $request_style->content
                );
            } else {
                if (is_null($request_content)) {
                    throw new InvalidArgumentException('Request content must be provided for Custom style');
                }
                $content = $request_content;
            }

            $payload = [
                'name' => $requestee->name,
                'username' => $requestee->username,
                'email' => $requestee->email,
                'avatar' => $requestee->avatar,
            ];

            Log::info('request 2', [
                'user_id' => $user->id,
                'requestee_id' => $requestee->id,
                'amount' => $amount->getAmount()->toFloat(),
                'currency' => $currency,
                'content' => $content,
                'ip_address' => $ip_address,
            ]);
            
            $transaction = $this->createSuccessfulTransaction($user, $user->wallet->id, $amount, $currency, 'REQUEST_MONEY', $ip_address, null, $payload, null);
            
            Log::info('request 3', [
                'user_id' => $user->id,
                'requestee_id' => $requestee->id,
                'amount' => $amount->getAmount()->toFloat(),
                'currency' => $currency,
                'content' => $content,
                'ip_address' => $ip_address,
            ]);
            $user->notify(new MoneyRequestSentNotification($transaction, $requestee->name));
            $requestee->notify(new MoneyRequestReceivedNotification($user->name, $content));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }

    public function getRecentRequestMoneyRecipients(User $user, int $limit = 10)
    {
        // Get the user's wallet
        $wallet = $user->wallet;
    
        if (is_null($wallet)) {
            return collect();
        }
    
        // Fetch recent transactions and process for unique recipients
        return Transaction::where('wallet_id', $wallet->id)
            ->where('type', 'REQUEST_MONEY')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'name' => $transaction->payload['name'] ?? null,
                    'username' => $transaction->payload['username'] ?? null,
                    'email' => $transaction->payload['email'] ?? null,
                    'avatar' => $transaction->payload['avatar'] ?? null,
                    'created_at' => $transaction->created_at // Keep for sorting
                ];
            })
            ->filter() // Remove null entries
            ->unique(function ($item) {
                return $item['username'].$item['email'];
            })
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(function ($item) {
                // Remove created_at before returning
                unset($item['created_at']);
                return $item;
            })
            ->values(); // Reset array keys
    }

    private function verifyRequest(array $data, User $user)
    {
        if (is_null($user->wallet)) {
            throw new InvalidArgumentException('Create and fund your wallet');
        }

        $min_transfer = Settings::where('name', 'min_transaction')->first()->value;

        if ($data['amount'] < $min_transfer) {
            throw new InvalidArgumentException('Transaction amount is below minimum transaction');
        }
    }

    public function verifyTransaction (array $data, User $user)
    {
        if (is_null($user->wallet)) {
            throw new InvalidArgumentException('Create and fund your wallet');
        }

        $min_transfer = Settings::where('name', 'min_transaction')->first()->value;

        // Uncomment the following lines if you want to enforce minimum transfer amount
        // if ($data['amount'] < $min_transfer) {
        //     throw new InvalidArgumentException('Transaction amount is below minimum transaction');
        // }

        $walletService = resolve(WalletService::class);
        $potential_charges = 20;
        if (!$walletService->checkBalance($user->wallet, isset($data['total_amount']) ? $data['total_amount'] : $data['amount'] + $potential_charges)) {
            throw new InvalidArgumentException('Insufficient balance for that transaction');
        }
    }

    public function getTransactionDescription(string $type, string $currency): ?string
    {
        return match ($type) {
            'SEND_MONEY' => "Sent $currency",
            'REQUEST_MONEY' => "Requested $currency",
            'FUND_WALLET' => "Funded $currency wallet",
            'SEND_MONEY_FEE' => "Charged $currency fee",
            'FUND_WALLET_FEE' => "Charged $currency fee",
            'AIRTIME' => "Recharged $currency",
            'AIRTIME_FEE' => "Charged $currency fee",
            'DATA' => "Purchased $currency",
            'DATA_FEE' => "Charged $currency fee",
            'CABLETV' => "Subscribed $currency",
            'CABLETV_FEE' => "Charged $currency fee",
            'UTILITY' => "Recharged $currency",
            'UTILITY_FEE' => "Charged $currency fee",
            'SUBSCRIPTION' => "Subscribed $currency",
            'SUBSCRIPTION_FEE' => "Charged $currency fee",
            'TRANSACTION_SYNC' => "Paid $currency",
            'TRANSACTION_SYNC_FEE' => "Charged $currency fee",
            default => null,
        };
    }

    /**
     * Create and return a new pending transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param ?string $userIp
     * 
     * @return Transaction
     */
    public function createPendingTransaction(
        User $user,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY",
        $reference,
        $payload,
        $wallet_id,
        $narration = null,
        $userIp = null,
        $external_transaction_reference = null
    ) {

        $description = $this->getTransactionDescription($type, $currency);
        $transaction = Transaction::create([
            "user_id" => $user->id,
            "currency" => $currency,
            "wallet_id" => $wallet_id,
            "amount" => $amount,
            "reference" => $reference,
            "payload" => $payload,
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
            "narration" => $narration,
            "user_ip" => $userIp,
            "external_transaction_reference" => $external_transaction_reference,
        ]);
        
        return $transaction;
    }

    /**
     * Create and return a new pending fee transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param ?string $userIp
     * 
     * @return Transaction
     */
    public function createPendingFeeTransaction(
        User $user,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY_FEE",
        $reference,
        $wallet_id,
        $principal_transaction_id,
    ) {

        $description = $this->getTransactionDescription($type, $currency);
        $transaction = Transaction::create([
            "user_id" => $user->id,
            "currency" => $currency,
            "wallet_id" => $wallet_id,
            "principal_transaction_id" => $principal_transaction_id,
            "amount" => $amount,
            "reference" => $reference,
            "status" => "PENDING",
            "type" => $type,
            "description" => $description,
        ]);
        
        return $transaction;
    }


    /**
     * Create and return a new successful transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param string $wallet_id
     * @param ?string $userIp
     * @param ?string $external_transaction_reference
     * 
     * @return Transaction
     */
    public function createSuccessfulTransaction(
        User $user,
        $wallet_id,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY",
        $userIp = null,
        $external_transaction_reference = null,
        $payload = null,
        $reference = null
    ) {

        $description = $this->getTransactionDescription($type, $currency);

        $transaction = Transaction::create([
            "user_id" => $user->id,
            "wallet_id" => $wallet_id,
            "currency" => $currency,
            "amount" => $amount,
            "reference" => isset($reference) ? $reference  : Str::uuid(),
            "external_transaction_reference" => $external_transaction_reference,
            "status" => "SUCCESSFUL",
            "type" => $type,
            "payload" => $payload,
            "description" => $description,
            "user_ip" => $userIp,
        ]);

        if ($transaction->isFundWalletTransaction()) {
            event(new FundWalletSuccessful($transaction));
        }

        return $transaction;
    }

    public function createSuccessfulFeeTransaction(
        User $user,
        $wallet_id,
        $amount,
        $currency = 'NGN',
        $type = "SEND_MONEY_FEE",
        $principal_transaction_id,
    ) {

        $description = $this->getTransactionDescription($type, $currency);

        $transaction = Transaction::create([
            "user_id" => $user->id,
            "wallet_id" => $wallet_id,
            "currency" => $currency,
            "amount" => $amount,
            "reference" => Str::uuid(),
            "status" => "SUCCESSFUL",
            "type" => $type,
            "description" => $description,
            "principal_transaction_id" => $principal_transaction_id,
        ]);

        return $transaction;
    }

    /**
     * Update Transaction with associated Wallet Transaction
     *
     * @param Transaction $transaction
     * @param Wallet $wallet
     * @param string|null $walletTransactionId
     * @return void
     */
    public function attachWalletTransactionFor(Transaction $transaction, Wallet $wallet, ?string $walletTransactionId = null)
    {
        $walletTransaction = null;

        if (is_null($walletTransactionId)) {
            $walletTransaction = WalletTransaction::with('wallet')->latest()->first();
        } else {
            $walletTransaction = WalletTransaction::with('wallet')->find($walletTransactionId);
        }

        $walletTransactionAmountChange = $walletTransaction->amount_change->getMinorAmount()->toInt();
        $transactionAmount = $transaction->amount->getMinorAmount()->toInt();
        $feeAmount = $transaction->feeTransactions()->first()->amount->getMinorAmount()->toInt();
        
        // Due diligence check to ensure that the transaction originates from the wallet
        if ($transaction->isFundWalletTransaction()) {
            if ($wallet->is($walletTransaction->wallet) && $wallet->is($transaction->wallet) && $walletTransactionAmountChange == $transactionAmount - $feeAmount) {
                $this->updateTransaction($transaction, ['wallet_transaction_id' => $walletTransaction->id]);
            }
        } else {
            if ($wallet->is($walletTransaction->wallet) && $wallet->is($transaction->wallet) && $walletTransactionAmountChange == $transactionAmount + $feeAmount) {
                $this->updateTransaction($transaction, ['wallet_transaction_id' => $walletTransaction->id]);
            }
        }
    }

    /**
     * Update a transaction with new data.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function updateTransaction(Transaction $transaction, array $data)
    {
        // Check if 'status' is in the data array and remove it
        $status = null;
        if (isset($data['status'])) {
            $status = $data['status'];
            unset($data['status']);
        }

        $transaction->update([
            'external_transaction_reference' => $data['external_transaction_reference'] ?? $transaction->external_transaction_reference,
            'reference' => $data['reference'] ?? $transaction->reference,
            'wallet_id' => $data['wallet_id'] ?? $transaction->wallet_id,
            'description' => $data['description'] ?? $transaction->description,
            'wallet_transaction_id' => $data['wallet_transaction_id'] ?? $transaction->wallet_transaction_id,
        ]);

        if ($status !== null) {
            $this->updateTransactionStatus($transaction, $status);
        }

        return $transaction;
    }

    /**
     * Update a transaction's status.
     *
     * @param Transaction $transaction
     * @param string $status
     * @return Transaction
     */
    public function updateTransactionStatus(Transaction $transaction, $status)
    {

        if (!in_array($status, ["SUCCESSFUL", "FAILED", "PENDING", "PROCESSING", "REVERSED"])) {
            throw new \Exception("TransactionService.updateTransactionStatus(): Invalid status: $status.");
        }

        $oldTransactionStatus = $transaction->status;

        $transaction->update([
            'status' => $status,
        ]);

        if ($status === "SUCCESSFUL" && $oldTransactionStatus !== "SUCCESSFUL") {
            // transaction state is changing to successful
            if ($transaction->isFundWalletTransaction()) {
                // Event
            }

            if ($transaction->isSendMoneyTransaction()) {
                // Event
            }
        }

        return $transaction;
    }
}
