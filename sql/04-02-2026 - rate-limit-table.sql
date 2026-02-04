-- Rate limiting table for brute force protection
CREATE TABLE IF NOT EXISTS `rate_limit_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL COMMENT 'Email, IP, or user identifier',
  `action` VARCHAR(50) NOT NULL COMMENT 'Action type (login, api_call, etc)',
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_identifier_action_created` (`identifier`, `action`, `created_at`),
  INDEX `idx_created_at` (`created_at`)
);
