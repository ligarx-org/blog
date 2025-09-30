<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/xato.log');

require_once 'api/config.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    logInfo("Database connection successful", ['timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    logError("Database connection failed", ['error' => $e->getMessage()]);
    die("Database connection failed: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'login':
                logSuccess("Admin login attempt");
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $captcha = $_POST['captcha'] ?? '';
                
                // Validate captcha
                if (!validateCaptcha($captcha, $_SESSION['captcha'] ?? '')) {
                    logError("Admin login captcha failed", ['provided' => $captcha, 'expected' => $_SESSION['captcha'] ?? '']);
                    echo json_encode(['success' => false, 'message' => 'Captcha noto\'g\'ri']);
                    exit;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    logSuccess("Admin login successful", ['admin_id' => $admin['id']]);
                    echo json_encode(['success' => true, 'message' => 'Muvaffaqiyatli kirdingiz!']);
                } else {
                    logError("Admin login failed", ['email' => $email]);
                    echo json_encode(['success' => false, 'message' => 'Email yoki parol noto\'g\'ri']);
                }
                exit;
                
            case 'create_post':
                if (!isset($_SESSION['admin_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Tizimga kiring']);
                    exit;
                }
                
                logSuccess("Post creation attempt");
                $title = sanitizeInput($_POST['title'] ?? '');
                $content = sanitizeInput($_POST['content'] ?? '');
                $hashtags = sanitizeInput($_POST['hashtags'] ?? '');
                $image = '';
                
                // Validate required fields
                if (empty($title) || empty($content)) {
                    logError("Post creation failed - missing required fields", ['title' => $title, 'content' => !empty($content)]);
                    echo json_encode(['success' => false, 'message' => 'Sarlavha va kontent majburiy']);
                    exit;
                }
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $imageName = uniqid() . '.' . $imageExtension;
                    $imagePath = $uploadDir . $imageName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                        $image = $imageName;
                        logInfo("Image uploaded successfully", ['image' => $imageName]);
                    }
                }
                
                // Generate slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                
                $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, image, hashtags, author_id, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
                $stmt->execute([$title, $slug, $content, $image, $hashtags, $_SESSION['admin_id']]);
                
                logSuccess("Post created successfully", ['title' => $title, 'admin_id' => $_SESSION['admin_id']]);
                echo json_encode(['success' => true, 'message' => 'Post muvaffaqiyatli yaratildi!']);
                exit;
                
            case 'delete_post':
                if (!isset($_SESSION['admin_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Tizimga kiring']);
                    exit;
                }
                
                $postId = (int)($_POST['post_id'] ?? 0);
                
                if ($postId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                    $stmt->execute([$postId]);
                    logSuccess("Post deleted", ['post_id' => $postId]);
                    echo json_encode(['success' => true, 'message' => 'Post o\'chirildi']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Noto\'g\'ri post ID']);
                }
                exit;
                
            case 'delete_user':
                if (!isset($_SESSION['admin_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Tizimga kiring']);
                    exit;
                }
                
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
                    $stmt->execute([$userId]);
                    logSuccess("User deleted", ['user_id' => $userId]);
                    echo json_encode(['success' => true, 'message' => 'Foydalanuvchi o\'chirildi']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Noto\'g\'ri foydalanuvchi ID']);
                }
                exit;
                
            case 'logout':
                session_destroy();
                echo json_encode(['success' => true, 'message' => 'Chiqildi']);
                exit;
        }
    } catch (Exception $e) {
        logError("Panel action error", ['error' => $e->getMessage(), 'action' => $_POST['action'] ?? '']);
        echo json_encode(['success' => false, 'message' => 'Server xatosi: ' . $e->getMessage()]);
        exit;
    }
}

// Check if admin is logged in
$isLoggedIn = isset($_SESSION['admin_id']);

