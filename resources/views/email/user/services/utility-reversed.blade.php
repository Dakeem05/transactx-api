@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your {{ $company }} {{ $vendType }} recharge to {{ $recipient }} costing {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} failed and your funds reversed.</p>
<p>Balance: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent