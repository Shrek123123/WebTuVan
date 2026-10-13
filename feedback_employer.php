<?php

if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$language = isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['vi', 'en'], true)
    ? $_SESSION['lang']
    : 'vi';
$_SESSION['lang'] = $language;
require_once __DIR__ . '/lang/' . $language . '.php';
require_once __DIR__ . '/config.php';

function show_feedback_alert(string $message): void
{
    echo '<script>alert(' . json_encode($message, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');</script>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['role']) || (!isset($_SESSION['jobseeker_id']) && !isset($_SESSION['employerid']))) {
        show_feedback_alert($lang['feedback_login_required']);
    } else {
        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
        $content = isset($_POST['note']) ? trim($_POST['note']) : '';
        $user_id = null;
        $user_type = null;

        if ($_SESSION['role'] === 'jobseeker' && isset($_SESSION['jobseeker_id'])) {
            $user_id = $_SESSION['jobseeker_id'];
            $user_type = 'jobseeker';
        } elseif ($_SESSION['role'] === 'employer' && isset($_SESSION['employerid'])) {
            $user_id = $_SESSION['employerid'];
            $user_type = 'recruiter';
        }

        if ($user_id !== null && $user_type !== null && $rating >= 1 && $rating <= 5) {
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, user_type, content, star_rating) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issi", $user_id, $user_type, $content, $rating);
                if ($stmt->execute()) {
                    show_feedback_alert($lang['feedback_success']);
                } else {
                    show_feedback_alert($lang['feedback_submit_error'] . $stmt->error);
                }
                $stmt->close();
            } else {
                show_feedback_alert($lang['feedback_prepare_error'] . $conn->error);
            }
        } else {
            show_feedback_alert($lang['feedback_invalid_rating']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/svg+xml" href="image/favicon.svg">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($lang['feedback_page_title'], ENT_QUOTES, 'UTF-8'); ?></title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background: #f4f6f8; color: #333; }
    .feedback-heading { max-width: 510px; margin: 0 auto 20px; padding: 20px 16px; border-radius: 8px; background: #e74c3c; color: #fff; text-align: center; }
    .feedback-heading h1 { margin: 0; font-size: clamp(1.5rem, 5vw, 2rem); }
    .feedback-form { width: 100%; max-width: 510px; margin: 0 auto 20px; padding: 25px 30px; border-radius: 8px; background: #fff; box-shadow: 0 0 15px rgba(0, 0, 0, .1); }
    .feedback-form label { display: block; margin-bottom: 8px; font-weight: bold; }
    .stars { display: flex; flex-direction: row-reverse; justify-content: center; margin-bottom: 20px; }
    .stars input { position: absolute; opacity: 0; pointer-events: none; }
    .stars label { padding: 0 5px; color: #ccc; cursor: pointer; font-size: 2rem; line-height: 1; transition: color .2s; }
    .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color: #ffca08; }
    .feedback-form textarea { width: 100%; min-height: 110px; margin-bottom: 20px; padding: 10px; border: 1.5px solid #ccc; border-radius: 4px; font: inherit; resize: vertical; }
    .feedback-form textarea:focus { border-color: #007bff; outline: none; }
    .btn-submit { width: 100%; padding: 12px 25px; border: 0; border-radius: 5px; background: #007bff; color: #fff; font-size: 1rem; font-weight: bold; cursor: pointer; }
    .btn-submit:hover { background: #0056b3; }
    @media (max-width: 600px) {
        body { padding: 12px; }
        .feedback-form { padding: 20px 16px; }
        .stars label { padding: 0 4px; font-size: 1.8rem; }
    }
</style>
</head>
<body>
<?php include __DIR__ . '/employerpage/header.php'; ?>
<header class="feedback-heading">
    <h1><?php echo htmlspecialchars($lang['feedback_page_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
</header>
<form class="feedback-form" method="post" action="">
    <label><?php echo htmlspecialchars($lang['feedback_rating_label'], ENT_QUOTES, 'UTF-8'); ?></label>
    <div class="stars">
        <?php for ($star = 5; $star >= 1; $star--): ?>
            <input type="radio" id="star<?php echo $star; ?>" name="rating" value="<?php echo $star; ?>">
            <label for="star<?php echo $star; ?>" title="<?php echo htmlspecialchars(str_replace(':count', (string) $star, $lang['feedback_star_label']), ENT_QUOTES, 'UTF-8'); ?>">&#9733;</label>
        <?php endfor; ?>
    </div>
    <label for="note"><?php echo htmlspecialchars($lang['feedback_note_label'], ENT_QUOTES, 'UTF-8'); ?></label>
    <textarea id="note" name="note" rows="4" placeholder="<?php echo htmlspecialchars($lang['feedback_note_placeholder'], ENT_QUOTES, 'UTF-8'); ?>"></textarea>
    <button class="btn-submit" type="submit"><?php echo htmlspecialchars($lang['feedback_submit'], ENT_QUOTES, 'UTF-8'); ?></button>
</form>
<?php include __DIR__ . '/homepage/footer.php'; ?>
</body>
</html>
