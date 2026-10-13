<style>
    .admin-mobile-menu-toggle,
    .admin-mobile-menu-overlay {
        display: none;
    }

    @media (max-width: 820px) {
        body {
            overflow-x: hidden;
        }

        body.admin-menu-open {
            overflow: hidden;
        }

        .admin-mobile-menu-toggle {
            position: fixed;
            top: 14px;
            right: 14px;
            left: auto;
            z-index: 3002;
            display: inline-flex;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: #0b1e45;
            color: #fff;
            font-size: 25px;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(11, 30, 69, 0.25);
        }

        .admin-mobile-menu-overlay {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: block;
            background: rgba(0, 0, 0, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .admin-mobile-menu-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .sidebar {
            z-index: 3001;
            width: min(82vw, 280px) !important;
            height: 100vh !important;
            padding: 20px 14px !important;
            box-sizing: border-box;
            transform: translateX(-105%);
            transition: transform 0.25s ease;
            overflow-y: auto;
        }

        .sidebar.is-open {
            transform: translateX(0);
        }

        .main,
        .main-content,
        .container,
        .admin-main {
            width: 100% !important;
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding: 72px 15px 20px !important;
            box-sizing: border-box;
        }

        .container {
            margin-top: 0 !important;
        }

        .top-bar,
        .admin-topbar,
        .cards,
        .content-grid {
            flex-wrap: wrap;
        }

        .admin-topbar {
            grid-template-columns: minmax(0, 1fr) auto auto !important;
            gap: 10px !important;
        }

        .admin-topbar h1 {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .cards {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .content-grid {
            grid-template-columns: 1fr !important;
        }

        table {
            display: block;
            max-width: 100%;
            overflow-x: auto;
            white-space: nowrap;
        }

        img {
            max-width: 100%;
        }

        .admin-topbar__logo {
            display: block !important;
            width: min(42vw, 200px) !important;
            height: auto !important;
            max-height: 48px;
            object-fit: contain !important;
        }

        .top-bar img {
            width: min(42vw, 200px) !important;
            height: auto !important;
            max-height: 48px;
            object-fit: contain !important;
        }

        .top-bar {
            gap: 12px;
        }
    }

    @media (max-width: 480px) {
        .cards {
            grid-template-columns: 1fr !important;
        }

        .main,
        .main-content,
        .container,
        .admin-main {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }
    }
</style>

<button type="button" class="admin-mobile-menu-toggle" aria-label="Mở menu quản trị" aria-expanded="false">
    &#9776;
</button>
<div class="admin-mobile-menu-overlay" aria-hidden="true"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menu = document.querySelector('.sidebar');
        const toggle = document.querySelector('.admin-mobile-menu-toggle');
        const overlay = document.querySelector('.admin-mobile-menu-overlay');

        if (!menu || !toggle || !overlay) {
            return;
        }

        const setMenuState = function (isOpen) {
            menu.classList.toggle('is-open', isOpen);
            overlay.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('admin-menu-open', isOpen);
        };

        toggle.addEventListener('click', function () {
            setMenuState(!menu.classList.contains('is-open'));
        });

        overlay.addEventListener('click', function () {
            setMenuState(false);
        });

        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setMenuState(false);
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 820) {
                setMenuState(false);
            }
        });
    });
</script>
