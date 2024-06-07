@component('mail::message')
<p>Dear {{ $user->first_name ?? $user->email }},</p>

<p>Your {{$wallet->currency}} wallet has been successfully created.</p>
<p>Current Balance: {{$wallet->currency}} {{ $wallet->amount->getAmount()->toFloat() }}.</p>

<p>Your can fund your wallet using the details below</p>
<ol>
<li>Account Number: {{ $virtualBankAccount->account_number }}</li>
<li>Bank Name: {{ $virtualBankAccount->bank_name }}</li>
<li>Account Name: {{ $virtualBankAccount->account_name }}</li>
</ol>

<p>Thank you for choosing TransactX!🚀</p>

@endcomponent