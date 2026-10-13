-- Fresh Mediato customer, mediator, booking, review, and transaction schema.
-- Existing deployments should run table.php to create these tables and migrate
-- legacy admin/customer accounts without deleting the archived job-board data.

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    avatar_url VARCHAR(255),
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer_password_reset (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer_password_reset_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mediators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(255),
    phone VARCHAR(20) NOT NULL,
    university VARCHAR(100),
    major VARCHAR(100),
    bio TEXT,
    specialties JSON,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    rating FLOAT DEFAULT 5.0,
    total_cases INT DEFAULT 0,
    status ENUM('online', 'busy', 'offline') DEFAULT 'offline',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mediators_status_rating (status, rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    mediator_id INT NOT NULL,
    conflict_type ENUM('TRO_NHA_O', 'VA_CHAM_GIAO_THONG', 'GIAO_DICH_MUA_BAN', 'VAY_MUON_TAI_CHINH', 'KHONG_GIAN_SONG', 'MOC_TINH_CAM_GIA_DINH', 'HOC_TAP_VIEC_NHOM', 'KHAC') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    consultation_mode ENUM('online', 'onsite') NOT NULL DEFAULT 'online',
    incident_location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    urgency_level ENUM('IMMEDIATE', 'SCHEDULED') DEFAULT 'IMMEDIATE',
    scheduled_time DATETIME,
    duration_hours DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mediator_id) REFERENCES mediators(id) ON DELETE CASCADE,
    INDEX idx_bookings_customer_created (customer_id, created_at),
    INDEX idx_bookings_mediator_status (mediator_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    mediator_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mediator_id) REFERENCES mediators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('CASH', 'BANK_TRANSFER', 'MOMO', 'VNPAY') DEFAULT 'CASH',
    payment_status ENUM('UNPAID', 'PAID', 'REFUNDED') DEFAULT 'UNPAID',
    transaction_code VARCHAR(100),
    paid_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_transactions_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
