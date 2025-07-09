@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>You have been reverted to the {{ ucfirst($model->name->value) }} subscription plan and your uncovered data deleted.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent