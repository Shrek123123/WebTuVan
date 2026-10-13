<?php
session_start();
header('Location: ' . (isset($_SESSION['usernameadmin']) ? 'admindashboard.php' : '../index.php'), true, 302);
exit;

// admin/jobpendingdetail.php
session_start();
require_once '../config.php'; // Điều chỉnh đường dẫn đến config.php

// --- Kiểm tra quyền Admin (quan trọng) ---
if (!isset($_SESSION['usernameadmin'])) {
    header('Location: ../index.php');
    exit();
}

$job = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $jobId = intval($_GET['id']);

    $sql = "SELECT j.id, j.posted_by_employer_id, c.company_name, c.company_size, j.job_title,
                j.job_description, j.job_benefit, j.job_requirement, j.job_location,
                j.no_employee_needed, j.salary, j.post_duration, j.contact_email,
                j.contact_phone, j.job_type, c.industry AS job_category, j.required_certification,
                j.job_experience, c.company_logo, j.created_at, j.last_updated_at, j.status, j.job_detailed_location, j.job_location_district
            FROM hoa_giai_vien j
            LEFT JOIN company c ON c.id = j.company_id
            WHERE j.id = ? AND j.status = 'pending_review'"; // Chỉ lấy job đang chờ duyệt
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $jobId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $job = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// --- Đếm số mục chờ duyệt cho badge trên sidebar ---
$pending_jobs_badge = 0;
$pending_companies_badge = 0;
require_once '../config.php';
$res_pending_jobs = $conn->query("SELECT COUNT(*) AS total FROM hoa_giai_vien WHERE status = 'pending_review'");
if ($res_pending_jobs) {
    $pending_jobs_badge = (int) ($res_pending_jobs->fetch_assoc()['total'] ?? 0);
    $res_pending_jobs->close();
}
$res_pending_companies = $conn->query("SELECT COUNT(*) AS total FROM company WHERE status = 'pending_review'");
if ($res_pending_companies) {
    $pending_companies_badge = (int) ($res_pending_companies->fetch_assoc()['total'] ?? 0);
    $res_pending_companies->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Chi tiết tin tuyển dụng chờ duyệt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f0f2;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0b1e45;
            text-align: center;
            margin-bottom: 25px;
        }
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .job-details {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background-color: #fefefe;
        }
        .job-details p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .job-details p strong {
            color: #15294c;
            display: inline-block;
            width: 180px; /* Căn chỉnh các label */
            vertical-align: top;
        }
        .job-details .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #0b1e45;
            font-size: 1.1em;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .action-buttons button {
            padding: 12px 25px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
            transition: background-color 0.2s;
        }
        .action-buttons .approve-btn {
            background-color: #28a745;
            color: white;
        }
        .action-buttons .approve-btn:hover {
            background-color: #218838;
        }
        .action-buttons .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .action-buttons .reject-btn:hover {
            background-color: #c82333;
        }
        .review-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(11, 30, 69, 0.55);
        }
        .review-modal-overlay[hidden] {
            display: none;
        }
        .review-modal {
            position: relative;
            width: min(100%, 560px);
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.24);
        }
        .review-modal h2 {
            margin: 0 42px 8px 0;
            color: #0b1e45;
            font-size: 1.35rem;
        }
        .modal-description {
            margin: 0 0 20px;
            color: #555;
            line-height: 1.5;
        }
        .modal-close {
            position: absolute;
            top: 12px;
            right: 16px;
            border: 0;
            background: transparent;
            color: #555;
            font-size: 1.8rem;
            line-height: 1;
            cursor: pointer;
        }
        .modal-close:hover,
        .modal-close:focus-visible {
            color: #0b1e45;
        }
        .review-modal label {
            display: block;
            margin-bottom: 8px;
            color: #15294c;
            font-weight: 600;
        }
        .review-modal textarea {
            display: block;
            width: 100%;
            min-height: 130px;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #15294c;
            font: inherit;
            resize: vertical;
        }
        .review-modal textarea:focus {
            outline: 3px solid rgba(0, 123, 255, 0.2);
            border-color: #007bff;
        }
        .field-help {
            margin: 6px 0 0;
            color: #777;
            font-size: 0.85rem;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
        }
        .modal-actions button {
            min-width: 110px;
            padding: 10px 16px;
            border: 0;
            border-radius: 5px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        .cancel-btn {
            background: #e9ecef;
            color: #15294c;
        }
        .cancel-btn:hover,
        .cancel-btn:focus-visible {
            background: #d8dde3;
        }
        .modal-submit-btn {
            color: #fff;
        }
        .approve-submit {
            background: #28a745;
        }
        .approve-submit:hover,
        .approve-submit:focus-visible {
            background: #218838;
        }
        .reject-submit {
            background: #dc3545;
        }
        .reject-submit:hover,
        .reject-submit:focus-visible {
            background: #c82333;
        }
        .modal-actions button:disabled {
            cursor: wait;
            opacity: 0.65;
        }
        .modal-close:focus-visible,
        .modal-actions button:focus-visible {
            outline: 3px solid rgba(0, 123, 255, 0.35);
            outline-offset: 2px;
        }
        body.modal-open {
            overflow: hidden;
        }
        @media (max-width: 600px) {
            .review-modal {
                padding: 24px 20px;
            }
            .modal-actions {
                flex-direction: column-reverse;
            }
            .modal-actions button {
                width: 100%;
            }
        }
        .not-found {
            text-align: center;
            color: #d90000;
            padding: 50px;
            font-size: 1.2em;
            background-color: #fefefe;
            border-radius: 8px;
            border: 1px dashed #d90000;
        }

        /* --- Sidebar đồng bộ --- */
        .sidebar {
            width: 200px;
            background-color: #0b1e45;
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px 10px;
            overflow-y: auto;
        }

        .sidebar h2 { margin-top: 0; }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 20px 0;
            padding: 10px;
            background-color: #15294c;
            border-radius: 5px;
        }

        .sidebar ul li:hover {
            background-color: #1e3a66;
            cursor: pointer;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .sidebar ul li a:hover { color: white; }

        .user-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #4caf50;
            border-radius: 50%;
        }

        .nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            background: #e74c3c;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
        }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>
