<?php
session_start();
require_once __DIR__ . '/config.php';
if (isset($_GET['lang'])) $_SESSION['lang'] = $_GET['lang'];
if (!isset($_SESSION['lang'])) $_SESSION['lang'] = 'vi';
include __DIR__ . '/lang/' . basename($_SESSION['lang']) . '.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="image/favicon.svg">
<title><?= htmlspecialchars($lang['employerforgetpassword_title'] ?? 'Employer password recovery', ENT_QUOTES, 'UTF-8') ?></title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:linear-gradient(135deg,#fff4f2,#f4f7fb);font-family:Arial,sans-serif}.recovery-card{width:min(100%,420px);padding:30px;border:1px solid #e4e7ec;border-radius:16px;background:#fff;box-shadow:0 18px 55px rgba(16,24,40,.12)}h1{margin:0 0 10px;color:#b42318;font-size:24px;text-align:center}p{color:#667085;line-height:1.5}label{display:block;margin:20px 0 7px;font-weight:700}input{width:100%;padding:12px;border:1px solid #d0d5dd;border-radius:8px;font-size:16px}button{width:100%;margin-top:18px;padding:12px;border:0;border-radius:8px;color:#fff;background:#b42318;font-size:16px;font-weight:700;cursor:pointer}button:disabled{opacity:.6;cursor:wait}.message{min-height:21px;margin:14px 0 0;color:#b42318;text-align:center}
</style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
<main class="recovery-card"><h1><?= htmlspecialchars($lang['employerforgetpassword_title'] ?? 'Employer password recovery', ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($lang['employerforgetpassword_intro'] ?? 'Enter your email and we will help you recover your password.', ENT_QUOTES, 'UTF-8') ?></p>
<form id="forgot-password-form"><label for="email-input"><?= htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8') ?></label><input id="email-input" name="email" type="email" required autocomplete="email"><button type="submit" id="send-otp"><?= htmlspecialchars($lang['employerforgetpassword_button'] ?? 'Send code', ENT_QUOTES, 'UTF-8') ?></button><p class="message" id="form-message" role="alert"></p></form></main>
<script>
document.getElementById('forgot-password-form').addEventListener('submit',async function(event){event.preventDefault();const button=document.getElementById('send-otp'),message=document.getElementById('form-message');button.disabled=true;message.textContent='';try{const body=new URLSearchParams({action:'send_otp',email:document.getElementById('email-input').value});const response=await fetch('employerforgetpassword_process.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});const data=await response.json();if(data.success){window.location.href='employerpasswordrecover.php';return}message.textContent=data.message||'<?= addslashes($lang['reset_generic_error'] ?? 'An error occurred. Please try again.') ?>'}catch(error){message.textContent='<?= addslashes($lang['reset_generic_error'] ?? 'An error occurred. Please try again.') ?>'}button.disabled=false});
</script>
</body></html>
