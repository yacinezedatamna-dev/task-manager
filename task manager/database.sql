-- ============================================
-- Task Manager 2027 - Database Schema
-- ============================================
CREATE DATABASE IF NOT EXISTS task_manager
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE task_manager;

-- ---------- Users ----------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Tasks ----------
CREATE TABLE IF NOT EXISTS tasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status      ENUM('todo','doing','done')     NOT NULL DEFAULT 'todo',
    priority    ENUM('low','medium','high')     NOT NULL DEFAULT 'medium',
    due_date    DATE NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;
