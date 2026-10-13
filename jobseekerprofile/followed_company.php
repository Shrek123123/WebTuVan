<?php
require_once '../config.php';
require_once '../helpers.php';
session_start();

if (!isset($_SESSION['username'])) {
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
    header("Location: $redirect");
    exit();
}

if (!isset($_SESSION['jobseeker_id'])) {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

include '../lang/' . $_SESSION['lang'] . '.php';

$jobseeker_id = (int) $_SESSION['jobseeker_id'];
$interest_csrf_token = $_SESSION['interest_csrf_token'] ??= bin2hex(random_bytes(32));
$interest_action_message = $_SESSION['interest_action_message'] ?? '';
unset($_SESSION['interest_action_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_interested_job'])) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $jobIdToRemove = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);

    if (!hash_equals($interest_csrf_token, $submittedToken) || !$jobIdToRemove || $jobIdToRemove < 1) {
        $_SESSION['interest_action_message'] = $lang['interest_remove_failed'];
    } else {
        $deleteStmt = $conn->prepare('DELETE FROM job_interest_count WHERE job_id = ? AND jobseeker_id = ?');
        if ($deleteStmt) {
            $deleteStmt->bind_param('ii', $jobIdToRemove, $jobseeker_id);
            $deleted = $deleteStmt->execute() && $deleteStmt->affected_rows > 0;
            $deleteStmt->close();
            $_SESSION['interest_action_message'] = $deleted
                ? $lang['interest_removed']
                : $lang['interest_remove_failed'];
        } else {
            $_SESSION['interest_action_message'] = $lang['interest_remove_failed'];
        }
    }

    header('Location: followed_company.php');
    exit();
}

$interested_jobs = [];

$sql = 'SELECT j.id, j.job_title, j.job_location, j.salary, j.job_type,
            c.company_name, c.company_logo, jic.created_at AS interested_at
        FROM job_interest_count jic
        INNER JOIN job j ON j.id = jic.job_id
        LEFT JOIN company c ON c.id = j.company_id
        WHERE jic.jobseeker_id = ?
        ORDER BY jic.created_at DESC';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    throw new RuntimeException('Unable to prepare interested jobs query.');
}
$stmt->bind_param('i', $jobseeker_id);
$stmt->execute();
$result = $stmt->get_result();
while ($job = $result->fetch_assoc()) {
    $interested_jobs[] = $job;
}
$stmt->close();

