# Database của dự án

# các bảng mặc dịnh của laravel
  - sessions
  - cache
  - cache_locks
  - jobs
  - job_batches
  - failed_jobs
  - personal_access_tokens

# provinces
    # note
    - Bảng provinces lưu trữ các tỉnh thành.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - name (varchar) -- tên tỉnh thành
    - code (varchar, unique) -- mã tỉnh thành
    - division_type (varchar, nullable) -- cấp hành chính
    - timestamps


# geo_caching_places
    # note
    - Bảng geo_caching_places lưu trữ các địa điểm được tìm kiếm từ API để tối ưu truy vấn.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - place_id (varchar, unique) -- mã định danh địa điểm từ API
    - keyword (varchar, nullable, index) -- từ khóa tìm kiếm
    - formatted_address (varchar) -- địa chỉ định dạng đầy đủ
    - latitude (decimal(12,8), nullable) -- vĩ độ
    - longitude (decimal(12,8), nullable) -- kinh độ
    - raw_data (json, nullable) -- dữ liệu thô trả về từ  API
    - timestamps

# users
    # note
    - Bảng users lưu trữ thông tin người dùng của hệ thống.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - phone (varchar, unique, nullable) -- số điện thoại
    - phone_verified_at (timestamp, nullable) -- thời gian xác thực số điện thoại
    - password (varchar) -- mật khẩu đã được mã hóa
    - name (varchar) -- tên người dùng
    - role (smallint) -- vai trò người dùng (trong enum UserRole)
    - language (varchar, nullable) -- ngôn ngữ (trong enum Language)
    - is_active (boolean) -- trạng thái bị khóa
    - referred_by_user_id (bigint, nullable) -- id người giới thiệu
    - last_login_at (timestamp, nullable) -- thời gian đăng nhập cuối cùng
    - affiliate_link (varchar, nullable) -- liên kết giới thiệu
    - softDeletes
    - timestamps

# user_devices
    # note
    - Bảng user_devices lưu trữ thông tin các thiết bị của người dùng.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - token (varchar) -- token của thiết bị
    - device_id (varchar) -- id thiết bị
    - device_type (varchar, nullable) -- loại thiết bị
    - unique(device_id, user_id) -- token và device_id phải là duy nhất
    - softDeletes
    - timestamps


# user_review_application
    # note
    - Bảng user_review_application lưu trữ thông tin duyệt hồ sơ để thành KTV hoặc Agency.

    # relations
    - Quan hệ 1-1 với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - agency_id (bigint, nullable, foreign key to agencies.id) -- id Agency
    - status (smallint) -- trạng thái ứng dụng (trong enum ReviewApplicationStatus)

    - province_code (varchar, nullable, foreign key to provinces.code) -- mã tỉnh thành
    - address (varchar, nullable) -- địa chỉ chi tiết
    - latitude (decimal(10,8), nullable) -- vĩ độ
    - longitude (decimal(11,8), nullable) -- kinh độ

    - bio (json, nullable) -- thông tin cá nhân (dạng json mô tả thông tin cá nhân - đa ngôn ngữ)
    - experience (integer, nullable) -- kinh nghiệm (năm)
    - note (text, nullable) -- ghi chú thêm

    - effective_date (timestamp, nullable) -- ngày hiệu lực
    - application_date (timestamp, nullable) -- ngày nộp hồ sơ

    - softDeletes
    - timestamps

# user_profiles
    # note
    - Bảng user_profiles lưu trữ thông tin chi tiết về hồ sơ người dùng.

    # relations
    - Quan hệ 1-1 với bảng users.
    - Quan hệ 1-n với bảng user_files.

    # cấu trúc
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - avatar_url (varchar, nullable) -- URL ảnh đại diện
    - date_of_birth (date, nullable) -- ngày sinh
    - gender (smallint, nullable) -- giới tính (trong enum Gender)
    - bio (text, nullable) -- thông tin cá nhân
    
    - softDeletes
    - timestamps

# user_files
    # note
    - Bảng user_files lưu trữ thông tin tệp tin (như ảnh, video) của người dùng.

    # relations
    - Quan hệ 1-n với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - type (smallint) -- loại tệp tin (trong enum UserFileType)
    - file_path (varchar) -- đường dẫn tệp tin
    - file_name (varchar, nullable) -- tên tệp tin
    - file_size (varchar, nullable) -- kích thước tệp tin (byte)
    - file_type (varchar) -- Loại tệp đính kèm, ví dụ: pdf, docx, jpg, v.v.
    - is_public (boolean) -- tệp tin có thể truy cập từ bên ngoài không
    - softDeletes
    - timestamps

