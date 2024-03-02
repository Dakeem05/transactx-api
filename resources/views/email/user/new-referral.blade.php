@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>We're thrilled to inform you that someone has recently joined TransactX using your referral code! 🌟</p>

<p>Thank you for spreading the word about our service and for being an integral part of our journey towards excellence. We look forward to continuing to serve you and your network with the highest standards of quality and satisfaction.</p>

<p>...and of course, don't stop. Your referral code is: {{ $user->referral_code }}</p>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent
