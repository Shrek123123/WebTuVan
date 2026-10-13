<?php
session_start();
require_once '../config.php'; // Đảm bảo config.php được bao gồm và thiết lập kết nối $conn
include '../lang/' . $_SESSION['lang'] . '.php';
include '../helpers.php';
// Lấy job_id từ request Ajax
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
require_once '../config.php';


// Phần code xử lý hiển thị danh sách ứng tuyển vẫn giữ nguyên
// ...

// Compute AJAX endpoint dynamically so fetch always targets the correct script path
// Build absolute AJAX endpoint (scheme + host + path) to avoid path issues
$ajaxPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/jobposts-applications.php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$ajaxEndpoint = $scheme . '://' . $_SERVER['HTTP_HOST'] . $ajaxPath;

// Xử lý POST cho các hành động cập nhật trạng thái application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Bảo đảm chỉ employer đã đăng nhập mới được phép
    if (!isset($_SESSION['employerid']) || empty($_SESSION['employerid'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $action = $_POST['action'];

    // Hỗ trợ action: hideFromEmployer (soft-hide) và updateViewedStatus/updateApplicationStatus
    if ($action === 'hideFromEmployer') {
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        if ($application_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid application ID']);
            exit;
        }

        $employerid = $_SESSION['employerid'] ?? 0;
        if ($employerid <= 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if (isset($conn) && $conn->ping()) {
            // Ensure employer owns the job for this application
            $checkSql = "SELECT j.posted_by_employer_id FROM application a JOIN hoa_giai_vien j ON a.job_id = j.id WHERE a.id = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            if ($checkStmt) {
                $checkStmt->bind_param("i", $application_id);
                $checkStmt->execute();
                $res = $checkStmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (intval($row['posted_by_employer_id']) !== intval($employerid)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Unauthorized to hide this application']);
                        $checkStmt->close();
                        exit;
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Application not found']);
                    $checkStmt->close();
                    exit;
                }
                $checkStmt->close();

                // Soft-hide for employer
                $updSql = "UPDATE application SET hidden_from_employer = 1 WHERE id = ?";
                $updStmt = $conn->prepare($updSql);
                if ($updStmt) {
                    $updStmt->bind_param("i", $application_id);
                    if ($updStmt->execute()) {
                        echo json_encode(['success' => true]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $conn->error]);
                    }
                    $updStmt->close();
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Error preparing update: ' . $conn->error]);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error preparing check statement: ' . $conn->error]);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection error']);
        }
        exit;
    }

    // Hỗ trợ hai action: updateViewedStatus (legacy) và updateApplicationStatus (flexible)
    if ($action === 'updateViewedStatus' || $action === 'updateApplicationStatus') {
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

        if ($application_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid application ID']);
            exit;
        }

        // Nếu là updateApplicationStatus thì lấy trạng thái mới và validate
        if ($action === 'updateApplicationStatus') {
            $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
            $allowed_statuses = ['applied', 'viewed', 'interviewed', 'hired', 'rejected'];
            if (!in_array($new_status, $allowed_statuses)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status provided.']);
                exit;
            }
        } else {
            // legacy: set to 'viewed' only if currently 'applied'
            $new_status = 'viewed';
        }

        if (isset($conn) && $conn->ping()) {
            $detailsSql = "SELECT a.email, j.job_title, c.company_name
                           FROM application a
                           JOIN hoa_giai_vien j ON a.job_id = j.id
                           JOIN company c ON j.company_id = c.id
                           WHERE a.id = ? AND j.posted_by_employer_id = ?
                           LIMIT 1";
            $detailsStmt = $conn->prepare($detailsSql);
            $detailsStmt->bind_param("ii", $application_id, $employerid);
            $detailsStmt->execute();
            $detailsResult = $detailsStmt->get_result();
            $applicationDetails = $detailsResult->fetch_assoc();
            $detailsStmt->close();

            if (!$applicationDetails) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Application not found']);
                exit;
            }

            $sql = "UPDATE application SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("si", $new_status, $application_id);
                if ($stmt->execute()) {
                    sendApplicationStatusEmail(
                        $applicationDetails['email'],
                        $applicationDetails['job_title'],
                        $applicationDetails['company_name'],
                        $new_status
                    );
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                $stmt->close();
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection error']);
        }
        exit; // Dừng việc thực thi script sau khi xử lý POST
    }
}
$employerid = $_SESSION['employerid'] ?? 0;
$stmt = $conn->prepare("
    SELECT *
    FROM hoa_giai_vien
    WHERE id = ? AND posted_by_employer_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $job_id, $employerid);
$stmt->execute();
$result = $stmt->get_result();
$job_row = $result->fetch_assoc();
$stmt->close();

if (!$job_row) {
    die("Job không tồn tại hoặc không thuộc employer.");
}

// Lấy job_post_number
$job_post_number = $job_row['job_post_number_by_employer'] ?? 0;

// Tạo message
$lang_key = 'your_job_post#';
$message = sprintf($lang[$lang_key], $job_post_number);

// Lấy tổng số application cho cùng một job
$sql = "SELECT COUNT(id) AS total_applications
    FROM application
    WHERE job_id = ? AND (hidden_from_employer = 0 OR hidden_from_employer IS NULL)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

$total_applications = 0;
if ($row = $result->fetch_assoc()) {
    $total_applications = $row['total_applications'];
}
?>


<style>
    @media (max-width: 768px) {
        .application-card > div:first-child,
        .application-card > div:last-child {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        
        .application-card > div:last-child div:last-child {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .application-card > div:last-child button,
        .application-card > div:last-child a {
            width: 100%;
            text-align: center;
            margin-right: 0 !important;
        }
    }
</style>

<div style="color: #b23b3b; font-weight: bold; font-size: 20px; text-align: center;"><?php echo $message ?><span></span>
</div>
<div style="padding: 10px;">
    <?php if ($job_id > 0): ?>
        <h4 style="margin-bottom: 15px; color: #b23b3b;">
            <?= $lang['application_detail_to_job'] ?> <strong><?php echo htmlspecialchars($total_applications); ?></strong>
        </h4>

        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php
            // Kiểm tra kết nối database
            if (isset($conn) && $conn->ping()) {
                // Truy vấn để lấy các ứng dụng cho job_id cụ thể
                // Lấy thông tin từ bảng 'application'
        $sql = "SELECT id, fullname, phonenumber, email, status, created_at, cv_file
            FROM application
            WHERE job_id = ? AND (hidden_from_employer = 0 OR hidden_from_employer IS NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    echo '<div style="color: #888; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' . $lang['no_application_found_for_this_job_post'] . '</div>';
                } else {
                    while ($row = $result->fetch_assoc()):
                        $application_id = htmlspecialchars($row['id']);
                        $fullname = htmlspecialchars($row['fullname']);
                        $phonenumber = htmlspecialchars($row['phonenumber']);
                        $email = htmlspecialchars($row['email']);
                        $job_status_raw = htmlspecialchars($row['status']);
                        $created_at = new DateTime($row['created_at']);
                        $formatted_created_at = formatDateByLocale($row['created_at']);
                        $final_formatted_date = trans('cv_application_date', [':date' => $formatted_created_at]);
                        $cv_file = !empty($row['cv_file']) ? htmlspecialchars($row['cv_file']) : '';

                        $status_lang_keys = [
                            'hired' => 'hired',
                            'pending' => 'pending',
                            'rejected' => 'rejected_application',
                            'interviewed' => 'interviewed',
                        ];

                        // For employer view: hide 'viewed' status (show blank). Only jobseeker sees 'viewed'.
                        if ($job_status_raw === 'viewed') {
                            // change nothing to employer
                            $job_status_raw = 'pending';
                            $display_status = $lang[$status_lang_keys[$job_status_raw]];
                        } else {
                            $display_status = isset($status_lang_keys[$job_status_raw]) ? $lang[$status_lang_keys[$job_status_raw]] : $job_status_raw;
                        }

                        // Định nghĩa màu cho trạng thái
                        $status_color = '';
                        switch ($job_status_raw) {
                            case 'pending':
                                $status_color = '#007bff'; // Blue
                                break;
                            case 'interviewed':
                                $status_color = '#ffc107'; // Yellow
                                break;
                            case 'hired':
                                $status_color = '#28a745'; // Green
                                break;
                            case 'rejected':
                                $status_color = '#dc3545'; // Red
                                break;
                            default:
                                $status_color = '#6c757d'; // Grey
                                break;
                        }

                        ?>
                        <div class="application-card" data-application-id="<?php echo $application_id; ?>"
                            style="display: flex; flex-direction: column; background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div style="font-weight: bold; font-size: 18px; color: #333;"><?php echo $fullname; ?></div>
                                <div style="font-size: 13px; color: #777;"><?php echo $final_formatted_date; ?></div>
                            </div>
                            <div style="font-size: 14px; color: #555; margin-bottom: 5px;">
                                Email: <strong><?php echo $email; ?></strong>
                            </div>
                            <div style="font-size: 14px; color: #555; margin-bottom: 10px;">
                                <?= $lang['phone'] ?> <strong><?php echo $phonenumber; ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <?= $lang['status2'] ?> <span
                                        style="font-weight: bold; color: <?php echo $status_color; ?>;"><?php echo ucfirst($display_status); ?></span>
                                </div>
                                <div>
                                    <?php if (!empty($cv_file)): ?>
                                        <a href="<?php echo htmlspecialchars($cv_file); ?>" class="view-cv-button"
                                            data-application-id="<?php echo $application_id; ?>"
                                            style="background: #b23b3b; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; text-decoration: none; margin-right: 8px;">
                                            <?= $lang['view_cv'] ?>
                                        </a>
                                        <button data-application-id="<?php echo $application_id; ?>" class="btn-manage-application"
                                        style="background: #007bff; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; cursor: pointer;"><?= $lang['manage'] ?></button>
                                    <?php endif; ?>

                                    <!-- Hide (archive from employer) button -->
                                    <button data-application-id="<?php echo $application_id; ?>" class="hide-application-button"
                                        style="background: #dc3545; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; cursor: pointer;"><?= $lang['delete_submitted_applications'] ?></button>
                                </div>
                            </div>
                        </div>
                        <?php
                    endwhile;
                }
            } else {
                echo '<div style="color: red; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">Database connection error.</div>';
            }
            ?>
        </div>
        <script>
            // Create a lightweight confirmation modal (no browser confirm)
            (function () {
                // Inject modal HTML into document once
                const modalHtml = `
                <div id="confirmModal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
                    <div id="confirmModalOverlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.45);"></div>
                    <div style="position:relative; background:#fff; border-radius:8px; max-width:420px; width:90%; padding:18px; box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:10000;">
                        <div style="font-size:16px; margin-bottom:12px; color:#222;" id="confirmModalMessage"></div>
                        <div style="text-align:right;">
                            <button id="confirmCancelBtn" style="margin-right:8px; padding:8px 12px; border-radius:6px; border:1px solid #ccc; background:#f5f5f5;"><?= addslashes($lang['cancel'] ?? 'Cancel') ?></button>
                            <button id="confirmOkBtn" style="padding:8px 12px; border-radius:6px; border:0; background:#dc3545; color:#fff;"><?= addslashes($lang['delete_submitted_applications'] ?? 'Delete') ?></button>
                        </div>
                    </div>
                </div>`;

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                const confirmModal = document.getElementById('confirmModal');
                const confirmMessage = document.getElementById('confirmModalMessage');
                const cancelBtn = document.getElementById('confirmCancelBtn');
                const okBtn = document.getElementById('confirmOkBtn');

                let pendingApplicationId = null;

                function showConfirm(message, applicationId) {
                    pendingApplicationId = applicationId;
                    confirmMessage.textContent = message;
                    confirmModal.style.display = 'flex';
                }

                function hideConfirm() {
                    pendingApplicationId = null;
                    confirmModal.style.display = 'none';
                }

                cancelBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    hideConfirm();
                });

                // Simple toast/notification helper
                function showToast(message, timeout = 2000) {
                    const toast = document.createElement('div');
                    toast.className = 'app-toast';
                    toast.style.position = 'fixed';
                    toast.style.right = '20px';
                    toast.style.bottom = '30px';
                    toast.style.background = 'rgba(0,0,0,0.8)';
                    toast.style.color = '#fff';
                    toast.style.padding = '10px 14px';
                    toast.style.borderRadius = '6px';
                    toast.style.zIndex = 11000;
                    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                    toast.style.opacity = '1';
                    toast.style.transition = 'opacity 0.4s ease';
                    toast.textContent = message;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => document.body.removeChild(toast), 400);
                    }, timeout);
                }

                okBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!pendingApplicationId) {
                        hideConfirm();
                        return;
                    }
                    // Perform hide (soft-delete) request
                        // Debug: log endpoint
                        console.log('Hiding application, POST to: "<?= addslashes($ajaxEndpoint) ?>"');
                        fetch('<?= addslashes($ajaxEndpoint) ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=hideFromEmployer&application_id=' + encodeURIComponent(pendingApplicationId)
                    })
                        .then(res => res.json())
                        .then(json => {
                            if (json && json.success) {
                                const card = document.querySelector('.application-card[data-application-id="' + pendingApplicationId + '"]');
                                if (card) card.remove();
                                // Show success toast
                                showToast('<?= addslashes($lang['delete_application_success_message'] ?? 'Application deleted successfully.') ?>');
                            } else {
                                alert('Could not hide application: ' + (json && json.error ? json.error : 'unknown'));
                            }
                        })
                        .catch(err => {
                            console.error('Error hiding application', err);
                            alert('Error hiding application');
                        })
                        .finally(() => hideConfirm());
                });

                // Delegated click handler to open the modal instead of browser confirm
                document.addEventListener('click', function (e) {
                    const btn = e.target.closest && e.target.closest('.hide-application-button');
                    if (!btn) return;
                    e.preventDefault();
                    const applicationId = btn.getAttribute('data-application-id');
                    if (!applicationId) return;
                    // Message can be localized via lang key
                    const msg = '<?= addslashes($lang['confirm_delete_submitted_applications'] ?? 'Delete submitted application?') ?>';
                    showConfirm(msg, applicationId);
                });
            })();
        </script>
    <?php else: ?>
        <div
            style="color: #888; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?= $lang['no_job_id_provided'] ?>
        </div>
    <?php endif; ?>
</div>