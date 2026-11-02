CREATE DATABASE IF NOT EXISTS screenlite
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE screenlite;

CREATE TABLE IF NOT EXISTS screens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    device_code VARCHAR(64) NOT NULL,
    last_seen_at DATETIME NULL COMMENT 'India Standard Time (Asia/Kolkata)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_screens_device_code (device_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS playlist_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    screen_id INT UNSIGNED NOT NULL,
    media_url VARCHAR(2048) NOT NULL,
    duration_seconds INT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_playlist_items_screen_position (screen_id, position),
    CONSTRAINT fk_playlist_items_screen
        FOREIGN KEY (screen_id) REFERENCES screens (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
