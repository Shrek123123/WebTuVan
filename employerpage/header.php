<?php
session_start();
require_once 'config.php';

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include 'lang/' . $_SESSION['lang'] . '.php';

$header_avatar = 'image/defaultavatar.jpg';
if (isset($_SESSION['employerid'])) {
    $header_employer_id = (int) $_SESSION['employerid'];
    $header_stmt = $conn->prepare("SELECT profile_pic FROM employer_profile WHERE employer_id = ? LIMIT 1");
    if ($header_stmt) {
        $header_stmt->bind_param('i', $header_employer_id);
        $header_stmt->execute();
        $header_result = $header_stmt->get_result();
        if ($header_result && $header_result->num_rows > 0) {
            $header_row = $header_result->fetch_assoc();
            if (!empty($header_row['profile_pic'])) {
                $header_avatar = $header_row['profile_pic'];
            }
        }
        $header_stmt->close();
    }
}
?>
<style>
    .loggined_option {
        display: flex;
        gap: 15px;
        margin-left: auto;
        align-items: center;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-right: 20px;
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

    .ra_trang_tim_viec {
        text-decoration: none;
        color: #333;
        margin-right: 15px;
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
        min-width: 100px;
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

    .header a:hover,
    .header .guest-options a:hover,
    .header .dropdown-menu a:hover {
        color: #d70018 !important;
        transition: color 0.1s;
    }

    .navbar {
        display: flex;
        align-items: center;
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

        .loggined_option .ra_trang_tim_viec {
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
            color: #d90000;
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
        <a href="employerpage.php">
            <img src="image/logo.png" alt="ViecNhe Logo" style="height: 50px; margin-right: 10px;">
        </a>
    </div>

    <div class="header-nav" id="header-nav">
        <div class="header-nav-links">
            <a href="#" style="text-decoration: none; color: #333;"><?php echo $lang['create_cv']; ?></a>
            <!-- <a href="contactus_employer.php"
                style="text-decoration: none; color: #333;"><?php echo $lang['contact_us']; ?></a> -->
            <a href="feedback_employer.php" style="text-decoration: none; color: #333;"><?php echo $lang['feedback']; ?></a>
        </div>
        <div class="loggined_option">
        <?php if (isset($_SESSION['usernameemployer'])): ?>
            <a class="ra_trang_tim_viec" href="index.php"><?php echo $lang['Ra_trang_tim_viec'] ?></a>
            <div class="user-dropdown">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($header_avatar) ?>" alt="User Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['usernameemployer']); ?></span>
                </div>
                <div class="dropdown-menu">
                    <?php
                    $employerId = isset($_SESSION['employerid']) ? intval($_SESSION['employerid']) : 0;
                    ?>
                    <a href="employerprofile/add_job_post.php"><?php echo $lang['profile']; ?></a>
                    <a href="employerlogout.php"><?php echo $lang['logout']; ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="guest-options">
                <a href="employerlogin.php" class="btn-login"><?php echo $lang['login/register_employer']; ?></a>
                <a href="index.php" class="btn-register"><?php echo $lang['jobseeker_redirect']; ?></a>
            </div>
        <?php endif; ?>
        </div>

        <button id="lang-switch-btn">
            <?php echo ($_SESSION['lang'] === 'vi') ? 'English' : 'Tiếng Việt'; ?>
        </button>
    </div>

    <div class="header-right">
        <?php if (isset($_SESSION['usernameemployer'])): ?>
            <div class="user-dropdown">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($header_avatar) ?>" alt="User Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['usernameemployer']); ?></span>
                </div>
                <div class="dropdown-menu">
                    <a href="employerprofile/add_job_post.php"><?php echo $lang['profile']; ?></a>
                    <a href="employerlogout.php"><?php echo $lang['logout']; ?></a>
                </div>
            </div>
        <?php else: ?>
            <a href="employerlogin.php" class="btn-login mobile-login-btn">
                <?php echo $lang['login']; ?>
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