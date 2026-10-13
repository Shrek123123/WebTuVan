<?php
require_once('../config.php');
session_start();

$isLoggedIn = isset($_SESSION['jobseeker_id']) && $_SESSION['jobseeker_id'] > 0;
$userId = $isLoggedIn ? (int) $_SESSION['jobseeker_id'] : 0;

// Get saved jobs for current user
$savedJobIds = [];
if ($isLoggedIn) {
  $stmt = $conn->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
  if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result_saved_jobs = $stmt->get_result();
    while ($row = $result_saved_jobs->fetch_assoc()) {
      $savedJobIds[] = (string) $row['job_id'];
    }
    $stmt->close();
  }
}

function normalizeCompanyLogoPath($path)
{
  $value = trim((string) $path);
  if ($value === '') {
    return 'image/logo1.png';
  }

  $value = preg_replace('#^(?:\./)+#', '', $value);
  $value = preg_replace('#^(?:\.\./)+#', '', $value);
  $value = preg_replace('#^/+#', '', $value);

  if (stripos($value, 'uploads/companylogo/') === 0) {
    return $value;
  }

  if (stripos($value, 'companylogo/') === 0) {
    return 'uploads/' . $value;
  }

  if (stripos($value, 'uploads/') === 0) {
    return $value;
  }

  if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
    return $value;
  }

  return 'uploads/companylogo/' . basename($value);
}

// Get job category from POST
$category = isset($_POST['category']) ? trim((string) $_POST['category']) : '';
if ($category !== '') {
  $category = strtolower($category);
  $category = preg_replace('/[^a-z0-9_]+/', '_', $category);
  $category = preg_replace('/_+/', '_', $category);
  $category = trim($category, '_');
}
$params = [];
$sql = "SELECT j.id, j.job_title, c.company_logo AS company_logo, c.company_name AS company_name, j.salary, j.job_location, j.created_at, j.post_duration, c.industry AS industry
        FROM job j
        LEFT JOIN company c ON c.id = j.company_id";
if ($category !== '') {
  $sql .= " WHERE LOWER(TRIM(c.industry)) = ?";
  $params[] = $category;
}
$sql .= " ORDER BY j.created_at DESC LIMIT 30";

$stmt = $conn->prepare($sql);
if ($category !== '') {
  $stmt->bind_param("s", $category);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0):
  while ($row = $result->fetch_assoc()):
    $created_at = new DateTime($row['created_at']);
    $post_duration = (int) $row['post_duration'];
    $expire_at = clone $created_at;
    $expire_at->modify("+$post_duration days");
    $now = new DateTime();
    $interval = $now->diff($expire_at);
    $days_left = (int) $interval->format('%r%a');
    $days_left_text = $days_left > 0 ? $days_left . ' days left' : 'Expired';
    $job_id = (int) $row['id'];
    $companyLogo = normalizeCompanyLogoPath($row['company_logo'] ?? 'image/logo1.png');
    $isJobAlreadySaved = in_array((string) $job_id, $savedJobIds);
    $savedClass = $isJobAlreadySaved ? 'active' : '';
    ?>
    <a href="jobdetail.php?id=<?php echo $job_id; ?>" style="text-decoration:none;color:inherit;">
      <div class="job-card">
        <div class="job-header">
          <h3><?php echo htmlspecialchars($row['job_title']); ?></h3>
          <button class="save-btn <?php echo $savedClass; ?>" aria-pressed="<?php echo $isJobAlreadySaved ? 'true' : 'false'; ?>"
            title="Save job" data-job-id="<?php echo $job_id; ?>">
            <svg class="heart-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e74c3c"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path
                d="M12 21s-6.5-5.5-8.5-8.5C1.5 9.5 3.5 6 7 6c1.7 0 3.4 1.1 4.1 2.7C11.6 7.1 13.3 6 15 6c3.5 0 5.5 3.5 3.5 6.5C18.5 15.5 12 21 12 21z"
                fill="<?php echo $isJobAlreadySaved ? '#e74c3c' : '#fff'; ?>" />
            </svg>
          </button>
        </div>
        <div class="job-body">
          <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Company Logo" class="company-logo">
          <div class="job-info">
            <div class="company-name"><?php echo htmlspecialchars($row['company_name']); ?></div>
            <div><span class="icon">💰</span> <?php echo htmlspecialchars($row['salary']); ?></div>
            <div><span class="icon">📍</span> <?php echo htmlspecialchars($row['job_location']); ?></div>
          </div>
        </div>
        <div class="divider"></div>
        <div class="job-footer">
          <div class="deadline"><?php echo $days_left_text; ?></div>
        </div>
      </div>
    </a>
    <?php
  endwhile;
else:
  ?>
  <div>No jobs found.</div>
<?php
endif;
?>