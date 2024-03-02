@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Please be informed that your TransactX mobile profile was accessed using {{ $user_agent }} [{{ $ip_address }}] at {{ now() }}.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent