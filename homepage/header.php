<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$language = $_SESSION['lang'] ?? 'vi';
if (!in_array($language, ['vi', 'en'], true)) {
    $language = 'vi';
}
$_SESSION['lang'] = $language;
include __DIR__ . '/../lang/' . $language . '.php';
$jobseeker_id = (int) ($_SESSION['customer_id'] ?? $_SESSION['jobseeker_id'] ?? 0);
$isCustomer = in_array($_SESSION['role'] ?? '', ['customer', 'jobseeker'], true);
$user_data = $isCustomer ? (string) ($_SESSION['username'] ?? '') : null;
$username = $user_data;
$profilePic = 'image/defaultavatar.jpg';
?>
<style>
    .loggined_option {
        display: flex;
        gap: 15px;
        margin-left: auto;
        align-items: center;
    }

    .loggined_option button {
        border: none;
        background: #f5f5f5;
        cursor: pointer;
        border-radius: 4px;
        padding: 8px 16px;
        margin-right: 20px;
    }
    .ra_trang_ntd {
        text-decoration: none;
        color: #333;
        margin-right: 15px;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-right: 50px;
    }

    .user-info img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
    }

    .user-info button {
        padding: 8px 16px;
        border: none;
        background: #f5f5f5;
        cursor: pointer;
        border-radius: 4px;
        margin-left: 10px;
        /* Thêm khoảng cách giữa ảnh và nút */
    }

    .user-info span {
        font-size: 16px;
        font-weight: bold;
    }

    .user-dropdown {
        position: relative;
        display: inline-block;
        cursor: pointer;
        margin-right: 50px;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        /* nằm ngay dưới .user-info */
        left: 0;
        background-color: white;
        border: 1px solid #ccc;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        flex-direction: column;
        z-index: 1000;
    }

    .dropdown-menu a {
        display: block;
        padding: 10px;
        text-decoration: none;
        color: #333;
        border-bottom: 1px solid #eee;
    }

    .dropdown-menu a:last-child {
        border-bottom: none;
    }

    .user-dropdown:hover .dropdown-menu {
        display: flex;
    }


    body {
        margin: 0;
        padding: 0;
    }

    .header {
        display: flex;
        align-items: center;
        background-color: white;
        padding: 10px;
        border-bottom: 1px solid #ddd;
        width: 100%;
        box-sizing: border-box;
        position: sticky;
        top: 0;
        z-index: 2000;
    }

    .header nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .guest-options {
        display: flex;
        gap: 15px;
        margin-left: auto;
        margin-right: 50px;
    }

    .btn-login,
    .btn-register {
        text-decoration: none;
    }

    #lang-switch-btn {
        padding: 8px 16px;
        border: none;
        background: #f5f5f5;
        cursor: pointer;
        border-radius: 4px;
        margin-right: 50px;
    }

    .logo-wrap {
        display: flex;
        align-items: center;
    }

    .logo-wrap img {
        display: block;
        width: auto;
        max-width: 300px;
    }

    .header-nav {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .header-nav-links {
        display: flex;
        gap: 30px;
        margin-left: 50px;
        align-items: center;
    }

    /* ===== Nut menu 3 gach (chi hien tren mobile) ===== */
    .menu-toggle {
        display: none;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        width: 40px;
        height: 40px;
        padding: 0;
        margin-right: 8px;
        border: none;
        background: transparent;
        cursor: pointer;
    }

    .menu-toggle span {
        display: block;
        width: 26px;
        height: 3px;
        background: #333;
        border-radius: 2px;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1500;
    }

    .sidebar-overlay.active {
        display: block;
    }

    @media (max-width: 768px) {
        .header {
            padding: 10px;
        }

        .logo-wrap {
            min-width: 0;
            flex: 0 1 auto;
        }

        .logo-wrap img {
            width: auto !important;
            max-width: 300px;
            height: 65px !important;
            object-fit: contain;
        }

        .menu-toggle {
            display: flex;
        }

        #lang-switch-btn {
            margin-left: auto;
            margin-right: 0;
        }

        .header-nav {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 270px;
            max-width: 82%;
            flex: none;
            flex-direction: column;
            align-items: stretch;
            gap: 0;
            background: #fff;
            padding: 20px 0;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.25);
            transform: translateX(-105%);
            transition: transform 0.3s ease;
            z-index: 2000;
            overflow-y: auto;
        }

        .header-nav.active {
            transform: translateX(0);
        }

        .header-nav-links {
            flex-direction: column;
            align-items: stretch;
            gap: 0;
            margin-left: 0;
            width: 100%;
        }

        .header-nav-links a {
            width: 100%;
            padding: 14px 20px;
            box-sizing: border-box;
            border-bottom: 1px solid #eee;
        }

        .loggined_option {
            flex-direction: column;
            align-items: stretch;
            gap: 0;
            margin-left: 0;
            width: 100%;
        }

        .loggined_option .ra_trang_ntd {
            margin-right: 0;
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
        }

        .loggined_option .guest-options {
            flex-direction: column;
            gap: 0;
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }

        .guest-options .btn-login,
        .guest-options .btn-register {
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
        }

        .header-nav .user-info {
            margin-right: 0;
            padding: 14px 20px;
            justify-content: flex-start;
        }

        .header-nav .user-dropdown {
            display: none;
        }

        .header-nav .dropdown-menu {
            position: static;
            display: flex;
            width: 100%;
            min-width: 0;
            border: none;
            border-radius: 0;
            box-shadow: none;
        }

        .header-nav .dropdown-menu a {
            padding: 12px 32px;
        }

        .header-nav #lang-switch-btn {
            margin: 0;
            width: 100%;
            border-radius: 0;
            text-align: left;
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
        }
    }

    .header-right {
        display: none;
    }

    @media (max-width: 768px) {
        .header-right {
            display: flex;
            align-items: center;
            flex: 0 0 auto;
            margin-left: auto;
        }

        .mobile-login-btn {
            display: inline-block;
            padding: 8px 12px;
            color: #2f7068;
            font-weight: bold;
            white-space: nowrap;
        }

        .header-nav .guest-options .btn-login {
            display: none;
        }

        .header-right .user-dropdown {
            display: inline-block;
            margin-right: 0;
        }

        .header-right .user-info {
            margin-right: 0;
            gap: 0;
        }

        .header-right .user-info span {
            display: none;
        }

        .header-right .user-info img {
            width: 36px;
            height: 36px;
        }

        .header-right .dropdown-menu {
            left: auto;
            right: 0;
            min-width: 0;
            width: max-content;
            align-items: flex-start;
        }

        .header-right .dropdown-menu a {
            width: max-content;
            align-self: flex-start;
            white-space: nowrap;
            box-sizing: border-box;
        }
    }
    :root {
        --mediator-paper: #f5f3ed;
        --mediator-ink: #1d302f;
        --mediator-teal: #2f7068;
        --mediator-terracotta: #a75e49;
        --mediator-muted: #5e6d69;
        --mediator-line: #dce1da;
    }

    body {
        background: var(--mediator-paper);
        color: var(--mediator-ink);
        font-family: Arial, Helvetica, sans-serif;
    }

    .header {
        position: sticky;
        top: 0;
        min-height: 76px;
        padding: 10px max(22px, calc((100vw - 1160px) / 2));
        border-bottom: 1px solid var(--mediator-line);
        background: rgba(250, 249, 245, .98);
        box-shadow: 0 4px 16px rgba(29, 48, 47, .035);
        z-index: 2000;
    }

    .logo-wrap { flex: 0 0 auto; }
    .logo-wrap a { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; }
    .logo-wrap img { display: none; }
    .mediator-brand-mark {
        display: grid;
        width: 43px;
        height: 43px;
        place-items: center;
        border: 1px solid #a9c0b8;
        background: #e9f0eb;
        color: var(--mediator-teal);
        font: 600 1.05rem Georgia, 'Times New Roman', serif;
    }
    .mediator-brand-name {
        color: var(--mediator-ink);
        font: 600 1.05rem/1.15 Georgia, 'Times New Roman', serif;
        white-space: nowrap;
    }
    .mediator-brand-name small {
        display: block;
        margin-top: 4px;
        color: var(--mediator-muted);
        font: 700 .58rem/1 Arial, Helvetica, sans-serif;
        letter-spacing: .13em;
        text-transform: uppercase;
    }
    .header-nav { gap: 24px; }
    .header-nav-links { gap: 24px; margin-left: 32px; }
    .header-nav-links a, .header .ra_trang_ntd {
        color: var(--mediator-ink) !important;
        font-size: .91rem;
        text-decoration: none !important;
    }
    .header a:hover, .header .guest-options a:hover, .header .dropdown-menu a:hover {
        color: var(--mediator-teal) !important;
    }
    .guest-options { align-items: center; gap: 10px; margin-right: 0; }
    .header .btn-login, .header .btn-register {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0 15px;
        border: 1px solid var(--mediator-line);
        color: var(--mediator-ink);
        font-size: .87rem;
        font-weight: 700;
        text-decoration: none;
    }
    .header .btn-login { border-color: var(--mediator-ink); background: var(--mediator-ink); color: #fff; }
    .header .btn-register { background: transparent; }
    .header #lang-switch-btn {
        margin: 0;
        padding: 8px 10px;
        border: 0;
        background: transparent;
        color: var(--mediator-muted);
        font: inherit;
        font-size: .82rem;
        cursor: pointer;
    }
    .header .user-info { margin-right: 0; }
    .header .user-info img { width: 38px; height: 38px; border: 1px solid var(--mediator-line); }
    .header .user-dropdown:focus-within .dropdown-menu { display: flex; }
    .header .dropdown-menu {
        min-width: 180px;
        border-color: var(--mediator-line);
        border-radius: 0;
        box-shadow: 0 12px 28px rgba(29, 48, 47, .12);
    }
    .header .dropdown-menu a { color: var(--mediator-ink); }
    .menu-toggle { position: relative; z-index: 2002; border: 1px solid var(--mediator-line); }
    .menu-toggle span { background: var(--mediator-ink); }
    .sidebar-overlay { background: rgba(20, 34, 33, .38); }
    .header a:focus-visible, .header button:focus-visible, .menu-toggle:focus-visible {
        outline: 3px solid rgba(47, 112, 104, .42);
        outline-offset: 3px;
    }

    @media (max-width: 900px) {
        .header { padding-right: 16px; padding-left: 16px; }
        .header-nav-links { margin-left: 16px; gap: 15px; }
        .header .btn-register { display: none; }
    }

    @media (max-width: 768px) {
        .header { min-height: 66px; padding: 8px 14px; }
        .logo-wrap img { display: none !important; }
        .mediator-brand-mark { width: 39px; height: 39px; }
        .mediator-brand-name { font-size: .96rem; }
        .menu-toggle { width: 40px; height: 40px; flex: 0 0 40px; margin-right: 10px; }
        .header-nav {
            top: 0;
            width: min(320px, calc(100vw - 44px));
            max-width: none;
            padding: 82px 0 24px;
            border-right: 1px solid var(--mediator-line);
            background: #faf9f5;
            box-shadow: 12px 0 30px rgba(29, 48, 47, .12);
        }
        .header-nav-links a, .loggined_option .ra_trang_ntd,
        .guest-options .btn-register, .header-nav .user-info {
            padding: 15px 22px;
            border-bottom: 1px solid var(--mediator-line);
        }
        .loggined_option { margin: 0; }
        .header-nav .guest-options .btn-login { display: flex; margin: 14px 18px 0; }
        .header-nav .guest-options .btn-register { display: flex; margin: 8px 18px 0; border: 1px solid var(--mediator-line); }
        .header-nav #lang-switch-btn { width: 100%; padding: 15px 22px; border-bottom: 1px solid var(--mediator-line); text-align: left; }
        .header-right .mobile-login-btn {
            padding: 9px 12px;
            border: 1px solid var(--mediator-ink);
            background: var(--mediator-ink);
            color: #fff;
            font-size: .82rem;
        }
    }

    @media (max-width: 390px) {
        .header { padding-right: 10px; padding-left: 10px; }
        .mediator-brand-mark { width: 35px; height: 35px; }
        .mediator-brand-name { font-size: .88rem; }
        .header-right .mobile-login-btn { padding: 8px 9px; }
    }
