<?php
session_start();
include_once "connect.php";

if(!isset($_SESSION['Email'])){
    header("Location: index.php");
    exit();
}

$email = $_SESSION['Email'];
$success = '';
$error = '';

if(isset($_POST['update_profile'])){
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName  = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $bio       = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $instagram = isset($_POST['instagram']) ? trim($_POST['instagram']) : '';
    $github    = isset($_POST['github']) ? trim($_POST['github']) : '';
    $linkedin  = isset($_POST['linkedin']) ? trim($_POST['linkedin']) : '';
    $whatsapp  = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';

    $profileImage = '';
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0){
        $target_dir = "uploads/profiles/";
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        
        $allowed = ['jpg', 'jpeg'];

        if(in_array(strtolower($ext), $allowed)){
            $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '', $email) . ".jpg";
            $target_file = $target_dir . $filename;

            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)){
                $oldStmt = $conn->prepare("SELECT profile_image FROM users WHERE Email = ?");
                $oldStmt->bind_param("s", $email);
                $oldStmt->execute();
                $oldRow = $oldStmt->get_result()->fetch_assoc();
                
                if(!empty($oldRow['profile_image']) && file_exists($oldRow['profile_image']) && strpos($oldRow['profile_image'], 'default') === false){
                    unlink($oldRow['profile_image']);
                }
                $profileImage = $target_file;
            } else {
                $error = "Gagal mengupload file.";
            }
        } else {
            $error = "Format file salah! Hanya JPG/JPEG yang diperbolehkan.";
        }
    }

    if($error === '') {
        if(!empty($profileImage)){
            $stmt = $conn->prepare("UPDATE users SET FirstName=?, LastName=?, bio=?, instagram=?, github=?, linkedin=?, Whatsapp=?, profile_image=? WHERE Email=?");
            $stmt->bind_param("sssssssss", $firstName, $lastName, $bio, $instagram, $github, $linkedin, $whatsapp, $profileImage, $email);
        } else {
            $stmt = $conn->prepare("UPDATE users SET FirstName=?, LastName=?, bio=?, instagram=?, github=?, linkedin=?, Whatsapp=? WHERE Email=?");
            $stmt->bind_param("ssssssss", $firstName, $lastName, $bio, $instagram, $github, $linkedin, $whatsapp, $email);
        }

        if($stmt->execute()){
            $success = "Profil berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui profil.";
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE Email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="dashboard.css">
    
    <style>
        :root {
            --primary-login: rgb(125, 125, 235);
            --secondary-login: hsl(327, 90%, 28%);
        }

        .btn-primary, .btn-upload {
            background: var(--primary-login) !important;
        }
        .btn-primary:hover, .btn-upload:hover {
            background: var(--secondary-login) !important;
        }

        .current-image img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 12px;
        }

        .avatar-fallback {
            width: 150px;
            height: 150px;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            color: #ccc;
            font-size: 80px;
        }

        .upload-controls input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-code"></i>
                <span>MyPortfolio</span>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php?page=home" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="dashboard.php?page=projects" class="nav-link">
                    <i class="fas fa-folder"></i> Projects
                </a>
                <a href="profile.php" class="nav-link active">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            <div class="nav-user">
                <span><?php echo htmlspecialchars($user['FirstName']); ?></span>
                <?php if(!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="User">
                <?php else: ?>
                    <div style="width:35px;height:35px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:white;">
                        <i class="fas fa-user" style="font-size:1rem;"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="profile-page">
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="profile-edit-container">
                <div class="profile-edit-header">
                    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                    <a href="dashboard.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <form method="POST" enctype="multipart/form-data" class="profile-form">
                    <div class="form-section">
                        <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                        
                        <div class="profile-image-upload">
                            <div class="current-image">
                                <?php if(!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>"
                                         alt="Current Profile" id="previewImage">
                                <?php else: ?>
                                    <div class="avatar-fallback" id="previewImageContainer">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="upload-controls">
                                <label for="profileImageInput" class="btn-upload">
                                    <i class="fas fa-camera"></i> Upload Photo
                                </label>
                                <input type="file" id="profileImageInput" name="profile_image" accept=".jpg,.jpeg,image/jpeg" onchange="previewProfileImage(this)">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name *</label>
                                <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($user['FirstName']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name *</label>
                                <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($user['LastName']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profileEmail">Email</label>
                            <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($user['Email']); ?>" disabled style="background:#f5f5f5; cursor:not-allowed;">
                            <small>Email tidak dapat diubah</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="profileBio">Bio</label>
                            <textarea id="profileBio" name="bio" rows="4" placeholder="Ceritakan tentang diri Anda..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-link"></i> Social Media Links</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="instagramInput"><i class="fab fa-instagram" style="color:#e4405f;"></i> Instagram</label>
                                <input type="url" id="instagramInput" name="instagram" value="<?php echo htmlspecialchars($user['instagram'] ?? ''); ?>" placeholder="https://instagram.com/username">
                            </div>
                            <div class="form-group">
                                <label for="githubInput"><i class="fab fa-github" style="color:#333;"></i> GitHub</label>
                                <input type="url" id="githubInput" name="github" value="<?php echo htmlspecialchars($user['github'] ?? ''); ?>" placeholder="https://github.com/username">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="linkedinInput"><i class="fab fa-linkedin" style="color:#0077b5;"></i> LinkedIn</label>
                                <input type="url" id="linkedinInput" name="linkedin" value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/username">
                            </div>
                            <div class="form-group">
                                <label for="whatsappInput"><i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp</label>
                                <input type="text" id="whatsappInput" name="whatsapp" value="<?php echo htmlspecialchars($user['Whatsapp'] ?? ''); ?>" placeholder="08123456789">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary btn-large">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-large">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewProfileImage(input) {
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewEl = document.getElementById('previewImage');
                    
                    if(previewEl.tagName === 'DIV') {
                        const img = document.createElement('img');
                        img.id = 'previewImage';
                        img.src = e.target.result;
                        img.alt = 'Profile Preview';
                        previewEl.parentNode.replaceChild(img, previewEl);
                    } else {
                        previewEl.src = e.target.result;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
