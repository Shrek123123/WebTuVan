<?php
require_once '../config.php'; // Đảm bảo config.php được bao gồm
// Check for ?lang=vi or ?lang=en in URL and update session
session_start();
include '../lang/' . $_SESSION['lang'] . '.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load file ngôn ngữ tương ứng
?>
<style>
    @media (max-width: 768px) {
        .dashboard-job-card {
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .dashboard-job-card img {
            width: 100% !important;
            max-width: 100% !important;
            margin-right: 0 !important;
            margin-bottom: 15px !important;
        }

        .dashboard-job-card div:last-child {
            margin-left: 0 !important;
            margin-top: 15px !important;
            align-items: flex-start !important;
            flex-direction: row !important;
            flex-wrap: wrap;
            gap: 10px;
        }
    }
</style>

<div style="padding: 30px; border-radius: 8px;">
    <h2 style="color: #b23b3b; margin-bottom: 20px;"><?= $lang['my_job_post'] ?></h2>
    <div style="display: flex; flex-direction: column; gap: 18px;">
        <?php
        // Get employer id from URL (?id=...)
        // Get employer id from session
        $employer_id = isset($_SESSION['employerid']) ? intval($_SESSION['employerid']) : 0;

        // If no employer id, do not query
        if ($employer_id > 0) {
            // Check if $conn is defined and connected before querying
            if (isset($conn) && $conn->ping()) {
                // Query ALL columns for editing purposes
                $sql = "SELECT * FROM hoa_giai_vien WHERE posted_by_employer_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $employer_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    echo '<div style="color: #888; text-align: center; padding: 20px;">No job posts found.</div>';
                }

                while ($row = $result->fetch_assoc()):
                    $job_id = htmlspecialchars($row['id']); // Get job ID
                    $logo = !empty($row['company_logo']) ? htmlspecialchars($row['company_logo']) : 'https://placehold.co/120x70/cccccc/333333?text=No+Logo';
                    $created_at = new DateTime($row['created_at']);
                    $duration = intval($row['post_duration']);
                    $expire_at = clone $created_at;
                    $expire_at->modify("+$duration days");
                    $now = new DateTime();
                    $days_left = $now < $expire_at ? $now->diff($expire_at)->days : 0;
                    $salary = htmlspecialchars($row['salary']);
                    $company_name = htmlspecialchars($row['company_name']);
                    $job_title = htmlspecialchars($row['job_title']);
                    $job_location = htmlspecialchars($row['job_location']);
                    $job_status = htmlspecialchars($row['status']); // Get the job status
                    // Use created_at if last_updated_at is empty or null
                    $last_updated = strtotime($row['last_updated_at']);
                    $now = time();
                    $diff = $now - $last_updated;
                    if ($diff < 60) {
                        $updated_str = sprintf($lang['updated_now'], $diff);
                    } elseif ($diff < 3600) {
                        $minutes = floor($diff / 60);
                        $updated_str = sprintf($lang['updated_minutes'], $minutes);
                    } elseif ($diff < 86400) {
                        $hours = floor($diff / 3600);
                        $updated_str = sprintf($lang['updated_hours'], $hours);
                    } else {
                        $days = floor($diff / 86400);
                        $updated_str = sprintf($lang['updated_days'], $days);
                    }
                    // $updated_at_str = !empty($row['last_updated_at']) ? $row['last_updated_at'] : $row['created_at'];
                    // $updated_at = new DateTime($updated_at_str);
                    // $updated = $updated_at->diff($now);
                    // if ($updated->days > 0) {
                    //     $updated_str = "Updated {$updated->days} days ago";
                    // } elseif ($updated->h > 0) {
                    //     $updated_str = "Updated {$updated->h} hours ago";
                    // } elseif ($updated->i > 0) {
                    //     $updated_str = "Updated {$updated->i} minutes ago";
                    // } else {
                    //     $updated_str = "Updated just now";
                    // }

                    // Determine status color for display
                    $job_status_raw = htmlspecialchars($row['status']); // Trạng thái gốc từ DB
                    $status_color = '#6c757d'; // Default màu xám
        
                    // Mảng ánh xạ status trong DB sang key trong file ngôn ngữ
                    $status_lang_keys = [
                        'pending_review' => 'pending_review',
                        'approved' => 'approved',
                        'rejected' => 'rejected',
                    ];

                    // Đặt màu dựa trên status gốc
                    switch ($job_status_raw) {
                        case 'pending_review':
                            $status_color = '#ffc107'; // Vàng
                            break;
                        case 'approved':
                            $status_color = '#28a745'; // Xanh lá
                            break;
                        case 'rejected':
                            $status_color = '#dc3545'; // Đỏ
                            break;
                    }
                    // Lấy bản dịch từ file ngôn ngữ
                    $job_status = isset($status_lang_keys[$job_status_raw]) ? $lang[$status_lang_keys[$job_status_raw]] : $job_status_raw;
                    ?>
                    <div class="dashboard-job-card"
                        style="background: #fff; border: 1px solid black; border-radius: 10px; padding: 18px 18px 18px 0; display: flex; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <img src="<?php echo $logo; ?>" alt="job"
                            style="width: 120px; height: 70px; border-radius: 8px; object-fit: cover; margin-right: 18px;">
                        <div style="flex: 1;">
                            <div style="font-weight: bold; font-size: 18px;">
                                <a href="jobdetail.php?id=<?php echo $job_id; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo $job_title; ?>
                                </a>
                            </div>
                            <div style="color: #888; font-size: 13px; margin-bottom: 6px;"><?php echo $company_name; ?></div>
                            <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                                <span
                                    style="background: #eee; color: #b23b3b; border-radius: 6px; padding: 2px 10px; font-size: 12px;"><?php echo htmlspecialchars($job_location); ?></span>
                                <span
                                    style="background: #eee; color: #b23b3b; border-radius: 6px; padding: 2px 10px; font-size: 12px;">
                                    <?php echo $days_left > 0 ? sprintf($lang['job_days_left'], $days_left) : $lang['job_closed']; ?>
                                </span>
                                <span
                                    style="background: #eee; color: #888; border-radius: 6px; padding: 2px 10px; font-size: 12px;"><?php echo $updated_str; ?></span>
                            </div>
                            <div style="color: #b23b3b; font-weight: bold; font-size: 15px; margin-bottom: 5px;">
                                <?php echo $salary; ?>
                            </div>
                            <div style="font-size: 14px;">
                                <?= $lang['status'] ?>: <span
                                    style="font-weight: bold; color: <?php echo $status_color; ?>;"><?php echo $job_status; ?></span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-left: 18px; align-items: flex-end;">
                            <button class="edit-job-button" data-jobid="<?php echo $job_id; ?>"
                                style="background: #d90000; color: #fff; border: none; border-radius: 6px; padding: 6px 18px; font-size: 13px; cursor: pointer;"><?= $lang['edit'] ?></button>
                            <button class="delete-job-button" data-jobid="<?php echo $job_id; ?>"
                                style="background: #fff; color: #d90000; border: 1px solid #d90000; border-radius: 6px; padding: 6px 18px; font-size: 13px; cursor: pointer;"><?= $lang['delete'] ?></button>
                            <button id="btn-jobpostsapplications" data-jobid="<?php echo $job_id; ?>"
                                style="background: #b23b3b; color: #fff; border: none; border-radius: 6px; padding: 6px 18px; font-size: 13px; cursor: pointer;"><?= $lang['check_submitted_applications'] ?></button>
                        </div>
                    </div>
                <?php endwhile;
            } else {
                echo '<div style="color: #888; text-align: center; padding: 20px;">Database connection error or no employer ID.</div>';
            }
        } else {
            echo '<div style="color: #888; text-align: center; padding: 20px;">No employer selected.</div>';
        }
        // Không đóng kết nối $conn ở đây nếu nó được dùng lại trong body.php
        // $conn->close();
        ?>
    </div>
