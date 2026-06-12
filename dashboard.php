<?php
session_start();
include_once "connect.php";
if(!isset($_SESSION['Email'])){
    header("Location: index.php");
    exit();
}
$email = $_SESSION['Email'];
$page = isset($_GET['page']) && in_array($_GET['page'], ['home', 'projects'], true) ? $_GET['page'] : 'home';
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$sortParam = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'asc' : 'desc';
$sort = $sortParam === 'asc' ? 'ASC' : 'DESC';

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE Email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil semua project
$pStmt = $conn->prepare("SELECT * FROM posts WHERE user_email = ? ORDER BY project_date $sort");
$pStmt->bind_param("s", $email);
$pStmt->execute();
$projects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pStmt->close();

// Fungsi batasi deskripsi 20 kata
function limitWords($text, $limit = 20) {
    $words = preg_split('/\s+/', trim(strip_tags($text))) ?: [];
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
    return trim($text);
}

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .greeting-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .greeting-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .greeting-text {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .greeting-text span {
            background: linear-gradient(to right, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .greeting-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .profile-card-compact {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.3);
        }
        
        .profile-avatar-compact {
            position: relative;
            flex-shrink: 0;
        }
        
        .profile-avatar-compact img,
        .profile-avatar-compact .avatar-fallback {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.5);
            object-fit: cover;
        }
        
        .avatar-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            color: white;
            font-size: 1.8rem;
        }
        
        .profile-info-compact h3 {
            font-size: 1.2rem;
            margin-bottom: 3px;
        }
        
        .profile-info-compact p {
            opacity: 0.85;
            font-size: 0.9rem;
            margin: 2px 0;
        }
        
        .timeline-new {
            position: relative;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px 0;
        }
        
        .timeline-new::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            transform: translateX(-50%);
            border-radius: 3px;
        }
        
        .timeline-item-new {
            position: relative;
            width: 100%;
            display: flex;
            align-items: flex-start;
            margin-bottom: 40px;
            padding: 0 30px;
        }
        
        .timeline-marker-new {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 24px;
            background: white;
            border: 4px solid var(--primary);
            border-radius: 50%;
            z-index: 10;
            box-shadow: 0 0 10px rgba(102,126,234,0.4);
            transition: all 0.3s;
        }
        
        .timeline-item-new:hover .timeline-marker-new {
            background: var(--primary);
            transform: translateX(-50%) scale(1.2);
        }
        
        .timeline-date-side {
            width: 50%;
            text-align: right;
            padding-right: 50px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .date-badge-new {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 2px solid var(--primary);
        }
        
        .date-badge-new i {
            font-size: 1.1rem;
        }
        
        .timeline-card-side {
            width: 50%;
            padding-left: 50px;
        }

        .project-card-new {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .project-card-new:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .card-header-new {
            padding: 20px 20px 10px;
        }
        
        .card-header-new h3 {
            color: var(--dark);
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .type-badge-new {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-badge-new.image { background: #c6f6d5; color: #22543d; }
        .type-badge-new.video { background: #feebc8; color: #744210; }
        .type-badge-new.pdf { background: #fed7d7; color: #822727; }
        .type-badge-new.document { background: #e6fffa; color: #234e52; }

        .media-preview-new {
            margin: 0 20px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .media-preview-new img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .media-preview-new img:hover {
            transform: scale(1.02);
        }
        
        .media-preview-new video {
            width: 100%;
            max-height: 250px;
            background: #000;
        }
        
        .media-preview-new iframe {
            width: 100%;
            height: 250px;
            border: 1px solid #ddd;
        }
        
        /* Deskripsi */
        .card-description {
            padding: 15px 20px;
            color: var(--gray);
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        /* Tombol Lihat Detail (Tanpa Icon Mata) */
        .btn-detail-new {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            margin: 0 20px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-detail-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        
        .dashboard-footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 30px 0;
            margin-top: 60px;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px;
        }
        
        .footer-brand {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .footer-text {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .footer-social {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .footer-social a {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light);
            color: var(--dark);
            border-radius: 50%;
            text-decoration: none;
        }
        
        .footer-social a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .footer-copyright {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        /* Project Slider */
        .project-slider-wrapper {
            max-width: 900px;
            margin: 0 auto 40px auto;
            padding: 0 20px;
        }
        .slider-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--dark);
        }
        .slider-container {
            position: relative;
        }
        .project-slider {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding-bottom: 15px;
            scrollbar-width: none;
            scroll-behavior: smooth;
        }
        .project-slider::-webkit-scrollbar {
            display: none;
        }
        .slider-btn {
            position: absolute;
            top: calc(50% - 7.5px);
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: var(--primary);
            z-index: 10;
            transition: all 0.3s;
        }
        .slider-btn:hover {
            background: var(--primary);
            color: white;
        }
        .slider-btn.prev {
            left: -18px;
        }
        .slider-btn.next {
            right: -18px;
        }
        .slider-item {
            flex: 0 0 calc(33.333% - 10px);
            min-width: 200px;
            height: 140px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            text-decoration: none;
            background: white;
            border: 1px solid #e2e8f0;
            transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.4s ease;
        }
        .slider-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .slider-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .slider-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary);
        }
        .slider-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px 10px 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
        }
        .slider-caption h4 {
            margin: 0;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .slider-item {
                flex: 0 0 calc(50% - 10px);
            }
            .timeline-new::before {
                left: 30px;
            }
            
            .timeline-marker-new {
                left: 30px;
            }
            
            .timeline-item-new {
                flex-direction: column;
                padding-left: 60px;
                padding-right: 0;
            }
            
            .timeline-date-side {
                width: 100%;
                text-align: left;
                padding-right: 0;
                padding-bottom: 10px;
                justify-content: flex-start;
            }
            
            .timeline-card-side {
                width: 100%;
                padding-left: 0;
            }
            
            .greeting-text {
                font-size: 1.5rem;
            }
        }
    </style>
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
                <a href="dashboard.php?page=home" class="nav-link <?php echo $page == 'home' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="dashboard.php?page=projects" class="nav-link <?php echo $page == 'projects' ? 'active' : ''; ?>">
                    <i class="fas fa-folder"></i> Projects
                </a>
                <a href="profile.php" class="nav-link">
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

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <!-- Notifikasi -->
        <?php if($msg !== ''): ?>
            <?php if($msg === 'success'): ?>
                <div class="alert alert-success" id="flashAlertSuccess">
                    <i class="fas fa-check-circle"></i> Project berhasil ditambahkan!
                </div>
            <?php elseif($msg === 'deleted'): ?>
                <div class="alert alert-danger" id="flashAlertDeleted">
                    <i class="fas fa-trash-alt"></i> Project berhasil dihapus!
                </div>
            <?php elseif($msg === 'updated'): ?>
                <div class="alert alert-success" id="flashAlertUpdated">
                    <i class="fas fa-check-circle"></i> Project berhasil diperbarui!
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($page == 'home'): ?>
            <div class="greeting-section">
                <div class="greeting-text">
                    Hi, <span><?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?>!</span>
                </div>
                <div class="greeting-subtitle">
                    Selamat datang di penyimpanan project anda.
                </div>
                
                <div class="profile-card-compact">
                    <div class="profile-avatar-compact">
                        <?php if(!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                        <?php else: ?>
                            <div class="avatar-fallback">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        <?php endif; ?>
                        <a href="profile.php" class="edit-avatar-btn" title="Edit Profile" style="position:absolute;bottom:0;right:0;width:25px;height:25px;background:white;color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;box-shadow:0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fas fa-camera"></i>
                        </a>
                    </div>
                    <div class="profile-info-compact">
                        <h3><?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['Email']); ?></p>
                        <p><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'Belum terdapat bio yang dimasukan'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Project Slider / Carousel -->
            <?php if(count($projects) > 0): ?>
            <div class="project-slider-wrapper">
                <div class="slider-header">
                    <h3 style="margin: 0; font-size: 1.2rem;">Project Highlights</h3>
                </div>
                <div class="slider-container">
                    <button class="slider-btn prev" onclick="slideProject(-1)"><i class="fas fa-chevron-left"></i></button>
                    <div class="project-slider" id="projectSlider">
                        <?php
                        // Menampilkan maksimal 6 project terbaru di slider
                        foreach (array_slice($projects, 0, 6) as $sp):
                            $ext = strtolower(pathinfo($sp['project_image'], PATHINFO_EXTENSION));
                            $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                        ?>
                            <a href="project_detail.php?id=<?php echo $sp['id']; ?>" class="slider-item">
                                <?php if(!empty($sp['project_image']) && $isImg): ?>
                                    <img src="<?php echo htmlspecialchars($sp['project_image']); ?>" alt="Project">
                                <?php else: ?>
                                    <div class="slider-placeholder"><i class="fas fa-file-alt"></i></div>
                                <?php endif; ?>
                                <div class="slider-caption">
                                    <h4><?php echo htmlspecialchars($sp['project_title']); ?></h4>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-btn next" onclick="slideProject(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <?php endif; ?>

            <div class="timeline-wrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; max-width: 900px; margin: 0 auto; padding: 0 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">
                        <i class="fas fa-stream"></i> Project Timeline
                    </h2>
                    <form action="dashboard.php" method="GET" style="display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="page" value="home">
                        <label for="sort_timeline" style="font-weight: 500; font-size: 0.9rem;">Urutkan:</label>
                        <select name="sort" id="sort_timeline" onchange="this.form.submit()" style="padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; outline: none;">
                            <option value="desc" <?php echo $sortParam === 'desc' ? 'selected' : ''; ?>>Terbaru ke Terlama</option>
                            <option value="asc" <?php echo $sortParam === 'asc' ? 'selected' : ''; ?>>Terlama ke Terbaru</option>
                        </select>
                    </form>
                </div>
                
                <?php if(count($projects) > 0): ?>
                    <div class="timeline-new">
                        <?php foreach($projects as $p):
                            $ext = strtolower(pathinfo($p['project_image'], PATHINFO_EXTENSION));
                            $fileType = getFileType($ext);
                        ?>
                            <div class="timeline-item-new">
                                <div class="timeline-marker-new"></div>
                                
                                <div class="timeline-date-side">
                                    <div class="date-badge-new">
                                        <i class="far fa-calendar-alt"></i>
                                        <span><?php echo date('d M Y', strtotime($p['project_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="timeline-card-side">
                                    <div class="project-card-new">
                                        <div class="card-header-new">
                                            <h3><?php echo htmlspecialchars($p['project_title']); ?></h3>
                                            <span class="type-badge-new <?php echo $fileType; ?>">
                                                <i class="fas fa-file"></i> <?php echo ucfirst($fileType); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if(!empty($p['project_image'])): ?>
                                            <div class="media-preview-new">
                                                <?php if($fileType == 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($p['project_image']); ?>"
                                                         alt="Project">
                                                <?php elseif($fileType == 'video'): ?>
                                                    <video controls>
                                                        <source src="<?php echo htmlspecialchars($p['project_image']); ?>"
                                                                type="video/<?php echo $ext; ?>">
                                                        <track default kind="captions" srclang="id" label="Tanpa subtitle" src="data:text/vtt;base64,PC0tLQ0KDQo="></track>
                                                        Browser Anda tidak mendukung video.
                                                    </video>
                                                <?php elseif($fileType == 'pdf'): ?>
                                                    <iframe src="<?php echo htmlspecialchars($p['project_image']); ?>"
                                                            type="application/pdf"
                                                            title="Project PDF preview">
                                                    </iframe>
                                                <?php else: ?>
                                                    <div style="padding:40px;text-align:center;background:var(--light);">
                                                        <i class="fas fa-file-alt" style="font-size:3rem;color:var(--primary);"></i>
                                                        <p style="margin-top:10px;color:var(--gray);">
                                                            Dokumen: <?php echo strtoupper($ext); ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="card-description">
                                            <?php echo htmlspecialchars(limitWords($p['project_description'], 20)); ?>
                                        </div>

                                        <a href="project_detail.php?id=<?php echo $p['id']; ?>" class="btn-detail-new">
                                            Lihat Detail
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-timeline">
                        <i class="fas fa-folder-open"></i>
                        <h3>Belum Ada Project</h3>
                        <p>Silakan tambahkan project pertama Anda di menu Projects</p>
                        <a href="dashboard.php?page=projects" class="btn-primary">
                            <i class="fas fa-plus"></i> Tambah Project
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif($page == 'projects'): ?>
            <div class="projects-page">
                <div class="page-header">
                    <h2><i class="fas fa-folder-open"></i> Manage Projects</h2>
                    <button class="btn-add" onclick="openModal()">
                        <i class="fas fa-plus"></i> Add New Project
                    </button>
                </div>
                <div class="projects-grid">
                    <?php if(count($projects) > 0): ?>
                        <?php foreach($projects as $p):
                            $ext = strtolower(pathinfo($p['project_image'], PATHINFO_EXTENSION));
                            $fileType = getFileType($ext);
                        ?>
                            <div class="project-card">
                                <div class="card-media">
                                    <?php if(!empty($p['project_image'])): ?>
                                        <?php if($fileType == 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($p['project_image']); ?>" alt="Project">
                                        <?php elseif($fileType == 'video'): ?>
                                            <video controls>
                                                <source src="<?php echo htmlspecialchars($p['project_image']); ?>" type="video/<?php echo $ext; ?>">
                                                <track default kind="captions" srclang="id" label="Tanpa subtitle" src="data:text/vtt;base64,PC0tLQ0KDQo="></track>
                                            </video>
                                        <?php elseif($fileType == 'pdf'): ?>
                                            <div class="pdf-icon">
                                                <i class="fas fa-file-pdf"></i>
                                                <span>PDF</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="doc-icon">
                                                <i class="fas fa-file-alt"></i>
                                                <span><?php echo strtoupper($ext); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="no-media">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-content">
                                    <h4><?php echo htmlspecialchars($p['project_title']); ?></h4>
                                    <p class="description-clamp">
                                        <?php echo htmlspecialchars(limitWords($p['project_description'], 20)); ?>
                                    </p>
                                    <div class="card-meta">
                                        <span class="date">
                                            <i class="far fa-calendar"></i>
                                            <?php echo date('d M Y', strtotime($p['project_date'])); ?>
                                        </span>
                                        <span class="type-badge <?php echo $fileType; ?>">
                                            <i class="fas fa-file"></i> <?php echo ucfirst($fileType); ?>
                                        </span>
                                    </div>
                                    <div class="card-actions">
                                        <a href="project_detail.php?id=<?php echo $p['id']; ?>" class="btn-action view">
                                            Lihat Detail
                                        </a>
                                        <a href="process_dashboard.php?delete=<?php echo $p['id']; ?>"
                                           class="btn-action delete"
                                           onclick="return confirm('Yakin ingin menghapus project ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-projects">
                            <i class="fas fa-folder-open"></i>
                            <p>Belum ada project</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal" id="addProjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Project</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form action="process_dashboard.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="projectTitle">Project Title *</label>
                        <input type="text" id="projectTitle" name="title" required placeholder="Contoh: Website E-Commerce">
                    </div>
                    <div class="form-group">
                        <label for="projectDescription">Description *</label>
                        <textarea id="projectDescription" name="description" rows="4" required placeholder="Jelaskan tentang project Anda..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="projectFile">Upload File *</label>
                        <input type="file" name="file" id="projectFile" accept="image/*,video/*,.pdf" required onchange="previewFileName()">
                        <small>Format: JPG, PNG, WEBP, GIF, MP4, WEBM, OGG, PDF (Maks 20MB)</small>
                        <div id="fileNamePreview" style="margin-top:8px; font-weight:600; color:#667eea;"></div>
                    </div>
                    <div class="form-group">
                        <label for="projectDate">Project Date *</label>
                        <input type="date" id="projectDate" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="add_project" class="btn btn-primary">Save Project</button>
                </div>
            </form>
        </div>
    </div>


    <footer class="dashboard-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <i class="fas fa-code"></i> Portfolio saya.
            </div>
            <div class="footer-text">
                Platform ini dibuat untuk memenuhi kebutuhan tugas dan project.
            </div>
            <div class="footer-social">
                <?php if(!empty($user['github'])): ?>
                    <a href="<?php echo htmlspecialchars($user['github']); ?>" target="_blank">
                        <i class="fab fa-github"></i>
                    </a>
                <?php endif; ?>
                <?php if(!empty($user['linkedin'])): ?>
                    <a href="<?php echo htmlspecialchars($user['linkedin']); ?>" target="_blank">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                <?php endif; ?>
                <?php if(!empty($user['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($user['instagram']); ?>" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        function openModal() {
            document.getElementById('addProjectModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            document.getElementById('addProjectModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        function previewFileName() {
            const fileInput = document.getElementById('projectFile');
            const preview = document.getElementById('fileNamePreview');
            if(fileInput.files && fileInput.files[0]) {
                preview.textContent = 'File: ' + fileInput.files[0].name;
            }
        }

        
        window.onclick = function(event) {
            const modal = document.getElementById('addProjectModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        const flashAlert = document.querySelector('[id^="flashAlert"]');
        if(flashAlert) {
            setTimeout(function() {
                flashAlert.style.transition = 'opacity 0.5s';
                flashAlert.style.opacity = '0';
                setTimeout(function() { flashAlert.remove(); }, 500);
            }, 4000);
        }

        function slideProject(direction) {
            const slider = document.getElementById('projectSlider');
            if(slider && slider.children.length > 0) {
                const item = slider.children[0];
                const style = window.getComputedStyle(slider);
                const gap = parseInt(style.gap) || 15;
                const scrollAmount = item.offsetWidth + gap;
                slider.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
