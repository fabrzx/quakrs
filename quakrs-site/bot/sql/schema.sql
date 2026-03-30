CREATE TABLE IF NOT EXISTS telegram_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  telegram_user_id BIGINT NOT NULL,
  chat_id BIGINT NOT NULL,
  username VARCHAR(128) NULL,
  first_name VARCHAR(128) NULL,
  language_code VARCHAR(16) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_telegram_user_id (telegram_user_id),
  KEY ix_chat_id (chat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS telegram_user_preferences (
  user_id BIGINT UNSIGNED NOT NULL,
  mode ENUM('essential','balanced','monitor') NOT NULL DEFAULT 'essential',
  categories_json JSON NOT NULL,
  eq_min_magnitude DECIMAL(3,1) NOT NULL DEFAULT 5.5,
  focus_country CHAR(2) NULL,
  center_lat DECIMAL(9,6) NULL,
  center_lon DECIMAL(9,6) NULL,
  radius_km INT NULL,
  digest_enabled TINYINT(1) NOT NULL DEFAULT 1,
  digest_time VARCHAR(5) NOT NULL DEFAULT '07:40',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_tup_user FOREIGN KEY (user_id) REFERENCES telegram_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bot_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category ENUM('earthquakes','volcanoes','tsunami','space_weather') NOT NULL,
  event_key VARCHAR(191) NOT NULL,
  provider VARCHAR(80) NOT NULL,
  event_time DATETIME NOT NULL,
  title VARCHAR(255) NOT NULL,
  summary TEXT NULL,
  country VARCHAR(80) NULL,
  region VARCHAR(160) NULL,
  latitude DECIMAL(10,6) NULL,
  longitude DECIMAL(10,6) NULL,
  magnitude DECIMAL(4,2) NULL,
  depth_km DECIMAL(6,2) NULL,
  severity_label VARCHAR(80) NULL,
  source_url VARCHAR(400) NULL,
  payload_json JSON NOT NULL,
  score INT NOT NULL,
  decision ENUM('ignore','digest','alert') NOT NULL,
  payload_hash CHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_event_key (event_key),
  KEY ix_cat_time (category, event_time),
  KEY ix_decision_time (decision, event_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  event_id BIGINT UNSIGNED NULL,
  notification_type ENUM('alert','digest','monitor','command_reply') NOT NULL,
  priority INT NOT NULL DEFAULT 50,
  payload_json JSON NOT NULL,
  status ENUM('pending','processing','sent','failed','dead') NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL,
  scheduled_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_status_sched (status, scheduled_at, priority),
  KEY ix_user_status (user_id, status),
  CONSTRAINT fk_nq_user FOREIGN KEY (user_id) REFERENCES telegram_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_nq_event FOREIGN KEY (event_id) REFERENCES bot_events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  notification_type ENUM('alert','digest','monitor','command_reply') NOT NULL,
  message_hash CHAR(64) NOT NULL,
  telegram_message_id BIGINT NULL,
  status ENUM('sent','failed') NOT NULL,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_event_user_type (event_id, user_id, notification_type),
  KEY ix_user_sent_at (user_id, sent_at),
  KEY ix_message_hash (message_hash),
  CONSTRAINT fk_en_user FOREIGN KEY (user_id) REFERENCES telegram_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_en_event FOREIGN KEY (event_id) REFERENCES bot_events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS digest_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  digest_date DATE NOT NULL,
  variant ENUM('global','italy') NOT NULL DEFAULT 'global',
  status ENUM('running','completed','failed') NOT NULL,
  content_text MEDIUMTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_digest_date_variant (digest_date, variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
