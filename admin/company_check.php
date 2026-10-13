<?php
// admin/company_check.php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (!isset($_SESSION['usernameadmin'])) {
    header('Location: adminlogin.php');
    exit();
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setCompanyFlash(string $type, string $message): void
{
    $_SESSION['admin_company_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function formatCompanyStatus(string $status): string
{
    return match ($status) {
        'pending_review' => 'Chờ duyệt',
        'approved' => 'Đã phê duyệt',
        'rejected' => 'Đã từ chối',
        default => 'Không rõ',
    };
}

function formatDateValue($value): string
{
    if (empty($value)) {
        return '—';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? '—' : date('d/m/Y H:i', $timestamp);
}

if (empty($_SESSION['admin_company_csrf'])) {
    $_SESSION['admin_company_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['admin_company_csrf'] ?? '');
    $action = (string) ($_POST['action'] ?? '');
    $companyId = filter_var(
        $_POST['company_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($sessionToken === '' || $csrfToken === '' || !hash_equals($sessionToken, $csrfToken)) {
        setCompanyFlash('error', 'Phiên xác nhận không hợp lệ. Vui lòng thử lại.');
        header('Location: company_check.php');
        exit();
    }

    if (!in_array($action, ['approve', 'reject'], true) || $companyId === false) {
        setCompanyFlash('error', 'Yêu cầu duyệt thông tin công ty không hợp lệ.');
        header('Location: company_check.php');
        exit();
    }

    $companyId = (int) $companyId;
    $checkStmt = $conn->prepare("SELECT c.id, c.company_name, c.status, u.email AS employer_email
        FROM company c
        JOIN user u ON u.id = c.employer_id
        WHERE c.id = ?
        LIMIT 1");
    $company = null;
    if ($checkStmt) {
        $checkStmt->bind_param('i', $companyId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $company = $checkResult ? $checkResult->fetch_assoc() : null;
        $checkStmt->close();
    }

    if (!$company) {
        setCompanyFlash('error', 'Không tìm thấy công ty cần cập nhật.');
        header('Location: company_check.php');
        exit();
    }

    $companyName = (string) ($company['company_name'] ?? '');
    $currentStatus = (string) ($company['status'] ?? 'pending_review');

    if ($currentStatus !== 'pending_review') {
        setCompanyFlash('error', 'Công ty ' . $companyName . ' không còn ở trạng thái chờ duyệt.');
        header('Location: company_check.php');
        exit();
    }

    $adminNote = '';
    if ($action === 'reject') {
        $adminNote = trim((string) ($_POST['admin_note'] ?? ''));
        if ($adminNote === '') {
            setCompanyFlash('error', 'Vui lòng nhập lý do từ chối công ty.');
            header('Location: company_check.php');
            exit();
        }
        $noteLength = function_exists('mb_strlen') ? mb_strlen($adminNote, 'UTF-8') : strlen($adminNote);
        if ($noteLength > 2000) {
            setCompanyFlash('error', 'Lý do từ chối không được vượt quá 2.000 ký tự.');
            header('Location: company_check.php');
            exit();
        }
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $updateStmt = $conn->prepare("UPDATE company SET status = ?, admin_notes = ? WHERE id = ? AND status = 'pending_review'");
    $updated = false;
    if ($updateStmt) {
        $updateStmt->bind_param('ssi', $newStatus, $adminNote, $companyId);
        $updateStmt->execute();
        $updated = $updateStmt->affected_rows === 1;
        $updateStmt->close();
    }

    if ($updated) {
        sendCompanyReviewEmail($company['employer_email'], $companyName, $newStatus, $adminNote);
        setCompanyFlash('success', $action === 'approve'
            ? 'Đã phê duyệt thông tin công ty ' . $companyName . '.'
            : 'Đã từ chối thông tin công ty ' . $companyName . '.');
    } else {
        setCompanyFlash('error', 'Không thể cập nhật thông tin công ty ' . $companyName . '.');
    }

    header('Location: company_check.php');
    exit();
}

$flash = $_SESSION['admin_company_flash'] ?? null;
unset($_SESSION['admin_company_flash']);

$csrfToken = (string) $_SESSION['admin_company_csrf'];

// --- Phân trang: 25 công ty mỗi trang ---
$perPage = 25;
$totalCompanies = 0;
$totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM company");
if ($totalStmt && $totalStmt->execute()) {
    $totalRow = $totalStmt->get_result()->fetch_assoc();
    $totalCompanies = (int) ($totalRow['total'] ?? 0);
    $totalStmt->close();
}
$totalPages = max(1, (int) ceil($totalCompanies / $perPage));
$currentPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 1]]);
if ($currentPage === false) {
    $currentPage = 1;
}
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$companies = [];
$listError = '';
$dataStmt = $conn->prepare("SELECT id, employer_id, company_name, company_address, industry, company_size,
        information, company_logo, banner_logo, status, admin_notes, created_at, last_updated_at
    FROM company
    ORDER BY (status = 'pending_review') DESC, id ASC
    LIMIT ? OFFSET ?");
if ($dataStmt) {
    $limit = $perPage;
    $dataStmt->bind_param('ii', $limit, $offset);
    $dataStmt->execute();
    $dataResult = $dataStmt->get_result();
    while ($dataResult && ($row = $dataResult->fetch_assoc())) {
        $companies[] = $row;
    }
    $dataStmt->close();
} else {
    $listError = 'Không thể tải danh sách công ty.';
}

// --- Số mục chờ duyệt cho badge trên sidebar ---
$pendingCompanies = 0;
$pendingJobs = 0;
$resC = $conn->query("SELECT COUNT(*) AS total FROM company WHERE status = 'pending_review'");
if ($resC) {
    $pendingCompanies = (int) ($resC->fetch_assoc()['total'] ?? 0);
    $resC->close();
}
$resJ = $conn->query("SELECT COUNT(*) AS total FROM job WHERE status = 'pending_review'");
if ($resJ) {
    $pendingJobs = (int) ($resJ->fetch_assoc()['total'] ?? 0);
    $resJ->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt thông tin công ty - ViecNhe</title>
    <style>
        :root {
            --admin-navy: #0b1e45;
            --admin-nav: #15294c;
            --admin-nav-hover: #1e3a66;
            --admin-page: #f3f0f2;
            --admin-surface: #ffffff;
            --admin-border: #d6dae2;
            --admin-text: #172033;
            --admin-muted: #667085;
            --admin-danger: #e74c3c;
            --admin-danger-dark: #b9362b;
            --admin-success: #0f9d58;
            --admin-scrim: rgba(11, 30, 69, 0.45);
            --admin-radius-sm: 4px;
            --admin-radius-md: 8px;
            --admin-shadow-modal: 0 18px 48px rgba(11, 30, 69, 0.22);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--admin-text);
            background: var(--admin-page);
            font-family: Arial, sans-serif;
            line-height: 1.4;
        }

        body.modal-open { overflow: hidden; }

        button, input, textarea { font: inherit; }

        a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible {
            outline: 3px solid rgba(30, 58, 102, 0.35);
            outline-offset: 2px;
        }

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

        .sidebar h2 {
            margin-top: 0;
        }

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

        .sidebar ul li a:hover {
            color: white;
        }

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
            font-weight: 700;
            line-height: 1;
        }

        .admin-main {
            min-width: 0;
            min-height: 100vh;
            margin-left: 200px;
            padding: 20px 24px 40px;
        }

        .admin-topbar {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 20px;
            min-height: 58px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--admin-border);
        }

        .admin-topbar h1 {
            margin: 0;
            font-size: clamp(24px, 3vw, 30px);
            line-height: 1.1;
        }

        .admin-topbar__logo {
            display: block;
            width: 200px;
            height: 48px;
            border-radius: 10px;
            object-fit: cover;
        }

        .admin-topbar__logout {
            justify-self: end;
            padding: 9px 15px;
            border: 0;
            border-radius: var(--admin-radius-sm);
            color: #fff;
            background: var(--admin-danger);
            font-weight: 700;
            cursor: pointer;
            transition: background-color 120ms ease;
        }

        .admin-topbar__logout:hover { background: var(--admin-danger-dark); }

        .admin-page-intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 22px 0 14px;
        }

        .admin-page-intro h2 { margin: 0; font-size: 21px; }

        .back-link {
            color: var(--admin-nav);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .back-link:hover { text-decoration: underline; }

        .flash-message {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
            padding: 11px 14px;
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius-sm);
            background: #eef3fb;
            color: var(--admin-navy);
            font-size: 13px;
        }

        .flash-message--error {
            border-color: #f0b4ae;
            background: #fff1ef;
            color: var(--admin-danger-dark);
        }

        .flash-message__close {
            flex: 0 0 auto;
            padding: 0;
            border: 0;
            color: inherit;
            background: transparent;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
        }

        .table-shell {
            overflow: auto;
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius-sm);
            background: var(--admin-surface);
        }

        .company-table {
            width: 100%;
            min-width: 1400px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        .company-table th,
        .company-table td {
            padding: 12px 13px;
            border-bottom: 1px solid var(--admin-border);
            text-align: left;
            vertical-align: middle;
        }

        .company-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            color: #fff;
            background: var(--admin-nav);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .company-table tbody tr:last-child td { border-bottom: 0; }

        .company-table tbody tr:hover td { background: #f7f8fa; }

        .company-table .numeric {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .company-table .company-name { min-width: 150px; font-weight: 700; }

        .company-table .muted { color: var(--admin-muted); }

        .company-table .info-cell {
            max-width: 260px;
            white-space: normal;
            word-break: break-word;
        }

        .company-table .logo-cell img {
            width: 60px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }

        .company-table .logo-cell a { color: var(--admin-nav); }

        .company-table .action-column {
            position: sticky;
            right: 0;
            z-index: 3;
            min-width: 130px;
            border-left: 1px solid var(--admin-border);
            background: var(--admin-surface);
        }

        .company-table thead .action-column {
            z-index: 4;
            background: var(--admin-nav);
        }

        .company-table tbody tr:hover .action-column { background: #f7f8fa; }

        .status-marker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
            font-size: 12px;
            font-weight: 700;
        }

        .status-marker::before {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--admin-nav);
            content: "";
        }

        .status-marker--pending { color: #9a6b00; }
        .status-marker--pending::before { background: #f4b400; }

        .status-marker--approved { color: var(--admin-success); }
        .status-marker--approved::before { background: var(--admin-success); }

        .status-marker--rejected { color: var(--admin-danger-dark); }
        .status-marker--rejected::before { background: var(--admin-danger); }

        .company-action {
            min-width: 84px;
            min-height: 32px;
            padding: 6px 10px;
            border: 1px solid var(--admin-danger);
            border-radius: var(--admin-radius-sm);
            color: var(--admin-danger-dark);
            background: #fff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: color 120ms ease, background-color 120ms ease;
        }

        .company-action:hover { color: #fff; background: var(--admin-danger); }

        .company-action--approve {
            border-color: var(--admin-success);
            color: #fff;
            background: var(--admin-success);
            margin-right: 6px;
        }

        .company-action--approve:hover { background: #0b7c44; }

        .empty-state {
            padding: 28px 16px !important;
            color: var(--admin-muted);
            text-align: center !important;
        }

        .retry-link {
            color: var(--admin-nav);
            font-weight: 700;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            min-height: 36px;
            padding: 0 10px;
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius-sm);
            color: var(--admin-nav);
            background: var(--admin-surface);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .pagination a:hover { background: #eef3fb; }

        .pagination .current {
            border-color: var(--admin-nav);
            color: #fff;
            background: var(--admin-nav);
        }

        .modal-backdrop[hidden] { display: none; }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: grid;
            place-items: center;
            padding: 16px;
            background: var(--admin-scrim);
        }

        .company-modal {
            width: min(400px, 100%);
            max-height: calc(100vh - 32px);
            overflow: auto;
            border-radius: var(--admin-radius-md);
            background: var(--admin-surface);
            box-shadow: var(--admin-shadow-modal);
        }

        .company-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 20px 0;
        }

        .company-modal__title {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
        }

        .company-modal__close {
            width: 32px;
            height: 32px;
            padding: 0;
            border: 0;
            color: var(--admin-muted);
            background: transparent;
            font-size: 25px;
            line-height: 1;
            cursor: pointer;
        }

        .company-modal__body { padding: 14px 20px 20px; }

        .company-modal__message {
            margin: 0 0 14px;
            color: var(--admin-muted);
            font-size: 14px;
        }

        .company-modal__target {
            margin: 0 0 16px;
            padding: 9px 10px;
            border-left: 3px solid var(--admin-navy);
            background: #f7f8fa;
            font-size: 13px;
        }

        .reason-field {
            display: grid;
            gap: 7px;
            margin-bottom: 18px;
        }

        .reason-field[hidden] { display: none; }

        .reason-field label { font-size: 13px; font-weight: 700; }

        .reason-field textarea {
            width: 100%;
            min-height: 96px;
            resize: vertical;
            padding: 10px;
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius-sm);
        }

        .company-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-button {
            min-height: 40px;
            padding: 9px 15px;
            border-radius: var(--admin-radius-sm);
            font-weight: 700;
            cursor: pointer;
        }

        .modal-button--cancel {
            border: 1px solid var(--admin-border);
            color: var(--admin-text);
            background: #fff;
        }

        .modal-button--confirm {
            border: 1px solid var(--admin-danger);
            color: #fff;
            background: var(--admin-danger);
        }

        .modal-button--confirm.is-approve {
            border-color: var(--admin-success);
            background: var(--admin-success);
        }

        .modal-button:disabled {
            opacity: 0.65;
            cursor: wait;
        }
    </style>
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
                    <?php if ($pendingJobs > 0): ?><span class="nav-badge"><?= $pendingJobs ?></span><?php endif; ?>
                </a></li>
            <li><a href="company_check.php" style="background:#1e3a66;">Duyệt thông tin công ty
                    <?php if ($pendingCompanies > 0): ?><span class="nav-badge"><?= $pendingCompanies ?></span><?php endif; ?>
                </a></li>
            <li><a href="user_table.php">Duyệt người dùng</a></li>
            <li><a href="feedbackcheck.php">Phản hồi</a></li>
        </ul>
    </div>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1>Duyệt thông tin công ty</h1>
            <img class="admin-topbar__logo" src="../image/logo.png" alt="ViecNhe">
            <form method="post" action="adminlogout.php">
                <button type="submit" name="admin_logout" class="admin-topbar__logout">Đăng xuất</button>
            </form>
        </div>

        <div class="admin-page-intro">
            <h2>Danh sách công ty (<?= $totalCompanies ?>)</h2>
            <a class="back-link" href="admindashboard.php">← Quay lại trang chủ</a>
        </div>

        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <div class="flash-message <?= ($flash['type'] ?? '') === 'error' ? 'flash-message--error' : '' ?>" role="status" aria-live="polite">
                <span><?= h($flash['message']) ?></span>
                <button type="button" class="flash-message__close" aria-label="Đóng thông báo">&times;</button>
            </div>
        <?php endif; ?>

        <div class="table-shell">
            <table class="company-table">
                <thead>
                    <tr>
                        <th scope="col">Mã</th>
                        <th scope="col">Mã nhà tuyển dụng</th>
                        <th scope="col">Tên công ty</th>
                        <th scope="col">Địa chỉ</th>
                        <th scope="col">Ngành nghề</th>
                        <th scope="col">Quy mô</th>
                        <th scope="col">Giới thiệu</th>
                        <th scope="col">Logo công ty</th>
                        <th scope="col">Ảnh bìa</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Ghi chú quản trị</th>
                        <th scope="col">Ngày tạo</th>
                        <th scope="col">Ngày cập nhật</th>
                        <th scope="col" class="action-column">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listError !== ''): ?>
                        <tr>
                            <td class="empty-state" colspan="14">
                                <?= h($listError) ?>
                                <a class="retry-link" href="company_check.php">Tải lại trang</a>
                            </td>
                        </tr>
                    <?php elseif (count($companies) === 0): ?>
                        <tr>
                            <td class="empty-state" colspan="14">Chưa có công ty nào trong hệ thống.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($companies as $company):
                            $companyId = (int) ($company['id'] ?? 0);
                            $status = (string) ($company['status'] ?? 'pending_review');
                            $isPending = $status === 'pending_review';
                        ?>
                            <tr>
                                <td class="numeric"><?= $companyId ?></td>
                                <td class="numeric"><?= h($company['employer_id'] ?? '—') ?></td>
                                <td class="company-name"><?= h($company['company_name'] ?? '—') ?></td>
                                <td><?= h($company['company_address'] ?? '—') ?></td>
                                <td><?= h($company['industry'] ?? '—') ?></td>
                                <td><?= h($company['company_size'] ?: '—') ?></td>
                                <td class="info-cell"><?= h($company['information'] ?: '—') ?></td>
                                <td class="logo-cell">
                                    <?php if (!empty($company['company_logo'])): ?>
                                        <a href="<?= h($company['company_logo']) ?>" target="_blank" rel="noopener">
                                            <img src="<?= h($company['company_logo']) ?>" alt="Logo <?= h($company['company_name'] ?? '') ?>">
                                        </a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="logo-cell">
                                    <?php if (!empty($company['banner_logo'])): ?>
                                        <a href="<?= h($company['banner_logo']) ?>" target="_blank" rel="noopener">Xem ảnh bìa</a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-marker status-marker--<?= h($status) ?>">
                                        <?= h(formatCompanyStatus($status)) ?>
                                    </span>
                                </td>
                                <td class="info-cell"><?= h($company['admin_notes'] ?: '—') ?></td>
                                <td class="numeric"><?= h(formatDateValue($company['created_at'] ?? null)) ?></td>
                                <td class="numeric"><?= h(formatDateValue($company['last_updated_at'] ?? null)) ?></td>
                                <td class="action-column">
                                    <?php if ($isPending): ?>
                                        <button type="button" class="company-action company-action--approve"
                                            data-company-action="approve" data-company-id="<?= $companyId ?>"
                                            data-company-name="<?= h($company['company_name'] ?? '') ?>">Phê duyệt</button>
                                        <button type="button" class="company-action"
                                            data-company-action="reject" data-company-id="<?= $companyId ?>"
                                            data-company-name="<?= h($company['company_name'] ?? '') ?>">Từ chối</button>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Phân trang danh sách công ty">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <?php if ($page === $currentPage): ?>
                        <span class="current" aria-current="page"><?= $page ?></span>
                    <?php else: ?>
                        <a href="company_check.php?page=<?= $page ?>"><?= $page ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </main>

    <div class="modal-backdrop" id="companyModal" role="dialog" aria-modal="true" aria-labelledby="companyModalTitle" aria-describedby="companyModalMessage" hidden>
        <form class="company-modal" method="post" action="company_check.php" id="companyModalForm">
            <div class="company-modal__header">
                <h2 class="company-modal__title" id="companyModalTitle">Xác nhận</h2>
                <button type="button" class="company-modal__close" id="companyModalClose" aria-label="Đóng">&times;</button>
            </div>
            <div class="company-modal__body">
                <p class="company-modal__message" id="companyModalMessage">Bạn có chắc muốn cập nhật công ty này?</p>
                <p class="company-modal__target">Công ty: <strong id="companyModalName"></strong></p>
                <div class="reason-field" id="rejectReasonField">
                    <label for="rejectReason">Lý do từ chối công ty</label>
                    <textarea id="rejectReason" name="admin_note" maxlength="2000" required></textarea>
                </div>
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" id="companyModalAction" value="approve">
                <input type="hidden" name="company_id" id="companyModalCompanyId" value="">
                <div class="company-modal__actions">
                    <button type="button" class="modal-button modal-button--cancel" id="companyModalCancel">Huỷ</button>
                    <button type="submit" class="modal-button modal-button--confirm" id="companyModalConfirm">Xác nhận</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('companyModal');
            const modalForm = document.getElementById('companyModalForm');
            const modalTitle = document.getElementById('companyModalTitle');
            const modalMessage = document.getElementById('companyModalMessage');
            const modalName = document.getElementById('companyModalName');
            const modalAction = document.getElementById('companyModalAction');
            const modalCompanyId = document.getElementById('companyModalCompanyId');
            const modalConfirm = document.getElementById('companyModalConfirm');
            const modalCancel = document.getElementById('companyModalCancel');
            const modalClose = document.getElementById('companyModalClose');
            const rejectReasonField = document.getElementById('rejectReasonField');
            const rejectReason = document.getElementById('rejectReason');
            const actionButtons = Array.from(document.querySelectorAll('[data-company-action]'));
            let lastTrigger = null;

            function openModal(trigger) {
                const action = trigger.dataset.companyAction === 'reject' ? 'reject' : 'approve';
                lastTrigger = trigger;
                modalAction.value = action;
                modalCompanyId.value = trigger.dataset.companyId || '';
                modalName.textContent = trigger.dataset.companyName || '';

                if (action === 'approve') {
                    modalTitle.textContent = 'Xác nhận phê duyệt công ty';
                    modalMessage.textContent = 'Bạn có chắc muốn phê duyệt thông tin công ty này?';
                    rejectReasonField.hidden = true;
                    rejectReason.required = false;
                    rejectReason.disabled = true;
                    modalConfirm.classList.add('is-approve');
                } else {
                    modalTitle.textContent = 'Xác nhận từ chối công ty';
                    modalMessage.textContent = 'Bạn có chắc muốn từ chối thông tin công ty này?';
                    rejectReasonField.hidden = false;
                    rejectReason.required = true;
                    rejectReason.disabled = false;
                    rejectReason.value = '';
                    modalConfirm.classList.remove('is-approve');
                }

                modalConfirm.textContent = 'Xác nhận';
                modalConfirm.disabled = false;
                modal.hidden = false;
                document.body.classList.add('modal-open');
                modalCancel.focus();
            }

            function closeModal() {
                modal.hidden = true;
                document.body.classList.remove('modal-open');
                modalForm.reset();
                modalAction.value = 'approve';
                rejectReasonField.hidden = false;
                rejectReason.required = true;
                rejectReason.disabled = false;
                modalConfirm.classList.remove('is-approve');
                if (lastTrigger) {
                    lastTrigger.focus();
                }
            }

            function trapFocus(event) {
                if (event.key !== 'Tab' || modal.hidden) return;
                const focusable = Array.from(modal.querySelectorAll('button:not([disabled]), textarea:not([disabled]), input:not([disabled])'));
                if (focusable.length === 0) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }

            actionButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(button);
                });
            });

            modalCancel.addEventListener('click', closeModal);
            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
                trapFocus(event);
            });
            modalForm.addEventListener('submit', function () {
                modalConfirm.disabled = true;
                modalConfirm.textContent = 'Đang xử lý...';
            });

            document.querySelectorAll('.flash-message__close').forEach(function (button) {
                button.addEventListener('click', function () {
                    const message = button.closest('.flash-message');
                    if (message) message.remove();
                });
            });
        });
    </script>
</body>

</html>
