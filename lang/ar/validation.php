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

    'required' => 'حقل :attribute مطلوب.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'string' => 'حقل :attribute يجب أن يكون نصاً.',
    'email' => 'حقل :attribute يجب أن يكون عنوان بريد إلكتروني صالحاً.',
    'date' => 'حقل :attribute يجب أن يكون تاريخاً صالحاً.',
    'image' => 'حقل :attribute يجب أن يكون صورة.',
    'max' => [
        'string' => 'حقل :attribute يجب ألا يتجاوز :max حروف.',
        'file' => 'حقل :attribute يجب ألا يتجاوز :max كيلوبايت.',
    ],
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصر على الأقل.',
        'numeric' => 'حقل :attribute يجب ألا يقل عن :min.',
    ],
    'phone' => 'حقل :attribute يحتوي على رقم غير صالح.',
    'in' => 'قيمة :attribute المحددة غير صالحة.',
    'exists' => 'قيمة :attribute المحددة غير صالحة.',
    'array' => 'حقل :attribute يجب أن يكون مصفوفة.',
    'integer' => 'حقل :attribute يجب أن يكون عدداً صحيحاً.',

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
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'phone_number' => 'رقم الجوال',
        'phone' => 'رقم الهاتف',
        'address' => 'العنوان',
        'street_address' => 'عنوان الشارع',
        'company_name' => 'اسم الشركة',
        'city' => 'المدينة',
        'province' => 'المنطقة',
        'postal_code' => 'الرمز البريدي',
        'country' => 'الدولة',
        'email' => 'البريد الإلكتروني',
        'proof_photo' => 'إثبات الزيارة',
        'products' => 'المنتجات',
        'products.*.product_id' => 'المنتج',
        'products.*.product_variant_id' => 'متغير المنتج',
        'products.*.quantity' => 'الكمية',
        'coupon' => 'الكوبون',
        'full_name' => 'الاسم الكامل',
        'product_name' => 'اسم المنتج',
        'estimated_quantity' => 'الكمية المقدرة',
        'budget' => 'الميزانية',
        'product_description' => 'وصف المنتج',
        'deadline' => 'الموعد',
        'product_images' => 'الصور',
        'message' => 'الرسالة',
        'sold_at' => 'تاريخ البيع',
        'user_id' => 'العميل',
    ],

];
