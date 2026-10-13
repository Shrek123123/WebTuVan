<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/views/mediators/shared.php';
$conn = mediator_ensure_connection($conn);

$filters = [
    'q' => mediator_request_value($_GET['q'] ?? ''),
    'specialty' => mediator_request_value($_GET['specialty'] ?? ''),
    'availability' => mediator_request_value($_GET['availability'] ?? ''),
    'sort' => mediator_request_value($_GET['sort'] ?? 'rating'),
];
$pageSize = 9;
$page = max(1, (int) mediator_request_value($_GET['page'] ?? '1'));
$directory = mediator_fetch_profiles($conn, $filters);
$totalPages = max(1, (int) ceil($directory['total'] / $pageSize));
$page = min($page, $totalPages);
$profiles = array_slice($directory['profiles'], ($page - 1) * $pageSize, $pageSize);
$specialtyOptions = mediator_specialty_options($conn);
$hasSearchFilters = $filters['q'] !== '' || $filters['specialty'] !== '' || $filters['availability'] !== '';
$emptyDirectoryCheck = null;
if (!$directory['error'] && !$profiles && $hasSearchFilters) {
    $emptyDirectoryCheck = mediator_fetch_profiles($conn, ['sort' => $filters['sort']]);
}
$noProfilesMatch = $hasSearchFilters && $emptyDirectoryCheck !== null && !$emptyDirectoryCheck['error'] && $emptyDirectoryCheck['total'] > 0;
$directoryHasNoProfiles = $directory['total'] === 0 && (!$hasSearchFilters || ($emptyDirectoryCheck !== null && !$emptyDirectoryCheck['error'] && $emptyDirectoryCheck['total'] === 0));

function mediator_render_search_booking_card($profile)
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
    .mediator-search-page { min-height: 70vh; padding: 44px 22px 84px; background: var(--mediator-paper); color: var(--mediator-ink); }
    .mediator-search-wrap { width: min(1160px, 100%); margin: 0 auto; }
    .mediator-search-heading { padding: 15px 0 24px; }
    .mediator-search-heading .mediator-eyebrow { color: var(--mediator-teal); font-size: .75rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .mediator-search-heading h1 { margin: 10px 0 9px; font: 500 clamp(2.35rem, 4vw, 3.6rem)/1.08 Georgia, 'Times New Roman', serif; letter-spacing: -.035em; }
    .mediator-search-heading p { margin: 0; color: var(--mediator-muted); line-height: 1.65; }
    .mediator-filter-panel { margin-bottom: 30px; padding: 18px; border: 1px solid var(--mediator-line); background: #fffefa; box-shadow: 0 10px 28px rgba(34, 46, 45, .045); }
    .mediator-search-form { display: grid; grid-template-columns: minmax(200px, 1.5fr) repeat(3, minmax(140px, .8fr)) auto; gap: 10px; align-items: end; }
    .mediator-filter-panel .mediator-field label { display: block; margin-bottom: 7px; color: var(--mediator-muted); font-size: .75rem; font-weight: 700; }
    .mediator-filter-panel .mediator-field input, .mediator-filter-panel .mediator-field select { width: 100%; min-height: 45px; padding: 9px 11px; border: 1px solid #d8ddd7; border-radius: 2px; background: #fff; color: var(--mediator-ink); font: inherit; }
    .mediator-filter-panel .mediator-field input:focus, .mediator-filter-panel .mediator-field select:focus { outline: 3px solid rgba(42, 111, 101, .17); border-color: var(--mediator-teal); }
    .mediator-filter-panel .mediator-submit { min-height: 45px; padding: 0 18px; border: 1px solid var(--mediator-ink); background: var(--mediator-ink); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    .mediator-filter-panel .mediator-submit:hover { background: var(--mediator-teal); border-color: var(--mediator-teal); }
    .mediator-listing-meta { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin: 20px 0 14px; color: var(--mediator-muted); font-size: .94rem; }
    .mediator-listing-meta strong { color: var(--mediator-ink); }
    .mediator-listing-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .mediator-search-page .mediator-card {
        box-sizing: border-box;
        width: min(100%, 252px);
        margin: 0 auto;
        padding: 14px 14px 16px;
    }
    .mediator-search-page .mediator-card-top {
        display: block;
        margin-bottom: 12px;
    }
    .mediator-search-page .mediator-card-top .mediator-avatar,
    .mediator-search-page .mediator-card-top .mediator-avatar-fallback {
        display: block;
        box-sizing: border-box;
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
    .mediator-search-page .mediator-card-top .mediator-avatar-fallback {
        display: grid;
        place-items: center;
        color: var(--mediator-teal);
        font: 500 clamp(1.5rem, 2vw, 2.2rem)/1 Georgia, 'Times New Roman', serif;
    }
    .mediator-search-page .mediator-card-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        width: 100%;
        margin-bottom: 8px;
    }
    .mediator-search-page .mediator-card h3 {
        min-width: 0;
        flex: 1 1 auto;
        margin: 0;
    }
    .mediator-search-page .mediator-status {
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
    .mediator-pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
    .mediator-pagination a, .mediator-pagination span { min-width: 42px; padding: 10px 12px; border: 1px solid var(--mediator-line); background: #fffefa; color: var(--mediator-ink); text-align: center; text-decoration: none; }
    .mediator-pagination .is-current { border-color: var(--mediator-ink); background: var(--mediator-ink); color: #fff; }
    .mediator-card-link { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding: 12px 14px; border: 1px solid var(--mediator-ink); background: var(--mediator-ink); color: #fff; font-size: .9rem; font-weight: 750; text-decoration: none; }
    .mediator-card-link:hover { border-color: var(--mediator-teal); background: var(--mediator-teal); color: #fff; }
    .mediator-card-link:focus-visible, .mediator-pagination a:focus-visible { outline: 3px solid rgba(47, 112, 104, .38); outline-offset: 3px; }
    .mediator-card-facts div:first-child { grid-column: auto; }
    .mediator-booking-note { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 0 0 26px; padding: 18px 20px; border-left: 3px solid var(--mediator-teal); background: #eaf0eb; }
    .mediator-booking-note p { margin: 0; color: var(--mediator-muted); font-size: .88rem; line-height: 1.6; }
    .mediator-booking-note strong { display: block; margin-bottom: 5px; color: var(--mediator-ink); font: 500 1.03rem Georgia, 'Times New Roman', serif; }
    .mediator-directory-empty { display: grid; grid-template-columns: minmax(0, 1fr) minmax(170px, .4fr); gap: 28px; padding: clamp(23px, 4vw, 40px); border: 1px solid var(--mediator-line); background: #fffefa; box-shadow: 0 8px 24px rgba(34, 46, 45, .045); }
    .mediator-directory-empty-kicker { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 15px; color: var(--mediator-teal); font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .mediator-directory-empty-kicker::before { width: 8px; height: 8px; border-radius: 50%; background: #b8624d; content: ""; }
    .mediator-directory-empty h2 { max-width: 600px; margin: 0; color: var(--mediator-ink); font: 500 clamp(1.4rem, 2.7vw, 1.9rem)/1.22 Georgia, 'Times New Roman', serif; }
    .mediator-directory-empty p { max-width: 630px; margin: 12px 0 0; color: var(--mediator-muted); font-size: .93rem; line-height: 1.7; }
    .mediator-empty-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 11px 18px; margin-top: 21px; }
    .mediator-empty-actions a { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 0 14px; border: 1px solid var(--mediator-ink); color: var(--mediator-ink); font-size: .87rem; font-weight: 700; text-decoration: none; }
    .mediator-empty-actions a:hover { background: var(--mediator-ink); color: #fff; }
    .mediator-empty-actions a:focus-visible { outline: 3px solid rgba(47, 112, 104, .38); outline-offset: 3px; }
    .mediator-directory-empty-index { display: flex; min-height: 140px; flex-direction: column; justify-content: space-between; padding: 18px; border-left: 1px solid #d5dfd8; background: linear-gradient(135deg, #eef3ee, #f8f7f1); color: var(--mediator-teal); }
    .mediator-directory-empty-index span { color: var(--mediator-muted); font-size: .71rem; font-weight: 750; letter-spacing: .1em; text-transform: uppercase; }
    .mediator-directory-empty-index strong { font: 500 2.5rem/1 Georgia, 'Times New Roman', serif; }
    .mediator-directory-empty-index small { color: var(--mediator-muted); font-size: .81rem; line-height: 1.5; }
    @media (max-width: 950px) {
        .mediator-search-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mediator-listing-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mediator-search-page .mediator-card-top .mediator-avatar,
        .mediator-search-page .mediator-card-top .mediator-avatar-fallback { min-height: 144px; }
    }
    @media (max-width: 560px) {
        .mediator-search-page { padding: 24px 16px 58px; }
        .mediator-search-form, .mediator-listing-grid { grid-template-columns: 1fr; }
        .mediator-search-page .mediator-card-top .mediator-avatar,
        .mediator-search-page .mediator-card-top .mediator-avatar-fallback { min-height: 192px; }
        .mediator-filter-panel { padding: 13px; }
        .mediator-listing-meta { align-items: flex-start; flex-direction: column; gap: 4px; }
        .mediator-booking-note { grid-template-columns: 1fr; gap: 14px; padding: 16px; }
        .mediator-pagination { flex-wrap: wrap; }
        .mediator-directory-empty { grid-template-columns: 1fr; gap: 20px; padding: 21px 18px; }
        .mediator-directory-empty-index { min-height: 108px; }
        .mediator-empty-actions { align-items: stretch; flex-direction: column; }
    }
</style>

<main class="mediator-search-page">
    <div class="mediator-search-wrap">
        <header class="mediator-search-heading">
            <div class="mediator-eyebrow">Tra cứu hồ sơ</div>
            <h1>Danh bạ hòa giải viên</h1>
            <p>Tìm theo tên, trường, chuyên ngành, giới thiệu hoặc lĩnh vực chuyên môn. Mỗi hồ sơ có thông tin để bạn cân nhắc trước khi gửi yêu cầu đặt lịch.</p>
        </header>

        <section class="mediator-booking-note" aria-label="Các hình thức tư vấn">
            <p><strong>Trực tuyến</strong>Có thể chọn khi phù hợp với hồ sơ và yêu cầu; cách thức trao đổi sẽ được xác nhận khi thống nhất lịch.</p>
            <p><strong>Trực tiếp</strong>Địa điểm gặp cần được hai bên thống nhất và xác nhận trước buổi tư vấn.</p>
        </section>

        <section class="mediator-filter-panel" aria-label="Bộ lọc danh bạ">
            <form class="mediator-search-form" method="get" action="searchpage.php">
                <div class="mediator-field">
                    <label for="mediator-search-q">Từ khóa</label>
                    <input id="mediator-search-q" type="search" name="q" value="<?= mediator_escape($filters['q']) ?>" placeholder="Nhập từ khóa cần tìm">
                </div>
                <div class="mediator-field">
                    <label for="mediator-search-specialty">Lĩnh vực</label>
                    <select id="mediator-search-specialty" name="specialty">
                        <option value="">Tất cả lĩnh vực</option>
                        <?php foreach ($specialtyOptions as $option): ?>
                            <option value="<?= mediator_escape($option) ?>"<?= $filters['specialty'] === $option ? ' selected' : '' ?>><?= mediator_escape($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mediator-field">
                    <label for="mediator-search-availability">Tình trạng</label>
                    <?= mediator_availability_select('mediator-search-availability', $filters['availability']) ?>
                </div>
                <div class="mediator-field">
                    <label for="mediator-search-sort">Sắp xếp</label>
                    <?= mediator_sort_select('mediator-search-sort', $filters['sort']) ?>
                </div>
                <button class="mediator-submit" type="submit">Lọc danh bạ</button>
            </form>
        </section>

        <div class="mediator-listing-meta">
            <span><strong><?= number_format($directory['total'], 0, ',', '.') ?></strong> hồ sơ phù hợp</span>
            <?php if ($directory['total'] > 0): ?>
                <span>Trang <?= $page ?> / <?= $totalPages ?></span>
            <?php endif; ?>
        </div>

        <?php if ($directory['error']): ?>
            <div class="mediator-state" role="status">
                <strong>Danh bạ chưa thể tải lúc này.</strong>
                <p>Vui lòng thử lại sau ít phút.</p>
            </div>
        <?php elseif (!$profiles): ?>
            <section class="mediator-directory-empty" role="status" aria-labelledby="mediator-empty-title">
                <div>
                    <span class="mediator-directory-empty-kicker"><?= $noProfilesMatch ? 'Không có hồ sơ khớp tìm kiếm' : ($directoryHasNoProfiles ? 'Chưa có hồ sơ được công bố' : 'Chưa tìm thấy kết quả') ?></span>
                    <h2 id="mediator-empty-title"><?= $noProfilesMatch ? 'Thử điều chỉnh tiêu chí tìm kiếm.' : ($directoryHasNoProfiles ? 'Đặt lịch sẽ mở khi có hồ sơ đối tác thực.' : 'Hãy thử lại với tiêu chí khác.') ?></h2>
                    <p><?= $noProfilesMatch
                        ? 'Danh bạ hiện có hồ sơ, nhưng chưa có kết quả khớp với các tiêu chí này. Hãy bỏ bớt bộ lọc hoặc tìm bằng từ khóa khác.'
                        : ($directoryHasNoProfiles
                            ? 'Hiện chưa có hòa giải viên nào được công bố trong danh bạ. Khi hồ sơ đối tác thực được đăng tải, bạn sẽ xem thông tin và gửi yêu cầu đặt lịch tại hồ sơ đó. Chúng tôi không hiển thị hồ sơ mẫu.'
                            : 'Chưa thể xác định toàn bộ kết quả lúc này. Hãy bỏ bộ lọc để thử lại hoặc quay lại sau.') ?></p>
                    <div class="mediator-empty-actions">
                        <?php if ($hasSearchFilters): ?>
                            <a href="searchpage.php">Xóa bộ lọc</a>
                        <?php else: ?>
                            <a href="index.php">Về trang chủ</a>
                        <?php endif; ?>
                        <a href="index.php#mediator-modes">Xem hình thức tư vấn</a>
                    </div>
                </div>
                <aside class="mediator-directory-empty-index" aria-label="Số hồ sơ phù hợp">
                    <span>Hồ sơ phù hợp</span>
                    <strong><?= number_format($directory['total'], 0, ',', '.') ?></strong>
                    <small><?= $noProfilesMatch ? 'Xóa bớt điều kiện để mở rộng kết quả.' : ($directoryHasNoProfiles ? 'Chỉ hồ sơ thật đã công bố mới hiển thị.' : 'Bạn có thể bỏ bộ lọc và thử lại.') ?></small>
                </aside>
            </section>
        <?php else: ?>
            <div class="mediator-listing-grid">
                <?php foreach ($profiles as $profile) { mediator_render_search_booking_card($profile); } ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="mediator-pagination" aria-label="Phân trang danh bạ">
                    <?php if ($page > 1): ?>
                        <a href="?<?= mediator_escape(http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>" aria-label="Trang trước">←</a>
                    <?php endif; ?>
                    <?php for ($number = max(1, $page - 2); $number <= min($totalPages, $page + 2); $number++): ?>
                        <?php if ($number === $page): ?>
                            <span class="is-current" aria-current="page"><?= $number ?></span>
                        <?php else: ?>
                            <a href="?<?= mediator_escape(http_build_query(array_merge($filters, ['page' => $number]))) ?>"><?= $number ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= mediator_escape(http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>" aria-label="Trang sau">→</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
