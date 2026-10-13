<!-- Xử lý tương tác với cơ sở dữ liệu -->

<!-- /app/models/Job.php -->
<?php
class Job {
    public static function searchJobs($keyword, $job_id, $location, $company_name, $minSalary, $maxSalary, $sort_by, $category, $experience, $job_type, $remote, $industry) {
    global $conn;
    $sql = "SELECT * FROM job WHERE 1";

    if ($keyword) {
        $sql .= " AND (title LIKE '%$keyword%' OR description LIKE '%$keyword%')";
    }
    if ($job_id) {
        $sql .= " AND id = " . intval($job_id);
    }
    if ($location) {
        $sql .= " AND location LIKE '%$location%'";
    }
    if ($company_name) {
        $sql .= " AND company_name LIKE '%$company_name%'";
    }
    if ($minSalary) {
        $sql .= " AND salary >= " . floatval($minSalary);
    }
    if ($maxSalary) {
        $sql .= " AND salary <= " . floatval($maxSalary);
    }
    if ($category) {
        $sql .= " AND category = '$category'";
    }
    if ($experience) {
        $sql .= " AND experience_level = '$experience'";
    }
    if ($job_type) {
        $sql .= " AND job_type = '$job_type'";
    }
    if ($remote) {
        $sql .= " AND remote = '$remote'";
    }
    if ($industry) {
        $sql .= " AND industry LIKE '%$industry%'";
    }

    // Sort
    if ($sort_by === 'date') {
        $sql .= " ORDER BY created_at DESC";
    } elseif ($sort_by === 'salary') {
        $sql .= " ORDER BY salary DESC";
    }

    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

}
?>
