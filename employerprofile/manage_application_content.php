<?php
require_once '../config.php'; // Đảm bảo config.php được bao gồm

// =========================================================================
// PHẦN XỬ LÝ AJAX POST REQUEST (Cập nhật trạng thái)
// =========================================================================
// Kiểm tra nếu đây là một POST request để cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateApplicationStatus') {
    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';

    // Đảm bảo trạng thái mới hợp lệ (tăng cường bảo mật)
    $allowed_statuses = ['applied', 'viewed', 'interviewed', 'hired', 'rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status provided.']);
        exit; // Rất quan trọng: Dừng việc thực thi script sau khi gửi JSON
    }

    if ($application_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid application ID.']);
        exit; // Dừng nếu ID không hợp lệ
    }

    if (isset($conn) && $conn->ping()) {
        $sql = "UPDATE application SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $application_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Database connection error.']);
    }
    exit; // Dừng việc thực thi script sau khi xử lý POST request
}

// =========================================================================
// PHẦN XỬ LÝ AJAX GET REQUEST (Tải nội dung ban đầu của modal)
// =========================================================================
// Nếu không phải là POST request để cập nhật, thì là GET request để hiển thị nội dung
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
$current_status = 'N/A'; // Default status
$applicant_name = 'N/A'; // Default name

if ($application_id > 0 && isset($conn) && $conn->ping()) {
    $sql = "SELECT status, fullname FROM application WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_status = htmlspecialchars($row['status']);
            $applicant_name = htmlspecialchars($row['fullname']);
        }
        $stmt->close();
    }
}
?>

<style>
    .status-button-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .status-action-button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        color: white;
        transition: opacity 0.2s;
    }

    .status-action-button.interviewed { background-color: #ffc107; }
    .status-action-button.hired { background-color: #28a745; }
    .status-action-button.rejected { background-color: #dc3545; }
    .status-action-button:hover { opacity: 0.8; }

    @media (max-width: 768px) {
        .status-button-group {
            flex-direction: column;
        }
        .status-action-button {
            width: 100%;
        }
    }
</style>

<div style="font-size: 16px; margin-bottom: 15px;">
    Change status for application of <strong><?php echo $applicant_name; ?></strong> (ID: <?php echo $application_id; ?>)<br>
    Current Status: <span style="font-weight: bold; color: #b23b3b;"><?php echo ucfirst($current_status); ?></span>
</div>

<div class="status-button-group">
    <button class="status-action-button interviewed" data-status="interviewed">Interviewed</button>
    <button class="status-action-button hired" data-status="hired">Hired</button>
    <button class="status-action-button rejected" data-status="rejected">Rejected</button>
</div>

<div id="statusUpdateMessage" style="margin-top: 15px; text-align: center; font-weight: bold;"></div>

<script>
    // JavaScript để xử lý việc click các nút trạng thái
    $(document).ready(function() {
        $('.status-action-button').on('click', function() {
            var newStatus = $(this).data('status');
            var applicationId = <?php echo $application_id; ?>; // Lấy application_id từ PHP

            // Xác nhận lại trước khi thay đổi (tùy chọn)
            if (!confirm('Are you sure you want to change the status to "' + newStatus + '"?')) {
                return;
            }

            $.ajax({
                url: 'employerprofile/manage_application_content.php', // Gửi POST request đến chính file này
                method: 'POST',
                data: {
                    action: 'updateApplicationStatus', // Hành động để PHP biết đây là yêu cầu cập nhật
                    application_id: applicationId,
                    new_status: newStatus
                },
                dataType: 'json', // Mong đợi phản hồi JSON
                success: function(response) {
                    var messageDiv = $('#statusUpdateMessage');
                    if (response.success) {
                        messageDiv.text('Status updated successfully to "' + newStatus + '"!').css('color', 'green');
                        alert('Status updated successfully to "' + newStatus + '"!');
                        // Sau khi cập nhật thành công, đóng modal và tải lại trang để cập nhật trạng thái hiển thị
                        $('#manageApplicationModal').hide();
                        location.reload(); // Tải lại toàn bộ trang để đơn giản hóa việc cập nhật UI
                    } else {
                        messageDiv.text('Error: ' + response.error).css('color', 'red');
                    }
                },
                error: function(xhr, status, error) {
                    $('#statusUpdateMessage').text('Ajax Error: ' + error).css('color', 'red');
                    console.error('Full AJAX Error Response:', xhr.responseText); // In ra phản hồi đầy đủ để debug
                }
            });
        });
    });
</script>