<body>
    <?php include __DIR__ . '/mobile_sidebar.php'; ?>
    <div class="sidebar">
        <h2>Quản trị</h2>
        <div class="user-status">
            <div class="status-dot"></div>
            <span>Trực tuyến</span>
        </div>
        <ul>
            <li><a href="#">Thay đổi ảnh trang chủ</a></li>
            <li><a href="#">Người dùng</a></li>
            <li><a href="jobpending.php">Duyệt tin tuyển dụng
                    <?php if ($pending_jobs_badge > 0): ?><span class="nav-badge"><?php echo $pending_jobs_badge; ?></span><?php endif; ?>
                </a></li>
            <li><a href="company_check.php">Duyệt thông tin công ty
                    <?php if ($pending_companies_badge > 0): ?><span class="nav-badge"><?php echo $pending_companies_badge; ?></span><?php endif; ?>
                </a></li>
            <li><a href="user_table.php">Duyệt người dùng</a></li>
            <li><a href="feedbackcheck.php">Phản hồi</a></li>
        </ul>
    </div>

    <div class="container" style="margin-left: 240px;">
        <a href="jobpending.php" class="back-button">&larr; Quay lại danh sách tin chờ duyệt</a>
        <h1>Chi tiết tin tuyển dụng chờ duyệt</h1>

        <?php if ($job): ?>
            <div class="job-details">
                <p><strong>Mã tin tuyển dụng:</strong> <?php echo htmlspecialchars($job['id']); ?></p>
                <p><strong>Đăng bởi nhà tuyển dụng mã số:</strong> <?php echo htmlspecialchars($job['posted_by_employer_id']); ?></p>
                <p><strong>Tên công ty:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                <p><strong>Quy mô công ty:</strong> <?php echo htmlspecialchars($job['company_size']); ?></p>
                <p><strong>Tiêu đề công việc:</strong> <?php echo htmlspecialchars($job['job_title']); ?></p>

                <div class="section-title">Mô tả công việc</div>
                <p><?php echo nl2br(htmlspecialchars($job['job_description'])); ?></p>

                <div class="section-title">Yêu cầu công việc</div>
                <p><?php echo nl2br(htmlspecialchars($job['job_requirement'])); ?></p>

                <div class="section-title">Quyền lợi công việc</div>
                <p><?php echo nl2br(htmlspecialchars($job['job_benefit'])); ?></p>

                <p><strong>Địa điểm làm việc:</strong> <?php echo htmlspecialchars($job['job_location']); ?></p>
                <p><strong>Địa chỉ chi tiết:</strong> <?php echo htmlspecialchars($job['job_detailed_location']); ?></p>
                <p><strong>Quận/huyện:</strong> <?php echo htmlspecialchars($job['job_location_district']); ?></p>
                <p><strong>Số lượng nhân sự cần tuyển:</strong> <?php echo htmlspecialchars($job['no_employee_needed']); ?></p>
                <p><strong>Mức lương:</strong> <?php echo htmlspecialchars($job['salary']); ?></p>
                <p><strong>Thời gian đăng tin (ngày):</strong> <?php echo htmlspecialchars($job['post_duration']); ?></p>
                <p><strong>Email liên hệ:</strong> <?php echo htmlspecialchars($job['contact_email']); ?></p>
                <p><strong>Số điện thoại liên hệ:</strong> <?php echo htmlspecialchars($job['contact_phone']); ?></p>
                <p><strong>Loại công việc:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
                <p><strong>Ngành nghề:</strong> <?php echo htmlspecialchars($job['job_category']); ?></p>
                <p><strong>Chứng chỉ yêu cầu:</strong> <?php echo htmlspecialchars($job['required_certification']); ?></p>
                <p><strong>Kinh nghiệm:</strong> <?php echo htmlspecialchars($job['job_experience']); ?></p>
                <p><strong>Logo công ty:</strong>
                    <?php if (!empty($job['company_logo'])): ?>
                        <a href="../<?php echo htmlspecialchars($job['company_logo']); ?>" target="_blank">Xem logo</a>
                    <?php else: ?>
                        Không có
                    <?php endif; ?>
                </p>
                <p><strong>Ngày đăng:</strong> <?php echo (new DateTime($job['created_at']))->format('d/m/Y H:i'); ?></p>
                <p><strong>Ngày cập nhật:</strong> <?php echo !empty($job['last_updated_at']) ? (new DateTime($job['last_updated_at']))->format('d/m/Y H:i') : '—'; ?></p>
                <p><strong>Trạng thái hiện tại:</strong> <span style="font-weight: bold; color: #ffc107;"><?php echo htmlspecialchars($job['status']); ?></span></p>
            </div>

            <div class="action-buttons" aria-label="Hành động duyệt tin">
                <button type="button" class="approve-btn" data-review-action="approve">Phê duyệt tin</button>
                <button type="button" class="reject-btn" data-review-action="reject">Từ chối tin</button>
            </div>

            <div class="review-modal-overlay" id="review-modal" hidden>
                <div class="review-modal" role="dialog" aria-modal="true" aria-labelledby="review-modal-title">
                    <button type="button" class="modal-close" id="close-review-modal" aria-label="Đóng hộp thoại ghi chú">&times;</button>
                    <h2 id="review-modal-title">Ghi chú cho nhà tuyển dụng (nếu có)</h2>
                    <p class="modal-description">Bạn có thể để lại ghi chú trước khi hoàn tất đánh giá công việc này.</p>

                    <form method="POST" action="process_job_review.php" id="job-review-form">
                        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" id="review-action" value="">

                        <label for="admin-note">Ghi chú <span id="admin-note-required-mark" style="color: #dc3545;"></span></label>
                        <textarea id="admin-note" name="admin_note" rows="6" maxlength="2000" placeholder="Nhập ghi chú cho nhà tuyển dụng (không bắt buộc)" aria-describedby="admin-note-help"></textarea>
                        <p class="field-help" id="admin-note-help">Tối đa 2.000 ký tự. <span id="admin-note-required-help" style="display: none; color: #dc3545;">Bắt buộc nhập lý do khi từ chối công việc.</span></p>

                        <div class="modal-actions">
                            <button type="button" class="cancel-btn" id="cancel-review">Hủy</button>
                            <button type="submit" class="modal-submit-btn" id="submit-review">Gửi đánh giá</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                (function () {
                    const reviewModal = document.getElementById('review-modal');
                    const reviewForm = document.getElementById('job-review-form');
                    const reviewAction = document.getElementById('review-action');
                    const adminNote = document.getElementById('admin-note');
                    const submitReview = document.getElementById('submit-review');
                    const closeReviewModal = document.getElementById('close-review-modal');
                    const cancelReview = document.getElementById('cancel-review');
                    const reviewButtons = document.querySelectorAll('[data-review-action]');
                    const requiredMark = document.getElementById('admin-note-required-mark');
                    const requiredHelp = document.getElementById('admin-note-required-help');

                    function closeModal() {
                        reviewModal.hidden = true;
                        document.body.classList.remove('modal-open');
                        reviewAction.value = '';
                        requiredMark.textContent = '';
                        requiredHelp.style.display = 'none';
                    }

                    function openModal(action) {
                        const isApprove = action === 'approve';
                        reviewAction.value = action;
                        submitReview.textContent = isApprove ? 'Phê duyệt tin' : 'Từ chối tin';
                        submitReview.classList.toggle('approve-submit', isApprove);
                        submitReview.classList.toggle('reject-submit', !isApprove);
                        // Khi từ chối (reject) thì bắt buộc phải nhập lý do
                        adminNote.required = !isApprove;
                        adminNote.placeholder = isApprove
                            ? 'Nhập ghi chú cho nhà tuyển dụng (không bắt buộc)'
                            : 'Bắt buộc nhập lý do từ chối công việc này';
                        requiredMark.textContent = isApprove ? '' : '*';
                        requiredHelp.style.display = isApprove ? 'none' : 'inline';
                        adminNote.setCustomValidity('');
                        reviewModal.hidden = false;
                        document.body.classList.add('modal-open');
                        adminNote.focus();
                    }

                    reviewButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            openModal(button.dataset.reviewAction);
                        });
                    });

                    closeReviewModal.addEventListener('click', closeModal);
                    cancelReview.addEventListener('click', closeModal);

                    reviewModal.addEventListener('click', function (event) {
                        if (event.target === reviewModal) {
                            closeModal();
                        }
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape' && !reviewModal.hidden) {
                            closeModal();
                        }
                    });

                    reviewForm.addEventListener('submit', function (event) {
                        // Chặn submit nếu là reject mà chưa nhập lý do
                        if (reviewAction.value === 'reject' && adminNote.value.trim() === '') {
                            event.preventDefault();
                            adminNote.setCustomValidity('Vui lòng nhập lý do từ chối công việc này.');
                            adminNote.reportValidity();
                            return;
                        }
                        adminNote.setCustomValidity('');
                        submitReview.disabled = true;
                        submitReview.textContent = 'Đang xử lý...';
                    });

                    // Xoá thông báo lỗi khi người dùng bắt đầu nhập
                    adminNote.addEventListener('input', function () {
                        adminNote.setCustomValidity('');
                    });
                }());
            </script>
        <?php else: ?>
            <div class="not-found">
                Không tìm thấy tin tuyển dụng hoặc tin này không còn chờ duyệt.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>