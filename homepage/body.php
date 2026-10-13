<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<?php
// Đặt ở ĐẦU file jobs.php (hoặc index.php)
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../helpers.php');

function t($key, $replace = [])
{
  global $lang;
  $text = $lang[$key] ?? $key;
  foreach ($replace as $k => $v) {
    $text = str_replace(":$k", $v, $text);
  }
  return $text;
}

function normalizeIndustryMatchText($value)
{
  $raw = trim((string) $value);
  if ($raw === '') {
    return '';
  }

  $normalized = strtolower($raw);
  $normalized = str_replace('&', ' and ', $normalized);
  $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized);
  $normalized = preg_replace('/\s+/', ' ', $normalized);
  return trim($normalized);
}

function resolveIndustryFilterValue($value)
{
  $raw = trim((string) $value);
  if ($raw === '') {
    return '';
  }

  $legacyMap = [
    'it and software' => 'cong_nghe_thong_tin',
    'marketing' => 'marketing_truyen_thong',
    'finance' => 'tai_chinh_ke_toan_ngan_hang',
    'healthcare' => 'y_te_duoc_pham',
    'government and public sector' => 'nha_nuoc_cong_vu',
    'education' => 'giao_duc_dao_tao',
    'manufacturing' => 'san_xuat_van_hanh_day_chuyen',
    'transportation and logistics' => 'van_tai_logistics',
    'real estate' => 'bat_dong_san',
    'insurance' => 'bao_hiem',
    'construction' => 'xay_dung_kien_truc',
    'hospitality' => 'du_lich_khach_san_nha_hang',
    'law' => 'luat',
    'design' => 'thiet_ke_nghe_thuat',
    'agriculture' => 'nong_nghiep',
    'food and beverage' => 'food_beverage',
    'human resources' => 'nhan_su',
    'administrative and office' => 'hanh_chinh_van_phong',
    'engineering' => 'ky_thuat',
    'others' => 'khac',
    'telecommunications' => 'cong_nghe_thong_tin',
    'retail' => 'khac',
    'media and entertainment' => 'khac',
    'energy and utilities' => 'khac',
    'non profit' => 'khac',
    'consulting' => 'khac'
  ];

  $normalized = normalizeIndustryMatchText($raw);
  if (isset($legacyMap[$normalized])) {
    return $legacyMap[$normalized];
  }

  $directKey = strtolower(str_replace([' ', '-'], '_', $raw));
  $directKey = preg_replace('/[^a-z0-9_]+/', '', $directKey);
  return trim((string) $directKey, '_');
}

$industryOptionKeys = [
  'kinh_doanh_ban_hang',
  'marketing_truyen_thong',
  'tai_chinh_ke_toan_ngan_hang',
  'cong_nghe_thong_tin',
  'y_te_duoc_pham',
  'nha_nuoc_cong_vu',
  'giao_duc_dao_tao',
  'san_xuat_van_hanh_day_chuyen',
  'van_tai_logistics',
  'bat_dong_san',
  'bao_hiem',
  'xay_dung_kien_truc',
  'du_lich_khach_san_nha_hang',
  'luat',
  'thiet_ke_nghe_thuat',
  'nong_nghiep',
  'food_beverage',
  'nhan_su',
  'hanh_chinh_van_phong',
  'ky_thuat',
  'khac'
];

$industryFilterOptions = [];
foreach ($industryOptionKeys as $industryKey) {
  if (isset($lang[$industryKey]) && is_string($lang[$industryKey]) && $lang[$industryKey] !== '') {
    $industryFilterOptions[] = [
      'value' => $industryKey,
      'label' => $lang[$industryKey],
      'db_key' => $industryKey,
    ];
  }
}

$isLoggedIn = isset($_SESSION['jobseeker_id']) && $_SESSION['jobseeker_id'] > 0;
$userId = $isLoggedIn ? (int) $_SESSION['jobseeker_id'] : 0;

// Lấy danh sách job_id mà người dùng hiện tại đã lưu từ database
$savedJobIds = [];
if ($isLoggedIn) {
  $stmt = $conn->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
  if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result_saved_jobs = $stmt->get_result();
    while ($row = $result_saved_jobs->fetch_assoc()) {
      $savedJobIds[] = (string) $row['job_id']; // Đảm bảo chuyển về string để khớp với data-job-id của HTML
    }
    $stmt->close();
  } else {
    error_log("Error preparing query to fetch saved_jobs: " . $conn->error);
  }
}
$savedJobIdsJson = json_encode($savedJobIds);

