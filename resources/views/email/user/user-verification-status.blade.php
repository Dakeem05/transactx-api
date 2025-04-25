@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

@if ($status == "SUCCESSFUL")
<p>
    Congratulations, your BVN verification was successful.
</p>
@else
<p>
    Unfortunately, your BVN verification failed. <br> Reason: {{ $event["data"]["reason"] }}. <br> Please contact our support team for more information.
</p>
@endif

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent