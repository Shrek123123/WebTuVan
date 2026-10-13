<?php
require_once 'config.php';
require_once 'helpers.php';
session_start();
if (!isset($_SESSION['job_interest_csrf'])) {
    $_SESSION['job_interest_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_interest') {
    if (($_SESSION['role'] ?? '') !== 'jobseeker' || empty($_SESSION['jobseeker_id'])) {
        http_response_code(403);
        exit('Unauthorized');
    }

    $postedJobId = (int) ($_POST['job_id'] ?? 0);
    $postedToken = (string) ($_POST['csrf_token'] ?? '');
    if ($postedJobId <= 0 || !hash_equals($_SESSION['job_interest_csrf'], $postedToken)) {
        http_response_code(400);
        exit('Invalid request');
    }

    $currentJobseekerId = (int) $_SESSION['jobseeker_id'];
    $interestCheck = $conn->prepare('SELECT id FROM job_interest_count WHERE job_id = ? AND jobseeker_id = ? LIMIT 1');
    $interestCheck->bind_param('ii', $postedJobId, $currentJobseekerId);
    $interestCheck->execute();
    $existingInterest = $interestCheck->get_result()->fetch_assoc();
    $interestCheck->close();

    if ($existingInterest) {
        $interestChange = $conn->prepare('DELETE FROM job_interest_count WHERE job_id = ? AND jobseeker_id = ?');
    } else {
        $interestChange = $conn->prepare('INSERT IGNORE INTO job_interest_count (job_id, jobseeker_id) VALUES (?, ?)');
    }
    $interestChange->bind_param('ii', $postedJobId, $currentJobseekerId);
    $interestChange->execute();
    $interestChange->close();

    header('Location: jobdetail.php?id=' . $postedJobId, true, 303);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết công việc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .job-title {
            font-size: 24px;
            font-weight: bold;
        }

        .tag {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-right: 10px;
        }

        .skill-tag {
            display: inline-block;
            background: #e0f3ff;
            color: #007bff;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            font-size: 13px;
        }

        .sidebar-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .footer-column h4,
        h4 {
            font-size: 16px !important;
            font-family: Arial, sans-serif !important;
            color: #fff !important;
            font-weight: bold !important;
            margin-bottom: 25px !important;
            line-height: 1.15 !important;
        }

        h3 {
            font-size: 18.72px !important;
            font-family: Arial, sans-serif !important;
            color: #D91616 !important;
            font-weight: bold !important;
            margin: 18.72px 0px 20px 0px !important;
        }
        .demucjob {
            font-size: 18.72px !important;
            color: #D90000;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .related-jobs-mobile {
            display: none;
        }

        #applyModal {
            z-index: 3000;
        }

        .modal-backdrop {
            z-index: 2990;
        }

        #applyModal .modal-dialog {
            position: relative;
            z-index: 3001;
        }

        @media (max-width: 767.98px) {
            #applyModal .modal-dialog {
                width: calc(100% - 24px);
                max-width: none;
                margin: 72px auto 12px;
            }

            #applyModal .modal-content {
                max-height: calc(100vh - 84px);
                overflow-y: auto;
            }

            .job-summary-row {
                display: flex;
                flex-wrap: nowrap;
                align-items: flex-start;
            }

            .job-summary-row > .job-summary-label {
                flex: 0 0 42%;
                max-width: 42%;
            }

            .job-summary-row > .job-summary-value {
                flex: 0 0 58%;
                max-width: 58%;
                min-width: 0;
                overflow-wrap: anywhere;
            }
        }

        @media (max-width: 991.98px) {
            .related-jobs-desktop {
                display: none;
            }

            .related-jobs-mobile {
                display: block;
            }
        }
    </style>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AJAX load applyform.php -->

</head>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const applyBtn = document.querySelector('.apply-btn');
        if (!applyBtn) return;

        applyBtn.addEventListener('click', function () {
            const isLoggedIn = this.getAttribute('data-logged-in') === '1';
            const loginUrl = this.getAttribute('data-login-url');
            const jobId = this.getAttribute('data-job-id');

            if (!isLoggedIn) {
                window.location.href = loginUrl;
                return;
            }

            if (this.getAttribute('data-already-applied') === '1') {
                window.alert(this.getAttribute('data-duplicate-message') || 'You have applied to this job before and therefore cannot apply again.');
                return;
            }

            // Hiển thị modal
            const applyModal = new bootstrap.Modal(document.getElementById('applyModal'));
            applyModal.show();

            // Hiển thị loading
            document.getElementById('applyFormContent').innerHTML = `
                <div class="modal-body text-center p-10">
                    <div class="spinner-border" role="status"></div>
                    <p>Loading...</p>
                </div>
            `;

            // Load form qua AJAX
            fetch('applyform.php?id=' + encodeURIComponent(jobId))
                .then(response => response.text())
                .then(html => {
                    document.getElementById('applyFormContent').innerHTML = html;
                });
        });
    });
</script>

<body>

    <?php require_once 'homepage/header.php' ?>
    <div>

    </div>
    <div class="container my-5">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8 mb-4">
                <div class="bg-white p-4 shadow rounded">
                    <?php

                    // Lấy id từ URL
                    $job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

                    // Lấy thông tin job
                    $job = null;
                    if ($job_id > 0) {
                        $stmt = $conn->prepare(
                            "SELECT j.*, c.company_name, c.company_logo, c.industry, c.company_size, c.company_address, c.banner_logo " .
                            "FROM job j LEFT JOIN company c ON c.id = j.company_id " .
                            "WHERE j.id = ? AND (c.status IS NULL OR c.status NOT IN ('pending_review', 'rejected'))"
                        );
                        $stmt->bind_param("i", $job_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $job = $result->fetch_assoc();
                        $stmt->close();
                    }

                    $job_company_logo = normalizeAssetPath(trim((string) ($job['company_logo'] ?? '')));
                    if ($job_company_logo === '') {
                        $job_company_logo = 'image/logo1.png';
                    }

                    // Lấy số người quan tâm
                    $interestedTotal = 0;
                    $stmt2 = $conn->prepare('SELECT COUNT(*) AS total FROM job_interest_count WHERE job_id = ?');
                    $stmt2->bind_param('i', $job_id);
                    $stmt2->execute();
                    $interestedTotal = (int) ($stmt2->get_result()->fetch_assoc()['total'] ?? 0);
                    $stmt2->close();

                    // Tính ngày hết hạn
                    $job_deadline = '';
                    if ($job && isset($job['created_at']) && isset($job['post_duration'])) {
                        $created_at = new DateTime($job['created_at']);
                        $created_at->modify('+' . intval($job['post_duration']) . ' days');
                        $job_deadline = $created_at->format('d/m/Y');
                    }
                    ?>
                    <div class="bg-white p-3 mb-4 shadow rounded ">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?= htmlspecialchars($job_company_logo) ?>"
                                 alt="Company Logo"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 14px; border: 1px solid #ececec; background: #fff;">
                            <div>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($job['job_title'] ?? 'Không có mô tả') ?></h5>
                                <small class="text-muted"><?= sprintf($lang['jobdetail_interested'], $interestedTotal) ?></small>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3 job-summary-row">
                            <div class="col-md-4 d-flex align-items-center job-summary-label">
                                <i class="bi bi-currency-dollar me-2"></i>
                                <strong><?= $lang['jobdetail_salary'] ?></strong>
                            </div>
                            <div class="col-md-8 text-danger fw-bold job-summary-value">
                                <?= htmlspecialchars(translateJobSalary($job['salary'] ?? $lang['jobdetail_no_data'], $lang), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <div class="row mb-3 job-summary-row">
                            <div class="col-md-4 d-flex align-items-center job-summary-label">
                                <i class="bi bi-geo-alt me-2"></i> <strong><?= $lang['jobdetail_location'] ?></strong>
                            </div>
                            <div class="col-md-8 job-summary-value"><?= htmlspecialchars($job['job_location'] ?? $lang['jobdetail_no_data']) ?></div>
                        </div>
                        <div class="row mb-3 job-summary-row">
                            <div class="col-md-4 d-flex align-items-center job-summary-label">
                                <i class="bi bi-person-workspace me-2"></i>
                                <strong><?= $lang['jobdetail_experience'] ?></strong>
                            </div>
                            <div class="col-md-8 job-summary-value"><?= htmlspecialchars(translateJobExperience($job['job_experience'] ?? '', $lang) ?: $lang['jobdetail_no_data']) ?>
                            </div>
                        </div>
                        <div class="text-muted mb-3">
                            <i class="bi bi-calendar-event me-2"></i><?= $lang['jobdetail_deadline'] ?>
                            <?= $job_deadline ?>
                        </div>
                        <?php
                        $is_logged_in = isset($_SESSION['username'], $_SESSION['role']) && $_SESSION['role'] === 'jobseeker';
                        $job_detail_id = isset($job['id']) ? intval($job['id']) : 0;
                        $current_url = "jobdetail.php?id=" . urlencode($job_detail_id);
                        $login_url = "jobseekerlogin.php?redirect=" . urlencode($current_url);
                        $has_applied = false;
                        $is_interested = false;

                        if ($is_logged_in && !empty($_SESSION['jobseeker_id']) && $job_detail_id > 0) {
                            $current_jobseeker_id = (int) $_SESSION['jobseeker_id'];
                            $interest_check = $conn->prepare('SELECT id FROM job_interest_count WHERE job_id = ? AND jobseeker_id = ? LIMIT 1');
                            $interest_check->bind_param('ii', $job_detail_id, $current_jobseeker_id);
                            $interest_check->execute();
                            $is_interested = (bool) $interest_check->get_result()->fetch_assoc();
                            $interest_check->close();

                            $application_check = $conn->prepare(
                                'SELECT id FROM application WHERE job_id = ? AND jobseeker_id = ? LIMIT 1'
                            );
                            if ($application_check) {
                                $application_check->bind_param('ii', $job_detail_id, $current_jobseeker_id);
                                $application_check->execute();
                                $application_check->store_result();
                                $has_applied = $application_check->num_rows > 0;
                                $application_check->close();
                            }
                        }
                        ?>


                        <div class="d-flex gap-3">
                            <button class="btn btn-danger apply-btn" type="button"
                                data-logged-in="<?= $is_logged_in ? '1' : '0' ?>" data-login-url="<?= htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8') ?>"
                                data-job-id="<?= $job_detail_id ?>"
                                data-already-applied="<?= $has_applied ? '1' : '0' ?>"
                                data-duplicate-message="<?= htmlspecialchars($lang['you_have_apply_before_warning'] ?? 'You have applied to this job before and therefore cannot apply again.', ENT_QUOTES, 'UTF-8') ?>">
                                <?= $lang['jobdetail_apply_now'] ?>
                            </button>
                            <?php if ($is_logged_in): ?>
                                <form method="POST" class="m-0">
                                    <input type="hidden" name="action" value="toggle_interest">
                                    <input type="hidden" name="job_id" value="<?= $job_detail_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['job_interest_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn <?= $is_interested ? 'btn-danger' : 'btn-secondary' ?>" type="submit" id="save-job-btn">
                                        <span aria-hidden="true"><?= $is_interested ? '&#9733;' : '&#9734;' ?></span>
                                        <?= $is_interested ? $lang['jobdetail_interested_button'] : $lang['jobdetail_like_job'] ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" type="button" id="save-job-btn" onclick="window.location.href='<?= htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8') ?>'">
                                    <span aria-hidden="true">&#9734;</span> <?= $lang['jobdetail_like_job'] ?>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>

                    <p class="demucjob"><?= $lang['jobdetail_job_description'] ?></p>
                    <p><?= nl2br(htmlspecialchars($job['job_description'] ?? $lang['jobdetail_no_data'])) ?></p>

                    <p class="demucjob"><?= $lang['jobdetail_employee_requirement'] ?></p>
                    <p><?= nl2br(htmlspecialchars($job['job_requirement'] ?? $lang['jobdetail_no_data'])) ?></p>

                    <p class="demucjob"><?= $lang['jobdetail_job_benefit'] ?></p>
                    <p><?= nl2br(htmlspecialchars($job['job_benefit'] ?? $lang['jobdetail_no_data'])) ?></p>

                    <!-- Modal xử lý nút Apply -->
                    <div class="modal fade " id="applyModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content" id="applyFormContent">
                                <div class="modal-body text-center p-10">
                                    <div class="spinner-border" role="status"></div>
                                    <p><?= $lang['jobdetail_loading'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Việc làm liên quan -->
                <?php
                $related_jobs = [];
                if ($job && !empty($job['industry'])) {
                    $stmt_related = $conn->prepare(
                        "SELECT j.id, j.job_title, j.salary
                         FROM job j
                         INNER JOIN company c ON c.id = j.company_id
                         WHERE c.industry = ? AND j.id != ?
                         AND c.status NOT IN ('pending_review', 'rejected')
                         LIMIT 5"
                    );
                    $stmt_related->bind_param("si", $job['industry'], $job_id);
                    $stmt_related->execute();
                    $result_related = $stmt_related->get_result();
                    while ($row = $result_related->fetch_assoc()) {
                        $related_jobs[] = $row;
                    }
                    $stmt_related->close();
                }
                ?>

                <?php if (!empty($related_jobs)): ?>
                    <div class="related-jobs-desktop bg-white p-4 shadow rounded mt-5">
                        <h4 class="text-danger"><?= $lang['jobdetail_related_jobs'] ?></h4>
                        <div class="list-group">
                            <?php foreach ($related_jobs as $rjob): ?>
                                <a href="jobdetail.php?id=<?= htmlspecialchars($rjob['id']) ?>"
                                    class="list-group-item list-group-item-action">
                                    <?= htmlspecialchars($rjob['job_title']) ?>: <?= htmlspecialchars(translateJobSalary($rjob['salary'] ?? '', $lang), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <div class="bg-white p-3 mb-4 shadow rounded">
                    <?php if ($job): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="sidebar-title mb-0">
                                <h4 style="color: #000 !important;">
                                    <?= htmlspecialchars($job['company_name'] ?? $job['company_name'] ?? 'Company Name') ?>
                                </h4>

                                <p style="margin-top: 8px; margin-bottom: 10px;">
                                    <a href="companyprofile.php?company_id=<?= (int) ($job['company_id'] ?? 0) ?>"
                                       style="color: #d91616; font-weight: 600; text-decoration: none;">
                                        <?= $lang['company_detail_page'] ?>
                                    </a>
                                </p>

                                <p><strong><?= $lang['jobdetail_company_size'] ?></strong>
                                    <?= htmlspecialchars($job['company_size'] ?? $lang['jobdetail_unknown']) ?></p>
                                <p><strong><?= $lang['jobdetail_industry'] ?></strong>
                                    <?= htmlspecialchars(translateIndustryDisplay($job['industry'] ?? '', $lang) ?: $lang['jobdetail_unknown']) ?></p>
                                <p><strong><?= $lang['jobdetail_address'] ?></strong>
                                    <?= htmlspecialchars($job['company_address'] ?? $job['job_detailed_location'] ?? $lang['jobdetail_unknown']) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-white p-3 mb-4 shadow rounded">
                    <div class="sidebar-title"><?= $lang['jobdetail_general_info'] ?></div>
                    <p><strong><?= $lang['jobdetail_position_level'] ?></strong>
                        <?= htmlspecialchars(translateJobPosition($job['job_position'] ?? '', $lang) ?: 'N/A') ?></p>
                    <p><strong><?= $lang['jobdetail_education'] ?></strong>
                        <?= htmlspecialchars($job['required_certification'] ?? 'N/A') ?></p>
                    <p><strong><?= $lang['jobdetail_quantity'] ?></strong>
                        <?= sprintf('%s', htmlspecialchars($job['no_employee_needed'] ?? 'N/A')) ?><?= $lang['jobdetail_person'] ?></p>
                    <p><strong><?= $lang['jobdetail_type'] ?></strong>
                        <?= htmlspecialchars(translateJobType($job['job_type'] ?? '', $lang) ?: 'N/A') ?></p>
                </div>

                <div class="bg-white p-3 mb-4 shadow rounded">
                    <div class="sidebar-title"><?= $lang['jobdetail_contact_info'] ?></div>
                    <p><strong><?= $lang['jobdetail_email'] ?></strong>
                        <?= htmlspecialchars($job['contact_email'] ?? 'N/A') ?></p>
                    <p><strong><?= $lang['jobdetail_phone'] ?></strong>
                        <?= htmlspecialchars($job['contact_phone'] ?? 'N/A') ?></p>
                </div>

                <div class="bg-white p-3 shadow rounded">
                    <div class="sidebar-title"><?= $lang['jobdetail_location_title'] ?></div>
                    <?php if ($job): ?>
                        <?php if (!empty($job['job_location'])): ?>
                            <span class="skill-tag"><?= htmlspecialchars($job['job_location']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($job['job_location_district'])): ?>
                            <span class="skill-tag"><?= htmlspecialchars($job['job_location_district']) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($related_jobs)): ?>
                    <div class="related-jobs-mobile bg-white p-4 shadow rounded mt-4">
                        <h4 class="text-danger"><?= $lang['jobdetail_related_jobs'] ?></h4>
                        <div class="list-group">
                            <?php foreach ($related_jobs as $rjob): ?>
                                <a href="jobdetail.php?id=<?= htmlspecialchars($rjob['id']) ?>"
                                    class="list-group-item list-group-item-action">
                                    <?= htmlspecialchars($rjob['job_title']) ?>: <?= htmlspecialchars(translateJobSalary($rjob['salary'] ?? '', $lang), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>


<footer>
    <?php require_once 'homepage/footer.php' ?>
</footer>

</html>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Delegate because the form is loaded dynamically
        document.body.addEventListener('submit', function (e) {
            if (e.target && e.target.id === 'apply-form') {
                e.preventDefault();

                const form = e.target;
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute('aria-busy', 'true');
                }
                const formData = new FormData(form);
                const showApplyError = message => {
                    let alert = form.querySelector('.apply-submit-alert');
                    if (!alert) {
                        alert = document.createElement('div');
                        alert.className = 'alert alert-danger apply-submit-alert';
                        alert.setAttribute('role', 'alert');
                        form.querySelector('.modal-body').prepend(alert);
                    }
                    alert.textContent = message;
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.removeAttribute('aria-busy');
                    }
                };

                fetch('applyform.php?id=' + encodeURIComponent(formData.get('job_id') || ''), {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        const isSuccess = Boolean(data && data.success);
                        const message = data && (data.message || data.error)
                            ? (data.message || data.error)
                            : 'There was an error. Please try again.';
                        if (!isSuccess) {
                            showApplyError(message);
                            return;
                        }
                        const alertType = isSuccess ? 'success' : 'warning';

                        document.getElementById('applyFormContent').innerHTML = `
                    <div class="modal-body text-center p-5">
                        <div class="alert alert-${alertType}" role="alert"></div>
                    </div>
                `;
                        document.querySelector('#applyFormContent .alert').textContent = message;

                        if (isSuccess) {
                            const applyButton = document.querySelector('.apply-btn');
                            if (applyButton) {
                                applyButton.setAttribute('data-already-applied', '1');
                            }

                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('applyModal'));
                                modal.hide();
                            }, 1500);
                        }
                    })
                    .catch(() => {
                        showApplyError('Could not submit the application. Please try again.');
                    });
            }
        });
    });
</script>