<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($platform_name) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #141414;
            color: #fff;
            min-height: 100vh;
            -webkit-user-select: none;
            user-select: none;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 20px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
            transition: background 0.3s;
        }

        .navbar.scrolled {
            background: #141414;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #e50914;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .nav-links a {
            color: #e5e5e5;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-icon {
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: #e50914;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Hero */
        .hero {
            position: relative;
            height: 50vh;
            min-height: 350px;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.3) 50%, rgba(0, 0, 0, 0.1) 100%),
                linear-gradient(to top, #141414 0%, transparent 30%),
                url('https://images-wixmp-ed30a86b8c4ca887773594c2.wixmp.com/f/2bf7cf63-597a-4df9-a804-226faf3069d5/dh2zj0w-d328e6b7-ec7e-4759-9348-6cadc92640df.jpg/v1/fill/w_360,h_540,q_75,strp/beautiful_black_lady_with_big_butt_by_sexybigbutt4_dh2zj0w-fullview.jpg?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1cm46YXBwOjdlMGQxODg5ODIyNjQzNzNhNWYwZDQxNWVhMGQyNmUwIiwiaXNzIjoidXJuOmFwcDo3ZTBkMTg4OTgyMjY0MzczYTVmMGQ0MTVlYTBkMjZlMCIsIm9iaiI6W1t7ImhlaWdodCI6Ijw9NTQwIiwicGF0aCI6Ii9mLzJiZjdjZjYzLTU5N2EtNGRmOS1hODA0LTIyNmZhZjMwNjlkNS9kaDJ6ajB3LWQzMjhlNmI3LWVjN2UtNDc1OS05MzQ4LTZjYWRjOTI2NDBkZi5qcGciLCJ3aWR0aCI6Ijw9MzYwIn1dXSwiYXVkIjpbInVybjpzZXJ2aWNlOmltYWdlLm9wZXJhdGlvbnMiXX0.2PzsUMbooAmSL7EqBJ9Xev5qB4Lg1eAKbkf1U7oUJo0') center/cover no-repeat;
            background-color: #141414;
        }

        .hero-content {
            position: absolute;
            bottom: 15%;
            left: 4%;
            max-width: 500px;
        }

        .hero-tag {
            display: inline-block;
            background: #e50914;
            padding: 4px 10px;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 3px;
            margin-bottom: 12px;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 12px;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 0.85rem;
        }

        .hero-meta .match {
            color: #46d369;
            font-weight: 600;
        }

        .hero-meta .year {
            color: #aaa;
        }

        .hero-meta .rating {
            border: 1px solid #aaa;
            padding: 0 5px;
            font-size: 0.75rem;
        }

        .hero-meta .duration {
            color: #aaa;
        }

        .hero-desc {
            font-size: 0.95rem;
            line-height: 1.4;
            color: #ccc;
            margin-bottom: 18px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .hero-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-white {
            background: #fff;
            color: #000;
        }

        .btn-white:hover {
            background: rgba(255, 255, 255, 0.75);
        }

        .btn-gray {
            background: rgba(109, 109, 110, 0.7);
            color: #fff;
        }

        .btn-gray:hover {
            background: rgba(109, 109, 110, 0.5);
        }

        .hero-poster {
            position: absolute;
            right: 8%;
            top: 50%;
            transform: translateY(-50%);
            width: 220px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
            z-index: 2;
        }

        .hero-poster img {
            width: 100%;
            height: auto;
            display: block;
        }

        .hero-fade {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(transparent, #141414);
        }
    
        /* Error Message Box */
        .modal-error {
            background: #fff1f2;
            color: #e11d48;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border: 1px solid #fecdd3;
            text-align: left;
            animation: fadeInDown 0.3s ease-out;
        }
        .modal-error i { font-size: 18px; }

    </style>
    <style>
        /* Content Row */
        .content-row {
            padding: 0 4%;
            margin-bottom: 50px;
            position: relative;
        }

        .row-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .row-title {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .row-title i {
            color: #e50914;
            margin-right: 10px;
        }

        /* Movie Grid - 2 columns */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        /* Movie Card */
        .movie-card {
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            background: #1a1a1a;
        }

        .movie-card:hover {
            transform: scale(1.08);
            z-index: 10;
        }

        .card-poster {
            position: relative;
            aspect-ratio: 16/10;
        }

        .card-poster video,
        .card-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }

        .card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent 40%, rgba(0, 0, 0, 0.95));
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 15px;
        }

        .movie-card:hover .card-overlay {
            opacity: 1;
        }

        .overlay-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .circle-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid rgba(255, 255, 255, 0.7);
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
        }

        .circle-btn.play-btn {
            background: #fff;
            color: #000;
            border-color: #fff;
        }

        .circle-btn:hover {
            transform: scale(1.1);
        }

        .overlay-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
        }

        .overlay-meta .match {
            color: #46d369;
            font-weight: 600;
        }

        .overlay-meta span {
            color: #aaa;
        }

        /* Badges */
        .card-badges {
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            display: flex;
            justify-content: space-between;
            z-index: 5;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .badge-new {
            background: #e50914;
        }

        .badge-hd {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #666;
        }

        .badge-unlocked {
            background: #46d369;
            color: #000;
        }

        .preview-timer {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(229, 9, 20, 0.95);
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: none;
            z-index: 20;
        }

        .card-info {
            padding: 12px 15px;
        }

        .card-info h3 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-info p {
            font-size: 0.8rem;
            color: #888;
        }

        .card-price {
            position: absolute;
            bottom: 60px;
            right: 10px;
            background: #e50914;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
        }
    
        /* Error Message Box */
        .modal-error {
            background: #fff1f2;
            color: #e11d48;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border: 1px solid #fecdd3;
            text-align: left;
            animation: fadeInDown 0.3s ease-out;
        }
        .modal-error i { font-size: 18px; }

    </style>
    <style>
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-box {
            background: #181818;
            border-radius: 8px;
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-top {
            background: linear-gradient(135deg, #e50914, #b20710);
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .modal-top h2 {
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .modal-top p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .modal-body {
            padding: 25px;
        }

        .price-box {
            text-align: center;
            margin-bottom: 25px;
        }

        .price-amount {
            font-size: 2.2rem;
            font-weight: 800;
            color: #46d369;
        }

        .price-label {
            color: #888;
            font-size: 0.85rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #aaa;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            background: #2a2a2a;
            border: 2px solid #333;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e50914;
        }

        .form-group input::placeholder {
            color: #666;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #e50914;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background: #f40612;
        }

        .submit-btn:disabled {
            background: #555;
            cursor: not-allowed;
        }

        .status-msg {
            margin-top: 18px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-size: 0.9rem;
            display: none;
        }

        .status-msg.error {
            background: rgba(229, 9, 20, 0.2);
            color: #ff6b6b;
        }

        .status-msg.success {
            background: rgba(70, 211, 105, 0.2);
            color: #46d369;
        }

        .status-msg.loading {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #333;
        }

        .modal-footer img {
            height: 28px;
            opacity: 0.8;
        }

        .modal-footer span {
            color: #666;
            font-size: 0.8rem;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    
        /* Error Message Box */
        .modal-error {
            background: #fff1f2;
            color: #e11d48;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border: 1px solid #fecdd3;
            text-align: left;
            animation: fadeInDown 0.3s ease-out;
        }
        .modal-error i { font-size: 18px; }

    </style>
    <style>
        /* Footer */
        .footer {
            background: #0a0a0a;
            padding: 30px 4%;
            margin-top: 60px;
        }

        .footer-copy {
            text-align: center;
            color: #555;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .nav-links {
                display: none;
            }

            .hero-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 12px 4%;
            }

            .logo {
                font-size: 1.4rem;
            }

            .nav-right {
                gap: 15px;
            }

            .nav-icon {
                font-size: 1rem;
            }

            .avatar {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }

            .hero {
                height: 35vh;
                min-height: 250px;
            }

            .hero-content {
                bottom: 10%;
                left: 4%;
                right: 4%;
                max-width: none;
            }

            .hero-tag {
                padding: 3px 8px;
                font-size: 0.6rem;
                margin-bottom: 8px;
            }

            .hero-title {
                font-size: 1.4rem;
                margin-bottom: 8px;
            }

            .hero-meta {
                gap: 8px;
                font-size: 0.7rem;
                margin-bottom: 8px;
                flex-wrap: wrap;
            }

            .hero-desc {
                display: none;
            }

            .hero-buttons {
                gap: 8px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 0.8rem;
                gap: 5px;
            }

            .hero-fade {
                height: 80px;
            }

            .hero-poster {
                display: none;
            }

            .content-row {
                padding: 0 10px;
                margin-bottom: 30px;
            }

            .row-title {
                font-size: 1.1rem;
            }

            .row-title i {
                margin-right: 6px;
            }

            /* 1 column on mobile */
            .movie-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .movie-card {
                border-radius: 8px;
            }

            .movie-card:hover {
                transform: none;
            }

            .card-poster {
                aspect-ratio: 16/9;
            }

            .card-badges {
                top: 8px;
                left: 8px;
                right: 8px;
            }

            .badge {
                padding: 4px 8px;
                font-size: 0.65rem;
            }

            .card-price {
                bottom: 55px;
                right: 8px;
                padding: 5px 10px;
                font-size: 0.75rem;
            }

            .preview-timer {
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            .card-info {
                padding: 12px 15px;
            }

            .card-info h3 {
                font-size: 0.95rem;
                margin-bottom: 5px;
            }

            .card-info p {
                font-size: 0.8rem;
            }

            .modal {
                padding: 15px;
            }

            .modal-box {
                border-radius: 10px;
            }

            .modal-top {
                padding: 20px 15px;
            }

            .modal-top h2 {
                font-size: 1.1rem;
            }

            .modal-top p {
                font-size: 0.8rem;
            }

            .modal-close {
                top: 10px;
                right: 10px;
                width: 28px;
                height: 28px;
            }

            .modal-body {
                padding: 20px 15px;
            }

            .price-amount {
                font-size: 1.8rem;
            }

            .price-label {
                font-size: 0.75rem;
            }

            .form-group label {
                font-size: 0.8rem;
            }

            .form-group input {
                padding: 12px;
                font-size: 0.9rem;
            }

            .submit-btn {
                padding: 12px;
                font-size: 0.9rem;
            }

            .modal-footer {
                margin-top: 15px;
                padding-top: 15px;
            }

            .modal-footer img {
                height: 22px;
            }

            .modal-footer span {
                font-size: 0.7rem;
            }

            .footer {
                padding: 20px 4%;
                margin-top: 40px;
            }

            .footer-copy {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 400px) {
            .logo {
                font-size: 1.2rem;
            }

            .hero {
                height: 30vh;
                min-height: 200px;
            }

            .hero-title {
                font-size: 1.2rem;
            }

            .hero-meta {
                font-size: 0.6rem;
            }

            .btn {
                padding: 6px 12px;
                font-size: 0.7rem;
            }

            .row-title {
                font-size: 0.95rem;
            }
        }

        video::-webkit-media-controls-download-button {
            display: none !important;
        }

        /* Fullscreen video styles */
        video:fullscreen,
        video:-webkit-full-screen {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }
    
        /* Error Message Box */
        .modal-error {
            background: #fff1f2;
            color: #e11d48;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border: 1px solid #fecdd3;
            text-align: left;
            animation: fadeInDown 0.3s ease-out;
        }
        .modal-error i { font-size: 18px; }

    </style>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-left">
            <a href="#" class="logo"><?= htmlspecialchars($platform_name) ?></a>
            <ul class="nav-links">
                <li><a href="#">18+</a></li>
                <li><a href="#">ALL VIDEOS</a></li>
                <li><a href="#">ONYO WAKUBWA TU 18+</a></li>
                <li><a href="#">New & Popular</a></li>
                <li><a href="#">My List</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <i class="fas fa-search nav-icon"></i>
            <i class="fas fa-bell nav-icon"></i>
            <div class="avatar">18+</div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-tag"><i class="fas fa-crown"></i> PREMIUM ACCESS</span>
            <div class="hero-meta">
                <span class="match">98% Match</span>
                <span class="year"></span>
                <span class="rating"><?= htmlspecialchars($platform_name) ?></span>
                <span class="duration">4.1M views</span>
            </div>
            <p class="hero-desc">video zote za NGONO zilizo vuja bongo na nje ya bongo zipo zote kwa tsh 1000/= tu.</p>
            <div class="hero-buttons">
                <button class="btn btn-white" onclick="playFeatured()">
                    <i class="fas fa-play"></i> ⭐FULL ACCESS⭐
                </button>
                <button class="btn btn-gray">
                    <i class="fas fa-info-circle"></i> More Info
                </button>
            </div>
        </div>
        <div class="hero-poster">
            <img
                src="https://images-wixmp-ed30a86b8c4ca887773594c2.wixmp.com/f/2bf7cf63-597a-4df9-a804-226faf3069d5/dh2zj0w-d328e6b7-ec7e-4759-9348-6cadc92640df.jpg/v1/fill/w_360,h_540,q_75,strp/beautiful_black_lady_with_big_butt_by_sexybigbutt4_dh2zj0w-fullview.jpg?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1cm46YXBwOjdlMGQxODg5ODIyNjQzNzNhNWYwZDQxNWVhMGQyNmUwIiwiaXNzIjoidXJuOmFwcDo3ZTBkMTg4OTgyMjY0MzczYTVmMGQ0MTVlYTBkMjZlMCIsIm9iaiI6W1t7ImhlaWdodCI6Ijw9NTQwIiwicGF0aCI6Ii9mLzJiZjdjZjYzLTU5N2EtNGRmOS1hODA0LTIyNmZhZjMwNjlkNS9kaDJ6ajB3LWQzMjhlNmI3LWVjN2UtNDc1OS05MzQ4LTZjYWRjOTI2NDBkZi5qcGciLCJ3aWR0aCI6Ijw9MzYwIn1dXSwiYXVkIjpbInVybjpzZXJ2aWNlOmltYWdlLm9wZXJhdGlvbnMiXX0.2PzsUMbooAmSL7EqBJ9Xev5qB4Lg1eAKbkf1U7oUJo0">
        </div>
        <div class="hero-fade"></div>
    </section>

    <!-- Episodes Section -->
    <section class="content-row">
        <div class="row-header">
            <h2 class="row-title"><i class="fas fa-fire"></i> LIPIA TSH 1000/= KU-PLAY FULL VIDEO
                ONYO WAKUBWA TU 18+</h2>
        </div>
        <div class="movie-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <div class="movie-card" data-id="<?= htmlspecialchars((string)$video['id']) ?>" 
                         data-title="<?= htmlspecialchars($video['title']) ?>"
                         data-price="<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>"
                         data-slug="<?= htmlspecialchars($video['slug']) ?>"
                         onclick="handleVideoClick(this, false, '<?= BASE_URL ?>/video_view.php?v=<?= htmlspecialchars($video['slug']) ?>')">

                        <div class="card-poster">
                            <video playsinline poster="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>"></video>

                            <div class="card-badges">
                                <span class="badge badge-new">PREMIUM</span>
                                <span class="badge badge-hd">HD</span>
                            </div>

                            <div class="preview-timer" style="display: block;">Lipia ku-play full video</div>
                            <span class="card-price">TSH <?= number_format($video['price'] ?? 1000) ?></span>
                        </div>

                        <div class="card-info">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <p><?= number_format($video['views'] ?? 0) ?> views • <i class="fas fa-star" style="color:#ffd700;font-size:0.7rem;"></i> 9.8</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #555;">
                    <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px; color: #e50914;"></i>
                    <p style="font-size: 1.1rem; font-weight: 600; color: white;">Hakuna video zilizopakiwa bado.</p>
                    <p style="font-size: 0.85rem; margin-top: 5px; color: #888;">Tafadhali rudi baadae.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-copy">
            © <?= date('Y') ?> <?= htmlspecialchars($platform_name) ?>. All rights reserved.
        </div>
    </footer>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-box">
            <div class="modal-top">
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
                <h2><i class="fas fa-unlock"></i> Unlock Content</h2>
                <p id="modalTitle">Episode Title</p>
            </div>
            <div class="modal-body">
                <div class="price-box">
                    <div class="price-amount">TSH 1,000</div>
                    <div class="price-label">Lipia ku-play full video</div>
                </div>
                <div class="form-group">
                    <label>jaza namba ya simu yenye pesa 👇👇</label>
                    <input type="text" id="phoneInput" placeholder="07XXX au 06XXX" maxlength="10">
                </div>
                <button class="submit-btn" id="payBtn" onclick="processPayment()">
                    <i class="fas fa-lock-open"></i> LIPIA SASA
                </button>
                <button onclick="closeModal()" style="width:100%;margin-top:10px;padding:12px;background:transparent;border:1px solid #333;border-radius:5px;color:#888;font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fas fa-times-circle"></i> Ghairi
                </button>
                <div id="statusMsg" class="status-msg"></div>

            </div>
        </div>
    </div>
    <script>

    function showError(type, msg) {
        let errId = '';
        if (type === 'package') errId = 'pkgError';
        else if (type === 'video') errId = 'vError';
        else errId = 'paymentError'; // default for landing1-7

        const errDiv = document.getElementById(errId);
        if (errDiv) {
            errDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
            errDiv.style.display = 'flex';
        } else {
            alert(msg); // fallback
        }
    }

    function hideError(type) {
        let errId = '';
        if (type === 'package') errId = 'pkgError';
        else if (type === 'video') errId = 'vError';
        else errId = 'paymentError';

        const errDiv = document.getElementById(errId);
        if (errDiv) errDiv.style.display = 'none';
    }

        let currentVideoId = null;
        let currentVideoPrice = 0;
        let currentVideoSlug = '';
        let paymentCheckInterval = null;

        function handleVideoClick(element, isPaid, playerUrl) {
            if (isPaid) {
                window.location.href = playerUrl;
            } else {
                currentVideoId = element.getAttribute('data-id');
                currentVideoTitle = element.getAttribute('data-title');
                currentVideoPrice = element.getAttribute('data-price');
                currentVideoSlug = element.getAttribute('data-slug') || '';

                document.getElementById('modalTitle').innerText = currentVideoTitle;
                document.querySelector('.price-amount').innerText = 'TSH ' + parseInt(currentVideoPrice).toLocaleString();

                // Always clear phone field and errors when switching videos
                var phoneEl = document.getElementById('phoneInput') || document.getElementById('phoneNumber');
                if (phoneEl) phoneEl.value = '';
                var statusMsg = document.getElementById('statusMsg');
                if (statusMsg) { statusMsg.style.display = 'none'; statusMsg.className = 'status-msg'; }
                var payBtn = document.getElementById('payBtn');
                if (payBtn) { payBtn.disabled = false; payBtn.innerHTML = '<i class="fas fa-lock-open"></i> LIPIA SASA'; }

                document.getElementById('paymentModal').classList.add('active');

                // Track view & click CTA
                fetch('<?= BASE_URL ?>/api/track', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: currentVideoId, event_type: 'view' }),
                    keepalive: true
                }).catch(err => {});
                fetch('<?= BASE_URL ?>/api/track', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: currentVideoId, event_type: 'click_cta' }),
                    keepalive: true
                }).catch(err => {});
            }
        }

        function closeModal() {
            document.getElementById('paymentModal').classList.remove('active');
            resetPaymentModal();
        }


        // Helper to fetch and handle non-JSON / error responses
        async function fetchAPI(url, options) {
            console.log("Fetching API URL:", url);
            const res = await fetch(url, options);
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error("Non-JSON response from " + url, text);
                throw new Error("Server returned invalid response (Status: " + res.status + ").\n\nContent:\n" + text.substring(0, 300));
            }
        }

        async function processPayment() {
        hideError('payment');
            const phoneInput = document.getElementById('phoneNumber') || document.getElementById('phoneInput');
            const videoInput = document.getElementById('videoID');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            
            // Resolve creator ID dynamically from PHP
            const creatorId = '<?= isset($domain_owner["user_id"]) ? $domain_owner["user_id"] : (isset($video["user_id"]) ? $video["user_id"] : (isset($user_videos[0]["user_id"]) ? $user_videos[0]["user_id"] : "")) ?>';
            
            // Dynamic resolution of video ID
            let videoId = '';
            if (videoInput && videoInput.value) {
                videoId = videoInput.value;
            } else if (typeof selectedVideoID !== 'undefined' && selectedVideoID) {
                videoId = selectedVideoID;
            } else if (typeof currentVideoId !== 'undefined' && currentVideoId) {
                videoId = currentVideoId;
            }
            
            // Dynamic resolution of video slug (for redirects)
            let videoSlug = '';
            if (typeof selectedVideoSlug !== 'undefined' && selectedVideoSlug) {
                videoSlug = selectedVideoSlug;
            } else if (typeof currentVideoSlug !== 'undefined' && currentVideoSlug) {
                videoSlug = currentVideoSlug;
            }
            
            if (!phone || phone.length < 10) {
                showError('payment', "Tafadhali weka namba ya simu sahihi / Please enter a valid phone number.");
                return;
            }
            if (!videoId) {
                showError('payment', "Error: Video ID not found.");
                return;
            }
            
            // Find the button to show loading state
            const btn = document.querySelector('button[onclick="processPayment()"]') || document.querySelector('.btn-full') || document.getElementById('payBtn');
            const originalText = btn ? btn.innerHTML : 'Pay';
            if (btn) {
                btn.innerHTML = 'Initiating Payment...';
                btn.disabled = true;
            }

            try {
                const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
                const data = await fetchAPI(baseUrl + '/api/process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: videoId, phone: phone })
                });
                
                if (data.status === 'success') {
                    if (btn) btn.innerHTML = 'Waiting for PIN...';
                    
                    // Start polling
                    let attempts = 0;
                    const maxAttempts = 24; // 2 minutes
                    const interval = setInterval(async () => {
                        attempts++;
                        if (attempts > maxAttempts) {
                            clearInterval(interval);
                            showError('payment', 'Payment timed out. Please try again.');
                            if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            return;
                        }
                        
                        try {
                            const pollData = await fetchAPI(baseUrl + '/api/check_payment.php?tranid=' + data.tranID);
                            const s = (pollData.payment_status || pollData.status || '').toUpperCase();
                            if (s === 'COMPLETED' || s === 'SUCCESS') {
                                clearInterval(interval);
                                if (btn) btn.innerHTML = 'SUCCESS!';
                                
                                // Set secure 24-hour cookie for streaming.php to avoid mobile data IP rotation lockouts
                                const expires = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
                                document.cookie = "sf_pass_" + creatorId + "=" + encodeURIComponent(data.tranID) + "; expires=" + expires + "; path=/; SameSite=Lax";
                                // Set per-video cookie so watch.php grants 24-hour Pay-Per-Video access
                                const _vid = (pollData && pollData.video_id) ? pollData.video_id : '';
                                if (_vid) document.cookie = "sf_video_" + _vid + "=" + encodeURIComponent(data.tranID) + "; expires=" + expires + "; path=/; SameSite=Lax";
                                
                                setTimeout(() => {
                                    
                                    const resData = typeof pollData !== "undefined" ? pollData : (typeof data !== "undefined" ? data : {});
                                    // Route: channel → streaming.php | single → watch.php
                                    if (resData.monetization_mode === 'channel') {
                                        window.location.href = (typeof baseUrl !== "undefined" ? baseUrl : BASE_URL) + '/streaming.php?creator_id=' + creatorId;
                                    } else if (resData.video_id) {
                                        window.location.href = (typeof baseUrl !== "undefined" ? baseUrl : BASE_URL) + '/watch.php?id=' + resData.video_id;
                                    } else if (resData.global_redirect_url) {
                                        window.location.href = resData.global_redirect_url;
                                    } else {
                                        window.location.href = baseUrl + '/streaming.php?creator_id=' + creatorId;
                                    }
                                }, 1000);
                            } else if (s === 'FAILED' || s === 'CANCELLED') {
                                clearInterval(interval);
                                showError('payment', 'Payment failed or was cancelled.');
                                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            }
                        } catch (e) {
                            console.error('Polling error', e);
                        }
                    }, 5000);
                    
                } else {
                    showError('payment', 'Error: ' + (data.message || 'Payment initiation failed'));
                    if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                }
            } catch (err) {
                showError('payment', 'Connection error.\n\n[Details]:\n' + err.message);
                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
            }
        }
    </script>
</body>

</html>
