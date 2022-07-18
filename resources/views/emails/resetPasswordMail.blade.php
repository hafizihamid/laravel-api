@component('mail::message')
{{ $mailInfo['title'] }}

To reset your password, click on the button below:

@component('mail::button', ['url' => $mailInfo['url']])
Reset Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent