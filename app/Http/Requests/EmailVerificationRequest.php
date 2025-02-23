<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest as AuthEmailVerificationRequest;
use Illuminate\Support\Facades\Auth;

class EmailVerificationRequest extends AuthEmailVerificationRequest
{
    public function authorize()
    {
        $user=User::find((string) $this->route('id'));
        Auth::login($user);
        if (! hash_equals((string) $this->user()->getKey(), (string) $this->route('id'))) {
            return false;
        }

        if (! hash_equals(sha1($this->user()->getEmailForVerification()), (string) $this->route('hash'))) {
            return false;
        }

        return true;
    }
}
