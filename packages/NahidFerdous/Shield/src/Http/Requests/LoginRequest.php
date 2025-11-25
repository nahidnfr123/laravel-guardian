<?php

namespace NahidFerdous\Shield\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        $credentialField = config('shield.login.credential_field', 'email');

        $rules = [
            'password' => 'required',
        ];

        // Add validation based on credential field
        if ($credentialField === 'email') {
            $rules['email'] = 'required|email';
        } elseif ($credentialField === 'mobile') {
            $rules['mobile'] = 'required|string';
        } else {
            // For custom fields, just require it exists
            $rules[$credentialField] = 'required';
        }

        return $rules;
    }
}
