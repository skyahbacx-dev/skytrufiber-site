<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$sender_type  = $_POST['sender_type'] ?? 'client';
$message      = trim($_POST['message'] ?? '');
$username     = $_SESSION['name'] ?? ($_POST['username'] ?? '');
$csr_user     = $_POST['csr_user'] ?? '';
$csr_fullname = $_POST['csr_fullname'] ?? '';

$media_path = null;
$media_type = null;

/* ===== MEDIA UPLOAD ===== */
if (!empty($_FILES['file']['name'])){
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    if(in_array($ext,['jpg','jpeg','png','gif','webp'])){
        $media_type = 'image'; 
        $folder="../uploads/chat_images/";
    } elseif(in_array($ext,['mp4','mov','avi','mkv','webm'])){
        $media_type = 'video';
        $folder="../uploads/chat_videos/";
    }

    if($media_type){
        $newName = time()."_".rand(1000,9999).".".$ext;
        $path = $folder.$newName;
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
        $media_path = str_replace("../","",$path);
    }
}

if($message==='' && $media_path===null){
    echo json_encode(['status'=>'error','msg'=>'Empty message']);
    exit;
}

/* ===== CLIENT MESSAGE ===== */
if($sender_type === 'client'){

    // Check or create client
    $stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name=:u LIMIT 1");
    $stmt->execute([':u'=>$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$row){
        // pick any available CSR
        $csrQ = $conn->query("SELECT username, full_name FROM csr_users WHERE status='active' ORDER BY is_online DESC LIMIT 1");
        $csr = $csrQ->fetch(PDO::FETCH_ASSOC);

        $assigned = $csr['username'] ?? 'CSR Support';
        $assigned_full = $csr['full_name'] ?? 'Support Team';

        $ins = $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (:n,:csr,NOW()) RETURNING id");
        $ins->execute([':n'=>$username, ':csr'=>$assigned]);
        $client_id = $ins->fetchColumn();
    } else {
        $client_id = $row['id'];
        $assigned = $row['assigned_csr'];
        $assigned_full = $csr_fullname;
    }

    // Insert client message
    $insert=$conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, media_path, media_type, assigned_csr, csr_fullname, created_at)
        VALUES (:cid,'client',:msg,:mp,:mt,:csr,:csrfull,NOW())
    ");
    $insert->execute([
        ':cid'=>$client_id, ':msg'=>$message,
        ':mp'=>$media_path, ':mt'=>$media_type,
        ':csr'=>$assigned, ':csrfull'=>$assigned_full
    ]);

    /* ===== AUTO GREETING (only if 1st message) ===== */
    $count = $conn->prepare("SELECT COUNT(*) FROM chat WHERE client_id=:cid");
    $count->execute([':cid'=>$client_id]);
    $total = $count->fetchColumn();

    if ($total == 1){
        $greet = "👋 Hi $username! This is $assigned_full from SkyTruFiber Support. How can I help you today?";

        $greetStmt=$conn->prepare("
          INSERT INTO chat (client_id,sender_type,message,assigned_csr,csr_fullname,created_at)
          VALUES (:cid,'csr',:msg,:csr,:full,NOW())
        ");
        $greetStmt->execute([
            ':cid'=>$client_id, ':msg'=>$greet,
            ':csr'=>$assigned, ':full'=>$assigned_full
        ]);
    }

    echo json_encode(['status'=>'ok','client_id'=>$client_id]);
    exit;
}

/* ===== CSR MESSAGE ===== */
if($sender_type==='csr' && isset($_POST['client_id'])){
    $client_id = (int)$_POST['client_id'];

    $cStmt=$conn->prepare("
      INSERT INTO chat(client_id,sender_type,message,media_path,media_type,assigned_csr,csr_fullname,created_at)
      VALUES(:cid,'csr',:msg,:mp,:mt,:csr,:full,NOW())
    ");
    $cStmt->execute([
      ':cid'=>$client_id,':msg'=>$message,
      ':mp'=>$media_path,':mt'=>$media_type,
      ':csr'=>$csr_user, ':full'=>$csr_fullname
    ]);

    echo json_encode(['status'=>'ok']);
    exit;
}

echo json_encode(['status'=>'error','msg'=>'Invalid']);
?>
