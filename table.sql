USE `sql_viecnhe_io_vn`;

CREATE TABLE IF NOT EXISTS `user` (
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
);

ALTER TABLE `user`
    ADD COLUMN status ENUM('unlocked', 'locked') NOT NULL DEFAULT 'unlocked' AFTER updated_at;

CREATE TABLE IF NOT EXISTS password_reset (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_password_reset_user (user_id),
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment_orders (
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
);

ALTER TABLE payment_orders
    ADD COLUMN employer_id INT UNSIGNED NULL,
    ADD COLUMN job_payload JSON NULL,
    ADD COLUMN job_id INT UNSIGNED NULL,
    ADD COLUMN provider_transaction_id VARCHAR(100) NULL UNIQUE;

ALTER TABLE payment_orders
    MODIFY COLUMN provider_transaction_id VARCHAR(100) NULL;

CREATE TABLE IF NOT EXISTS jobseeker_profile (
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
    FOREIGN KEY (jobseeker_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employer_profile (
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
    FOREIGN KEY (employer_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS company (
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
    FOREIGN KEY (employer_id) REFERENCES `user`(id)
);

CREATE TABLE IF NOT EXISTS prominent_company (
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
    CONSTRAINT fk_prominent_company_employer FOREIGN KEY (employer_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    posted_by_employer_id INT UNSIGNED NOT NULL,
    company_id INT(6) UNSIGNED NOT NULL,
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
    FOREIGN KEY (posted_by_employer_id) REFERENCES `user`(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS job_payments (
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
    CONSTRAINT fk_job_payments_employer FOREIGN KEY (employer_id) REFERENCES `user`(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_payments_job FOREIGN KEY (job_id) REFERENCES job(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS application (
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
    FOREIGN KEY (job_id) REFERENCES job(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES `user`(id) ON DELETE CASCADE
);

ALTER TABLE application
    ADD COLUMN cover_letter TEXT NULL AFTER cv_file;

CREATE TABLE IF NOT EXISTS feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_type ENUM('jobseeker', 'recruiter', 'admin') NOT NULL,
    content TEXT NOT NULL,
    star_rating TINYINT UNSIGNED NOT NULL CHECK (star_rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS company_followers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    jobseeker_id INT UNSIGNED NOT NULL,
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (company_id, jobseeker_id),
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_interest_count (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    jobseeker_id INT(6) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_job_interest (job_id, jobseeker_id),
    FOREIGN KEY (job_id) REFERENCES job(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS saved_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES job(id) ON DELETE CASCADE,
    UNIQUE (user_id, job_id)
);

INSERT IGNORE INTO `user` (username, user_type, email, password)
VALUES ('minh', 'admin', 'qudominh16@gmail.com', '$2y$10$x4ivO9ifBzKHNfp/ijjN.efwEuk2NzzF7j8dtZiivjzKPkx8mFsLC');