</div>

<script>
    // Event delegation for "Edit" button inside jobposts-content.php
    $(document).off('click', '.edit-job-button').on('click', '.edit-job-button', function () {
        var jobId = $(this).data('jobid');
        if (jobId) {
            // 1. Clear the current content-area (modal is already removed as it's part of content-area in this setup)
            $('.content-area').empty().html('<div style="padding:30px;text-align:center;">Loading job edit form...</div>');

            // 2. Load the jobposts_edit-modal.php into content-area
            $.ajax({
                url: 'employerprofile/jobposts_edit-modal.php',
                type: 'GET',
                data: { job_id: jobId }, // Pass job ID to the edit form
                success: function (response) {
                    $('.content-area').html(response);
                },
                error: function () {
                    $('.content-area').html('<div style="color: red; text-align: center; padding: 20px;">Error loading job edit form.</div>');
                }
            });
        } else {
            console.error("Job ID not found for edit button.");
        }
    });

    // --- Bổ sung đoạn code xử lý nút Delete ---
    $(document).off('click', '.delete-job-button').on('click', '.delete-job-button', function () {
        var jobId = $(this).data('jobid');
        var employerId = <?php echo $employer_id; ?>; // Lấy employer_id từ PHP

        var confirmDeleteMsg = <?php echo json_encode($lang['confirm-deletejobpost']); ?>;
        if (confirm(confirmDeleteMsg)) {
            if (jobId && employerId) {
                $.ajax({
                    url: 'employerprofile/delete_job_post.php', // Đường dẫn tới file PHP xử lý xóa
                    type: 'POST',
                    dataType: 'json', // Mong đợi phản hồi JSON
                    data: { job_id: jobId, employer_id: employerId },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                            // Tải lại nội dung trang jobposts-content.php sau khi xóa thành công
                            // Để cập nhật danh sách bài đăng
                            $('.content-area').load('employerprofile/jobposts-content.php?id=' + employerId);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        // Xử lý lỗi AJAX (ví dụ: PHP trả về không phải JSON)
                        console.error("AJAX Error: ", status, error);
                        console.log("Response Text: ", xhr.responseText);
                        alert('An error occurred while deleting the job post. Please check the console for details.');
                    }
                });
            } else {
                alert("Cannot delete job. Job ID or Employer ID is missing.");
            }
        }
    });
    // --- Kết thúc bổ sung ---
</script>