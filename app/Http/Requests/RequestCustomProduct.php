<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestCustomProduct extends FormRequest
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
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'phone:INTERNATIONAL'],
            'product_name' => ['required', 'string', 'max:255'],
            'estimated_quantity' => ['required', 'numeric'],
            'budget' => ['required', 'numeric'],
            'product_description' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date'],
            'product_images' => ['nullable', 'array', 'max:6'],
            'product_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp|max:5120'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('phone') && !str_starts_with($this->phone, '+')) {
            $this->merge([
                'phone' => '+' . $this->phone
            ]);
        }
    }


    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Log::error('Validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request' => $this->all()
        ]);

        $originalErrors = $validator->errors()->toArray();
        $newErrors = [];

        foreach ($originalErrors as $key => $value) {
            if (strpos($key, 'address.') === 0) {
                $newKey = str_replace('address.', '', $key);
                $newErrors[$newKey] = $value;
            } else {
                $newErrors[$key] = $value;
            }
        }

        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'Validation failed',
            'errors' => $newErrors,
        ], 422));
    }
}
