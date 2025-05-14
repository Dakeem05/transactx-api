@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your transfer of {{ $transaction->amount->getAmount()->toFloat() }} {{ $transaction->currency }} to {{ $recipientFirstName }} {{ $recipientLastName }}  failed and your funds reversed.</p>
<p>New Balance: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent