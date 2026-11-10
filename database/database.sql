CREATE DATABASE IF NOT EXISTS pengaduan_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pengaduan_db;

CREATE TABLE IF NOT EXISTS complaints (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ciphertext   LONGBLOB       NOT NULL,  -- JSON terenkripsi AES-256-GCM
  iv           VARBINARY(12)  NOT NULL,  -- nonce GCM
  tag          VARBINARY(16)  NOT NULL,  -- auth tag GCM
  wrapped_key  VARBINARY(512) NOT NULL,  -- kunci AES dibungkus RSA-OAEP
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
