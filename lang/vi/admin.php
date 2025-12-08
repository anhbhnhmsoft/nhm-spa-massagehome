<?php
return
    [
        "nav" => [
            "ktv" => "Quản lý kỹ thuật viên",
        ],
        "user" => [
            "label" => "Kỹ thuật viên"
        ],
        "common" => [
            'table' => [
                'name' => 'Tên',
                'email' => 'Email',
                'phone' => 'Số điện thoại',
                'address' => 'Địa chỉ',
                'created_at' => 'Ngày tạo',
                'updated_at' => 'Ngày cập nhật',
                'deleted_at' => 'Ngày xóa',
                'avatar' => 'Ảnh đại diện',
                'phone' => 'Số điện thoại',
                'date_of_birth' => 'Ngày sinh',
                'gender' => 'Giới tính',
                'status' => 'Trạng thái',
                'last_login' => 'Lần đăng nhập cuối',
                'basic_info' => 'Thông tin cơ bản',
                'account_info' => 'Thông tin tài khoản',
                'role' => 'Vai trò',
                'password' => 'Mật khẩu',
                'bio' => 'Giới thiệu',
                'is_online' => 'Trạng thái online',
            ],
            'action' => [
                'view' => 'Xem',
                'edit' => 'Sửa',
                'delete' => 'Xóa',
                'restore' => 'Khôi phục',
                'force_delete' => 'Xóa vĩnh viễn',
                'confirm_delete' => 'Xác nhận xóa',
                'export_excel' => 'Xuất dữ liệu',
                'import' => 'Import dữ liệu',
                'upload_excel' => 'Tải lên file .xlsx chưa thông tin sản phẩm',
                'add' => 'Thêm mới',
                'download_template' => 'Tải mẫu xuống',
                'save' => 'Lưu',
                'cancel' => 'Hủy',
                'add' => 'Thêm'

            ],
            'tooltip' => [
                'view' => 'Xem chi tiết ',
                'edit' => 'Chỉnh sửa ',
                'delete' => 'Xóa này',
                'restore' => 'Khôi phục đã xóa',
                'force_delete' => 'Xóa vĩnh viễn khỏi hệ thống',
            ],
            'modal' => [
                'delete_title' => 'Xóa ',
                'delete_confirm' => 'Bạn có chắc chắn muốn xóa đã chọn?',
            ],
            'gender' => [
                'male' => 'Nam',
                'female' => 'Nữ',
            ],
            'filter' => [
                'gender' => 'Giới tính',
                'status' => 'Trạng thái',
            ],
            'status' => [
                'active' => 'Kích hoạt',
                'inactive' => 'Tắt',
            ],
            'empty' => 'Không có dữ liệu',

        ],
        "ktv_apply" => [
            "nav" => "Quản lý đăng ký",
            "label" => "KTV đăng ký",
            "status" => [
                "pending" => "Chờ duyệt",
                "approved" => "Đã duyệt",
                "rejected" => "Từ chối",
            ],
            "file_type" => [
                "identity_card_front" => "CMND/CCCD mặt trước",
                "identity_card_back" => "CMND/CCCD mặt sau",
                "license" => "Bằng cấp/Chứng chỉ",
                "health_insurance" => "Bảo hiểm y tế",
            ],
            "fields" => [
                "experience" => "Kinh nghiệm",
                "skills" => "Kỹ năng",
                "bio" => "Giới thiệu bản thân",
                "experience_desc" => "Mô tả kinh nghiệm",
                "province" => "Tỉnh/Thành phố",
                "address" => "Địa chỉ chi tiết",
                "files" => "Hồ sơ đính kèm",
                "file_type" => "Loại file",
                "file_name" => "Tên file",
                "years" => "năm",
                "system_info" => "Thông tin hệ thống",
                "registration_info" => "Thông tin đăng ký",
                "personal_info" => "Thông tin cá nhân",
            ],
            "actions" => [
                "approve" => [
                    "label" => "Duyệt hồ sơ",
                    "heading" => "Duyệt hồ sơ KTV",
                    "description" => "Bạn có chắc chắn muốn duyệt hồ sơ này?",
                    "success_title" => "Duyệt hồ sơ thành công",
                    "success_body" => "Hồ sơ KTV đã được duyệt.",
                ],
                "reject" => [
                    "label" => "Từ chối hồ sơ",
                    "heading" => "Từ chối hồ sơ KTV",
                    "description" => "Bạn có chắc chắn muốn từ chối hồ sơ này?",
                    "reason_label" => "Lý do từ chối",
                    "success_title" => "Từ chối hồ sơ thành công",
                    "success_body" => "Hồ sơ KTV đã bị từ chối.",
                ],
            ],
        ],
        "ktv" => [
            "label" => "Kỹ thuật viên"
        ],
        "user_role" => [
            "customer" => "Khách hàng",
            "ktv" => "Kỹ thuật viên",
            "agency" => "Đối tác",
            "admin" => "Quản trị viên",
        ],
        'notification' => [
            'success' => [
                'update_success' => 'Cập nhật thành công',
                'create_success' => 'Tạo thành công',
            ],
            'error' => [
                'update_error' => 'Cập nhật thất bại',
                'create_error' => 'Tạo thất bại',
            ],
        ]
    ];
