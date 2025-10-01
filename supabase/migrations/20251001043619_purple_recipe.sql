-- Complete Database Schema for CodeBlog System
-- This file contains all tables and data needed for the blog system

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS stacknro_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stacknro_blog;

-- Users table
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email verifications table
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    verification_code VARCHAR(10) NOT NULL,
    code_type ENUM('registration', 'password_reset') DEFAULT 'registration',
    expires_at TIMESTAMP NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts table
CREATE TABLE IF NOT EXISTS posts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table for security
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_email_code ON email_verifications(email, verification_code);
CREATE INDEX IF NOT EXISTS idx_expires ON email_verifications(expires_at);
CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at);
CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id);
CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_likes_post_user ON likes(post_id, user_id);
CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_id);
CREATE INDEX IF NOT EXISTS idx_comments_user ON comments(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_sender ON chat_messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_chat_receiver ON chat_messages(receiver_id);
CREATE INDEX IF NOT EXISTS idx_chat_read ON chat_messages(is_read);
CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_newsletter_email ON newsletter_subscribers(email);
CREATE INDEX IF NOT EXISTS idx_newsletter_active ON newsletter_subscribers(is_active);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, is_admin, is_verified, bio) 
SELECT 'admin', 'admin@blog.com', 
       '$2y$10$1v8JgZ77RrjPuXH.u2c7A.WTqJytVglNQtUoXQdR90yvwGSZXo4OG', 
       1, 1, 'System Administrator'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE is_admin = 1);

-- Insert sample posts
INSERT INTO posts (title, slug, content, image, hashtags, author_id, views) 
SELECT 'CodeBlog ga xush kelibsiz!', 'codeblog-ga-xush-kelibsiz', 
       '<h2>Salom, dasturchilar!</h2><p>Bu bizning yangi blog platformamiz. Bu yerda siz:</p><ul><li>Dasturlash bo\'yicha maqolalar o\'qishingiz mumkin</li><li>O\'z tajribalaringiz bilan bo\'lishishingiz mumkin</li><li>Boshqa dasturchilar bilan muloqot qilishingiz mumkin</li></ul><p>Bizga qo\'shiling va bilimlaringizni bo\'lishing!</p>', 
       'welcome.jpg', 'welcome, blog, programming, community', 
       (SELECT id FROM users WHERE is_admin = 1 LIMIT 1), 45
WHERE NOT EXISTS (SELECT 1 FROM posts WHERE slug = 'codeblog-ga-xush-kelibsiz');

INSERT INTO posts (title, slug, content, image, hashtags, author_id, views) 
SELECT 'JavaScript ES6+ Yangiliklari', 'javascript-es6-yangiliklari', 
       '<h2>ES6+ da qanday yangiliklar bor?</h2><p>JavaScript ning yangi versiyalarida juda ko\'p foydali xususiyatlar qo\'shildi:</p><h3>Arrow Functions</h3><pre><code>const salom = (ism) => `Salom, ${ism}!`;</code></pre><h3>Destructuring</h3><pre><code>const {ism, yosh} = user;</code></pre><h3>Template Literals</h3><pre><code>const xabar = `Salom, ${ism}! Siz ${yosh} yoshdasiz.`;</code></pre>', 
       'javascript-es6.jpg', 'javascript, es6, programming, tutorial', 
       (SELECT id FROM users WHERE is_admin = 1 LIMIT 1), 78
WHERE NOT EXISTS (SELECT 1 FROM posts WHERE slug = 'javascript-es6-yangiliklari');

INSERT INTO posts (title, slug, content, image, hashtags, author_id, views) 
SELECT 'React Hooks bilan Ishlash', 'react-hooks-bilan-ishlash', 
       '<h2>React Hooks nima?</h2><p>Hooks - bu React 16.8 da qo\'shilgan yangi xususiyat bo\'lib, function komponentlarda state va lifecycle metodlaridan foydalanish imkonini beradi.</p><h3>useState Hook</h3><pre><code>import React, { useState } from \'react\';\n\nfunction Counter() {\n  const [count, setCount] = useState(0);\n  \n  return (\n    &lt;div&gt;\n      &lt;p&gt;Siz {count} marta bosdingiz&lt;/p&gt;\n      &lt;button onClick={() =&gt; setCount(count + 1)}&gt;\n        Bosing\n      &lt;/button&gt;\n    &lt;/div&gt;\n  );\n}</code></pre><h3>useEffect Hook</h3><pre><code>import React, { useState, useEffect } from \'react\';\n\nfunction Timer() {\n  const [seconds, setSeconds] = useState(0);\n\n  useEffect(() =&gt; {\n    const interval = setInterval(() =&gt; {\n      setSeconds(seconds =&gt; seconds + 1);\n    }, 1000);\n\n    return () =&gt; clearInterval(interval);\n  }, []);\n\n  return &lt;div&gt;Vaqt: {seconds} soniya&lt;/div&gt;;\n}</code></pre>', 
       'react-hooks.jpg', 'react, hooks, javascript, frontend', 
       (SELECT id FROM users WHERE is_admin = 1 LIMIT 1), 92
WHERE NOT EXISTS (SELECT 1 FROM posts WHERE slug = 'react-hooks-bilan-ishlash');