-- Added database fixes and default values
USE blog_system;

-- Fix posts table to ensure all columns have proper defaults
ALTER TABLE posts 
MODIFY COLUMN views INT DEFAULT 0,
MODIFY COLUMN status VARCHAR(20) DEFAULT 'published';

-- Update existing posts to have default values
UPDATE posts SET views = 0 WHERE views IS NULL;
UPDATE posts SET status = 'published' WHERE status IS NULL OR status = '';

-- Ensure all tables have proper indexes for performance
CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at);
CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id);
CREATE INDEX IF NOT EXISTS idx_likes_post_user ON likes(post_id, user_id);
CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at);
