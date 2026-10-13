<?php
require_once 'config.php'; // Đảm bảo config.php được bao gồm
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employer Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .top-bar {
            background-color: #943737;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            padding: 10px 20px;
        }

        .top-bar .title {
            font-weight: bold;
            font-size: 15px;
        }

        .top-bar .company {
            display: flex;
            align-items: center;
        }

        .top-bar .company img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .top-bar .company-info {
            text-align: right;
            font-size: 12px;
        }

        .top-bar .status {
            font-size: 11px;
            color: #ffdddd;
        }

        .main {
            display: flex;
        }

        .sidebar {
            width: 160px;
            border-right: 1px solid #ccc;
            padding: 10px;
            background: #fff;
        }

        .sidebar h3 {
            margin-top: 20px;
            font-size: 14px;
        }

        .sidebar button {
            width: 100%;
            padding: 6px;
            margin-bottom: 10px;
            font-size: 13px;
            border: 1px solid black;
            background-color: white;
            cursor: pointer;
        }

        .sidebar button:hover {
            background-color: #f2f2f2;
        }

        .applicant-card {
            background-color: #b76d6d;
            color: white;
            border-radius: 6px;
            padding: 6px;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .applicant-card img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .applicant-info {
            font-size: 13px;
        }

        .status-viewed {
            font-size: 11px;
            color: #b2ffb2;
        }

        .status-new {
            font-size: 11px;
            color: #cce0ff;
        }

        .view-more {
            font-size: 12px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: underline;
        }

        .content-area {
            flex-grow: 1;
            background-color: #fff;
        }

        #btn-jobposts.active {
            background: #b23b3b;
            color: #fff;
            font-weight: bold;
        }

        /* Styles for the custom modal */
        .custom-modal-overlay {
            display: none;
            /* Hidden by default */
            position: fixed;
            z-index: 1000;
            /* Should be higher than any other content */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .custom-modal-content {
            background-color: #fefefe;
            margin: auto;
            /* Centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            /* Could be adjusted */
            max-width: 700px;
            /* Max width for larger screens */
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            border-radius: 8px;
            position: relative;
            /* For close button positioning */
            animation-name: animatetop;
            animation-duration: 0.4s
        }

        /* Add Animation */
        @keyframes animatetop {
            from {
                top: -300px;
                opacity: 0
            }

            to {
                top: 0;
                opacity: 1
            }
        }

        .custom-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .custom-modal-close:hover,
        .custom-modal-close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .main {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                box-sizing: border-box;
                border-right: none;
                border-bottom: 1px solid #ccc;
            }

            .custom-modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <div class="title"><?= $lang['employerprofile'] ?></div>
    </div>

    <div class="main">
        <div class="sidebar">
            <button><?= $lang['employerprofile_postajob'] ?></button>
            <button id="btn-jobposts"><?= $lang['employerprofile_myjobposts'] ?></button>
            <button><?= $lang['employerprofile_myprofile'] ?></button>
            <button><?= $lang['employerprofile_changeinfo'] ?></button>

            <h3><?= $lang['employerprofile_applicantprofiles'] ?></h3>

            <div class="applicant-card">
                <img src="https://via.placeholder.com/28" alt="avatar">
                <div class="applicant-info">
                    Alex<br>
                    <span class="status-viewed">✔ Viewed</span>
                </div>
            </div>

            <div class="applicant-card">
                <img src="https://via.placeholder.com/28" alt="avatar">
                <div class="applicant-info">
                    Selena<br>
                    <span class="status-new">★ New</span>
                </div>
            </div>
            <div class="view-more">View more</div>
        </div>

        <div class="content-area">
        </div>

    </div>

    <div id="jobApplicationsModal" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <span class="custom-modal-close">&times;</span>
            <div id="modal2-content">
                <div style="text-align: center;">Loading applications...</div>
            </div>
        </div>
    </div>

    <div id="manageApplicationModal" class="custom-modal-overlay">
        <div class="custom-modal-content" style="max-width: 500px;">
            <span class="close-button" id="closeManageApplicationModal">&times;</span>
            <h3 style="color: #b23b3b; margin-bottom: 20px;">Manage Application</h3>
            <div id="manageApplicationContent" style="padding: 10px;">
                <div style="text-align: center; padding: 20px;">Loading application details...</div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Function to open the second modal
            function openSecondModal(jobId) {
                $('#jobIdDisplay').text(jobId); // Display the job ID in the modal title
                $('#modal2-content').html('<div style="text-align: center; padding: 20px;">Loading applications for Job ID: ' + jobId + '...</div>');

                // Load content into the second modal via Ajax
                $.ajax({
                    url: 'employerprofile/jobposts-applications.php',
                    type: 'GET',
                    data: {
                        job_id: jobId
                    },
                    success: function (response) {
                        $('#modal2-content').html(response);
                    },
                    error: function () {
                        $('#modal2-content').html('<div style="color: red; text-align: center; padding: 20px;">Error loading applications.</div>');
                    }
                });
                $('#jobApplicationsModal').fadeIn(); // Show the modal
            }

            // Function to close the second modal
            function closeSecondModal() {
                $('#jobApplicationsModal').fadeOut(); // Hide the modal
                $('#modal2-content').empty(); // Clear content when closing
            }

            // Event listener for the close button inside the second modal
            $('.custom-modal-close').on('click', function () {
                closeSecondModal();
            });

            // Close the modal if user clicks outside the modal content
            $('#jobApplicationsModal').on('click', function (e) {
                if ($(e.target).is('.custom-modal-overlay')) {
                    closeSecondModal();
                }
            });


            // Handler for the first modal button (My job posts)
            $('#btn-jobposts').on('click', function () {
                // Remove active class from all buttons
                $('.sidebar button').removeClass('active');
                // Add active class to the clicked button
                $(this).addClass('active');
                // Show loading
                $('.content-area').html('<div style="padding:30px;text-align:center;">Loading job posts...</div>');
                // Lấy employer_id từ URL PHP và truyền vào Ajax
                var employer_id = <?php echo isset($_GET['id']) ? intval($_GET['id']) : 0; ?>;
                $.get('employerprofile/jobposts-content.php', { id: employer_id }, function (data) {
                    $('.content-area').html(data);
                });
            });

            // Event delegation for "Check submitted applications" button inside jobposts-content.php
            // This needs to be on a parent element that is always present in the DOM (e.g., document or .content-area)
            $(document).on('click', '#btn-jobpostsapplications', function () {
                var jobId = $(this).data('jobid'); // Get job ID from data attribute
                if (jobId) {
                    openSecondModal(jobId);
                } else {
                    console.error("Job ID not found for applications button.");
                }
            });

            // Optional: Automatically load jobposts when the page loads
            // $(document).ready(function() {
            //      $('#btn-jobposts').trigger('click');
            // });
        });
    </script>


    <script>// Xử lý sự kiện click cho nút "View CV" trong danh sách ứng tuyển
        $(document).on('click', '.view-cv-button', function (event) {
            event.preventDefault(); // Ngăn trình duyệt chuyển hướng đến file CV ngay lập tức

            var applicationId = $(this).data('application-id');
            var cvUrl = $(this).attr('href'); // Lấy URL của file CV

            // Gọi Ajax để cập nhật trạng thái thành 'viewed'
            $.ajax({
                url: 'employerprofile/jobposts-applications.php', // Hoặc 'update_viewed_status.php' nếu bạn tách file
                method: 'POST',
                data: {
                    action: 'updateViewedStatus',
                    application_id: applicationId
                },
                dataType: 'json', // Mong đợi phản hồi JSON
                success: function (response) {
                    if (response.success) {
                        // Nếu cập nhật thành công, chuyển hướng đến file CV
                        window.open(cvUrl, '_blank');
                    } else {
                        console.error('Lỗi khi cập nhật trạng thái "viewed":', response.error);
                        alert('Lỗi khi xem CV. Vui lòng thử lại.'); // Hoặc thông báo lỗi cho người dùng
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Lỗi Ajax:', error);
                    alert('Lỗi khi xem CV. Vui lòng thử lại.');
                }
            });
        });
    </script>

    <script>
        // Function to open the third modal (Manage Application Modal)
        function openManageApplicationModal(applicationId) {
            $('#manageApplicationModal').show(); // Show the manage application modal
            $('#manageApplicationContent').html('<div style="text-align: center; padding: 20px;">Loading application details for ID: ' + applicationId + '...</div>');

            // Load content into the manage application modal via Ajax
            $.ajax({
                url: 'employerprofile/manage_application_content.php', // This file will provide the buttons
                type: 'GET', // Use GET to fetch initial content
                data: {
                    application_id: applicationId
                },
                success: function (response) {
                    $('#manageApplicationContent').html(response);
                },
                error: function () {
                    $('#manageApplicationContent').html('<div style="color: red; text-align: center; padding: 20px;">Error loading management options.</div>');
                }
            });
        }

        // Event listener for the "Manage" buttons (using event delegation for dynamic content)
        $(document).on('click', '.btn-manage-application', function () {
            var applicationId = $(this).data('application-id');
            openManageApplicationModal(applicationId);
        });

        // Close button for the Manage Application Modal
        $('#closeManageApplicationModal').on('click', function () {
            $('#manageApplicationModal').hide();
        });

        // Close modal if clicked outside (optional but good for UX)
        $(window).on('click', function (event) {
            if ($(event.target).is('#manageApplicationModal')) {
                $('#manageApplicationModal').hide();
            }
        });

        // ... (other scripts) ...
    </script>
</body>

</html>