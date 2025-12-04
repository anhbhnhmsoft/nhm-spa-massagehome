<?php

return [
    'password' => [
        'required' => 'Mật khẩu không hợp lệ.',
        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        'regex' => 'Mật khẩu phải chứa ít nhất một chữ hoa, một chữ thường và một số.',
    ],
    'name' => [
        'required' => 'Tên không hợp lệ.',
        'string' => 'Tên phải là chuỗi ký tự.',
        'max' => 'Tên phải có ít nhất :max ký tự.',
    ],
    'referral_code' => [
        'string' => 'Mã giới thiệu phải là chuỗi ký tự.',
    ],
    'gender' => [
        'required' => 'Giới tính không hợp lệ.',
        'in' => 'Giới tính không hợp lệ.',
    ],
    'language' => [
        'required' => 'Ngôn ngữ không hợp lệ.',
        'in' => 'Ngôn ngữ không hợp lệ.',
    ],
];
