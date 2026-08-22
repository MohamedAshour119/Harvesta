<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'required' => 'The :attribute field is required.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'string'   => 'The :attribute field must be a string.',
    'email'    => 'The :attribute field must be a valid email address.',
    'date'     => 'The :attribute field must be a valid date.',
    'image'    => 'The :attribute field must be an image.',
    'max' => [
        'string' => 'The :attribute field must not exceed :max characters.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
    ],
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'numeric' => 'The :attribute field must be at least :min.',
    ],
    'phone'    => 'The :attribute field contains an invalid number.',
    'in'       => 'The selected :attribute is invalid.',
    'exists'   => 'The selected :attribute is invalid.',
    'array'    => 'The :attribute field must be an array.',
    'integer'  => 'The :attribute field must be an integer.',


    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'first_name'    => 'First Name',
        'last_name'     => 'Last Name',
        'phone_number'  => 'Phone Number',
        'phone'         => 'Phone Number',
        'address'       => 'Address',
        'street_address'=> 'Street Address',
        'company_name'  => 'Company Name',
        'city'          => 'City',
        'province'      => 'Province',
        'postal_code'   => 'Postal Code',
        'country'       => 'Country',
        'email'         => 'Email',
        'proof_photo'   => 'Visit Proof',
        'products'      => 'Products',
        'products.*.product_id' => 'Product',
        'products.*.product_variant_id' => 'Product Variant',
        'products.*.quantity' => 'Quantity',
        'sold_at'       => 'Sold At',
        'user_id'       => 'Customer',
        'coupon'        => 'Coupon',
        'full_name'     => 'Full Name',
        'product_name'  => 'Product Name',
        'estimated_quantity' => 'Estimated Quantity',
        'budget'  => 'Budget',
        'product_description' => 'Product Description',
        'deadline'      => 'Deadline',
        'product_images' => 'Product Images',
        'message'=> 'Message',
    ],


];
