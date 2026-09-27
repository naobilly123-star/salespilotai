<?php
/* ========================================================================== */
/* 🌟 BizProfit AI 소상공인 매출분석 플랫폼 통합 공통 헤더 (header.php) */
/* ========================================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.htm");
    exit;
}

// db.php를 header.php 내부에서 직접 로드하여 $pdo 보장
require_once 'db.php';

/* ========================================================================== */
/* 본인 정보 조회 로직 (모달 초기 바인딩용) */
/* ========================================================================== */
$my_user_info = [];
if (isset($pdo) && isset($_SESSION['user_id'])) {
    $current_my_user_id = (int)$_SESSION['user_id'];
    $my_user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $my_user_stmt->execute(['id' => $current_my_user_id]);
    $my_user_info = $my_user_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* ========================================================================== */
/* DB site_settings에서 실시간 SEO 메타 정보 동적 추출 */
/* ========================================================================== */
$seo_meta = [
    'title'       => '소상공인 AI 매출분석 플랫폼 - BizProfit AI',
    'description' => '소상공인을 위한 실시간 AI 손익분기점(BEP), 날씨 기반 수요예측, 수수료 차감 순이익 분석 및 맞춤형 마케팅 솔루션',
    'keywords'    => '소상공인, 매출분석, 손익분기점, BEP계산, AI마케팅, 상권분석, 배달수수료절감, BizProfit',
    'author'      => 'BizProfit AI Team',
    'robots'      => 'index, follow',
    'og_title'    => '소상공인 AI 매출분석 플랫폼 - BizProfit AI',
    'og_desc'     => '실시간 원가 분석 및 배달 플랫폼 수수료 차감 후 순수익 정밀 진단',
    'og_image'    => 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
    'canonical'   => ''
];

try {
    if (isset($pdo)) {
        $st_query = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'seo_%'");
        if ($st_query) {
            while ($s_row = $st_query->fetch(PDO::FETCH_ASSOC)) {
                $k = $s_row['setting_key'];
                $v = trim($s_row['setting_value'] ?? '');
                if ($k === 'seo_site_title' && !empty($v)) $seo_meta['title'] = $v;
                if ($k === 'seo_site_description' && !empty($v)) $seo_meta['description'] = $v;
                if ($k === 'seo_site_keywords' && !empty($v)) $seo_meta['keywords'] = $v;
                if ($k === 'seo_site_author' && !empty($v)) $seo_meta['author'] = $v;
                if ($k === 'seo_robots' && !empty($v)) $seo_meta['robots'] = $v;
                if ($k === 'seo_og_title' && !empty($v)) $seo_meta['og_title'] = $v;
                if ($k === 'seo_og_description' && !empty($v)) $seo_meta['og_desc'] = $v;
                if ($k === 'seo_og_image' && !empty($v)) $seo_meta['og_image'] = $v;
                if ($k === 'seo_canonical_url' && !empty($v)) $seo_meta['canonical'] = $v;
            }
        }
    }
} catch (Exception $e) {}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= htmlspecialchars($seo_meta['title'], ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($seo_meta['description'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seo_meta['keywords'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="author" content="<?= htmlspecialchars($seo_meta['author'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= htmlspecialchars($seo_meta['robots'], ENT_QUOTES, 'UTF-8') ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($seo_meta['og_title'], ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seo_meta['og_desc'], ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seo_meta['og_image'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($seo_meta['canonical'])): ?>
        <meta property="og:url" content="<?= htmlspecialchars($seo_meta['canonical'], ENT_QUOTES, 'UTF-8') ?>">
        <link rel="canonical" href="<?= htmlspecialchars($seo_meta['canonical'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($seo_meta['og_title'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seo_meta['og_desc'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($seo_meta['og_image'], ENT_QUOTES, 'UTF-8') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --secondary-color: #0f172a;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --card-border: rgba(226, 232, 240, 0.85);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 12px 24px -4px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.04);
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        body {
            font-family: 'Pretendard', 'Inter', -apple-system, BlinkMacSystemFont, system-ui, Roboto, "Helvetica Neue", sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            letter-spacing: -0.015em;
            -webkit-font-smoothing: antialiased;
        }

        #global-app-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            z-index: 999999 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: all;
        }

        #global-app-loader.loader-hidden {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        .loader-content-box {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 26px;
            padding: 2.2rem 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), inset 0 0 0 1px rgba(255, 255, 255, 0.1);
            animation: loaderFloat 3s ease-in-out infinite alternate;
        }

        @keyframes loaderFloat {
            0% { transform: translateY(0); }
            100% { transform: translateY(-6px); }
        }

        .premium-spinner-container {
            position: relative;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .premium-spinner-outer {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #38bdf8;
            border-right-color: #2563eb;
            animation: spinClockwise 1.1s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
            filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.5));
        }

        .premium-spinner-inner {
            position: absolute;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 2.5px solid transparent;
            border-bottom-color: #06b6d4;
            border-left-color: #9333ea;
            animation: spinCounterClockwise 0.85s linear infinite;
        }

        .premium-spinner-center {
            font-size: 1.45rem;
            color: #ffffff;
            animation: pulseGlow 1.8s ease-in-out infinite;
        }

        @keyframes spinClockwise {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes spinCounterClockwise {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }

        @keyframes pulseGlow {
            0%, 100% { transform: scale(0.9); opacity: 0.8; filter: drop-shadow(0 0 4px #38bdf8); }
            50% { transform: scale(1.15); opacity: 1; filter: drop-shadow(0 0 14px #38bdf8); }
        }

        .loader-text-title {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .loader-text-subtitle {
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 400;
            letter-spacing: -0.01em;
        }

        .loader-progress-bar-wrap {
            width: 140px;
            height: 3px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 1rem;
            position: relative;
        }

        .loader-progress-bar-fill {
            width: 40%;
            height: 100%;
            background: linear-gradient(90deg, #38bdf8, #2563eb);
            border-radius: 999px;
            position: absolute;
            animation: progressBarIndeterminate 1.5s infinite ease-in-out;
        }

        @keyframes progressBarIndeterminate {
            0% { left: -40%; }
            100% { left: 100%; }
        }

        /* [개선 핵심] 헤더 및 네비게이션 overflow 완전 해제 (드롭다운 가둠 및 스크롤 발생 원천 차단) */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.94) !important;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.35);
            padding: 0.55rem 1.25rem !important;
            z-index: 1030 !important;
            transition: all 0.3s ease;
            overflow: visible !important; /* 상위 클리핑 방지 */
        }

        .navbar-custom .container-fluid {
            overflow: visible !important;
        }

        .navbar-custom .navbar-brand {
            font-weight: 800;
            font-size: 1.22rem;
            color: #ffffff !important;
            letter-spacing: -0.03em;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
        }
        .navbar-custom .navbar-brand i {
            color: #38bdf8 !important;
            font-size: 1.28rem;
            filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.6));
        }

        .navbar-custom .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.4rem 0.65rem;
            border-radius: 10px;
        }

        .navbar-custom .navbar-nav .nav-link {
            color: #94a3b8 !important;
            font-weight: 500;
            font-size: 0.855rem;
            padding: 0.46rem 0.72rem !important;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 1px;
            white-space: nowrap;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.45rem !important;
        }
        .navbar-custom .navbar-nav .nav-link:hover {
            color: #f8fafc !important;
            background: rgba(255, 255, 255, 0.07);
        }

        .navbar-custom .navbar-nav .nav-link.active {
            color: #ffffff !important;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.42), rgba(29, 78, 216, 0.2)) !important;
            border: 1px solid rgba(96, 165, 250, 0.35);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
            font-weight: 600;
        }

        .navbar-custom .dropdown-menu {
            background: #0b1329 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 14px !important;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6) !important;
            padding: 0.5rem 0.4rem !important;
            margin-top: 0.4rem !important;
            z-index: 1050 !important;
        }

        .navbar-custom .dropdown-item {
            color: #cbd5e1 !important;
            font-size: 0.84rem !important;
            font-weight: 500 !important;
            padding: 0.48rem 0.85rem !important;
            border-radius: 8px !important;
            transition: all 0.18s ease;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            white-space: nowrap;
        }

        .navbar-custom .dropdown-item i {
            font-size: 0.9rem;
            width: 18px;
            text-align: center;
            color: #38bdf8;
        }

        .navbar-custom .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.25) !important;
            color: #ffffff !important;
            transform: translateX(3px);
        }

        .navbar-custom .dropdown-item.active {
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        .navbar-custom .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.08) !important;
            margin: 0.35rem 0.2rem !important;
        }

        .navbar-custom .user-action-group .store-badge {
            font-size: 0.88rem !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.08) !important;
            padding: 0.4rem 0.85rem !important;
            border-radius: 50rem !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6) !important;
        }
        .navbar-custom .user-action-group .store-badge span.store-name-text {
            color: #ffffff !important;
            font-weight: 700 !important;
            letter-spacing: -0.01em !important;
        }
        .navbar-custom .user-action-group .store-badge i {
            color: #38bdf8 !important;
            font-size: 0.95rem !important;
            filter: drop-shadow(0 0 5px rgba(56, 189, 248, 0.6)) !important;
        }

        .navbar-custom .user-action-group .btn-edit-profile {
            font-size: 0.78rem !important;
            padding: 0.36rem 0.75rem !important;
            border-radius: 50rem;
            border: 1px solid rgba(56, 189, 248, 0.4) !important;
            color: #38bdf8 !important;
            background: rgba(56, 189, 248, 0.1) !important;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .navbar-custom .user-action-group .btn-edit-profile:hover {
            background: rgba(56, 189, 248, 0.22) !important;
            color: #ffffff !important;
            border-color: #38bdf8 !important;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
        }

        .navbar-custom .user-action-group .btn-logout {
            font-size: 0.78rem !important;
            padding: 0.36rem 0.8rem !important;
            border-radius: 50rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #cbd5e1;
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .navbar-custom .user-action-group .btn-logout:hover {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.08);
        }

        /* PC 화면 규격 : 스크롤 제거 및 오버플로우 visible 처리 */
        @media (min-width: 992px) {
            .navbar-custom .navbar-collapse {
                overflow: visible !important;
                display: flex !important;
            }
            .navbar-custom .navbar-pc-wrapper {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                overflow: visible !important;
            }
            .navbar-custom .navbar-nav-group {
                display: flex;
                align-items: center;
                flex-wrap: nowrap; /* PC에서 불필요한 줄바꿈 방지 */
                max-width: 82%;
                gap: 2px;
                order: 1;
                overflow: visible !important; /* PC에서 메뉴바 스크롤 원천 제거 */
            }
            .navbar-custom .dropdown-menu {
                position: absolute !important;
            }
            .navbar-custom .user-action-group {
                display: flex;
                align-items: center;
                white-space: nowrap;
                order: 2;
                gap: 0.5rem;
                overflow: visible !important;
            }
            .navbar-custom .user-action-group .user-btns-wrapper {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
        }

        /* 모바일/태블릿 반응형 규격 */
        @media (max-width: 991.98px) {
            .navbar-custom .navbar-collapse {
                width: 100% !important;
                margin-top: 0.65rem;
                background: #090e1a !important;
                padding: 0.85rem !important;
                border-radius: 16px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 16px 40px rgba(0, 0, 0, 0.75);
                max-height: 80vh !important;
                overflow-y: auto !important;
            }

            .navbar-custom .navbar-pc-wrapper {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
            }

            .navbar-custom .user-action-group {
                order: 1 !important;
                width: 100% !important;
                margin: 0 0 0.65rem 0 !important;
                padding: 0.75rem 0.95rem !important;
                background: rgba(255, 255, 255, 0.07) !important;
                border-radius: 12px !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.65rem !important;
            }

            .navbar-custom .user-action-group .store-badge {
                font-size: 1rem !important;
                font-weight: 800 !important;
                color: #ffffff !important;
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8) !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
            }
            .navbar-custom .user-action-group .store-badge span.store-name-text {
                color: #ffffff !important;
                font-weight: 800 !important;
            }

            .navbar-custom .user-action-group .user-btns-wrapper {
                width: 100% !important;
                display: flex !important;
                justify-content: flex-end !important;
                align-items: center !important;
                gap: 0.5rem !important;
            }

            .navbar-custom .navbar-nav-group {
                order: 2 !important;
                flex-direction: column !important;
                width: 100% !important;
                padding-left: 0 !important;
                margin-bottom: 0 !important;
                gap: 3px !important;
                overflow: visible !important;
            }

            .navbar-custom .dropdown-menu {
                background: rgba(255, 255, 255, 0.04) !important;
                border: 1px solid rgba(255, 255, 255, 0.06) !important;
                box-shadow: none !important;
                padding-left: 0.8rem !important;
                margin-top: 0.2rem !important;
                position: static !important;
            }
        }

        .glass-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        .main-container-responsive {
            padding: clamp(1rem, 2vw, 2rem) clamp(0.75rem, 1.8vw, 1.8rem);
            flex: 1;
        }
    </style>

    <script>
        window.AppLoader = {
            show: function(title, subtitle) {
                const loader = document.getElementById('global-app-loader');
                if (!loader) return;
                if (title) document.getElementById('loaderTitle').innerText = title;
                if (subtitle) document.getElementById('loaderSubtitle').innerText = subtitle;
                loader.classList.remove('loader-hidden');
            },
            hide: function() {
                const loader = document.getElementById('global-app-loader');
                if (loader) loader.classList.add('loader-hidden');
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() { window.AppLoader.hide(); }, 120);

            document.addEventListener('click', function(e) {
                const targetLink = e.target.closest('a');
                if (!targetLink) return;
                const href = targetLink.getAttribute('href');
                if (!href || href === '#' || href.startsWith('javascript:') || targetLink.getAttribute('target') === '_blank') return;
                if (targetLink.hasAttribute('data-bs-toggle') || targetLink.classList.contains('no-loader')) return;
                window.AppLoader.show('페이지를 불러오는 중입니다...', 'BizProfit AI 분석 엔진과 동기화하고 있습니다.');
            }, true);

            const toggler = document.querySelector('.navbar-toggler');
            const collapseElement = document.getElementById('navbarNav');
            if (toggler && collapseElement) {
                toggler.addEventListener('click', function () {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseElement, { toggle: false });
                        bsCollapse.toggle();
                    } else {
                        collapseElement.classList.toggle('show');
                    }
                });
            }
        });
        window.addEventListener('pageshow', function() { window.AppLoader.hide(); });
    </script>
