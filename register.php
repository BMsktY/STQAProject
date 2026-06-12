<?php
include_once 'connect.php';

$allowedDomains = ["belajar.ac.id"];

function clean($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function isValidDomain($email, $allowedDomains) {
    $parts = explode('@', $email);
    return count($parts) === 2 && in_array($parts[1], $allowedDomains);
}

function isValidWhatsapp($phone) {
    return preg_match('/^08\d{8,11}$/', $phone);
}

if(isset($_POST['signUp'])){
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: index.php?error=csrf_failed"); exit();
    }

    $FirstName       = clean($_POST['fName']);
    $LastName        = clean($_POST['lName']);
    $Email           = clean($_POST['email']);
    $Whatsapp        = clean($_POST['whatsapp']);
    $Password        = $_POST['password'];
    $ConfirmPassword = $_POST['confirm_password'] ?? '';
    $Agree           = isset($_POST['agree']) ? 1 : 0;

    if(empty($FirstName) || empty($LastName) || empty($Email) || empty($Whatsapp) || empty($Password)){
        header("Location: index.php?error=empty_fields"); exit();
    }

    if(!$Agree){
        header("Location: index.php?error=terms_not_accepted"); exit();
    }

    if(!filter_var($Email, FILTER_VALIDATE_EMAIL)){
        header("Location: index.php?error=invalid_email"); exit();
    }

    if(!isValidDomain($Email, $allowedDomains)){
        header("Location: index.php?error=invalid_domain"); exit();
    }

    if(!isValidWhatsapp($Whatsapp)){
        header("Location: index.php?error=invalid_whatsapp"); exit();
    }

    if(empty($ConfirmPassword)){
        header("Location: index.php?error=empty_confirm_password"); exit();
    }

    if($Password !== $ConfirmPassword){
        header("Location: index.php?error=password_mismatch"); exit();
    }

    if (strlen($Password) < 8 || !preg_match('/[A-Z]/', $Password) || !preg_match('/\d/', $Password) || !preg_match('/[@$!%*?&]/', $Password)) {
        header("Location: index.php?error=weak_password"); exit();
    }

    $stmt = $conn->prepare("SELECT Email FROM users WHERE Email = ?");
    $stmt->bind_param("s", $Email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: index.php?error=email_exists"); exit();
    } else {
        $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);
        $insertQuery = "INSERT INTO users (FirstName, LastName, Email, Whatsapp, Password, is_active) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssss", $FirstName, $LastName, $Email, $Whatsapp, $PasswordHash);
        if($stmt->execute()){
            header("Location: index.php?success=registered");
        } else {
            header("Location: index.php?error=register_failed");
        }
    }
    $stmt->close();
    exit();
}

if(isset($_POST['signIn'])){
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: index.php?error=csrf_failed"); exit();
    }

    $Email    = clean($_POST['email']);
    $Password = $_POST['password'];

    if(empty($Email) || empty($Password)){
        header("Location: index.php?error=empty_fields"); exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
    $stmt->bind_param("s", $Email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if($row['is_active'] == 1 && password_verify($Password, $row['Password'])){
            $_SESSION['Email']     = $row['Email'];
            $_SESSION['FirstName'] = $row['FirstName'];
            $_SESSION['LastName']  = $row['LastName'];
            session_regenerate_id(true);
            header("Location: dashboard.php");
        } else {
            header("Location: index.php?error=invalid_login");
        }
    } else {
        header("Location: index.php?error=invalid_login");
    }
    $stmt->close();
    exit();
}
exit();
