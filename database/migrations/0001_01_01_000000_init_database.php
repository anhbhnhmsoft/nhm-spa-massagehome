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
        // Tạo bảng provinces để lưu trữ thông tin về các tỉnh thành
        Schema::create('provinces', function (Blueprint $table) {;
            $table->id();
            $table->comment('Bảng provinces lưu trữ các tỉnh thành');
            $table->string('name')->comment('Tên');
            $table->string('code')->unique()->comment('Mã');
            $table->string('division_type')->nullable()->comment('Cấp hành chính');
            $table->timestamps();
        });

        // Tạo bảng districts để lưu trữ thông tin về các quận huyện
        Schema::create('districts', function (Blueprint $table) {;
            $table->id();
            $table->comment('Bảng districts lưu trữ các quận huyện');
            $table->string('name')->comment('Tên');
            $table->string('code')->unique()->comment('Mã');
            $table->string('division_type')->nullable()->comment('Cấp hành chính');
            $table->string('province_code');
            $table->foreign('province_code')->references('code')->on('provinces')->cascadeOnDelete();
            $table->timestamps();
        });

        // Tạo bảng districts để lưu trữ thông tin về các phường xã
        Schema::create('wards', function (Blueprint $table) {;
            $table->id();
            $table->comment('Bảng ward lưu trữ các phường xã');
            $table->string('name')->comment('Tên');
            $table->string('code')->unique()->comment('Mã');
            $table->string('division_type')->nullable()->comment('Cấp hành chính');

            // Khóa ngoại nối bằng code
            $table->string('district_code');
            $table->foreign('district_code')->references('code')->on('districts')->cascadeOnDelete();
            $table->timestamps();
        });

        // Tạo bảng users để lưu trữ thông tin người dùng
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
            $table->boolean('disabled')->default(false)->comment('Trạng thái hoạt động');
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

        // Tạo bảng user_profiles để lưu trữ thông tin hồ sơ người dùng
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->comment('Bảng user_profiles lưu trữ thông tin hồ sơ người dùng');
            // Dùng user_id làm Khóa chính & Khóa ngoại (Quan hệ 1-1)
            $table->bigInteger('user_id')->primary();
            // Các trường thông tin cá nhân
            $table->string('avatar_url')->nullable()->comment('URL ảnh đại diện');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh');
            $table->smallInteger('gender')->nullable()->comment('Giới tính (trong enum Gender)');

            // Các trường thông tin địa lý
            $table->string('province_code')->nullable()->comment('Mã tỉnh thành');
            $table->string('district_code')->nullable()->comment('Mã quận huyện');
            $table->string('ward_code')->nullable()->comment('Mã phường xã');
            $table->string('address')->nullable()->comment('Địa chỉ chi tiết');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Vĩ độ');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Kinh độ');
            // Các trường thông tin cá nhân
            $table->text('bio')->nullable()->comment('Thông tin cá nhân');
            $table->integer('experience')->nullable()->comment('Kinh nghiệm - theo tháng');
            $table->json('skills')->nullable()->comment('Kỹ năng (dạng mảng enum, thuộc enum UserSkill)');

            // Bỏ softDeletes
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Tạo bảng user_files để lưu trữ thông tin tệp tin (như ảnh, video) của người dùng
        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->smallInteger('file_type')->comment('Loại tệp tin (trong enum UserFileType)');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->string('file_type')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Tạo bảng categories để lưu trữ thông tin danh mục dịch vụ
        Schema::create('categories', function (Blueprint $table) {
            $table->comment('Bảng categories lưu trữ thông tin danh mục dịch vụ');
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // Tạo bảng services để lưu trữ thông tin dịch vụ
        Schema::create('services', function (Blueprint $table) {
            $table->comment('Bảng services lưu trữ thông tin dịch vụ');
            $table->id();
            $table->bigInteger('user_id');
            $table->string('name')->comment('Tên dịch vụ');
            $table->text('description')->nullable()->comment('Mô tả dịch vụ (có html tag)');
            $table->decimal('price', 15, 2)->comment('Giá dịch vụ');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->string('image_url')->nullable()->comment('URL hình ảnh dịch vụ');
            $table->json('duration')->comment('Thời gian thực hiện dịch vụ (dạng json, lưu mảng các enum ServiceDuration - minutes)');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Tạo bảng service_categories để lưu trữ quan hệ n-n giữa dịch vụ và danh mục
        Schema::create('service_categories', function (Blueprint $table) {
            $table->comment('Bảng service_categories lưu trữ quan hệ n-n giữa dịch vụ và danh mục');
            $table->id();
            $table->bigInteger('service_id');
            $table->bigInteger('category_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::create();


        // Tạo bảng wallets để lưu trữ thông tin ví tiền của người dùng
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

        // Tạo bảng wallet_transactions để lưu trữ thông tin giao dịch ví tiền của người dùng
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

        /**
         * Bảng affiliate_registrations lưu trữ thông tin đăng ký Affiliate
         */
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

        /**
         * Bảng affiliate_configs lưu trữ thông tin cấu hình Affiliate
         */
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

        /**
         * Bảng affiliate_earnings lưu trữ thông tin doanh thu Affiliate
         */
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
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('affiliate_earnings');
        Schema::dropIfExists('affiliate_configs');
        Schema::dropIfExists('affiliate_registrations');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('wards');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
    }
};
