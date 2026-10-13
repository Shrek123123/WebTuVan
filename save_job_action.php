<?php
session_start(); // Start session to get user info

// Set header to indicate JSON response
header('Content-Type: application/json');

// Check request method and POST parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['job_id'])) {
  // Include your database config file
  require_once('config.php'); 

  // Get user_id from session (safest way)
  $user_id = isset($_SESSION['jobseeker_id']) ? (int)$_SESSION['jobseeker_id'] : 0;
  
  // Get job_id and action from POST data, sanitize input
  $job_id = (int)$_POST['job_id'];
  $action = $conn->real_escape_string($_POST['action']); // Sanitize action string

  // Validate user_id and job_id
  if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in or invalid ID.']);
    exit;
  }
  if ($job_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid job ID.']);
    exit;
  }

  if ($action === 'save') {
    // Use INSERT IGNORE to avoid error if user already saved this job
    $stmt = $conn->prepare("INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
    if ($stmt) {
      $stmt->bind_param("ii", $user_id, $job_id);
      if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Job saved successfully.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'DB error when saving job: ' . $stmt->error]);
      }
      $stmt->close();
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Error preparing save query: ' . $conn->error]);
    }
  } elseif ($action === 'unsave') {
    // Delete saved job
    $stmt = $conn->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    if ($stmt) {
      $stmt->bind_param("ii", $user_id, $job_id);
      if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Job unsaved successfully.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'DB error when unsaving job: ' . $stmt->error]);
      }
      $stmt->close();
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Error preparing unsave query: ' . $conn->error]);
    }
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
  }

  $conn->close(); // Close database connection after processing
} else {
  // Respond with error if request is invalid
  echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
exit; // Ensure no other content is sent after JSON response
?>