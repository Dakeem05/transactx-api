@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>At the expiration of your 7 days allowance your subscription will be reverted to the {{ ucfirst($model->name) }} subscription plan and your uncovered data deleted. Renew your subscription to avoid this.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent