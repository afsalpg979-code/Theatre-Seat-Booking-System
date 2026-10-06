-- Smart Theatre ERP foundation schema
-- Compatible with MariaDB/MySQL.
-- This migration intentionally adds new ERP tables without deleting
-- or modifying the existing legacy booking tables.

CREATE TABLE IF NOT EXISTS erp_roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  is_system_role TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_erp_rp_role FOREIGN KEY (role_id) REFERENCES erp_roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_rp_permission FOREIGN KEY (permission_id) REFERENCES erp_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_theatres (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(150) NOT NULL,
  address TEXT NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(100) NULL,
  pincode VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  gst_number VARCHAR(40) NULL,
  status ENUM('ACTIVE','INACTIVE','MAINTENANCE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_theatres_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_screens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  theatre_id INT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(100) NOT NULL,
  screen_type ENUM('STANDARD','3D','IMAX','PREMIUM','OTHER') NOT NULL DEFAULT 'STANDARD',
  capacity INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('ACTIVE','INACTIVE','MAINTENANCE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_screen_code (theatre_id, code),
  CONSTRAINT fk_erp_screens_theatre FOREIGN KEY (theatre_id) REFERENCES erp_theatres(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_seat_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  default_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_seat_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_seats (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  screen_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL,
  seat_code VARCHAR(20) NOT NULL,
  row_label VARCHAR(10) NULL,
  seat_number INT UNSIGNED NULL,
  seat_type ENUM('NORMAL','COUPLE','WHEELCHAIR','PREMIUM') NOT NULL DEFAULT 'NORMAL',
  status ENUM('ACTIVE','BLOCKED','MAINTENANCE','DISABLED') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_seat_code (screen_id, seat_code),
  CONSTRAINT fk_erp_seats_screen FOREIGN KEY (screen_id) REFERENCES erp_screens(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_seats_category FOREIGN KEY (category_id) REFERENCES erp_seat_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_movies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  language VARCHAR(60) NULL,
  genre VARCHAR(100) NULL,
  certification VARCHAR(30) NULL,
  duration_minutes SMALLINT UNSIGNED NULL,
  director VARCHAR(150) NULL,
  cast_text TEXT NULL,
  description TEXT NULL,
  poster_url TEXT NULL,
  trailer_url TEXT NULL,
  release_date DATE NULL,
  status ENUM('DRAFT','UPCOMING','NOW_SHOWING','ENDED','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_movies_status (status),
  KEY idx_erp_movies_release_date (release_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_shows (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  movie_id INT UNSIGNED NOT NULL,
  screen_id INT UNSIGNED NOT NULL,
  show_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NULL,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('SCHEDULED','OPEN','SOLD_OUT','CANCELLED','COMPLETED') NOT NULL DEFAULT 'SCHEDULED',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_shows_date (show_date),
  KEY idx_erp_shows_movie_date (movie_id, show_date),
  CONSTRAINT fk_erp_shows_movie FOREIGN KEY (movie_id) REFERENCES erp_movies(id) ON DELETE RESTRICT,
  CONSTRAINT fk_erp_shows_screen FOREIGN KEY (screen_id) REFERENCES erp_screens(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_show_seats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  show_id INT UNSIGNED NOT NULL,
  seat_id INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('AVAILABLE','LOCKED','BOOKED','BLOCKED') NOT NULL DEFAULT 'AVAILABLE',
  locked_until DATETIME NULL,
  booking_id INT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_show_seat (show_id, seat_id),
  KEY idx_erp_show_seat_status (show_id, status),
  CONSTRAINT fk_erp_show_seats_show FOREIGN KEY (show_id) REFERENCES erp_shows(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_show_seats_seat FOREIGN KEY (seat_id) REFERENCES erp_seats(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_bookings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_reference VARCHAR(40) NOT NULL,
  show_id INT UNSIGNED NOT NULL,
  customer_id INT NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_phone VARCHAR(30) NULL,
  customer_email VARCHAR(150) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('PENDING','CONFIRMED','CANCELLED','REFUNDED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_booking_reference (booking_reference),
  KEY idx_erp_bookings_show (show_id),
  CONSTRAINT fk_erp_bookings_show FOREIGN KEY (show_id) REFERENCES erp_shows(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_booking_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  show_seat_id BIGINT UNSIGNED NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_booking_item (booking_id, show_seat_id),
  CONSTRAINT fk_erp_booking_items_booking FOREIGN KEY (booking_id) REFERENCES erp_bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_booking_items_show_seat FOREIGN KEY (show_seat_id) REFERENCES erp_show_seats(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NULL,
  payment_reference VARCHAR(80) NOT NULL,
  method ENUM('CASH','UPI','CARD','NETBANKING','WALLET','OTHER') NOT NULL,
  gateway VARCHAR(50) NULL,
  gateway_order_id VARCHAR(120) NULL,
  gateway_payment_id VARCHAR(120) NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('PENDING','AUTHORIZED','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_payment_reference (payment_reference),
  CONSTRAINT fk_erp_payments_booking FOREIGN KEY (booking_id) REFERENCES erp_bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sku VARCHAR(50) NOT NULL,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0,
  is_food_item TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_products_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_inventory_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  transaction_type ENUM('OPENING','PURCHASE','SALE','ADJUSTMENT','WASTE','RETURN','TRANSFER_IN','TRANSFER_OUT') NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  reference_type VARCHAR(40) NULL,
  reference_id BIGINT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_inventory_product_date (product_id, created_at),
  CONSTRAINT fk_erp_inventory_product FOREIGN KEY (product_id) REFERENCES erp_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_suppliers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  tax_number VARCHAR(50) NULL,
  status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_purchase_orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  supplier_id INT UNSIGNED NOT NULL,
  order_number VARCHAR(50) NOT NULL,
  status ENUM('DRAFT','PENDING','APPROVED','RECEIVED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  ordered_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_po_number (order_number),
  CONSTRAINT fk_erp_po_supplier FOREIGN KEY (supplier_id) REFERENCES erp_suppliers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_purchase_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  purchase_order_id BIGINT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  CONSTRAINT fk_erp_purchase_items_po FOREIGN KEY (purchase_order_id) REFERENCES erp_purchase_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_purchase_items_product FOREIGN KEY (product_id) REFERENCES erp_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_departments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_department_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_employees (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_code VARCHAR(40) NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  department_id INT UNSIGNED NULL,
  designation VARCHAR(100) NULL,
  joining_date DATE NULL,
  status ENUM('ACTIVE','ON_LEAVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_employee_code (employee_code),
  CONSTRAINT fk_erp_employees_department FOREIGN KEY (department_id) REFERENCES erp_departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_shifts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_employee_attendance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id INT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  shift_id INT UNSIGNED NULL,
  check_in DATETIME NULL,
  check_out DATETIME NULL,
  status ENUM('PRESENT','ABSENT','LATE','HALF_DAY','LEAVE') NOT NULL DEFAULT 'PRESENT',
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_attendance_employee_date (employee_id, attendance_date),
  CONSTRAINT fk_erp_attendance_employee FOREIGN KEY (employee_id) REFERENCES erp_employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_attendance_shift FOREIGN KEY (shift_id) REFERENCES erp_shifts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_assets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  asset_code VARCHAR(50) NOT NULL,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NULL,
  theatre_id INT UNSIGNED NULL,
  screen_id INT UNSIGNED NULL,
  purchase_date DATE NULL,
  warranty_until DATE NULL,
  status ENUM('ACTIVE','MAINTENANCE','RETIRED','LOST') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_asset_code (asset_code),
  CONSTRAINT fk_erp_assets_theatre FOREIGN KEY (theatre_id) REFERENCES erp_theatres(id) ON DELETE SET NULL,
  CONSTRAINT fk_erp_assets_screen FOREIGN KEY (screen_id) REFERENCES erp_screens(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_maintenance_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asset_id INT UNSIGNED NULL,
  theatre_id INT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  status ENUM('OPEN','ASSIGNED','IN_PROGRESS','RESOLVED','CLOSED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at DATETIME NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_erp_maintenance_asset FOREIGN KEY (asset_id) REFERENCES erp_assets(id) ON DELETE SET NULL,
  CONSTRAINT fk_erp_maintenance_theatre FOREIGN KEY (theatre_id) REFERENCES erp_theatres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_finance_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_type ENUM('REVENUE','EXPENSE','REFUND') NOT NULL,
  category VARCHAR(80) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  reference_type VARCHAR(40) NULL,
  reference_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  entry_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_finance_date_type (entry_date, entry_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_type VARCHAR(30) NOT NULL DEFAULT 'USER',
  actor_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  old_values LONGTEXT NULL,
  new_values LONGTEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_audit_entity (entity_type, entity_id),
  KEY idx_erp_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_ai_queries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id INT UNSIGNED NULL,
  question TEXT NOT NULL,
  intent VARCHAR(100) NULL,
  answer LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_ai_predictions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  prediction_type ENUM('REVENUE','OCCUPANCY','INVENTORY','DEMAND','OTHER') NOT NULL,
  target_date DATE NULL,
  entity_type VARCHAR(60) NULL,
  entity_id BIGINT UNSIGNED NULL,
  predicted_value DECIMAL(14,4) NULL,
  confidence DECIMAL(6,4) NULL,
  model_version VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_ai_prediction_type_date (prediction_type, target_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO erp_roles (name, description, is_system_role) VALUES
('SUPER_ADMIN','Full ERP access',1),
('THEATRE_MANAGER','Theatre operations and management',1),
('BOOKING_MANAGER','Bookings, shows and tickets',1),
('INVENTORY_MANAGER','Inventory and procurement',1),
('HR_MANAGER','Employees, shifts and attendance',1),
('FINANCE_MANAGER','Finance and revenue',1),
('MAINTENANCE_MANAGER','Assets and maintenance',1),
('EMPLOYEE','Operational employee access',1);

INSERT IGNORE INTO erp_permissions (code, description) VALUES
('dashboard.view','View ERP dashboard'),
('theatre.manage','Manage theatres'),
('screen.manage','Manage screens'),
('seat.manage','Manage seats'),
('movie.manage','Manage movies'),
('show.manage','Manage shows'),
('booking.manage','Manage bookings'),
('payment.manage','Manage payments'),
('ticket.verify','Verify tickets'),
('pos.manage','Manage POS'),
('inventory.manage','Manage inventory'),
('procurement.manage','Manage procurement'),
('hr.manage','Manage HR'),
('maintenance.manage','Manage maintenance'),
('finance.manage','Manage finance'),
('reports.view','View reports'),
('ai.use','Use AI assistant'),
('audit.view','View audit logs'),
('settings.manage','Manage system settings');
