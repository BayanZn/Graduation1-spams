<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    try {
        if ($action === 'add') {
            $project_id = (int)$_POST['project_id'];
            $supervisor_id = (int)$_POST['supervisor_id'];
            $is_lead = isset($_POST['is_lead']) ? 1 : 0;

            if (empty($project_id) || empty($supervisor_id)) {
                echo json_encode(['status'=>'error','message'=>'Project and Supervisor are required']); exit;
            }

            $stmt = $db->prepare("SELECT id FROM project_supervision WHERE project_id=? AND supervisor_id=?");
            $stmt->execute([$project_id, $supervisor_id]);
            if ($stmt->rowCount()>0){ echo json_encode(['status'=>'error','message'=>'Supervisor already assigned']); exit; }

            $stmt = $db->prepare("INSERT INTO project_supervision (project_id, supervisor_id, is_lead) VALUES (?,?,?)");
            $stmt->execute([$project_id,$supervisor_id,$is_lead]);

            if($is_lead){
                $db->prepare("UPDATE project_supervision SET is_lead=0 WHERE project_id=? AND supervisor_id!=?")
                   ->execute([$project_id,$supervisor_id]);
            }

            echo json_encode(['status'=>'success','message'=>'Supervisor assigned successfully']);
        }

        elseif($action==='remove'){
            $id=(int)$_POST['id'];
            $stmt=$db->prepare("DELETE FROM project_supervision WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Supervisor removed']);
        }

        elseif($action==='toggle_lead'){
            $id=(int)$_POST['id'];
            $project_id=(int)$_POST['project_id'];
            $db->prepare("UPDATE project_supervision SET is_lead=0 WHERE project_id=?")->execute([$project_id]);
            $db->prepare("UPDATE project_supervision SET is_lead=1 WHERE id=?")->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Lead supervisor updated']);
        }

        elseif($action==='list'){
            $project_id=(int)$_POST['project_id'];
            $stmt=$db->prepare("SELECT ps.id, s.full_name, s.department, ps.is_lead 
                                FROM project_supervision ps 
                                JOIN supervisors s ON ps.supervisor_id=s.id 
                                WHERE ps.project_id=?");
            $stmt->execute([$project_id]);
            $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status'=>'success','data'=>$data]);
        }

    }catch(PDOException $e){
        echo json_encode(['status'=>'error','message'=>'Database error','debug'=>$e->getMessage()]);
    }
}else{
    echo json_encode(['status'=>'error','message'=>'Invalid request method']);
}