?>
<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  html,
  body {
    max-width: 100%;
    overflow-x: clip;
  }

  body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    color: #000;
  }

  .section-1 {
    background: linear-gradient(to right, #c31432, rgb(84, 37, 117));
  }

  .container {
    max-width: 1100px;
    margin: auto;
    padding: 40px 20px;
  }

  .title {
    font-size: 24px;
    font-weight: bold;
    text-align: center;
  }

  .subtitle {
    text-align: center;
    margin-top: 5px;
    font-size: 14px;
    color: #ddd;
  }

  .search-box {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;

  }

  .search-box input,
  .search-box select {
    padding: 10px;
    border-radius: 8px;
    border: none;
    min-width: 220px;
  }

  .search-box button {
    background-color: #c4002f;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .main-content {
    display: flex;
    margin-top: 30px;
    gap: 20px;
    flex-wrap: wrap;
  }

  .left-menu {
    flex: 1;
    min-width: 200px;
    background: white;
    color: black;
    border-radius: 10px;
    padding: 20px;
  }

  .left-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .left-menu li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    cursor: pointer;
  }

  .right-banner {
    flex: 3;
    min-width: 300px;
  }

  .right-banner img {
    width: 100%;
    border-radius: 10px;
  }

  .job-section {
    padding: 40px 20px;
    border-radius: 10px;
    background-color: #eee;
    margin: 10px;
  }



  .job-filters {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .job-filters button {
    padding: 8px 15px;
    border: none;
    border-radius: 20px;
    background-color: #eee;
    color: #333;
    cursor: pointer;
  }

  .job-filters button.active {
    background-color: #d70018;
    color: white;
    font-weight: bold;
  }

  .sort-dropdown button {
    background-color: #e0e0e0;
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
  }

  .section-2 {
    background-color: #eee;
  }


  .circle-logo {
    background-color: #888;
    color: white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    text-align: center;
    line-height: 32px;
    font-weight: bold;
  }



  .pagination {
    margin-top: 30px;
    text-align: center;
  }

  .pagination .dot {
    height: 10px;
    width: 10px;
    margin: 0 4px;
    background-color: #ccc;
    border-radius: 50%;
    display: inline-block;
  }

  .pagination .dot.active {
    background-color: #d70018;
  }

  .section-3 {
    background-color: #eee;
    position: relative;
    z-index: 1;
  }

  .section-3 .container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
  }

  .section-3 .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .section-3 h2 {
    font-size: 24px;
    font-weight: bold;
    color: #000;
  }

  .section-3 .view-all {
    color: #c00;
    text-decoration: none;
    font-weight: bold;
  }

  .logos {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    gap: 20px;
  }

  .logos img {
    max-height: 60px;
    object-fit: contain;
  }

  .logos .featured-company {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    color: #333;
    font-size: 13px;
    text-align: center;
    text-decoration: none;
  }

  .featured-carousel {
    position: relative;
    display: block;
    padding: 14px;
    border: 1px solid #d7dedb;
    background: #f7f9f8;
  }

  .section-3 .container > .logos {
    display: block;
  }

  .featured-carousel__arrow {
    position: absolute;
    top: 50%;
    z-index: 2;
    display: grid;
    width: 38px;
    height: 52px;
    padding: 0;
    place-items: center;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #111;
    font-size: 38px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
  }

  #featured-company-prev {
    left: calc((100vw - 100%) / -2 + 8px);
  }

  #featured-company-next {
    right: calc((100vw - 100%) / -2 + 8px);
  }

  .featured-carousel__arrow:disabled {
    color: #111;
    opacity: .35;
    cursor: default;
  }

  .featured-carousel__arrow:not(:disabled):hover {
    color: #111;
    opacity: .7;
  }

  .featured-carousel .logos {
    min-width: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    align-items: stretch;
    gap: 12px;
  }

  .featured-carousel .featured-company {
    min-width: 0;
    justify-content: center;
    padding: 10px 8px;
    background: #fff;
    border: 1px solid #e0e7e4;
  }

  .featured-carousel .featured-company img {
    width: 100%;
    height: 58px;
  }

  .featured-companies-empty {
    grid-column: 1 / -1;
    margin: 0;
    padding: 20px;
    color: #687a75;
    text-align: center;
  }

  .section-4 {
    background-color: #f9f5f5;

  }

  .section-4 h3 {
    font-size: 20px;
    font-weight: bold;
    margin-top: 30px;
  }

  .section-4 ul {
    list-style: disc;
    margin-left: 20px;
  }

  .info {
    background-color: #f9f5f5;

  }

  .job-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    max-width: 1300px;
    margin: 0 auto;

  }

  .job-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    padding: 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
  }

  .job-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .job-header h3 {
    font-size: 16px;
    margin: 0;
  }

  .save-btn {
    background: none;
    border: none;
    font-size: 20px;
    color: #e74c3c;
    cursor: pointer;
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
  }

  .job-body {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-top: 10px;
  }

  .company-logo {
    width: 90px;
    height: 80px;
    object-fit: contain;
    border-radius: 8px;
    flex-shrink: 0;
    margin-right: 12px;
  }

  .job-info {
    font-size: 14px;
  }

  .job-info .company-name {
    font-weight: bold;
    margin-bottom: 5px;
    color: #818080;
  }

  .job-meta-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
  }

  .job-meta-item {
    display: inline-flex;
    align-items: center;
    font-size: 13px;
    color: #d45a5a;
  }

  .job-meta-divider {
    width: 1px;
    height: 12px;
    background-color: #ccc;
    display: inline-block;
  }

  .divider {
    border-top: 1px solid #e0e0e0;
    margin: 10px 0;
  }

  .job-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 8px;
  }

  .job-footer-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    flex: 1;
  }

  .job-footer-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    border-radius: 999px;
    background: #f3f4f6;
    color: #555;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
  }

  .save-btn {
    background: #fff1f3;
    border: 1px #e74c3c;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin-left: auto;
    flex-shrink: 0;
    position: static;
  }

  .save-btn .heart-icon {
    width: 18px;
    height: 18px;
  }

  @media (max-width: 992px) {
    .job-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 600px) {
    .job-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .container {
      padding: 24px 16px;
    }

    .title {
      font-size: 20px;
    }

    .search-box {
      margin-top: 20px;
    }

    .search-box form {
      flex-direction: column !important;
      align-items: stretch !important;
      width: 100%;
    }

    .search-box input,
    .search-box select,
    .search-box button {
      width: 100%;
      min-width: 0;
      box-sizing: border-box;
    }

    .main-content {
      flex-direction: column;
      margin-top: 20px;
      gap: 15px;
    }

    .left-menu {
      width: 100%;
      min-width: 0;
      order: 2;
    }

    .right-banner {
      width: 100%;
      min-width: 0;
      order: 1;
    }

    .right-banner img {
      max-height: 160px;
      object-fit: cover;
    }

    .job-section {
      padding: 24px 12px;
      margin: 10px 0;
    }

    .job-section h2 {
      font-size: 18px;
    }

    .job-filters {
      gap: 8px;
      flex-wrap: nowrap;
    }

    .job-filters button {
      padding: 6px 12px;
      font-size: 13px;
      flex-shrink: 0;
    }

    .industry-filter-carousel {
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch;
    }

    .industry-filter-window {
      overflow-x: auto !important;
    }

    .industry-nav-btn {
      display: none !important;
    }

    .company-logo {
      width: 60px;
      height: 60px;
    }

    .logos {
      flex-wrap: wrap;
      justify-content: center;
    }

    .logos img {
      max-height: 40px;
    }

    .section-3 .header {
      flex-wrap: wrap;
      gap: 10px;
    }

    .featured-carousel {
      display: grid;
      grid-template-columns: 34px minmax(0, 1fr) 34px;
      align-items: stretch;
      gap: 8px;
      padding: 8px;
    }

    .featured-carousel__arrow {
      position: static;
      width: 34px;
      height: auto;
      transform: none;
      font-size: 27px;
    }

    #featured-company-prev,
    #featured-company-next {
      left: auto;
      right: auto;
    }

    .featured-carousel .logos {
      display: flex;
      gap: 10px;
      overflow-x: auto;
      overscroll-behavior-x: contain;
      scroll-snap-type: x mandatory;
      scrollbar-width: none;
    }

    .featured-carousel .logos::-webkit-scrollbar {
      display: none;
    }

    .featured-carousel .featured-company {
      flex: 0 0 calc((100% - 20px) / 3);
      scroll-snap-align: start;
      padding: 8px 5px;
    }

    .featured-carousel .featured-company img {
      height: 48px;
    }

    .featured-carousel__arrow {
      font-size: 23px;
    }
  }
