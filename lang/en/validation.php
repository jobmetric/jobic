<?php

return [
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'string' => 'The :attribute must be text.',
    'integer' => 'The :attribute must be an integer.',
    'boolean' => 'The :attribute must be true or false.',
    'in' => 'The selected :attribute is invalid.',
    'email' => 'The :attribute must be a valid email address.',
    'url' => 'The :attribute must be a valid URL.',
    'regex' => 'The :attribute format is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'starts_with' => 'The :attribute must start with :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must contain at least :min characters.',
    ],
    'max' => [
        'numeric' => 'The :attribute must be at most :max.',
        'string' => 'The :attribute must contain at most :max characters.',
    ],
    'between' => ['numeric' => 'The :attribute must be between :min and :max.'],
    'attributes' => array_merge((require __DIR__.'/installation.php')['fields'], [
        'locale' => 'language',
        'step' => 'step',
    ]),
];
