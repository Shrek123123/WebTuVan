<?php
require_once '../config.php'; // Đảm bảo config.php được bao gồm
session_start();
include '../lang/' . $_SESSION['lang'] . '.php';

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$job_data = null;

if ($job_id > 0 && isset($conn) && $conn->ping()) {
    $sql = "SELECT * FROM hoa_giai_vien WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $job_data = $result->fetch_assoc();
    }
    $stmt->close();
}
// Không đóng kết nối $conn ở đây nếu nó được dùng lại ở body.php
// $conn->close();
?>

<style>
    /* Basic styling for the form to make it readable */
    .edit-form-container {
        padding: 30px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .edit-form-container h2 {
        color: #b23b3b;
        margin-bottom: 25px;
        text-align: center;
    }

    .edit-form-container label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }

    .edit-form-container input[type="text"],
    .edit-form-container input[type="email"],
    .edit-form-container input[type="number"],
    .edit-form-container textarea,
    .edit-form-container select {
        width: calc(100% - 22px);
        /* Adjust for padding/border */
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
        box-sizing: border-box;
        /* Include padding and border in the element's total width and height */
    }

    .edit-form-container textarea {
        resize: vertical;
        min-height: 80px;
    }

    .edit-form-container button {
        background: #b23b3b;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 12px 25px;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
        margin-top: 20px;
    }

    .edit-form-container button:hover {
        background: #d90000;
    }

    .edit-form-container .form-group {
        margin-bottom: 15px;
    }

    .edit-form-container .error-message {
        color: red;
        margin-bottom: 15px;
        text-align: center;
    }

    .edit-form-container .success-message {
        color: green;
        margin-bottom: 15px;
        text-align: center;
    }

    @media (max-width: 768px) {
        .edit-form-container {
            padding: 15px;
        }
    }
</style>

