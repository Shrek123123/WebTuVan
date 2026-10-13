<?php
session_start(); // Bắt đầu phiên làm việc nếu chưa được bắt đầu
// jobseekerprofile/myapplications-modal.php

// Đảm bảo đường dẫn đến config.php là đúng
// và config.php phải chứa session_start();
require_once '../config.php';
include '../lang/' . $_SESSION['lang'] . '.php';
// Lấy jobseeker_id trực tiếp từ SESSION
// Cần đảm bảo session_start() đã được gọi và $_SESSION['jobseeker_id'] đã được set
$jobseeker_id = $_SESSION['jobseeker_id'] ?? 0; // Sử dụng toán tử null coalescing để gán 0 nếu không tồn tại
if ($jobseeker_id <= 0) {
    // Thông báo lỗi nếu không tìm thấy ID trong session hoặc ID không hợp lệ
    echo '<div style="padding: 30px; text-align: center; color: #d90000;">Lỗi: Không tìm thấy ID ứng viên trong phiên làm việc. Vui lòng đăng nhập lại.</div>';
    exit;
}
?>

<div style="padding: 30px;">
    <h2 style="color: #b23b3b; margin-bottom: 20px;"><?php echo $lang['my_applications'] ?></h2>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php
        // Kiểm tra kết nối database
        if (isset($conn) && $conn->ping()) {
            $sql = "SELECT
                            a.id AS application_id,
                            a.created_at AS application_created_at,
                            a.status AS application_status,
                            a.fullname,
                            a.email,
                            a.phonenumber,
                            a.cv_file,
                            j.job_title,
                            j.company_name,
                            j.job_location,
                            j.id AS job_id
                        FROM
                            application a
                        JOIN
                            job j ON a.job_id = j.id
                        WHERE
                            a.jobseeker_id = ?
                        ORDER BY
                            a.created_at DESC";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $jobseeker_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    echo '<div style="color: #888; text-align: center; padding: 20px;">' . $lang['no_applications'] . '</div>';
                } else {
                    $counter = 0; // Để đếm số thứ tự ứng tuyển
                    while ($row = $result->fetch_assoc()):
                        $counter++;
                        $app_id = htmlspecialchars($row['application_id']);
                        $application_submitted_datetime = new DateTime($row['application_created_at']);
                        $submitted_time_full = $application_submitted_datetime->format('d/m/Y H:i');
                        $submitted_date_only = $application_submitted_datetime->format('d/m/Y');

                        $job_title = htmlspecialchars($row['job_title']);
                        $company_name = htmlspecialchars($row['company_name']);
                        $job_location = htmlspecialchars($row['job_location']);
                        $job_id = htmlspecialchars($row['job_id']);
                        $app_status = htmlspecialchars($row['application_status']);
                        $fullname = htmlspecialchars($row['fullname']);
                        $email = htmlspecialchars($row['email']);
                        $phonenumber = htmlspecialchars($row['phonenumber']);
                        $cv_file = !empty($row['cv_file']) ? htmlspecialchars($row['cv_file']) : '#';

                        $display_status = '';
                        $status_color = '#6c757d';

                        switch ($app_status) {
                            case 'pending':
                                $display_status = $lang['pending'];
                                $status_color = '#007bff';
                                break;
                            case 'viewed':
                                $display_status = $lang['viewed'];
                                $status_color = '#17a2b8';
                                break;
                            case 'interviewed':
                                $display_status = $lang['interviewed'];
                                $status_color = '#ffc107';
                                break;
                            case 'hired':
                                $display_status = $lang['hired'];
                                $status_color = '#28a745';
                                break;
                            case 'rejected':
                                $display_status = $lang['rejected'];
                                $status_color = '#dc3545';
                                break;
                            default:
                                $display_status = $lang['unknown_status'];
                                $status_color = '#6c757d';
                                break;
                        }
                        ?>
                        <div class="application-card"
                            style="background: #fff; border: 1px solid #eee; border-left: 5px solid #b23b3b; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                            <div style="font-size: 1.1em; font-weight: bold; color: #b23b3b; margin-bottom: 10px;">
                                <?= $lang['don_ung_tuyen'] ?>                <?php echo $counter; ?> (<?= $lang['at'] ?>
                                <?php echo $submitted_time_full; ?>)
                            </div>
                            <div style="margin-bottom: 5px;">
                                <?= $lang['vi_tri'] ?>:
                                <a href="jobdetail.php?id=<?php echo $job_id; ?>"
                                    style="color: #007bff; text-decoration: none; font-weight: bold;">
                                    <?php echo $job_title; ?>                 <?= $lang['tai'] ?>                 <?php echo $job_location; ?> <?= $lang['xem_chi_tiet'] ?>
                                </a>
                            </div>
                            <div style="margin-bottom: 5px;">
                                <?= $lang['cong_ty'] ?>: <strong><?php echo $company_name; ?></strong></div>
                            <div style="margin-bottom: 5px;"><?= $lang['submitted_on'] ?><?php echo $submitted_date_only; ?></div>
                            <div style="margin-bottom: 5px;">
                                <?= $lang['submitted_cv'] ?>
                                <?php if ($cv_file != '#'): ?>
                                    <a href="<?php echo $cv_file; ?>" target="_blank"
                                        style="color: #007bff; text-decoration: none;"><?= $lang['xem_chi_tiet'] ?></a>
                                <?php else: ?>
                                    <span><?= $lang['khong_co'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="margin-bottom: 5px;">
                                <?= $lang['trang_thai_application'] ?><span
                                    style="font-weight: bold; color: <?php echo $status_color; ?>;"><?php echo $display_status; ?></span>
                            </div>
                            <div style="margin-bottom: 5px;"><?= $lang['ten_jobseekerprofile'] ?><?php echo $fullname; ?></div>
                            <div style="margin-bottom: 5px;">✉️ Email: <?php echo $email; ?></div>
                            <div><?= $lang['so_dien_thoai_jobseekerprofile'] ?><?php echo $phonenumber; ?></div>
                        </div>
                        <?php
                    endwhile;
                }
                $stmt->close();
            } else {
                echo '<div style="padding: 30px; text-align: center; color: #d90000;">Lỗi chuẩn bị truy vấn: ' . $conn->error . '</div>';
            }
            $conn->close();
        } else {
            echo '<div style="padding: 30px; text-align: center; color: #d90000;">Lỗi kết nối cơ sở dữ liệu.</div>';
        }
        ?>
    </div>
</div>