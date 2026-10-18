<?php
include_once 'config.php';

$existingMediatorTable = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hoa_giai_vien'");
if (!$existingMediatorTable) {
    throw new RuntimeException('Could not inspect hoa_giai_vien: ' . $conn->error);
}
if ($existingMediatorTable && $existingMediatorTable->num_rows > 0) {
    $existingMediatorColumns = $conn->query("SHOW COLUMNS FROM `hoa_giai_vien`");
    if (!$existingMediatorColumns) {
        throw new RuntimeException('Could not inspect hoa_giai_vien columns: ' . $conn->error);
    }
    $mediatorColumnNames = [];
    while ($column = $existingMediatorColumns->fetch_assoc()) {
        $mediatorColumnNames[] = $column['Field'];
    }

    $requiredMediatorColumns = ['id', 'full_name', 'avatar_url', 'university', 'major', 'bio', 'specialties', 'hourly_rate', 'rating', 'total_cases', 'status'];
    if (!in_array('full_name', $mediatorColumnNames, true)) {
        $legacyTable = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hoa_giai_vien_legacy_jobs'");
        if ($legacyTable && $legacyTable->num_rows > 0) {
            throw new RuntimeException('Cannot migrate hoa_giai_vien: the backup table hoa_giai_vien_legacy_jobs already exists.');
        }
        if ($conn->query("RENAME TABLE `hoa_giai_vien` TO `hoa_giai_vien_legacy_jobs`") !== true) {
            throw new RuntimeException('Could not preserve the legacy job table: ' . $conn->error);
        }
    } else {
        $missingColumns = array_diff($requiredMediatorColumns, $mediatorColumnNames);
        if ($missingColumns) {
            throw new RuntimeException('The existing hoa_giai_vien schema is incomplete; missing columns: ' . implode(', ', $missingColumns));
        }
    }
}

