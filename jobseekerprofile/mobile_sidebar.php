<style>
    .profile-sidebar-toggle {
        display: none;
        border: 0;
        background: transparent;
        color: #fff;
        cursor: pointer;
        padding: 6px;
        margin-left: auto;
    }

    .profile-sidebar-toggle span {
        display: block;
        width: 24px;
        height: 3px;
        margin: 4px 0;
        border-radius: 2px;
        background: currentColor;
    }

    .profile-sidebar-overlay {
        display: none;
    }

    @media (max-width: 768px) {
        .top-bar {
            position: relative;
            flex-direction: row !important;
            align-items: center !important;
        }

        .profile-sidebar-toggle {
            display: block;
        }

        .profile-sidebar-overlay {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 2999;
            background: rgba(0, 0, 0, .4);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .profile-sidebar-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .main {
            display: block !important;
        }

        .main > .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: min(280px, 86vw) !important;
            height: 100vh;
            box-sizing: border-box;
            z-index: 3000;
            overflow-y: auto;
            border: 0 !important;
            box-shadow: -3px 0 14px rgba(0, 0, 0, .2);
            transform: translateX(105%);
            transition: transform .25s ease;
        }

        .main > .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<div class="profile-sidebar-overlay" id="profile-sidebar-overlay"></div>
<script>
    (function () {
        var toggle = document.querySelector('.profile-sidebar-toggle');
        var sidebar = document.querySelector('.main > .sidebar');
        var overlay = document.getElementById('profile-sidebar-overlay');
        if (!toggle || !sidebar || !overlay) return;

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }

        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        overlay.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('button').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!button.closest('.change-info-wrap')) closeSidebar();
            });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeSidebar();
        });
    }());
</script>