function interestedJobLogoPath($logo) {
    $logo = trim((string) $logo);
    if ($logo === '') {
        return '../image/logo.png';
    }

    if (preg_match('#^(https?:|data:|/|\\.\\./)#i', $logo)) {
        return $logo;
    }

    return '../' . ltrim($logo, '/');
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <title><?= htmlspecialchars($lang['interested_jobs_title']) ?></title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .top-bar {
            background-color: #943737;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            padding: 10px 20px;
        }

        .top-bar .title {
            font-weight: bold;
            font-size: 15px;
        }

        .main {
            display: flex;
        }

        .sidebar {
            width: 160px;
            border-right: 1px solid #ccc;
            padding: 10px;
            background: #fff;
        }

        .sidebar button {
            width: 100%;
            padding: 6px;
            margin-bottom: 10px;
            font-size: 13px;
            border: 1px solid black;
            background-color: white;
            cursor: pointer;
        }

        .sidebar button:hover {
            background-color: #f2f2f2;
        }

        .change-info-wrap {
            position: relative;
            width: auto;
            display: block;
        }

        .change-info-menu {
            position: absolute;
            top: 0;
            left: calc(100% + 10px);
            display: none;
            flex-direction: column;
            gap: 8px;
            min-width: 190px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e8dede;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.14);
            z-index: 50;
        }

        .change-info-menu.show {
            display: flex;
        }

        .change-info-option {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #f0d8d8;
            background: #fff8f8;
            color: #733333;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        #btn-changeinfo.active,
        #btn-followed-companies.active {
            background: #b23b3b;
            color: #fff;
            font-weight: bold;
        }

        .content-area {
            flex-grow: 1;
            background-color: #f3f3f3;
        }

        .interested-jobs-content {
            padding: 30px;
        }

        .interested-jobs-content h2 {
            color: #b23b3b;
            margin: 0 0 20px;
        }

        .interested-jobs-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 18px;
        }

        .interested-job-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
            padding: 18px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .interested-job-link {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
            color: inherit;
            text-decoration: none;
        }

        .interested-job-card:hover {
            border-color: #b23b3b;
        }

        .interested-job-link img {
            width: 70px;
            height: 70px;
            flex: 0 0 70px;
            object-fit: cover;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .interested-job-info {
            min-width: 0;
        }

        .interested-job-title {
            margin: 0 0 8px;
            color: #333;
            font-size: 18px;
            overflow-wrap: anywhere;
        }

        .interested-job-company,
        .interested-job-details {
            margin: 0 0 8px;
            color: #666;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .interested-job-details {
            margin: 0;
        }

        .interest-action-message {
            margin: 0 0 16px;
            padding: 12px 14px;
            background: #edf9f0;
            border: 1px solid #bfe7c9;
            color: #1e7d45;
        }

        .interest-remove-form {
            align-self: flex-end;
            margin: 0;
        }

        .interest-remove-button {
            border: 1px solid #b23b3b;
            padding: 7px 12px;
            background: #fff;
            color: #a02b2b;
            cursor: pointer;
        }

        .interest-remove-button:hover {
            background: #b23b3b;
            color: #fff;
        }

        .empty-interested-jobs {
            padding: 24px;
            color: #888;
            text-align: center;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: row;
            }

            .main {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                box-sizing: border-box;
                border-right: none;
                border-bottom: 1px solid #ccc;
            }

            .change-info-menu {
                position: static;
                width: 100%;
                box-sizing: border-box;
                margin-top: 5px;
                box-shadow: none;
            }

            .interested-jobs-content {
                padding: 22px 16px;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="top-bar">
        <div class="title"><?= htmlspecialchars($lang['jobseekerprofile']) ?></div>
        <button class="profile-sidebar-toggle" type="button" aria-label="Toggle profile menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <div class="main">
        <div class="sidebar">
            <button id="btn-myprofile" onclick="window.location.href='ho_so_ung_vien.php'"><?= $lang['btn_myprofile'] ?></button>
            <button id="btn-myapplications" onclick="window.location.href='don_ung_tuyen.php'"><?= $lang['btn_myapplications'] ?></button>
            <button id="btn-savedjobs" onclick="window.location.href='cong_viec_da_luu.php'"><?= $lang['btn_savedjobs'] ?></button>
            <button id="btn-followed-companies" class="active" onclick="window.location.href='followed_company.php'"><?= $lang['btn_interested_jobs'] ?></button>
            <div class="change-info-wrap">
                <button id="btn-changeinfo" type="button"><?= $lang['btn_changeinfo'] ?></button>
                <div id="changeInfoMenu" class="change-info-menu" aria-label="Change info actions">
                    <button type="button" class="change-info-option" data-href="thay_doi_email.php"><?= $lang['change_email_action'] ?></button>
                    <button type="button" class="change-info-option" data-href="thay_doi_mat_khau.php"><?= $lang['change_password_action'] ?></button>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="interested-jobs-content">
                <h2><?= htmlspecialchars($lang['interested_jobs_title']) ?></h2>
                <?php if ($interest_action_message !== ''): ?>
                    <div class="interest-action-message" role="status"><?= htmlspecialchars($interest_action_message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (empty($interested_jobs)): ?>
                    <div class="empty-interested-jobs"><?= htmlspecialchars($lang['no_interested_jobs']) ?></div>
                <?php else: ?>
                    <div class="interested-jobs-list">
                        <?php foreach ($interested_jobs as $job): ?>
                            <?php
                            $company_name = (string) ($job['company_name'] ?? '');
                            $job_title = (string) ($job['job_title'] ?? '');
                            $job_location = (string) ($job['job_location'] ?? '');
                            $salary = translateJobSalary($job['salary'] ?? '', $lang);
                            ?>
                            <article class="interested-job-card">
                                <a class="interested-job-link" href="../jobdetail.php?id=<?= (int) $job['id'] ?>">
                                    <img src="<?= htmlspecialchars(interestedJobLogoPath($job['company_logo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="interested-job-info">
                                        <h3 class="interested-job-title"><?= htmlspecialchars($job_title, ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="interested-job-company"><?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="interested-job-details"><?= htmlspecialchars($job_location, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($salary, ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </a>
                                <form class="interest-remove-form" method="POST">
                                    <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($interest_csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="interest-remove-button" type="submit" name="remove_interested_job" value="1"><?= htmlspecialchars($lang['remove_interest'], ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('btn-changeinfo');
            const menu = document.getElementById('changeInfoMenu');
            if (!button || !menu) return;

            button.addEventListener('click', function (event) {
                event.stopPropagation();
                menu.classList.toggle('show');
                button.classList.toggle('active');
            });

            menu.querySelectorAll('.change-info-option').forEach(function (option) {
                option.addEventListener('click', function () {
                    window.location.href = option.dataset.href;
                });
            });

            document.addEventListener('click', function (event) {
                if (!button.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.remove('show');
                    button.classList.remove('active');
                }
            });
        });
    </script>

    <footer>
        <?php require_once '../jobseekerprofile/footer.php'; ?>
    </footer>

    <?php include 'mobile_sidebar.php'; ?>
</body>

</html>
