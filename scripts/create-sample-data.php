<?php
// Create sample data for testing
require_once '../api/config.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Creating sample data...\n";
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
    $stmt->execute();
    $adminId = $stmt->fetchColumn();
    
    if (!$adminId) {
        // Create admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin, is_verified, bio) VALUES (?, ?, ?, 1, 1, ?)");
        $stmt->execute(['admin', 'admin@blog.com', $hashedPassword, 'System Administrator']);
        $adminId = $pdo->lastInsertId();
        echo "Admin user created\n";
    }
    
    // Create sample posts
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
            'content' => '<h2>React Hooks nima?</h2><p>Hooks - bu React 16.8 da qo\'shilgan yangi xususiyat bo\'lib, function komponentlarda state va lifecycle metodlaridan foydalanish imkonini beradi.</p><h3>useState Hook</h3><pre><code>import React, { useState } from \'react\';\n\nfunction Counter() {\n  const [count, setCount] = useState(0);\n  \n  return (\n    &lt;div&gt;\n      &lt;p&gt;Siz {count} marta bosdingiz&lt;/p&gt;\n      &lt;button onClick={() => setCount(count + 1)}&gt;\n        Bosing\n      &lt;/button&gt;\n    &lt;/div&gt;\n  );\n}</code></pre>',
            'hashtags' => 'react, hooks, javascript, frontend',
            'image' => 'react-hooks.jpg'
        ]
    ];
    
    foreach ($samplePosts as $post) {
        // Check if post already exists
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->execute([$post['slug']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, image, hashtags, author_id, views) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $post['title'],
                $post['slug'],
                $post['content'],
                $post['image'],
                $post['hashtags'],
                $adminId,
                rand(10, 100)
            ]);
            echo "Created post: " . $post['title'] . "\n";
        }
    }
    
    echo "Sample data creation completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>