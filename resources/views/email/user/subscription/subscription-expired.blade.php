@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your {{ ucfirst($model->name->value) }} subscription plan has expired. It will auto renew or you would manually renew it.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent