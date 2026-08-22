<?php

namespace App\Http\Requests;

use App\Helper\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
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
        if (isset($this->address['phone'])) {
            $phone = $this->address['phone'];

            // Add "+" if not already there
            if (strpos($phone, '+') !== 0) {
                $phone = '+' . $phone;
            }

        // Merge it back into the request
            $this->merge([
                'address' => array_merge($this->address, [
                    'phone' => $phone,
                ]),
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
            'address.firstName' => ['required', 'string', 'max:255'],
            'address.lastName' => ['required', 'string', 'max:255'],
            'address.phone' => ['required', 'phone:INTERNATIONAL'],
            'address.phoneCountryCode' => ['required', 'string', 'max:255'],
            'address.streetAddress' => ['required', 'string', 'max:255'],
            'address.companyName' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.province' => ['required', 'string', 'max:100'],
            'address.postalCode' => ['required', 'string', 'max:20'],
            // 'address.country' => ['required', 'array'],
            // 'address.country.label' => ['required', 'string', Rule::in(Helper::getCountryNames())],
            // 'address.country.value' => ['required', 'string', Rule::in(Helper::getCountryCodes())],
            'address.email' => ['required', 'string', 'email', 'max:255'],

            'products' => ['required', 'array', 'min:1'],
            'products.productId*' => ['required', 'integer', 'exists:products,id'],
            'products.productVariantId*' => ['required', 'integer', 'exists:product_variants,id'],
            'coupon' => ['nullable', 'string', 'max:60'],
            'agent_code' => ['nullable', 'string', Rule::exists('users', 'code')->where('is_agent', true)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'address.firstName' => __('translations.attributes.firstName'),
            'address.lastName' => __('translations.attributes.lastName'),
            'address.phone' => __('translations.attributes.phone'),
            'address.streetAddress' => __('translations.attributes.streetAddress'),
            'address.companyName' => __('translations.attributes.companyName'),
            'address.city' => __('translations.attributes.city'),
            'address.province' => __('translations.attributes.province'),
            'address.postalCode' => __('translations.attributes.postalCode'),
            'address.country' => __('translations.attributes.country'),
            'address.country.label' => __('translations.attributes.country'),
            'address.country.value' => __('translations.attributes.country'),
            'address.email' => __('translations.attributes.email'),
        ];
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
