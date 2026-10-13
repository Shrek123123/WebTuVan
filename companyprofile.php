<?php
session_start();

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
include __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';

$company_id = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
if ($company_id <= 0) {
    $company_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
}

$company = null;
if (isset($conn) && $company_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM company WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$company) {
    $stmt = $conn->prepare("SELECT * FROM company ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$company) {
    $company = [
        'company_name' => 'ViecNhe',
        'company_address' => 'Hồ Chí Minh, Việt Nam',
        'industry' => 'Human Resources',
        'information' => 'No company information available yet.',
        'company_logo' => 'image/logo.png',
        'banner_logo' => 'image/banner.jpg'
    ];
}

// Nếu công ty đang chờ duyệt hoặc đã bị từ chối thì không hiển thị hồ sơ công ty
$company_review_status = strtolower(trim((string) ($company['status'] ?? '')));
if (in_array($company_review_status, ['pending_review', 'rejected'], true)) {
    $under_review_message = $lang['company_under_review_notice'] ?? 'The company page is under review by admin';
    $under_review_ok = $lang['ok'] ?? 'OK';
    $under_review_cancel = $lang['cancel'] ?? 'Cancel';
    ?>
    <!DOCTYPE html>
    <html lang="<?= htmlspecialchars($_SESSION['lang']) ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($under_review_message) ?></title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif;
                background: rgba(17, 24, 39, 0.55);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .review-dialog {
                background: #ffffff;
                width: min(440px, 100%);
                border-radius: 16px;
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
                padding: 32px 28px 24px;
                text-align: center;
                animation: review-pop 0.2s ease-out;
            }
            @keyframes review-pop {
                from { transform: scale(0.96); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .review-icon {
                width: 64px;
                height: 64px;
                margin: 0 auto 18px;
                border-radius: 50%;
                background: #fff4e5;
                color: #f59e0b;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 34px;
                font-weight: 700;
            }
            .review-message {
                font-size: 16px;
                line-height: 1.6;
                color: #1f2937;
                margin: 0 0 24px;
            }
            .review-actions {
                display: flex;
                gap: 12px;
                justify-content: center;
            }
            .review-actions button {
                flex: 1;
                padding: 12px 18px;
                border-radius: 10px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                border: none;
                transition: all 0.2s ease;
            }
            .review-btn-ok {
                background: #d43d3d;
                color: #ffffff;
            }
            .review-btn-ok:hover { background: #b93131; }
            .review-btn-cancel {
                background: #f3f4f6;
                color: #374151;
            }
            .review-btn-cancel:hover { background: #e5e7eb; }
        </style>
    </head>
    <body>
        <div class="review-dialog" role="alertdialog" aria-modal="true">
            <div class="review-icon">!</div>
            <p class="review-message"><?= htmlspecialchars($under_review_message) ?></p>
            <div class="review-actions">
                <button type="button" class="review-btn-ok" id="under-review-ok"><?= htmlspecialchars($under_review_ok) ?></button>
                <button type="button" class="review-btn-cancel" id="under-review-cancel"><?= htmlspecialchars($under_review_cancel) ?></button>
            </div>
        </div>
        <script>
            (function () {
                function goBack() {
                    var referrer = document.referrer || '';
                    if (referrer && referrer.indexOf('companyprofile.php') === -1) {
                        window.location.href = referrer;
                    } else if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = 'index.php';
                    }
                }
                document.getElementById('under-review-ok').addEventListener('click', goBack);
                document.getElementById('under-review-cancel').addEventListener('click', goBack);
            })();
        </script>
    </body>
    </html>
    <?php
    exit;
}

$company_id_for_query = isset($company['id']) ? (int) $company['id'] : 0;
$jobseeker_id = isset($_SESSION['jobseeker_id']) ? (int) $_SESSION['jobseeker_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow_company'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($jobseeker_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => $lang['follow_company_login_required'] ?? 'Please login to follow this company.'
        ]);
        exit;
    }

    $company_id_for_toggle = isset($_POST['company_id']) ? (int) $_POST['company_id'] : 0;
    if ($company_id_for_toggle <= 0) {
        echo json_encode([
            'success' => false,
            'message' => $lang['follow_company_invalid_company'] ?? 'Invalid company.'
        ]);
        exit;
    }

    if (isset($conn)) {
        $check_stmt = $conn->prepare('SELECT 1 FROM company_followers WHERE company_id = ? AND jobseeker_id = ? LIMIT 1');
        if ($check_stmt) {
            $check_stmt->bind_param('ii', $company_id_for_toggle, $jobseeker_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $is_following = $check_result->num_rows > 0;
            $check_stmt->close();

            if ($is_following) {
                $delete_stmt = $conn->prepare('DELETE FROM company_followers WHERE company_id = ? AND jobseeker_id = ?');
                if ($delete_stmt) {
                    $delete_stmt->bind_param('ii', $company_id_for_toggle, $jobseeker_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                }
                $is_following = false;
            } else {
                $insert_stmt = $conn->prepare('INSERT INTO company_followers (company_id, jobseeker_id) VALUES (?, ?)');
                if ($insert_stmt) {
                    $insert_stmt->bind_param('ii', $company_id_for_toggle, $jobseeker_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
                $is_following = true;
            }

            $count_stmt = $conn->prepare('SELECT COUNT(*) AS total FROM company_followers WHERE company_id = ?');
            if ($count_stmt) {
                $count_stmt->bind_param('i', $company_id_for_toggle);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $follower_count = (int) ($count_result['total'] ?? 0);
                $count_stmt->close();
            } else {
                $follower_count = 0;
            }

            echo json_encode([
                'success' => true,
                'following' => $is_following,
                'follower_count' => $follower_count,
                'message' => $is_following ? ($lang['follow_company_success'] ?? 'You are now following this company.') : ($lang['unfollow_company_success'] ?? 'You unfollowed this company.')
            ]);
            exit;
        }
    }

    echo json_encode([
        'success' => false,
        'message' => $lang['follow_company_db_error'] ?? 'Unable to update follow status.'
    ]);
    exit;
}

$related_jobs = [];
if ($company_id_for_query > 0 && isset($conn)) {
    $job_sql = "SELECT id, job_title, job_location, salary, job_experience, created_at
                FROM job
                WHERE company_id = ?
                ORDER BY created_at ASC
                LIMIT 6";
    $job_stmt = $conn->prepare($job_sql);
    if ($job_stmt) {
        $job_stmt->bind_param('i', $company_id_for_query);
        $job_stmt->execute();
        $job_result = $job_stmt->get_result();
        while ($row = $job_result->fetch_assoc()) {
            $related_jobs[] = $row;
        }
        $job_stmt->close();
    }
}

$follower_count = 0;
$is_following = false;
if ($company_id_for_query > 0 && isset($conn)) {
    $count_stmt = $conn->prepare('SELECT COUNT(*) AS total FROM company_followers WHERE company_id = ?');
    if ($count_stmt) {
        $count_stmt->bind_param('i', $company_id_for_query);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $follower_count = (int) ($count_result['total'] ?? 0);
        $count_stmt->close();
    }

    if ($jobseeker_id > 0) {
        $followed_stmt = $conn->prepare('SELECT 1 FROM company_followers WHERE company_id = ? AND jobseeker_id = ? LIMIT 1');
        if ($followed_stmt) {
            $followed_stmt->bind_param('ii', $company_id_for_query, $jobseeker_id);
            $followed_stmt->execute();
            $followed_result = $followed_stmt->get_result();
            $is_following = $followed_result->num_rows > 0;
            $followed_stmt->close();
        }
    }
}

$company_logo = normalizeAssetPath(trim((string) ($company['company_logo'] ?? '')));
if ($company_logo === '') {
    $company_logo = 'image/logo.png';
}

$banner_logo = normalizeAssetPath(trim((string) ($company['banner_logo'] ?? '')));
if ($banner_logo === '') {
    $banner_logo = 'image/banner.jpg';
}

$company_address = htmlspecialchars((string) ($company['company_address'] ?? 'Chưa cập nhật'));
$company_industry = htmlspecialchars(translateCompanyIndustry($company['industry'] ?? 'Chưa cập nhật', $lang));
$company_info = nl2br(htmlspecialchars((string) ($company['information'] ?? 'Chưa có thông tin mô tả.')));
$company_name = htmlspecialchars((string) ($company['company_name'] ?? 'ViecNhe'));
$company_size_display = '';
if (!empty($company['company_size'] ?? '')) {
    $company_size_display = htmlspecialchars((string) $company['company_size']);
} else {
    foreach ($related_jobs as $job) {
        if (!empty($job['company_size'] ?? '')) {
            $company_size_display = htmlspecialchars((string) $job['company_size']);
            break;
        }
    }
}

$company_profile_info_title = $lang['company_profile_info_title'] ?? 'Company information';
$company_profile_company_name_label = $lang['company_profile_company_name_label'] ?? 'Company name:';
$company_profile_industry_label = $lang['company_profile_industry_label'] ?? 'Industry:';
$company_profile_address_label = $lang['company_profile_address_label'] ?? 'Address:';
$company_profile_company_size_label = $lang['jobdetail_company_size'] ?? 'Company size:';
$company_profile_introduction_title = $lang['company_profile_introduction_title'] ?? 'Company introduction';
$company_profile_jobs_title = $lang['company_profile_jobs_title'] ?? 'Job openings at :company_name';
$company_profile_job_location = $lang['company_profile_job_location'] ?? 'Location:';
$company_profile_job_salary = $lang['company_profile_job_salary'] ?? 'Salary:';
$company_profile_job_experience = $lang['company_profile_job_experience'] ?? 'Experience:';
$company_profile_job_view_details = $lang['company_profile_job_view_details'] ?? 'View details';
$company_profile_no_jobs = $lang['company_profile_no_jobs'] ?? 'This company does not have any job postings yet.';
$company_profile_jobs_title = str_replace(':company_name', $company_name, $company_profile_jobs_title);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang']) ?>">
<head>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $company_name ?> | ViecNhe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
        }
        a { text-decoration: none; }
        .company-page {
            background: #f5f5f5;
            min-height: 100vh;
        }
        .company-hero {
            position: relative;
            width: min(1200px, 92%);
            margin: 0 auto;
            border-radius: 0 0 18px 18px;
            overflow: visible;
            background: transparent;
            display: flex;
            align-items: stretch;
            justify-content: center;
            padding: 0 0 12px;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 20px;
            width: min(1200px, 92%);
            color: white;
        }
        .hero-logo-block {
            flex-shrink: 0;
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border-radius: 0;
            box-shadow: none;
            overflow: visible;
            padding: 0;
        }
        .hero-logo-block img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            object-position: center;
            border-radius: 0;
            background: transparent;
        }
        .hero-text-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            width: 100%;
            min-width: 0;
            min-height: 180px;
            padding: 22px 22px;
            border-radius: 18px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }
        .hero-text-group::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0.52) 35%, rgba(0,0,0,0.28) 68%, rgba(0,0,0,0.16) 100%);
            z-index: 0;
        }
        .hero-text,
        .follow-company-btn {
            position: relative;
            z-index: 1;
        }
        .hero-text {
            min-width: 0;
            color: #ffffff;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
            font-family: "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif;
        }
        .hero-text h1 {
            margin: 0 0 8px;
            font-size: 32px;
            line-height: 1.2;
            word-break: break-word;
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 0.01em;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: 13px;
            opacity: 1;
            color: #f3f4f6;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .follow-company-btn {
            border: none;
            border-radius: 999px;
            background: #ffffff;
            color: #d43d3d;
            font-size: 15px;
            font-weight: 700;
            padding: 12px 20px;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 10px 24px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
            margin-left: auto;
            justify-self: flex-end;
        }
        .follow-company-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.18);
        }
        .follow-company-btn.is-following {
            background: #d43d3d;
            color: #ffffff;
        }
        .company-shell {
            width: min(1200px, 92%);
            margin: 30px auto 60px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 28px;
        }
        .info-card,
        .job-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(28, 36, 51, 0.06);
        }
        .info-card {
            padding: 24px;
            height: fit-content;
        }
        .info-card h3 {
            margin: 0 0 18px;
            font-size: 22px;
            color: #1f2937;
        }
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .info-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            color: #444;
            line-height: 1.5;
        }
        .info-item i {
            width: 18px;
            color: #d43d3d;
            margin-top: 3px;
        }
        .main-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .company-summary {
            padding: 24px 28px;
        }
        .section-title {
            margin: 0 0 16px;
            font-size: 24px;
            color: #1f2937;
        }
        .summary-text {
            color: #4b5563;
            line-height: 1.8;
            font-size: 16px;
        }
        .jobs-box {
            padding: 24px 28px;
        }
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
            margin-top: 18px;
        }
        .job-card {
            padding: 18px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(28, 36, 51, 0.1);
        }
        .job-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }
        .job-meta {
            color: #555;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .job-meta strong { color: #333; }
        .job-link {
            display: inline-block;
            margin-top: 12px;
            padding: 9px 14px;
            background: #d43d3d;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        .empty-state {
            padding: 18px;
            color: #666;
            background: #fff;
            border-radius: 12px;
            border: 1px dashed #d9d9d9;
        }
        @media (max-width: 900px) {
            .company-shell {
                grid-template-columns: 1fr;
            }
            .hero-content {
                flex-direction: column;
                align-items: flex-start;
            }
            .hero-text h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/homepage/header.php'; ?>

    <div class="company-page">
        <section class="company-hero">
            <div class="hero-content">
                <div class="hero-logo-block" style="margin-left: 20px;">
                    <img src="<?= htmlspecialchars($company_logo) ?>" alt="<?= $company_name ?> logo">
                </div>

                <div class="hero-text-group" style="background-image: url('<?= htmlspecialchars($banner_logo) ?>');">
                    <div class="hero-text" style="margin-left: 8px;">
                        <h1><?= $company_name ?></h1>
                        <div class="hero-meta">
                            <span><i class="fa-solid fa-building"></i> <?= $company_industry ?></span>
                            <span><i class="fa-solid fa-location-dot"></i> <?= $company_address ?></span>
                            <span id="company-follower-meta"><i class="fa-solid fa-user-plus"></i> <?= $lang['company_follower_display_text'] ?>: <span id="company-follower-count"><?= (int) $follower_count ?></span></span>
                        </div>
                    </div>

                    <?php if ($jobseeker_id > 0): ?>
                        <button
                            type="button"
                            id="follow-company-btn"
                            class="follow-company-btn <?= $is_following ? 'is-following' : '' ?>"
                            data-company-id="<?= (int) $company_id_for_query ?>"
                            data-following="<?= $is_following ? '1' : '0' ?>"
                            data-follow-text="<?= htmlspecialchars($lang['follow_company_button'] ?? 'Follow') ?>"
                            data-unfollow-text="<?= htmlspecialchars($lang['unfollow_company_button'] ?? 'Unfollow') ?>"
                        >
                            <?= $is_following ? ($lang['unfollow_company_button'] ?? $lang['follow_company_button']) : ($lang['follow_company_button'] ?? 'Follow') ?>
                        </button>
                    <?php else: ?>
                        <a
                            class="follow-company-btn"
                            href="jobseekerlogin.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                            style="display:inline-block; text-align:center;"
                        >
                            <?= $lang['follow_company_button'] ?? 'Follow' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="company-shell">
            <aside class="info-card">
                <h3><?= $company_profile_info_title ?></h3>
                <div class="info-list">
                    <div class="info-item">
                        <i class="fa-solid fa-building"></i>
                        <div><strong><?= $company_profile_company_name_label ?></strong><br><?= $company_name ?></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-industry"></i>
                        <div><strong><?= $company_profile_industry_label ?></strong><br><?= $company_industry ?></div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div><strong><?= $company_profile_address_label ?></strong><br><?= $company_address ?></div>
                    </div>
                    <?php if ($company_size_display !== ''): ?>
                        <div class="info-item">
                            <i class="fa-solid fa-users"></i>
                            <div><strong><?= $company_profile_company_size_label ?></strong><br><?= $company_size_display ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

            <main class="main-section">
                <section class="company-summary info-card">
                    <h2 class="section-title"><?= $company_profile_introduction_title ?></h2>
                    <div class="summary-text">
                        <?= $company_info ?>
                    </div>
                </section>

                <section class="jobs-box info-card">
                    <h2 class="section-title"><?= $company_profile_jobs_title ?></h2>

                    <?php if (!empty($related_jobs)): ?>
                        <div class="jobs-grid">
                            <?php foreach ($related_jobs as $job): ?>
                                <?php
                                $job_title = htmlspecialchars((string) ($job['job_title'] ?? ''));
                                $job_location = htmlspecialchars((string) ($job['job_location'] ?? ''));
                                $job_salary = htmlspecialchars((string) ($job['salary'] ?? 'Thỏa thuận'));
                                $job_experience = htmlspecialchars(translateJobExperience($job['job_experience'] ?? '', $lang) ?: ($lang['jobdetail_no_data'] ?? 'No data'));
                                ?>
                                <article class="job-card">
                                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                                        <img src="<?= htmlspecialchars($company_logo) ?>" alt="<?= $job_title ?>" style="width:42px;height:42px;object-fit:cover;border-radius:10px;border:1px solid #eee;">
                                        <div style="font-size:13px;color:#666;"><?= $company_name ?></div>
                                    </div>
                                    <div class="job-title"><?= $job_title ?></div>
                                    <div class="job-meta"><strong><?= $company_profile_job_location ?></strong> <?= $job_location ?></div>
                                    <div class="job-meta"><strong><?= $company_profile_job_salary ?></strong> <?= $job_salary ?></div>
                                    <div class="job-meta"><strong><?= $company_profile_job_experience ?></strong> <?= $job_experience ?></div>
                                    <a href="jobdetail.php?id=<?= (int) ($job['id'] ?? 0) ?>" class="job-link"><?= $company_profile_job_view_details ?></a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state"><?= $company_profile_no_jobs ?></div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/homepage/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const followButton = document.getElementById('follow-company-btn');
            if (!followButton) {
                return;
            }

            followButton.addEventListener('click', function () {
                const companyId = Number(followButton.dataset.companyId || 0);
                if (!companyId) {
                    return;
                }

                const formData = new URLSearchParams();
                formData.append('toggle_follow_company', '1');
                formData.append('company_id', companyId);

                fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Unable to update follow status.');
                        return;
                    }

                    const isFollowing = !!data.following;
                    followButton.dataset.following = isFollowing ? '1' : '0';
                    followButton.classList.toggle('is-following', isFollowing);
                    followButton.textContent = isFollowing ? (followButton.dataset.unfollowText || 'Unfollow') : (followButton.dataset.followText || 'Follow');

                    const countNode = document.getElementById('company-follower-count');
                    if (countNode) {
                        countNode.textContent = data.follower_count ?? 0;
                    }
                })
                .catch(() => {
                    alert('Unable to update follow status.');
                });
            });
        });
    </script>
</body>
</html>
