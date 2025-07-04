@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>You have successfully linked {{ $account->account_number }} of {{ $account->bank_name}}. Your transactions can now be synched.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent