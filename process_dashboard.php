<?php
session_start();
include_once "connect.php";

if (!defined('REDIRECT_PREFIX')) {
    define('REDIRECT_PREFIX', 'Location: ');
}

if (!isset($_SESSION['Email'])) {
    header(REDIRECT_PREFIX . 'index.php');
    exit();
}

$email      = $_SESSION['Email'];
$target_dir = "uploads/";

if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}

if (isset($_POST['add_project'])) {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=csrf_error');
        exit();
    }

    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $date  = $_POST['date'];

    // Validate date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=invalid_date');
        exit();
    }

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $file    = $_FILES['file'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg', 'pdf', 'doc', 'docx', 'txt', 'zip'];

        if (in_array($ext, $allowed)) {
            $new_name    = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $target_file = $target_dir . $new_name;

            if ($file['size'] <= 20 * 1024 * 1024) {
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $stmt = $conn->prepare("INSERT INTO posts (user_email, project_title, project_description, project_image, project_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $email, $title, $desc, $target_file, $date);
                    if ($stmt->execute()) {
                        header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=success');
                    } else {
                        header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=db_error');
                    }
                    $stmt->close();
                } else {
                    header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=upload_error');
                }
            } else {
                header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=file_too_large');
            }
        } else {
            header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=invalid_type');
        }
    } else {
        header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=no_file');
    }
    exit();
}

if (isset($_POST['update_project'])) {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=csrf_error');
        exit();
    }

    $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $detailRedirectBase = 'project_detail.php?id=';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $desc  = isset($_POST['description']) ? trim($_POST['description']) : '';
    $date  = isset($_POST['date']) ? $_POST['date'] : '';

    // Validate date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        header(REDIRECT_PREFIX . $detailRedirectBase . $id . '&msg=invalid_date');
        exit();
    }

    $file_path = null;
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === 0) {
        $file    = $_FILES['project_file'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg', 'pdf', 'doc', 'docx', 'txt', 'zip'];

        if (in_array($ext, $allowed) && $file['size'] <= 20 * 1024 * 1024) {
            $new_name    = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $target_file = $target_dir . $new_name;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $file_path = $target_file;

                $oldStmt = $conn->prepare("SELECT project_image FROM posts WHERE id = ? AND user_email = ?");
                $oldStmt->bind_param("is", $id, $email);
                $oldStmt->execute();
                $oldRow = $oldStmt->get_result()->fetch_assoc();
                $oldStmt->close();

                if (!empty($oldRow['project_image']) && file_exists($oldRow['project_image']) && $oldRow['project_image'] !== $target_file) {
                    unlink($oldRow['project_image']);
                }
            }
        }
    }

    if ($file_path) {
        $stmt = $conn->prepare("UPDATE posts SET project_title=?, project_description=?, project_image=?, project_date=? WHERE id=? AND user_email=?");
        $stmt->bind_param("ssssis", $title, $desc, $file_path, $date, $id, $email);
    } else {
        $stmt = $conn->prepare("UPDATE posts SET project_title=?, project_description=?, project_date=? WHERE id=? AND user_email=?");
        $stmt->bind_param("sssis", $title, $desc, $date, $id, $email);
    }

    if ($stmt->execute()) {
        header(REDIRECT_PREFIX . $detailRedirectBase . $id . '&msg=updated');
    } else {
        header(REDIRECT_PREFIX . $detailRedirectBase . $id . '&msg=db_error');
    }
    $stmt->close();
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("SELECT project_image FROM posts WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['project_image']) && file_exists($row['project_image'])) {
        unlink($row['project_image']);
    }

    $del = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_email = ?");
    $del->bind_param("is", $id, $email);
    $del->execute();
    $del->close();

    header(REDIRECT_PREFIX . 'dashboard.php?page=projects&msg=deleted');
    exit();
}
