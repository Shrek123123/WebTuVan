<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

if(!isset($_SESSION['jobseeker_id'])){
    echo json_encode(['success'=>false,'message'=>'Chưa đăng nhập']);
    exit;
}

$jobseeker_id = (int)$_SESSION['jobseeker_id'];

$stmt = $conn->prepare("SELECT cv_file FROM jobseeker_profile WHERE jobseeker_id=?");
$stmt->bind_param("i",$jobseeker_id);
$stmt->execute();
$stmt->bind_result($filePath);
$stmt->fetch();
$stmt->close();

if($filePath && file_exists($filePath)){
    unlink($filePath);
}

$stmt = $conn->prepare("UPDATE jobseeker_profile SET cv_file=NULL WHERE jobseeker_id=?");
$stmt->bind_param("i",$jobseeker_id);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'message'=>$stmt->error]);
}
