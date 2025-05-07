@component('mail::message')
<p>Dear {{ $main_account->first_name ?? $main_account->email }},</p>

<p>Please be informed that your sub account <strong>{{ $user->name }}</strong> accessed TransactX mobile profile with {{ $user_agent }} [{{ $ip_address }}] at {{ now() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent