@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your last manual bank transaction sync of successful and you were charged {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }}.</p>
<p>Balance: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent