<?php
session_start();
include_once "connect.php";

function getFileType($ext) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $videoExtensions = ['mp4', 'webm', 'ogg'];
    $fileType = 'document';

    if (in_array($ext, $imageExtensions, true)) {
        $fileType = 'image';
    } elseif (in_array($ext, $videoExtensions, true)) {
        $fileType = 'video';
    } elseif ($ext === 'pdf') {
        $fileType = 'pdf';
    }

    return $fileType;
}

if (!isset($_SESSION['Email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['Email'];
$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg   = isset($_GET['msg']) && in_array($_GET['msg'], ['updated', 'invalid_date', 'db_error'], true) ? $_GET['msg'] : '';

// Ambil data user
$userStmt = $conn->prepare("SELECT * FROM users WHERE Email = ? LIMIT 1");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Ambil data project (hanya milik user yg login)
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_email = ?");
$stmt->bind_param("is", $id, $email);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>
    <title>Tidak Ditemukan</title>
    <link rel='stylesheet' href='dashboard.css'>
    </head><body>
    <div style='text-align:center;padding:80px 20px;'>
        <h2>Project tidak ditemukan</h2>
        <p>Project tidak ada atau Anda tidak memiliki akses.</p>
        <a href='dashboard.php?page=home' style='color:#667eea;'>&#8592; Kembali ke Dashboard</a>
    </div></body></html>";
    exit();
}

$ext      = strtolower(pathinfo($project['project_image'], PATHINFO_EXTENSION));
$fileType = getFileType($ext);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail - <?php echo htmlspecialchars($project['project_title']); ?> | Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <!-- TOP NAVIGATION -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-code"></i>
                <span>portfolio saya</span>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php?page=home" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="dashboard.php?page=projects" class="nav-link"><i class="fas fa-folder"></i> Projects</a>
                <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <div class="nav-user">
                <span><?php echo htmlspecialchars($user['FirstName']); ?></span>
                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
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
        <?php if ($msg === 'updated'): ?>
            <div class="alert alert-success" id="flashAlert">
                <i class="fas fa-check-circle"></i> Project berhasil diperbarui!
            </div>
        <?php endif; ?>

        <div class="profile-edit-container">
            <div class="profile-edit-header">
                <h2><i class="fas fa-folder-open"></i> Project Detail</h2>
                <a href="dashboard.php?page=home" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <!-- Info Judul & Meta -->
            <div style="margin-bottom:25px;">
                <h1 style="color:var(--dark);margin-bottom:10px;">
                    <?php echo htmlspecialchars($project['project_title']); ?>
                </h1>
                <div style="color:var(--gray);display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
                    <span><i class="far fa-calendar"></i> <?php echo date("d F Y", strtotime($project['project_date'])); ?></span>
                    <?php if (!empty($project['created_at'])): ?>
                    <span><i class="far fa-clock"></i> <?php echo date("H:i", strtotime($project['created_at'])); ?></span>
                    <?php endif; ?>
                    <span class="type-badge <?php echo $fileType; ?>">
                        <i class="fas fa-file"></i> <?php echo ucfirst($fileType); ?>
                    </span>
                </div>
            </div>

            <!-- Media Preview -->
            <div style="margin-bottom:30px; background:var(--light); padding:10px; border-radius:12px; text-align:center;">
                <?php if (!empty($project['project_image'])): ?>
                    <?php if ($fileType === 'image'): ?>
                        <img src="<?php echo htmlspecialchars($project['project_image']); ?>"
                             alt="<?php echo htmlspecialchars($project['project_title']); ?>"
                             style="max-width:100%; height:auto; border-radius:8px; display:inline-block;">
                    <?php elseif ($fileType === 'video'): ?>
                        <video controls style="max-width:100%; height:auto; border-radius:8px; display:inline-block;">
                            <source src="<?php echo htmlspecialchars($project['project_image']); ?>" type="video/<?php echo $ext; ?>">
                            <track default kind="captions" srclang="id" label="Tanpa subtitle" src="data:text/vtt;base64,PC0tLQ0KDQo="></track>
                            Browser Anda tidak mendukung video.
                        </video>
                    <?php elseif ($fileType === 'pdf'): ?>
                        <iframe src="<?php echo htmlspecialchars($project['project_image']); ?>"
                                title="<?php echo htmlspecialchars($project['project_title']); ?>"
                                style="width:100%;height:600px;border:1px solid #ddd;border-radius:12px;"></iframe>
                        <div style="margin-top:15px;text-align:center;">
                            <a href="<?php echo htmlspecialchars($project['project_image']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Buka PDF di Tab Baru
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:60px;background:var(--light);border-radius:12px;">
                            <i class="fas fa-file-alt" style="font-size:5rem;color:var(--primary);margin-bottom:20px;"></i>
                            <h3>Dokumen: <?php echo strtoupper($ext); ?></h3>
                            <p style="color:var(--gray);margin:15px 0;">File ini tidak dapat ditampilkan langsung di browser.</p>
                            <a href="<?php echo htmlspecialchars($project['project_image']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download / Lihat Dokumen
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="width:100%;height:300px;background:var(--light);display:flex;align-items:center;justify-content:center;border-radius:12px;">
                        <i class="fas fa-image" style="font-size:4rem;color:#ddd;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Deskripsi -->
            <div style="margin-bottom:30px;padding:25px;background:var(--light);border-radius:12px;border-left:4px solid var(--primary);">
                <h3 style="margin-bottom:15px;color:var(--dark);">
                    <i class="fas fa-align-left"></i> Deskripsi
                </h3>
                <div style="line-height:1.9;color:var(--gray);font-size:1.05rem;">
                    <?php echo nl2br(htmlspecialchars($project['project_description'])); ?>
                </div>
            </div>

            <!-- Tombol Edit -->
            <div style="display:flex;justify-content:flex-end;margin-bottom:30px;">
                <button type="button" id="btn-open-edit" class="btn btn-primary" style="min-width:140px;justify-content:center;">
                    <i class="fas fa-edit"></i> Edit Project
                </button>
            </div>

            <!-- Form Edit (tersembunyi) -->
            <div id="project-edit-section" style="display:none;margin-top:40px;padding-top:35px;border-top:3px solid #e2e8f0;">
                <h3 style="color:var(--primary);margin-bottom:25px;">
                    <i class="fas fa-edit"></i> Edit Project
                </h3>
                <form action="process_dashboard.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $project['id']; ?>">


                    <div class="form-row">
                        <div class="form-group">
                            <label for="projectTitle">Project Title *</label>
                            <input type="text" id="projectTitle" name="title" value="<?php echo htmlspecialchars($project['project_title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="projectDate">Project Date *</label>
                            <input type="date" id="projectDate" name="date" value="<?php echo htmlspecialchars($project['project_date']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="projectDescription">Description *</label>
                        <textarea id="projectDescription" name="description" rows="6" required><?php echo htmlspecialchars($project['project_description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="projectFile">Ganti File <small style="color:var(--gray);">(Opsional)</small></label>
                        <input type="file" id="projectFile" name="project_file" accept="image/*,video/*,.pdf,.doc,.docx,.txt,.zip">
                        <small>
                            Biarkan kosong jika tidak ingin mengubah file.
                            <?php if (!empty($project['project_image'])): ?>
                                File saat ini: <strong><?php echo htmlspecialchars(basename($project['project_image'])); ?></strong>
                            <?php endif; ?>
                        </small>
                    </div>

                    <div class="form-actions" style="justify-content:flex-end;gap:10px;">
                        <button type="submit" name="update_project" class="btn btn-primary" style="min-width:140px;justify-content:center;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" id="btn-cancel-edit" class="btn btn-secondary" style="min-width:140px;justify-content:center;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <a href="process_dashboard.php?delete=<?php echo $project['id']; ?>"
                           class="btn btn-danger"
                           style="min-width:140px;justify-content:center;"
                           onclick="return confirm('Yakin ingin menghapus project ini? Tindakan ini tidak dapat dibatalkan.')">
                            <i class="fas fa-trash"></i> Delete Project
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const btnOpenEdit   = document.getElementById('btn-open-edit');
        const btnCancelEdit = document.getElementById('btn-cancel-edit');
        const editSection   = document.getElementById('project-edit-section');

        if (btnOpenEdit && editSection) {
            btnOpenEdit.addEventListener('click', function () {
                editSection.style.display = 'block';
                this.style.display = 'none';
                editSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (btnCancelEdit && editSection && btnOpenEdit) {
            btnCancelEdit.addEventListener('click', function () {
                editSection.style.display = 'none';
                btnOpenEdit.style.display = 'inline-flex';
            });
        }

        const flashAlert = document.getElementById('flashAlert');
        if (flashAlert) {
            setTimeout(function () {
                flashAlert.style.transition = 'opacity 0.5s';
                flashAlert.style.opacity = '0';
                setTimeout(function () { flashAlert.remove(); }, 500);
            }, 4000);
        }
    </script>
</body>
</html>
