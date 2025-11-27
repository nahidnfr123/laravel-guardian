<?php

namespace NahidFerdous\Shield\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShieldLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $credentialField = config('shield.auth.login.credential_field', 'email');

        $rules = [
            'password' => 'required',
        ];

        // Handle multiple credential fields (e.g., 'email|mobile')
        if (str_contains($credentialField, '|')) {
            $fields = explode('|', $credentialField);

            // Accept a generic 'login' field OR any of the specific fields
            $rules['login'] = 'required_without_all:'.implode(',', $fields).'|string';

            foreach ($fields as $field) {
                if ($field === 'email') {
                    $rules[$field] = 'nullable|email';
                } else {
                    $rules[$field] = 'nullable|string';
                }
            }
        } else {
            // Single credential field
            if ($credentialField === 'email') {
                $rules['email'] = 'required|email';
            } elseif ($credentialField === 'mobile') {
                $rules['mobile'] = 'required|string';
            } else {
                // For custom fields or 'login' field
                $rules[$credentialField] = 'required|string';
            }
        }

        return $rules;
    }
}
