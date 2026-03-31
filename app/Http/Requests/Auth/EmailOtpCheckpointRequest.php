<?php

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EmailOtpCheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmation_token' => ['required', 'string'],
            'otp_code'           => ['required', 'string', 'size:6'],
        ];
    }
}