<div class="edit-form-container">
    <h2><?php echo $lang['edit_job_post_modal_title']; ?></h2>
    <?php if ($job_data): ?>
        <form id="editJobForm" method="POST">
            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_data['id']); ?>">
            <input type="hidden" name="posted_by_employer_id"
                value="<?php echo htmlspecialchars($job_data['posted_by_employer_id']); ?>">

            <div class="form-group">
                <label for="job_title"><?php echo $lang['edit_job_post_modal_job_title']; ?>:</label>
                <input type="text" id="job_title" name="job_title"
                    value="<?php echo htmlspecialchars($job_data['job_title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="company_name"><?php echo $lang['edit_job_post_modal_company_name']; ?>:</label>
                <input type="text" id="company_name" name="company_name"
                    value="<?php echo htmlspecialchars($job_data['company_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="company_size"><?php echo $lang['edit_job_post_modal_company_size']; ?>:</label>
                <input type="text" id="company_size" name="company_size"
                    value="<?php echo htmlspecialchars($job_data['company_size']); ?>">
            </div>

            <div class="form-group">
                <label for="job_description"><?php echo $lang['edit_job_post_modal_job_description']; ?>:</label>
                <textarea id="job_description" name="job_description"
                    required><?php echo htmlspecialchars($job_data['job_description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="job_requirement"><?php echo $lang['edit_job_post_modal_job_requirement']; ?>:</label>
                <textarea id="job_requirement"
                    name="job_requirement"><?php echo htmlspecialchars($job_data['job_requirement']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="job_benefit"><?php echo $lang['edit_job_post_modal_job_benefit']; ?>:</label>
                <textarea id="job_benefit"
                    name="job_benefit"><?php echo htmlspecialchars($job_data['job_benefit']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="job_location"><?php echo $lang['edit_job_post_modal_job_location']; ?>:</label>
                <input type="text" id="job_location" name="job_location"
                    value="<?php echo htmlspecialchars($job_data['job_location']); ?>" required>
            </div>

            <div class="form-group">
                <label for="no_employee_needed"><?php echo $lang['edit_job_post_modal_no_employee_needed']; ?>:</label>
                <input type="number" id="no_employee_needed" name="no_employee_needed"
                    value="<?php echo htmlspecialchars($job_data['no_employee_needed']); ?>" required min="1">
            </div>

            <div class="form-group">
                <label for="salary"><?php echo $lang['edit_job_post_modal_salary']; ?>:</label>
                <input type="text" id="salary" name="salary" value="<?php echo htmlspecialchars($job_data['salary']); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="post_duration"><?php echo $lang['edit_job_post_modal_post_duration']; ?>:</label>
                <input type="number" id="post_duration" name="post_duration"
                    value="<?php echo htmlspecialchars($job_data['post_duration']); ?>" required min="1">
            </div>

            <div class="form-group">
                <label for="contact_email"><?php echo $lang['edit_job_post_modal_contact_email']; ?>:</label>
                <input type="email" id="contact_email" name="contact_email"
                    value="<?php echo htmlspecialchars($job_data['contact_email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="contact_phone"><?php echo $lang['edit_job_post_modal_contact_phone']; ?>:</label>
                <input type="text" id="contact_phone" name="contact_phone"
                    value="<?php echo htmlspecialchars($job_data['contact_phone']); ?>">
            </div>

            <div class="form-group">
                <label for="job_type"><?php echo $lang['edit_job_post_modal_job_type']; ?>:</label>
                <select id="job_type" name="job_type" required>
                    <option value="Full-time" <?php echo ($job_data['job_type'] == 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                    <option value="Part-time" <?php echo ($job_data['job_type'] == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                    <option value="Contract" <?php echo ($job_data['job_type'] == 'Freelance') ? 'selected' : ''; ?>>Freelance</option>
                    <option value="Temporary" <?php echo ($job_data['job_type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                    <option value="Internship" <?php echo ($job_data['job_type'] == 'Remote') ? 'selected' : ''; ?>>Remote</option>
                </select>
            </div>

            <div class="form-group">
                <label for="job_category"><?php echo $lang['edit_job_post_modal_job_category']; ?>:</label>
                <input type="text" id="job_category" name="job_category"
                    value="<?php echo htmlspecialchars($job_data['job_category']); ?>" required>
            </div>

            <div class="form-group">
                <label
                    for="required_certification"><?php echo $lang['edit_job_post_modal_required_certification']; ?>:</label>
                <input type="text" id="required_certification" name="required_certification"
                    value="<?php echo htmlspecialchars($job_data['required_certification']); ?>">
            </div>

            <div class="form-group">
                <label for="job_experience"><?php echo $lang['edit_job_post_modal_job_experience']; ?>:</label>
                <input type="text" id="job_experience" name="job_experience"
                    value="<?php echo htmlspecialchars($job_data['job_experience']); ?>">
            </div>

            <div class="form-group">
                <label for="company_logo"><?php echo $lang['edit_job_post_modal_company_logo']; ?>:</label>
                <input type="text" id="company_logo" name="company_logo"
                    value="<?php echo htmlspecialchars($job_data['company_logo']); ?>">
            </div>

            <button type="submit"><?php echo $lang['edit_job_post_modal_submit']; ?></button>
            <div id="form-messages" style="margin-top: 15px;"></div>
        </form>
    <?php else: ?>
        <p class="error-message"><?php echo $lang['edit_job_post_modal_error']; ?></p>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        // Handle form submission
        $('#editJobForm').on('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            var formData = $(this).serialize(); // Get all form data

            $.ajax({
                url: 'employerprofile/update_job_post.php', // This file will handle the update
                type: 'POST',
                data: formData,
                dataType: 'json', // Expect JSON response from server
                beforeSend: function () {
                    $('#form-messages').html('<div style="color: blue;">Updating job...</div>');
                },
                success: function (response) {
                    if (response.success) {
                        $('#form-messages').html('<div class="success-message">' + response.message + '</div>');
                        // Optional: Reload the job posts list after successful update
                        // You might want to delay this or add a button to go back
                        setTimeout(function () {
                            // Simulate click on "My job posts" button to reload the list
                            $('#btn-jobposts').trigger('click');
                        }, 1500);
                    } else {
                        $('#form-messages').html('<div class="error-message">' + response.message + '</div>');
                    }
                },
                error: function (xhr, status, error) {
                    $('#form-messages').html('<div class="error-message">An error occurred: ' + error + '</div>');
                    console.error("AJAX Error:", status, error, xhr.responseText);
                }
            });
        });
    });
</script>