<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/views/mediators/location-data.php';

if (empty($_SESSION['admin_id']) || empty($_SESSION['usernameadmin'])) {
    header('Location: adminlogin.php');
    exit();
}

$adminId = (int) $_SESSION['admin_id'];
$adminStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin' AND status = 'active' LIMIT 1");
if (!$adminStmt) {
    throw new RuntimeException('Could not verify administrator: ' . $conn->error);
}
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$isAdmin = (bool) $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();
if (!$isAdmin) {
    session_destroy();
    header('Location: adminlogin.php');
    exit();
}

if (empty($_SESSION['mediator_admin_csrf'])) {
    $_SESSION['mediator_admin_csrf'] = bin2hex(random_bytes(32));
}

function mediator_admin_redirect_with_error(string $message, int $mediatorId = 0): void
{
    $_SESSION['mediator_admin_flash'] = ['type' => 'error', 'message' => $message];
    header('Location: mediators.php' . ($mediatorId > 0 ? '?edit=' . $mediatorId : ''));
    exit();
}

function mediator_admin_remove_managed_avatar(string $avatarPath): void
{
    if (strpos($avatarPath, 'uploads/mediators/') !== 0) {
        return;
    }
    $filename = substr($avatarPath, strlen('uploads/mediators/'));
    if ($filename === '' || basename($filename) !== $filename) {
        return;
    }
    $path = __DIR__ . '/../uploads/mediators/' . $filename;
    if (is_file($path)) {
        unlink($path);
    }
}

$flash = $_SESSION['mediator_admin_flash'] ?? null;
unset($_SESSION['mediator_admin_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['mediator_admin_csrf'], $csrfToken)) {
        $_SESSION['mediator_admin_flash'] = ['type' => 'error', 'message' => 'Phiên làm việc không hợp lệ. Vui lòng thử lại.'];
        header('Location: mediators.php');
        exit();
    }

    $mediatorId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $university = trim((string) ($_POST['university'] ?? ''));
    $major = trim((string) ($_POST['major'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $provinceLocation = mediator_request_value($_POST['province_location'] ?? '');
    $districtLocation = mediator_request_value($_POST['district_location'] ?? '');
    $specialtyInput = (string) ($_POST['specialties'] ?? '');
    $rateInput = $_POST['hourly_rate'] ?? '';
    $status = (string) ($_POST['status'] ?? '');
    $validStatuses = ['online', 'busy', 'offline'];
    $specialties = array_values(array_unique(array_filter(array_map('trim', explode(',', $specialtyInput)), static fn($value) => $value !== '')));
    $hourlyRate = is_scalar($rateInput) && is_numeric($rateInput) ? (float) $rateInput : -1.0;

    if ($mediatorId === false || $fullName === '' || strlen($fullName) > 100
        || $phone === '' || strlen($phone) > 20 || strlen($university) > 100 || strlen($major) > 100
        || strlen($bio) > 60000 || strlen($specialtyInput) > 2000
        || $hourlyRate < 0 || $hourlyRate > 99999999.99 || !in_array($status, $validStatuses, true)
        || !mediator_location_pair_is_valid($provinceLocation, $districtLocation)) {
        mediator_admin_redirect_with_error('Thông tin hồ sơ chưa hợp lệ; vui lòng kiểm tra các trường bắt buộc và giới hạn độ dài.', (int) ($mediatorId ?: 0));
    }

    $previousAvatar = '';
    if ((int) $mediatorId > 0) {
        $avatarLookup = $conn->prepare('SELECT avatar_url FROM mediators WHERE id = ? LIMIT 1');
        if (!$avatarLookup) {
            throw new RuntimeException('Could not prepare existing mediator avatar lookup: ' . $conn->error);
        }
        $existingId = (int) $mediatorId;
        $avatarLookup->bind_param('i', $existingId);
        $avatarLookup->execute();
        $existingProfile = $avatarLookup->get_result()->fetch_assoc();
        $avatarLookup->close();
        if (!$existingProfile) {
            mediator_admin_redirect_with_error('Không tìm thấy hồ sơ hòa giải viên cần cập nhật.');
        }
        $previousAvatar = (string) ($existingProfile['avatar_url'] ?? '');
    }

    $upload = $_FILES['avatar_file'] ?? null;
    $hasUpload = is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $removeAvatar = ($_POST['remove_avatar'] ?? '') === '1';
    if ($hasUpload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        mediator_admin_redirect_with_error('Tải ảnh lên không thành công. Vui lòng chọn lại tệp ảnh.', (int) ($mediatorId ?: 0));
    }
    if ($hasUpload && (!isset($upload['tmp_name'], $upload['size']) || !is_uploaded_file($upload['tmp_name'])
        || (int) $upload['size'] < 1 || (int) $upload['size'] > 5 * 1024 * 1024)) {
        mediator_admin_redirect_with_error('Ảnh phải nhỏ hơn hoặc bằng 5 MB.', (int) ($mediatorId ?: 0));
    }

    $avatar = $removeAvatar ? '' : $previousAvatar;
    $newAvatarPath = null;
    if ($hasUpload) {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($upload['tmp_name']);
        $allowedImageTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $imageInfo = @getimagesize($upload['tmp_name']);
        if (!isset($allowedImageTypes[$mimeType]) || $imageInfo === false
            || (int) $imageInfo[0] < 1 || (int) $imageInfo[1] < 1
            || (int) $imageInfo[0] > 6000 || (int) $imageInfo[1] > 6000
            || (int) $imageInfo[0] * (int) $imageInfo[1] > 20000000) {
            mediator_admin_redirect_with_error('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP hợp lệ (tối đa 20 megapixel).', (int) ($mediatorId ?: 0));
        }

        $uploadDirectory = __DIR__ . '/../uploads/mediators';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Could not create mediator avatar upload directory.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$mimeType];
        $destination = $uploadDirectory . '/' . $filename;
        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            throw new RuntimeException('Could not store uploaded mediator avatar.');
        }
        $newAvatarPath = 'uploads/mediators/' . $filename;
        $avatar = $newAvatarPath;
    }

    $specialtiesJson = json_encode($specialties, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if ((int) $mediatorId > 0) {
        $stmt = $conn->prepare(
            'UPDATE mediators
             SET full_name = ?, avatar_url = ?, phone = ?, university = ?, major = ?, bio = ?,
                 province_location = ?, district_location = ?, specialties = ?, hourly_rate = ?, status = ?
             WHERE id = ?'
        );
        if (!$stmt) {
            if ($newAvatarPath !== null) {
                mediator_admin_remove_managed_avatar($newAvatarPath);
            }
            throw new RuntimeException('Could not prepare mediator profile update: ' . $conn->error);
        }
        $id = (int) $mediatorId;
        $stmt->bind_param('sssssssssdsi', $fullName, $avatar, $phone, $university, $major, $bio, $provinceLocation, $districtLocation, $specialtiesJson, $hourlyRate, $status, $id);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO mediators (full_name, avatar_url, phone, university, major, bio, province_location, district_location, specialties, hourly_rate, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            if ($newAvatarPath !== null) {
                mediator_admin_remove_managed_avatar($newAvatarPath);
            }
            throw new RuntimeException('Could not prepare mediator profile creation: ' . $conn->error);
        }
        $stmt->bind_param('sssssssssds', $fullName, $avatar, $phone, $university, $major, $bio, $provinceLocation, $districtLocation, $specialtiesJson, $hourlyRate, $status);
    }

    try {
        $stmt->execute();
        $_SESSION['mediator_admin_flash'] = ['type' => 'success', 'message' => (int) $mediatorId > 0 ? 'Đã cập nhật hồ sơ hòa giải viên.' : 'Đã thêm hồ sơ hòa giải viên vào danh bạ.'];
        if ($newAvatarPath !== null && $previousAvatar !== $newAvatarPath) {
            mediator_admin_remove_managed_avatar($previousAvatar);
        } elseif ($removeAvatar) {
            mediator_admin_remove_managed_avatar($previousAvatar);
        }
    } catch (mysqli_sql_exception $exception) {
        if ($newAvatarPath !== null) {
            mediator_admin_remove_managed_avatar($newAvatarPath);
        }
        error_log('Mediator profile administration failed: ' . $exception->getMessage());
        $_SESSION['mediator_admin_flash'] = ['type' => 'error', 'message' => 'Chưa thể lưu hồ sơ. Vui lòng kiểm tra dữ liệu và thử lại.'];
    } finally {
        $stmt->close();
    }
    header('Location: mediators.php');
    exit();
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editProfile = null;
if ($editId) {
    $editStmt = $conn->prepare('SELECT id, full_name, avatar_url, phone, university, major, bio, province_location, district_location, specialties, hourly_rate, status FROM mediators WHERE id = ? LIMIT 1');
    if (!$editStmt) {
        throw new RuntimeException('Could not prepare mediator profile lookup: ' . $conn->error);
    }
    $editStmt->bind_param('i', $editId);
    $editStmt->execute();
    $editProfile = $editStmt->get_result()->fetch_assoc() ?: null;
    $editStmt->close();
}
$result = $conn->query('SELECT id, full_name, phone, university, major, hourly_rate, rating, total_cases, status FROM mediators ORDER BY full_name, id');
if (!$result) {
    throw new RuntimeException('Could not load mediator profiles: ' . $conn->error);
}
$mediators = $result->fetch_all(MYSQLI_ASSOC);
$editingSpecialties = [];
$editingAvatarUrl = (string) ($editProfile['avatar_url'] ?? '');
if (strpos($editingAvatarUrl, 'uploads/') === 0) {
    $editingAvatarUrl = '../' . $editingAvatarUrl;
}
if ($editProfile) {
    $decodedSpecialties = json_decode((string) ($editProfile['specialties'] ?? ''), true);
    if (is_array($decodedSpecialties)) {
        $editingSpecialties = array_filter($decodedSpecialties, 'is_string');
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý hòa giải viên — Mediato</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f6f2; color: #203b38; font: 15px/1.55 Arial, sans-serif; }
        main { width: min(1180px, calc(100% - 32px)); margin: 35px auto 70px; }
        h1, h2 { font-family: Georgia, serif; font-weight: 500; }
        .admin-nav { display: flex; gap: 18px; flex-wrap: wrap; padding: 14px max(16px, calc((100% - 1180px) / 2)); background: #173c37; }
        .admin-nav a { color: white; text-decoration: none; font-weight: 700; }
        .intro { color: #647571; }
        .panel { margin-top: 22px; padding: 24px; border: 1px solid #dce4df; background: #fffefa; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        .location-filter-fields { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        label { display: grid; gap: 6px; font-weight: 700; }
        input, select, textarea { width: 100%; min-height: 42px; padding: 10px; border: 1px solid #cdd9d3; border-radius: 3px; font: inherit; }
        textarea { min-height: 110px; resize: vertical; }
        .wide { grid-column: 1 / -1; }
        button { min-height: 42px; margin-top: 18px; padding: 9px 16px; border: 0; border-radius: 3px; background: #176b62; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        .flash { padding: 13px 15px; background: #fff; border: 1px solid #dce4df; }
        .flash.error { border-color: #d9a5a0; color: #8d3029; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5ebe7; text-align: left; white-space: nowrap; }
        .avatar-preview { display: block; width: 104px; height: 104px; margin: 8px 0; border: 1px solid #dce4df; border-radius: 50%; object-fit: cover; }
        .avatar-help { margin: 4px 0 0; color: #647571; font-size: .88rem; font-weight: 400; }
        .remove-avatar { display: flex; grid-template-columns: auto 1fr; align-items: center; justify-content: start; gap: 9px; margin-top: 10px; font-weight: 400; }
        .remove-avatar input { width: auto; min-height: unset; }
        @media (max-width: 650px) { .form-grid { grid-template-columns: 1fr; } .wide, .location-filter-fields { grid-column: auto; } .location-filter-fields { grid-template-columns: 1fr; } }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>
<body>
    <nav class="admin-nav" aria-label="Điều hướng quản trị">
        <a href="admindashboard.php">Bảng điều khiển</a>
        <a href="mediators.php">Hòa giải viên</a>
        <a href="user_table.php">Khách hàng và quản trị viên</a>
        <form method="post" action="adminlogout.php" style="margin:0 0 0 auto"><button style="margin:0;min-height:unset;padding:4px 10px" type="submit" name="admin_logout">Đăng xuất</button></form>
    </nav>
    <main>
        <h1>Hồ sơ hòa giải viên</h1>
        <p class="intro">Khu vực quản trị riêng để Mediato quản lý đối tác. Không có đăng ký hòa giải viên công khai.</p>
        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <p class="flash <?= ($flash['type'] ?? '') === 'error' ? 'error' : '' ?>" role="status"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <section class="panel">
            <h2><?= $editProfile ? 'Cập nhật hồ sơ' : 'Thêm hồ sơ đối tác' ?></h2>
            <form method="post" enctype="multipart/form-data" data-location-source="<?= htmlspecialchars(mediator_application_url('app/data/vietnam-locations.json'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['mediator_admin_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) ($editProfile['id'] ?? 0) ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                <div class="form-grid">
                    <label>Họ và tên<input name="full_name" maxlength="100" required value="<?= htmlspecialchars((string) ($editProfile['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label>Số điện thoại<input name="phone" maxlength="20" required value="<?= htmlspecialchars((string) ($editProfile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label>Trường đại học<input name="university" maxlength="100" value="<?= htmlspecialchars((string) ($editProfile['university'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label>Chuyên ngành<input name="major" maxlength="100" value="<?= htmlspecialchars((string) ($editProfile['major'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label>Phí tư vấn mỗi giờ (VND)<input name="hourly_rate" type="number" min="0" max="99999999.99" step="0.01" required value="<?= htmlspecialchars((string) ($editProfile['hourly_rate'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label>Trạng thái
                        <select name="status" required>
                            <?php foreach (['online' => 'Sẵn sàng', 'busy' => 'Đang bận', 'offline' => 'Ngoại tuyến'] as $statusValue => $statusLabel): ?>
                                <option value="<?= $statusValue ?>" <?= ($editProfile['status'] ?? 'offline') === $statusValue ? 'selected' : '' ?>><?= $statusLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="location-filter-fields">
                        <?= mediator_location_select_fields('mediator-admin-location', $editProfile['province_location'] ?? '', $editProfile['district_location'] ?? '') ?>
                    </div>
                    <div class="wide">
                        <label for="avatar-file">Ảnh đại diện</label>
                        <?php if (!empty($editProfile['avatar_url'])): ?>
                            <img class="avatar-preview" src="<?= htmlspecialchars($editingAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh đại diện hiện tại">
                            <label class="remove-avatar"><input type="checkbox" name="remove_avatar" value="1"> Gỡ ảnh hiện tại</label>
                        <?php endif; ?>
                        <input id="avatar-file" name="avatar_file" type="file" accept="image/jpeg,image/png,image/webp">
                        <p class="avatar-help">Chọn ảnh JPG, PNG hoặc WebP, tối đa 5 MB. Để trống nếu muốn giữ ảnh hiện tại.</p>
                    </div>
                    <label class="wide">Lĩnh vực chuyên môn (phân tách bằng dấu phẩy)<input name="specialties" maxlength="2000" value="<?= htmlspecialchars(implode(', ', $editingSpecialties), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="wide">Giới thiệu<textarea name="bio" maxlength="60000"><?= htmlspecialchars((string) ($editProfile['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
                </div>
                <p class="mediator-location-status" role="status" aria-live="polite"></p>
                <button type="submit"><?= $editProfile ? 'Lưu thay đổi' : 'Thêm vào danh bạ' ?></button>
                <?php if ($editProfile): ?><a href="mediators.php">Hủy chỉnh sửa</a><?php endif; ?>
            </form>
        </section>
        <section class="panel">
            <h2>Danh sách hồ sơ</h2>
            <?php if (!$mediators): ?>
                <p>Chưa có hồ sơ hòa giải viên. Thêm hồ sơ đối tác đã được duyệt để hiển thị trong danh bạ khách hàng.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tên</th><th>Điện thoại</th><th>Chuyên ngành</th><th>Phí/giờ</th><th>Đánh giá</th><th>Số ca</th><th>Trạng thái</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($mediators as $mediator): ?>
                            <tr>
                                <td><?= htmlspecialchars($mediator['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($mediator['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($mediator['major'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float) $mediator['hourly_rate'], 0, ',', '.') ?> đ</td>
                                <td><?= number_format((float) $mediator['rating'], 1, ',', '.') ?></td>
                                <td><?= (int) $mediator['total_cases'] ?></td>
                                <td><?= htmlspecialchars($mediator['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><a href="mediators.php?edit=<?= (int) $mediator['id'] ?>">Sửa</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="<?= htmlspecialchars(mediator_application_url('app/views/mediators/location-filter.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
