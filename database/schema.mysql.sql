-- PhysioWeb Clinic Management System
-- Production schema for MySQL 8.0+
--
-- Design notes:
--  * InnoDB everywhere (required for foreign keys and transactions).
--  * utf8mb4 for full Unicode (patient names, notes, emoji-safe).
--  * Patients and their clinical history are never hard-deleted from the
--    application layer (see Models\Patient::deactivate) — `is_active`
--    flags preserve the medico-legal record; ON DELETE behaviors below are
--    still defined for referential integrity in case of deliberate data
--    purges performed directly in the database by an administrator.
--  * All monetary columns use DECIMAL (never FLOAT) to avoid rounding
--    errors in billing.
--  * id_sequences backs App\Core\IdGenerator for human-facing codes like
--    PAT-2026-0001 / INV-2026-0001 / APT-2026-0001.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- id_sequences
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS id_sequences;
CREATE TABLE id_sequences (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    prefix VARCHAR(10) NOT NULL,
    year INT NOT NULL,
    last_value INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prefix_year (prefix, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- staff (HR records for every employee: physiotherapists, receptionists,
-- assistants, administrators)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS staff;
CREATE TABLE staff (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_code VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('physiotherapist','receptionist','assistant','admin') NOT NULL,
    qualification VARCHAR(255) NULL,
    specialization VARCHAR(255) NULL,
    experience_years DECIMAL(4,1) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    salary DECIMAL(10,2) NULL,
    joining_date DATE NULL,
    photo VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_staff_code (staff_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- therapists (public-facing + schedulable profile; every therapist is a
-- physiotherapist staff member)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS therapists;
CREATE TABLE therapists (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_id INT UNSIGNED NOT NULL,
    slug VARCHAR(160) NOT NULL,
    bio TEXT NULL,
    default_session_minutes INT UNSIGNED NOT NULL DEFAULT 45,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_therapist_slug (slug),
    CONSTRAINT fk_therapist_staff FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- users (CMS / admin login accounts)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','receptionist','therapist_staff') NOT NULL DEFAULT 'receptionist',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT fk_user_staff FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- patients
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS patients;
CREATE TABLE patients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_code VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    date_of_birth DATE NULL,
    mobile VARCHAR(20) NOT NULL,
    alt_mobile VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    password_hash VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NULL DEFAULT 'India',
    pincode VARCHAR(10) NULL,
    blood_group VARCHAR(5) NULL,
    allergies TEXT NULL,
    existing_conditions TEXT NULL,
    medical_history TEXT NULL,
    emergency_contact_name VARCHAR(150) NULL,
    emergency_contact_relationship VARCHAR(60) NULL,
    emergency_contact_mobile VARCHAR(20) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_patient_code (patient_code),
    UNIQUE KEY uq_patient_email (email),
    KEY idx_patient_mobile (mobile),
    KEY idx_patient_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- patient_documents
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS patient_documents;
CREATE TABLE patient_documents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    document_type ENUM('id_proof','medical_report','scan','xray','mri','other') NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_doc_patient (patient_id),
    CONSTRAINT fk_doc_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE,
    CONSTRAINT fk_doc_user FOREIGN KEY (uploaded_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- services (public service catalog + billing catalog)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS services;
CREATE TABLE services (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    category VARCHAR(100) NULL,
    short_description VARCHAR(500) NULL,
    description TEXT NULL,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 45,
    image VARCHAR(255) NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_service_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- appointments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS appointments;
CREATE TABLE appointments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_number VARCHAR(20) NOT NULL,
    patient_id INT UNSIGNED NULL,
    guest_name VARCHAR(150) NULL,
    guest_mobile VARCHAR(20) NULL,
    guest_email VARCHAR(150) NULL,
    therapist_id INT UNSIGNED NULL,
    service_id INT UNSIGNED NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 45,
    status ENUM('scheduled','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
    source ENUM('public_website','admin','patient_portal') NOT NULL DEFAULT 'admin',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_appointment_number (appointment_number),
    KEY idx_appt_date (scheduled_date),
    KEY idx_appt_patient (patient_id),
    KEY idx_appt_therapist_date (therapist_id, scheduled_date),
    CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE SET NULL,
    CONSTRAINT fk_appt_therapist FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON DELETE SET NULL,
    CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- treatment_plans
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS treatment_plans;
CREATE TABLE treatment_plans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_number VARCHAR(20) NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    therapist_id INT UNSIGNED NULL,
    diagnosis VARCHAR(500) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active','completed','discontinued') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plan_number (plan_number),
    KEY idx_plan_patient (patient_id),
    CONSTRAINT fk_plan_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_therapist FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- treatment_sessions
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS treatment_sessions;
CREATE TABLE treatment_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    treatment_plan_id INT UNSIGNED NOT NULL,
    session_number INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    therapist_id INT UNSIGNED NULL,
    treatment_given VARCHAR(500) NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    pain_score_before TINYINT UNSIGNED NULL,
    pain_score_after TINYINT UNSIGNED NULL,
    mobility_score TINYINT UNSIGNED NULL,
    progress_notes TEXT NULL,
    recommendations TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_session_plan (treatment_plan_id),
    CONSTRAINT fk_session_plan FOREIGN KEY (treatment_plan_id) REFERENCES treatment_plans (id) ON DELETE CASCADE,
    CONSTRAINT fk_session_therapist FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON DELETE SET NULL,
    CONSTRAINT chk_pain_before CHECK (pain_score_before IS NULL OR pain_score_before BETWEEN 0 AND 10),
    CONSTRAINT chk_pain_after CHECK (pain_score_after IS NULL OR pain_score_after BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- assessments (medical record / EMR entries, independent of a plan)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS assessments;
CREATE TABLE assessments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    therapist_id INT UNSIGNED NULL,
    assessment_date DATE NOT NULL,
    diagnosis VARCHAR(500) NULL,
    symptoms TEXT NULL,
    pain_scale TINYINT UNSIGNED NULL,
    mobility_score TINYINT UNSIGNED NULL,
    recovery_status ENUM('improving','stable','worsening') NULL,
    clinical_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assessment_patient_date (patient_id, assessment_date),
    CONSTRAINT fk_assessment_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE,
    CONSTRAINT fk_assessment_therapist FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON DELETE SET NULL,
    CONSTRAINT chk_pain_scale CHECK (pain_scale IS NULL OR pain_scale BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- exercises (library) + exercise_assignments (prescriptions)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS exercises;
CREATE TABLE exercises (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NULL,
    instructions TEXT NOT NULL,
    default_repetitions VARCHAR(50) NULL,
    default_frequency VARCHAR(100) NULL,
    video_link VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS exercise_assignments;
CREATE TABLE exercise_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NOT NULL,
    repetitions VARCHAR(50) NULL,
    frequency VARCHAR(100) NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    notes VARCHAR(500) NULL,
    assigned_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assignment_patient (patient_id),
    CONSTRAINT fk_assignment_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_exercise FOREIGN KEY (exercise_id) REFERENCES exercises (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_user FOREIGN KEY (assigned_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- packages + patient_packages
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS packages;
CREATE TABLE packages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    total_sessions INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    validity_days INT UNSIGNED NOT NULL DEFAULT 90,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS patient_packages;
CREATE TABLE patient_packages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NULL,
    purchased_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    sessions_used INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','completed','expired') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_patient_package (patient_id),
    CONSTRAINT fk_pp_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- invoices + invoice_items + payments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(20) NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    gst_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partially_paid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
    notes VARCHAR(500) NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invoice_number (invoice_number),
    KEY idx_invoice_patient (patient_id),
    KEY idx_invoice_date (invoice_date),
    CONSTRAINT fk_invoice_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE RESTRICT,
    CONSTRAINT fk_invoice_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE SET NULL,
    CONSTRAINT fk_invoice_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS invoice_items;
CREATE TABLE invoice_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_item_invoice (invoice_id),
    CONSTRAINT fk_item_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE,
    CONSTRAINT fk_item_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_mode ENUM('cash','upi','credit_card','debit_card','net_banking') NOT NULL,
    payment_date DATE NOT NULL,
    reference_number VARCHAR(100) NULL,
    received_by_user_id INT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payment_invoice (invoice_id),
    CONSTRAINT fk_payment_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_user FOREIGN KEY (received_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- CMS content: blog, gallery, testimonials, contact inquiries, settings
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS blog_categories;
CREATE TABLE blog_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS blogs;
CREATE TABLE blogs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    author_user_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL,
    excerpt VARCHAR(500) NULL,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255) NULL,
    tags VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_slug (slug),
    KEY idx_blog_status (status, published_at),
    CONSTRAINT fk_blog_category FOREIGN KEY (category_id) REFERENCES blog_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_blog_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS gallery;
CREATE TABLE gallery (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NULL,
    media_type ENUM('image','video') NOT NULL DEFAULT 'image',
    file_path VARCHAR(500) NOT NULL,
    category ENUM('clinic','treatments','events') NOT NULL DEFAULT 'clinic',
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS testimonials;
CREATE TABLE testimonials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_name VARCHAR(150) NOT NULL,
    patient_photo VARCHAR(255) NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    content TEXT NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    source ENUM('public_submission','admin_added') NOT NULL DEFAULT 'admin_added',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS contact_inquiries;
CREATE TABLE contact_inquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','responded','closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    key_name VARCHAR(100) NOT NULL,
    value TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_setting_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- audit_logs + login_attempts
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS audit_logs;
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    user_type ENUM('admin','patient','system') NOT NULL DEFAULT 'system',
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS login_attempts;
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    scope ENUM('admin','patient') NOT NULL,
    success TINYINT(1) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_lookup (identifier, ip_address, scope, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
