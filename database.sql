-- ChemistLab: database MySQL/MariaDB
-- Cara pakai: phpMyAdmin > Impor, atau jalankan:  mysql -u root < database.sql
-- (Jika tabel sudah kamu buat manual dengan struktur yang sama, tidak apa-apa: perintah CREATE memakai IF NOT EXISTS.)

CREATE DATABASE IF NOT EXISTS chemistlab CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE chemistlab;

-- Profil pemain
CREATE TABLE IF NOT EXISTS users (
  id       INT(11)      NOT NULL AUTO_INCREMENT,
  username VARCHAR(50)  NOT NULL,
  password VARCHAR(255) NOT NULL,          -- disimpan sebagai hash (password_hash)
  level    INT(11)      NOT NULL DEFAULT 1,
  coins    INT(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inventory & progres pemain (satu baris per kode)
CREATE TABLE IF NOT EXISTS users_inventory (
  id        INT(11)     NOT NULL AUTO_INCREMENT,
  user_id   INT(11)     NOT NULL,
  item_code VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  CONSTRAINT fk_inv_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Jika tabel "users" kamu buat manual dan belum ada UNIQUE pada username, jalankan (opsional):
-- ALTER TABLE users ADD UNIQUE KEY uq_username (username);