</style>

<div class="header">
    <button class="menu-toggle" id="menu-toggle" aria-label="Mở menu" aria-controls="header-nav" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="logo-wrap">
        <a href="index.php">
            <span class="mediator-brand-mark" aria-hidden="true">M</span>
            <span class="mediator-brand-name">Mediato<small>Đồng hành hòa giải</small></span>
        </a>
    </div>

    <nav class="header-nav" id="header-nav" aria-label="Điều hướng chính">
        <div class="header-nav-links">
            <a href="index.php">
                Trang chủ
            </a>
            <a href="searchpage.php">
                Tìm hòa giải viên
            </a>
            <!-- <a href="contactus_jobseeker.php" style="text-decoration: none; color: #333;">
                <?= $lang['contact_us']; ?>
            </a> -->
        </div>

        <div class="loggined_option">
        <?php if ($isCustomer): ?>
            <a class="ra_trang_ntd" href="jobseekerprofile/bookings.php">Lịch tư vấn</a>
            <div class="user-dropdown">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($profilePic); ?>" alt="User Avatar">
                    <span><?= htmlspecialchars($username ?? 'Người dùng'); ?></span>
                </div>
                <div class="dropdown-menu">
                    <a href="jobseekerprofile/ho_so_ung_vien.php">
                        Tài khoản
                    </a>
                    <a href="jobseekerprofile/bookings.php">
                        Lịch tư vấn
                    </a>
                    <a href="jobseekerlogout.php">
                        <?= $lang['logout']; ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="guest-options">
                <a href="jobseekerlogin.php" class="btn-login">
                    Đăng nhập
                </a>
                <a href="jobseekerregister.php" class="btn-register">
                    Tạo tài khoản
                </a>

            </div>
        <?php endif; ?>
        </div>

        <button id="lang-switch-btn">
            <?php echo ($_SESSION['lang'] === 'vi') ? 'English' : 'Tiếng Việt'; ?>
        </button>
    </nav>

    <div class="header-right">
        <?php if ($isCustomer): ?>
            <div class="user-dropdown">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($profilePic); ?>" alt="User Avatar">
                    <span><?= htmlspecialchars($username ?? 'Người dùng'); ?></span>
                </div>
                <div class="dropdown-menu">
                    <a href="jobseekerprofile/ho_so_ung_vien.php">
                        Tài khoản
                    </a>
                    <a href="jobseekerprofile/bookings.php">
                        Lịch tư vấn
                    </a>
                    <a href="jobseekerlogout.php">
                        <?= $lang['logout']; ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="jobseekerlogin.php" class="btn-login mobile-login-btn">
                Đăng nhập
            </a>
        <?php endif; ?>
    </div>
</div>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<script>
    (function () {
        var toggle = document.getElementById('menu-toggle');
        var sidebar = document.getElementById('header-nav');
        var overlay = document.getElementById('sidebar-overlay');

        function closeMenu() {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Mở menu');
            }
        }

        if (toggle && sidebar && overlay) {
            toggle.addEventListener('click', function () {
                var isOpen = sidebar.classList.toggle('active');
                overlay.classList.toggle('active', isOpen);
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                toggle.setAttribute('aria-label', isOpen ? 'Đóng menu' : 'Mở menu');
            });
            overlay.addEventListener('click', closeMenu);
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeMenu();
            });
        }

        var langBtn = document.getElementById('lang-switch-btn');
        if (langBtn) {
            langBtn.addEventListener('click', function () {
                var url = new URL(window.location.href);
                var currentLang = '<?php echo $_SESSION['lang']; ?>';
                url.searchParams.set('lang', currentLang === 'vi' ? 'en' : 'vi');
                window.location.href = url.toString();
            });
        }
    })();
</script>