// Get statistics if logged in
$stats = [];
if ($isLoggedIn) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['users'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
        $stats['posts'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
        $stats['comments'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
        $stats['unread_messages'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE is_active = 1");
        $stats['newsletter_subscribers'] = $stmt->fetch()['count'];
        
        // Get recent posts
        $stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC LIMIT 10");
        $stmt->execute();
        $recentPosts = $stmt->fetchAll();
        
        // Get recent users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $recentUsers = $stmt->fetchAll();
        
        // Get contact messages
        $stmt = $pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $contactMessages = $stmt->fetchAll();
        
    } catch (Exception $e) {
        logError("Error loading admin statistics", ['error' => $e->getMessage()]);
        $stats = ['users' => 0, 'posts' => 0, 'comments' => 0, 'unread_messages' => 0, 'newsletter_subscribers' => 0];
        $recentPosts = [];
        $recentUsers = [];
        $contactMessages = [];
    }
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CodeBlog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 100px auto;
        }
        
        .admin-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab {
            padding: 15px 25px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: white;
            border-bottom: 3px solid #667eea;
            color: #667eea;
        }
        
        .tab-content {
            padding: 20px;
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .captcha-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .captcha-image {
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .refresh-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .refresh-btn:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        .d-flex {
            display: flex;
        }
        
        .justify-content-between {
            justify-content: space-between;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$isLoggedIn): ?>
            <!-- Login Form -->
            <div class="login-form">
                <h2 class="text-center mb-3">Admin Panel</h2>
                <p class="text-center mb-3" style="color: #666;">Tizimga kirish uchun ma'lumotlaringizni kiriting</p>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Parol</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="captcha">Xavfsizlik kodi</label>
                        <div class="captcha-container">
                            <img src="./api/captcha.php?<?= time() ?>" alt="Captcha" class="captcha-image" id="captchaImage">
                            <button type="button" class="refresh-btn" onclick="refreshCaptcha()">Yangilash</button>
                        </div>
                        <input type="text" id="captcha" name="captcha" class="form-control" placeholder="Yuqoridagi kodni kiriting" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Kirish</button>
                </form>
                
                <div class="mt-3 text-center">
                    <small style="color: #666;">
                        Default: admin@blog.com / admin123
                    </small>
                </div>
            </div>
        <?php else: ?>
            <!-- Admin Panel -->
            <div class="admin-panel">
                <div class="header">
                    <div>
                        <h1>Admin Panel</h1>
                        <p>Xush kelibsiz, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</p>
                    </div>
                    <button class="btn btn-danger" onclick="logout()">Chiqish</button>
                </div>
                
                <!-- Statistics -->
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['users'] ?></div>
                        <div>Foydalanuvchilar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['posts'] ?></div>
                        <div>Postlar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['comments'] ?></div>
                        <div>Izohlar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['unread_messages'] ?></div>
                        <div>O'qilmagan xabarlar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['newsletter_subscribers'] ?></div>
                        <div>Newsletter obunachilari</div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" onclick="showTab('posts')">Postlar</button>
                    <button class="tab" onclick="showTab('users')">Foydalanuvchilar</button>
                    <button class="tab" onclick="showTab('messages')">Xabarlar</button>
                    <button class="tab" onclick="showTab('create-post')">Post yaratish</button>
                </div>
                
                <!-- Posts Tab -->
                <div id="posts" class="tab-content active">
                    <h3>So'nggi postlar</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sarlavha</th>
                                <th>Muallif</th>
                                <th>Ko'rishlar</th>
                                <th>Sana</th>
                                <th>Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td><?= $post['id'] ?></td>
                                <td><?= htmlspecialchars($post['title']) ?></td>
                                <td><?= htmlspecialchars($post['username']) ?></td>
                                <td><?= $post['views'] ?></td>
                                <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deletePost(<?= $post['id'] ?>)">O'chirish</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Users Tab -->
                <div id="users" class="tab-content">
                    <h3>Foydalanuvchilar</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Tasdiqlangan</th>
                                <th>Ro'yxatdan o'tgan</th>
                                <th>Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['is_verified'] ? '✅' : '❌' ?></td>
                                <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $user['id'] ?>)">O'chirish</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Messages Tab -->
                <div id="messages" class="tab-content">
                    <h3>Bog'lanish xabarlari</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ism</th>
                                <th>Email</th>
                                <th>Xabar</th>
                                <th>Sana</th>
                                <th>Holat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contactMessages as $message): ?>
                            <tr>
                                <td><?= $message['id'] ?></td>
                                <td><?= htmlspecialchars($message['name']) ?></td>
                                <td><?= htmlspecialchars($message['email']) ?></td>
                                <td><?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...</td>
                                <td><?= date('d.m.Y H:i', strtotime($message['created_at'])) ?></td>
                                <td><?= $message['is_read'] ? 'O\'qilgan' : 'Yangi' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Create Post Tab -->
                <div id="create-post" class="tab-content">
                    <h3>Yangi post yaratish</h3>
                    <form id="createPostForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Sarlavha</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Kontent</label>
                            <textarea id="content" name="content" class="form-control" rows="10" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="hashtags">Hashtaglar (vergul bilan ajrating)</label>
                            <input type="text" id="hashtags" name="hashtags" class="form-control" placeholder="javascript, react, programming">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Rasm</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Post yaratish</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Refresh captcha
        function refreshCaptcha() {
            document.getElementById('captchaImage').src = './api/captcha.php?' + Date.now();
        }
        
        // Show tab
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Login form
        <?php if (!$isLoggedIn): ?>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', document.getElementById('email').value);
            formData.append('password', document.getElementById('password').value);
            formData.append('captcha', document.getElementById('captcha').value);
            
            try {
                const response = await fetch('panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                    refreshCaptcha();
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('Xatolik yuz berdi');
            }
        });
        <?php endif; ?>
        
        // Create post form
        <?php if ($isLoggedIn): ?>
        document.getElementById('createPostForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'create_post');
            formData.append('title', document.getElementById('title').value);
            formData.append('content', document.getElementById('content').value);
            formData.append('hashtags', document.getElementById('hashtags').value);
            
            const imageFile = document.getElementById('image').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }
            
            try {
                const response = await fetch('panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    document.getElementById('createPostForm').reset();
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Create post error:', error);
                alert('Xatolik yuz berdi');
            }
        });
        
        // Delete post
        function deletePost(postId) {
            if (confirm('Rostdan ham bu postni o\'chirmoqchimisiz?')) {
                const formData = new FormData();
                formData.append('action', 'delete_post');
                formData.append('post_id', postId);
                
                fetch('panel.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Xatolik yuz berdi');
                });
            }
        }
        
        // Delete user
        function deleteUser(userId) {
            if (confirm('Rostdan ham bu foydalanuvchini o\'chirmoqchimisiz?')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                
                fetch('panel.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Xatolik yuz berdi');
                });
            }
        }
        
        // Logout
        function logout() {
            if (confirm('Rostdan ham chiqmoqchimisiz?')) {
                const formData = new FormData();
                formData.append('action', 'logout');
                
                fetch('panel.php', {
                    method: 'POST',
                    body: formData
                })
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    location.reload();
                });
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>