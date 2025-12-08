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
            // Mã giới thiệu CỦA TÔI
            $table->string('referral_code', 20)->unique()->nullable()->comment('Mã giới thiệu');
            // ID của người giới thiệu TÔI
            $table->bigInteger('referred_by_user_id')->nullable()->comment('ID người giới thiệu');
            $table->timestamp('last_login_at')->nullable()->comment('Thời gian đăng nhập cuối cùng');

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('role');
            $table->index('referral_code');
        });

        Schema::create('user_review_application', function (Blueprint $table) {
            $table->comment('Bảng user_review_application lưu trữ thông tin duyệt hồ sơ để thành KTV hoặc Agency');
            $table->id();
            $table->bigInteger('user_id')->unique()->comment('ID người dùng');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->smallInteger('status')->comment('Trạng thái ứng dụng (trong enum ReviewApplicationStatus)');
            $table->string('province_code')->nullable()->comment('Mã tỉnh thành');
            $table->foreign('province_code')->references('code')->on('provinces')->cascadeOnDelete();
            $table->string('address')->nullable()->comment('Địa chỉ chi tiết');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Vĩ độ');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Kinh độ');
            $table->text('bio')->nullable()->comment('Thông tin cá nhân');
            $table->integer('experience')->nullable()->comment('Kinh nghiệm - theo năm');
            $table->json('skills')->nullable()->comment('Kỹ năng (dạng mảng string mô tả kỹ năng)');

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
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->comment('Bảng categories lưu trữ thông tin danh mục dịch vụ');
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
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
            $table->string('name')->comment('Tên dịch vụ');
            $table->text('description')->nullable()->comment('Mô tả dịch vụ (có html tag)');
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

        Schema::create('service_bookings', function (Blueprint $table) {
            $table->comment('Bảng service_bookings lưu trữ thông tin đặt lịch hẹn');
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id');
            $table->smallInteger('duration')->comment('Thời gian thực hiện dịch vụ (dạng enum ServiceDuration - minutes)');
            $table->timestamp('booking_time')->comment('Thời gian đặt lịch');
            $table->timestamp('start_time')->comment('Thời gian bắt đầu');
            $table->timestamp('end_time')->comment('Thời gian kết thúc');
            $table->smallInteger('status')->comment('Trạng thái đặt lịch (trong enum BookingStatus)');
            $table->decimal('price', 15, 2)->comment('Giá dịch vụ (lưu trữ giá trị thực tế khi đặt lịch)');
            $table->text('note')->nullable()->comment('Ghi chú');
            // address
            $table->string('address')->comment('Địa chỉ cụ thể');
            $table->decimal('latitude', 10, 8)->comment('Vĩ độ');
            $table->decimal('longitude', 11, 8)->comment('Kinh độ');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->comment('Bảng wallets lưu trữ thông tin ví tiền của người dùng');
            // Dùng user_id làm Khóa chính & Khóa ngoại (Quan hệ 1-1)
            $table->bigInteger('user_id')->primary();
            // Dùng decimal cho tiền tệ
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('password')->nullable();
            // Không cần softDeletes, vì nó theo user
            $table->timestamps();

            // Khai báo khóa ngoại
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('wallet_id');
            $table->smallInteger('type');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->smallInteger('status');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('wallet_id')->references('user_id')->on('wallets');
        });

        Schema::create('affiliate_registrations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->smallInteger('status');
            $table->text('note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
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

        Schema::create('affiliate_earnings', function (Blueprint $table) {
            $table->comment('Bảng affiliate_earnings lưu trữ thông tin doanh thu Affiliate');
            $table->id();
            $table->bigInteger('affiliate_user_id');
            $table->bigInteger('referred_user_id');
            $table->bigInteger('transaction_id');
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->smallInteger('status');
            $table->timestamp('processed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('affiliate_user_id')->references('id')->on('users');
            $table->foreign('referred_user_id')->references('id')->on('users');
            $table->foreign('transaction_id')->references('id')->on('wallet_transactions');
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

        Schema::create('coupons', function (Blueprint $table) {
            $table->comment('Bảng coupons lưu trữ thông tin mã giảm giá.');
            $table->id();
            $table->string('code')->unique()->comment('Mã giảm giá');
            $table->string('label')->comment('Tên mã giảm giá');
            $table->text('description')->nullable()->comment('Mô tả mã giảm giá');
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
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['code', 'created_by']);
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable FK checks to avoid constraint errors while dropping tables
        Schema::disableForeignKeyConstraints();

        $tables = [
            'personal_access_tokens',
            'failed_jobs',
            'job_batches',
            'jobs',
            'cache_locks',
            'cache',
            'sessions',



            'configs',
            'reviews',
            'affiliate_earnings',
            'affiliate_configs',
            'affiliate_registrations',
            'wallet_transactions',
            'wallets',
            'service_bookings',
            'services',
            'categories',
            'user_files',
            'user_profiles',
            'user_review_application',
            'users',
            'provinces',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
