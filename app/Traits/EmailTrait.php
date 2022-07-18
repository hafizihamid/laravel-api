<?php

namespace App\Traits;

use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Mail;

trait EmailTrait
{
    public function sendResetPasswordEmail($email, $token)
    {
        $linkEmail = str_replace("{token}", $token, config('staticdata.frontend.reset_password_path'));
        $linkEmail = str_replace("{email}", urlencode($email), $linkEmail);
        $linkEmail = config('staticdata.frontend.url') . $linkEmail;
   
        $mailInfo = [
            'title' => config('app.name') . " - Password Reset",
            'url' => $linkEmail
        ];
  
        Mail::to($email)->send(new ResetPasswordMail($mailInfo));
    }
}
