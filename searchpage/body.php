<link href="https://fonts.com/css2?family=Roboto&display=swap" rel="stylesheet">
<?php
// Đặt ở ĐẦU file jobs.php (hoặc index.php)
require_once(__DIR__ . '/../config.php'); // Đảm bảo file này chứa kết nối $conn
require_once(__DIR__ . '/../helpers.php');

if (!function_exists('t')) {
    function t($key, $replace = [])
    {
        global $lang;
        $text = $lang[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(":$k", $v, $text);
        }
        return $text;
    }
}

$isLoggedIn = isset($_SESSION['jobseeker_id']) && $_SESSION['jobseeker_id'] > 0;
$userId = $isLoggedIn ? (int) $_SESSION['jobseeker_id'] : 0;

// Helper function for bind_param with dynamic arguments
// Hàm này tạo ra các tham chiếu từ một mảng, cần thiết cho bind_param()
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) // Kiểm tra phiên bản PHP >= 5.3
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key]; // Lấy tham chiếu của mỗi phần tử
        return $refs;
    }
    return $arr; // Đối với PHP < 5.3, không cần thiết phải làm gì đặc biệt
}

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
        ];
    }
}

?>
<style>
    body {
        margin: 0;
        font-family: 'Roboto', sans-serif;
        color: #000;
    }

    .section-1 {
        background: linear-gradient(to right, #c31432, #240b36);
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

    .job-section {
        padding: 40px 20px;
        border-radius: 10px;
        background-color: #eee;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        max-width: 1000px;
        margin: 0 auto;
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

    .sort-by-container {
        max-width: 1000px;
        margin: 0 auto 20px;
        display: flex;
        justify-content: flex-end;
        padding: 0 20px;
        box-sizing: border-box;
    }

    .sort-by-form {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
    }

    .sort-by-form select,
    .sort-by-form button {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 14px;
    }

    .sort-by-form select {
        border: 1px solid #ccc;
        background: #fff;
        cursor: pointer;
    }

    .sort-by-form button {
        border: 0;
        background: #d70018;
        color: #fff;
        cursor: pointer;
    }

    .section-2 {
        background-color: #eee;
        padding: 20px 0;
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

    .job-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        padding: 10px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 100%;
        box-sizing: border-box;
        position: relative;
    }

    .job-section > .no-jobs-message {
        grid-column: 1 / -1;
    }

    .job-card-link {
        display: block;
        color: inherit;
        text-decoration: none;
        border-radius: 8px;
        outline: none;
    }

    .job-card-link:hover .job-header h3,
    .job-card-link:focus-visible .job-header h3 {
        color: #d70018;
    }

    .job-card-link:focus-visible {
        box-shadow: 0 0 0 3px rgba(215, 0, 24, 0.22);
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
        color: #e74c3c;
        cursor: pointer;
        z-index: 10;
    }

    .save-btn:hover,
    .save-btn:focus-visible {
        background: #ffe0e5;
        outline: 2px solid rgba(231, 76, 60, 0.3);
        outline-offset: 2px;
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
        min-width: 0;
        font-size: 14px;
        line-height: 1.45;
    }

    .job-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .job-header h3 {
        font-size: 16px;
        line-height: 1.35;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .job-info .company-name {
        font-weight: bold;
        margin: 5px 0;
        color: #818080;
    }

    .job-info .company-industry {
        margin-top: 4px;
        color: #666;
        font-size: 12px;
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
        overflow-wrap: anywhere;
    }

    .job-meta-divider {
        width: 1px;
        height: 12px;
        background-color: #ccc;
        display: inline-block;
        flex: 0 0 auto;
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
        min-width: 0;
    }

    .job-footer-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 100%;
        padding: 4px 8px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #555;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Media queries: Đảm bảo responsive */
    @media (max-width: 1024px) {
        .job-section,
        .sort-by-container {
            max-width: 90%;
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

        .job-section {
            max-width: 95%;
            padding: 24px 12px;
            grid-template-columns: 1fr;
        }

        .sort-by-container {
            max-width: 95%;
            padding: 0 15px;
            justify-content: center;
        }

        .sort-by-form {
            flex-direction: row;
            align-items: stretch;
        }

        .sort-by-form select,
        .sort-by-form button {
            width: auto;
            min-width: 0;
            flex: 1 1 0;
        }
    }

    @media (max-width: 600px) {
        .job-section {
            max-width: 100%;
            padding: 20px 10px;
            grid-template-columns: 1fr;
        }

        .sort-by-container {
            max-width: 100%;
            padding: 0 10px;
            justify-content: center;
        }

        .company-logo {
            width: 60px;
            height: 60px;
        }

        .job-footer {
            align-items: flex-end;
        }

        .save-btn {
            width: 28px;
            height: 28px;
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
    }

    .job-section {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
        box-sizing: border-box;
    }

    @media (max-width: 768px) {
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
            font-size: 23px;
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
    }
</style>

<section class="section-1">
    <div class="container">
        <div class="title"><?php echo $lang['find_jobs_title']; ?></div>
        <div class="subtitle"><?php echo $lang['find_jobs_subtitle']; ?>
        </div>

        <div class="search-box">
            <form action="" method="get" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <select name="category">
                    <option value=""><?php echo $lang['job_category_select_menu']; ?></option>
                    <?php foreach ($industryFilterOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option['value']) ?>" <?= (isset($_GET['category']) && $_GET['category'] === $option['value']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="q" placeholder="<?php echo $lang['job_title_company_placeholder'] ?? 'Job title, company name'; ?>" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                <input type="text" name="location" placeholder="<?php echo $lang['location_placeholder'] ?? 'Location'; ?>" value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                <input type="hidden" name="job_type" value="<?php echo htmlspecialchars($_GET['job_type'] ?? ''); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort'] ?? 'newest_first'); ?>">
                <button type="submit"><?php echo $lang['search']; ?></button>
            </form>
        </div>
    </div>
</section>

<div class="info">
    <section class="section-2">
        <?php $current_filter_type = $_GET['filter_type'] ?? ''; $current_filter_value = $_GET['filter_value'] ?? ''; ?>
        <div class="sort-by-container">
            <form class="sort-by-form" action="" method="get" id="job-filter-form">
                <select name="filter_type" id="job-filter-type">
                    <option value=""><?php echo htmlspecialchars($lang['filter_type'] ?? 'Filter by'); ?></option>
                    <?php foreach (['salary' => 'filter_salary', 'experience' => 'filter_experience', 'position' => 'filter_position', 'job_type' => 'filter_job_type'] as $type => $key): ?>
                        <option value="<?php echo $type; ?>" <?php echo $current_filter_type === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang[$key] ?? ucfirst($type)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_value" id="job-filter-value"><option value=""><?php echo htmlspecialchars($lang['filter_value'] ?? 'Choose an option'); ?></option></select>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                <input type="hidden" name="location" value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                <input type="hidden" name="job_type" value="<?php echo htmlspecialchars($_GET['job_type'] ?? ''); ?>">
            </form>
        </div>
        <script>
        (function () {
            var type = document.getElementById('job-filter-type'), value = document.getElementById('job-filter-value');
            var options = <?php echo json_encode([
                'salary' => [
                    ['value' => 'salary_lowest', 'label' => $lang['sort_salary_lowest'] ?? 'Salary: Lowest to highest'],
                    ['value' => 'salary_highest', 'label' => $lang['sort_salary_highest'] ?? 'Salary: Highest to lowest'],
                    ['value' => 'thoa_thuan', 'label' => $lang['salary_negotiate'] ?? 'Negotiable']
                ],
                'experience' => array_map(function ($key) use ($lang) { return ['value' => $key, 'label' => translateJobExperience($key, $lang)]; }, array_keys($lang['job_experience_labels'] ?? [])),
                'position' => array_map(function ($key) use ($lang) { return ['value' => $key, 'label' => translateJobPosition($key, $lang)]; }, array_keys($lang['job_position_options'] ?? [])),
                'job_type' => array_map(function ($key) use ($lang) { return ['value' => $key, 'label' => translateJobType($key, $lang)]; }, ['full_time', 'part_time', 'intern', 'freelance', 'remote'])
            ], JSON_UNESCAPED_UNICODE); ?>;
            function refresh() {
                value.innerHTML = '<option value=""><?php echo htmlspecialchars($lang['filter_value'] ?? 'Choose an option', ENT_QUOTES); ?></option>';
                (options[type.value] || []).forEach(function (item) {
                    var option = document.createElement('option'); option.value = item.value; option.textContent = item.label;
                    if (item.value === <?php echo json_encode($current_filter_value); ?>) option.selected = true;
                    value.appendChild(option);
                });
            }
            type.addEventListener('change', function () { refresh(); value.value = ''; });
            value.addEventListener('change', function () { if (value.value) document.getElementById('job-filter-form').submit(); });
            refresh();
        }());
        </script>

        <div class="job-section">
            <?php
            // --- Logic Xử lý Tìm kiếm và Phân trang ---

            $jobs_per_page = 10;
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

            $search_category = $_GET['category'] ?? '';
            $search_query = $_GET['q'] ?? ''; // job title, company name
            $search_location = $_GET['location'] ?? '';
            $search_job_type = strtolower(trim((string) ($_GET['job_type'] ?? '')));
            $sort_order = $_GET['sort'] ?? 'newest_first';
            $filter_type = $_GET['filter_type'] ?? '';
            $filter_value = $_GET['filter_value'] ?? '';

            $job_type_aliases = [
                'full time' => 'full-time',
                'toàn thời gian' => 'full-time',
                'part time' => 'part-time',
                'bán thời gian' => 'part-time',
                'thực tập' => 'internship',
                'thời vụ' => 'freelance',
                'làm việc từ xa' => 'remote',
            ];
            if (isset($job_type_aliases[$search_job_type])) {
                $search_job_type = $job_type_aliases[$search_job_type];
            }

            // Bắt đầu xây dựng câu truy vấn SQL
            $sql_base = "SELECT j.id, j.job_title, c.company_logo AS company_logo,
                c.company_name AS company_name, c.industry AS industry,
                j.salary, j.job_location, j.job_type, j.job_experience, j.job_position,
                j.created_at, j.post_duration
                FROM job j
                LEFT JOIN company c ON c.id = j.company_id
                LEFT JOIN (SELECT company_id, MIN(paid_at) AS featured_at FROM prominent_company WHERE status = 'paid' GROUP BY company_id) pc_featured ON pc_featured.company_id = j.company_id";
            $sql_count_base = "SELECT COUNT(*) as total FROM job j LEFT JOIN company c ON c.id = j.company_id";

            $conditions[] = "j.status = 'approved'";
            // Không hiển thị job card nếu công ty đang chờ duyệt hoặc đã bị từ chối
            $conditions[] = "(c.status IS NULL OR c.status NOT IN ('pending_review', 'rejected'))";
            $params = [];
            $param_types = '';

            // Thêm điều kiện cho industry của công ty
            if (!empty($search_category)) {
                $conditions[] = "LOWER(c.industry) = ?";
                $params[] = strtolower(trim($search_category));
                $param_types .= 's';
            }

            // Thêm điều kiện cho job_title hoặc company_name
            if (!empty($search_query)) {
                $conditions[] = "(j.job_title LIKE ? OR c.company_name LIKE ?)";
                $params[] = '%' . $search_query . '%';
                $params[] = '%' . $search_query . '%';
                $param_types .= 'ss';
            }

            // Thêm điều kiện cho job_location
            if (!empty($search_location)) {
                $conditions[] = "j.job_location LIKE ?";
                $params[] = '%' . $search_location . '%';
                $param_types .= 's';
            }

            if (in_array($search_job_type, ['full-time', 'part-time', 'internship', 'freelance', 'remote'], true)) {
                $conditions[] = "LOWER(j.job_type) = ?";
                $params[] = $search_job_type;
                $param_types .= 's';
            }

            if ($filter_type === 'experience' && $filter_value !== '') {
                $conditions[] = "LOWER(REPLACE(REPLACE(TRIM(j.job_experience), '-', '_'), ' ', '_')) = ?";
                $params[] = strtolower($filter_value);
                $param_types .= 's';
            } elseif ($filter_type === 'position' && $filter_value !== '') {
                $conditions[] = "LOWER(REPLACE(REPLACE(TRIM(j.job_position), '-', '_'), ' ', '_')) = ?";
                $params[] = strtolower($filter_value);
                $param_types .= 's';
            } elseif ($filter_type === 'job_type' && $filter_value !== '') {
                $conditions[] = "LOWER(REPLACE(REPLACE(TRIM(j.job_type), '-', '_'), ' ', '_')) = ?";
                $params[] = strtolower($filter_value);
                $param_types .= 's';
            } elseif ($filter_type === 'salary' && $filter_value === 'thoa_thuan') {
                $conditions[] = "LOWER(TRIM(j.salary)) IN ('thoa_thuan', 'negotiable')";
            }

            // Hoàn thiện mệnh đề WHERE
            $where_clause = '';
            if (!empty($conditions)) {
                $where_clause = " WHERE " . implode(" AND ", $conditions);
            }

            // --- Xử lý sắp xếp ---
            $salary_numeric_first = "CAST(REPLACE(REGEXP_SUBSTR(LOWER(TRIM(j.salary)), '[0-9]+([.,][0-9]+)?'), ',', '.') AS DECIMAL(20,2))";

            if ($filter_type === 'salary' && in_array($filter_value, ['salary_lowest', 'salary_highest'], true)) {
                $sort_order = $filter_value;
            }
            $allowed_sort_orders = ['newest_first', 'oldest_first', 'salary_highest', 'salary_lowest'];
            if (!in_array($sort_order, $allowed_sort_orders, true)) {
                $sort_order = 'newest_first';
            }

            switch ($sort_order) {
                case 'newest_first':
                    $order_by_clause = " ORDER BY pc_featured.featured_at IS NULL ASC, pc_featured.featured_at ASC, j.created_at DESC";
                    break;
                case 'oldest_first':
                    $order_by_clause = " ORDER BY pc_featured.featured_at IS NULL ASC, pc_featured.featured_at ASC, j.created_at ASC";
                    break;
                case 'salary_highest':
                    $order_by_clause = " ORDER BY pc_featured.featured_at IS NULL ASC, pc_featured.featured_at ASC, " . $salary_numeric_first . " DESC, j.created_at DESC";
                    break;
                case 'salary_lowest':
                    $order_by_clause = " ORDER BY pc_featured.featured_at IS NULL ASC, pc_featured.featured_at ASC, " . $salary_numeric_first . " ASC, j.created_at DESC";
                    break;
                default:
                    $order_by_clause = " ORDER BY pc_featured.featured_at IS NULL ASC, pc_featured.featured_at ASC, j.created_at DESC";
                    break;
            }

            // Đếm tổng số jobs phù hợp
            $count_stmt = $conn->prepare($sql_count_base . $where_clause);
            if ($count_stmt) {
                if (!empty($params)) {
                    // Dùng refValues() cho bind_param() của truy vấn đếm
                    $bind_params = array_merge([$param_types], refValues($params));
                    call_user_func_array([$count_stmt, 'bind_param'], refValues($bind_params));
                }
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $total_jobs = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;
                $count_stmt->close();
            } else {
                error_log("Error preparing count query: " . $conn->error);
                $total_jobs = 0; // Đặt về 0 nếu có lỗi
            }

            $total_pages = ceil($total_jobs / $jobs_per_page);
            $offset = ($page - 1) * $jobs_per_page;

            // Lấy dữ liệu jobs phù hợp cho trang hiện tại
            $sql_fetch = $sql_base . $where_clause . $order_by_clause . " LIMIT ? OFFSET ?"; // Thêm ORDER BY
            $fetch_stmt = $conn->prepare($sql_fetch);

            if ($fetch_stmt) {
                // Tạo một bản sao của mảng params và param_types cho truy vấn fetch
                // Vì chúng ta sẽ thêm LIMIT và OFFSET vào
                $fetch_params = $params;
                $fetch_param_types = $param_types;

                // Thêm type cho LIMIT và OFFSET
                $fetch_param_types .= 'ii';
                $fetch_params[] = $jobs_per_page;
                $fetch_params[] = $offset;

                // Dùng refValues() cho bind_param() của truy vấn lấy dữ liệu
                $bind_fetch_params = array_merge([$fetch_param_types], refValues($fetch_params));
                call_user_func_array([$fetch_stmt, 'bind_param'], refValues($bind_fetch_params));

                $fetch_stmt->execute();
                $result = $fetch_stmt->get_result();
                $fetch_stmt->close();
            } else {
                error_log("Error preparing fetch query: " . $conn->error);
                $result = false;
            }
            // --- Kết thúc Logic Xử lý Tìm kiếm và Phân trang ---
            ?>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                    $created_at = new DateTime($row['created_at']);
                    $post_duration = (int) $row['post_duration'];
                    $expire_at = clone $created_at;
                    $expire_at->modify("+$post_duration days");
                    $now = new DateTime();
                    $interval = $now->diff($expire_at);
                    $days_left = (int) $interval->format('%r%a');
                    $days_left_text = $days_left > 0 ? sprintf($lang['days_left'], $days_left) : $lang['expired'];
                    $job_id = (int) $row['id'];
                    $companyName = $row['company_name'] ?? 'ViecNhe';
                    $companyLogo = normalizeCompanyLogoPath($row['company_logo'] ?? 'image/logo1.png');
                    $companyIndustry = translateCompanyIndustry($row['industry'] ?? '', $lang) ?: ($lang['jobdetail_unknown'] ?? 'Unknown');
                    $jobExperienceLabel = translateJobExperience($row['job_experience'] ?? '', $lang);
                    $jobSalaryLabel = translateJobSalary($row['salary'] ?? '', $lang);
                    $isJobAlreadySaved = in_array((string) $job_id, $savedJobIds, true);
                    ?>
                    <div class="job-card" data-job-id="<?php echo $job_id; ?>">
                        <a href="jobdetail.php?id=<?php echo $job_id; ?>" class="job-card-link">
                            <div class="job-body">
                                <img src="<?php echo htmlspecialchars($companyLogo, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Company logo" class="company-logo">
                                <div class="job-info">
                                    <div class="job-header">
                                        <h3><?php echo htmlspecialchars((string) $row['job_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    </div>
                                    <div class="company-name"><?php echo htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if ($companyIndustry !== ''): ?>
                                        <div class="company-industry">
                                            <?php echo htmlspecialchars((string) $companyIndustry, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="job-meta-row">
                                        <div class="job-meta-item">
                                            <?php echo htmlspecialchars((string) $jobSalaryLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <span class="job-meta-divider" aria-hidden="true"></span>
                                        <div class="job-meta-item">
                                            <?php echo htmlspecialchars((string) ($row['job_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <div class="divider"></div>
                        <div class="job-footer">
                            <div class="job-footer-meta">
                                <div class="job-footer-pill">
                                    <?php echo htmlspecialchars(translateJobType($row['job_type'] ?? '', $lang), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php if ($jobExperienceLabel !== ''): ?>
                                    <div class="job-footer-pill">
                                        <?php echo htmlspecialchars((string) $jobExperienceLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="job-footer-pill">
                                    <?php echo htmlspecialchars((string) $days_left_text, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <button type="button" class="save-btn"
                                aria-label="Save job: <?php echo htmlspecialchars((string) $row['job_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                aria-pressed="<?php echo $isJobAlreadySaved ? 'true' : 'false'; ?>"
                                title="Save job" data-job-id="<?php echo $job_id; ?>">
                                <svg class="heart-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="#e74c3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path
                                        d="M12 21s-6.5-5.5-8.5-8.5C1.5 9.5 3.5 6 7 6c1.7 0 3.4 1.1 4.1 2.7C11.6 7.1 13.3 6 15 6c3.5 0 5.5 3.5 3.5 6.5C18.5 15.5 12 21 12 21z"
                                        fill="<?php echo $isJobAlreadySaved ? '#e74c3c' : '#fff'; ?>" />
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-jobs-message" style="text-align: center; padding: 20px; font-size: 16px; color: #555;"><?php echo $lang['searchpage_no_jobs_found']; ?></div>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php
            // Lấy tất cả các tham số GET hiện có để giữ lại chúng khi chuyển trang
            $current_get_params = $_GET;
            unset($current_get_params['page']); // Loại bỏ tham số page hiện tại

            // Chuyển các tham số GET thành chuỗi truy vấn URL
            $query_string = http_build_query($current_get_params);

            // Bổ sung dấu '&' nếu đã có tham số
            if (!empty($query_string)) {
                $query_string .= '&';
            }
            ?>
            <a href="?<?php echo $query_string; ?>page=<?php echo $page > 1 ? ($page - 1) : 1; ?>" class="arrow<?php if ($page <= 1) echo ' disabled'; ?>" <?php if ($page <= 1) echo 'tabindex="-1" aria-disabled="true"'; ?>>
                &larr;
            </a>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?php echo $query_string; ?>page=<?php echo $i; ?>" class="page-number<?php if ($i == $page) echo ' active'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <a href="?<?php echo $query_string; ?>page=<?php echo $page < $total_pages ? ($page + 1) : $total_pages; ?>" class="arrow<?php if ($page >= $total_pages) echo ' disabled'; ?>" <?php if ($page >= $total_pages) echo 'tabindex="-1" aria-disabled="true"'; ?>>
                &rarr;
            </a>
        </div>
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

<script>
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
                e.preventDefault(); // Ngăn chặn hành vi mặc định của button

                // Kiểm tra đăng nhập
                if (!window.isLoggedIn) {
                    showPopup('Please login to save jobs to your account.');
                    return;
                }

                let currentSavedJobs = JSON.parse(localStorage.getItem('savedJobs') || '[]');
                const isCurrentlySaved = btn.getAttribute('aria-pressed') === 'true';
                let action;

                if (!isCurrentlySaved) { // Chưa lưu -> Lưu
                    heartPath.setAttribute('fill', '#e74c3c');
                    btn.setAttribute('aria-pressed', 'true');
                    currentSavedJobs.push(jobId);
                    showPopup(<?php echo json_encode($lang['job_saved_successfully']); ?>);
                    action = 'save';
                } else { // Đã lưu -> Hủy lưu
                    heartPath.setAttribute('fill', '#fff');
                    btn.setAttribute('aria-pressed', 'false');
                    currentSavedJobs = currentSavedJobs.filter(id => id !== jobId);
                    showPopup(<?php echo json_encode($lang['job_unsaved_successfully']); ?>);
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
            }, 1200);
        }
    });
</script>