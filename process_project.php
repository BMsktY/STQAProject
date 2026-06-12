<?php
session_start();
include_once "connect.php";
define('REDIRECT_HOME', 'Location: homepage.php');

if(!isset($_SESSION['Email'])){
    header("Location: index.php");
    exit();
}

$email = $_SESSION['Email'];

if(isset($_POST['upload'])){
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $date  = $_POST['date'];
    
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    if(isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0){
        $file      = $_FILES['media_file'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target_file = $target_dir . $file_name;
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov'];
        $max_size    = 50 * 1024 * 1024;
        
        if(!in_array($ext, $allowed_ext)){
            $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, PNG, MP4, atau WEBM.";
            header(REDIRECT_HOME);
            exit();
        }
        
        if($file['size'] > $max_size){
            $_SESSION['error'] = "Ukuran file terlalu besar (Maks 50MB).";
            header(REDIRECT_HOME);
            exit();
        }
        
        if(move_uploaded_file($file['tmp_name'], $target_file)){
            $stmt = $conn->prepare("INSERT INTO posts (user_email, project_title, project_description, project_image, project_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $email, $title, $desc, $target_file, $date);
            
            if($stmt->execute()){
                $_SESSION['success'] = "Project berhasil ditambahkan!";
            } else {
                $_SESSION['error'] = "Gagal menyimpan ke database.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Gagal mengupload file.";
        }
    } else {
        $_SESSION['error'] = "Silakan pilih file media.";
    }
    
    header(REDIRECT_HOME);
    exit();
}

if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT project_image FROM posts WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if($row && !empty($row['project_image']) && file_exists($row['project_image'])){
        unlink($row['project_image']);
    }
    
    $del = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_email = ?");
    $del->bind_param("is", $id, $email);
    $del->execute();
    
    $_SESSION['success'] = "Project berhasil dihapus!";
    header("Location: dashboard.php");
    exit();
}