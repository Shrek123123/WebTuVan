<?php
// Nếu người dùng bấm đổi ngôn ngữ
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include 'lang/' . $_SESSION['lang'] . '.php';

$auth_header_mode = $auth_header_mode ?? 'register';
$auth_switch = null;
if ($auth_header_mode === 'login') {
    $auth_switch = [
        'href' => 'employerregister.php',
        'label' => $lang['button_register'],
    ];
} else {
    $auth_switch = [
        'href' => 'employerlogin.php',
        'label' => $lang['employer_login_button'],
    ];
}
?>
<style>
    .header a:hover {
        color: #d70018 !important;
        transition: color 0.1s;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-right: 10px;
    }

    .user-info img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-info span {
        font-size: 16px;
        font-weight: bold;
    }

    .user-dropdown {
        position: relative;
        display: inline-block;
        cursor: pointer;
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
        min-width: 120px;
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
        position: relative;
        border-bottom: 1px solid #ddd;
        width: 100%;
        box-sizing: border-box;
    }

    .header nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .navbar {
        display: flex;
        align-items: center;
    }

    .guest-options {
        display: flex;
        gap: 15px;
        margin-left: auto;
        margin-right: 10px;
    }

    .btn-login,
    .btn-register {
        text-decoration: none;
    }

    .auth-switch {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 8px 16px;
        border: 1px solid #d70000;
        border-radius: 6px;
        background: #fff;
        color: #d70000;
        font-weight: bold;
        white-space: nowrap;
        transition: background-color .2s ease, color .2s ease, border-color .2s ease;
    }

    .auth-switch:hover {
        background: #d70000;
        color: #fff !important;
    }

    .desktop-auth-switch {
        margin-left: auto;
        margin-right: 12px;
    }

    .mobile-auth-switch {
        display: none;
    }

    .header-nav {
        display: flex;
        align-items: center;
        flex: 1;
    }

    #lang-switch-btn {
        padding: 8px 16px;
        border: none;
        background: #f5f5f5;
        cursor: pointer;
        border-radius: 4px;
        margin-right: 15px;
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

    .header-right {
        display: none;
    }

    @media (max-width: 768px) {
        .logo-wrap img {
            width: auto !important;
            max-width: 300px;
            height: 65px !important;
            object-fit: contain;
        }

        .menu-toggle {
            display: flex;
        }

        .desktop-auth-switch {
            display: none !important;
        }

        .mobile-auth-switch {
            display: inline-flex;
            position: absolute;
            top: 12px;
            right: 10px;
            margin: 0;
            z-index: 10;
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

        .header-nav #lang-switch-btn {
            margin: 0;
            width: 100%;
            border-radius: 0;
            text-align: left;
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
            background: #f5f5f5;
        }

        .header-right {
            display: flex;
            align-items: center;
            margin-left: auto;
            padding-right: 105px;
        }

        .mobile-login-btn {
            padding: 8px 12px;
            color: #d90000;
            font-weight: bold;
            white-space: nowrap;
        }
    }
</style>

<div class="header">
    <button class="menu-toggle" id="menu-toggle" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="logo-wrap">
        <a href="index.php">
            <img src="image/logo.png" alt="ViecNhe Logo" style="height: 50px; margin-right: 10px;">
        </a>
    </div>

    <div class="header-nav" id="header-nav">
        <div class="header-nav-links">
            <a href="#" style="text-decoration: none; color: #333;">
                <?= $lang['create_cv']; ?>
            </a>
            <a href="feedback_employer.php" style="text-decoration: none; color: #333;">
                <?= $lang['feedback']; ?>
            </a>
        </div>

        <?php if ($auth_switch !== null): ?>
            <a class="auth-switch desktop-auth-switch" href="<?= htmlspecialchars($auth_switch['href'], ENT_QUOTES, 'UTF-8'); ?>">
                <?= htmlspecialchars($auth_switch['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>

        <button id="lang-switch-btn">
            <?php echo ($_SESSION['lang'] === 'vi') ? 'English' : 'Tiếng Việt'; ?>
        </button>
    </div>

    <?php if ($auth_switch !== null): ?>
        <a class="auth-switch mobile-auth-switch" href="<?= htmlspecialchars($auth_switch['href'], ENT_QUOTES, 'UTF-8'); ?>">
            <?= htmlspecialchars($auth_switch['label'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    <?php endif; ?>
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
        }

        if (toggle && sidebar && overlay) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', closeMenu);
        }

        var langBtn = document.getElementById('lang-switch-btn');
        if (langBtn) {
            langBtn.addEventListener('click', function () {
                var currentLang = '<?php echo $_SESSION['lang']; ?>';
                window.location.href = currentLang === 'vi' ? '?lang=en' : '?lang=vi';
            });
        }
    })();
</script>