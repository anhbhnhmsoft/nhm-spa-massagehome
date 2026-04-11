<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Massage Home - Thư giãn & Phục hồi sức khỏe</title>
    <meta name="description"
        content="Ứng dụng đặt lịch Spa & Massage chuyên nghiệp. Trải nghiệm dịch vụ thư giãn đẳng cấp ngay tại nhà hoặc tại spa.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* --- ROOT VARIABLES --- */
        :root {
            --primary-color: #2b7bc4;
            /* Xanh lá dịu nhẹ -> Xanh dương nhạt */
            --accent-color: #d4a373;
            /* Màu nâu đất/đồng giữ nguyên */
            --light-bg: #fdfbf7;
            /* Màu kem nhạt */
            --text-color: #4a4a4a;
            --white: #ffffff;
            --max-width: 1200px;
            --transition: all 0.3s ease;
        }

        /* --- GLOBAL STYLES --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--white);
            overflow-x: hidden;
        }

        h1,
        h2,
        h3 {
            color: var(--primary-color);
            line-height: 1.2;
        }

        a {
            text-decoration: none;
            transition: var(--transition);
        }

        ul {
            list-style: none;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 20px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
        }

        .btn-group .btn img {
            max-width: 150px;

        }

        .btn {
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-download {
            background-color: var(--primary-color);
            color: var(--white);
            border: 2px solid var(--primary-color);
        }

        .btn-download:hover {
            background-color: transparent;
            color: var(--primary-color);
        }

        section {
            padding: 80px 0;
        }

        /* --- SECTION 1: HERO --- */
        .hero {
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            padding: 60px 0 40px;
            overflow: hidden;
        }

        .hero-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 40px;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: #2b7bc4;
        }

        .hero-content p {
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: #555;
        }

        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
        }

        .mockup-container {
            width: 300px;
            height: 600px;
            background: #333;
            border: 12px solid #1a1a1a;
            border-radius: 36px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .mockup-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- SECTION 2: FEATURES --- */
        .features {
            background-color: var(--white);
            text-align: center;
        }

        .section-title {
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.2rem;
            margin-bottom: 15px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-card {
            padding: 40px 30px;
            border-radius: 15px;
            background: var(--light-bg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-bottom: 4px solid transparent;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-bottom: 4px solid var(--accent-color);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(212, 163, 115, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: var(--accent-color);
        }

        .feature-card h3 {
            margin-bottom: 15px;
            font-size: 1.25rem;
        }

        /* --- SECTION 3: BENEFITS --- */
        .benefits {
            background-color: #F0F8FF;
            /* Nền xanh nhạt */
        }

        .benefits-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 60px;
        }

        .benefit-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .benefit-check {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.2rem;
        }

        .benefit-image {
            text-align: right;
        }

        .benefit-image img {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            max-width: 300px;
        }

        /* --- SECTION 4: CTA --- */
        .cta {
            background-color: var(--primary-color);
            color: var(--white);
            text-align: center;
            padding: 60px 0;
        }

        .cta h2 {
            color: var(--white);
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta p {
            margin-bottom: 40px;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .btn-white {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .cta .btn-group {
            justify-content: center;
        }

        /* --- FOOTER (Small) --- */
        footer {
            padding: 30px 0;
            text-align: center;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
            background-color: #f9f9f9;
        }

        .btn-quick-access {
            margin-top: 16px;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 992px) {

            .hero-wrapper,
            .benefits-wrapper {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 30px;
            }

            .btn-group {
                justify-content: center;
            }

            .hero-image {
                order: -1;
            }

            .benefits-image {
                display: none;
            }

            .btn {
                min-width: 150px;
            }

            .btn-group .btn img {
                max-width: 140px;
            }

            .hero {
                padding: 30px 0px;
            }

            .benefit-image img {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .btn {
                width: auto;
                min-width: unset;
                padding: 6px 10px;
                font-size: 13px;
                flex: 0 1 auto;
            }

            .btn-group .btn img {
                max-width: 100px;
            }

            .mockup-container {
                width: 220px;
                height: 440px;
                border: 8px solid #1a1a1a;
            }

            .btn-quick-access {
                margin-top: 16px;
            }

            section {
                padding: 40px 0;
            }

            .hero-btns {
                gap: 8px !important;
                justify-content: center !important;
            }
        }
    </style>
</head>

<body>

    <section class="hero">
        <div class="container">
            <div class="hero-wrapper">
                <div class="hero-content">
                    <h1>Massage tại nhà & Spa chuyên nghiệp</h1>
                    <p>Đặt lịch mát-xa thư giãn, trị liệu chuyên sâu ngay trên ứng dụng. Đội ngũ kỹ thuật viên tay nghề
                        cao sẵn sàng phục vụ bạn mọi lúc, mọi nơi.</p>
                    <div class="btn-group hero-btns" style="display: flex; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <a href="{{ $appstore ?? '#' }}" class="btn btn-white" title="Tải cho IOS (Iphone)"><img
                                src="images/appstore.png"></a>
                        <a href="{{ $chplay ?? '#' }}" class="btn btn-white" title="Tải cho CHPlay"><img
                                src="images/chplay.png"></a>
                        <a href="{{ $web ?? '#' }}" target="_blank" class="btn btn-white" title="Truy cập nhanh"
                           style="color: var(--primary-color);">
                            <img src="/images/logo.png" style="max-width: 40px; margin: 0 8px;" />
                            Truy cập nhanh</a>

                    </div>
                </div>
                <div class="hero-image">
                    <div class="mockup-container">
                        <img src="images/appview.jpg" style="object-fit: contain; padding: 2px; background: #fff;"
                            alt="NHM Spa App Mockup">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Dịch vụ đẳng cấp</h2>
                <p>Trải nghiệm sự thư giãn tuyệt đối với các liệu trình đa dạng</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">💆‍♀️</div>
                    <h3>Đặt lịch dễ dàng</h3>
                    <p>Chọn kỹ thuật viên, thời gian và địa điểm linh hoạt chỉ với vài thao tác đơn giản trên ứng dụng.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌿</div>
                    <h3>Liệu trình đa dạng</h3>
                    <p>Từ mát-xa cổ truyền, xông hơi đá muối đến trị liệu chuyên sâu cổ vai gáy.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💎</div>
                    <h3>Ưu đãi hội viên</h3>
                    <p>Tích điểm đổi quà, nhận voucher giảm giá đặc biệt dành riêng cho khách hàng thân thiết.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3>KTV Chuyên nghiệp</h3>
                    <p>Đội ngũ nhân viên được đào tạo bài bản, phục vụ tận tâm và chu đáo.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="benefits">
        <div class="container">
            <div class="benefits-wrapper">
                <div class="benefits-content">
                    <h2>Tại sao chọn NHM Spa?</h2>
                    <br>
                    <div class="benefit-item">
                        <span class="benefit-check">✓</span>
                        <div>
                            <strong>Tiện lợi tuyệt đối:</strong> Không cần đi xa, Spa đến ngay tại nhà bạn.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-check">✓</span>
                        <div>
                            <strong>Giá cả minh bạch:</strong> Niêm yết gía rõ ràng trên ứng dụng, không phụ phí ẩn.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-check">✓</span>
                        <div>
                            <strong>An toàn & Tin cậy:</strong> Hồ sơ KTV rõ ràng, được xác thực và đánh giá bởi cộng
                            đồng.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-check">✓</span>
                        <div>
                            <strong>Hỗ trợ 24/7:</strong> Tổng đài chăm sóc khách hàng luôn sẵn sàng giải đáp mọi thắc
                            mắc.
                        </div>
                    </div>
                </div>
                <div class="benefit-image">
                    <!-- Cập nhật ảnh spa -->
                    {{-- <img src="images/spa_relax.jpg" alt="Thư giãn cùng NHM Spa"> --}}
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Sẵn sàng thư giãn ngay hôm nay?</h2>
            <p>Tải ứng dụng Masa Home iđể nhận ngay voucher giảm giá 20% cho lần đặt lịch đầu tiên.</p>
            <div class="btn-group hero-btns" style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap">
                <a href="{{ $appstore ?? '#' }}" class="btn btn-white" title="Tải cho IOS (Iphone)"><img
                        src="images/appstore.png"></a>
                <a href="{{ $chplay ?? '#' }}" class="btn btn-white" title="Tải cho CHPlay"><img
                        src="images/chplay.png"></a>
                <a href="{{ $web ?? '#' }}" target="_blank" class="btn btn-white" title="Truy cập nhanh"
                   style="color: var(--primary-color);">
                    <img src="/images/logo.png" style="max-width: 40px; margin: 0 8px;" />
                    Truy cập nhanh</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} Massage Home. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>
