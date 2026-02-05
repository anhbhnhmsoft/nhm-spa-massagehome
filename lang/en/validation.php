<?php

return [
    'required' => 'This information is required.',
    'password' => [
        'required' => 'Invalid password.',
        'min' => 'Password must be at least :min characters.',
        'regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
    ],
    'user' => [
        'name_required' => 'Please enter name.',
        'phone_required' => 'Please enter phone number.',
    ],
    'name' => [
        'required' => 'Invalid name.',
        'string' => 'Name must be a string.',
        'max' => 'Name must be at least :max characters.',
    ],
    'gender' => [
        'required' => 'Invalid gender.',
        'in' => 'Invalid gender.',
    ],
    'language' => [
        'required' => 'Invalid language.',
        'in' => 'Invalid language.',
    ],
    'service_id' => [
        'required' => 'Please select service.',
        'numeric' => 'Invalid service.',
        'exists' => 'Service does not exist.',
    ],
    'book_time' => [
        'required' => 'Please select time.',
        'date' => 'Invalid time.',
        'after' => 'Time must be 1 hour after current time.',
        'timestamp' => 'Invalid time.',
    ],
    'option_id' => [
        'required' => 'Please select service package.',
        'numeric' => 'Invalid service package.',
        'exists' => 'Service package does not exist.',
    ],
    'coupon_id' => [
        'exists' => 'Coupon does not exist.',
    ],
    'address' => [
        'required' => 'Please enter address.',
        'string' => 'Address must be a string.',
        'max' => 'Address must be at least :max characters.',
        'invalid' => 'Invalid address.',
    ],
    'lat' => [
        'required' => 'Please enter latitude.',
        'numeric' => 'Latitude must be a number.',
        'invalid' => 'Latitude must be between -90 and 90.',
    ],
    'lng' => [
        'required' => 'Please enter longitude.',
        'numeric' => 'Longitude must be a number.',
        'invalid' => 'Longitude must be between -180 and 180.',
    ],
    'agency_not_found' => 'Agency code not found or Agency is inactive.',
    'duration' => [
        'required' => 'Please select duration.',
        'in' => 'Invalid duration.',
    ],
    'amount' => [
        'required' => 'Please enter amount.',
        'numeric' => 'Amount must be a number.',
        'min' => 'Amount must be greater than 0.',
        'max' => 'Amount must be less than 50,000,000.',
    ],
    'payment_type' => [
        'required' => 'Please select payment method.',
        'in' => 'Invalid payment method.',
    ],
    'transaction_id' => [
        'required' => 'Please enter transaction code.',
        'numeric' => 'Transaction code must be a number.',
        'exists' => 'Transaction code does not exist in the system.',
    ],
    'location' => [
        'keyword_required' => 'Keyword cannot be empty',
        'keyword_string' => 'Keyword must be a string',
        'radius_numeric' => 'Radius must be a number',
        'limit_numeric' => 'Limit must be a number',
        'place_id_required' => 'Place ID cannot be empty',
        'place_id_string' => 'Place ID must be a string',
        'address_string' => 'Address must be a string',
        'address_required' => 'Address cannot be empty',
        'latitude_required' => 'Latitude cannot be empty',
        'latitude_numeric' => 'Latitude must be a number',
        'latitude_between' => 'Latitude must be between -90 and 90',
        'longitude_required' => 'Longitude cannot be empty',
        'longitude_numeric' => 'Longitude must be a number',
        'longitude_between' => 'Longitude must be between -180 and 180',
        'desc_string' => 'Description must be a string',
        'is_primary_boolean' => 'is_primary must be a boolean',
    ],
    'note' => [
        'max' => 'Note cannot exceed 500 characters.',
    ],
    'note_address' => [
        'max' => 'Address cannot exceed 500 characters.',
    ],
    'coupon' => [
        'required' => 'Please select coupon.',
        'exists' => 'Coupon does not exist.',
        'array' => 'Coupon must be an array.',
        'collect_error' => 'Cannot collect code :code at this time',
        'collect_limit_error' => 'Code :code has reached collect limit for today',
    ],
    'rating' => [
        'required' => 'Please rate.',
        'integer' => 'Rating must be a number.',
        'min' => 'Rating must be greater than 0.',
        'max' => 'Rating must be less than 5.',
    ],
    'hidden' => [
        'boolean' => 'Invalid hidden value.',
    ],
    'service_booking_id' => [
        'required' => 'Please select service booking.',
        'numeric' => 'Service booking must be a number.',
        'exists' => 'Service booking does not exist.',
    ],
    'ktv_id' => [
        'required' => 'Please select KTV.',
        'numeric' => 'KTV ID must be a number.',
        'exists' => 'KTV ID does not exist.',
    ],
    'user_id' => [
        'required' => 'Please select customer :field.',
        'numeric' => 'Customer ID must be a number.',
        'exists' => 'Customer ID does not exist.',
    ],
    'room_id' => [
        'required' => 'Please select chat room.',
        'numeric' => 'Chat room ID must be a number.',
        'exists' => 'Chat room ID does not exist.',
    ],
    'content' => [
        'required' => 'Please enter message content.',
        'string' => 'Message content must be a string.',
        'max' => 'Message content must not exceed :max characters.',
    ],
    'role' => [
        'required' => 'Please select role.',
        'in' => 'Invalid role.',
        'invalid' => 'Invalid role.',
    ],
    'referrer_id' => [
        'required' => 'Please select Referrer.',
        'numeric' => 'Referrer ID must be a number.',
        'exists' => 'Referrer ID does not exist.',
    ],
    'province_code' => [
        'required' => 'Please select province/city.',
        'string' => 'Province/city must be a string.',
        'max' => 'Province/city cannot exceed 10 characters.',
    ],
    'type_withdraw_info' => [
        'required' => 'Please select withdrawal type.',
        'in' => 'Invalid withdrawal type.',
    ],
    'config_withdraw_info' => [
        'required' => 'Please enter withdrawal info.',
        'invalid' => 'Invalid withdrawal info.',
        'missing_field' => 'Withdrawal info is missing required fields.',
    ],
    'user_withdraw_info' => [
        'invalid' => 'Invalid withdrawal info.',
    ],
    'category_id' => [
        'required' => 'Please select service category.',
        'invalid' => 'Invalid service category.',
    ],
    'image' => [
        'required' => 'Please upload avatar.',
        'max'      => 'Image must not exceed 20MB.',
        'mimes'    => 'Invalid image format (only jpeg, png, jpg accepted).',
    ],
    'name_service' => [
        'required' => 'Please enter service name in at least 1 language.',
        'invalid' => 'Invalid service name format.',
        'max'    => 'Service name too long (max 255 characters).',
    ],
    'description_service' => [
        'required' => 'Please enter service description in at least 1 language.',
        'invalid' => 'Invalid service description format.',
        'max'    => 'Description too long (max 1000 characters).',
    ],
    'option_service' => [
        'required' => 'Please add at least 1 service option.',
        'invalid' => 'Service price selection data does not match package category.',
        'distinct' => 'Service price selection data must not be duplicated.',
    ],
    'files' => [
        'required_with' => 'Please upload at least 1 image.',
    ],
    'from_date' => [
        'required' => 'Please enter start date.',
        'date' => 'Invalid start date.',
        'date_format' => 'Start date must be in yyyy-MM-dd format.',
    ],
    'to_date' => [
        'required' => 'Please enter end date.',
        'date' => 'Invalid end date.',
        'date_format' => 'End date must be in yyyy-MM-dd format.',
        'after_or_equal' => 'End date must be greater than or equal to start date.',
    ],
    'direction' => [
        'in' => 'Invalid sort direction.',
    ],
    'type_date_range' => [
        'in' => 'Invalid display time type.',
        'required' => 'Please select display time type.',
    ],
    'images' => [
        'required' => 'Please upload at least 1 image.',
        'array' => 'Image list must be an array.',
        'min' => 'Image list must have at least 1 item.',
        'string' => 'Image path must be a string.',
        'mimes' => 'File must be an image',
        'image' => 'File must be an image',
        'max' => 'Max image size :max',
    ],
    'date_of_birth' => [
        'required' => 'Please enter date of birth.',
        'date' => 'Invalid date of birth.',
        'date_format' => 'Date of birth must be in yyyy-MM-dd format.',
    ],
    'old_pass' => [
        'required' => 'Please enter old password.',
        'string' => 'Old password must be a string.',
    ],
    'new_pass' => [
        'required' => 'Please enter new password.',
        'string' => 'New password must be a string.',
    ],
    'bio' => [
        'required' => 'Please enter description in at least 1 language.',
        'invalid' => 'Invalid description format.',
        'vi' => [
            'required' => 'Please enter Vietnamese description.',
            'string' => 'Vietnamese description must be a string.',
            'max' => 'Vietnamese description too long (max 1000 characters).',
            'invalid' => 'Invalid Vietnamese description.',
        ],
        'cn' => [
            'required' => 'Please enter Chinese description.',
            'string' => 'Chinese description must be a string.',
            'max' => 'Chinese description too long (max 1000 characters).',
            'invalid' => 'Invalid Chinese description.',
        ],
        'en' => [
            'required' => 'Please enter English description.',
            'string' => 'English description must be a string.',
            'max' => 'English description too long (max 1000 characters).',
            'invalid' => 'Invalid English description.',
        ],
    ],
    'experience' => [
        'required' => 'Please enter experience.',
        'integer' => 'Experience must be an integer.',
        'min' => 'Experience must be greater than or equal to 0.',
    ],
    'file_apply_partner_uploads' => [
        'required' => 'Please upload at least 1 image.',
        'array' => 'Image list must be an array.',
        'invalid' => 'Invalid image format and max 10MB per image.',
        'invalid_type' => 'Invalid image type.',
        'invalid_type_for_role' => 'Invalid image type for this role.',
        'duplicate_type' => 'Some images are duplicated.',
        'invalid_type_count' => 'Invalid number of images for this type.',
        'missing_type' => 'Missing required image type.',
    ],
    'is_working' => [
        'required' => 'Please select status.',
        'invalid' => 'Invalid status.',
    ],
    'working_schedule' => [
        'required' => 'Please enter working schedule.',
        'array' => 'Working schedule must be an array.',
        'size' => 'Working schedule must have 7 elements.',
        'day_key' => [
            'required' => 'Please select day.',
        ],
        'active' => [
            'required' => 'Please select status.',
        ],
        'start_time' => [
            'required_if' => 'Please enter start time.',
            'date_format' => 'Invalid start time.',
        ],
        'end_time' => [
            'required_if' => 'Please enter end time.',
            'date_format' => 'Invalid end time.',
            'after' => 'End time must be after start time.',
        ],
    ],
    'type_contract' => [
        'required' => 'Please select contract type.',
        'integer' => 'Invalid contract type.',
        'in' => 'Invalid contract type.',
    ],
    'nickname' => [
        'required_if' => 'Please enter display name.',
        'nullable' => 'Display name must be a string.',
        'string' => 'Display name must be a string.',
        'max' => 'Display name too long (max 255 characters).',
    ],
];