</style>

<section class="section-1">
  <div class="container">

    <!-- Tiêu đề & mô tả -->
    <h1 class="title"><?= $lang['job_search']; ?></h1>
    <div class="subtitle"><?= $lang['job_search_subtitle']; ?></div>

    <!-- Hộp tìm kiếm -->
    <div class="search-box">
      <form action="searchpage.php" method="get" style="display: flex; gap: 10px; flex-wrap: wrap;">

        <select name="category">
          <option value=""><?php echo $lang['job_category_select_menu']; ?></option>
          <?php foreach ($industryFilterOptions as $option): ?>
            <option value="<?= htmlspecialchars($option['value']) ?>" <?= (isset($_GET['category']) && $_GET['category'] === $option['value']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($option['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <input type="text" name="q" placeholder="<?= $lang['job_title']; ?>">
        <input type="text" name="location" placeholder="<?= $lang['location']; ?>">
        <button type="submit"><?= $lang['search']; ?></button>
      </form>
    </div>

    <!-- Bộ lọc loại công việc -->
    <div class="main-content">
      <div class="left-menu">
        <ul>
          <li id="filter-fulltime" class="job-type-filter" data-job-type="full-time"><?= $lang['job_type_filters']['full_time']; ?></li>
          <li id="filter-parttime" class="job-type-filter" data-job-type="part-time"><?= $lang['job_type_filters']['part_time']; ?></li>
          <li id="filter-internship" class="job-type-filter" data-job-type="internship"><?= $lang['job_type_filters']['internship']; ?></li>
          <li id="filter-freelance" class="job-type-filter" data-job-type="freelance"><?= $lang['job_type_filters']['freelance']; ?></li>
          <li id="filter-remote" class="job-type-filter" data-job-type="remote"><?= $lang['job_type_filters']['remote']; ?></li>
        </ul>


        <style>
          .job-type-filter.active {
            background: #d70018;
            color: #fff;
            border-radius: 10px;
            transition: background 0.2s, color 0.2s;
          }

          .left-menu .job-type-filter {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding-left: 12px;
          }
        </style>
        <script>
          function buildJobsUrl(overrides) {
            const params = new URLSearchParams(window.location.search);
            Object.entries(overrides).forEach(function ([key, value]) {
              if (value === null || value === '') {
                params.delete(key);
              } else {
                params.set(key, value);
              }
            });
            const query = params.toString();
            return (query ? '?' + query : '?') + '#job-grid';
          }

          document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const activeType = (urlParams.get('job_type') || '').trim().toLowerCase();

            document.querySelectorAll('.job-type-filter').forEach(function (item) {
              const itemType = (item.getAttribute('data-job-type') || '').trim().toLowerCase();
              item.classList.toggle('active', itemType === activeType);

              item.addEventListener('click', function () {
                const selectedType = (this.getAttribute('data-job-type') || '').trim().toLowerCase();
                window.location.href = buildJobsUrl({
                  page: '1',
                  job_type: selectedType,
                });
              });
            });
          });
        </script>

      </div>
      <div class="right-banner">
        <img src="image/jobhive.png" alt="Tuyển dụng">
      </div>
    </div>
  </div>
</section>

<div class="info">
  <section class="section-2">
    <div class="job-section">
      <div class="job-header">
        <h2><?= $lang['urgent_job_openings']; ?></h2>
      </div> <!-- giữ nguyên thẻ đóng của khối bên ngoài nếu có -->

      <div class="job-filters" style="align-items: center;">
        <button class="job-category-filter industry-filter-item" data-category="" data-db-key="">
          <?= $lang['all_jobs']; ?>
        </button>

        <style>
          .job-category-filter.active,
          .industry-filter-item.active {
            background: #d70018;
            color: #fff;
            border-radius: 20px;
            transition: background 0.2s, color 0.2s;
          }
        </style>

        <div class="industry-filter-carousel" style="flex: 1; overflow: hidden; position: relative;">
          <button type="button" class="industry-nav-btn" data-direction="prev" aria-label="Previous industries" style="display:none;">&lsaquo;</button>
          <div class="industry-filter-window" style="overflow: hidden; flex: 1;">
            <div class="industry-filter-track" style="display: flex; gap: 10px; transition: transform 0.35s ease; white-space: nowrap;">
              <?php foreach ($industryFilterOptions as $option): ?>
                <button class="job-category-filter industry-filter-item" data-category="<?= htmlspecialchars($option['value']) ?>" data-db-key="<?= htmlspecialchars($option['db_key']) ?>">
                  <?= htmlspecialchars($option['label']) ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="button" class="industry-nav-btn" data-direction="next" aria-label="Next industries">&rsaquo;</button>
        </div>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const urlParams = new URLSearchParams(window.location.search);
          const categoryValue = (urlParams.get('category') || '').trim();

          function syncIndustryButtons() {
            const activeValue = (categoryValue || '').trim().toLowerCase();
            document.querySelectorAll('.industry-filter-item').forEach(function (btn) {
              const category = (btn.getAttribute('data-category') || '').trim();
              const dbKey = (btn.getAttribute('data-db-key') || '').trim();
              const matchValue = (dbKey || category || '').trim().toLowerCase();
              const isActive = activeValue === ''
                ? (category === '' && dbKey === '')
                : (matchValue === activeValue);

              btn.classList.toggle('active', isActive);
            });
          }

          syncIndustryButtons();

          const filterButtons = Array.from(document.querySelectorAll('.industry-filter-item:not(.industry-nav-btn)'));
          const carouselTrack = document.querySelector('.industry-filter-track');
          const prevBtn = document.querySelector('.industry-nav-btn[data-direction="prev"]');
          const nextBtn = document.querySelector('.industry-nav-btn[data-direction="next"]');

          if (carouselTrack && filterButtons.length > 7) {
            const visibleCount = 7;
            const step = 3;
            let startIndex = 0;

            function updateCarousel() {
              const maxStart = Math.max(0, filterButtons.length - visibleCount);
              startIndex = Math.min(startIndex, maxStart);
              const offset = filterButtons[0].offsetWidth + 10;
              carouselTrack.style.transform = 'translateX(' + (-startIndex * offset) + 'px)';

              if (prevBtn) prevBtn.style.display = startIndex > 0 ? 'inline-flex' : 'none';
              if (nextBtn) nextBtn.style.display = startIndex < maxStart ? 'inline-flex' : 'none';
            }

            if (prevBtn) {
              prevBtn.addEventListener('click', function () {
                startIndex = Math.max(0, startIndex - step);
                updateCarousel();
              });
            }

            if (nextBtn) {
              nextBtn.addEventListener('click', function () {
                startIndex = Math.min(filterButtons.length - visibleCount, startIndex + step);
                updateCarousel();
              });
            }

            updateCarousel();
          } else if (carouselTrack) {
            carouselTrack.style.transform = 'translateX(0px)';
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
          }

          document.querySelectorAll('.industry-filter-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
              const btnCategory = (btn.getAttribute('data-category') || '').trim();
              const btnDbKey = (btn.getAttribute('data-db-key') || '').trim();
              const selectedValue = (btnDbKey || btnCategory || '').trim();
              const params = new URLSearchParams(window.location.search);
              params.set('page', '1');

              if (selectedValue !== '') {
                params.set('category', selectedValue);
              } else {
                params.delete('category');
              }

              const query = params.toString();
              const nextUrl = (query ? '?' + query : '?') + '#job-grid';
              window.location.assign(nextUrl);
            });
          });
        });
      </script>

      <div class="job-grid" id="job-grid">
        <?php
        // Pagination logic
        $jobs_per_page = 9;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
        $job_type = isset($_GET['job_type']) ? trim((string) $_GET['job_type']) : '';
        $selectedIndustryKey = $category !== '' ? strtolower(preg_replace('/[^a-z0-9_]+/', '', str_replace(['-', ' '], '_', $category))) : '';
        $selectedIndustryKey = trim((string) $selectedIndustryKey, '_');

        $jobTypeMap = [
          'full-time' => 'full-time',
          'full time' => 'full-time',
          'toàn thời gian' => 'full-time',
          'bán thời gian' => 'part-time',
          'part-time' => 'part-time',
          'part time' => 'part-time',
          'thực tập' => 'internship',
          'internship' => 'internship',
          'thời vụ' => 'freelance',
          'freelance' => 'freelance',
          'làm việc từ xa' => 'remote',
          'remote' => 'remote',
        ];
        $normalized_job_type = strtolower($job_type);
        if ($job_type !== '' && isset($jobTypeMap[$normalized_job_type])) {
          $job_type = $jobTypeMap[$normalized_job_type];
        } elseif ($job_type !== '') {
          $job_type = strtolower($job_type);
        }

        $count_sql = "SELECT COUNT(*) as total
            FROM hoa_giai_vien j
            LEFT JOIN company c ON c.id = j.company_id
            WHERE j.status = 'approved'
            AND (c.status IS NULL OR c.status NOT IN ('pending_review', 'rejected'))";
        $count_bind_types = '';
        $count_params = [];
        if ($selectedIndustryKey !== '') {
          $count_sql .= " AND LOWER(TRIM(c.industry)) = ?";
          $count_bind_types .= 's';
          $count_params[] = $selectedIndustryKey;
        }
        if ($job_type !== '') {
          $count_sql .= " AND LOWER(j.job_type) = ?";
          $count_bind_types .= 's';
          $count_params[] = $job_type;
        }
        $stmt_count = $conn->prepare($count_sql);
        if (!empty($count_params)) {
          $stmt_count->bind_param($count_bind_types, ...$count_params);
        }
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $total_jobs = $result_count ? (int) $result_count->fetch_assoc()['total'] : 0;
        $total_pages = max(1, (int) ceil($total_jobs / $jobs_per_page));

        $offset = ($page - 1) * $jobs_per_page;
        $sql = "SELECT j.id, j.job_title, j.salary, j.job_location, j.job_type, j.job_experience, j.created_at, j.post_duration,
                        c.company_name, c.company_logo, c.industry AS industry
                        FROM hoa_giai_vien j
                        LEFT JOIN company c ON c.id = j.company_id
                        LEFT JOIN (SELECT company_id, MIN(paid_at) AS featured_at FROM prominent_company WHERE status = 'paid' GROUP BY company_id) pc_featured ON pc_featured.company_id = j.company_id
                        WHERE j.status = 'approved'
                        AND (c.status IS NULL OR c.status NOT IN ('pending_review', 'rejected'))";
        $bind_types = '';
        $params = [];
        if ($selectedIndustryKey !== '') {
          $sql .= " AND LOWER(TRIM(c.industry)) = ?";
          $bind_types .= 's';
          $params[] = $selectedIndustryKey;
        }
        if ($job_type !== '') {
          $sql .= " AND LOWER(j.job_type) = ?";
          $bind_types .= 's';
          $params[] = $job_type;
        }
        $sql .= " ORDER BY pc_featured.featured_at IS NULL ASC, pc_featured.featured_at ASC, j.created_at DESC LIMIT ? OFFSET ?";
        $bind_types .= 'ii';
        $params[] = $jobs_per_page;
        $params[] = $offset;
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
          $stmt->bind_param($bind_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Output jobs (only 9 per page)
        if ($result && $result->num_rows > 0):
          while ($row = $result->fetch_assoc()):
            $created_at = new DateTime($row['created_at']);
            $post_duration = (int) $row['post_duration'];
            $expire_at = clone $created_at;
            $expire_at->modify("+$post_duration days");
            $now = new DateTime();
            $interval = $now->diff($expire_at);
            $days_left = (int) $interval->format('%r%a');
            $days_left_text = $days_left > 0
              ? t('dayleft', ['d' => $days_left])
              : t('expired');
            $job_id = (int) $row['id'];
            $companyName = $row['company_name'] ?? 'ViecNhe';
            $companyLogo = normalizeCompanyLogoPath($row['company_logo'] ?? 'image/logo1.png');
            $companyIndustry = translateCompanyIndustry($row['industry'] ?? '', $lang) ?: ($lang['jobdetail_unknown'] ?? 'Unknown');
            $jobExperienceLabel = translateJobExperience($row['job_experience'] ?? '', $lang);
            $isJobAlreadySaved = in_array((string) $job_id, $savedJobIds);
            if ($isJobAlreadySaved) {
              $savedClass = 'active';
            } else {
              $savedClass = '';
            }
            ?>
            <div class="job-card">
              <a href="jobdetail.php?id=<?php echo $job_id; ?>" style="text-decoration:none;color:inherit;">
                <div class="job-body">
                  <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Company Logo" class="company-logo">
                  <div class="job-info">
                    <div class="job-header">
                    <h3><?php echo htmlspecialchars($row['job_title']); ?></h3>
                    </div>
                    <div class="company-name"><?php echo htmlspecialchars($companyName); ?></div>
                    <?php if ($companyIndustry !== ''): ?>
                      <div class="company-name" style="font-size:12px; color:#666; margin-top:4px;">
                        <?php echo htmlspecialchars($companyIndustry); ?>
                      </div>
                    <?php endif; ?>
                    <div class="job-meta-row">
                      <div class="job-meta-item"><?php echo htmlspecialchars(translateJobSalary($row['salary'] ?? '', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
                      <span class="job-meta-divider" aria-hidden="true"></span>
                      <div class="job-meta-item"><?php echo htmlspecialchars($row['job_location']); ?></div>
                    </div>
                  </div>
                </div>
              </a>
              <div class="job-footer">
                <div class="job-footer-meta">
                  <div class="job-footer-pill"><?php echo htmlspecialchars(translateJobType($row['job_type'] ?? '', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="job-footer-pill"><?php echo htmlspecialchars($jobExperienceLabel); ?></div>
                  <div class="job-footer-pill"><?php echo $days_left_text; ?></div>
                </div>
                <button class="save-btn" aria-pressed="<?php echo $isJobAlreadySaved ? 'true' : 'false'; ?>"
                  title="Save job" data-job-id="<?php echo $job_id; ?>">
                  <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e74c3c"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                      d="M12 21s-6.5-5.5-8.5-8.5C1.5 9.5 3.5 6 7 6c1.7 0 3.4 1.1 4.1 2.7C11.6 7.1 13.3 6 15 6c3.5 0 5.5 3.5 3.5 6.5C18.5 15.5 12 21 12 21z"
                      fill="<?php echo $isJobAlreadySaved ? '#e74c3c' : '#fff'; ?>" />
                  </svg>
                </button>
              </div>
            </div>
            <?php
          endwhile;
        else:
          ?>
          <div><?= $lang['no_jobs_found']; ?></div>
        <?php endif; ?>
      </div>

      <script>
        // Lưu trữ biến lang trong window để sử dụng trong các script khác
        window.lang = <?= json_encode($lang) ?>;
        // Các biến PHP truyền sang JavaScript
        window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        window.currentUserId = <?php echo $userId; ?>;
        // Danh sách Job ID đã lưu của người dùng hiện tại, lấy từ database
        window.initialSavedJobIds = <?php echo $savedJobIdsJson; ?>;

        document.addEventListener('DOMContentLoaded', function () {
          // Khởi tạo trạng thái cho localStorage nếu chưa có hoặc không khớp
          // Điều này đảm bảo trạng thái localStorage luôn phản ánh dữ liệu từ DB khi tải trang
          localStorage.setItem('savedJobs', JSON.stringify(window.initialSavedJobIds));

          document.querySelectorAll('.save-btn').forEach(function (btn) {
            const jobId = btn.getAttribute('data-job-id');
            const heartPath = btn.querySelector('.heart-icon path');
            // Lấy trạng thái hiện tại từ localStorage (đã được đồng bộ với DB ở trên)
            let savedJobs = JSON.parse(localStorage.getItem('savedJobs') || '[]');

            // Cập nhật trạng thái ban đầu của nút dựa trên dữ liệu PHP (và localStorage)
            if (savedJobs.includes(jobId)) {
              heartPath.setAttribute('fill', '#e74c3c');
              btn.setAttribute('aria-pressed', 'true');
            } else {
              heartPath.setAttribute('fill', '#fff');
              btn.setAttribute('aria-pressed', 'false');
            }

            btn.addEventListener('click', function (e) {
              // Kiểm tra đăng nhập
              if (!window.isLoggedIn) {
                showPopup(window.lang.login_to_save_jobs);
                return;
              }

              let currentSavedJobs = JSON.parse(localStorage.getItem('savedJobs') || '[]');
              const isCurrentlySaved = btn.getAttribute('aria-pressed') === 'true';
              let action;

              if (!isCurrentlySaved) { // Chưa lưu -> Lưu
                heartPath.setAttribute('fill', '#e74c3c');
                btn.setAttribute('aria-pressed', 'true');
                currentSavedJobs.push(jobId);
                showPopup(window.lang.job_saved_successfully);
                action = 'save';
              } else { // Đã lưu -> Hủy lưu
                heartPath.setAttribute('fill', '#fff');
                btn.setAttribute('aria-pressed', 'false');
                currentSavedJobs = currentSavedJobs.filter(id => id !== jobId);
                showPopup(window.lang.job_unsaved_successfully);
                action = 'unsave';
              }
              localStorage.setItem('savedJobs', JSON.stringify(currentSavedJobs));
              saveJobAjax(jobId, action); // Gửi yêu cầu AJAX
            });
          });

          // Hàm gửi yêu cầu AJAX đến save_job_action.php
          function saveJobAjax(jobId, action) {
            fetch('save_job_action.php', { // Tên file xử lý AJAX mới
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'action=' + encodeURIComponent(action) + '&job_id=' + encodeURIComponent(jobId)
            })
              .then(response => {
                if (!response.ok) {
                  throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json(); // Chờ phản hồi JSON từ server
              })
              .then(data => {
                if (data.status === 'success') {
                  console.log('Database update successful:', data.message);
                } else {
                  console.error('Database update failed:', data.message);
                  // Optionally handle error here, e.g., revert UI changes
                  showPopup('Error: ' + data.message); // Show error from server
                }
              })
              .catch(error => {
                console.error('AJAX request error:', error);
                showPopup('Connection or processing error. Please try again.');
              });
          }

          // Hàm hiển thị Popup (giữ nguyên từ code của bạn)
          function showPopup(message) {
            let existing = document.getElementById('job-popup-message');
            if (existing) {
              existing.remove();
            }
            let popup = document.createElement('div');
            popup.id = 'job-popup-message';
            popup.textContent = message;
            popup.style.position = 'fixed';
            popup.style.top = '30px';
            popup.style.left = '50%';
            popup.style.transform = 'translateX(-50%)';
            popup.style.background = '#fff';
            popup.style.color = '#e74c3c';
            popup.style.padding = '12px 28px';
            popup.style.borderRadius = '8px';
            popup.style.boxShadow = '0 2px 12px rgba(0,0,0,0.15)';
            popup.style.zIndex = 9999;
            popup.style.opacity = 1;
            document.body.appendChild(popup);
            setTimeout(() => {
              popup.style.transition = 'opacity 0.4s';
              popup.style.opacity = 0;
              setTimeout(() => popup.remove(), 400);
            }, 3000);
          }
        });
      </script>

      <div class="pagination" id="job-pagination">
        <a href="<?php echo $page > 1 ? '?page=' . ($page - 1) . ($category ? '&category=' . urlencode($category) : '') . ($job_type ? '&job_type=' . urlencode($job_type) : '') : 'javascript:void(0);'; ?>" class="arrow<?php if ($page <= 1)
                       echo ' disabled'; ?>" <?php if ($page <= 1)
                           echo 'tabindex="-1" aria-disabled="true"'; ?>
          data-scroll="1">
          &larr;
        </a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?php echo $i; ?><?php if ($category) echo '&category=' . urlencode($category); ?><?php if ($job_type) echo '&job_type=' . urlencode($job_type); ?>" class="page-number<?php if ($i == $page)
               echo ' active'; ?>" data-scroll="1">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
        <a href="<?php echo $page < $total_pages ? '?page=' . ($page + 1) . ($category ? '&category=' . urlencode($category) : '') . ($job_type ? '&job_type=' . urlencode($job_type) : '') : 'javascript:void(0);'; ?>" class="arrow<?php if ($page >= $total_pages)
                       echo ' disabled'; ?>" <?php if ($page >= $total_pages)
                           echo 'tabindex="-1" aria-disabled="true"'; ?> data-scroll="1">&rarr;
        </a>
      </div>
      <script>
        // Khi click vào pagination, thêm hash để biết cần scroll sau khi reload
        document.querySelectorAll('#job-pagination a[data-scroll="1"]').forEach(function (a) {
          a.addEventListener('click', function (e) {
            if (this.getAttribute('href').startsWith('?page=')) {
              // Thêm hash để sau reload biết cần scroll
              window.location.href = this.getAttribute('href') + '#job-grid';
              e.preventDefault();
            }
          });
        });

        // Khi trang tải lại, nếu có hash #job-grid thì scroll tới .job-grid
        document.addEventListener('DOMContentLoaded', function () {
          if (window.location.hash === '#job-grid') {
            var el = document.querySelector('.job-grid');
            if (el) {
              el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }
        });
      </script>
      <style>
        .pagination .page-number {
          display: inline-block;
          min-width: 24px;
          padding: 4px 8px;
          margin: 0 2px;
          border-radius: 6px;
          background: #ccc;
          color: #333;
          font-weight: bold;
          text-align: center;
          text-decoration: none;
          transition: background 0.2s, color 0.2s;
        }

        .pagination .page-number.active {
          background: #d70018;
          color: #fff;
          pointer-events: none;
        }

        .pagination .arrow {
          display: inline-block;
          min-width: 24px;
          padding: 4px 8px;
          margin: 0 2px;
          border-radius: 6px;
          background: #eee;
          color: #333;
          font-weight: bold;
          text-align: center;
          cursor: pointer;
          text-decoration: none;
          transition: background 0.2s, color 0.2s;
        }

        .pagination .arrow.disabled {
          background: #f3f3f3;
          color: #bbb;
          cursor: not-allowed;
          pointer-events: none;
        }

        .pagination a {
          text-decoration: none;
        }
      </style>
    </div>
  </section>


  <section class="section-3">
    <div class="container">
      <div class="header">
        <h2><?= $lang['featured_companies']; ?></h2>
        <a href="/ds_cong_ty_noi_bat.php" class="view-all"><?= $lang['view_all']; ?></a>
      </div>

      <div class="logos">
        <?php
        $featuredCompaniesWithMore = getProminentCompanies($conn, 7);
        $featuredHasNext = count($featuredCompaniesWithMore) > 6;
        $featuredCompanies = array_slice($featuredCompaniesWithMore, 0, 6);
        if ($featuredCompanies):
        ?>
          <div class="featured-carousel" id="featured-company-carousel" data-has-next="<?= $featuredHasNext ? '1' : '0'; ?>">
            <button class="featured-carousel__arrow" id="featured-company-prev" type="button" aria-label="Previous featured companies" disabled>&lsaquo;</button>
            <div class="logos" id="featured-company-list" aria-live="polite">
              <?php foreach ($featuredCompanies as $featuredCompany): ?>
                <?php
                $featuredCompanyLogo = normalizeCompanyLogoPath($featuredCompany['company_logo'] ?? '');
                $featuredCompanyName = (string) $featuredCompany['company_name'];
                ?>
                <a class="featured-company" href="/companyprofile.php?company_id=<?= (int) $featuredCompany['id']; ?>" title="<?= htmlspecialchars($featuredCompanyName, ENT_QUOTES, 'UTF-8'); ?>">
                  <img src="<?= htmlspecialchars($featuredCompanyLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($featuredCompanyName, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" />
                  <span><?= htmlspecialchars(truncateCompanyName($featuredCompanyName), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
            <button class="featured-carousel__arrow" id="featured-company-next" type="button" aria-label="Next featured companies" <?= $featuredHasNext ? '' : 'disabled'; ?>>&rsaquo;</button>
          </div>
        <?php else: ?>
          <div class="featured-carousel">
            <button class="featured-carousel__arrow" type="button" aria-label="Previous featured companies" disabled>&lsaquo;</button>
            <div class="logos"><p class="featured-companies-empty"><?= htmlspecialchars($lang['featured_companies_empty'], ENT_QUOTES, 'UTF-8'); ?></p></div>
            <button class="featured-carousel__arrow" type="button" aria-label="Next featured companies" disabled>&rsaquo;</button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <script>
    (() => {
      const carousel = document.getElementById('featured-company-carousel');
      if (!carousel) return;

      const list = document.getElementById('featured-company-list');
      const previousButton = document.getElementById('featured-company-prev');
      const nextButton = document.getElementById('featured-company-next');
      let offset = 0;
      let hasNext = carousel.dataset.hasNext === '1';
      let isLoading = false;

      function updateButtons() {
        previousButton.disabled = isLoading || offset === 0;
        nextButton.disabled = isLoading || !hasNext;
      }

      function renderCompanies(companies) {
        list.replaceChildren();
        companies.forEach(company => {
          const link = document.createElement('a');
          link.className = 'featured-company';
          link.href = '/companyprofile.php?company_id=' + encodeURIComponent(company.id);
          link.title = company.company_name;

          const logo = document.createElement('img');
          logo.src = company.logo;
          logo.alt = company.company_name;
          logo.loading = 'lazy';

          const name = document.createElement('span');
          name.textContent = company.display_name;

          link.append(logo, name);
          list.appendChild(link);
        });
        list.scrollLeft = 0;
      }

      async function moveCompanies(direction) {
        if (isLoading) return;
        const nextOffset = direction === 'next' ? offset + 6 : Math.max(0, offset - 6);
        if (nextOffset === offset || (direction === 'next' && !hasNext)) return;

        isLoading = true;
        updateButtons();
        try {
          const response = await fetch('/featured_companies.php?offset=' + nextOffset, {
            headers: { Accept: 'application/json' }
          });
          if (!response.ok) throw new Error('Featured companies request failed.');
          const data = await response.json();
          renderCompanies(data.companies);
          offset = nextOffset;
          hasNext = data.has_more;
        } catch (error) {
          console.error(error);
        } finally {
          isLoading = false;
          updateButtons();
        }
      }

      previousButton.addEventListener('click', () => moveCompanies('previous'));
      nextButton.addEventListener('click', () => moveCompanies('next'));
      updateButtons();
    })();
  </script>

  <section class="section-4">
    <div class="container">
      <h3><?= $lang['opportunities_at_top_companies']; ?></h3>
      <p><?= $lang['opportunities_at_top_companies_desc']; ?></p>

      <h3><?= $lang['why_viecnhe']; ?></h3>
      <ul>
        <li>
          <strong><?= $lang['quality_jobs']; ?></strong><br />
          <?= $lang['quality_jobs_desc']; ?>
        </li>

        <li>
          <strong><?= $lang['free_cv_builder']; ?></strong><br />
          <?= $lang['free_cv_builder_desc']; ?>
        </li>

        <li>
          <strong><?= $lang['job_seeker_support']; ?></strong><br />
          <?= $lang['job_seeker_support_desc']; ?>
        </li>
      </ul>

      <p><?= $lang['find_best_salary']; ?></p>
    </div>
  </section>

</div>