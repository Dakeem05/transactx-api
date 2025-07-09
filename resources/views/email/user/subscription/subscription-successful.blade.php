@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your {{ ucfirst($plan) }} plan subscription of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} billed at {{ $billed_at }} {{ $transaction->currency }} is successful.</p>
<p>Balance remains: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}. You were charged {{ $transaction->feeTransactions()->first()->amount->getAmount()->toFloat() }} {{ $transaction->currency }} .</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent