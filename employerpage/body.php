<?php
require_once 'config.php';
include 'helpers.php';
if (!isset($_SESSION['usernameemployer'])) {
  echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
      var openBtn = document.getElementById('openModalBtn');
      if (openBtn) {
        openBtn.onclick = function() {
          window.location.href = 'employerlogin.php';
        }
      }
    });
  </script>";
}

$hasFree30DayPost = false;
if (!empty($_SESSION['employerid'])) {
  $freePostEmployerId = (int) $_SESSION['employerid'];
  $freePostCheckStmt = $conn->prepare("SELECT 1 FROM job WHERE posted_by_employer_id = ? AND post_duration = 30 LIMIT 1");
  if ($freePostCheckStmt) {
    $freePostCheckStmt->bind_param("i", $freePostEmployerId);
    $freePostCheckStmt->execute();
    $hasFree30DayPost = (bool) $freePostCheckStmt->get_result()->fetch_assoc();
    $freePostCheckStmt->close();
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $companyId = (int)($_POST['company_id'] ?? 0);
  $jobRequirementText = $_POST['jobRequirementText'];
  $jobBenefit = $_POST['jobBenefit'];
  $jobTitle = $_POST['jobTitle'];
  $jobDescription = $_POST['jobDescription'];
  $jobLocation = $_POST['jobLocation'];
  $salaryType = $_POST['salary_type'] ?? '';
  $salaryMin = preg_replace('/[^0-9.]/', '', (string)($_POST['salary_min'] ?? ''));
  $salaryMax = preg_replace('/[^0-9.]/', '', (string)($_POST['salary_max'] ?? ''));
  $salaryAmount = preg_replace('/[^0-9.]/', '', (string)($_POST['salary_amount'] ?? ''));
  $salary = $salaryType === 'negotiate' ? 'thoa_thuan' : ($salaryType === 'range' ? $salaryMin . '-' . $salaryMax . ' triệu' : $salaryAmount . ' triệu');
  $contactEmail = $_POST['contactEmail'];
  $contactPhone = $_POST['contactPhone'];
  $jobType = $_POST['jobType'];
  $requiredcertification = $_POST['requiredcertification'];
  $jobExperience = $_POST['jobExperience'];
  $jobPosition = $_POST['jobPosition'] ?? '';
  $noEmplpoyeeNeeeded = $_POST['noEmplpoyeeNeeded'] ?? 0;

  $jobDetailedLocation = $_POST['jobDetailedLocation'] ?? '';
  $jobLocationDistrict = $_POST['jobLocationDistrict'] ?? '';
  if (!isset($_SESSION['employerid'])) {
    echo "<script>alert('You must be logged in as an employer to post a job.');</script>";
    exit;
  }
  $employerid = (int) $_SESSION['employerid'];

  if ($hasFree30DayPost) {
    echo '<script>alert(' . json_encode($lang['free_30_day_post_already_used'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . '); window.location.href = window.location.pathname;</script>';
    exit;
  }

  if ($companyId <= 0) {
    echo "<script>alert('Please select a company before posting a job.'); window.history.back();</script>";
    exit;
  }

  $companyCheckStmt = $conn->prepare("SELECT id FROM company WHERE id = ? AND employer_id = ? LIMIT 1");
  if (!$companyCheckStmt) {
    echo "<script>alert('Error verifying company selection: {$conn->error}');</script>";
    exit;
  }
  $companyCheckStmt->bind_param("ii", $companyId, $employerid);
  $companyCheckStmt->execute();
  $companyCheckResult = $companyCheckStmt->get_result();
  if ($companyCheckResult->num_rows === 0) {
    $companyCheckStmt->close();
    echo "<script>alert('Selected company is invalid for this employer.'); window.history.back();</script>";
    exit;
  }
  $companyCheckStmt->close();

  // Remove company logo upload handling for this simplified job-post flow.

  // Validate required fields
  // $requiredFields = [
  //   'companyName' => $companyName,
  //   'jobTitle' => $jobTitle,
  //   'jobDescription' => $jobDescription,
  //   'jobLocation' => $jobLocation,
  //   'salary' => $salary,
  //   'contactEmail' => $contactEmail,
  //   'contactPhone' => $contactPhone,
  //   'jobType' => $jobType,
  //   'jobCategory' => $jobCategory,
  //   'requiredcertification' => $requiredcertification,
  //   'jobExperience' => $jobExperience,
  //   'post_duration' => $post_duration,
  //   'jobRequirementText' => $jobRequirementText,
  //   'jobBenefit' => $jobBenefit,
  //   'companySize' => $companySize,
  //   'jobPosition' => $jobPosition,
  //   'noEmplpoyeeNeeded' => $noEmplpoyeeNeeeded,
  //   'jobDetailedLocation' => $jobDetailedLocation,
  //   'jobLocationDistrict' => $jobLocationDistrict
  // ];

  // foreach ($requiredFields as $field => $value) {
  //   if (empty($value)) {
  //     echo "<script>alert('Please fill out all required information.');</script>";
  //   }
  // }
  $jobStatus = 'pending_review'; // Mặc định là chờ duyệt
  $jobStatus = $hasFree30DayPost ? 'pending_review' : 'approved';

  // Database connection and insertion logic here
  // Lấy số thứ tự bài đăng mới nhất của employer này
  $sql = "SELECT MAX(job_post_number_by_employer) AS last_number FROM job WHERE posted_by_employer_id = ?";
  $stmt_number = $conn->prepare($sql);
  if (!$stmt_number) {
    echo "<script>alert('Error preparing statement for job number: {$conn->error}');</script>";
    exit;
  }
  $stmt_number->bind_param("i", $employerid);
  $stmt_number->execute();
  $result = $stmt_number->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $last_number = $row && isset($row['last_number']) ? (int)$row['last_number'] : 0;
  $stmt_number->close();

  $new_job_number = $last_number + 1;

  $stmt = $conn->prepare("INSERT INTO job (
    posted_by_employer_id, company_id, job_post_number_by_employer, job_title, job_description, job_location, salary, contact_email, contact_phone, job_type, required_certification, job_experience, job_requirement, job_benefit, no_employee_needed, job_position, job_detailed_location, job_location_district, post_duration, status
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

  if (!$stmt) {
    echo "<script>alert('Error preparing statement: {$conn->error}');</script>";
    exit;
  }

  $postDuration = 30;
  $types = "ii" . "i" . str_repeat("s", 11) . "i" . str_repeat("s", 3) . "i" . "s";
  $stmt->bind_param(
    $types,
    $employerid,
    $companyId,
    $new_job_number,
    $jobTitle,
    $jobDescription,
    $jobLocation,
    $salary,
    $contactEmail,
    $contactPhone,
    $jobType,
    $requiredcertification,
    $jobExperience,
    $jobRequirementText,
    $jobBenefit,
    $noEmplpoyeeNeeeded,
    $jobPosition,
    $jobDetailedLocation,
    $jobLocationDistrict,
    $postDuration,
    $jobStatus
  );

  //   if ($stmt->execute()) {
//     echo "<script>alert('Job posted successfully!');</script>";
//   } else {
//     echo "<script>alert('Error posting job: {$stmt->error}');</script>";
//   }
//   $stmt->close();
// }
  if ($stmt->execute()) {
    $jobPostMessage = $jobStatus === 'approved'
      ? $lang['job_post_published_successfully']
      : $lang['job_post_is_saved_for_review'];
    echo '<script>alert(' . json_encode($jobPostMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . '); window.location.href = window.location.href;</script>';
    // window.location.href = window.location.href; sẽ tải lại trang hiện tại (body.php)
    // và đóng modal, làm mới trạng thái
  } else {
    echo "<script>alert('Error posting job: {$stmt->error}');</script>";
  }
  $stmt->close();
  // Đảm bảo kết nối được đóng sau khi xử lý POST
  $conn->close(); // Nếu bạn không đóng ở config.php
  exit; // Quan trọng: dừng script sau khi xử lý POST
}
?>
<style>
  .hero {
    position: relative;
    color: white;
    background: url('https://via.placeholder.com/1500x700') no-repeat center center/cover;
    height: 90vh;
    display: flex;
    align-items: center;
    padding: 40px;
  }

  .hero-overlay {
    background: rgba(0, 0, 0, 0.5);
    padding: 40px;
    max-width: 700px;
    border-radius: 12px;
  }

  .hero h1 {
    font-size: 48px;
    color: #ff0000;
    margin-bottom: 20px;
  }

  .hero ul {
    list-style: disc;
    padding-left: 20px;
    color: white;
    font-size: 18px;
    margin-bottom: 30px;
  }

  .hero-buttons {
    display: flex;
    gap: 20px;
  }

  .hero-buttons button {
    padding: 12px 20px;
    font-size: 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
  }

  .consult-btn {
    background: white;
    color: #d40000;
    border: 2px solid #d40000;
  }

  .post-now-btn {
    background: #d40000;
    color: white;
  }

  .modal {
    display: none;
    position: fixed;
    z-index: 2100;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow-y: auto;
  }

  .modal-content {
    background-color: #fff;
    margin: 40px auto 40px auto;
    /* Cách đều trên/dưới */
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    text-align: center;
    position: relative;
    top: 0;
  }

  .closeBtn {
    float: right;
    font-size: 24px;
    cursor: pointer;
  }

  @media (max-width: 768px) {
    #myModal {
      z-index: 2100;
      padding: 16px;
      box-sizing: border-box;
    }

    #myModal[style*="display: block"] {
      display: flex !important;
      align-items: center;
      justify-content: center;
    }

    #myModal .modal-content {
      width: 100%;
      max-width: 520px;
      max-height: calc(100vh - 32px);
      margin: 0 auto;
      padding: 20px 16px;
      box-sizing: border-box;
      overflow-y: auto;
    }

    .hero {
      height: auto;
      padding: 20px;
      justify-content: center;
    }

    .hero-overlay {
      padding: 20px;
      text-align: center;
    }

    .hero h1 {
      font-size: 28px;
    }

    .hero ul {
      font-size: 16px;
      text-align: left;
    }

    .hero-buttons {
      flex-direction: column;
      gap: 10px;
    }

    .hero-buttons button {
      width: 100%;
    }

    .form-table,
    .form-table tr,
    .form-table td {
      display: block;
      width: 100%;
    }

    .form-table td {
      padding: 5px 0;
      text-align: center;
    }

    .form-table label {
      margin-bottom: 5px;
      display: block;
      white-space: normal;
      text-align: center;
    }

    .form-table input,
    .form-table select,
    .form-table textarea {
      display: block;
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
      box-sizing: border-box;
    }
  }
</style>
<section class="hero" style="background: url('image/employerpagebackground.png') no-repeat center center/cover;">
  <div class="hero-overlay">
    <h1><?php echo $lang['hero_section']; ?></h1>
    <ul>
      <li><?php echo $lang['hero_section_desc']['post_jobs_free']; ?></li>
      <li><?php echo $lang['hero_section_desc']['candidate_pool']; ?></li>
    </ul>
    <div class="hero-buttons">
      <button class="consult-btn" id="add_prominent_company_btn" type="button"><?php echo $lang['add_prominent_company_btn']; ?></button>
      <button class="post-now-btn" id="openModalBtn"><?php echo $lang['post_job_now']; ?></button>
    </div>
  </div>
</section>

<?php
$companyOptions = [];
if (isset($_SESSION['employerid'])) {
  $currentEmployerId = (int) $_SESSION['employerid'];
  $companyOptionsStmt = $conn->prepare("SELECT id, company_name FROM company WHERE employer_id = ? ORDER BY company_name ASC");
  if ($companyOptionsStmt) {
    $companyOptionsStmt->bind_param("i", $currentEmployerId);
    $companyOptionsStmt->execute();
    $companyOptionsResult = $companyOptionsStmt->get_result();
    while ($companyRow = $companyOptionsResult->fetch_assoc()) {
      $companyOptions[] = $companyRow;
    }
    $companyOptionsStmt->close();
  }
}
?>

<?php $_SESSION['csrf_prominent_company'] = bin2hex(random_bytes(32)); ?>
<div id="prominentCompanyModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="prominentCompanyTitle">
  <div class="modal-content">
    <span class="prominent-close-btn" data-close-prominent-modal role="button" tabindex="0" aria-label="Close">&times;</span>
    <h2 id="prominentCompanyTitle"><?= htmlspecialchars($lang['prominent_company_payment_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
    <p><?= htmlspecialchars($lang['prominent_company_confirmation'], ENT_QUOTES, 'UTF-8'); ?></p>
    <form method="POST" action="employerpage.php">
      <input type="hidden" name="employerpage_action" value="prominent_company_payment">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_prominent_company'], ENT_QUOTES, 'UTF-8'); ?>">
      <label for="prominent_company_id"><?= htmlspecialchars($lang['prominent_company_choose'], ENT_QUOTES, 'UTF-8'); ?></label>
      <select id="prominent_company_id" name="company_id" required>
        <option value=""><?= htmlspecialchars($lang['prominent_company_choose'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php foreach ($companyOptions as $company): ?>
          <option value="<?= (int) $company['id']; ?>"><?= htmlspecialchars((string) $company['company_name'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
      <div class="prominent-company-actions">
        <button type="button" data-close-prominent-modal><?= htmlspecialchars($lang['prominent_company_cancel'], ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="submit"><?= htmlspecialchars($lang['prominent_company_confirm'], ENT_QUOTES, 'UTF-8'); ?></button>
      </div>
    </form>
  </div>
</div>

<div id="myModal" class="modal">
  <div class="modal-content">
    <span class="closeBtn">&times;</span>
    <h2><?= $lang['post_job_form_title']; ?></h2>
    <h4 style="color: grey; margin-bottom: 20px;"><?= $lang['post_job_form_subtitle']; ?></h4>
    <form method="POST">
      <table class="form-table">
        <tr>
          <td><label for="company_id"><?= $lang['choose_company']; ?>:</label></td>
          <td>
            <select id="company_id" name="company_id" required>
              <option value=""><?= $lang['choose_company']; ?></option>
              <?php foreach ($companyOptions as $company): ?>
                <option value="<?= (int) $company['id']; ?>"><?= htmlspecialchars((string) $company['company_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="jobTitle"><?= $lang['post_job_form_job_title']; ?>:</label></td>
          <td><input type="text" id="jobTitle" name="jobTitle" placeholder="<?= $lang['job_title_placeholder']; ?>"
              required></td>
        </tr>
        <tr>
          <td><label for="jobDescription"><?= $lang['post_job_form_job_description']; ?>:</label></td>
          <td><textarea id="jobDescription" name="jobDescription"
              placeholder="<?= $lang['job_description_placeholder']; ?>" required></textarea>
          </td>
        </tr>
        <tr>
          <td><label for="jobPosition"><?= $lang['post_job_form_job_position']; ?>:</label></td>
          <td>
            <select id="jobPosition" name="jobPosition" required>
              <option value=""><?= $lang['post_job_form_job_position']; ?></option>
              <?php foreach (['senior', 'junior', 'cong_tac_vien', 'manager', 'director', 'lead', 'staff', 'specialist', 'assistant', 'executive', 'other'] as $positionValue): ?>
                <option value="<?= htmlspecialchars($positionValue) ?>"><?= htmlspecialchars(translateJobPosition($positionValue, $lang)) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="jobLocation"><?= $lang['post_job_form_job_location']; ?>:</label></td>
          <td><input type="text" id="jobLocation" name="jobLocation" placeholder="<?= $lang['job_city_placeholder']; ?>"
              required></td>
        </tr>
        <tr>
          <td><label for="salary_type"><?= $lang['salary_label']; ?>:</label></td>
          <td>
            <select id="salary_type" name="salary_type" class="salary-type" onchange="toggleSalaryFields(this)" required>
              <option value="" selected disabled><?= $lang['salary_select']; ?></option>
              <option value="range"><?= $lang['salary_range']; ?></option>
              <option value="fixed"><?= $lang['salary_fixed']; ?></option>
              <option value="negotiate"><?= $lang['salary_negotiate']; ?></option>
            </select>
            <div class="salary-controls" data-salary-controls style="display:none;">
              <input type="number" name="salary_min" min="0" step="0.01" placeholder="<?= $lang['salary_min_placeholder']; ?>">
              <span aria-hidden="true">-</span>
              <input type="number" name="salary_max" min="0" step="0.01" placeholder="<?= $lang['salary_max_placeholder']; ?>">
            </div>
            <div class="salary-controls" data-salary-fixed style="display:none;">
              <input type="number" name="salary_amount" min="0" step="0.01" placeholder="<?= $lang['salary_amount_placeholder']; ?>">
            </div>
          </td>
        </tr>
        <tr>
          <td><label for="employee_gender"><?= $lang['employee_gender_quick_post']; ?></label></td>
          <td>
            <select id="employee_gender" required>
              <option value="Nam"><?= $lang['employee_gender_male']; ?></option>
              <option value="Nữ"><?= $lang['employee_gender_female']; ?></option>
              <option value="Not_required"><?= $lang['employee_gender_not_required']; ?></option>
            </select>
        </tr>
        <tr>
          <td><label for="contactEmail"><?= $lang['post_job_form_contact_email']; ?>:</label></td>
          <td><input type="email" id="contactEmail" name="contactEmail" placeholder="<?= $lang['required']; ?>"
              required></td>
        </tr>
        <tr>
          <td><label for="contactPhone"><?= $lang['post_job_form_contact_phone']; ?>:</label></td>
          <td><input type="text" id="contactPhone" name="contactPhone" placeholder="<?= $lang['required']; ?>" required>
          </td>
        </tr>
        <tr>
          <td><label for="noEmplpoyeeNeeded"><?= $lang['post_job_form_no_employee_needed']; ?>:</label></td>
          <td>
            <input type="number" id="noEmplpoyeeNeeded" name="noEmplpoyeeNeeded" placeholder="<?= $lang['required']; ?>"
              min="0" oninput="this.value = Math.abs(this.value)">
          </td>
        </tr>
        <tr>
          <td><label for="jobDetailedLocation"><?= $lang['post_job_form_job_detailed_location']; ?>:</label></td>
          <td><input type="text" id="jobDetailedLocation" name="jobDetailedLocation"
              placeholder="<?= $lang['job_detailedlocation_placeholder']; ?>"></td>
        </tr>
        <tr>
          <td><label for="jobLocationDistrict"><?= $lang['post_job_form_job_location_district']; ?>:</label></td>
          <td><input type="text" id="jobLocationDistrict" name="jobLocationDistrict"
              placeholder="<?= $lang['optional']; ?>"></td>
        </tr>
        <tr>
          <td><label for="jobType"><?= $lang['post_job_form_job_type']; ?>:</label></td>
          <td>
            <select id="jobType" name="jobType" required>
              <option value="Full-time">Full-time</option>
              <option value="Part-time">Part-time</option>
              <option value="Internship">Internship</option>
              <option value="Freelance">Freelance</option>
              <option value="Remote">Remote</option>
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="requiredcertification"><?= $lang['post_job_form_required_certification']; ?>:</label></td>
          <td><input type="text" id="requiredcertification" name="requiredcertification"
              placeholder="<?= $lang['optional']; ?>" required></td>
        </tr>
        <tr>
          <td><label for="jobExperience"><?= $lang['post_job_form_job_experience']; ?>:</label></td>
          <td>
            <select id="jobExperience" name="jobExperience" required>
              <option value=""><?= $lang['post_job_form_experience_placeholder']; ?></option>
              <?php foreach ($lang['post_job_form_experience_options'] as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="jobRequirementText"><?= $lang['post_job_form_job_requirement']; ?>:</label></td>
          <td>
            <textarea id="jobRequirementText" name="jobRequirementText"
              placeholder="<?= $lang['job_requirement_placeholder']; ?>" required></textarea>
          </td>
        </tr>
        <tr>
          <td><label for="jobBenefit"><?= $lang['post_job_form_job_benefit']; ?>:</label></td>
          <td>
            <textarea id="jobBenefit" name="jobBenefit"
              placeholder="<?= $lang['job_benefit_placeholder']; ?>"></textarea>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="text-align:center;">
            <button type="submit"><?= $lang['post_job_form_submit']; ?></button>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<style>
  .form-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }

  .form-table td {
    padding: 6px 8px;
    vertical-align: middle;
  }

  .form-table label {
    font-weight: 500;
    white-space: nowrap;
  }

  .form-table input[type="text"],
  .form-table input[type="email"],
  .form-table input[type="number"],
  .form-table input[type="file"],
  .form-table textarea,
  .form-table select {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
    box-sizing: border-box;
  }

  .form-table textarea {
    resize: vertical;
    min-height: 60px;
  }

  .form-table button[type="submit"] {
    padding: 10px 30px;
    background: #d40000;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
  }

  .salary-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
  }

  .salary-controls input {
    flex: 1;
    min-width: 0;
  }

  .prominent-close-btn {
    cursor: pointer;
  }

  #prominentCompanyModal .modal-content {
    text-align: left;
  }

  #prominentCompanyModal p {
    line-height: 1.6;
  }

  #prominentCompanyModal select {
    width: 100%;
    margin-top: 8px;
    padding: 10px;
  }

  .prominent-company-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
  }

  .prominent-company-actions button {
    padding: 10px 16px;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
  }

  .prominent-company-actions button[type="submit"] {
    background: #d40000;
    color: #fff;
  }

  @media (max-width: 600px) {
    .salary-controls { gap: 5px; }
  }
</style>


<script>
  function toggleSalaryFields(select) {
    const form = select.closest('form');
    const range = form.querySelector('[data-salary-controls]');
    const fixed = form.querySelector('[data-salary-fixed]');
    const isRange = select.value === 'range';
    const isFixed = select.value === 'fixed';
    range.style.display = isRange ? 'flex' : 'none';
    fixed.style.display = isFixed ? 'flex' : 'none';
    range.querySelectorAll('input').forEach(input => input.required = isRange);
    fixed.querySelectorAll('input').forEach(input => input.required = isFixed);
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.salary-type').forEach(toggleSalaryFields);
  });

  const modal = document.getElementById("myModal");
  const openBtn = document.getElementById("openModalBtn");
  const closeBtn = document.querySelector(".closeBtn");
  const prominentCompanyModal = document.getElementById("prominentCompanyModal");
  const prominentCompanyButton = document.getElementById("add_prominent_company_btn");

  prominentCompanyButton.onclick = () => {
    if (!<?= isset($_SESSION['employerid']) ? 'true' : 'false' ?>) {
      window.location.href = 'employerlogin.php?redirect=employerpage.php';
      return;
    }
    prominentCompanyModal.style.display = "block";
  };

  document.querySelectorAll('[data-close-prominent-modal]').forEach(button => {
    button.addEventListener('click', () => prominentCompanyModal.style.display = "none");
    button.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') prominentCompanyModal.style.display = "none";
    });
  });

  openBtn.onclick = () => {
    if (<?= $hasFree30DayPost ? 'true' : 'false' ?>) {
      alert(<?= json_encode($lang['free_30_day_post_already_used'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
      return;
    }
    modal.style.display = "block";
  }

  closeBtn.onclick = () => {
    modal.style.display = "none";
  }

  window.onclick = (event) => {
    if (event.target == modal) {
      modal.style.display = "none";
    }
    if (event.target == prominentCompanyModal) {
      prominentCompanyModal.style.display = "none";
    }
  }

</script>
