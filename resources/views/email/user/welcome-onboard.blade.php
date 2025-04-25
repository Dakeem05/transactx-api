@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>We're thrilled to have you onboard. Complete the next steps to unlock the full capabilities of TransactX.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent