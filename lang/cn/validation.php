<?php

return [
    'required' => '此信息为必填项。',
    'password' => [
        'required' => '无效的密码。',
        'min' => '密码至少包含 :min 个字符。',
        'regex' => '密码必须包含至少一个大写字母、一个小写字母和一个数字。',
    ],
    'user' => [
        'name_required' => '请输入姓名。',
        'phone_required' => '请输入电话号码。',
    ],
    'name' => [
        'required' => '无效的姓名。',
        'string' => '姓名必须是字符串。',
        'max' => '姓名必须至少包含 :max 个字符。',
    ],
    'gender' => [
        'required' => '无效的性别。',
        'in' => '无效的性别。',
    ],
    'language' => [
        'required' => '无效的语言。',
        'in' => '无效的语言。',
    ],
    'service_id' => [
        'required' => '请选择服务。',
        'numeric' => '无效的服务。',
        'exists' => '服务不存在。',
    ],
    'book_time' => [
        'required' => '请选择时间。',
        'date' => '无效的时间。',
        'after' => '时间必须晚于当前时间 1 小时。',
        'timestamp' => '无效的时间。',
    ],
    'option_id' => [
        'required' => '请选择服务套餐。',
        'numeric' => '无效的服务套餐。',
        'exists' => '服务套餐不存在。',
    ],
    'coupon_id' => [
        'exists' => '优惠券不存在。',
    ],
    'address' => [
        'required' => '请输入地址。',
        'string' => '地址必须是字符串。',
        'max' => '地址必须至少包含 :max 个字符。',
        'invalid' => '无效的地址。',
    ],
    'lat' => [
        'required' => '请输入纬度。',
        'numeric' => '纬度必须是数字。',
        'invalid' => '纬度必须在 -90 到 90 之间。',
    ],
    'lng' => [
        'required' => '请输入经度。',
        'numeric' => '经度必须是数字。',
        'invalid' => '经度必须在 -180 到 180 之间。',
    ],
    'agency_not_found' => '未找到此 Agency 代码或 Agency 未激活。',
    'duration' => [
        'required' => '请选择时长。',
        'in' => '无效的时长。',
    ],
    'amount' => [
        'required' => '请输入金额。',
        'numeric' => '金额必须是数字。',
        'min' => '金额必须大于 0。',
        'max' => '金额必须小于 50,000,000。',
    ],
    'payment_type' => [
        'required' => '请选择支付方式。',
        'in' => '无效的支付方式。',
    ],
    'transaction_id' => [
        'required' => '请输入交易代码。',
        'numeric' => '交易代码必须是数字。',
        'exists' => '系统中不存在此交易代码。',
    ],
    'location' => [
        'keyword_required' => '关键词不能为空',
        'keyword_string' => '关键词必须是字符串',
        'radius_numeric' => '半径必须是数字',
        'limit_numeric' => '限制必须是数字',
        'place_id_required' => '地点 ID 不能为空',
        'place_id_string' => '地点 ID 必须是字符串',
        'address_string' => '地址必须是字符串',
        'address_required' => '地址不能为空',
        'latitude_required' => '纬度不能为空',
        'latitude_numeric' => '纬度必须是数字',
        'latitude_between' => '纬度必须在 -90 到 90 之间',
        'longitude_required' => '经度不能为空',
        'longitude_numeric' => '经度必须是数字',
        'longitude_between' => '经度必须在 -180 到 180 之间',
        'desc_string' => '描述必须是字符串',
        'is_primary_boolean' => 'is_primary 必须是布尔值',
    ],
    'note' => [
        'max' => '备注不能超过 500 个字符。',
    ],
    'note_address' => [
        'max' => '地址不能超过 500 个字符。',
    ],
    'coupon' => [
        'required' => '请选择优惠券。',
        'exists' => '优惠券不存在。',
        'array' => '优惠券必须是数组。',
        'collect_error' => '此时无法领取代码 :code',
        'collect_limit_error' => '代码 :code 今日已达领取上限',
    ],
    'rating' => [
        'required' => '请评分。',
        'integer' => '评分必须是数字。',
        'min' => '评分必须大于 0。',
        'max' => '评分必须小于 5。',
    ],
    'hidden' => [
        'boolean' => '无效的隐藏值。',
    ],
    'service_booking_id' => [
        'required' => '请选择服务预订。',
        'numeric' => '服务预订必须是数字。',
        'exists' => '服务预订不存在。',
    ],
    'ktv_id' => [
        'required' => '请选择技师。',
        'numeric' => '技师 ID 必须是数字。',
        'exists' => '技师 ID 不存在。',
    ],
    'user_id' => [
        'required' => '请选择客户 :field。',
        'numeric' => '客户 ID 必须是数字。',
        'exists' => '客户 ID 不存在。',
    ],
    'room_id' => [
        'required' => '请选择聊天室。',
        'numeric' => '聊天室 ID 必须是数字。',
        'exists' => '聊天室 ID 不存在。',
    ],
    'content' => [
        'required' => '请输入消息内容。',
        'string' => '消息内容必须是字符串。',
        'max' => '消息内容不能超过 :max 个字符。',
    ],
    'role' => [
        'required' => '请选择角色。',
        'in' => '无效的角色。',
        'invalid' => '无效的角色。',
    ],
    'referrer_id' => [
        'required' => '请选择推荐人。',
        'numeric' => '推荐人 ID 必须是数字。',
        'exists' => '推荐人 ID 不存在。',
    ],
    'province_code' => [
        'required' => '请选择省/市。',
        'string' => '省/市必须是字符串。',
        'max' => '省/市不能超过 10 个字符。',
    ],
    'type_withdraw_info' => [
        'required' => '请选择提现类型。',
        'in' => '无效的提现类型。',
    ],
    'config_withdraw_info' => [
        'required' => '请输入提现信息。',
        'invalid' => '无效的提现信息。',
        'missing_field' => '提现信息缺少必要字段。',
    ],
    'user_withdraw_info' => [
        'invalid' => '无效的提现信息。',
    ],
    'category_id' => [
        'required' => '请选择服务分类。',
        'invalid' => '无效的服务分类。',
    ],
    'image' => [
        'required' => '请上传头像。',
        'max'      => '图片不能超过 20MB。',
        'mimes'    => '无效的图片格式（仅接受 jpeg, png, jpg）。',
    ],
    'name_service' => [
        'required' => '请输入至少 1 种语言的服务名称。',
        'invalid' => '服务名称格式无效。',
        'max'    => '服务名称太长（最多 255 个字符）。',
    ],
    'description_service' => [
        'required' => '请输入至少 1 种语言的服务描述。',
        'invalid' => '服务描述格式无效。',
        'max'    => '描述太长（最多 1000 个字符）。',
    ],
    'option_service' => [
        'required' => '请添加至少 1 个服务选项。',
        'invalid' => '服务价格选择数据与包类别不匹配。',
        'distinct' => '服务价格选择数据不得重复。',
    ],
    'files' => [
        'required_with' => '请上传至少 1 张图片。',
    ],
    'from_date' => [
        'required' => '请输入开始日期。',
        'date' => '无效的开始日期。',
        'date_format' => '开始日期必须是 yyyy-MM-dd 格式。',
    ],
    'to_date' => [
        'required' => '请输入结束日期。',
        'date' => '无效的结束日期。',
        'date_format' => '结束日期必须是 yyyy-MM-dd 格式。',
        'after_or_equal' => '结束日期必须大于或等于开始日期。',
    ],
    'direction' => [
        'in' => '无效的排序方向。',
    ],
    'type_date_range' => [
        'in' => '无效的显示时间类型。',
        'required' => '请选择显示时间类型。',
    ],
    'images' => [
        'required' => '请上传至少 1 张图片。',
        'array' => '图片列表必须是数组。',
        'min' => '图片列表必须至少包含 1 个项目。',
        'string' => '图片路径必须是字符串。',
        'mimes' => '文件必须是图片',
        'image' => '文件必须是图片',
        'max' => '最大图片大小 :max',
    ],
    'date_of_birth' => [
        'required' => '请输入出生日期。',
        'date' => '无效的出生日期。',
        'date_format' => '出生日期必须是 yyyy-MM-dd 格式。',
    ],
    'old_pass' => [
        'required' => '请输入旧密码。',
        'string' => '旧密码必须是字符串。',
    ],
    'new_pass' => [
        'required' => '请输入新密码。',
        'string' => '新密码必须是字符串。',
    ],
    'bio' => [
        'required' => '请输入至少 1 种语言的描述。',
        'invalid' => '描述格式无效。',
        'vi' => [
            'required' => '请输入越南语描述。',
            'string' => '越南语描述必须是字符串。',
            'max' => '越南语描述太长（最多 1000 个字符）。',
            'invalid' => '越南语描述无效。',
        ],
        'cn' => [
            'required' => '请输入中文描述。',
            'string' => '中文描述必须是字符串。',
            'max' => '中文描述太长（最多 1000 个字符）。',
            'invalid' => '中文描述无效。',
        ],
        'en' => [
            'required' => '请输入英语描述。',
            'string' => '英语描述必须是字符串。',
            'max' => '英语描述太长（最多 1000 个字符）。',
            'invalid' => '英语描述无效。',
        ],
    ],
    'experience' => [
        'required' => '请输入经验。',
        'integer' => '经验必须是整数。',
        'min' => '经验必须大于或等于 0。',
    ],
    'file_apply_partner_uploads' => [
        'required' => '请上传至少 1 张图片。',
        'array' => '图片列表必须是数组。',
        'invalid' => '图片格式无效，每张图片最大 :max MB。',
        'invalid_type' => '无效的图片类型。',
        'invalid_type_for_role' => '此角色的图片类型无效。',
        'duplicate_type' => '某些图片重复。',
        'invalid_type_count' => '此类型的图片数量无效。',
        'missing_type' => '缺少所需的图片类型。',
    ],
    'is_working' => [
        'required' => '请选择状态。',
        'invalid' => '无效的状态。',
    ],
    'working_schedule' => [
        'required' => '请输入工作时间表。',
        'array' => '工作时间表必须是数组。',
        'size' => '工作时间表必须有 7 个元素。',
        'day_key' => [
            'required' => '请选择日期。',
        ],
        'active' => [
            'required' => '请选择状态。',
        ],
        'start_time' => [
            'required_if' => '请输入开始时间。',
            'date_format' => '开始时间无效。',
        ],
        'end_time' => [
            'required_if' => '请输入结束时间。',
            'date_format' => '结束时间无效。',
            'after' => '结束时间必须在开始时间之后。',
        ],
    ],
    'type_contract' => [
        'required' => '请选择合同类型。',
        'integer' => '合同类型无效。',
        'in' => '合同类型无效。',
    ],
    'nickname' => [
        'required_if' => '请输入显示名称。',
        'invalid' => '显示名称必须是字符串，且至少 4 个字符，最多 255 个字符。',
    ],
];