# user_address
    # note
    - Bảng user_address lưu trữ thông tin địa chỉ của người dùng.

    # relations
    - Quan hệ 1-n với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - address (varchar, nullable) -- địa chỉ chi tiết
    - latitude (decimal(10,8), nullable) -- vĩ độ
    - longitude (decimal(11,8), nullable) -- kinh độ
    - desc (text, nullable) -- ghi chú thêm
    - softDeletes
    - timestamps

# wallets
    # note
    - Bảng wallets lưu trữ thông tin ví tiền của người dùng.

    # relations
    - Quan hệ 1-1 với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - balance (decimal(15,2), default 0.00) -- số dư ví tiền -> là point riêng của hệ thống
    - is_active (boolean, default true) -- trạng thái hoạt động của ví tiền
    - softDeletes
    - timestamps

# wallet_transactions
    # note
    - Bảng wallet_transactions lưu trữ thông tin giao dịch ví tiền của người dùng (như nạp tiền, rút tiền, chuyển tiền, affiliate).

    # relations
    - Quan hệ 1-n với bảng wallets.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - wallet_id (bigint, foreign key to wallets.id) -- id ví tiền
    - foreign_key (unsigned bigint, nullable) -- khóa ngoại liên kết với bảng khác (ví dụ: order_id, payment_id)
    - transaction_code (varchar) -- mã giao dịch
    - transaction_id (varchar, nullable) -- id giao dịch bên thứ 3
    - metadata (text, nullable) -- Dữ liệu bổ sung liên quan đến giao dịch, có thể là thông tin bổ sung từ hệ thống thanh toán
    - type (smallint) -- loại giao dịch (trong enum TransactionType)

    - money_amount (decimal(15,2), nullable) -- số tiền thực tế (số tiền người dùng nạp, rút)
    - exchange_rate_point (decimal(15,2), nullable, default 0.00) -- tỷ giá đổi tiền (số tiền point đổi thành 1 unit tiền)
    - point_amount (decimal(15,2), nullable) -- số tiền point (số tiền point sau khi đổi tiền)
    - balance_after (decimal(15,2), nullable) -- số dư ví sau giao dịch (số tiền point sau khi giao dịch)
    - status (smallint) -- trạng thái giao dịch (trong enum TransactionStatus)
    - description (varchar, nullable) -- mô tả giao dịch
    - expired_at (timestamp, nullable) -- thời gian hết hạn (nếu có)
    - softDeletes
    - timestamps

# affiliate_registrations
    # note
    - Bảng affiliate_registrations lưu trữ đơn đăng ký tham gia chương trình Affiliate của người dùng.

    # relations

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - status (smallint) -- trạng thái đăng ký (trong enum AffiliateRegistrationStatus)
    - note (text, nullable) -- ghi chú
    - processed_at (timestamp, nullable) -- thời gian xử lý duyệt đơn hoặc từ chối
    - softDeletes
    - timestamps

# affiliate_configs
    # note
    - Bảng affiliate_configs lưu trữ thông tin cấu hình chương trình Affiliate.

    # relations

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - name (varchar) -- tên cấu hình
    - description (text, nullable) -- mô tả cấu hình
    - target_role (smallint) -- Áp dụng cho vai trò nào được giới thiệu (trong enum UserRole)
    - commission_rate (decimal(5,2)) -- tỷ lệ hoa hồng (%)
    - min_commission (decimal(15,2)) -- mức hoa hồng tối thiểu
    - max_commission (decimal(15,2)) -- mức hoa hồng tối đa
    - is_active (boolean) -- trạng thái kích hoạt
    - softDeletes
    - timestamps

# affiliate_earnings
    # note
    - Bảng affiliate_earnings lưu trữ thông tin hoa hồng kiếm được từ chương trình Affiliate.

    # relations

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - affiliate_user_id (bigint, foreign key to users.id) -- id người dùng affiliate
    - referred_user_id (bigint, foreign key to users.id) -- id người dùng đã phát sinh giao dịch
    - transaction_id (bigint, foreign key to wallet_transactions.id) -- id giao dịch phát sinh hoa hồng
    - commission_amount (decimal(15,2)) -- số tiền hoa hồng kiếm được
    - commission_rate (decimal(5,2)) -- tỷ lệ hoa hồng (%) lúc được áp dụng
    - status (smallint) -- trạng thái hoa hồng (trong enum AffiliateEarningStatus)
    - processed_at (timestamp, nullable) -- thời gian xử lý hoa hồng (nhận hoặc từ chối)
    - softDeletes
    - timestamps

# categories
    # note
    - Bảng categories lưu trữ thông tin danh mục dịch vụ.
     # cấu trúc
    - id (bigint, primary key, auto-increment)
    - name (json) -- tên danh mục (đa ngôn ngữ, lưu trữ dưới dạng JSON)
    - description (json, nullable) -- mô tả danh mục (đa ngôn ngữ, lưu trữ dưới dạng JSON)
    - image_url (varchar, nullable) -- URL hình ảnh danh mục
    - position (smallint, default 0) -- vị trí hiển thị (số càng nhỏ thì hiển thị càng lên trên)
    - is_featured (boolean, default false) -- có hiển thị nổi bật hay không
    - usage_count (bigint, default 0) -- số lần sử dụng
    - is_active (boolean) -- trạng thái kích hoạt
    - softDeletes
    - timestamps

# services
    # note
    - Bảng services lưu trữ thông tin dịch vụ.
    # relations
    - Quan hệ 1-n với bảng categories.
    - Quan hệ 1-n với bảng users.

     # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng cung cấp dịch vụ
    - category_id (bigint, foreign key to categories.id) -- id danh mục dịch vụ
    - name (json) -- tên dịch vụ
    - description (json, nullable) -- mô tả dịch vụ (đa ngôn ngữ, lưu trữ dưới dạng JSON)
    - is_active (boolean) -- trạng thái kích hoạt
    - image_url (varchar, nullable) -- URL hình ảnh dịch vụ
    - softDeletes
    - timestamps

# service_options
    # note
    - Bảng service_options lưu trữ thông tin tùy chọn dịch vụ (ví dụ: thời gian thực hiện, giá).

    #relations
    - Quan hệ 1-n với bảng services.
    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - service_id (bigint, foreign key to services.id) -- id dịch vụ
    - duration (smallint) -- thời gian thực hiện dịch vụ (dạng enum ServiceDuration - minutes)
    - price (decimal(15,2)) -- giá tùy chọn
    - softDeletes
    - timestamps

# service_bookings
    # note
    - Bảng service_bookings lưu trữ thông tin đặt lịch hẹn.

    # relations
    - Quan hệ 1-n với bảng users.
    - Quan hệ 1-n với bảng services.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng đặt lịch
    - ktv_user_id (bigint, foreign key to users.id) -- id người làm dịch vụ (KTV)
    - service_option_id (bigint, foreign key to service_options.id) -- id tùy chọn dịch vụ
    - service_id (bigint, foreign key to services.id) -- id dịch vụ
    - coupon_id (bigint, foreign key to coupons.id, nullable) -- id mã giảm giá
    - duration (smallint) -- thời gian thực hiện dịch vụ (dạng enum ServiceDuration - minutes)
    - booking_time (timestamp) -- thời gian đặt lịch
    - start_time (timestamp, nullable) -- thời gian bắt đầu
    - end_time (timestamp, nullable) -- thời gian kết thúc
    - status (smallint) -- trạng thái đặt lịch (trong enum BookingStatus)
    - price (decimal(15,2)) -- giá dịch vụ (lưu trữ giá trị thực tế khi đặt lịch)
    - price_before_discount (decimal(15,2)) -- giá dịch vụ trước khi áp dụng mã giảm giá
    - note (text, nullable) -- ghi chú
    - address (varchar, nullable) -- địa chỉ hẹn
    - latitude (decimal(10,8), nullable) -- vĩ độ
    - longitude (decimal(11,8), nullable) -- kinh độ
    - payment_type (smallint, nullable) -- hình thức thanh toán (trong enum PaymentType), null là khi dịch vụ chưa được xác nhận
    - softDeletes
    - timestamps

# coupons
    # note
    - Bảng coupons lưu trữ thông tin mã giảm giá.

    # relations
    - Quan hệ 1-n với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - code (varchar) -- mã giảm giá
    - label (json) -- tên mã giảm giá (đa ngôn ngữ, lưu trữ dưới dạng JSON)
    - description (json, nullable) -- mô tả mã giảm giá (đa ngôn ngữ, lưu trữ dưới dạng JSON)
    - created_by (bigint, foreign key to users.id) -- id người dùng tạo mã giảm giá
    - for_service_id (bigint, foreign key to services.id, nullable) -- id dịch vụ áp dụng mã giảm giá (nếu null thì áp dụng cho tất cả dịch vụ)
    - is_percentage (boolean, default false) -- có phải là phần trăm giảm giá hay không (nếu là false thì là giảm giá cố định)
    - discount_value (decimal(15,2)) -- giá trị giảm giá 
    - max_discount (decimal(15,2), nullable) -- giá trị giảm giá tối đa
    - start_at (timestamp) -- ngày bắt đầu áp dụng
    - end_at (timestamp) -- ngày kết thúc áp dụng
    - usage_limit (bigint, nullable) -- số lần sử dụng tối đa
    - used_count (bigint, default 0) -- số lần đã sử dụng
    - is_active (boolean) -- trạng thái kích hoạt
    - banners (json, nullable) -- danh sách banner đa ngôn ngữ
    - display_ads (boolean, default true) -- có hiển thị quảng cáo ở homepage không
    - softDeletes
    - timestamps
    - unique (code, created_by) -- mã giảm giá phải là duy nhất cho từng người dùng
# coupon_used
    #note
    - Bảng coupon_used lưu trữ thông tin mã giảm giá đã được sử dụng.

    # relations
    - Quan hệ 1-n với bảng coupons.
    - Quan hệ 1-n với bảng users.
    - Quan hệ 1-n với bảng services.
    - Quan hệ 1-n với bảng service_bookings.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - coupon_id (bigint, foreign key to coupons.id) -- id mã giảm giá
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - service_id (bigint, foreign key to services.id) -- id dịch vụ
    - booking_id (bigint, foreign key to service_bookings.id) -- id đơn đặt lịch
    - softDeletes
    - timestamps
    - unique (booking_id, coupon_id) -- mã giảm giá phải là duy nhất cho từng đơn đặt lịch

# coupon_users
    #note
    - Bảng coupon_users lưu trữ thông tin người dùng đã sử dụng mã giảm giá.

    # relations
    - Quan hệ 1-n với bảng coupons.
    - Quan hệ 1-n với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - coupon_id (bigint, foreign key to coupons.id) -- id mã giảm giá
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - quantity (smallint) -- số lượng mã giảm giá
    - softDeletes
    - timestamps
    - unique (user_id, coupon_id) -- mã giảm giá phải là duy nhất cho từng người dùng

# reviews 
    # note
    - Bảng reviews lưu trữ thông tin đánh giá dịch vụ.

    # relations
    - Quan hệ 1-n với bảng users.
    - Quan hệ 1-n với bảng services.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người được đánh giá
    - review_by (bigint, foreign key to users.id) -- id người dùng đánh giá
    - rating (smallint) -- xếp hạng (dạng số từ 1-5)
    - comment (text, nullable) -- bình luận
    - review_at (timestamp) -- thời gian đánh giá
    - hidden (boolean, default false) -- có ẩn hay không
    - softDeletes
    - timestamps

# configs
    # note
    - Bảng configs lưu trữ thông tin cấu hình hệ thống.

    # relations
    - Quan hệ 1-n với bảng service_bookings.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - config_key (varchar , unique) -- khóa cấu hình
    - config_type (smallint) -- kiểu cấu hình (trong enum ConfigType)
    - config_value (text) -- giá trị cấu hình
    - description (text, nullable) -- mô tả cấu hình
    - softDeletes
    - timestamps

# notifications
    # note
    - Bảng notifications lưu trữ thông tin các thông báo gửi đến người dùng.

    # relations
    - Quan hệ 1-n với bảng users.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- ID người dùng nhận thông báo
    - title (varchar) -- Tiêu đề thông báo
    - description (text) -- Nội dung thông báo
    - data (text, nullable) -- Dữ liệu bổ sung (json format)
    - type (smallint) -- Loại thông báo (trong enum NotificationType)
    - status (smallint, default 0) -- Trạng thái thông báo (trong enum NotificationStatus)
    - softDeletes
    - timestamps

# banners
    # note
    - Bảng banners lưu trữ thông tin các banner hiển thị trên home page.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - image_url (json) -- URL hình ảnh banner (lưu trữ dưới dạng JSON đa ngôn ngữ)
    - order (smallint, default 0) -- Sắp xếp banner
    - is_active (boolean, default true) -- Trạng thái kích hoạt
    - softDeletes
    - timestamps
