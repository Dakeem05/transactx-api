@component('mail::message')

<p>Hi there,</p>

<p>Code: {{ $verification_code }}</p>

<p>Please be informed that this code expires in {{ $expiry_minutes }} minutes.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent