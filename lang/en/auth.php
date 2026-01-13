<?php


return [
    'success' => [
        'otp_register' => 'OTP code has been sent. Please enter the verification code.',
        'verify_register' => 'Phone number has been verified.',
        'register' => 'Account has been registered.',
        'login' => 'You have logged in successfully.',
        'user' => 'User information.',
        'set_language' => 'Language has been updated.',
        'update_success' => 'Information updated successfully.',
        'logout' => 'You have logged out successfully.',
        'lock_account' => 'Account has been locked.',
    ],
    'error' => [
        'phone_verified' => 'This phone number has already been verified.',
        'blocked' => 'Too many invalid attempts. Please try again in :minutes minutes.',
        'already_sent' => 'OTP code has already been sent. Please check again.',
        'invalid_otp' => 'Invalid OTP code.',
        'otp_expired' => 'OTP code has expired.',
        'attempts_left' => 'Too many attempts. Please try again in :minutes minutes.',
        'resend_otp' => 'Too many resend attempts. Please try again in :minutes minutes.',
        'not_sent' => 'OTP code does not exist.',
        'phone_invalid' => 'Invalid phone number.',
        'invalid_token_register' => 'Invalid registration token.',
        'disabled' => 'This account has been locked, please contact administrator for support.',
        'invalid_login' => 'Incorrect phone number or password.',
        'language_invalid' => 'Invalid language.',
        'unauthorized' => 'You are not authorized to perform this action.',
        'validation_failed' => 'Validation error.',
        'unauthenticated' => 'You are not logged in.',
        'wrong_password' => 'Incorrect old password.',
    ],

    'admin' => [
        'phone' => 'Phone number.',
        'password' => 'Password.',
    ],
    'validation' => [
        'name_required' => 'Name consists cannot be empty.',
        'name_min' => 'Name must be at least 4 characters.',
        'name_max' => 'Name cannot exceed 255 characters.',
        'address_invalid' => 'Invalid address.',
        'address_max' => 'Address cannot exceed 255 characters.',
        'introduce_invalid' => 'Invalid introduction.',
        'password_required' => 'Password cannot be empty.',
        'password_min' => 'Password must be at least 8 characters.',
        'confirm_password_same' => 'Password confirmation does not match.',
        'date_invalid' => 'Invalid date.',
        'date_before' => 'Date cannot be after current date.',
    ],
];
