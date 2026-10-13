<!-- Các xử lý logic liên quan đến công việc -->
<!-- /app/controllers/JobController.php -->
<?php
include_once(__DIR__ . '/../models/Job.php');

class JobController {
    // Hiển thị form tìm kiếm bằng cách gọi đến file seach.php trong view
    public function search() {
    include $_SERVER['DOCUMENT_ROOT'] . '/ViecNhe/app/views/job/search.php'; // Đường dẫn tuyệt đối
    }

    // Xử lý tìm kiếm và hiển thị kết quả
    public function searchResults() {
    $keyword = $_GET['keyword'] ?? '';
    $job_id = $_GET['job_id'] ?? '';
    $location = $_GET['location'] ?? '';
    $company_name = $_GET['company_name'] ?? '';
    $minSalary = $_GET['minSalary'] ?? '';
    $maxSalary = $_GET['maxSalary'] ?? '';

    $sort_by = $_GET['sort_by'] ?? '';
    $category = $_GET['category'] ?? '';
    $experience = $_GET['experience'] ?? '';
    $job_type = $_GET['job_type'] ?? '';
    $remote = $_GET['remote'] ?? '';
    $industry = $_GET['industry'] ?? '';

    // Gọi model để lấy kết quả tìm kiếm
    $jobs = Job::searchJobs($keyword, $job_id, $location, $company_name, $minSalary, $maxSalary, $sort_by, $category, $experience, $job_type, $remote, $industry);

    include __DIR__ . '/../views/job/search_results.php';
}

}
?>
