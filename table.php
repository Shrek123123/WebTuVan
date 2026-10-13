<?php
include_once 'config.php'; // Kết nối đến cơ sở dữ liệu MySQL

// Tạo bảng user
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS user (
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
)");
if ($stmt === TRUE) {
    echo "Table user created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Bổ sung status cho các database đã được tạo trước đó.
$user_status_column = $conn->query("SHOW COLUMNS FROM `user` LIKE 'status'");
if ($user_status_column && $user_status_column->num_rows === 0) {
    if ($conn->query("ALTER TABLE `user` ADD COLUMN status ENUM('unlocked', 'locked') NOT NULL DEFAULT 'unlocked' AFTER updated_at") === TRUE) {
        echo "Column user.status added successfully <br>";
    } else {
        echo "Error adding user.status: " . $conn->error . "<br>";
    }
}

$stmt = $conn->query("CREATE TABLE IF NOT EXISTS password_reset (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_password_reset_user (user_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
)");
if ($stmt !== TRUE) {
    echo "Error creating password_reset table: " . $conn->error . "<br>";
}

// Tạo bảng ho_so_khach_hang

$stmt = $conn->query("CREATE TABLE IF NOT EXISTS payment_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    transaction_code VARCHAR(100) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'VND',
    status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    employer_id INT UNSIGNED NULL,
    job_payload JSON NULL,
    job_id INT UNSIGNED NULL,
    provider_transaction_id VARCHAR(100) NULL UNIQUE,
    provider_reference VARCHAR(255) NULL,
    raw_webhook JSON NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
if ($stmt !== TRUE) {
    echo "Error creating payment_orders table: " . $conn->error . "<br>";
}
$conn->query("ALTER TABLE payment_orders ADD COLUMN IF NOT EXISTS employer_id INT UNSIGNED NULL");
$conn->query("ALTER TABLE payment_orders ADD COLUMN IF NOT EXISTS job_payload JSON NULL");
$conn->query("ALTER TABLE payment_orders ADD COLUMN IF NOT EXISTS job_id INT UNSIGNED NULL");

$paymentProviderColumn = $conn->query("SHOW COLUMNS FROM payment_orders LIKE 'provider_transaction_id'");
if ($paymentProviderColumn && $paymentProviderColumn->num_rows > 0) {
    $paymentProviderDefinition = $paymentProviderColumn->fetch_assoc();
    if (stripos((string) ($paymentProviderDefinition['Type'] ?? ''), 'bigint') === 0) {
        $conn->query("ALTER TABLE payment_orders MODIFY provider_transaction_id VARCHAR(100) NULL UNIQUE");
    }
}
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS ho_so_khach_hang(
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
)");
if ($stmt === TRUE) {
    echo "Table ho_so_khach_hang created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Tạo bảng ho_so_hoa_giai_vien
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS ho_so_hoa_giai_vien (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    profile_description TEXT, -- Có thể để NULL nếu không bắt buộc
    profile_pic VARCHAR(255),
    -- Các trường mới được thêm vào
    phone_number VARCHAR(20) UNIQUE, -- Số điện thoại, có thể để UNIQUE nếu bạn muốn mỗi số chỉ thuộc về 1 profile
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'), -- Giới tính
    address VARCHAR(255), -- Địa chỉ hiện tại
    date_of_birth DATE, -- Ngày sinh (yyyy-mm-dd)
    marital_status VARCHAR(50), -- Tình trạng hôn nhân (ví dụ: 'Single', 'Married', 'Divorced')
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Thêm trường updated_at để theo dõi sửa đổi
    FOREIGN KEY (employer_id) REFERENCES user(id) ON DELETE CASCADE
)");
if ($stmt === TRUE) {
    echo "Table ho_so_hoa_giai_vien created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Tạo bảng company
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS company (
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
)");
if ($stmt === TRUE) {
    echo "Table company created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

$stmt = $conn->query("CREATE TABLE IF NOT EXISTS prominent_company (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT(6) UNSIGNED NOT NULL,
    employer_id INT(6) UNSIGNED NOT NULL,
    transaction_code VARCHAR(100) NOT NULL UNIQUE,
    payment_link_id VARCHAR(100) NULL,
    checkout_url TEXT NULL,
    amount BIGINT UNSIGNED NOT NULL DEFAULT 69000,
    status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    confirmation_email_status ENUM('pending', 'sending', 'sent') NOT NULL DEFAULT 'pending',
    confirmation_email_sent_at DATETIME NULL,
    provider_reference VARCHAR(255) NULL,
    raw_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prominent_company_company_status (company_id, status),
    INDEX idx_prominent_company_employer (employer_id),
    CONSTRAINT fk_prominent_company_company FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
    CONSTRAINT fk_prominent_company_employer FOREIGN KEY (employer_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($stmt !== TRUE) {
    echo "Error creating prominent_company table: " . $conn->error . "<br>";
}
$prominentEmailStatusColumn = $conn->query("SHOW COLUMNS FROM prominent_company LIKE 'confirmation_email_status'");
if ($prominentEmailStatusColumn && $prominentEmailStatusColumn->num_rows === 0) {
    $conn->query("ALTER TABLE prominent_company ADD COLUMN confirmation_email_status ENUM('pending', 'sending', 'sent') NOT NULL DEFAULT 'pending'");
}
$prominentEmailSentAtColumn = $conn->query("SHOW COLUMNS FROM prominent_company LIKE 'confirmation_email_sent_at'");
if ($prominentEmailSentAtColumn && $prominentEmailSentAtColumn->num_rows === 0) {
    $conn->query('ALTER TABLE prominent_company ADD COLUMN confirmation_email_sent_at DATETIME NULL');
}

// Tạo bảng job
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS hoa_giai_vien (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    posted_by_employer_id INT UNSIGNED NOT NULL,
    company_id INT(6) UNSIGNED NOT NULL, -- Cột liên kết với bảng company
    job_title VARCHAR(100) NOT NULL,
    job_description TEXT NOT NULL,
    job_benefit TEXT,
    job_requirement TEXT,
    job_position VARCHAR(100) NOT NULL,
    job_location VARCHAR(100) NOT NULL,
    job_detailed_location VARCHAR(255) NOT NULL,
    job_location_district VARCHAR(100) NOT NULL,
    no_employee_needed INT UNSIGNED NOT NULL,
    employee_gender VARCHAR(20) NULL,
    salary VARCHAR(30) NOT NULL,
    post_duration INT UNSIGNED NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20),
    job_type VARCHAR(50) NOT NULL,
    required_certification VARCHAR(255),
    job_experience VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('pending_review', 'approved', 'rejected') DEFAULT 'pending_review',
    admin_notes TEXT,
    job_post_number_by_employer INT(11) NOT NULL DEFAULT 0,
    FOREIGN KEY (posted_by_employer_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE ON UPDATE CASCADE
)");
if ($stmt === TRUE) {
    echo "Table hoa_giai_vien created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

$stmt = $conn->query("CREATE TABLE IF NOT EXISTS job_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NULL,
    order_code BIGINT UNSIGNED NOT NULL UNIQUE,
    payment_link_id VARCHAR(100) NULL,
    checkout_url TEXT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    confirmation_email_status ENUM('pending', 'sending', 'sent') NOT NULL DEFAULT 'pending',
    job_payload JSON NOT NULL,
    provider_reference VARCHAR(255) NULL,
    raw_response JSON NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_payments_employer (employer_id),
    INDEX idx_job_payments_job (job_id),
    CONSTRAINT fk_job_payments_employer FOREIGN KEY (employer_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_payments_job FOREIGN KEY (job_id) REFERENCES hoa_giai_vien(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($stmt !== TRUE) {
    echo "Error creating job_payments table: " . $conn->error . "<br>";
}

// Tạo bảng application
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS application (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    jobseeker_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'viewed', 'interviewed', 'hired', 'rejected') DEFAULT 'pending',
    fullname VARCHAR(100) NOT NULL,
    phonenumber VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cv_file VARCHAR(255),
    cover_letter TEXT NULL,
    allow_search BOOLEAN DEFAULT FALSE,
    hidden_from_employer BOOLEAN DEFAULT FALSE,
    UNIQUE (job_id, jobseeker_id),
    numbered_of_fifth_application INT UNSIGNED DEFAULT 1,
    FOREIGN KEY (job_id) REFERENCES hoa_giai_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES user(id) ON DELETE CASCADE
)");
if ($stmt === TRUE) {
    echo "Table application created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Thêm cột cover_letter cho các database đã được tạo trước đó.
$cover_letter_column = $conn->query("SHOW COLUMNS FROM application LIKE 'cover_letter'");
if ($cover_letter_column && $cover_letter_column->num_rows === 0) {
    if ($conn->query("ALTER TABLE application ADD COLUMN cover_letter TEXT NULL AFTER cv_file") === TRUE) {
        echo "Column application.cover_letter added successfully <br>";
    } else {
        echo "Error adding application.cover_letter: " . $conn->error . "<br>";
    }
}

$application_columns = [
    'allow_search' => 'ALTER TABLE application ADD COLUMN allow_search BOOLEAN DEFAULT FALSE',
    'hidden_from_employer' => 'ALTER TABLE application ADD COLUMN hidden_from_employer BOOLEAN DEFAULT FALSE',
    'numbered_of_fifth_application' => 'ALTER TABLE application ADD COLUMN numbered_of_fifth_application INT UNSIGNED DEFAULT 1'
];
foreach ($application_columns as $column_name => $alter_query) {
    $column_result = $conn->query("SHOW COLUMNS FROM application LIKE '$column_name'");
    if ($column_result && $column_result->num_rows === 0) {
        if ($conn->query($alter_query) === TRUE) {
            echo "Column application.$column_name added successfully <br>";
        } else {
            echo "Error adding application.$column_name: " . $conn->error . "<br>";
        }
    }
}

// Tạo bảng feedback
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_type ENUM('jobseeker', 'recruiter', 'admin') NOT NULL,
    content TEXT NOT NULL,
    star_rating TINYINT UNSIGNED NOT NULL CHECK (star_rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
)");
if ($stmt === TRUE) {
    echo "Table feedback created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Tạo bảng company_followers
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS company_followers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    jobseeker_id INT UNSIGNED NOT NULL,
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (company_id, jobseeker_id),
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES user(id) ON DELETE CASCADE
)");
if ($stmt === TRUE) {
    echo "Table company_followers created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

//Tạo bảng job_interest_count
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS job_interest_count (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    jobseeker_id INT(6) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_job_interest (job_id, jobseeker_id),
    FOREIGN KEY (job_id) REFERENCES hoa_giai_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES user(id) ON DELETE CASCADE
)");
if ($stmt === TRUE) {
    echo "Table job_interest_count created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

$interest_jobseeker_column = $conn->query("SHOW COLUMNS FROM job_interest_count LIKE 'jobseeker_id'");
if ($interest_jobseeker_column && $interest_jobseeker_column->num_rows === 0) {
    $conn->query("ALTER TABLE job_interest_count ADD COLUMN jobseeker_id INT(6) UNSIGNED NULL AFTER job_id");
}

$interest_count_column = $conn->query("SHOW COLUMNS FROM job_interest_count LIKE 'interest_count'");
if ($interest_count_column && $interest_count_column->num_rows > 0) {
    $conn->query("DELETE FROM job_interest_count WHERE jobseeker_id IS NULL");
    $conn->query("ALTER TABLE job_interest_count DROP COLUMN interest_count");
}

$conn->query("DELETE FROM job_interest_count WHERE jobseeker_id IS NULL");
$conn->query("ALTER TABLE job_interest_count MODIFY jobseeker_id INT(6) UNSIGNED NOT NULL");

$interest_unique_index = $conn->query("SHOW INDEX FROM job_interest_count WHERE Key_name = 'unique_job_interest'");
if ($interest_unique_index && $interest_unique_index->num_rows === 0) {
    $conn->query("ALTER TABLE job_interest_count ADD UNIQUE KEY unique_job_interest (job_id, jobseeker_id)");
}

$interest_user_fk = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_interest_count' AND COLUMN_NAME = 'jobseeker_id' AND REFERENCED_TABLE_NAME = 'user'");
if ($interest_user_fk && $interest_user_fk->num_rows === 0) {
    $conn->query("ALTER TABLE job_interest_count ADD CONSTRAINT fk_job_interest_jobseeker FOREIGN KEY (jobseeker_id) REFERENCES user(id) ON DELETE CASCADE");
}
// Tạo bảng saved_jobs
$stmt = $conn->query("CREATE TABLE IF NOT EXISTS saved_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES hoa_giai_vien(id) ON DELETE CASCADE,
    UNIQUE (user_id, job_id)
)");
if ($stmt === TRUE) {
    echo "Table saved_jobs created successfully <br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Dữ liệu mẫu cho bảng user
// Sample data for user table
// $username = 'Employer1';
// $user_type = 'employer';
// $email = 'employer1@gmail.com';
// $password_plain = '123';
// $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// $sql_user = "INSERT INTO user (username, user_type, email, password) VALUES (?, ?, ?, ?)";
// $stmt_user = $conn->prepare($sql_user);
// $sample_employer_id = null;
// if ($stmt_user) {
//     $stmt_user->bind_param("ssss", $username, $user_type, $email, $password_hashed);
//     if ($stmt_user->execute()) {
//         $sample_employer_id = $conn->insert_id;
//         echo "Sample user data inserted successfully<br>";
//     } else {
//         echo "Error inserting sample user data: " . $stmt_user->error . "<br>";
//     }
//     $stmt_user->close();
// } else {
//     echo "Error preparing statement for user table: " . $conn->error . "<br>";
// }

// $sample_company_id = null;
// if ($sample_employer_id) {
//     $default_company_logos = [
//         'kinh_doanh_ban_hang' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=800&q=80',
//         'marketing_truyen_thong' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80',
//         'tai_chinh_ke_toan_ngan_hang' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=800&q=80',
//         'cong_nghe_thong_tin' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80',
//         'y_te_duoc_pham' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=800&q=80',
//         'nha_nuoc_cong_vu' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
//         'giao_duc_dao_tao' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80',
//         'san_xuat_van_hanh_day_chuyen' => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=800&q=80',
//         'van_tai_logistics' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&q=80',
//         'bat_dong_san' => 'https://images.unsplash.com/photo-1460317442991-0ec209397118?auto=format&fit=crop&w=800&q=80',
//         'bao_hiem' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?auto=format&fit=crop&w=800&q=80',
//         'xay_dung_kien_truc' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=800&q=80',
//         'du_lich_khach_san_nha_hang' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=800&q=80',
//         'luat' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=800&q=80',
//         'thiet_ke_nghe_thuat' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=800&q=80',
//         'nong_nghiep' => 'https://images.unsplash.com/photo-1464226184884-fa520f1b8679?auto=format&fit=crop&w=800&q=80',
//         'food_beverage' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=800&q=80',
//         'nhan_su' => 'https://images.unsplash.com/photo-1556157382-97eda2d62296?auto=format&fit=crop&w=800&q=80',
//         'hanh_chinh_van_phong' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=800&q=80',
//         'ky_thuat' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
//         'khac' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80'
//     ];

//     $company_seed = [
//         'kinh_doanh_ban_hang' => ['company_name' => 'Viet Market Plus', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '100-200', 'information' => 'Retail and wholesale distribution business.'],
//         'marketing_truyen_thong' => ['company_name' => 'BrandWave Studio', 'company_address' => 'Da Nang, Vietnam', 'company_size' => '50-100', 'information' => 'Digital marketing and brand storytelling company.'],
//         'tai_chinh_ke_toan_ngan_hang' => ['company_name' => 'Capital Ledger', 'company_address' => 'Ho Chi Minh City, Vietnam', 'company_size' => '200-500', 'information' => 'Finance, accounting and banking advisory provider.'],
//         'cong_nghe_thong_tin' => ['company_name' => 'NeoTech Solutions', 'company_address' => 'Ho Chi Minh City, Vietnam', 'company_size' => '500+', 'information' => 'Software development and IT consulting firm.'],
//         'y_te_duoc_pham' => ['company_name' => 'CareNova Health', 'company_address' => 'Can Tho, Vietnam', 'company_size' => '200-500', 'information' => 'Healthcare and pharmaceutical services provider.'],
//         'nha_nuoc_cong_vu' => ['company_name' => 'Public City Services', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '200+', 'information' => 'Public administration and city service management.'],
//         'giao_duc_dao_tao' => ['company_name' => 'EduFuture Academy', 'company_address' => 'Hai Phong, Vietnam', 'company_size' => '50-100', 'information' => 'Education and professional training provider.'],
//         'san_xuat_van_hanh_day_chuyen' => ['company_name' => 'FlowLine Manufacturing', 'company_address' => 'Binh Duong, Vietnam', 'company_size' => '500+', 'information' => 'Industrial production and operations management.'],
//         'van_tai_logistics' => ['company_name' => 'NorthStar Logistics', 'company_address' => 'Hai Phong, Vietnam', 'company_size' => '200-500', 'information' => 'Freight and supply chain company.'],
//         'bat_dong_san' => ['company_name' => 'Urban Horizon Realty', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '100-200', 'information' => 'Property sales and investment advisory.'],
//         'bao_hiem' => ['company_name' => 'SafeLife Insurance', 'company_address' => 'Ho Chi Minh City, Vietnam', 'company_size' => '300+', 'information' => 'Insurance and risk protection solutions.'],
//         'xay_dung_kien_truc' => ['company_name' => 'BuildCore Architecture', 'company_address' => 'Da Nang, Vietnam', 'company_size' => '200+', 'information' => 'Construction and architectural design firm.'],
//         'du_lich_khach_san_nha_hang' => ['company_name' => 'Crown Stay Group', 'company_address' => 'Nha Trang, Vietnam', 'company_size' => '100-200', 'information' => 'Hospitality and travel services.'],
//         'luat' => ['company_name' => 'LegalPrime Advisory', 'company_address' => 'Ho Chi Minh City, Vietnam', 'company_size' => '20-50', 'information' => 'Corporate law and compliance consulting.'],
//         'thiet_ke_nghe_thuat' => ['company_name' => 'DesignCraft Lab', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '20-50', 'information' => 'Brand design and creative production studio.'],
//         'nong_nghiep' => ['company_name' => 'GreenHarvest Farm', 'company_address' => 'Thanh Hoa, Vietnam', 'company_size' => '100-200', 'information' => 'Agricultural production and food supply chain.'],
//         'food_beverage' => ['company_name' => 'FreshBite Foods', 'company_address' => 'Ho Chi Minh City, Vietnam', 'company_size' => '100-200', 'information' => 'Food and beverage distribution company.'],
//         'nhan_su' => ['company_name' => 'TalentBridge HR', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '20-50', 'information' => 'Human resources and recruitment services.'],
//         'hanh_chinh_van_phong' => ['company_name' => 'OfficeFlow Vietnam', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '50-100', 'information' => 'Administrative operations and office management.'],
//         'ky_thuat' => ['company_name' => 'TechForge Engineering', 'company_address' => 'Da Nang, Vietnam', 'company_size' => '200-500', 'information' => 'Engineering design and industrial solutions.'],
//         'khac' => ['company_name' => 'General Growth Ventures', 'company_address' => 'Hanoi, Vietnam', 'company_size' => '50-100', 'information' => 'Cross-industry business support, consulting and operations.']
//     ];

//     $company_rows = [];
//     foreach ($company_seed as $industry => $company) {
//         $company_name = $conn->real_escape_string($company['company_name']);
//         $company_address = $conn->real_escape_string($company['company_address']);
//         $company_size = $conn->real_escape_string($company['company_size']);
//         $information = $conn->real_escape_string($company['information']);
//         $company_logo = $conn->real_escape_string($company['company_logo'] ?? $default_company_logos[$industry] ?? $default_company_logos['khac']);
//         $banner_logo = $conn->real_escape_string($default_company_logos[$industry] ?? $default_company_logos['khac']);
//         $company_rows[] = "($sample_employer_id, '$company_name', '$company_address', '$industry', '$company_size', '$information', '$company_logo', '$banner_logo')";
//     }

//     $company_sql = "INSERT INTO company (employer_id, company_name, company_address, industry, company_size, information, company_logo, banner_logo) VALUES " . implode(", ", $company_rows) . ";";
//     if ($conn->query($company_sql) === TRUE) {
//         $sample_company_id = $conn->insert_id;
//         echo "Sample companies inserted successfully<br>";
//     } else {
//         echo "Error inserting sample company data: " . $conn->error . "<br>";
//     }
// }

// $company_ids = [];
// if ($sample_employer_id) {
//     $company_lookup = $conn->query("SELECT id, industry FROM company WHERE employer_id = $sample_employer_id ORDER BY id ASC");
//     if ($company_lookup) {
//         while ($row = $company_lookup->fetch_assoc()) {
//             $company_ids[$row['industry']] = (int) $row['id'];
//         }
//     }
// }

// $jobs_seed = [
//     'kinh_doanh_ban_hang' => [
//         ['job_title' => 'Sales Executive', 'job_description' => 'Drive retail and wholesale sales growth through customer acquisition and relationship management.', 'job_benefit' => 'Bonus, travel allowance, annual leave.', 'job_requirement' => 'Good communication, negotiation, 1+ year sales experience.', 'job_position' => 'Sales', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Hai Ba Trung, Hanoi', 'job_location_district' => 'Hai Ba Trung', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$800 - $1200', 'post_duration' => 30, 'contact_email' => 'hr@vietmarketplus.vn', 'contact_phone' => '0901234567', 'job_type' => 'full-time', 'required_certification' => 'Bachelor degree', 'job_experience' => '1 year'],
//         ['job_title' => 'Key Account Manager', 'job_description' => 'Manage strategic business accounts and improve retention with enterprise clients.', 'job_benefit' => 'Commission, healthcare package, mobile allowance.', 'job_requirement' => 'Experience in B2B sales and account management.', 'job_position' => 'Sales Management', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 1, Ho Chi Minh City', 'job_location_district' => 'District 1', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1200 - $1800', 'post_duration' => 45, 'contact_email' => 'careers@vietmarketplus.vn', 'contact_phone' => '0907654321', 'job_type' => 'full-time', 'required_certification' => 'Business degree', 'job_experience' => '2 years']
//     ],
//     'marketing_truyen_thong' => [
//         ['job_title' => 'Digital Marketing Specialist', 'job_description' => 'Plan and optimize digital communication campaigns across social channels and search.', 'job_benefit' => 'Remote support, team training, performance bonus.', 'job_requirement' => 'Google Ads / Meta Ads knowledge and strong analysis.', 'job_position' => 'Marketing', 'job_location' => 'Da Nang', 'job_detailed_location' => 'Hai Chau, Da Nang', 'job_location_district' => 'Hai Chau', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$700 - $1100', 'post_duration' => 30, 'contact_email' => 'jobs@brandwave.vn', 'contact_phone' => '0911223344', 'job_type' => 'full-time', 'required_certification' => 'Marketing certificate', 'job_experience' => '1 year'],
//         ['job_title' => 'Content Creator', 'job_description' => 'Create engaging short-form and long-form content for brand promotion.', 'job_benefit' => 'Creative budget, monthly team events.', 'job_requirement' => 'Strong storytelling, video editing, Canva or Adobe skills.', 'job_position' => 'Creative', 'job_location' => 'Remote', 'job_detailed_location' => 'Remote work', 'job_location_district' => 'Remote', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$600 - $900', 'post_duration' => 45, 'contact_email' => 'creative@brandwave.vn', 'contact_phone' => '0919988776', 'job_type' => 'part-time', 'required_certification' => 'Portfolio required', 'job_experience' => '6 months']
//     ],
//     'tai_chinh_ke_toan_ngan_hang' => [
//         ['job_title' => 'Finance Analyst', 'job_description' => 'Prepare financial reports, forecast budgets and monitor performance.', 'job_benefit' => 'Premium insurance, annual bonus.', 'job_requirement' => 'Excel, accounting knowledge, financial modeling experience.', 'job_position' => 'Finance', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 7, Ho Chi Minh City', 'job_location_district' => 'District 7', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1500', 'post_duration' => 30, 'contact_email' => 'hiring@capitalledger.vn', 'contact_phone' => '0903344556', 'job_type' => 'full-time', 'required_certification' => 'Accounting degree', 'job_experience' => '2 years'],
//         ['job_title' => 'Banking Operations Officer', 'job_description' => 'Support transactions, document processing and customer service tasks.', 'job_benefit' => 'Shift allowance, training support.', 'job_requirement' => 'Good attention to detail, banking procedures knowledge.', 'job_position' => 'Operations', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Dong Da, Hanoi', 'job_location_district' => 'Dong Da', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$900 - $1300', 'post_duration' => 60, 'contact_email' => 'ops@capitalledger.vn', 'contact_phone' => '0905566778', 'job_type' => 'full-time', 'required_certification' => 'Bachelor degree', 'job_experience' => '1 year']
//     ],
//     'cong_nghe_thong_tin' => [
//         ['job_title' => 'Backend Developer', 'job_description' => 'Build and maintain APIs, server-side services and business logic.', 'job_benefit' => 'Macbook, bonus, performance review.', 'job_requirement' => 'PHP, MySQL, REST API experience.', 'job_position' => 'Software Engineering', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'Thu Duc, Ho Chi Minh City', 'job_location_district' => 'Thu Duc', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$1800 - $2500', 'post_duration' => 30, 'contact_email' => 'jobs@neotech.vn', 'contact_phone' => '0901122334', 'job_type' => 'full-time', 'required_certification' => 'IT degree', 'job_experience' => '2 years'],
//         ['job_title' => 'Frontend Developer', 'job_description' => 'Build responsive interfaces and improve user experience across products.', 'job_benefit' => 'Flexible schedule, wellness benefit.', 'job_requirement' => 'JavaScript, HTML, CSS, React knowledge.', 'job_position' => 'Frontend', 'job_location' => 'Remote', 'job_detailed_location' => 'Remote work', 'job_location_district' => 'Remote', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1400 - $2000', 'post_duration' => 45, 'contact_email' => 'frontend@neotech.vn', 'contact_phone' => '0909988001', 'job_type' => 'remote', 'required_certification' => 'Portfolio', 'job_experience' => '1 year']
//     ],
//     'y_te_duoc_pham' => [
//         ['job_title' => 'Nurse', 'job_description' => 'Support patient care and daily medical procedures in a clinical setting.', 'job_benefit' => 'Shift bonus, medical allowance, annual leave.', 'job_requirement' => 'Nursing degree and patient care experience.', 'job_position' => 'Healthcare', 'job_location' => 'Can Tho', 'job_detailed_location' => 'Ninh Kieu, Can Tho', 'job_location_district' => 'Ninh Kieu', 'no_employee_needed' => 2, 'employee_gender' => 'Female', 'salary' => '$700 - $1000', 'post_duration' => 30, 'contact_email' => 'recruit@carenova.vn', 'contact_phone' => '0913344556', 'job_type' => 'full-time', 'required_certification' => 'Nursing certificate', 'job_experience' => '1 year'],
//         ['job_title' => 'Pharmacist Assistant', 'job_description' => 'Support medication preparation and customer advice in the pharmacy.', 'job_benefit' => 'Medical insurance, training assistance.', 'job_requirement' => 'Pharmacy knowledge and customer service skills.', 'job_position' => 'Pharmaceutical', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Cau Giay, Hanoi', 'job_location_district' => 'Cau Giay', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$800 - $1200', 'post_duration' => 45, 'contact_email' => 'careers@carenova.vn', 'contact_phone' => '0917788990', 'job_type' => 'full-time', 'required_certification' => 'Pharmacy certificate', 'job_experience' => '2 years']
//     ],
//     'nha_nuoc_cong_vu' => [
//         ['job_title' => 'Administrative Officer', 'job_description' => 'Coordinate public records, document management and administrative support.', 'job_benefit' => 'Public sector benefits and stable work environment.', 'job_requirement' => 'Strong organization and document control ability.', 'job_position' => 'Administration', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Ba Dinh, Hanoi', 'job_location_district' => 'Ba Dinh', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$600 - $900', 'post_duration' => 60, 'contact_email' => 'hr@publiccity.vn', 'contact_phone' => '0905566443', 'job_type' => 'full-time', 'required_certification' => 'University degree', 'job_experience' => '1 year'],
//         ['job_title' => 'Policy Analyst', 'job_description' => 'Research and evaluate public policy initiatives and administrative procedures.', 'job_benefit' => 'Pension, stable work schedule.', 'job_requirement' => 'Policy analysis and report writing skill.', 'job_position' => 'Policy', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Hoan Kiem, Hanoi', 'job_location_district' => 'Hoan Kiem', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1500', 'post_duration' => 45, 'contact_email' => 'policy@publiccity.vn', 'contact_phone' => '0909911223', 'job_type' => 'full-time', 'required_certification' => 'Relevant degree', 'job_experience' => '2 years']
//     ],
//     'giao_duc_dao_tao' => [
//         ['job_title' => 'Training Coordinator', 'job_description' => 'Manage course schedules, participant support and learning logistics.', 'job_benefit' => 'Flexible schedule, training budget.', 'job_requirement' => 'Strong organization and communication skills.', 'job_position' => 'Education', 'job_location' => 'Hai Phong', 'job_detailed_location' => 'Hong Bang, Hai Phong', 'job_location_district' => 'Hong Bang', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$600 - $900', 'post_duration' => 30, 'contact_email' => 'training@edufuture.vn', 'contact_phone' => '0916543210', 'job_type' => 'full-time', 'required_certification' => 'Education degree', 'job_experience' => '1 year'],
//         ['job_title' => 'English Tutor', 'job_description' => 'Teach communication and learning sessions for youth and adult learners.', 'job_benefit' => 'Competitive hourly rate and teaching materials.', 'job_requirement' => 'CELTA/TEFL or strong English communication skills.', 'job_position' => 'Teaching', 'job_location' => 'Remote', 'job_detailed_location' => 'Remote work', 'job_location_district' => 'Remote', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$350 - $600', 'post_duration' => 30, 'contact_email' => 'tutor@edufuture.vn', 'contact_phone' => '0918877665', 'job_type' => 'part-time', 'required_certification' => 'TESOL preferred', 'job_experience' => '1 year']
//     ],
//     'san_xuat_van_hanh_day_chuyen' => [
//         ['job_title' => 'Production Supervisor', 'job_description' => 'Monitor manufacturing flow, quality and team productivity on shift.', 'job_benefit' => 'Safety allowance and overtime pay.', 'job_requirement' => 'Experience in industrial operations and KPI monitoring.', 'job_position' => 'Manufacturing', 'job_location' => 'Binh Duong', 'job_detailed_location' => 'Thu Dau Mot, Binh Duong', 'job_location_district' => 'Thu Dau Mot', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1500', 'post_duration' => 45, 'contact_email' => 'hr@flowlinevn.vn', 'contact_phone' => '0907778881', 'job_type' => 'full-time', 'required_certification' => 'Production management certificate', 'job_experience' => '3 years'],
//         ['job_title' => 'Maintenance Technician', 'job_description' => 'Maintain equipment reliability and perform preventive maintenance.', 'job_benefit' => 'Uniform, training, team bonus.', 'job_requirement' => 'Mechanical or electrical maintenance experience.', 'job_position' => 'Engineering', 'job_location' => 'Long An', 'job_detailed_location' => 'Tan An, Long An', 'job_location_district' => 'Tan An', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$900 - $1300', 'post_duration' => 60, 'contact_email' => 'maintenance@flowlinevn.vn', 'contact_phone' => '0905522446', 'job_type' => 'full-time', 'required_certification' => 'Technical certificate', 'job_experience' => '2 years']
//     ],
//     'van_tai_logistics' => [
//         ['job_title' => 'Logistics Coordinator', 'job_description' => 'Coordinate shipments, inventory tracking and routing across warehouses.', 'job_benefit' => 'Night shift bonus, travel allowance.', 'job_requirement' => 'Good planning and Excel skills.', 'job_position' => 'Logistics', 'job_location' => 'Hai Phong', 'job_detailed_location' => 'Cat Hai, Hai Phong', 'job_location_district' => 'Cat Hai', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$800 - $1200', 'post_duration' => 30, 'contact_email' => 'logistics@northstar.vn', 'contact_phone' => '0912233445', 'job_type' => 'full-time', 'required_certification' => 'Logistics certificate', 'job_experience' => '1 year'],
//         ['job_title' => 'Warehouse Supervisor', 'job_description' => 'Oversee storage operations, staff assignment and stock accuracy.', 'job_benefit' => 'Team bonus, overtime pay.', 'job_requirement' => 'Warehouse operation experience and leadership skills.', 'job_position' => 'Operations', 'job_location' => 'Binh Duong', 'job_detailed_location' => 'Ben Cat, Binh Duong', 'job_location_district' => 'Ben Cat', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1500', 'post_duration' => 45, 'contact_email' => 'warehouse@northstar.vn', 'contact_phone' => '0915566778', 'job_type' => 'full-time', 'required_certification' => 'Warehouse management certificate', 'job_experience' => '2 years']
//     ],
//     'bat_dong_san' => [
//         ['job_title' => 'Real Estate Consultant', 'job_description' => 'Support customers in buying, selling and leasing property units.', 'job_benefit' => 'Commission, performance bonus.', 'job_requirement' => 'Excellent sales and customer relationship skills.', 'job_position' => 'Sales', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Nam Tu Liem, Hanoi', 'job_location_district' => 'Nam Tu Liem', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$900 - $1400', 'post_duration' => 30, 'contact_email' => 'careers@urbanhorizon.vn', 'contact_phone' => '0919898989', 'job_type' => 'full-time', 'required_certification' => 'Real estate certificate', 'job_experience' => '1 year'],
//         ['job_title' => 'Property Manager', 'job_description' => 'Coordinate tenant and property operations for residential assets.', 'job_benefit' => 'Housing support, annual bonus.', 'job_requirement' => 'Negotiation and property management experience.', 'job_position' => 'Management', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 2, Ho Chi Minh City', 'job_location_district' => 'District 2', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1200 - $1700', 'post_duration' => 60, 'contact_email' => 'pm@urbanhorizon.vn', 'contact_phone' => '0906677889', 'job_type' => 'full-time', 'required_certification' => 'Property management degree', 'job_experience' => '3 years']
//     ],
//     'bao_hiem' => [
//         ['job_title' => 'Insurance Advisor', 'job_description' => 'Present insurance solutions to individuals and SMEs and support renewals.', 'job_benefit' => 'Commission, health benefit, leadership path.', 'job_requirement' => 'Customer service and sales aptitude.', 'job_position' => 'Sales', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'Tan Binh, Ho Chi Minh City', 'job_location_district' => 'Tan Binh', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$800 - $1200', 'post_duration' => 45, 'contact_email' => 'jobs@safelife.vn', 'contact_phone' => '0917744888', 'job_type' => 'full-time', 'required_certification' => 'Insurance license', 'job_experience' => '1 year'],
//         ['job_title' => 'Claims Specialist', 'job_description' => 'Review customer claims, validate information and coordinate settlements.', 'job_benefit' => 'Insurance package and annual bonus.', 'job_requirement' => 'Strong documentation and problem-solving skill.', 'job_position' => 'Operations', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Long Bien, Hanoi', 'job_location_district' => 'Long Bien', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1400', 'post_duration' => 30, 'contact_email' => 'claims@safelife.vn', 'contact_phone' => '0903216547', 'job_type' => 'full-time', 'required_certification' => 'Claims training', 'job_experience' => '2 years']
//     ],
//     'xay_dung_kien_truc' => [
//         ['job_title' => 'Architect', 'job_description' => 'Design building concepts, site planning and construction documentation.', 'job_benefit' => 'Project allowance, healthcare package.', 'job_requirement' => 'Architectural degree and project design experience.', 'job_position' => 'Design', 'job_location' => 'Da Nang', 'job_detailed_location' => 'Thanh Khe, Da Nang', 'job_location_district' => 'Thanh Khe', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1400 - $2000', 'post_duration' => 45, 'contact_email' => 'design@buildcore.vn', 'contact_phone' => '0908181818', 'job_type' => 'full-time', 'required_certification' => 'Architecture license', 'job_experience' => '3 years'],
//         ['job_title' => 'Construction Engineer', 'job_description' => 'Supervise site progress, quality standards and contractor coordination.', 'job_benefit' => 'Site allowance, overtime pay.', 'job_requirement' => 'Civil engineering background and site supervision experience.', 'job_position' => 'Engineering', 'job_location' => 'Quang Nam', 'job_detailed_location' => 'Hoi An, Quang Nam', 'job_location_district' => 'Hoi An', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$1100 - $1600', 'post_duration' => 60, 'contact_email' => 'site@buildcore.vn', 'contact_phone' => '0901212121', 'job_type' => 'full-time', 'required_certification' => 'Civil engineering degree', 'job_experience' => '2 years']
//     ],
//     'du_lich_khach_san_nha_hang' => [
//         ['job_title' => 'Hotel Manager', 'job_description' => 'Supervise front office, room standards and guest satisfaction operations.', 'job_benefit' => 'Accommodation, meal support, performance bonus.', 'job_requirement' => 'Hospitality leadership experience and guest service excellence.', 'job_position' => 'Hospitality', 'job_location' => 'Nha Trang', 'job_detailed_location' => 'Vinh Nguyen, Nha Trang', 'job_location_district' => 'Vinh Nguyen', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1200 - $1800', 'post_duration' => 45, 'contact_email' => 'hm@crownstay.vn', 'contact_phone' => '0916655443', 'job_type' => 'full-time', 'required_certification' => 'Hospitality degree', 'job_experience' => '3 years'],
//         ['job_title' => 'Travel Consultant', 'job_description' => 'Design vacation itineraries and support customers with travel bookings.', 'job_benefit' => 'Travel perks, sales incentives.', 'job_requirement' => 'Strong customer service and travel product knowledge.', 'job_position' => 'Travel', 'job_location' => 'Da Nang', 'job_detailed_location' => 'Son Tra, Da Nang', 'job_location_district' => 'Son Tra', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$700 - $1100', 'post_duration' => 30, 'contact_email' => 'travel@crownstay.vn', 'contact_phone' => '0912223334', 'job_type' => 'part-time', 'required_certification' => 'Tourism certificate', 'job_experience' => '1 year']
//     ],
//     'luat' => [
//         ['job_title' => 'Junior Legal Consultant', 'job_description' => 'Support contract review, compliance checks and legal document preparation.', 'job_benefit' => 'Bonus, training support, professional growth.', 'job_requirement' => 'Law degree and strong research skill.', 'job_position' => 'Legal', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 3, Ho Chi Minh City', 'job_location_district' => 'District 3', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1500', 'post_duration' => 45, 'contact_email' => 'legal@legalprime.vn', 'contact_phone' => '0904455667', 'job_type' => 'full-time', 'required_certification' => 'Legal certificate', 'job_experience' => '2 years'],
//         ['job_title' => 'Compliance Officer', 'job_description' => 'Ensure regulatory compliance and internal legal policy adherence.', 'job_benefit' => 'Career growth, annual bonus.', 'job_requirement' => 'Knowledge of legal procedures and business compliance.', 'job_position' => 'Compliance', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Cau Giay, Hanoi', 'job_location_district' => 'Cau Giay', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1200 - $1700', 'post_duration' => 60, 'contact_email' => 'compliance@legalprime.vn', 'contact_phone' => '0909988774', 'job_type' => 'full-time', 'required_certification' => 'Compliance background', 'job_experience' => '3 years']
//     ],
//     'thiet_ke_nghe_thuat' => [
//         ['job_title' => 'Graphic Designer', 'job_description' => 'Develop branding and visual assets for digital and print campaigns.', 'job_benefit' => 'Creative budget and hybrid schedule.', 'job_requirement' => 'Figma, Adobe Illustrator, Photoshop experience.', 'job_position' => 'Design', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Ba Dinh, Hanoi', 'job_location_district' => 'Ba Dinh', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$800 - $1300', 'post_duration' => 30, 'contact_email' => 'design@designcraft.vn', 'contact_phone' => '0903344551', 'job_type' => 'full-time', 'required_certification' => 'Portfolio', 'job_experience' => '1 year'],
//         ['job_title' => 'UI/UX Designer', 'job_description' => 'Design intuitive user flows and modern interface patterns for web experiences.', 'job_benefit' => 'Learning budget and product workshops.', 'job_requirement' => 'Strong UX thinking and design system knowledge.', 'job_position' => 'UX', 'job_location' => 'Remote', 'job_detailed_location' => 'Remote work', 'job_location_district' => 'Remote', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1600', 'post_duration' => 45, 'contact_email' => 'ux@designcraft.vn', 'contact_phone' => '0907654322', 'job_type' => 'remote', 'required_certification' => 'Portfolio', 'job_experience' => '2 years']
//     ],
//     'nong_nghiep' => [
//         ['job_title' => 'Agricultural Technician', 'job_description' => 'Monitor crop health and support efficient farming operations.', 'job_benefit' => 'Field allowance, transport support.', 'job_requirement' => 'Agronomy knowledge and field observation skills.', 'job_position' => 'Agriculture', 'job_location' => 'Thanh Hoa', 'job_detailed_location' => 'Sam Son, Thanh Hoa', 'job_location_district' => 'Sam Son', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$600 - $900', 'post_duration' => 30, 'contact_email' => 'jobs@greenharvest.vn', 'contact_phone' => '0917676767', 'job_type' => 'full-time', 'required_certification' => 'Agronomy certificate', 'job_experience' => '1 year'],
//         ['job_title' => 'Farm Operations Supervisor', 'job_description' => 'Manage harvesting and operational planning across cultivation activities.', 'job_benefit' => 'Bonus, accommodation support.', 'job_requirement' => 'Leadership and agricultural production experience.', 'job_position' => 'Operations', 'job_location' => 'Nghe An', 'job_detailed_location' => 'Vinh, Nghe An', 'job_location_district' => 'Vinh', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$900 - $1400', 'post_duration' => 60, 'contact_email' => 'ops@greenharvest.vn', 'contact_phone' => '0913456789', 'job_type' => 'full-time', 'required_certification' => 'Agricultural degree', 'job_experience' => '2 years']
//     ],
//     'food_beverage' => [
//         ['job_title' => 'Food Product Specialist', 'job_description' => 'Support product development and market expansion in food categories.', 'job_benefit' => 'Staff meal, performance bonus.', 'job_requirement' => 'Food industry knowledge and market research skill.', 'job_position' => 'Product', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 10, Ho Chi Minh City', 'job_location_district' => 'District 10', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$900 - $1400', 'post_duration' => 45, 'contact_email' => 'careers@freshbite.vn', 'contact_phone' => '0914433221', 'job_type' => 'full-time', 'required_certification' => 'Food safety knowledge', 'job_experience' => '2 years'],
//         ['job_title' => 'Barista', 'job_description' => 'Prepare beverages, maintain cleanliness and create great guest experience.', 'job_benefit' => 'Service bonus, free meals.', 'job_requirement' => 'Hospitality and beverage preparation experience.', 'job_position' => 'Food & Beverage', 'job_location' => 'Da Nang', 'job_detailed_location' => 'Hai Chau, Da Nang', 'job_location_district' => 'Hai Chau', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$500 - $800', 'post_duration' => 30, 'contact_email' => 'barista@freshbite.vn', 'contact_phone' => '0903334445', 'job_type' => 'part-time', 'required_certification' => 'Food service certificate', 'job_experience' => '6 months']
//     ],
//     'nhan_su' => [
//         ['job_title' => 'HR Generalist', 'job_description' => 'Manage recruitment, onboarding and employee engagement programs.', 'job_benefit' => '14th month salary, annual bonus.', 'job_requirement' => 'HR operations knowledge and strong communication.', 'job_position' => 'Human Resources', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Dong Da, Hanoi', 'job_location_district' => 'Dong Da', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1000 - $1500', 'post_duration' => 45, 'contact_email' => 'hr@talentbridge.vn', 'contact_phone' => '0907779966', 'job_type' => 'full-time', 'required_certification' => 'HR certificate', 'job_experience' => '2 years'],
//         ['job_title' => 'Recruitment Specialist', 'job_description' => 'Source candidates, interview shortlists and support hiring plans.', 'job_benefit' => 'Performance bonus, flexible schedule.', 'job_requirement' => 'Candidate sourcing and recruiting experience.', 'job_position' => 'Recruitment', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 5, Ho Chi Minh City', 'job_location_district' => 'District 5', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$800 - $1200', 'post_duration' => 30, 'contact_email' => 'recruit@talentbridge.vn', 'contact_phone' => '0911122335', 'job_type' => 'full-time', 'required_certification' => 'HR degree', 'job_experience' => '1 year']
//     ],
//     'hanh_chinh_van_phong' => [
//         ['job_title' => 'Office Assistant', 'job_description' => 'Maintain office operations, supplies and daily administrative support.', 'job_benefit' => 'Lunch support, transport allowance.', 'job_requirement' => 'Strong organization and good communication skill.', 'job_position' => 'Administration', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Hai Ba Trung, Hanoi', 'job_location_district' => 'Hai Ba Trung', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$500 - $800', 'post_duration' => 30, 'contact_email' => 'office@officeflow.vn', 'contact_phone' => '0902211334', 'job_type' => 'full-time', 'required_certification' => 'Office software', 'job_experience' => '6 months'],
//         ['job_title' => 'Operations Coordinator', 'job_description' => 'Coordinate office workflows, schedules and internal support requests.', 'job_benefit' => 'Performance bonus and learning support.', 'job_requirement' => 'Administrative and project coordination experience.', 'job_position' => 'Operations', 'job_location' => 'Da Nang', 'job_detailed_location' => 'Lien Chieu, Da Nang', 'job_location_district' => 'Lien Chieu', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$700 - $1000', 'post_duration' => 45, 'contact_email' => 'ops@officeflow.vn', 'contact_phone' => '0917766554', 'job_type' => 'part-time', 'required_certification' => 'Office administration', 'job_experience' => '1 year']
//     ],
//     'ky_thuat' => [
//         ['job_title' => 'Mechanical Engineer', 'job_description' => 'Support design, testing and optimization of equipment and systems.', 'job_benefit' => 'Project bonus, training program.', 'job_requirement' => 'Mechanical engineering knowledge and CAD skills.', 'job_position' => 'Engineering', 'job_location' => 'Da Nang', 'job_detailed_location' => 'Hoa Vang, Da Nang', 'job_location_district' => 'Hoa Vang', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$1200 - $1800', 'post_duration' => 60, 'contact_email' => 'engineer@techforge.vn', 'contact_phone' => '0912345670', 'job_type' => 'full-time', 'required_certification' => 'Engineering degree', 'job_experience' => '3 years'],
//         ['job_title' => 'Process Engineer', 'job_description' => 'Improve production workflow, efficiency and quality across operations.', 'job_benefit' => 'Overtime allowance and annual bonus.', 'job_requirement' => 'Process improvement and industrial systems experience.', 'job_position' => 'Process', 'job_location' => 'Binh Duong', 'job_detailed_location' => 'Di An, Binh Duong', 'job_location_district' => 'Di An', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$1100 - $1600', 'post_duration' => 30, 'contact_email' => 'process@techforge.vn', 'contact_phone' => '0905522331', 'job_type' => 'full-time', 'required_certification' => 'Technical degree', 'job_experience' => '2 years']
//     ],
//     'khac' => [
//         ['job_title' => 'Business Operations Associate', 'job_description' => 'Support operational execution across varied business initiatives and team planning.', 'job_benefit' => 'Flexible schedule, learning support.', 'job_requirement' => 'Good organizational skills and multitasking ability.', 'job_position' => 'Operations', 'job_location' => 'Hanoi', 'job_detailed_location' => 'Tay Ho, Hanoi', 'job_location_district' => 'Tay Ho', 'no_employee_needed' => 1, 'employee_gender' => 'Any', 'salary' => '$700 - $1100', 'post_duration' => 30, 'contact_email' => 'hello@generalgrowth.vn', 'contact_phone' => '0906655443', 'job_type' => 'full-time', 'required_certification' => 'Bachelor degree', 'job_experience' => '1 year'],
//         ['job_title' => 'Project Coordinator', 'job_description' => 'Coordinate schedules, team collaboration and progress tracking across projects.', 'job_benefit' => 'Bonus, professional development budget.', 'job_requirement' => 'Strong multitasking and communication skills.', 'job_position' => 'Project Support', 'job_location' => 'Ho Chi Minh City', 'job_detailed_location' => 'District 4, Ho Chi Minh City', 'job_location_district' => 'District 4', 'no_employee_needed' => 2, 'employee_gender' => 'Any', 'salary' => '$800 - $1200', 'post_duration' => 45, 'contact_email' => 'projects@generalgrowth.vn', 'contact_phone' => '0907788990', 'job_type' => 'full-time', 'required_certification' => 'Business degree', 'job_experience' => '1 year']
//     ]
// ];

// $job_rows = [];
// foreach ($jobs_seed as $industry => $industry_jobs) {
//     $company_id = $company_ids[$industry] ?? $sample_company_id;
//     if (!$company_id) {
//         continue;
//     }

//     foreach ($industry_jobs as $job) {
//         $job_title = $conn->real_escape_string($job['job_title']);
//         $job_description = $conn->real_escape_string($job['job_description']);
//         $job_benefit = $conn->real_escape_string($job['job_benefit']);
//         $job_requirement = $conn->real_escape_string($job['job_requirement']);
//         $job_position = $conn->real_escape_string($job['job_position']);
//         $job_location = $conn->real_escape_string($job['job_location']);
//         $job_detailed_location = $conn->real_escape_string($job['job_detailed_location']);
//         $job_location_district = $conn->real_escape_string($job['job_location_district']);
//         $employee_gender = $conn->real_escape_string($job['employee_gender']);
//         $salary = $conn->real_escape_string($job['salary']);
//         $contact_email = $conn->real_escape_string($job['contact_email']);
//         $contact_phone = $conn->real_escape_string($job['contact_phone']);
//         $job_type = $conn->real_escape_string($job['job_type']);
//         $required_certification = $conn->real_escape_string($job['required_certification']);
//         $job_experience = $conn->real_escape_string($job['job_experience']);
//         $job_post_number_by_employer = 1;
//         $job_rows[] = "($sample_employer_id, $company_id, '$job_title', '$job_description', '$job_benefit', '$job_requirement', '$job_position', '$job_location', '$job_detailed_location', '$job_location_district', {$job['no_employee_needed']}, '$employee_gender', '$salary', {$job['post_duration']}, '$contact_email', '$contact_phone', '$job_type', '$required_certification', '$job_experience', 'approved', $job_post_number_by_employer)";
//     }
// }

// if (!empty($job_rows)) {
//     $job_insert_sql = "INSERT INTO hoa_giai_vien (
//         posted_by_employer_id,
//         company_id,
//         job_title,
//         job_description,
//         job_benefit,
//         job_requirement,
//         job_position,
//         job_location,
//         job_detailed_location,
//         job_location_district,
//         no_employee_needed,
//         employee_gender,
//         salary,
//         post_duration,
//         contact_email,
//         contact_phone,
//         job_type,
//         required_certification,
//         job_experience,
//         status,
//         job_post_number_by_employer
//     ) VALUES " . implode(", ", $job_rows) . ";";

//     if ($conn->query($job_insert_sql) === TRUE) {
//         echo "Sample jobs inserted successfully. Rows affected: {$conn->affected_rows}<br>";
//     } else {
//         echo "Error inserting sample job data: " . $conn->error . "<br>";
//     }
// }

//tài khoản admin
$admin_username = 'minh';
$user_type = 'admin';
$email = 'qudominh16@gmail.com';
// Mã hóa mật khẩu 123 trước khi đưa vào database
$password = password_hash('123', PASSWORD_DEFAULT);

$sql_user = "INSERT INTO user (username, user_type, email, password) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql_user);
$stmt->execute([$admin_username, $user_type, $email, $password]);