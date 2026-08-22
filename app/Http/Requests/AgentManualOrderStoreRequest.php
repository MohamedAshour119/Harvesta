<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentManualOrderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_agent;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_agent', false)),
            ],
            'sold_at' => ['required', 'date'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'special_sales_options' => ['nullable', 'array'],
            'proof_photo' => ['required', 'image', 'max:5120'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customerSnapshot(): array
    {
        $customer = null;
        if ($this->filled('user_id')) {
            $customer = User::find($this->integer('user_id'));
        }

        return [
            'user_id' => $customer?->id,
            'first_name' => $this->input('first_name') ?: ($customer ? $this->extractFirstName($customer->full_name) : null),
            'last_name' => $this->input('last_name') ?: ($customer ? $this->extractLastName($customer->full_name) : null),
            'email' => $this->input('email') ?: $customer?->email,
            'phone_number' => $this->input('phone_number') ?: $customer?->phone_number,
            'address' => $this->input('address'),
            'city' => $this->input('city'),
            'province' => $this->input('province'),
            'postal_code' => $this->input('postal_code'),
            'company_name' => $this->input('company_name'),
        ];
    }

    private function extractFirstName(string $fullName): string
    {
        return trim(strtok($fullName, ' ')) ?: $fullName;
    }

    private function extractLastName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return $parts[0] ?? $fullName;
        }

        return end($parts) ?: $fullName;
    }
}
