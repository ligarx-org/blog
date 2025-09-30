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
            echo "✅ Database '{$this->dbname}' ready!\n";
            
            // Connect to the database
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            echo "✅ Database connection successful!\n";
            $this->createTables();
            $this->createIndexes();
            $this->createDefaultData();
            
        } catch(PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    private function createTables() {
        echo "\n📋 Creating tables...\n";
        
        $tables = [
            // Users table
            "users" => "CREATE TABLE IF NOT EXISTS users (
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
            "email_verifications" => "CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL,
                verification_code VARCHAR(10) NOT NULL,
                code_type ENUM('registration', 'password_reset') DEFAULT 'registration',
                expires_at TIMESTAMP NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Posts table
            "posts" => "CREATE TABLE IF NOT EXISTS posts (
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
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Likes table
            "likes" => "CREATE TABLE IF NOT EXISTS likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_like (user_id, post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Comments table
            "comments" => "CREATE TABLE IF NOT EXISTS comments (
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
            "chat_messages" => "CREATE TABLE IF NOT EXISTS chat_messages (
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
            "contact_messages" => "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Sessions table for security
            "user_sessions" => "CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) UNIQUE NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Newsletter subscribers table
            "newsletter_subscribers" => "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) UNIQUE NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
                echo "✅ Table '$tableName' created/updated successfully\n";
            } catch(PDOException $e) {
                echo "❌ Error creating table '$tableName': " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function createIndexes() {
        echo "\n📊 Creating indexes...\n";
        
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_email_code ON email_verifications(email, verification_code)",
            "CREATE INDEX IF NOT EXISTS idx_expires ON email_verifications(expires_at)",
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
                echo "✅ Index created successfully\n";
            } catch(PDOException $e) {
                // Index might already exist, that's okay
                echo "ℹ️ Index note: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function createDefaultData() {
        echo "\n👤 Creating default admin user...\n";
        
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, is_admin, is_verified, bio) VALUES (?, ?, ?, 1, 1, ?)");
                $stmt->execute(['admin', 'admin@blog.com', $hashedPassword, 'System Administrator']);
                echo "✅ Default admin user created (admin@blog.com / admin123)\n";
            } else {
                echo "ℹ️ Admin user already exists\n";
            }
            
            // Create sample posts if none exist
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM posts");
            $stmt->execute();
            $postCount = $stmt->fetchColumn();
            
            if ($postCount == 0) {
                $this->createSamplePosts();
            } else {
                echo "ℹ️ Posts already exist\n";
            }
            
        } catch(PDOException $e) {
            echo "❌ Error creating default data: " . $e->getMessage() . "\n";
        }
    }
    
    private function createSamplePosts() {
        echo "\n📝 Creating sample posts...\n";
        
        try {
            // Get admin user ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
            $stmt->execute();
            $adminId = $stmt->fetchColumn();
            
            if (!$adminId) {
                echo "❌ No admin user found for sample posts\n";
                return;
            }
            
            $samplePosts = [
                [
                    'title' => 'CodeBlog ga xush kelibsiz!',
                    'slug' => 'codeblog-ga-xush-kelibsiz',
                    'content' => '<h2>Salom, dasturchilar!</h2><p>Bu bizning yangi blog platformamiz. Bu yerda siz:</p><ul><li>Dasturlash bo\'yicha maqolalar o\'qishingiz mumkin</li><li>O\'z tajribalaringiz bilan bo\'lishishingiz mumkin</li><li>Boshqa dasturchilar bilan muloqot qilishingiz mumkin</li></ul><p>Bizga qo\'shiling va bilimlaringizni bo\'lishing!</p>',
                    'hashtags' => 'welcome, blog, programming, community',
                    'image' => 'welcome.jpg'
                ],
                [
                    'title' => 'JavaScript ES6+ Yangiliklari',
                    'slug' => 'javascript-es6-yangiliklari',
                    'content' => '<h2>ES6+ da qanday yangiliklar bor?</h2><p>JavaScript ning yangi versiyalarida juda ko\'p foydali xususiyatlar qo\'shildi:</p><h3>Arrow Functions</h3><pre><code>const salom = (ism) => `Salom, ${ism}!`;</code></pre><h3>Destructuring</h3><pre><code>const {ism, yosh} = user;</code></pre><h3>Template Literals</h3><pre><code>const xabar = `Salom, ${ism}! Siz ${yosh} yoshdasiz.`;</code></pre>',
                    'hashtags' => 'javascript, es6, programming, tutorial',
                    'image' => 'javascript-es6.jpg'
                ],
                [
                    'title' => 'React Hooks bilan Ishlash',
                    'slug' => 'react-hooks-bilan-ishlash',
                    'content' => '<h2>React Hooks nima?</h2><p>Hooks - bu React 16.8 da qo\'shilgan yangi xususiyat bo\'lib, function komponentlarda state va lifecycle metodlaridan foydalanish imkonini beradi.</p><h3>useState Hook</h3><pre><code>import React, { useState } from \'react\';\n\nfunction Counter() {\n  const [count, setCount] = useState(0);\n  \n  return (\n    <div>\n      <p>Siz {count} marta bosdingiz</p>\n      <button onClick={() => setCount(count + 1)}>\n        Bosing\n      </button>\n    </div>\n  );\n}</code></pre>',
                    'hashtags' => 'react, hooks, javascript, frontend',
                    'image' => 'react-hooks.jpg'
                ]
            ];
            
            $stmt = $this->pdo->prepare("INSERT INTO posts (title, slug, content, image, hashtags, author_id, views) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($samplePosts as $post) {
                // Check if post already exists
                $checkStmt = $this->pdo->prepare("SELECT id FROM posts WHERE slug = ?");
                $checkStmt->execute([$post['slug']]);
                if (!$checkStmt->fetch()) {
                    $stmt->execute([
                        $post['title'],
                        $post['slug'],
                        $post['content'],
                        $post['image'],
                        $post['hashtags'],
                        $adminId,
                        rand(10, 100)
                    ]);
                    echo "✅ Created post: " . $post['title'] . "\n";
                }
            }
            
        } catch(PDOException $e) {
            echo "❌ Error creating sample posts: " . $e->getMessage() . "\n";
        }
    }
    
    private function createIndexes() {
        echo "\n📊 Creating performance indexes...\n";
        
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_email_code ON email_verifications(email, verification_code)",
            "CREATE INDEX IF NOT EXISTS idx_expires ON email_verifications(expires_at)",
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
                echo "✅ Index created successfully\n";
            } catch(PDOException $e) {
                // Index might already exist, that's okay
                echo "ℹ️ Index note: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function createDefaultData() {
        echo "\n🔧 Setting up default data...\n";
        
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, is_admin, is_verified, bio) VALUES (?, ?, ?, 1, 1, ?)");
                $stmt->execute(['admin', 'admin@blog.com', $hashedPassword, 'System Administrator']);
                echo "✅ Default admin user created (admin@blog.com / admin123)\n";
            } else {
                echo "ℹ️ Admin user already exists\n";
            }
            
            // Create uploads directory
            $uploadDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                echo "✅ Uploads directory created\n";
            } else {
                echo "ℹ️ Uploads directory already exists\n";
            }
            
        } catch(PDOException $e) {
            echo "❌ Error creating default data: " . $e->getMessage() . "\n";
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Initialize database
echo "🚀 Starting database setup...\n";
echo "=====================================\n";

$dbStarter = new DatabaseStarter();

echo "\n=====================================\n";
echo "🎉 Database setup completed successfully!\n";
echo "\n📋 Default admin credentials:\n";
echo "   Email: admin@blog.com\n";
echo "   Password: admin123\n";
echo "   Username: admin\n";
echo "\n🔗 Access admin panel at: panel.php\n";
echo "🔗 Test API at: api/test-connection.php\n";
echo "\n⚠️  IMPORTANT: Change default admin password after first login!\n";
?>