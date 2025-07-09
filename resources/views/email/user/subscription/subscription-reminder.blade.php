@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your {{ ucfirst($model->name) }} subscription plan will expire {{ $subscription->end_at->diffForHumans() }}. You can either set up auto renewal or be ready to manually renew when it expires.</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent