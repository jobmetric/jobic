<?php

return [
    'required' => 'وارد کردن :attribute الزامی است.',
    'required_if' => 'در این حالت وارد کردن :attribute الزامی است.',
    'string' => ':attribute باید متن باشد.',
    'integer' => ':attribute باید عدد صحیح باشد.',
    'boolean' => 'مقدار :attribute باید بله یا خیر باشد.',
    'in' => 'مقدار انتخاب‌شده برای :attribute معتبر نیست.',
    'email' => ':attribute باید یک آدرس ایمیل معتبر باشد.',
    'url' => ':attribute باید یک آدرس معتبر باشد.',
    'regex' => 'قالب :attribute معتبر نیست.',
    'not_regex' => 'قالب :attribute معتبر نیست.',
    'confirmed' => 'تأیید :attribute مطابقت ندارد.',
    'starts_with' => ':attribute باید با :values شروع شود.',
    'min' => [
        'numeric' => ':attribute باید حداقل :min باشد.',
        'string' => ':attribute باید حداقل :min نویسه داشته باشد.',
    ],
    'max' => [
        'numeric' => ':attribute باید حداکثر :max باشد.',
        'string' => ':attribute باید حداکثر :max نویسه داشته باشد.',
    ],
    'between' => ['numeric' => ':attribute باید بین :min و :max باشد.'],
    'attributes' => array_merge((require __DIR__.'/installation.php')['fields'], [
        'locale' => 'زبان',
        'step' => 'مرحله',
    ]),
];
