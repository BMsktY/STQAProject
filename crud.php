<?php
session_start();
include_once "connect.php";

if(!isset($_SESSION['Email'])){
    header("Location: index.php");
    exit();
}

$email = $_SESSION['Email'];
$success_message = '';
$error_message = '';

if (isset($_GET['error']) && $_GET['error'] === 'csrf_failed') {
    $error_message = "Permintaan tidak sah (CSRF Token Invalid). Silakan coba lagi.";
}
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success_message = "Project berhasil diperbarui!";
}
if (isset($_GET['created']) && $_GET['created'] == '1') {
    $success_message = "Project berhasil ditambahkan!";
}

// Handle Delete
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $deleteStmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_email = ?");
    $deleteStmt->bind_param("is", $id, $email);
    if($deleteStmt->execute()){
        $success_message = "Project berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus project.";
    }
    $deleteStmt->close();
}

$editData = null;
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_email = ?");
    $editStmt->bind_param("is", $id, $email);
    $editStmt->execute();
    $result = $editStmt->get_result();
    $editData = $result->fetch_assoc();
    $editStmt->close();
}

$projects = [];
$sortParam = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'asc' : 'desc';
$sort = $sortParam === 'asc' ? 'ASC' : 'DESC';
$projectStmt = $conn->prepare("SELECT * FROM posts WHERE user_email = ? ORDER BY project_date $sort");
$projectStmt->bind_param("s", $email);
$projectStmt->execute();
$resultProjects = $projectStmt->get_result();
$projects = $resultProjects->fetch_all(MYSQLI_ASSOC);
$projectStmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Projects - Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        body {
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            min-height: 100vh;
        }
        .main-header {
            background: #7d7deb;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }
        .crud-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .page-title {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-section h3 {
            color: #7d7deb;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #7d7deb;
            color: white;
        }
        .btn-primary:hover {
            background: #5a5ad4;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        /* Table Section */
        .table-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .table-section h3 {
            color: #7d7deb;
            margin-bottom: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table thead {
            background: #7d7deb;
            color: white;
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        .data-table img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 4px;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo"><i class="fas fa-tasks"></i> CRUD Manager</div>
        <nav class="nav-links">
            <a href="dashboard.php">Home</a>
            <a href="crud.php" class="active">CRUD Projects</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="crud-container">
        <h1 class="page-title">
            <i class="fas fa-project-diagram"></i>
            Project Management - CRUD Demo
        </h1>

        <?php if($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="form-section">
            <h3>
                <i class="fas fa-<?php echo $editData ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $editData ? 'Edit Project' : 'Tambah Project Baru'; ?>
            </h3>
            <form action="process_crud.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)($editData['id'] ?? '')); ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Judul Project *</label>
                        <input type="text" id="title" name="title"
                               value="<?php echo htmlspecialchars($editData['project_title'] ?? ''); ?>"
                               placeholder="Contoh: Website E-Commerce" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Tanggal Project *</label>
                        <input type="date" id="date" name="date"
                               value="<?php echo htmlspecialchars($editData['project_date'] ?? date('Y-m-d')); ?>"
                               required>
                    </div>
                    <div class="form-group full-width">
                        <label for="description">Deskripsi Project *</label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Jelaskan tentang project Anda..." required><?php echo htmlspecialchars($editData['project_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label for="image">URL Gambar (Opsional)</label>
                        <input type="url" id="image" name="image"
                               value="<?php echo htmlspecialchars($editData['project_image'] ?? ''); ?>"
                               placeholder="https://example.com/image.jpg">
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="save" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $editData ? 'Update Project' : 'Simpan Project'; ?>
                    </button>
                    <?php if($editData): ?>
                    <a href="crud.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">
                    <i class="fas fa-list"></i>
                    Daftar Project (<?php echo count($projects); ?> items)
                </h3>
                <form action="crud.php" method="GET" style="display: flex; align-items: center; gap: 10px;">
                    <label for="sort" style="font-weight: 500; font-size: 0.9rem;">Urutkan:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()" style="padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd;">
                        <option value="desc" <?php echo $sortParam === 'desc' ? 'selected' : ''; ?>>Terbaru ke Terlama</option>
                        <option value="asc" <?php echo $sortParam === 'asc' ? 'selected' : ''; ?>>Terlama ke Terbaru</option>
                    </select>
                </form>
            </div>
            <?php if(count($projects) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Deskripsi</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach($projects as $project):
                        $shortDesc = strlen($project['project_description']) > 100
                            ? substr($project['project_description'], 0, 100) . '...'
                            : $project['project_description'];
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <?php if(!empty($project['project_image'])): ?>
                            <img src="<?php echo htmlspecialchars($project['project_image']); ?>" alt="Project">
                            <?php else: ?>
                            <div style="width:80px;height:60px;background:#ddd;border-radius:5px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-image" style="color:#999;"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($project['project_title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($shortDesc); ?></td>
                        <td><?php echo date('d M Y', strtotime($project['project_date'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="crud.php?edit=<?php echo $project['id']; ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="crud.php?delete=<?php echo $project['id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Yakin ingin menghapus project ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align:center;padding:40px;color:#666;">
                <i class="fas fa-folder-open" style="font-size:3rem;color:#ddd;margin-bottom:10px;"></i>
                <p>Belum ada project. Silakan tambahkan project pertama Anda!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
