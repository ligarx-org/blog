<?php
// Enhanced Database Setup and Table Creation
class DatabaseStarter {
    private $host = 'localhost';
    private $dbname = 'stacknro_blog';
    private $username = 'stacknro_blog';
    private $password = 'admin-2025';
    private $pdo;
    
    public function __construct() {
        try {
            // First connect without database to create it if needed
            $this->pdo = new PDO("mysql:host={$this->host}", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Database '{$this->dbname}' ready!\n";
            
            // Connect to the database
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            echo "Database connection successful!\n";
            $this->createTables();
            $this->createIndexes();
            
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    private function createTables() {
        $tables = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                avatar VARCHAR(255) DEFAULT 'default-avatar.png',
                bio TEXT DEFAULT '',
                location VARCHAR(100) DEFAULT '',
                website VARCHAR(255) DEFAULT '',
                is_admin TINYINT(1) DEFAULT 0,
                is_verified TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Email verifications table
            "CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL,
                verification_code VARCHAR(10) NOT NULL,
                code_type ENUM('registration', 'password_reset') DEFAULT 'registration',
                expires_at TIMESTAMP NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_code (email, verification_code),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Posts table
            "CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                content LONGTEXT NOT NULL,
                image VARCHAR(255) DEFAULT '',
                hashtags TEXT DEFAULT '',
                author_id INT NOT NULL,
                status ENUM('draft', 'published') DEFAULT 'published',
                views INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                FULLTEXT(title, hashtags)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Likes table
            "CREATE TABLE IF NOT EXISTS likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_like (user_id, post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Comments table
            "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Chat messages table
            "CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Contact messages table
            "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Sessions table for security
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) UNIQUE NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Newsletter subscribers table
            "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) UNIQUE NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($tables as $table) {
            try {
                $this->pdo->exec($table);
                echo "Table created/updated successfully\n";
            } catch(PDOException $e) {
                echo "Error creating table: " . $e->getMessage() . "\n";
            }
        }
        
        // Insert default admin user
        $this->createDefaultAdmin();
        echo "All tables created successfully!\n";
    }
    
    private function createIndexes() {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id)",
            "CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)",
            "CREATE INDEX IF NOT EXISTS idx_likes_post_user ON likes(post_id, user_id)",
            "CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_id)",
            "CREATE INDEX IF NOT EXISTS idx_comments_user ON comments(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_chat_sender ON chat_messages(sender_id)",
            "CREATE INDEX IF NOT EXISTS idx_chat_receiver ON chat_messages(receiver_id)",
            "CREATE INDEX IF NOT EXISTS idx_chat_read ON chat_messages(is_read)",
            "CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token)",
            "CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at)",
            "CREATE INDEX IF NOT EXISTS idx_newsletter_email ON newsletter_subscribers(email)",
            "CREATE INDEX IF NOT EXISTS idx_newsletter_active ON newsletter_subscribers(is_active)"
        ];
        
        foreach ($indexes as $index) {
            try {
                $this->pdo->exec($index);
                echo "Index created successfully\n";
            } catch(PDOException $e) {
                // Index might already exist, that's okay
                echo "Index note: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function createDefaultAdmin() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, is_admin, is_verified, bio) VALUES (?, ?, ?, 1, 1, ?)");
                $stmt->execute(['admin', 'admin@blog.com', $hashedPassword, 'System Administrator']);
                echo "Default admin user created (admin/admin123)\n";
            } else {
                echo "Admin user already exists\n";
            }
            
            // Create sample posts if none exist
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM posts");
            $stmt->execute();
            $postCount = $stmt->fetchColumn();
            
            if ($postCount == 0) {
                $this->createSamplePosts();
            }
            
        } catch(PDOException $e) {
            echo "Error creating admin: " . $e->getMessage() . "\n";
        }
    }
    
    private function createSamplePosts() {
        try {
            // Get admin user ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
            $stmt->execute();
            $adminId = $stmt->fetchColumn();
            
            if (!$adminId) {
                echo "No admin user found for sample posts\n";
                return;
            }
            
            $samplePosts = [
                [
                    'title' => 'Welcome to CodeBlog',
                    'slug' => 'welcome-to-codeblog',
                    'content' => '<h2>Xush kelibsiz!</h2><p>Bu bizning yangi blog tizimimiz. Bu yerda dasturlash, texnologiya va innovatsiyalar haqida maqolalar topasiz.</p><p>Bizning maqsadimiz:</p><ul><li>Sifatli ta\'lim materiallari taqdim etish</li><li>Dasturchilar jamiyatini rivojlantirish</li><li>Yangi texnologiyalar haqida ma\'lumot berish</li></ul>',
                    'hashtags' => 'welcome, blog, programming, technology',
                    'image' => 'welcome.jpg'
                ],
                [
                    'title' => 'JavaScript Asoslari',
                    'slug' => 'javascript-basics',
                    'content' => '<h2>JavaScript nima?</h2><p>JavaScript - bu web sahifalarni interaktiv qilish uchun ishlatiladigan dasturlash tili.</p><h3>Asosiy xususiyatlari:</h3><ul><li>Dinamik typing</li><li>Prototype-based OOP</li><li>First-class functions</li><li>Event-driven programming</li></ul><p>Misol:</p><pre><code>function salom(ism) {\n    return `Salom, ${ism}!`;\n}\n\nconsole.log(salom("Dunyo"));</code></pre>',
                    'hashtags' => 'javascript, programming, web, tutorial',
                    'image' => 'javascript.jpg'
                ],
                [
                    'title' => 'React.js bilan Tanishish',
                    'slug' => 'react-introduction',
                    'content' => '<h2>React nima?</h2><p>React - bu Facebook tomonidan yaratilgan JavaScript kutubxonasi bo\'lib, foydalanuvchi interfeyslari yaratish uchun ishlatiladi.</p><h3>React ning afzalliklari:</h3><ul><li>Component-based architecture</li><li>Virtual DOM</li><li>Unidirectional data flow</li><li>Large ecosystem</li></ul><p>Oddiy React komponenti:</p><pre><code>function Welcome(props) {\n    return &lt;h1&gt;Salom, {props.name}!&lt;/h1&gt;;\n}</code></pre>',
                    'hashtags' => 'react, javascript, frontend, components',
                    'image' => 'react.jpg'
                ]
            ];
            
            $stmt = $this->pdo->prepare("INSERT INTO posts (title, slug, content, image, hashtags, author_id, views) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($samplePosts as $post) {
                $stmt->execute([
                    $post['title'],
                    $post['slug'],
                    $post['content'],
                    $post['image'],
                    $post['hashtags'],
                    $adminId,
                    rand(10, 100) // Random view count
                ]);
            }
            
            echo "Sample posts created successfully\n";
            
        } catch(PDOException $e) {
            echo "Error creating sample posts: " . $e->getMessage() . "\n";
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Initialize database
echo "Starting database setup...\n";
$dbStarter = new DatabaseStarter();
echo "Database setup completed successfully!\n";
echo "\nDefault admin credentials:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
echo "Email: admin@blog.com\n";
?>