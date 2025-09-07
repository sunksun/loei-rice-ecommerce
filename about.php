<?php
// เริ่ม session
session_start();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับเรา - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="เรื่องราวของข้าวพันธุ์พื้นเมืองเลย การอนุรักษ์และสืบสานภูมิปัญญาท้องถิ่น เพื่อสุขภาพและสิ่งแวดล้อม">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }

        .nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #a8e6cf;
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.9), rgba(45, 80, 22, 0.9)),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><defs><pattern id="rice-bg" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><circle cx="40" cy="40" r="2" fill="rgba(255,255,255,0.1)"/><ellipse cx="25" cy="25" rx="12" ry="4" fill="rgba(255,255,255,0.05)" transform="rotate(45 25 25)"/><ellipse cx="55" cy="55" rx="10" ry="3" fill="rgba(255,255,255,0.05)" transform="rotate(-30 55 55)"/></pattern></defs><rect width="1200" height="600" fill="url(%23rice-bg)"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: #f8f9fa;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .breadcrumb-list {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            font-size: 0.9rem;
        }

        .breadcrumb-item {
            color: #666;
        }

        .breadcrumb-item a {
            color: #27ae60;
            text-decoration: none;
        }

        /* Content Sections */
        .section {
            padding: 4rem 0;
        }

        .section:nth-child(even) {
            background: #f8f9fa;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .section-subtitle {
            font-size: 1.1rem;
            text-align: center;
            color: #666;
            margin-bottom: 3rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .content-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        .content-text h3 {
            color: #2d5016;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }

        .content-text p {
            margin-bottom: 1.5rem;
        }

        .content-image {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            border-radius: 15px;
            aspect-ratio: 4/3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .content-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
        }

        /* Values Grid */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .value-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            font-size: 3rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }

        .value-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .value-desc {
            color: #666;
            line-height: 1.6;
        }

        /* Team Grid */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-5px);
        }

        .team-image {
            height: 200px;
            background: linear-gradient(135deg, #27ae60, #2d5016);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .team-content {
            padding: 1.5rem;
            text-align: center;
        }

        .team-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d5016;
        }

        .team-role {
            color: #27ae60;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .team-desc {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #27ae60;
            transform: translateX(-50%);
        }

        .timeline-item {
            display: flex;
            margin-bottom: 3rem;
            position: relative;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            flex: 1;
            margin: 0 2rem;
            position: relative;
        }

        .timeline-year {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: #27ae60;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            z-index: 10;
        }

        .timeline-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .timeline-desc {
            color: #666;
            line-height: 1.6;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            text-align: center;
            padding: 4rem 0;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-desc {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: #27ae60;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: #27ae60;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .timeline::before {
                left: 1rem;
            }

            .timeline-item {
                flex-direction: column;
                padding-left: 3rem;
            }

            .timeline-item:nth-child(even) {
                flex-direction: column;
            }

            .timeline-content {
                margin: 0;
            }

            .timeline-year {
                left: 1rem;
                transform: translateY(-50%);
            }

            .values-grid,
            .team-grid,
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 2rem 0;
            }

            .section {
                padding: 2rem 0;
            }

            .cta-section {
                padding: 2rem 0;
            }

            .btn {
                display: block;
                margin: 0.5rem 0;
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.6s ease;
        }

        .slide-in-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.6s ease;
        }

        .slide-in-right.visible {
            opacity: 1;
            transform: translateX(0);
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span>🌾</span>
                <span>ข้าวพื้นเมืองเลย</span>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav-link">หน้าแรก</a>
                <a href="products.php" class="nav-link">สินค้า</a>
                <a href="about.php" class="nav-link" style="color: #a8e6cf;">เกี่ยวกับเรา</a>
                <a href="contact.php" class="nav-link">ติดต่อ</a>
                <button class="cart-btn" onclick="toggleCart()">
                    🛒 ตะกร้า <span id="cartCount">(0)</span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <div class="breadcrumb-list">
                <span class="breadcrumb-item"><a href="index.php">หน้าแรก</a></span>
                <span class="breadcrumb-item">›</span>
                <span class="breadcrumb-item">เกี่ยวกับเรา</span>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="hero-title fade-in">เรื่องราวของเรา</h1>
            <p class="hero-subtitle fade-in">อนุรักษ์และสืบสานภูมิปัญญาท้องถิ่น เพื่อสุขภาพและสิ่งแวดล้อม</p>
        </div>
    </section>

    <!-- About Story -->
    <section class="section">
        <div class="container">
            <div class="content-grid">
                <div class="content-text slide-in-left">
                    <h3>🌱 จุดเริ่มต้นของความใส่ใจ</h3>
                    <p>
                        ข้าวพันธุ์พื้นเมืองเลยเกิดขึ้นจากความห่วงใยต่อการสูญหายของข้าวพันธุ์ดั้งเดิม
                        และความปรารถนาที่จะอนุรักษ์ภูมิปัญญาการเกษตรของบรรพบุรุษ
                    </p>
                    <p>
                        เราเริ่มต้นจากการเก็บรวบรวมเมล็ดพันธุ์ข้าวพื้นเมืองที่เหลืออยู่ในชุมชน
                        ศึกษาวิธีการปลูกแบบดั้งเดิม และพัฒนาวิธีการผลิตที่ผสมผสานความรู้โบราณกับเทคโนโลยีสมัยใหม่
                    </p>
                    <p>
                        วันนี้เราภูมิใจที่ได้เป็นส่วนหนึ่งในการฟื้นฟูข้าวพันธุ์พื้นเมืองให้กลับมาเป็นที่รู้จัก
                        และเป็นทางเลือกเพื่อสุขภาพของครอบครัวไทย
                    </p>
                </div>
                <div class="content-image slide-in-right">
                    🌾
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in">พันธกิจและวิสัยทัศน์</h2>
            <p class="section-subtitle fade-in">สิ่งที่เราเชื่อและมุ่งมั่นที่จะทำ</p>

            <div class="content-grid">
                <div class="content-image slide-in-left">
                    🎯
                </div>
                <div class="content-text slide-in-right">
                    <h3>🎯 วิสัยทัศน์</h3>
                    <p>
                        เป็นผู้นำในการอนุรักษ์และส่งเสริมข้าวพันธุ์พื้นเมืองของประเทศไทย
                        เพื่อให้คนไทยได้บริโภคข้าวที่มีคุณภาพ ปลอดภัย และเป็นมิตรกับสิ่งแวดล้อม
                    </p>

                    <h3>🚀 พันธกิจ</h3>
                    <p>
                        • อนุรักษ์และฟื้นฟูข้าวพันธุ์พื้นเมืองที่หายากและใกล้สูญพันธุ์<br>
                        • ส่งเสริมการเกษตรกรรมแบบยั่งยืนโดยไม่ใช้สารเคมี<br>
                        • สนับสนุนเกษตรกรท้องถิ่นให้มีรายได้ที่มั่นคง<br>
                        • สร้างความตระหนักรู้เกี่ยวกับคุณค่าของข้าวพื้นเมือง<br>
                        • นำเสนอสินค้าคุณภาพสู่ผู้บริโภค
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in">คุณค่าที่เราดูแล</h2>
            <p class="section-subtitle fade-in">หลักการที่เรายึดถือในการดำเนินธุรกิจ</p>

            <div class="values-grid">
                <div class="value-card fade-in">
                    <div class="value-icon">🌱</div>
                    <h3 class="value-title">ความปลอดภัย</h3>
                    <p class="value-desc">
                        ปลูกและผลิตโดยไม่ใช้สารเคมี เพื่อความปลอดภัยของผู้บริโภคและสิ่งแวดล้อม
                    </p>
                </div>

                <div class="value-card fade-in">
                    <div class="value-icon">🏆</div>
                    <h3 class="value-title">คุณภาพเยี่ยม</h3>
                    <p class="value-desc">
                        คัดสรรข้าวพันธุ์พื้นเมืองแท้ คุณภาพสูง ผ่านกระบวนการคัดเลือกอย่างพิถีพิถัน
                    </p>
                </div>

                <div class="value-card fade-in">
                    <div class="value-icon">🤝</div>
                    <h3 class="value-title">การสนับสนุนชุมชน</h3>
                    <p class="value-desc">
                        ร่วมมือกับเกษตรกรท้องถิ่น สร้างรายได้ที่ยั่งยืนให้กับชุมชน
                    </p>
                </div>

                <div class="value-card fade-in">
                    <div class="value-icon">🌍</div>
                    <h3 class="value-title">ความยั่งยืน</h3>
                    <p class="value-desc">
                        ดูแลสิ่งแวดล้อมและอนุรักษ์ความหลากหลายทางชีวภาพของข้าวพื้นเมือง
                    </p>
                </div>

                <div class="value-card fade-in">
                    <div class="value-icon">📚</div>
                    <h3 class="value-title">การสืบทอดภูมิปัญญา</h3>
                    <p class="value-desc">
                        รักษาและส่งต่อความรู้การเกษตรแบบดั้งเดิมสู่คนรุ่นใหม่
                    </p>
                </div>

                <div class="value-card fade-in">
                    <div class="value-icon">❤️</div>
                    <h3 class="value-title">ความใส่ใจ</h3>
                    <p class="value-desc">
                        ใส่ใจในทุกขั้นตอน ตั้งแต่การปลูก การเก็บเกี่ยว จนถึงการส่งมอบสินค้า
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in">เส้นทางการเดินทาง</h2>
            <p class="section-subtitle fade-in">ประวัติการเติบโตของเรา</p>

            <div class="timeline">
                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">เริ่มต้นการรวบรวมพันธุ์ข้าว</h3>
                        <p class="timeline-desc">
                            เริ่มต้นจากการเก็บรวบรวมเมล็ดพันธุ์ข้าวพื้นเมืองจากเกษตรกรอาวุโสในจังหวัดเลย
                            และศึกษาวิธีการปลูกแบบดั้งเดิม
                        </p>
                    </div>
                    <div class="timeline-year">2018</div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">การทดลองปลูกครั้งแรก</h3>
                        <p class="timeline-desc">
                            เริ่มทดลองปลูกข้าวพันธุ์พื้นเมืองในพื้นที่เล็กๆ โดยไม่ใช้สารเคมี
                            และศึกษาวิธีการดูแลที่เหมาะสม
                        </p>
                    </div>
                    <div class="timeline-year">2019</div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">ก่อตั้งกลุ่มเกษตรกร</h3>
                        <p class="timeline-desc">
                            รวมตัวกับเกษตรกรท้องถิ่นเพื่อขยายพื้นที่ปลูกและแบ่งปันความรู้
                            สร้างเครือข่ายการผลิตข้าวพื้นเมือง
                        </p>
                    </div>
                    <div class="timeline-year">2020</div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">เปิดตัวผลิตภัณฑ์</h3>
                        <p class="timeline-desc">
                            เริ่มจำหน่ายข้าวพันธุ์พื้นเมืองครั้งแรก พร้อมพัฒนาผลิตภัณฑ์แปรรูป
                            เช่น ข้าวพอง ข้าวกระยาสารท
                        </p>
                    </div>
                    <div class="timeline-year">2021</div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">ขยายผลิตภัณฑ์</h3>
                        <p class="timeline-desc">
                            พัฒนาผลิตภัณฑ์ใหม่จากข้าวพื้นเมือง เช่น ครีมบำรุงผิว สบู่ธรรมชาติ
                            และเปิดช่องทางการขายออนไลน์
                        </p>
                    </div>
                    <div class="timeline-year">2022</div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">ขยายสู่ตลาดทั่วประเทศ</h3>
                        <p class="timeline-desc">
                            เปิดตัวเว็บไซต์อีคอมเมิร์ซ จัดส่งทั่วประเทศ และเริ่มโครงการศึกษาดูงานเพื่อการอนุรักษ์
                        </p>
                    </div>
                    <div class="timeline-year">2023</div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3 class="timeline-title">การเติบโตอย่างยั่งยืน</h3>
                        <p class="timeline-desc">
                            ขยายเครือข่ายเกษตรกรและพัฒนาระบบการจัดการคุณภาพ
                            เพื่อการเติบโตที่ยั่งยืนและรักษาคุณภาพสินค้า
                        </p>
                    </div>
                    <div class="timeline-year">2024</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in">ผลงานในตัวเลข</h2>
            <p class="section-subtitle fade-in">ความสำเร็จที่เราภูมิใจ</p>

            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-number">12+</div>
                    <div class="stat-label">พันธุ์ข้าวพื้นเมือง</div>
                </div>

                <div class="stat-card fade-in">
                    <div class="stat-number">150+</div>
                    <div class="stat-label">เกษตรกรในเครือข่าย</div>
                </div>

                <div class="stat-card fade-in">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">ครอบครัวที่ไว้วางใจ</div>
                </div>

                <div class="stat-card fade-in">
                    <div class="stat-number">2,000+</div>
                    <div class="stat-label">ไร่ที่ปลูกแบบยั่งยืน</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in">ทีมงานของเรา</h2>
            <p class="section-subtitle fade-in">คนที่อยู่เบื้องหลังความสำเร็จ</p>

            <div class="team-grid">
                <div class="team-card fade-in">
                    <div class="team-image">👨‍🌾</div>
                    <div class="team-content">
                        <h3 class="team-name">สมชาย ใจดี</h3>
                        <div class="team-role">ผู้ก่อตั้งและผู้อำนวยการ</div>
                        <p class="team-desc">
                            เกษตรกรรุ่นใหม่ที่มีความหลงใหลในข้าวพื้นเมือง
                            และมุ่งมั่นในการอนุรักษ์ภูมิปัญญาท้องถิ่น
                        </p>
                    </div>
                </div>

                <div class="team-card fade-in">
                    <div class="team-image">👩‍🔬</div>
                    <div class="team-content">
                        <h3 class="team-name">มาลี สวยงาม</h3>
                        <div class="team-role">หัวหน้าฝ่ายควบคุมคุณภาพ</div>
                        <p class="team-desc">
                            ผู้เชี่ยวชาญด้านการควบคุมคุณภาพอาหาร
                            ดูแลให้สินค้าทุกชิ้นมีคุณภาพตามมาตรฐาน
                        </p>
                    </div>
                </div>

                <div class="team-card fade-in">
                    <div class="team-image">👨‍💼</div>
                    <div class="team-content">
                        <h3 class="team-name">วิชัย เจริญพร</h3>
                        <div class="team-role">ผู้จัดการฝ่ายการตลาด</div>
                        <p class="team-desc">
                            ผู้เชี่ยวชาญด้านการตลาดออนไลน์
                            นำเสนอสินค้าคุณภาพสู่ลูกค้าทั่วประเทศ
                        </p>
                    </div>
                </div>

                <div class="team-card fade-in">
                    <div class="team-image">👩‍🌾</div>
                    <div class="team-content">
                        <h3 class="team-name">สุดา อินแสง</h3>
                        <div class="team-role">ผู้ประสานงานเกษตรกร</div>
                        <p class="team-desc">
                            ผู้ที่เชื่อมโยงระหว่างเกษตรกรกับองค์กร
                            สนับสนุนและพัฒนาเครือข่ายการผลิต
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title fade-in">ร่วมเป็นส่วนหนึ่งกับเรา</h2>
            <p class="cta-desc fade-in">มาร่วมกันอนุรักษ์ข้าวพันธุ์พื้นเมืองเพื่อคนรุ่นหลัง</p>
            <div class="fade-in">
                <a href="products.php" class="btn">เลือกซื้อสินค้า</a>
                <a href="contact.php" class="btn btn-outline">ติดต่อเรา</a>
            </div>
        </div>
    </section>

    <script>
        // การจัดการตะกร้าสินค้า
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        function updateCartCount() {
            const cartCount = document.getElementById('cartCount');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = `(${totalItems})`;
        }

        function toggleCart() {
            window.location.href = 'cart.php';
        }

        // Scroll Animation
        function observeElements() {
            const elements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1
            });

            elements.forEach(element => {
                observer.observe(element);
            });
        }

        // Counter Animation
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const target = parseInt(counter.textContent.replace(/\D/g, ''));
                        const increment = target / 100;
                        let current = 0;

                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                current = target;
                                clearInterval(timer);
                            }

                            const suffix = counter.textContent.replace(/\d/g, '');
                            counter.textContent = Math.floor(current) + suffix;
                        }, 20);

                        observer.unobserve(counter);
                    }
                });
            }, {
                threshold: 0.5
            });

            counters.forEach(counter => {
                observer.observe(counter);
            });
        }

        // เริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            observeElements();
            animateCounters();

            // เพิ่ม animation delay
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Parallax effect สำหรับ hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    </script>
</body>

</html>