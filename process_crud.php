<?php
session_start();
include_once "connect.php";

if (!isset($_SESSION['Email'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: crud.php?error=csrf_failed");
        exit();
    }

    $user_email  = $_SESSION['Email'];
    $id          = (isset($_POST['id']) && !empty($_POST['id'])) ? (int)$_POST['id'] : null;
    $title       = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $image       = isset($_POST['image']) ? trim($_POST['image']) : '';
    $date        = isset($_POST['date']) ? $_POST['date'] : '';

    // Validate date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        header("Location: crud.php?error=invalid_date");
        exit();
    }

    if ($id) {
        $sql  = "UPDATE posts SET project_title=?, project_description=?, project_image=?, project_date=? WHERE id=? AND user_email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $title, $description, $image, $date, $id, $user_email);
        if ($stmt->execute()) {
            header("Location: crud.php?updated=1");
        } else {
            header("Location: crud.php?error=db_error");
        }
    } else {
        $sql  = "INSERT INTO posts (user_email, project_title, project_description, project_image, project_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $user_email, $title, $description, $image, $date);
        if ($stmt->execute()) {
            header("Location: crud.php?created=1");
        } else {
            header("Location: crud.php?error=db_error");
        }
    }
    $stmt->close();
    exit();
}
