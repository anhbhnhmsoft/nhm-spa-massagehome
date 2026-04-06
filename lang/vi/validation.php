<?php

return [
    'required' => 'Thông tin này là bắt buộc.',
    'phone' => [
        'required' => 'Bạn hãy nhập số điện thoại.',
        'required_if' => 'Số điện thoại là bắt buộc khi bạn đăng ký bằng Email.',
        'invalid' => 'Số điện thoại không hợp lệ định dạng số Việt Nam.',
        'exists' => 'Số điện thoại đã tồn tại.',
    ],
    'password' => [
        'required' => 'Mật khẩu không hợp lệ.',
        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        'regex' => 'Mật khẩu phải chứa ít nhất một chữ hoa, một chữ thường và một số.',
    ],
    'user' => [
        'name_required' => 'Vui lòng nhập tên.',
        'phone_required' => 'Vui lòng nhập số điện thoại.',
    ],
    'name' => [
        'required' => 'Tên không hợp lệ.',
        'string' => 'Tên phải là chuỗi ký tự.',
        'max' => 'Tên phải có ít nhất :max ký tự.',
    ],
    'gender' => [
        'required' => 'Giới tính không hợp lệ.',
        'in' => 'Giới tính không hợp lệ.',
    ],
    'language' => [
        'required' => 'Ngôn ngữ không hợp lệ.',
        'in' => 'Ngôn ngữ không hợp lệ.',
    ],
    'service_id' => [
        'required' => 'Vui lòng chọn dịch vụ.',
        'numeric' => 'Dịch vụ không hợp lệ.',
        'exists' => 'Dịch vụ không tồn tại.',
    ],
    'book_time' => [
        'required' => 'Vui lòng chọn thời gian.',
        'date' => 'Thời gian không hợp lệ.',
        'after' => 'Thời gian phải sau thời điểm hiện tại 1 tiếng.',
        'timestamp' => 'Thời gian không hợp lệ.',
    ],
    'option_id' => [
        'required' => 'Vui lòng chọn gói dịch vụ.',
        'numeric' => 'Gói dịch vụ không hợp lệ.',
        'exists' => 'Gói dịch vụ không tồn tại.',
    ],
    'option_ids' => [
        'required' => 'Vui lòng chọn gói dịch vụ.',
        'invalid' => 'Gói dịch vụ không hợp lệ.',
    ],

    'coupon_id' => [
        'exists' => 'Mã giảm giá không tồn tại.',
    ],
    'address' => [
        'required' => 'Vui lòng nhập địa chỉ.',
        'string' => 'Địa chỉ phải là chuỗi ký tự.',
        'max' => 'Địa chỉ phải có ít nhất :max ký tự.',
        'invalid' => 'Địa chỉ không hợp lệ.',
    ],
    'lat' => [
        'required' => 'Vui lòng nhập tọa độ latitude.',
        'numeric' => 'Tọa độ latitude phải là số.',
        'invalid' => 'Tọa độ latitude phải trong khoảng -90 đến 90.',
    ],
    'lng' => [
        'required' => 'Vui lòng nhập tọa độ longitude.',
        'numeric' => 'Tọa độ longitude phải là số.',
        'invalid' => 'Tọa độ longitude phải trong khoảng -180 đến 180.',
    ],
    'agency_not_found' => 'Không tìm thấy mã Agency này hoặc Agency không hoạt động.',
    'duration' => [
        'required' => 'Vui lòng chọn thời gian.',
        'in' => 'Thời gian không hợp lệ.',
    ],
    'amount' => [
        'required' => 'Vui lòng nhập số tiền.',
        'numeric' => 'Số tiền phải là số.',
        'min' => 'Số tiền phải lớn hơn 0.',
        'max' => 'Số tiền phải nhỏ hơn 50.000.000.',
    ],
    'payment_type' => [
        'required' => 'Vui lòng chọn hình thức thanh toán.',
        'in' => 'Hình thức thanh toán không hợp lệ.',
    ],
    'transaction_id' => [
        'required' => 'Vui lòng nhập mã giao dịch.',
        'numeric' => 'Mã giao dịch phải là số.',
        'exists' => 'Mã giao dịch không tồn tại trong hệ thống.',
    ],
    'location' => [
        'keyword_required' => 'Từ khóa không được để trống',
        'keyword_string' => 'Từ khóa phải là chuỗi',
        'radius_numeric' => 'Khoảng cách phải là số',
        'limit_numeric' => 'Giới hạn phải là số',
        'place_id_required' => 'ID địa điểm không được để trống',
        'place_id_string' => 'ID địa điểm phải là chuỗi',
        'address_string' => 'Địa chỉ phải là chuỗi',
        'address_required' => 'Địa chỉ không được để trống',
        'latitude_required' => 'Vĩ độ không được để trống',
        'latitude_numeric' => 'Vĩ độ phải là số',
        'latitude_between' => 'Vĩ độ phải trong khoảng -90 đến 90',
        'longitude_required' => 'Kinh độ không được để trống',
        'longitude_numeric' => 'Kinh độ phải là số',
        'longitude_between' => 'Kinh độ phải trong khoảng -180 đến 180',
        'desc_string' => 'Mô tả phải là chuỗi',
        'is_primary_boolean' => 'is_primary phải là boolean',
    ],
    'note' => [
        'max' => 'Ghi chú không được vượt quá 500 ký tự.',
    ],
    'note_address' => [
        'max' => 'Note Địa chỉ không được vượt quá 500 ký tự.',
    ],
    'coupon' => [
        'required' => 'Vui lòng chọn mã giảm giá.',
        'invalid' => 'Mã giảm giá không hợp lệ.',
        'exists' => 'Mã giảm giá không tồn tại.',
        'array' => 'Mã giảm giá phải là mảng.',
        'collect_error' => 'Không thể dùng mã :code vào thời điểm này',
        'collect_limit_error' => 'Mã :code đã hết lượt thu thập hôm nay',
    ],
    'rating' => [
        'required' => 'Vui lòng đánh giá.',
        'integer' => 'Đánh giá phải là số.',
        'min' => 'Đánh giá phải lớn hơn 0.',
        'max' => 'Đánh giá phải nhỏ hơn 5.',
    ],
    'hidden' => [
        'boolean' => 'Ẩn không hợp lệ.',
    ],
    'service_booking_id' => [
        'required' => 'Vui lòng chọn booking dịch vụ.',
        'numeric' => 'Booking dịch vụ phải là số.',
        'exists' => 'Booking dịch vụ không tồn tại.',
    ],
    'ktv_id' => [
        'required' => 'Vui lòng chọn KTV.',
        'numeric' => 'ID KTV phải là số.',
        'exists' => 'ID KTV không tồn tại.',
    ],
    'user_id' => [
        'required' => 'Vui lòng chọn khách hàng :field.',
        'numeric' => 'ID khách hàng phải là số.',
        'exists' => 'ID khách hàng không tồn tại.',
    ],
    'room_id' => [
        'required' => 'Vui lòng chọn phòng chat.',
        'numeric' => 'ID phòng chat phải là số.',
        'exists' => 'ID phòng chat không tồn tại.',
    ],
    'content' => [
        'required' => 'Vui lòng nhập nội dung tin nhắn.',
        'string' => 'Nội dung tin nhắn phải là chuỗi.',
        'max' => 'Nội dung tin nhắn không được vượt quá :max ký tự.',
    ],
    'role' => [
        'required' => 'Vui lòng chọn vai trò.',
        'in' => 'Vai trò không hợp lệ.',
        'invalid' => 'Vai trò không hợp lệ.',
    ],
    'referrer_id' => [
        'required' => 'Vui lòng chọn Người giới thiệu.',
        'numeric' => 'ID Người giới thiệu phải là số.',
        'exists' => 'ID Người giới thiệu không tồn tại.',
    ],
    'province_code' => [
        'required' => 'Vui lòng chọn tỉnh/thành phố.',
        'string' => 'Tỉnh/thành phố phải là chuỗi.',
        'max' => 'Tỉnh/thành phố không được vượt quá 10 ký tự.',
    ],
    'type_withdraw_info' => [
        'required' => 'Vui lòng chọn loại rút tiền.',
        'in' => 'Loại rút tiền không hợp lệ.',
    ],
    'config_withdraw_info' => [
        'required' => 'Vui lòng nhập thông tin rút tiền.',
        'invalid' => 'Thông tin rút tiền không hợp lệ.',
        'missing_field' => 'Thông tin rút tiền đang thiếu các trường cần thiết.',
    ],
    'user_withdraw_info' => [
        'invalid' => 'Thông tin rút tiền không hợp lệ.',
    ],
    'category_id' => [
        'required' => 'Vui lòng chọn danh mục dịch vụ.',
        'invalid' => 'Danh mục dịch vụ không hợp lệ.',
    ],
    'image' => [
        'required' => 'Vui lòng tải lên ảnh đại diện.',
        'max' => 'Ảnh không được vượt quá 20MB.',
        'mimes' => 'Định dạng ảnh không hợp lệ (chỉ chấp nhận jpeg, png, jpg).',
    ],
    'name_service' => [
        'required' => 'Vui lòng nhập tên dịch vụ ít nhất bằng 1 ngôn ngữ.',
        'invalid' => 'Dữ liệu tên dịch vụ không đúng định dạng.',
        'max' => 'Tên dịch vụ quá dài (tối đa 255 ký tự).',
    ],
    'description_service' => [
        'required' => 'Vui lòng nhập mô tả dịch vụ ít nhất bằng 1 ngôn ngữ.',
        'invalid' => 'Dữ liệu mô tả dịch vụ không đúng định dạng.',
        'max' => 'Mô tả quá dài (tối đa 1000 ký tự).',
    ],
    'option_service' => [
        'required' => 'Vui lòng thêm ít nhất 1 lựa chọn dịch vụ.',
        'invalid' => 'Dữ liệu lựa chọn giá dịch vụ không đúng với danh mục của gói.',
        'distinct' => 'Dữ liệu lựa chọn giá dịch vụ không được trùng lặp.',
    ],
    'files' => [
        'required_with' => 'Vui lòng tải lên ít nhất 1 ảnh.',
    ],
    'from_date' => [
        'required' => 'Vui lòng nhập ngày bắt đầu.',
        'date' => 'Ngày bắt đầu không hợp lệ.',
        'date_format' => 'Ngày bắt đầu phải có định dạng yyyy-MM-dd.',
    ],
    'to_date' => [
        'required' => 'Vui lòng nhập ngày kết thúc.',
        'date' => 'Ngày kết thúc không hợp lệ.',
        'date_format' => 'Ngày kết thúc phải có định dạng yyyy-MM-dd.',
        'after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
    ],
    'direction' => [
        'in' => 'Hướng sắp xếp không hợp lệ.',
    ],
    'type_date_range' => [
        'in' => 'Kiểu thời gian hiển thị không hợp lệ.',
        'required' => 'Vui lòng chọn kiểu thời gian hiển thị.',
    ],
    'images' => [
        'required' => 'Vui lòng tải lên ít nhất 1 ảnh.',
        'array' => 'Danh sách ảnh phải là mảng.',
        'min' => 'Danh sách ảnh phải có ít nhất 1 phần tử.',
        'string' => 'Đường dẫn ảnh phải là chuỗi.',
        'mimes' => 'File phải là hình ảnh',
        'image' => 'File phải là hình ảnh',
        'max' => 'Dung lượng hình ảnh tối đa :max',
    ],
    'date_of_birth' => [
        'required' => 'Vui lòng nhập ngày sinh.',
        'date' => 'Ngày sinh không hợp lệ.',
        'invalid' => 'Ngày sinh không hợp lệ.',
        'date_format' => 'Ngày sinh phải có định dạng yyyy-MM-dd.',
    ],
    'old_pass' => [
        'required' => 'Vui lòng nhập mật khẩu cũ.',
        'string' => 'Mật khẩu cũ phải là chuỗi.',
    ],
    'new_pass' => [
        'required' => 'Vui lòng nhập mật khẩu mới.',
        'string' => 'Mật khẩu mới phải là chuỗi.',
    ],
    'bio' => [
        'required' => 'Vui lòng nhập mô tả ít nhất bằng 1 ngôn ngữ.',
        'invalid' => 'Dữ liệu mô tả không đúng định dạng.',
        'min' => 'Mô tả quá ngắn (tối thiểu :min ký tự).',
        'max' => 'Mô tả  quá dài (tối đa :max ký tự).',
        'vi' => [
            'required' => 'Vui lòng nhập mô tả tiếng Việt.',
            'string' => 'Mô tả tiếng Việt phải là chuỗi.',
            'max' => 'Mô tả tiếng Việt quá dài (tối đa 1000 ký tự).',
            'invalid' => 'Mô tả tiếng Việt không hợp lệ.',
        ],
        'cn' => [
            'required' => 'Vui lòng nhập mô tả tiếng Trung.',
            'string' => 'Mô tả tiếng Trung phải là chuỗi.',
            'max' => 'Mô tả tiếng Trung quá dài (tối đa 1000 ký tự).',
            'invalid' => 'Mô tả tiếng Trung không hợp lệ.',
        ],
        'en' => [
            'required' => 'Vui lòng nhập mô tả tiếng Anh.',
            'string' => 'Mô tả tiếng Anh phải là chuỗi.',
            'max' => 'Mô tả tiếng Anh quá dài (tối đa 1000 ký tự).',
            'invalid' => 'Mô tả tiếng Anh không hợp lệ.',
        ],
    ],
    'avatar' => [
        'required' => 'Vui lòng tải lên ảnh đại diện.',
        'invalid' => 'Avatar phải là file hình ảnh (jpg, jpeg, png).',
        'max' => 'Dung lượng ảnh đại diện tối đa :max MB.',
    ],
    'experience' => [
        'required' => 'Vui lòng nhập kinh nghiệm.',
        'integer' => 'Kinh nghiệm phải là số nguyên.',
        'min' => 'Kinh nghiệm phải lớn hơn hoặc bằng 0.',
    ],
    'file_apply_partner_uploads' => [
        'required' => 'Vui lòng tải lên ít nhất 1 ảnh.',
        'array' => 'Danh sách ảnh phải là mảng.',
        'invalid' => 'Dữ liệu ảnh không đúng định dạng và tối đa :max MB mỗi ảnh.',
        'invalid_type' => 'Bạn đang gửi ảnh không hợp lệ.',
        'invalid_type_for_role' => 'Loại ảnh không hợp lệ cho vai trò này.',
        'duplicate_type' => 'Có ảnh đang bị trùng lặp.',
        'invalid_type_count' => 'Số lượng ảnh cho loại này không hợp lệ.',
        'missing' => [
            'cccd_front' => 'Thiếu yêu cầu ảnh CCCD mặt trước.',
            'cccd_back' => 'Thiếu yêu cầu ảnh CCCD mặt sau.',
            'license' => 'Thiếu yêu cầu ảnh giấy phép hoạt đông.',
            'face_with_id' => 'Thiếu yêu cầu ảnh khuôn mặt với CCCD.',
            'ktv_image_display' => 'Thiếu yêu cầu ảnh hiển thị cho khách hàng.',
        ],
        'count' => [
            'cccd_front' => 'Chỉ có thể tải lên :count ảnh CCCD mặt trước.',
            'cccd_back' => 'Chỉ có thể tải lên :count ảnh CCCD mặt sau.',
            'license' => 'Chỉ có thể tải lên :count ảnh giấy phép hoạt động.',
            'face_with_id' => 'Chỉ có thể tải lên :count ảnh khuôn mặt với CCCD.',
            'ktv_image_display' => 'Chỉ có thể tải lên :min - :max ảnh hiển thị cho khách hàng.',
        ],

    ],
    'is_working' => [
        'required' => 'Vui lòng chọn trạng thái.',
        'invalid' => 'Trạng thái không hợp lệ.',
    ],
    'working_schedule' => [
        'required' => 'Vui lòng nhập lịch làm việc.',
        'array' => 'Danh sách lịch làm việc phải là mảng.',
        'size' => 'Danh sách lịch làm việc phải có 7 phần tử.',
        'day_key' => [
            'required' => 'Vui lòng chọn ngày.',
        ],
        'active' => [
            'required' => 'Vui lòng chọn trạng thái.',
        ],
        'start_time' => [
            'required_if' => 'Vui lòng nhập giờ bắt đầu.',
            'date_format' => 'Giờ bắt đầu không hợp lệ.',
        ],
        'end_time' => [
            'required_if' => 'Vui lòng nhập giờ kết thúc.',
            'date_format' => 'Giờ kết thúc không hợp lệ.',
            'after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ],
    ],
    'type_contract' => [
        'required' => 'Vui lòng chọn loại hợp đồng.',
        'integer' => 'Loại hợp đồng phải không hợp lệ.',
        'in' => 'Loại hợp đồng không hợp lệ.',
    ],
    'nickname' => [
        'required_if' => 'Vui lòng nhập tên hiển thị.',
        'invalid' => 'Tên hiển thị phải là chuỗi và ít nhất 4 ký tự, tối đa 255 ký tự.',
        'for_real' => [
            'required' => 'Vui lòng nhập tên thật.',
            'invalid' => 'Tên thật phải là chuỗi và ít nhất 4 ký tự, tối đa 255 ký tự.',
        ]
    ],
    'message_id' => [
        'required' => 'Vui lòng nhập ID tin nhắn.',
        'numeric' => 'ID tin nhắn phải là số.',
        'exists' => 'Tin nhắn không tồn tại.',
    ],
    'review_id' => [
        'required' => 'Vui lòng nhập ID đánh giá.',
        'invalid' => 'Đánh giá không tồn tại hoặc không hợp lệ.',
    ],
    'lang' => [
        'required' => 'Vui lòng chọn ngôn ngữ.',
        'invalid' => 'Ngôn ngữ không hợp lệ.',
    ],
    'username' => [
        'required' => 'Tên đăng nhập không hợp lệ.',
        'string' => 'Tên đăng nhập phải là chuỗi ký tự.',
        'email_or_phone' => 'Tên đăng nhập phải là email hoặc số điện thoại hợp lệ.',
    ],
    'type_authenticate' => [
        'required' => 'Vui lòng chọn phương thức xác thực.',
        'invalid' => 'Phương thức xác thực không hợp lệ.',
    ]
];
