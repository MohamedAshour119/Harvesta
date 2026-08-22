<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation()
    {
        if (isset($this->phone)) {
            $phone = $this->phone;

            // Add "+" if not already there
            if (strpos($phone, '+') !== 0) {
                $phone = '+' . $phone;
            }

        // Merge it back into the request
            $this->merge([
                'phone' => $phone,
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required','max:255'],
            'email' => ['required','email','max:255'],
            'phone' => ['nullable', 'phone:INTERNATIONAL','max:255'],
            'message' => ['required','max:1500'],
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