</head>
<body>

    <div id="global-app-loader" aria-live="polite" role="status">
        <div class="loader-content-box">
            <div class="premium-spinner-container">
                <div class="premium-spinner-outer"></div>
                <div class="premium-spinner-inner"></div>
                <div class="premium-spinner-center"><i class="fa-solid fa-chart-pie"></i></div>
            </div>
            <div class="loader-text-title" id="loaderTitle"><i class="fa-solid fa-bolt text-warning"></i>BizProfit AI</div>
            <div class="loader-text-subtitle" id="loaderSubtitle">시스템 데이터를 안전하게 불러오는 중입니다...</div>
            <div class="loader-progress-bar-wrap"><div class="loader-progress-bar-fill"></div></div>
        </div>
    </div>

    <!-- 네비게이션 헤더 -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between w-100 flex-wrap" style="overflow: visible;">
                <a class="navbar-brand me-2 me-xl-4" href="dashboard.htm">
                    <i class="fa-solid fa-chart-line"></i>BizProfit AI
                </a>
                
                <button class="navbar-toggler ms-auto" type="button" aria-controls="navbarNav" aria-expanded="false" aria-label="메뉴 토글">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <div class="navbar-pc-wrapper">
                        
                        <!-- 1. 사용자 매장 정보 (1줄) + [반응형 교정] 정보수정/로그아웃 버튼 (다음줄 맨 우측 정렬) -->
                        <div class="user-action-group">
                            <span class="store-badge">
                                <i class="fa-solid fa-shop"></i>
                                <span class="store-name-text" id="headerStoreNameText"><?= htmlspecialchars($_SESSION['store_name'] ?? '내 매장') ?></span>
                            </span>

                            <div class="user-btns-wrapper">
                                <button type="button" class="btn-edit-profile no-loader" data-bs-toggle="modal" data-bs-target="#myProfileModal" title="내 정보 수정">
                                    <i class="fa-solid fa-user-pen"></i>정보수정
                                </button>

                                <a href="logout.htm" class="btn btn-outline-light btn-sm btn-logout">
                                    <i class="fa-solid fa-right-from-bracket"></i>로그아웃
                                </a>
                            </div>
                        </div>

                        <!-- 2. 메뉴 네비게이션 목록 (5대 핵심 대분류 그룹화) -->
                        <ul class="navbar-nav navbar-nav-group me-auto mb-2 mb-lg-0">
                            
                            <li class="nav-item">
                                <a class="nav-link <?= $current_page == 'dashboard.htm' ? 'active' : '' ?>" href="dashboard.htm">
                                    <i class="fa-solid fa-gauge"></i>대시보드
                                </a>
                            </li>

                            <!-- [그룹 1] AI 분석 & 수요 예측 -->
                            <?php 
                                $group1_pages = ['store_diagnosis.htm', 'ai_forecast.htm', 'bep_analysis.htm', 'table_turnover.htm', 'benchmark.htm', 'competitor_intel.htm'];
                                $group1_active = in_array($current_page, $group1_pages);
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= $group1_active ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-chart-pie"></i>AI 분석·진단
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item <?= $current_page == 'store_diagnosis.htm' ? 'active' : '' ?>" href="store_diagnosis.htm"><i class="fa-solid fa-notes-medical"></i>AI 경영건강검진</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'ai_forecast.htm' ? 'active' : '' ?>" href="ai_forecast.htm"><i class="fa-solid fa-cloud-sun-rain"></i>AI 수요예측 (날씨)</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'bep_analysis.htm' ? 'active' : '' ?>" href="bep_analysis.htm"><i class="fa-solid fa-calculator"></i>손익분기점(BEP)</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'table_turnover.htm' ? 'active' : '' ?>" href="table_turnover.htm"><i class="fa-solid fa-chair"></i>피크타임 테이블 회전율</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= $current_page == 'benchmark.htm' ? 'active' : '' ?>" href="benchmark.htm"><i class="fa-solid fa-scale-balanced"></i>동종 상권 비교</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'competitor_intel.htm' ? 'active' : '' ?>" href="competitor_intel.htm"><i class="fa-solid fa-crosshairs"></i>경쟁사 가격 벤치마킹</a></li>
                                </ul>
                            </li>

                            <!-- [그룹 2] 매출·원가·세무 정산 -->
                            <?php 
                                $group2_pages = ['daily_closing.htm', 'sales_calendar.htm', 'tax_report.htm', 'menu_pricing.htm', 'cost_inventory.htm', 'supplies_compare.htm', 'receipt_issuer.htm', 'utility_cost.htm', 'loan_repayment.htm'];
                                $group2_active = in_array($current_page, $group2_pages);
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= $group2_active ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-coins"></i>매출·원가·세무
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item <?= $current_page == 'daily_closing.htm' ? 'active' : '' ?>" href="daily_closing.htm"><i class="fa-solid fa-receipt"></i>일일 영업마감 다이어리</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'sales_calendar.htm' ? 'active' : '' ?>" href="sales_calendar.htm"><i class="fa-solid fa-calendar-days"></i>정산/지출 캘린더</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'tax_report.htm' ? 'active' : '' ?>" href="tax_report.htm"><i class="fa-solid fa-file-invoice-dollar"></i>세무/손익 리포트</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= $current_page == 'menu_pricing.htm' ? 'active' : '' ?>" href="menu_pricing.htm"><i class="fa-solid fa-tags"></i>상품/서비스 원가 & 마진분석</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'cost_inventory.htm' ? 'active' : '' ?>" href="cost_inventory.htm"><i class="fa-solid fa-boxes-stacked"></i>원부자재/상품 시세 & AI 발주</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'supplies_compare.htm' ? 'active' : '' ?>" href="supplies_compare.htm"><i class="fa-solid fa-box-open"></i>포장/소모품 단가비교</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'receipt_issuer.htm' ? 'active' : '' ?>" href="receipt_issuer.htm"><i class="fa-solid fa-file-invoice"></i>간이영수증 즉시발행</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= $current_page == 'utility_cost.htm' ? 'active' : '' ?>" href="utility_cost.htm"><i class="fa-solid fa-bolt"></i>광열비/에너지 누수진단</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'loan_repayment.htm' ? 'active' : '' ?>" href="loan_repayment.htm"><i class="fa-solid fa-credit-card"></i>대출 상환 스케줄러</a></li>
                                </ul>
                            </li>

                            <!-- [그룹 3] 마케팅·리뷰·CRM -->
                            <?php 
                                $group3_pages = ['ai_marketing.htm', 'ai_sns_generator.htm', 'review_sentiment.htm', 'review_ai_assistant.htm', 'crm_messaging.htm', 'loyalty_stamp.htm', 'place_seo_audit.htm'];
                                $group3_active = in_array($current_page, $group3_pages);
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= $group3_active ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-bullhorn"></i>마케팅·CRM
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item <?= $current_page == 'ai_marketing.htm' ? 'active' : '' ?>" href="ai_marketing.htm"><i class="fa-solid fa-wand-magic-sparkles"></i>AI 맞춤 마케팅 전략</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'ai_sns_generator.htm' ? 'active' : '' ?>" href="ai_sns_generator.htm"><i class="fa-solid fa-hashtag"></i>AI SNS 카피라이팅</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'review_sentiment.htm' ? 'active' : '' ?>" href="review_sentiment.htm"><i class="fa-solid fa-comments"></i>평판/리뷰 감성분석</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'review_ai_assistant.htm' ? 'active' : '' ?>" href="review_ai_assistant.htm"><i class="fa-solid fa-headset"></i>리뷰 컴플레인 대응</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= $current_page == 'crm_messaging.htm' ? 'active' : '' ?>" href="crm_messaging.htm"><i class="fa-solid fa-comments-dollar"></i>알림톡/문자 마케팅</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'loyalty_stamp.htm' ? 'active' : '' ?>" href="loyalty_stamp.htm"><i class="fa-solid fa-stamp"></i>단골 스탬프 카드</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'place_seo_audit.htm' ? 'active' : '' ?>" href="place_seo_audit.htm"><i class="fa-solid fa-map-location-dot"></i>지도 플레이스 SEO 진단</a></li>
                                </ul>
                            </li>

                            <!-- [그룹 4] 노무·안전·행정 규제 방어 -->
                            <?php 
                                $group4_pages = ['labor_contract.htm', 'labor_cost_optimizer.htm', 'hygiene_compliance.htm', 'safety_insurance.htm', 'compliance_training.htm', 'compliance_calendar.htm', 'sop_manual.htm', 'allergy_origin_board.htm', 'cctv_compliance.htm', 'equipment_maintenance.htm', 'gov_subsidy.htm', 'commercial_lease.htm', 'turnaround_plan.htm', 'anomaly_alerts.htm'];
                                $group4_active = in_array($current_page, $group4_pages);
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= $group4_active ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-shield-halved"></i>노무·안전·규제
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item <?= $current_page == 'labor_contract.htm' ? 'active' : '' ?>" href="labor_contract.htm"><i class="fa-solid fa-file-signature"></i>표준근로계약서 AI</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'labor_cost_optimizer.htm' ? 'active' : '' ?>" href="labor_cost_optimizer.htm"><i class="fa-solid fa-users-gear"></i>인건비/주휴 시뮬레이터</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'hygiene_compliance.htm' ? 'active' : '' ?>" href="hygiene_compliance.htm"><i class="fa-solid fa-shield-virus"></i>식품위생 점검 체크리스트</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'safety_insurance.htm' ? 'active' : '' ?>" href="safety_insurance.htm"><i class="fa-solid fa-fire-extinguisher"></i>화재안전/배상책임보험</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'compliance_training.htm' ? 'active' : '' ?>" href="compliance_training.htm"><i class="fa-solid fa-graduation-cap"></i>법정의무교육 이수관리</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'compliance_calendar.htm' ? 'active' : '' ?>" href="compliance_calendar.htm"><i class="fa-solid fa-calendar-check"></i>법정의무일정 D-Day</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= $current_page == 'sop_manual.htm' ? 'active' : '' ?>" href="sop_manual.htm"><i class="fa-solid fa-list-check"></i>알바 인수인계 매뉴얼SOP</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'allergy_origin_board.htm' ? 'active' : '' ?>" href="allergy_origin_board.htm"><i class="fa-solid fa-shield-cat"></i>원산지/알레르기 표지판</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'cctv_compliance.htm' ? 'active' : '' ?>" href="cctv_compliance.htm"><i class="fa-solid fa-video"></i>CCTV 설치안내판</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'equipment_maintenance.htm' ? 'active' : '' ?>" href="equipment_maintenance.htm"><i class="fa-solid fa-screwdriver-wrench"></i>매장/설비 유지보수 점검</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= $current_page == 'gov_subsidy.htm' ? 'active' : '' ?>" href="gov_subsidy.htm"><i class="fa-solid fa-hand-holding-dollar"></i>정부 정책지원금 매칭</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'commercial_lease.htm' ? 'active' : '' ?>" href="commercial_lease.htm"><i class="fa-solid fa-building"></i>상가임대차/권리금 진단</a></li>
                                    <li><a class="dropdown-item <?= $current_page == 'turnaround_plan.htm' ? 'active' : '' ?>" href="turnaround_plan.htm"><i class="fa-solid fa-life-ring"></i>폐업방지 골든타임</a></li>
                                    <li><a class="dropdown-item text-danger <?= $current_page == 'anomaly_alerts.htm' ? 'active' : '' ?>" href="anomaly_alerts.htm"><i class="fa-solid fa-triangle-exclamation text-danger"></i>AI 긴급 이상징후 감지</a></li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?= $current_page == 'store_manage.htm' ? 'active' : '' ?>" href="store_manage.htm">
                                    <i class="fa-solid fa-store"></i>사업장 관리
                                </a>
                            </li>

                            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['USER', 'STORE'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $current_page == 'my_inquiries.htm' ? 'active' : '' ?>" href="my_inquiries.htm">
                                        <i class="fa-solid fa-comments"></i>내 1:1 문의
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a class="nav-link <?= $current_page == 'chat.htm' ? 'active' : '' ?>" href="chat.htm">
                                    <i class="fa-solid fa-comment-dots text-info"></i>실시간 채팅
                                </a>
                            </li>

                            <!-- [그룹 5] 최고 관리자 전용 그룹 (ADMIN) -->
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                                <?php 
                                    $admin_pages = ['admin_users.htm', 'admin_api_logs.htm', 'admin_sns_config.htm', 'admin_inquiries.htm', 'admin_notices.htm', 'admin_site_settings.htm'];
                                    $admin_active = in_array($current_page, $admin_pages);
                                ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle text-warning fw-bold <?= $admin_active ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-user-shield text-warning"></i>최고관리자
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item <?= $current_page == 'admin_users.htm' ? 'active' : '' ?>" href="admin_users.htm"><i class="fa-solid fa-users-gear text-warning"></i>회원 관리</a></li>
                                        <li><a class="dropdown-item <?= $current_page == 'admin_api_logs.htm' ? 'active' : '' ?>" href="admin_api_logs.htm"><i class="fa-solid fa-server text-danger"></i>API/DB 연동 검증</a></li>
                                        <li><a class="dropdown-item <?= $current_page == 'admin_sns_config.htm' ? 'active' : '' ?>" href="admin_sns_config.htm"><i class="fa-solid fa-sliders text-info"></i>SNS 옵션 관리</a></li>
                                        <li><a class="dropdown-item <?= $current_page == 'admin_inquiries.htm' ? 'active' : '' ?>" href="admin_inquiries.htm"><i class="fa-solid fa-headset text-info"></i>1:1 문의 접수 관리</a></li>
                                        <li><a class="dropdown-item <?= $current_page == 'admin_notices.htm' ? 'active' : '' ?>" href="admin_notices.htm"><i class="fa-solid fa-bullhorn text-warning"></i>공지사항 관리</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item <?= $current_page == 'admin_site_settings.htm' ? 'active' : '' ?>" href="admin_site_settings.htm"><i class="fa-solid fa-gears text-success"></i>통합 환경설정(API/SEO)</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- 본인 전용 내 정보 수정 모달창 -->
    <div class="modal fade" id="myProfileModal" tabindex="-1" aria-labelledby="myProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                <form id="formMyProfileModal">
                    <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #0f172a, #1e293b) !important;">
                        <h5 class="modal-title fw-bold text-white fs-6" id="myProfileModalLabel">
                            <i class="fa-solid fa-user-pen text-primary me-2"></i>내 정보 수정
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div id="myProfileAlertWrap"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">아이디</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($my_user_info['username'] ?? '') ?>" readonly>
                                <div class="form-text small text-muted">아이디는 변경할 수 없습니다.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">새 비밀번호 (변경 시에만 입력)</label>
                                <input type="password" name="my_new_password" id="my_new_password" class="form-control" placeholder="미입력 시 기존 비밀번호 유지">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">상호명(점포명) <span class="text-danger">*</span></label>
                                <input type="text" name="my_store_name" id="my_store_name" class="form-control" value="<?= htmlspecialchars($my_user_info['store_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">대표자명</label>
                                <input type="text" name="my_owner_name" id="my_owner_name" class="form-control" value="<?= htmlspecialchars($my_user_info['owner_name'] ?? '') ?>" placeholder="대표자 성함">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">연락처</label>
                                <input type="text" name="my_phone" id="my_phone" class="form-control" value="<?= htmlspecialchars($my_user_info['phone'] ?? '') ?>" placeholder="010-0000-0000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">이메일 주소</label>
                                <input type="email" name="my_email" id="my_email" class="form-control" value="<?= htmlspecialchars($my_user_info['email'] ?? '') ?>" placeholder="sample@domain.com">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">사업자등록번호</label>
                                <input type="text" name="my_biz_no" id="my_biz_no" class="form-control" value="<?= htmlspecialchars($my_user_info['biz_no'] ?? '') ?>" placeholder="000-00-00000">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">업종 대분류</label>
                                <select name="my_biz_category" id="my_biz_category" class="form-select" onchange="updateMyProfileSubCategories()">
                                    <option value="">대분류 선택</option>
                                    <option value="음식점/외식업">음식점 / 외식업</option>
                                    <option value="카페/베이커리">카페 / 디저트 / 베이커리</option>
                                    <option value="도소매/유통업">도소매 / 유통업</option>
                                    <option value="서비스/뷰티/여가">서비스 / 뷰티 / 여가·스포츠</option>
                                    <option value="정보통신/IT/온라인">정보통신 / IT / 온라인 쇼핑몰</option>
                                    <option value="교육/학원">교육 / 학원 / 교습소</option>
                                    <option value="제조/인쇄/공방">제조 / 인쇄 / 공방</option>
                                    <option value="기타 업종">기타 업종 (직접 입력)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">상세 소분류</label>
                                <select name="my_biz_sub" id="my_biz_sub" class="form-select" onchange="checkMyProfileCustomSub()">
                                    <option value="">대분류를 먼저 선택하세요</option>
                                </select>
                            </div>
                            <div class="col-12" id="my_custom_wrap" style="display: none;">
                                <label class="form-label fw-bold small text-primary"><i class="fa-solid fa-pen-nib me-1"></i>상세 업종 직접 입력</label>
                                <input type="text" name="my_biz_custom" id="my_biz_custom" class="form-control border-primary" placeholder="구체적인 업종명 입력">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">사업장 주소</label>
                                <input type="text" name="my_address" id="my_address" class="form-control" value="<?= htmlspecialchars($my_user_info['address'] ?? '') ?>" placeholder="사업장 전체 주소">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold" id="btnSubmitMyProfile">
                            <i class="fa-solid fa-check me-1"></i>수정 저장하기
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const myProfileBizData = {
            "음식점/외식업": ["한식 일반", "고기/구이점", "중식", "일식/초밥", "양식/패밀리레스토랑", "치킨/호프", "분식/야식", "패스트푸드", "주점/포차", "배달전문음식점", "직접입력"],
            "카페/베이커리": ["커피전문점(카페)", "베이커리/제과점", "디저트/마카롱", "떡/전통찻집", "아이스크림/빙수", "테이크아웃 전문", "직접입력"],
            "도소매/유통업": ["슈퍼마켓/편의점", "의류/패션잡화", "화장품/뷰티제품", "식료품/정육/과일", "문구/사무용품", "가구/인테리어 소품", "철물/공구", "무인 매장(아이스크림/세탁 등)", "직접입력"],
            "서비스/뷰티/여가": ["미용실/헤어샵", "네일아트/속눈썹/피부", "마사지/스파", "헬스장/필라테스/PT", "당구장/골프연습장/볼링", "PC방/오락실/노래방", "세탁소/빨래방", "반려동물 미용/호텔", "직접입력"],
            "정보통신/IT/온라인": ["소프트웨어 개발/SI", "웹/앱 서비스 운영", "스마트스토어/온라인 쇼핑몰", "통신판매중개업", "데이터/AI 분석 솔루션", "디지털 콘텐츠 제작/유튜브", "직접입력"],
            "교육/학원": ["입시/보습학원", "어학/영어학원", "예체능(음악/미술/무용)", "태권도/유도 체육도장", "독서실/스터디카페", "직업/기술 훈련학원", "직접입력"],
            "제조/인쇄/공방": ["수제공방/가죽/목공", "인쇄/출판/디자인제작", "식품가공/수제도시락제조", "금속가공/정밀부품", "직접입력"],
            "기타 업종": ["직접입력"]
        };

        function updateMyProfileSubCategories(selectedSub = null) {
            const catSelect = document.getElementById('my_biz_category');
            const subSelect = document.getElementById('my_biz_sub');
            const customWrap = document.getElementById('my_custom_wrap');
            if (!catSelect || !subSelect) return;

            const selectedCat = catSelect.value;
            subSelect.innerHTML = '<option value="">상세업종 선택</option>';

            if (selectedCat && myProfileBizData[selectedCat]) {
                myProfileBizData[selectedCat].forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub;
                    opt.textContent = sub;
                    if (selectedSub && sub === selectedSub) {
                        opt.selected = true;
                    }
                    subSelect.appendChild(opt);
                });

                if (selectedCat === "기타 업종" || subSelect.value === '직접입력') {
                    if (customWrap) customWrap.style.display = 'block';
                } else {
                    if (customWrap) customWrap.style.display = 'none';
                }
            } else {
                if (customWrap) customWrap.style.display = 'none';
            }
        }

        function checkMyProfileCustomSub() {
            const subSelect = document.getElementById('my_biz_sub');
            const customWrap = document.getElementById('my_custom_wrap');
            const customInput = document.getElementById('my_biz_custom');
            const catSelect = document.getElementById('my_biz_category');
            if (!subSelect || !customWrap) return;

            if (subSelect.value === '직접입력') {
                customWrap.style.display = 'block';
            } else {
                if (catSelect && catSelect.value !== '기타 업종') {
                    customWrap.style.display = 'none';
                    if (customInput) customInput.value = '';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialBizType = <?= json_encode($my_user_info['biz_type'] ?? '') ?>;
            const catSelect = document.getElementById('my_biz_category');
            const customWrap = document.getElementById('my_custom_wrap');
            const customInput = document.getElementById('my_biz_custom');

            if (catSelect && initialBizType) {
                if (initialBizType.includes(' > ')) {
                    const parts = initialBizType.split(' > ');
                    const mainCat = parts[0].trim();
                    const subCat = parts[1].trim();

                    catSelect.value = mainCat;
                    if (myProfileBizData[mainCat] && myProfileBizData[mainCat].includes(subCat)) {
                        updateMyProfileSubCategories(subCat);
                        if (customWrap) customWrap.style.display = 'none';
                    } else {
                        updateMyProfileSubCategories('직접입력');
                        if (customWrap) customWrap.style.display = 'block';
                        if (customInput) customInput.value = subCat;
                    }
                } else {
                    catSelect.value = initialBizType;
                    if (myProfileBizData[initialBizType]) {
                        updateMyProfileSubCategories();
                    } else {
                        catSelect.value = '기타 업종';
                        updateMyProfileSubCategories('직접입력');
                        if (customWrap) customWrap.style.display = 'block';
                        if (customInput) customInput.value = initialBizType;
                    }
                }
            }

            const btnSubmitMyProfile = document.getElementById('btnSubmitMyProfile');
            const formMyProfile = document.getElementById('formMyProfileModal');
            const alertWrap = document.getElementById('myProfileAlertWrap');
            const modalEl = document.getElementById('myProfileModal');

            if (btnSubmitMyProfile && formMyProfile) {
                btnSubmitMyProfile.addEventListener('click', function(e) {
                    e.preventDefault();

                    const sName = document.getElementById('my_store_name').value.trim();
                    if (!sName) {
                        alertWrap.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i>상호명(점포명)을 입력해주세요.</div>';
                        document.getElementById('my_store_name').focus();
                        return;
                    }

                    btnSubmitMyProfile.disabled = true;
                    btnSubmitMyProfile.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>저장 중...';

                    const formData = new FormData(formMyProfile);

                    fetch('update_my_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alertWrap.innerHTML = '<div class="alert alert-success py-2 px-3 small rounded-3 mb-3"><i class="fa-solid fa-circle-check me-1"></i>' + data.message + '</div>';
                            
                            const headerStoreNameEl = document.getElementById('headerStoreNameText');
                            if (headerStoreNameEl) {
                                headerStoreNameEl.innerText = sName;
                            }

                            const pwdInput = document.getElementById('my_new_password');
                            if (pwdInput) pwdInput.value = '';

                            btnSubmitMyProfile.disabled = false;
                            btnSubmitMyProfile.innerHTML = '<i class="fa-solid fa-check me-1"></i>수정 저장하기';

                            setTimeout(function() {
                                alertWrap.innerHTML = '';
                                if (modalEl && typeof bootstrap !== 'undefined') {
                                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                    if (modalInstance) {
                                        modalInstance.hide();
                                    }
                                }
                            }, 1000);

                        } else {
                            alertWrap.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i>' + (data.message || '저장 중 오류가 발생했습니다.') + '</div>';
                            btnSubmitMyProfile.disabled = false;
                            btnSubmitMyProfile.innerHTML = '<i class="fa-solid fa-check me-1"></i>수정 저장하기';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alertWrap.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i>서버 통신 중 오류가 발생했습니다.</div>';
                        btnSubmitMyProfile.disabled = false;
                        btnSubmitMyProfile.innerHTML = '<i class="fa-solid fa-check me-1"></i>수정 저장하기';
                    });
                });
            }
        });
    </script>

    <div class="container-fluid main-container-responsive">