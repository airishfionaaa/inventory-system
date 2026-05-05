-- ============================================================
--  Hardware Inventory System — Full Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventory_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(80)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO categories (name) VALUES
  ('Laptop'),('Monitor'),('Printer'),('Mouse'),
  ('Speaker'),('Headphones'),('Keyboard');

-- Brands
CREATE TABLE IF NOT EXISTS brands (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO brands (name) VALUES
  ('Asus'),('Acer'),('Lenovo'),('HP'),('Dell'),
  ('Apple'),('Logitech'),('Samsung'),('Sony'),('JBL'),
  ('Razer'),('MSI'),('Epson'),('Canon'),('HyperX');

-- Assets (products/items)
CREATE TABLE IF NOT EXISTS assets (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(200) NOT NULL,
  sku          VARCHAR(100) NOT NULL UNIQUE,
  category_id  INT UNSIGNED NOT NULL,
  brand_id     INT UNSIGNED NOT NULL,
  unit         VARCHAR(30)  NOT NULL DEFAULT 'pcs',
  stock        INT          NOT NULL DEFAULT 0,
  reorder_qty  INT          NOT NULL DEFAULT 5,
  unit_price   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  location     VARCHAR(150),
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (brand_id)    REFERENCES brands(id)
);

-- Stock-In
CREATE TABLE IF NOT EXISTS stock_in (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id    INT UNSIGNED NOT NULL,
  quantity    INT          NOT NULL,
  unit_cost   DECIMAL(12,2) DEFAULT 0.00,
  supplier    VARCHAR(150),
  reference   VARCHAR(100),
  remarks     TEXT,
  created_by  INT UNSIGNED,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asset_id)   REFERENCES assets(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Stock-Out
CREATE TABLE IF NOT EXISTS stock_out (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id     INT UNSIGNED NOT NULL,
  quantity     INT          NOT NULL,
  requested_by VARCHAR(150),
  purpose      ENUM('Use','Repair','Transfer','Disposal','Sale') DEFAULT 'Use',
  remarks      TEXT,
  created_by   INT UNSIGNED,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asset_id)   REFERENCES assets(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Audit log (for triggers to write into)
CREATE TABLE IF NOT EXISTS audit_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action     VARCHAR(50)  NOT NULL,
  table_name VARCHAR(80)  NOT NULL,
  record_id  INT UNSIGNED NOT NULL,
  detail     TEXT,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  STORED PROCEDURES
-- ============================================================

DELIMITER $$

-- Record a stock-in transaction
CREATE PROCEDURE IF NOT EXISTS sp_stock_in(
  IN p_asset_id   INT UNSIGNED,
  IN p_qty        INT,
  IN p_cost       DECIMAL(12,2),
  IN p_supplier   VARCHAR(150),
  IN p_reference  VARCHAR(100),
  IN p_remarks    TEXT,
  IN p_user_id    INT UNSIGNED
)
BEGIN
  INSERT INTO stock_in (asset_id, quantity, unit_cost, supplier, reference, remarks, created_by)
  VALUES (p_asset_id, p_qty, p_cost, p_supplier, p_reference, p_remarks, p_user_id);

  UPDATE assets SET stock = stock + p_qty WHERE id = p_asset_id;
END$$

-- Record a stock-out transaction
CREATE PROCEDURE IF NOT EXISTS sp_stock_out(
  IN p_asset_id    INT UNSIGNED,
  IN p_qty         INT,
  IN p_requested   VARCHAR(150),
  IN p_purpose     VARCHAR(20),
  IN p_remarks     TEXT,
  IN p_user_id     INT UNSIGNED
)
BEGIN
  DECLARE current_stock INT;
  SELECT stock INTO current_stock FROM assets WHERE id = p_asset_id;

  IF current_stock < p_qty THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock';
  END IF;

  INSERT INTO stock_out (asset_id, quantity, requested_by, purpose, remarks, created_by)
  VALUES (p_asset_id, p_qty, p_requested, p_purpose, p_remarks, p_user_id);

  UPDATE assets SET stock = stock - p_qty WHERE id = p_asset_id;
END$$

DELIMITER ;

-- ============================================================
--  TRIGGERS
-- ============================================================

DELIMITER $$

-- Log every stock-in insert
CREATE TRIGGER IF NOT EXISTS trg_after_stock_in
AFTER INSERT ON stock_in
FOR EACH ROW
BEGIN
  INSERT INTO audit_log (action, table_name, record_id, detail)
  VALUES ('STOCK_IN', 'stock_in', NEW.id,
    CONCAT('Asset ID: ', NEW.asset_id, ' | Qty: +', NEW.quantity));
END$$

-- Log every stock-out insert
CREATE TRIGGER IF NOT EXISTS trg_after_stock_out
AFTER INSERT ON stock_out
FOR EACH ROW
BEGIN
  INSERT INTO audit_log (action, table_name, record_id, detail)
  VALUES ('STOCK_OUT', 'stock_out', NEW.id,
    CONCAT('Asset ID: ', NEW.asset_id, ' | Qty: -', NEW.quantity));
END$$

DELIMITER ;