$schema = [
    'user' => "CREATE TABLE IF NOT EXISTS user (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL,
        user_type VARCHAR(30) NOT NULL,
        email VARCHAR(50) NOT NULL UNIQUE,
        phone_number VARCHAR(20) UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verify_email BOOLEAN DEFAULT FALSE,
        admin_note TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('unlocked', 'locked') NOT NULL DEFAULT 'unlocked'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'password_reset' => "CREATE TABLE IF NOT EXISTS password_reset (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_password_reset_user (user_id),
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'ho_so_khach_hang' => "CREATE TABLE IF NOT EXISTS ho_so_khach_hang (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        jobseeker_id INT UNSIGNED NOT NULL,
        profile_description TEXT,
        profile_pic VARCHAR(255),
        phone_number VARCHAR(20) UNIQUE,
        gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
        address VARCHAR(255),
        date_of_birth DATE,
        marital_status VARCHAR(50),
        cv_file VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (jobseeker_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'ho_so_hoa_giai_vien' => "CREATE TABLE IF NOT EXISTS ho_so_hoa_giai_vien (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employer_id INT UNSIGNED NOT NULL,
        profile_description TEXT,
        profile_pic VARCHAR(255),
        phone_number VARCHAR(20) UNIQUE,
        gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
        address VARCHAR(255),
        date_of_birth DATE,
        marital_status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employer_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'company' => "CREATE TABLE IF NOT EXISTS company (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employer_id INT(6) UNSIGNED NOT NULL,
        company_name VARCHAR(100) NOT NULL,
        company_address VARCHAR(255) NOT NULL,
        industry VARCHAR(100) NOT NULL,
        company_size VARCHAR(50),
        information TEXT,
        company_logo VARCHAR(255),
        banner_logo VARCHAR(255),
        status ENUM('pending_review', 'approved', 'rejected') DEFAULT 'pending_review',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employer_id) REFERENCES user(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'hoa_giai_vien' => "CREATE TABLE IF NOT EXISTS hoa_giai_vien (
        id INT PRIMARY KEY AUTO_INCREMENT,
        full_name VARCHAR(100),
        avatar_url VARCHAR(255),
        university VARCHAR(100),
        major VARCHAR(100),
        bio TEXT,
        specialties JSON,
        hourly_rate DECIMAL(10,2),
        rating FLOAT DEFAULT 5.0,
        total_cases INT DEFAULT 0,
        status ENUM('online', 'busy', 'offline')
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'feedback' => "CREATE TABLE IF NOT EXISTS feedback (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        user_type ENUM('jobseeker', 'recruiter', 'admin') NOT NULL,
        content TEXT NOT NULL,
        star_rating TINYINT UNSIGNED NOT NULL CHECK (star_rating BETWEEN 1 AND 5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

$legacyUserTable = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user'");
$hasLegacyUsers = $legacyUserTable && $legacyUserTable->num_rows > 0;
if ($hasLegacyUsers) {
    foreach ($schema as $tableName => $createTableSql) {
        if ($conn->query($createTableSql) !== true) {
            throw new RuntimeException("Could not create {$tableName}: " . $conn->error);
        }
    }
}

$userStatusColumn = null;
if ($hasLegacyUsers) {
    $userStatusColumn = $conn->query("SHOW COLUMNS FROM `user` LIKE 'status'");
    if ($userStatusColumn && $userStatusColumn->num_rows === 0) {
        if ($conn->query("ALTER TABLE `user` ADD COLUMN status ENUM('unlocked', 'locked') NOT NULL DEFAULT 'unlocked' AFTER updated_at") !== true) {
            throw new RuntimeException('Could not add user.status: ' . $conn->error);
        }
    }
}

$bookingSchema = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'customer_password_reset' => "CREATE TABLE IF NOT EXISTS customer_password_reset (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_customer_password_reset_user (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'mediators' => "CREATE TABLE IF NOT EXISTS mediators (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT UNIQUE,
        full_name VARCHAR(100) NOT NULL,
        avatar_url VARCHAR(255),
        phone VARCHAR(20) NOT NULL,
        location VARCHAR(255),
        province_location VARCHAR(100),
        district_location VARCHAR(100),
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
        INDEX idx_mediators_status_rating (status, rating),
        INDEX idx_mediators_location (province_location, district_location)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'bookings' => "CREATE TABLE IF NOT EXISTS bookings (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'transactions' => "CREATE TABLE IF NOT EXISTS transactions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($bookingSchema as $tableName => $createTableSql) {
    if ($conn->query($createTableSql) !== true) {
        throw new RuntimeException("Could not create {$tableName}: " . $conn->error);
    }
}

$mediatorColumnsResult = $conn->query("SHOW COLUMNS FROM `mediators`");
if (!$mediatorColumnsResult) {
    throw new RuntimeException('Could not inspect mediator location columns: ' . $conn->error);
}
$mediatorColumns = [];
while ($column = $mediatorColumnsResult->fetch_assoc()) {
    $mediatorColumns[] = $column['Field'];
}
foreach (['province_location', 'district_location'] as $locationColumn) {
    if (!in_array($locationColumn, $mediatorColumns, true)
        && $conn->query("ALTER TABLE `mediators` ADD COLUMN `{$locationColumn}` VARCHAR(100) NULL") !== true) {
        throw new RuntimeException("Could not add mediators.{$locationColumn}: " . $conn->error);
    }
}
$locationIndex = $conn->query("SHOW INDEX FROM `mediators` WHERE Key_name = 'idx_mediators_location'");
if (!$locationIndex) {
    throw new RuntimeException('Could not inspect mediator location index: ' . $conn->error);
}
if ($locationIndex->num_rows === 0
    && $conn->query('ALTER TABLE `mediators` ADD INDEX idx_mediators_location (province_location, district_location)') !== true) {
    throw new RuntimeException('Could not add mediator location index: ' . $conn->error);
}

$existingUsers = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user'");
if ($existingUsers && $existingUsers->num_rows > 0) {
    $legacyUsers = $conn->query(
        "INSERT IGNORE INTO users (id, full_name, email, phone, password_hash, role, created_at, status, admin_note)
         SELECT id, username, email, COALESCE(NULLIF(phone_number, ''), CONCAT('legacy-', id)),
                password, IF(user_type = 'admin', 'admin', 'customer'), created_at,
                IF(status = 'locked', 'locked', 'active'), admin_note
         FROM `user`
         WHERE user_type IN ('admin', 'jobseeker')"
    );
    if ($legacyUsers !== true) {
        throw new RuntimeException('Could not migrate existing customer/admin accounts: ' . $conn->error);
    }
}

$mediatorTable = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hoa_giai_vien'");
if ($mediatorTable && $mediatorTable->num_rows > 0) {
    $mediatorColumns = $conn->query("SHOW COLUMNS FROM `hoa_giai_vien`");
    $fields = [];
    while ($column = $mediatorColumns->fetch_assoc()) {
        $fields[] = $column['Field'];
    }
    if (in_array('full_name', $fields, true) && in_array('specialties', $fields, true)) {
        $migrateMediators = $conn->query(
            "INSERT IGNORE INTO mediators
                (id, full_name, avatar_url, phone, university, major, bio, specialties, hourly_rate, rating, total_cases, status)
             SELECT id, COALESCE(NULLIF(full_name, ''), CONCAT('Hòa giải viên ', id)), avatar_url,
                CONCAT('pending-', id), university, major, bio, specialties,
                COALESCE(hourly_rate, 0.00), COALESCE(rating, 5.0), COALESCE(total_cases, 0),
                COALESCE(status, 'offline')
             FROM hoa_giai_vien"
        );
        if ($migrateMediators !== true) {
            throw new RuntimeException('Could not migrate existing mediator profiles: ' . $conn->error);
        }
    }
}

echo 'Mediato customer and booking tables are ready.';
