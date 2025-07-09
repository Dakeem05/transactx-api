@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>You have upgraded to {{ ucfirst($model->name->value) }} plan subscription and will be activated at the end of your current subscription.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent