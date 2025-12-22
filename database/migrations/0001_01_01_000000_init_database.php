<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {;
            $table->id();
            $table->comment('Bảng provinces lưu trữ các tỉnh thành');
            $table->string('name')->comment('Tên');
            $table->string('code')->unique()->comment('Mã');
            $table->string('division_type')->nullable()->comment('Cấp hành chính');
            $table->timestamps();
        });

        Schema::create('geo_caching_places', function (Blueprint $table) {
            $table->id();
            $table->string('place_id')->unique();
            $table->string('keyword')->nullable()->index();
            $table->string('formatted_address');
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->comment('Bảng users lưu trữ thông tin người dùng');
            // Dùng BIGINT ID (Snowflake), không auto-increment
            $table->bigInteger('id')->primary();
            $table->string('phone')->unique()->comment('Số điện thoại');
            $table->timestamp('phone_verified_at')->nullable()->comment('Thời gian xác thực số điện thoại');
            $table->string('password')->comment('Mật khẩu đã được mã hóa');
            $table->string('name')->comment('Tên người dùng');
            $table->smallInteger('role')->comment('Vai trò người dùng (trong enum UserRole)');
            $table->string('language', 5)->nullable()->default('vn')->comment('Ngôn ngữ (trong enum Language)');
            // false: hoạt động, true: bị vô hiệu hóa
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            // ID của người giới thiệu TÔI
            $table->bigInteger('referred_by_user_id')->nullable()->comment('ID người giới thiệu');
            $table->timestamp('last_login_at')->nullable()->comment('Thời gian đăng nhập cuối cùng');

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('role');
        });

        Schema::create('user_review_application', function (Blueprint $table) {
            $table->comment('Bảng user_review_application lưu trữ thông tin duyệt hồ sơ để thành KTV hoặc Agency');
            $table->id();
            $table->bigInteger('user_id')->unique()->comment('ID người dùng');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('agency_id')->nullable()->comment('ID đại lý');
            $table->foreign('agency_id')->references('id')->on('users')->onDelete('cascade');
            $table->smallInteger('status')->comment('Trạng thái ứng dụng (trong enum ReviewApplicationStatus)');
            $table->string('province_code')->nullable()->comment('Mã tỉnh thành');
            $table->foreign('province_code')->references('code')->on('provinces')->cascadeOnDelete();
            $table->string('address')->nullable()->comment('Địa chỉ chi tiết');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Vĩ độ');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Kinh độ');
            $table->json('bio')->nullable()->comment('Thông tin cá nhân');
            $table->integer('experience')->nullable()->comment('Kinh nghiệm - theo năm');
            $table->json('skills')->nullable()->comment('Kỹ năng (dạng mảng string mô tả kỹ năng)');
            $table->text('note')->nullable()->comment('Ghi chú thêm');
            $table->timestamp('effective_date')->nullable()->comment('Ngày hiệu lực');
            $table->timestamp('application_date')->nullable()->comment('Ngày nộp hồ sơ');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->comment('Bảng user_profiles lưu trữ thông tin hồ sơ người dùng');
            // Dùng user_id làm Khóa chính & Khóa ngoại (Quan hệ 1-1)
            $table->bigInteger('user_id')->primary();
            // Các trường thông tin cá nhân
            $table->string('avatar_url')->nullable()->comment('URL ảnh đại diện');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh');
            $table->smallInteger('gender')->nullable()->comment('Giới tính (trong enum Gender)');
            $table->text('bio')->nullable()->comment('Thông tin cá nhân');
            // Bỏ softDeletes
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->smallInteger('type')->comment('Loại tệp tin (trong enum UserFileType)');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->string('file_type')->nullable();
            $table->boolean('is_public')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('address', 255);
            $table->decimal('latitude', 10, 8)->default(0);
            $table->decimal('longitude', 11, 8)->default(0);
            $table->text('desc')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->comment('Bảng categories lưu trữ thông tin danh mục dịch vụ');
            $table->id();
            $table->json('name')->comment('Tên danh mục (đa ngôn ngữ, lưu trữ dưới dạng JSON)');
            $table->json('description')->nullable()->comment('Mô tả danh mục (đa ngôn ngữ, lưu trữ dưới dạng JSON)');
            $table->string('image_url')->nullable()->comment('URL hình ảnh danh mục');
            $table->smallInteger('position')->default(0)->comment('Vị trí hiển thị (số càng nhỏ thì hiển thị càng lên trên)');
            $table->boolean('is_featured')->default(false)->comment('Có hiển thị nổi bật hay không');
            $table->bigInteger('usage_count')->default(0)->comment('Số lần sử dụng');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->comment('Bảng services lưu trữ thông tin dịch vụ');
            $table->id();
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->json('name')->comment('Tên dịch vụ (đa ngôn ngữ, lưu trữ dưới dạng JSON)');
            $table->json('description')->nullable()->comment('Mô tả dịch vụ (đa ngôn ngữ, lưu trữ dưới dạng JSON)');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->string('image_url')->nullable()->comment('URL hình ảnh dịch vụ');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('service_options', function (Blueprint $table) {
            $table->comment('Bảng service_options lưu trữ thông tin tùy chọn dịch vụ');
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->smallInteger('duration')->comment('Thời gian thực hiện dịch vụ (dạng enum ServiceDuration - minutes)');
            $table->decimal('price', 15, 2)->comment('Giá tùy chọn');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->comment('Bảng coupons lưu trữ thông tin mã giảm giá.');
            $table->id();
            $table->string('code')->unique()->comment('Mã giảm giá');
            $table->json('label')->comment('Tên mã giảm giá (đa ngôn ngữ, lưu trữ dưới dạng JSON)');
            $table->json('description')->nullable()->comment('Mô tả mã giảm giá (đa ngôn ngữ, lưu trữ dưới dạng JSON)');
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('for_service_id')
                ->nullable()
                ->constrained('services')
                ->onDelete('cascade');
            $table->boolean('is_percentage')
                ->default(false)
                ->comment('Có phải là phần trăm giảm giá hay không (nếu là false thì là giảm giá cố định)');
            $table->decimal('discount_value', 15, 2)
                ->comment('Giá trị giảm giá');
            $table->decimal('min_order_value', 15, 2)
                ->nullable()->comment('Giá trị đơn hàng tối thiểu để áp dụng mã giảm giá');
            $table->decimal('max_discount', 15, 2)
                ->nullable()
                ->comment('Giá trị giảm giá tối đa');
            $table->timestamp('start_at')
                ->comment('Ngày bắt đầu áp dụng');
            $table->timestamp('end_at')
                ->comment('Ngày kết thúc áp dụng');
            $table->bigInteger('usage_limit')
                ->nullable()->comment('Số lần sử dụng tối đa');
            $table->bigInteger('used_count')
                ->default(0)->comment('Số lần đã sử dụng');
            $table->boolean('is_active')
                ->default(true)->comment('Trạng thái kích hoạt');
            $table->json('banners')->nullable()->comment('Danh sách banner đa ngôn ngữ');
            $table->boolean('display_ads')->default(true)->comment('Hiển thị quảng cáo ở homepage');
            $table->jsonb('config')->nullable()->comment('Cấu hình điều kiện sử dụng');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['code', 'created_by']);
        });

        Schema::create('coupon_used', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id')->comment('ID mã giảm giá');
            $table->unsignedBigInteger('user_id')->comment('ID người dùng sử dụng');
            $table->unsignedBigInteger('service_id')->comment('ID dịch vụ');
            $table->unsignedBigInteger('booking_id')->unique()->comment('ID đơn đặt lịch/giao dịch');

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('service_bookings')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['booking_id', 'coupon_id']);
        });

        Schema::create('coupon_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id')->comment('ID mã giảm giá');
            $table->unsignedBigInteger('user_id')->comment('ID người dùng');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->smallInteger('is_used')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'coupon_id']);
        });

        Schema::create('service_bookings', function (Blueprint $table) {
            $table->comment('Bảng service_bookings lưu trữ thông tin đặt lịch hẹn');
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('service_option_id')->nullable()->comment('Tùy chọn dịch vụ áp dụng');
            $table->unsignedBigInteger('coupon_id')->nullable()->comment('Mã giảm giá áp dụng');
            $table->smallInteger('duration')->comment('Thời gian thực hiện dịch vụ (dạng enum ServiceDuration - minutes)');
            $table->timestamp('booking_time')->comment('Thời gian đặt lịch');
            $table->timestamp('start_time')->nullable()->comment('Thời gian bắt đầu');
            $table->timestamp('end_time')->nullable()->comment('Thời gian kết thúc');
            $table->smallInteger('status')->comment('Trạng thái đặt lịch (trong enum BookingStatus)');
            $table->decimal('price', 15, 2)->comment('Giá dịch vụ (lưu trữ giá trị thực tế khi đặt lịch)');
            $table->decimal('price_before_discount', 15, 2)->comment('Giá dịch vụ trước khi áp dụng mã giảm giá');
            $table->text('note')->nullable()->comment('Ghi chú');
            // address
            $table->string('address')->comment('Địa chỉ cụ thể');
            $table->decimal('latitude', 10, 8)->comment('Vĩ độ');
            $table->decimal('longitude', 11, 8)->comment('Kinh độ');

            $table->smallInteger('payment_type')->nullable()->comment('hình thức thanh toán (trong enum PaymentType), null là khi dịch vụ chưa được xác nhận');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('service_option_id')->references('id')->on('service_options')->onDelete('set null');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->comment('Bảng wallets lưu trữ thông tin ví tiền của người dùng');
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('ID người dùng');
            // Dùng decimal cho tiền tệ
            $table->decimal('balance', 15, 2)->default(0.00)->comment('Số dư ví tiền -> là point riêng của hệ thống');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            // Không cần softDeletes, vì nó theo user
            $table->timestamps();
            // Khai báo khóa ngoại
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->softDeletes();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->comment('Bảng wallet_transactions lưu trữ thông tin giao dịch ví tiền');
            $table->id();
            $table->unsignedBigInteger('wallet_id')->comment('ID ví tiền');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->unsignedBigInteger('foreign_key')->nullable()->comment('Khóa ngoại liên kết với bảng khác');
            $table->string('transaction_code')->comment('Mã giao dịch');
            $table->string('transaction_id')->nullable()->comment('ID giao dịch bên thứ 3');
            $table->text('metadata')->nullable()->comment('Dữ liệu bổ sung liên quan đến giao dịch');
            $table->smallInteger('type')->comment('Loại giao dịch (trong enum TransactionType)');
            $table->decimal('money_amount', 15, 2)->nullable()->comment('Số tiền thực tế (số tiền người dùng nạp, rút)');
            $table->decimal('exchange_rate_point', 15, 2)->nullable()->default(0.00)->comment('Tỷ giá đổi tiền (số tiền point đổi thành 1 unit tiền)');
            $table->decimal('point_amount', 15, 2)->nullable()->comment('Số tiền point (số tiền point sau khi đổi tiền)');
            $table->decimal('balance_after', 15, 2)->nullable()->comment('Số dư ví sau giao dịch (số tiền point sau khi giao dịch)');
            $table->smallInteger('status')->comment('Trạng thái giao dịch (trong enum TransactionStatus)');
            $table->string('description')->nullable()->comment('Mô tả giao dịch');
            $table->timestamp('expired_at')->nullable()->comment('Thời gian hết hạn (nếu có)');
            $table->index(['transaction_id']);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('affiliate_configs', function (Blueprint $table) {
            $table->comment('Bảng affiliate_configs lưu trữ thông tin cấu hình Affiliate');
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->smallInteger('target_role');
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('min_commission', 15, 2);
            $table->decimal('max_commission', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('ID người giới thiệu');
            $table->string('client_ip')->comment('Địa chỉ IP của người nhấp chuột');
            $table->text('user_agent')->comment('Thông tin thiết bị/trình duyệt');
            $table->boolean('is_matched')->default(false)->comment('Đã so khớp thành công chưa');
            $table->timestamp('expired_at')->comment('Thời gian hết hạn đối sánh');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->comment('Bảng reviews lưu trữ thông tin đánh giá dịch vụ.');
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('review_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('review_at')->useCurrent()->comment('Thời gian đánh giá');
            $table->boolean('hidden')->default(false)->comment('Có ẩn hay không');
            $table->smallInteger('rating')->unsigned()->default(0)->comment('Đánh giá từ 1-5');
            $table->text('comment')->nullable()->comment('Bình luận');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('configs', function (Blueprint $table) {
            $table->comment('Bảng configs lưu trữ thông tin cấu hình hệ thống.');
            $table->id();
            $table->string('config_key')->unique()->comment('Khóa cấu hình');
            $table->smallInteger('config_type')->comment('Kiểu cấu hình (trong enum ConfigType)');
            $table->text('config_value')->comment('Giá trị cấu hình');
            $table->text('description')->nullable()->comment('Mô tả cấu hình');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->comment('Bảng user_devices lưu trữ thông tin các thiết bị của người dùng.');
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token')->comment('Token của thiết bị.');
            $table->string('device_id')->comment('ID của thiết bị.');
            $table->string('device_type', 20)->nullable()->comment('Loại thiết bị.');
            $table->unique(['device_id', 'user_id']);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->json('image_url')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * Các bảng của Laravel
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // Bảng chat_rooms - Lưu thông tin phòng chat giữa khách hàng và KTV
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->comment('ID khách hàng');
            $table->bigInteger('ktv_id')->comment('ID KTV');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ktv_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['customer_id', 'ktv_id']);
        });

        // Bảng messages - Lưu nội dung tin nhắn trong phòng chat
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('temp_id')->nullable()->comment('ID tạm thời cho tin nhắn');
            $table->unsignedBigInteger('room_id')->comment('ID phòng chat (chat_rooms.id)');
            $table->bigInteger('sender_by')->comment('ID người gửi (users.id)');
            $table->text('content')->comment('Nội dung tin nhắn');
            $table->timestamp('seen_at')->nullable()->comment('Thời gian đã đọc tin nhắn');
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
            $table->foreign('sender_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable FK checks to avoid constraint errors while dropping tables
        Schema::disableForeignKeyConstraints();

        $tables = [
            'banners',
            'personal_access_tokens',
            'failed_jobs',
            'job_batches',
            'jobs',
            'cache_locks',
            'cache',
            'sessions',


            'service_options',
            'user_devices',
            'configs',
            'reviews',
            'affiliate_links',
            'affiliate_configs',
            'wallet_transactions',
            'wallets',
            'service_bookings',
            'coupons',
            'services',
            'categories',
            'user_address',
            'user_files',
            'user_profiles',
            'user_review_application',
            'users',
            'coupon_used',
            'provinces',
            'geo_caching_places',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
