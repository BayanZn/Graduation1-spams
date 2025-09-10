<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

$id = (int)($_POST['submission_id'] ?? 0);
$feedback = trim($_POST['feedback'] ?? '');
$status = trim($_POST['status'] ?? '');
$sup_id = $_SESSION['related_id'] ?? 0;

if (!$id || !$feedback || !$status) {
  echo json_encode(['status'=>'error','message'=>'All fields are required']); exit;
}

$stmt = $db->prepare("
  SELECT cs.id FROM project_chapter_submissions cs
  JOIN students s ON cs.student_id = s.id
  JOIN project_supervision psv ON s.project_id = psv.project_id
  WHERE cs.id = ? AND psv.supervisor_id = ?
");
$stmt->execute([$id, $sup_id]);
if (!$stmt->fetch()) {
  echo json_encode(['status'=>'error','message'=>'Unauthorized or not found']); exit;
}

$stmt = $db->prepare("
  UPDATE project_chapter_submissions 
  SET feedback = ?, status = ?, updated_at = NOW()
  WHERE id = ?
");
$stmt->execute([$feedback, $status, $id]);
echo json_encode(['status'=>'success','message'=>'Review submitted successfully']);
