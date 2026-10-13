<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}
$bookingNotice = $_SESSION['booking_flash'] ?? null;
unset($_SESSION['booking_flash']);
ob_start();
require_once __DIR__ . '/app/views/mediators/shared.php';
$mediatorSharedStyles = ob_get_clean();
$conn = mediator_ensure_connection($conn);

$mediatorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$profile = null;
$loadError = false;

if ($mediatorId && $mediatorId > 0) {
    try {
        $stmt = $conn->prepare(
            'SELECT id, full_name, avatar_url, university, major, bio, specialties, hourly_rate, rating, total_cases, status
             FROM mediators
             WHERE id = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('i', $mediatorId);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
        } else {
            $loadError = true;
        }
    } catch (Throwable $exception) {
        error_log('Unable to load mediator profile: ' . $exception->getMessage());
        $loadError = true;
    }
}

if (!$profile && !$loadError) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Thông tin hồ sơ hòa giải viên">
    <title><?= $profile ? mediator_escape($profile['full_name']) . ' — Hồ sơ hòa giải viên' : 'Không tìm thấy hồ sơ — Danh bạ hòa giải viên' ?></title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
    <?php require_once __DIR__ . '/homepage/header.php'; ?>
    <?= $mediatorSharedStyles ?>
    <style>
        .mediator-detail-page { min-height: 72vh; padding: 44px 22px 86px; background: var(--mediator-paper); color: var(--mediator-ink); }
        .mediator-detail-wrap { width: min(1040px, 100%); margin: 0 auto; }
        .mediator-back-link { display: inline-block; margin-bottom: 24px; color: var(--mediator-teal); font-weight: 700; text-decoration-thickness: 1px; text-underline-offset: 4px; }
        .mediator-detail-card { overflow: hidden; border: 1px solid var(--mediator-line); background: #fffefa; box-shadow: 0 16px 42px rgba(34, 46, 45, .07); }
        .mediator-detail-top { display: grid; grid-template-columns: 175px minmax(0, 1fr); gap: 30px; align-items: center; padding: clamp(24px, 5vw, 52px); border-bottom: 1px solid var(--mediator-line); }
        .mediator-detail-avatar { width: 156px; height: 156px; border: 1px solid var(--mediator-line); object-fit: cover; background: #e8efeb; }
        .mediator-detail-avatar-fallback { display: grid; place-items: center; color: var(--mediator-teal); font: 500 3rem Georgia, 'Times New Roman', serif; }
        .mediator-detail-title h1 { margin: 10px 0 8px; font: 500 clamp(2rem, 4vw, 3.2rem)/1.08 Georgia, 'Times New Roman', serif; letter-spacing: -.035em; }
        .mediator-detail-school { color: var(--mediator-muted); font-size: 1.02rem; line-height: 1.6; }
        .mediator-detail-body { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(240px, .8fr); gap: 45px; padding: clamp(24px, 5vw, 48px); }
        .mediator-detail-body h2 { margin: 0 0 16px; font: 500 1.55rem Georgia, 'Times New Roman', serif; }
        .mediator-detail-bio { color: #40504e; line-height: 1.85; white-space: pre-line; overflow-wrap: anywhere; }
        .mediator-detail-specialties { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 26px; }
        .mediator-detail-metrics { border-left: 1px solid var(--mediator-line); padding-left: 28px; }
        .mediator-metric { padding: 15px 0; border-bottom: 1px solid var(--mediator-line); }
        .mediator-metric:first-child { padding-top: 0; }
        .mediator-metric-label { display: block; margin-bottom: 5px; color: var(--mediator-muted); font-size: .8rem; font-weight: 750; letter-spacing: .05em; text-transform: uppercase; }
        .mediator-metric-value { color: var(--mediator-ink); font-size: 1.1rem; font-weight: 750; }
        .mediator-detail-empty { margin: 20px 0; }
        .booking-panel { margin-top: 28px; padding: clamp(22px, 4vw, 38px); border: 1px solid var(--mediator-line); background: #fffefa; }
        .booking-panel h2 { margin: 0 0 8px; font: 500 1.7rem Georgia, 'Times New Roman', serif; }
        .booking-panel-intro { margin: 0 0 22px; color: var(--mediator-muted); line-height: 1.6; }
        .booking-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .booking-field { display: grid; gap: 7px; }
        .booking-field--wide { grid-column: 1 / -1; }
        .booking-field label { color: var(--mediator-ink); font-size: .9rem; font-weight: 700; }
        .booking-field input, .booking-field select, .booking-field textarea { width: 100%; min-height: 46px; padding: 11px 12px; border: 1px solid var(--mediator-line); border-radius: 3px; background: white; color: var(--mediator-ink); font: inherit; }
        .booking-field textarea { min-height: 112px; resize: vertical; }
        .booking-submit { margin-top: 20px; padding: 13px 22px; border: 0; background: var(--mediator-teal); color: white; font: inherit; font-weight: 750; cursor: pointer; }
        .booking-login-note { margin: 0; color: var(--mediator-muted); }
        .booking-login-note a { color: var(--mediator-teal); font-weight: 750; }
        @media (max-width: 600px) { .booking-form-grid { grid-template-columns: 1fr; } .booking-field--wide { grid-column: auto; } }
        @media (max-width: 700px) { .mediator-detail-page { padding: 24px 16px 56px; } .mediator-detail-top { grid-template-columns: 94px 1fr; gap: 18px; } .mediator-detail-avatar { width: 92px; height: 92px; } .mediator-detail-avatar-fallback { font-size: 2rem; } .mediator-detail-body { grid-template-columns: 1fr; gap: 30px; } .mediator-detail-metrics { border-left: 0; border-top: 1px solid var(--mediator-line); padding: 14px 0 0; } }
    </style>
    <main class="mediator-detail-page">
        <div class="mediator-detail-wrap">
            <a class="mediator-back-link" href="searchpage.php">← Trở về danh bạ</a>
            <?php if (is_array($bookingNotice) && !empty($bookingNotice['message'])): ?>
                <div class="mediator-state mediator-detail-empty" role="status"><?= mediator_escape($bookingNotice['message']) ?></div>
            <?php endif; ?>
            <?php if ($loadError): ?>
                <div class="mediator-state mediator-detail-empty" role="status">
                    <strong>Chưa thể tải hồ sơ lúc này.</strong>
                    <p>Vui lòng thử lại sau ít phút.</p>
                </div>
            <?php elseif (!$profile): ?>
                <div class="mediator-state mediator-detail-empty" role="status">
                    <strong>Không tìm thấy hồ sơ này.</strong>
                    <p>Hồ sơ có thể không còn tồn tại hoặc đường dẫn không chính xác.</p>
                    <a href="searchpage.php">Khám phá danh bạ</a>
                </div>
            <?php else: ?>
                <?php $specialties = mediator_decode_specialties($profile['specialties'] ?? null); ?>
                <article class="mediator-detail-card">
                    <header class="mediator-detail-top">
                        <?= mediator_render_avatar($profile, 'mediator-detail-avatar') ?>
                        <div class="mediator-detail-title">
                            <span class="mediator-status <?= mediator_status_class($profile['status'] ?? '') ?>">
                                <span class="mediator-status-dot" aria-hidden="true"></span><?= mediator_escape(mediator_status_label($profile['status'] ?? '')) ?>
                            </span>
                            <h1><?= mediator_escape($profile['full_name'] ?? 'Hòa giải viên') ?></h1>
                            <div class="mediator-detail-school">
                                <?php if (!empty($profile['university'])): ?><?= mediator_escape($profile['university']) ?><?php endif; ?>
                                <?php if (!empty($profile['university']) && !empty($profile['major'])): ?> · <?php endif; ?>
                                <?php if (!empty($profile['major'])): ?><?= mediator_escape($profile['major']) ?><?php endif; ?>
                            </div>
                        </div>
                    </header>
                    <div class="mediator-detail-body">
                        <section>
                            <h2>Giới thiệu</h2>
                            <div class="mediator-detail-bio"><?= trim((string) ($profile['bio'] ?? '')) !== '' ? nl2br(mediator_escape($profile['bio'])) : 'Hồ sơ chưa có nội dung giới thiệu.' ?></div>
                            <h2 style="margin-top: 32px;">Lĩnh vực chuyên môn</h2>
                            <?php if ($specialties): ?>
                                <div class="mediator-detail-specialties">
                                    <?php foreach ($specialties as $specialty): ?><span class="mediator-specialty"><?= mediator_escape($specialty) ?></span><?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="mediator-empty-copy">Chưa có lĩnh vực chuyên môn được cập nhật.</p>
                            <?php endif; ?>
                        </section>
                        <aside class="mediator-detail-metrics" aria-label="Thông tin hồ sơ">
                            <div class="mediator-metric">
                                <span class="mediator-metric-label">Phí theo giờ</span>
                                <span class="mediator-metric-value"><?= mediator_escape(mediator_hourly_rate($profile['hourly_rate'] ?? null)) ?></span>
                            </div>
                            <div class="mediator-metric">
                                <span class="mediator-metric-label">Đánh giá</span>
                                <span class="mediator-metric-value"><?= mediator_escape(mediator_rating($profile['rating'] ?? null)) ?></span>
                            </div>
                            <div class="mediator-metric">
                                <span class="mediator-metric-label">Số vụ việc</span>
                                <span class="mediator-metric-value"><?= number_format(max(0, (int) ($profile['total_cases'] ?? 0)), 0, ',', '.') ?></span>
                            </div>
                            <div class="mediator-metric">
                                <span class="mediator-metric-label">Tình trạng hiện tại</span>
                                <span class="mediator-metric-value">
                                    <span class="mediator-status <?= mediator_status_class($profile['status'] ?? '') ?>">
                                        <span class="mediator-status-dot" aria-hidden="true"></span><?= mediator_escape(mediator_status_label($profile['status'] ?? '')) ?>
                                    </span>
                                </span>
                            </div>
                        </aside>
                    </div>
                </article>
                <section class="booking-panel" id="dat-lich">
                    <h2>Đặt lịch tư vấn</h2>
                    <p class="booking-panel-intro">Phí dự kiến: <?= mediator_escape(mediator_hourly_rate($profile['hourly_rate'] ?? null)) ?>. Chưa thu tiền tại bước đặt lịch.</p>
                    <?php if (!in_array($_SESSION['role'] ?? '', ['customer', 'jobseeker'], true)): ?>
                        <p class="booking-login-note">
                            Vui lòng <a href="jobseekerlogin.php?redirect=<?= rawurlencode('jobdetail.php?id=' . (int) $profile['id'] . '#dat-lich') ?>">đăng nhập tài khoản khách hàng</a> để tiếp tục đặt lịch.
                        </p>
                    <?php else: ?>
                        <form action="bookings.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= mediator_escape($_SESSION['booking_csrf'] ?? '') ?>">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="mediator_id" value="<?= (int) $profile['id'] ?>">
                            <div class="booking-form-grid">
                                <div class="booking-field">
                                    <label for="conflict-type">Loại mâu thuẫn</label>
                                    <select id="conflict-type" name="conflict_type" required>
                                        <option value="TRO_NHA_O">Trọ / nhà ở</option>
                                        <option value="VA_CHAM_GIAO_THONG">Va chạm giao thông</option>
                                        <option value="GIAO_DICH_MUA_BAN">Giao dịch mua bán</option>
                                        <option value="VAY_MUON_TAI_CHINH">Vay mượn tài chính</option>
                                        <option value="KHONG_GIAN_SONG">Không gian sống</option>
                                        <option value="MOC_TINH_CAM_GIA_DINH">Tình cảm / gia đình</option>
                                        <option value="HOC_TAP_VIEC_NHOM">Học tập / việc nhóm</option>
                                        <option value="KHAC">Khác</option>
                                    </select>
                                </div>
                                <div class="booking-field">
                                    <label for="consultation-mode">Hình thức tư vấn</label>
                                    <select id="consultation-mode" name="consultation_mode" required>
                                        <option value="online">Trực tuyến</option>
                                        <option value="onsite">Tại hiện trường</option>
                                    </select>
                                </div>
                                <div class="booking-field booking-field--wide">
                                    <label for="booking-title">Tiêu đề yêu cầu</label>
                                    <input id="booking-title" name="title" maxlength="255" required>
                                </div>
                                <div class="booking-field booking-field--wide">
                                    <label for="booking-description">Mô tả tình huống</label>
                                    <textarea id="booking-description" name="description" maxlength="10000"></textarea>
                                </div>
                                <div class="booking-field booking-field--wide">
                                    <label for="incident-location">Địa điểm liên quan</label>
                                    <input id="incident-location" name="incident_location" maxlength="255" placeholder="Không cần nhập nếu tư vấn trực tuyến">
                                </div>
                                <div class="booking-field">
                                    <label for="urgency-level">Thời gian</label>
                                    <select id="urgency-level" name="urgency_level" required>
                                        <option value="IMMEDIATE">Cần tư vấn sớm</option>
                                        <option value="SCHEDULED">Đặt lịch cụ thể</option>
                                    </select>
                                </div>
                                <div class="booking-field" id="scheduled-time-field" hidden>
                                    <label for="scheduled-time">Ngày và giờ mong muốn</label>
                                    <input id="scheduled-time" type="datetime-local" name="scheduled_time">
                                </div>
                                <div class="booking-field">
                                    <label for="duration-hours">Thời lượng (giờ)</label>
                                    <input id="duration-hours" type="number" name="duration_hours" min="0.5" max="8" step="0.5" value="1" required>
                                </div>
                            </div>
                            <button class="booking-submit" type="submit">Gửi yêu cầu đặt lịch</button>
                        </form>
                        <script>
                            (() => {
                                const urgency = document.getElementById('urgency-level');
                                const scheduledField = document.getElementById('scheduled-time-field');
                                const scheduledInput = document.getElementById('scheduled-time');
                                const mode = document.getElementById('consultation-mode');
                                const location = document.getElementById('incident-location');
                                const updateFields = () => {
                                    const scheduled = urgency.value === 'SCHEDULED';
                                    scheduledField.hidden = !scheduled;
                                    scheduledInput.required = scheduled;
                                    location.required = mode.value === 'onsite';
                                    location.placeholder = mode.value === 'online' ? 'Không cần nhập nếu tư vấn trực tuyến' : 'Nhập địa chỉ hiện trường';
                                };
                                urgency.addEventListener('change', updateFields);
                                mode.addEventListener('change', updateFields);
                                updateFields();
                            })();
                        </script>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once __DIR__ . '/homepage/footer.php'; ?>
</body>
</html>
