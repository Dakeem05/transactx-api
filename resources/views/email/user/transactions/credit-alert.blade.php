@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>You have been credited an amount of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} by {{ $senderFirstName }} {{ $senderLastName }} 💸.</p>
<p>Thank you for choosing TransactX!🚀</p>

@endcomponent