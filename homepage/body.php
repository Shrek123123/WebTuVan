<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/views/mediators/shared.php';
$conn = mediator_ensure_connection($conn);

$filters = [
    'q' => mediator_request_value($_GET['q'] ?? ''),
    'specialty' => mediator_request_value($_GET['specialty'] ?? ''),
    'availability' => mediator_request_value($_GET['availability'] ?? ''),
    'sort' => mediator_request_value($_GET['sort'] ?? 'rating'),
    'province_location' => mediator_request_value($_GET['province_location'] ?? ''),
    'district_location' => mediator_request_value($_GET['district_location'] ?? ''),
];
$hasHomeFilters = $filters['q'] !== '' || $filters['specialty'] !== '' || $filters['availability'] !== '' || $filters['province_location'] !== '' || $filters['district_location'] !== '';
$pageSize = 6;
$directory = mediator_fetch_profiles($conn, $filters);
$profileCount = $directory['total'];
$profiles = array_slice($directory['profiles'], 0, $pageSize);
$specialtyOptions = mediator_specialty_options($conn);
$emptyDirectoryCheck = null;
if (!$directory['error'] && !$profiles && $hasHomeFilters) {
    $emptyDirectoryCheck = mediator_fetch_profiles($conn, ['sort' => $filters['sort']]);
}
$hasPublishedProfiles = $profileCount > 0 || ($emptyDirectoryCheck !== null && !$emptyDirectoryCheck['error'] && $emptyDirectoryCheck['total'] > 0);
$noProfilesMatch = $hasHomeFilters && $emptyDirectoryCheck !== null && !$emptyDirectoryCheck['error'] && $emptyDirectoryCheck['total'] > 0;
$directoryHasNoProfiles = $profileCount === 0 && (!$hasHomeFilters || ($emptyDirectoryCheck !== null && !$emptyDirectoryCheck['error'] && $emptyDirectoryCheck['total'] === 0));

function mediator_render_home_booking_card($profile)
{
    $id = (int) ($profile['id'] ?? 0);
    $name = trim((string) ($profile['full_name'] ?? '')) ?: 'Hòa giải viên';
    $specialties = $profile['decoded_specialties'] ?? mediator_decode_specialties($profile['specialties'] ?? null);
    ?>
    <article class="mediator-card">
        <div class="mediator-card-top">
            <?= mediator_render_avatar($profile) ?>
        </div>
        <div class="mediator-card-title-row">
            <h3><?= mediator_escape($name) ?></h3>
            <span class="mediator-status <?= mediator_status_class($profile['status'] ?? '') ?>">
                <span class="mediator-status-dot" aria-hidden="true"></span><?= mediator_escape(mediator_status_label($profile['status'] ?? '')) ?>
            </span>
        </div>
        <?php if ($specialties): ?>
            <div class="mediator-card-specialties" aria-label="Lĩnh vực chuyên môn">
                <?php foreach (array_slice($specialties, 0, 3) as $item): ?><span class="mediator-specialty"><?= mediator_escape($item) ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="mediator-card-facts">
            <div><span>Đánh giá</span><strong><?= mediator_escape(mediator_rating($profile['rating'] ?? null)) ?></strong></div>
            <div><span>Vụ việc đã xử lý</span><strong><?= number_format(max(0, (int) ($profile['total_cases'] ?? 0)), 0, ',', '.') ?></strong></div>
        </div>
        <a class="mediator-card-link" href="jobdetail.php?id=<?= $id ?>">Đặt lịch <span aria-hidden="true">→</span></a>
    </article>
    <?php
}
?>
<style>
    .mediator-home { background: var(--mediator-paper); color: var(--mediator-ink); padding: 38px 22px 76px; }
    .mediator-wrap { width: min(1160px, 100%); margin: 0 auto; }
    .mediator-intro { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr); align-items: end; gap: 46px; padding: 26px 0 38px; }
    .mediator-eyebrow { color: var(--mediator-teal); font-size: .75rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .mediator-intro h1 { max-width: 650px; margin: 12px 0 15px; font: 500 clamp(2.25rem, 3.8vw, 3.8rem)/1.08 Georgia, 'Times New Roman', serif; letter-spacing: -.035em; text-wrap: balance; }
    .mediator-intro p { max-width: 560px; margin: 0; color: var(--mediator-muted); font-size: 1rem; line-height: 1.68; }
    .mediator-note { border-left: 2px solid var(--mediator-terracotta); padding: 5px 0 5px 18px; color: var(--mediator-muted); font-size: .94rem; line-height: 1.65; }
    .mediator-note strong { display: block; margin-bottom: 6px; color: var(--mediator-ink); font-family: Georgia, 'Times New Roman', serif; font-size: 1.2rem; }
    .mediator-search-panel { padding: 20px; border: 1px solid var(--mediator-line); background: #fffefa; box-shadow: 0 12px 32px rgba(34, 46, 45, .055); }
    .mediator-form { display: grid; grid-template-columns: minmax(160px, 1.4fr) repeat(5, minmax(100px, .8fr)) auto; gap: 10px; align-items: end; }
    .mediator-field label { display: block; margin: 0 0 7px; color: var(--mediator-muted); font-size: .75rem; font-weight: 700; }
    .mediator-field input, .mediator-field select { width: 100%; min-height: 46px; padding: 10px 12px; border: 1px solid #d8ddd7; border-radius: 2px; background: #fff; color: var(--mediator-ink); font: inherit; }
    .mediator-field input:focus, .mediator-field select:focus { outline: 3px solid rgba(42, 111, 101, .17); border-color: var(--mediator-teal); }
    .mediator-submit { min-height: 46px; padding: 0 20px; border: 1px solid var(--mediator-ink); background: var(--mediator-ink); color: white; font: inherit; font-weight: 700; cursor: pointer; }
    .mediator-submit:hover { background: var(--mediator-teal); border-color: var(--mediator-teal); }
    .mediator-section-heading { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin: 48px 0 20px; }
    .mediator-section-heading h2 { margin: 0; font: 500 clamp(1.8rem, 3vw, 2.55rem)/1.15 Georgia, 'Times New Roman', serif; letter-spacing: -.025em; }
    .mediator-section-heading p { margin: 0; color: var(--mediator-muted); }
    .mediator-results { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .mediator-home .mediator-card {
        box-sizing: border-box;
        width: min(100%, 252px);
        margin: 0 auto;
        padding: 14px 14px 16px;
    }
    .mediator-home .mediator-card-top {
        display: block;
        margin-bottom: 12px;
    }
    .mediator-home .mediator-card-top .mediator-avatar,
    .mediator-home .mediator-card-top .mediator-avatar-fallback {
        display: block;
        width: 100%;
        max-width: 100%;
        height: auto;
        min-height: 176px;
        aspect-ratio: 4 / 5;
        margin: 0 auto;
        border: 1px solid var(--mediator-line);
        border-radius: 14px;
        object-fit: cover;
        background: #e9efe7;
        object-position: center;
    }
    .mediator-home .mediator-card-top .mediator-avatar-fallback {
        display: grid;
        place-items: center;
        color: var(--mediator-teal);
        font: 500 clamp(1.5rem, 2vw, 2.2rem)/1 Georgia, 'Times New Roman', serif;
    }
    .mediator-home .mediator-card-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        width: 100%;
        margin-bottom: 8px;
    }
    .mediator-home .mediator-card h3 {
        margin: 0;
        min-width: 0;
        flex: 1 1 auto;
    }
    .mediator-home .mediator-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex: 0 0 auto;
        margin: 0 0 0 auto;
        padding: 6px 9px;
        border: 1px solid rgba(34, 46, 45, .08);
        border-radius: 999px;
        background: rgba(244, 245, 241, .96);
        color: var(--mediator-muted);
        font-size: .68rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }
    .mediator-home .mediator-card-facts {
        margin-top: 16px;
    }
    .mediator-all-link { display: inline-flex; margin-top: 26px; color: var(--mediator-teal); font-weight: 750; text-decoration-thickness: 1px; text-underline-offset: 4px; }
    @media (max-width: 950px) { .mediator-form { grid-template-columns: repeat(2, minmax(0, 1fr)); } .mediator-submit { align-self: end; } }
    .mediator-home-empty { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, .48fr); gap: 28px; padding: clamp(23px, 4vw, 42px); border: 1px solid var(--mediator-line); background: #fffefa; box-shadow: 0 8px 24px rgba(34, 46, 45, .045); }
    .mediator-empty-kicker { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 16px; color: var(--mediator-teal); font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .mediator-empty-kicker::before { width: 8px; height: 8px; border-radius: 50%; background: #b8624d; content: ""; }
    .mediator-home-empty h3 { max-width: 580px; margin: 0; color: var(--mediator-ink); font: 500 clamp(1.45rem, 2.8vw, 2rem)/1.2 Georgia, 'Times New Roman', serif; }
    .mediator-home-empty p { max-width: 610px; margin: 12px 0 0; color: var(--mediator-muted); font-size: .95rem; line-height: 1.7; }
    .mediator-empty-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 13px 20px; margin-top: 22px; }
    .mediator-empty-actions a { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 0 15px; border: 1px solid var(--mediator-ink); color: var(--mediator-ink); font-size: .87rem; font-weight: 700; text-decoration: none; }
    .mediator-empty-actions a:hover { background: var(--mediator-ink); color: #fff; }
    .mediator-empty-actions a:focus-visible { outline: 3px solid rgba(47, 112, 104, .38); outline-offset: 3px; }
    .mediator-empty-index { display: flex; min-height: 150px; flex-direction: column; justify-content: space-between; padding: 19px; border-left: 1px solid #d5dfd8; background: linear-gradient(135deg, #eef3ee, #f8f7f1); color: var(--mediator-teal); }
    .mediator-empty-index span { color: var(--mediator-muted); font-size: .72rem; font-weight: 750; letter-spacing: .1em; text-transform: uppercase; }
    .mediator-empty-index strong { font: 500 2.65rem/1 Georgia, 'Times New Roman', serif; }
    .mediator-empty-index small { color: var(--mediator-muted); font-size: .82rem; line-height: 1.5; }
    .mediator-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 22px; }
    .mediator-primary-action, .mediator-secondary-action { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; padding: 0 18px; font-size: .9rem; font-weight: 750; text-decoration: none; }
    .mediator-primary-action { border: 1px solid var(--mediator-terracotta); background: var(--mediator-terracotta); color: #fff; }
    .mediator-primary-action:hover { border-color: #914c39; background: #914c39; }
    .mediator-secondary-action { color: var(--mediator-ink); text-decoration: underline; text-decoration-color: #a9b8b1; text-underline-offset: 4px; }
    .mediator-primary-action:focus-visible, .mediator-secondary-action:focus-visible, .mediator-card-link:focus-visible { outline: 3px solid rgba(47, 112, 104, .38); outline-offset: 3px; }
    .mediator-card-link { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding: 12px 14px; border: 1px solid var(--mediator-ink); background: var(--mediator-ink); color: #fff; font-size: .9rem; font-weight: 750; text-decoration: none; }
    .mediator-card-link:hover { border-color: var(--mediator-teal); background: var(--mediator-teal); color: #fff; }
    .mediator-card-facts div:first-child { grid-column: auto; }
    .mediator-modes { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1px; margin-top: 54px; border: 1px solid var(--mediator-line); background: var(--mediator-line); }
    .mediator-mode { padding: 23px 25px; background: #edf1eb; }
    .mediator-mode-label { display: block; margin-bottom: 8px; color: var(--mediator-teal); font-size: .72rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .mediator-mode h3 { margin: 0 0 7px; color: var(--mediator-ink); font: 500 1.2rem/1.25 Georgia, 'Times New Roman', serif; }
    .mediator-mode p { max-width: 470px; margin: 0; color: var(--mediator-muted); font-size: .9rem; line-height: 1.65; }
    @media (max-width: 850px) {
        .mediator-home { padding: 28px 20px 64px; }
        .mediator-intro { grid-template-columns: minmax(0, 1fr); gap: 18px; padding: 20px 0 28px; }
        .mediator-intro > div { max-width: 680px; }
        .mediator-intro h1 { max-width: 620px; font-size: clamp(2.1rem, 5vw, 3.1rem); line-height: 1.1; }
        .mediator-intro p { max-width: 620px; font-size: .98rem; }
        .mediator-note { max-width: 620px; padding: 10px 0 10px 16px; }
        .mediator-results { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mediator-home .mediator-card-top .mediator-avatar,
        .mediator-home .mediator-card-top .mediator-avatar-fallback {
            min-height: 144px;
        }
        .mediator-section-heading { margin-top: 38px; }
    }
    @media (max-width: 520px) {
        .mediator-home { padding: 19px 16px 50px; }
        .mediator-intro { gap: 15px; padding: 15px 0 23px; }
        .mediator-intro h1 { max-width: 470px; margin: 10px 0 11px; font-size: clamp(1.9rem, 8vw, 2.45rem); line-height: 1.12; letter-spacing: -.03em; }
        .mediator-intro p { font-size: .94rem; line-height: 1.62; }
        .mediator-note { padding: 9px 0 9px 13px; font-size: .9rem; line-height: 1.58; }
        .mediator-note strong { font-size: 1.08rem; }
        .mediator-form, .mediator-results { grid-template-columns: 1fr; }
        .mediator-section-heading { align-items: start; flex-direction: column; gap: 8px; margin: 32px 0 16px; }
        .mediator-search-panel { padding: 14px; }
        .mediator-modes { grid-template-columns: 1fr; margin-top: 38px; }
        .mediator-mode { padding: 19px 17px; }
        .mediator-hero-actions { align-items: stretch; }
        .mediator-primary-action { width: 100%; }
        .mediator-home-empty { grid-template-columns: 1fr; gap: 20px; padding: 21px 18px; }
        .mediator-home-empty p { font-size: .9rem; }
        .mediator-home .mediator-card-top .mediator-avatar,
        .mediator-home .mediator-card-top .mediator-avatar-fallback {
            min-height: 192px;
        }
        .mediator-empty-index { min-height: 112px; }
        .mediator-empty-index strong { font-size: 2.2rem; }
        .mediator-empty-actions { align-items: stretch; flex-direction: column; }
    }
</style>

<main class="mediator-home">
    <div class="mediator-wrap">
        <section class="mediator-intro" aria-labelledby="mediator-home-title">
            <div>
                <div class="mediator-eyebrow">Danh bạ hòa giải viên</div>
                <h1 id="mediator-home-title">Một cuộc đối thoại tốt có thể mở ra lối đi mới.</h1>
                <p>Tìm hiểu hồ sơ, chuyên môn và tình trạng hiện tại của các hòa giải viên để chọn người phù hợp với vấn đề của bạn.</p>
                <div class="mediator-hero-actions">
                    <a class="mediator-primary-action" href="#mediator-directory"><?= $hasPublishedProfiles ? 'Chọn người &amp; đặt lịch' : 'Tình trạng đặt lịch' ?> <span aria-hidden="true">&nbsp;→</span></a>
                    <a class="mediator-secondary-action" href="#mediator-modes">Xem hình thức tư vấn</a>
                </div>
            </div>
            <aside class="mediator-note">
                <strong>Chọn người phù hợp, theo cách của bạn</strong>
                Xem thông tin chuyên môn và kinh nghiệm được chia sẻ trên hồ sơ. Bạn có thể lọc danh bạ theo lĩnh vực hoặc tình trạng sẵn sàng.
            </aside>
        </section>

        <section class="mediator-search-panel" aria-label="Tìm kiếm hòa giải viên">
            <form class="mediator-form" method="get" action="searchpage.php" data-location-source="<?= mediator_escape(mediator_application_url('app/data/vietnam-locations.json')) ?>">
                <div class="mediator-field">
                    <label for="mediator-home-q">Từ khóa</label>
                    <input id="mediator-home-q" type="search" name="q" value="<?= mediator_escape($filters['q']) ?>" placeholder="Tên, trường, chuyên môn…">
                </div>
                <div class="mediator-field">
                    <label for="mediator-home-specialty">Lĩnh vực</label>
                    <select id="mediator-home-specialty" name="specialty">
                        <option value="">Tất cả lĩnh vực</option>
                        <?php foreach ($specialtyOptions as $option): ?>
                            <option value="<?= mediator_escape($option) ?>"<?= $filters['specialty'] === $option ? ' selected' : '' ?>><?= mediator_escape($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= mediator_location_select_fields('mediator-home-location', $filters['province_location'], $filters['district_location']) ?>
                <div class="mediator-field">
                    <label for="mediator-home-availability">Tình trạng</label>
                    <?= mediator_availability_select('mediator-home-availability', $filters['availability']) ?>
                </div>
                <div class="mediator-field">
                    <label for="mediator-home-sort">Sắp xếp</label>
                    <?= mediator_sort_select('mediator-home-sort', $filters['sort']) ?>
                </div>
                <button class="mediator-submit" type="submit">Tìm hồ sơ</button>
            </form>
            <span class="mediator-location-status" role="status" aria-live="polite"></span>
        </section>
        <script src="<?= mediator_escape(mediator_application_url('app/views/mediators/location-filter.js')) ?>" defer></script>

        <section id="mediator-directory" aria-labelledby="mediator-home-results">
            <div class="mediator-section-heading">
                <div>
                    <div class="mediator-eyebrow">Hồ sơ nổi bật</div>
                    <h2 id="mediator-home-results">Gặp gỡ các hòa giải viên</h2>
                </div>
                <p><?= number_format($profileCount, 0, ',', '.') ?> hồ sơ phù hợp</p>
            </div>
            <?php if ($directory['error']): ?>
                <div class="mediator-state" role="status">
                    <strong>Danh bạ chưa thể tải lúc này.</strong>
                    <p>Vui lòng thử lại sau ít phút.</p>
                </div>
            <?php elseif (!$profiles): ?>
                <div class="mediator-home-empty" role="status">
                    <div>
                        <span class="mediator-empty-kicker"><?= $noProfilesMatch ? 'Không có hồ sơ khớp tìm kiếm' : ($directoryHasNoProfiles ? 'Chưa có hồ sơ được công bố' : 'Chưa tìm thấy kết quả') ?></span>
                        <h3><?= $noProfilesMatch ? 'Thử điều chỉnh tiêu chí tìm kiếm.' : ($directoryHasNoProfiles ? 'Đặt lịch sẽ mở khi có hồ sơ đối tác thực.' : 'Hãy thử lại với tiêu chí khác.') ?></h3>
                        <p><?= $noProfilesMatch
                            ? 'Danh bạ hiện có hồ sơ, nhưng chưa có kết quả khớp với các tiêu chí này. Hãy bỏ bớt bộ lọc hoặc tìm bằng từ khóa khác.'
                            : ($directoryHasNoProfiles
                                ? 'Hiện chưa có hòa giải viên nào được công bố trong danh bạ. Khi hồ sơ đối tác thực được đăng tải, bạn sẽ xem được thông tin và gửi yêu cầu đặt lịch tại đây. Chúng tôi không hiển thị hồ sơ mẫu.'
                                : 'Chưa thể xác định toàn bộ kết quả lúc này. Hãy bỏ bộ lọc để thử lại hoặc quay lại sau.') ?></p>
                        <div class="mediator-empty-actions">
                            <?php if ($hasHomeFilters && $directoryHasNoProfiles): ?>
                                <a href="index.php">Xóa bộ lọc</a>
                            <?php elseif ($noProfilesMatch || $hasHomeFilters): ?>
                                <a href="index.php">Xóa bộ lọc &amp; xem danh bạ</a>
                            <?php else: ?>
                                <a href="searchpage.php">Mở trang tìm kiếm</a>
                            <?php endif; ?>
                            <a href="#mediator-modes">Tìm hiểu hình thức tư vấn</a>
                        </div>
                    </div>
                    <aside class="mediator-empty-index" aria-label="<?= $noProfilesMatch ? 'Không có kết quả phù hợp' : 'Chưa có hồ sơ công khai' ?>">
                        <span>Hồ sơ đang hiển thị</span>
                        <strong><?= number_format($profileCount, 0, ',', '.') ?></strong>
                        <small><?= $noProfilesMatch ? 'Điều chỉnh tìm kiếm để xem kết quả phù hợp.' : ($directoryHasNoProfiles ? 'Chỉ hồ sơ thật đã được công bố mới xuất hiện.' : 'Bạn có thể bỏ bộ lọc và thử lại.') ?></small>
                    </aside>
                </div>
            <?php else: ?>
                <div class="mediator-results">
                    <?php foreach ($profiles as $profile) { mediator_render_home_booking_card($profile); } ?>
                </div>
                <a class="mediator-all-link" href="searchpage.php">Xem toàn bộ danh bạ <span aria-hidden="true">&nbsp;→</span></a>
            <?php endif; ?>
        </section>

        <section class="mediator-modes" id="mediator-modes" aria-label="Hình thức tư vấn">
            <article class="mediator-mode">
                <span class="mediator-mode-label">Linh hoạt</span>
                <h3>Tư vấn trực tuyến</h3>
                <p>Nếu phù hợp với hồ sơ và yêu cầu của bạn, có thể chọn hình thức trực tuyến. Cách thức trao đổi sẽ được xác nhận khi thống nhất lịch.</p>
            </article>
            <article class="mediator-mode">
                <span class="mediator-mode-label">Trao đổi trước</span>
                <h3>Tư vấn trực tiếp</h3>
                <p>Với buổi gặp trực tiếp, địa điểm cụ thể cần được hai bên thống nhất và xác nhận trước buổi tư vấn.</p>
            </article>
        </section>
    </div>
</main>
