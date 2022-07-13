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
        $email = 'mail@hotmail.com';
   
        $mailInfo = [
            'title' => 'Welcome New User',
            'url' => 'https://www.remotestack.io'
        ];
  
        Mail::to($email)->send(new ResetPasswordMail($mailInfo));
    }
}
