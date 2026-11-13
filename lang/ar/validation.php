<?php

declare(strict_types=1);

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون حقل :attribute نصاً.',
    'email' => 'يجب أن يكون حقل :attribute بريداً إلكترونياً صحيحاً.',
    'numeric' => 'يجب أن يكون حقل :attribute رقماً.',
    'integer' => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'max' => [
        'string' => 'يجب ألا يتجاوز حقل :attribute :max حرفاً.',
        'numeric' => 'يجب ألا تتجاوز قيمة حقل :attribute :max.',
    ],
    'min' => [
        'string' => 'يجب أن يحتوي حقل :attribute على :min حروف على الأقل.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute :min على الأقل.',
    ],
    'unique' => 'قيمة حقل :attribute مستخدمة بالفعل مسبقاً.',
    'exists' => 'القيمة المحددة في حقل :attribute غير صالحة.',
    'uuid' => 'يجب أن يكون حقل :attribute معرف UUID صالحاً.',
    'in' => 'القيمة المحددة في حقل :attribute غير صالحة.',
];
