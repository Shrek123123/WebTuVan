<?php
require_once '../config.php';

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include '../lang/' . $_SESSION['lang'] . '.php';

$header_avatar = '../image/defaultavatar.jpg';
$normalize_profile_pic = function ($path) {
    if (empty($path)) {
        return '../image/defaultavatar.jpg';
    }

    $normalized = trim($path);
    if (preg_match('/^(https?:|data:)/i', $normalized)) {
        return $normalized;
    }

    $normalized = ltrim($normalized, '/');
    if (strpos($normalized, '../') === 0) {
        return $normalized;
    }

    if (strpos($normalized, 'uploads/') === 0) {
        return '../' . $normalized;
    }

    return '../uploads/' . basename($normalized);
};

if (isset($_SESSION['employerid'])) {
    $header_employer_id = (int) $_SESSION['employerid'];
    $header_stmt = $conn->prepare("SELECT profile_pic FROM ho_so_hoa_giai_vien WHERE employer_id = ? LIMIT 1");
    if ($header_stmt) {
        $header_stmt->bind_param('i', $header_employer_id);
        $header_stmt->execute();
        $header_result = $header_stmt->get_result();
        if ($header_result && $header_result->num_rows > 0) {
            $header_row = $header_result->fetch_assoc();
            if (!empty($header_row['profile_pic'])) {
                $header_avatar = $normalize_profile_pic($header_row['profile_pic']);
            }
        }
        $header_stmt->close();
    }
}
?>
<style>
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
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
        margin-right: 50px;
    }
    .ra_trang_tim_viec {
        text-decoration: none;
        color: #333;
        margin-right: 15px;
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

    .loggined_option {
        display: flex;
        gap: 15px;
        margin-left: auto;
        align-items: center;
    }
    .loggined_option .ra_trang_tim_viec {
            margin-right: 0;
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
        }

    #lang-switch-btn {
        padding: 8px 16px;
        border: none;
        background: #f5f5f5;
        cursor: pointer;
        border-radius: 4px;
        margin-right: 10px;
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
        gap: 50px;
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

    .dashboard-sidebar-toggle {
        display: none;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        width: 40px;
        height: 40px;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 4px;
        background: transparent;
        cursor: pointer;
    }

    .dashboard-sidebar-toggle span {
        display: block;
        width: 22px;
        height: 2px;
        margin: 0 auto;
        background: #fff;
        border-radius: 2px;
    }

    .dashboard-sidebar-overlay {
        display: none;
    }

    @media (max-width: 768px) {
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

    .mobile-applicant-preview {
        display: none;
    }

    @media (max-width: 768px) {
        .mobile-applicant-preview {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            padding: 12px 16px;
            background: #f3f3f3;
            box-sizing: border-box;
        }

        .header-right {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .mobile-login-btn {
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

        .header-right .user-info span {
            display: none;
        }

        .header-right .dropdown-menu {
            left: auto;
            right: 0;
        }

        .dashboard-topbar {
            flex-direction: row !important;
            align-items: center !important;
            gap: 0 !important;
        }

        .dashboard-sidebar-toggle {
            display: flex;
            flex: 0 0 auto;
            margin-left: auto;
        }

        .dashboard-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 2100;
            width: min(320px, 85vw) !important;
            height: 100vh;
            box-sizing: border-box;
            overflow-y: auto;
            border: 0 !important;
            padding: 24px 16px;
            box-shadow: -8px 0 24px rgba(0, 0, 0, 0.2);
            transform: translateX(105%);
            transition: transform 0.3s ease;
        }

        .dashboard-sidebar.is-open {
            transform: translateX(0);
        }

        .dashboard-sidebar-overlay {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 2050;
            background: rgba(0, 0, 0, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .dashboard-sidebar-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .dashboard-sidebar,
        .dashboard-sidebar-overlay {
            transition: none;
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
        <a href="../employerpage.php">
            <img src="../image/logo.png" alt="ViecNhe Logo" style="height: 50px; margin-right: 10px;">
        </a>
    </div>

    <div class="header-nav" id="header-nav">
        <div class="header-nav-links">
            <a href="#" style="text-decoration: none; color: #333;"><?php echo $lang['create_cv']; ?></a>
            <!-- <a href="contactus_employer.php"
                style="text-decoration: none; color: #333;"><?php echo $lang['contact_us']; ?></a> -->
            <a href="../feedback_employer.php" style="text-decoration: none; color: #333;"><?php echo $lang['feedback']; ?></a>
        </div>
        <div class="loggined_option">
        <a class="ra_trang_tim_viec" href="../index.php"><?php echo $lang['Ra_trang_tim_viec'] ?></a>
        <?php if (isset($_SESSION['usernameemployer'])): ?>
            <div class="user-dropdown">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($header_avatar) ?>" alt="User Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['usernameemployer']); ?></span>
                </div>
                <div class="dropdown-menu">
                    <?php
                    $employerId = isset($_SESSION['employerid']) ? intval($_SESSION['employerid']) : 0;
                    ?>
                    <a href="../employerlogout.php"><?php echo $lang['logout']; ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="guest-options">
                <a href="employerlogin.php" class="btn-login"><?php echo $lang['login/register_employer']; ?></a>
                <a href="index.php" class="btn-register"><?php echo $lang['jobseeker_redirect']; ?></a>
                <a href="admin/adminlogin.php" class="btn-register"><?php echo $lang['admin_login']; ?></a>
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
                    <a href="../employerlogout.php"><?php echo $lang['logout']; ?></a>
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

        function initializeDashboardDrawer() {
            var topbar = document.querySelector('.dashboard-topbar');
            var dashboardSidebar = document.querySelector('.dashboard-sidebar');
            if (!topbar || !dashboardSidebar || topbar.querySelector('.dashboard-sidebar-toggle')) {
                return;
            }

            var dashboardToggle = document.createElement('button');
            dashboardToggle.type = 'button';
            dashboardToggle.className = 'dashboard-sidebar-toggle';
            dashboardToggle.setAttribute('aria-label', 'Open dashboard menu');
            dashboardToggle.setAttribute('aria-controls', 'dashboard-sidebar');
            dashboardToggle.setAttribute('aria-expanded', 'false');
            dashboardToggle.innerHTML = '<span></span><span></span><span></span>';
            dashboardSidebar.id = 'dashboard-sidebar';
            topbar.appendChild(dashboardToggle);

            var dashboardOverlay = document.createElement('div');
            dashboardOverlay.className = 'dashboard-sidebar-overlay';
            dashboardOverlay.id = 'dashboard-sidebar-overlay';
            dashboardOverlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(dashboardOverlay);

            function closeDashboardDrawer() {
                dashboardSidebar.classList.remove('is-open');
                dashboardOverlay.classList.remove('is-open');
                dashboardToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }

            function openDashboardDrawer() {
                dashboardSidebar.classList.add('is-open');
                dashboardOverlay.classList.add('is-open');
                dashboardToggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }

            dashboardToggle.addEventListener('click', function () {
                if (dashboardSidebar.classList.contains('is-open')) {
                    closeDashboardDrawer();
                } else {
                    openDashboardDrawer();
                }
            });
            dashboardOverlay.addEventListener('click', closeDashboardDrawer);
            dashboardSidebar.addEventListener('click', function (event) {
                if (event.target.closest('button') && !event.target.closest('#btn-changeinfo')) {
                    closeDashboardDrawer();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeDashboardDrawer();
                }
            });
            window.addEventListener('resize', function () {
                if (window.matchMedia('(min-width: 769px)').matches) {
                    closeDashboardDrawer();
                }
            });
        }

        var applicantSidebar = null;
        var applicantItems = [];
        var applicantPlaceholders = [];
        var applicantPreview = null;

        function moveApplicantPreview() {
            if (!applicantSidebar) {
                applicantSidebar = document.querySelector('.dashboard-sidebar');
                if (!applicantSidebar) {
                    return;
                }

                applicantItems = Array.prototype.slice.call(
                    applicantSidebar.querySelectorAll('.applicant-preview-heading, .applicant-card, .view-more')
                );
                if (!applicantItems.length) {
                    return;
                }

                applicantItems.forEach(function (item) {
                    var placeholder = document.createComment('applicant-preview-position');
                    item.parentNode.insertBefore(placeholder, item);
                    applicantPlaceholders.push(placeholder);
                });
            }

            var dashboardMain = applicantSidebar.closest('.dashboard-main');
            if (!dashboardMain) {
                return;
            }

            var dashboardContainer = dashboardMain.parentElement;
            if (!dashboardContainer) {
                return;
            }

            var preview = applicantPreview || dashboardContainer.querySelector('.mobile-applicant-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'mobile-applicant-preview';
                dashboardContainer.insertBefore(preview, dashboardMain.nextElementSibling);
            }
            applicantPreview = preview;

            var isMobile = window.matchMedia('(max-width: 768px)').matches;
            applicantItems.forEach(function (item, index) {
                if (isMobile) {
                    preview.appendChild(item);
                } else {
                    applicantSidebar.insertBefore(item, applicantPlaceholders[index]);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                moveApplicantPreview();
                initializeDashboardDrawer();
            });
        } else {
            moveApplicantPreview();
            initializeDashboardDrawer();
        }
        window.addEventListener('resize', moveApplicantPreview);
    })();
</script>