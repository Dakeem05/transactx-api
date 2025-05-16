@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>You have successfully requested an amount of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} from {{ $requesteeFirstName }} {{ $requesteeLastName }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent