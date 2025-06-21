@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your {{ $network }} airtime purchase of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} to {{ $recipient }} failed and your funds reversed.</p>
<p>Balance: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent