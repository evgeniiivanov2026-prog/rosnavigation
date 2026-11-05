-- РосНавигация. Кодировка utf8mb4, движок InnoDB.
-- Импорт через phpMyAdmin. Пароль базы в этот файл не входит.

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone         VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(100) NOT NULL,
  city          VARCHAR(100) DEFAULT NULL,
  role          ENUM('seeker','helper','both') NOT NULL DEFAULT 'both',
  truck_brand   VARCHAR(100) DEFAULT NULL,
  truck_plate   VARCHAR(20) DEFAULT NULL,
  truck_trailer VARCHAR(50) DEFAULT NULL,
  experience_y  TINYINT UNSIGNED DEFAULT NULL,
  avatar_path   VARCHAR(255) DEFAULT NULL,
  car_path      VARCHAR(255) DEFAULT NULL,
  motto         VARCHAR(200) DEFAULT NULL,
  status        ENUM('on_air','on_route','need_help','resting','offline')
                NOT NULL DEFAULT 'offline',
  rating_sum    INT UNSIGNED DEFAULT 0,
  rating_count  INT UNSIGNED DEFAULT 0,
  helps_count   INT UNSIGNED DEFAULT 0,
  is_partner    TINYINT(1) DEFAULT 0,
  is_banned     TINYINT(1) DEFAULT 0,
  last_seen_at  DATETIME DEFAULT NULL,
  lat           DECIMAL(10,7) DEFAULT NULL,
  lng           DECIMAL(10,7) DEFAULT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_city (city),
  INDEX idx_last_seen (last_seen_at),
  INDEX idx_geo (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sos_signals (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  type          ENUM('breakdown','fuel','sleep','accident','medical','other')
                NOT NULL,
  lat           DECIMAL(10,7) NOT NULL,
  lng           DECIMAL(10,7) NOT NULL,
  route         VARCHAR(50) DEFAULT NULL,
  km            INT UNSIGNED DEFAULT NULL,
  comment       VARCHAR(500) DEFAULT NULL,
  status        ENUM('active','accepted','completed','cancelled')
                NOT NULL DEFAULT 'active',
  helper_id     INT UNSIGNED DEFAULT NULL,
  approx_geo    TINYINT(1) NOT NULL DEFAULT 0,
  demo          TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accepted_at   DATETIME DEFAULT NULL,
  completed_at  DATETIME DEFAULT NULL,
  expires_at    DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (helper_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status_created (status, created_at),
  INDEX idx_geo (lat, lng),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sos_messages (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sos_id        INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,
  body          VARCHAR(500) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sos_id) REFERENCES sos_signals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_sos (sos_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partners (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  type          ENUM('sto','fuel','hotel','insurance','lawyer','shop','other')
                NOT NULL,
  city          VARCHAR(100) NOT NULL,
  address       VARCHAR(255) DEFAULT NULL,
  lat           DECIMAL(10,7) DEFAULT NULL,
  lng           DECIMAL(10,7) DEFAULT NULL,
  phone         VARCHAR(30) DEFAULT NULL,
  site_url      VARCHAR(255) DEFAULT NULL,
  description   TEXT DEFAULT NULL,
  photo_path    VARCHAR(255) DEFAULT NULL,
  price_from    INT UNSIGNED DEFAULT NULL,
  works_24_7    TINYINT(1) DEFAULT 0,
  rating_avg    DECIMAL(3,2) DEFAULT 0,
  rating_count  INT UNSIGNED DEFAULT 0,
  is_active     TINYINT(1) DEFAULT 1,
  demo          TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_type_city (type, city),
  INDEX idx_geo (lat, lng),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channel_posts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  body          TEXT NOT NULL,
  photo_path    VARCHAR(255) DEFAULT NULL,
  city          VARCHAR(100) DEFAULT NULL,
  route         VARCHAR(50) DEFAULT NULL,
  likes_count   INT UNSIGNED DEFAULT 0,
  status        ENUM('pending','published','rejected')
                NOT NULL DEFAULT 'pending',
  demo          TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE channel_likes (
  user_id       INT UNSIGNED NOT NULL,
  post_id       INT UNSIGNED NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, post_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES channel_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_id     INT UNSIGNED NOT NULL,
  target_user   INT UNSIGNED DEFAULT NULL,
  target_partner INT UNSIGNED DEFAULT NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  body          VARCHAR(500) DEFAULT NULL,
  status        ENUM('pending','published','rejected')
                NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_target_user (target_user),
  INDEX idx_target_partner (target_partner),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  target_type   ENUM('post','user','sos') NOT NULL,
  target_id     INT UNSIGNED NOT NULL,
  reason        VARCHAR(300) DEFAULT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
  id            CHAR(64) PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  data          TEXT,
  last_seen_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at    DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE csrf_tokens (
  token         CHAR(64) PRIMARY KEY,
  user_id       INT UNSIGNED DEFAULT NULL,
  expires_at    DATETIME NOT NULL,
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
