-- Ensure newsletter table has proper structure and default values
ALTER TABLE newsletter_subscribers 
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Update existing records to have proper timestamps if missing
UPDATE newsletter_subscribers 
SET created_at = CURRENT_TIMESTAMP 
WHERE created_at IS NULL OR created_at = '0000-00-00 00:00:00';

-- Ensure all subscribers are active by default
UPDATE newsletter_subscribers 
SET is_active = 1 
WHERE is_active IS NULL;
