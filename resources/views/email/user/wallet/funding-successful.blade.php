@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Funding of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} successful 💸.</p>
<p>New Balance: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent