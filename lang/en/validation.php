<?php

declare(strict_types=1);

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'email' => 'The :attribute field must be a valid email address.',
    'numeric' => 'The :attribute field must be a number.',
    'integer' => 'The :attribute field must be an integer.',
    'max' => [
        'string' => 'The :attribute field must not exceed :max characters.',
        'numeric' => 'The :attribute field must not be greater than :max.',
    ],
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
        'numeric' => 'The :attribute field must be at least :min.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'exists' => 'The selected :attribute is invalid.',
    'uuid' => 'The :attribute field must be a valid UUID.',
    'in' => 'The selected :attribute is invalid.',
];
