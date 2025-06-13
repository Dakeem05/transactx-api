@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your transfer of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} to {{ $recipientFirstName }} {{ $recipientLastName }} is successful 💸.</p>
<p>Balance remains: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}. You were charged {{ $transaction->feeTransactions()->first()->amount->getAmount()->toFloat() }} {{ $transaction->currency }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent