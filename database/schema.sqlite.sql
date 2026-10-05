-- PhysioWeb Clinic Management System
-- SQLite translation of database/schema.mysql.sql, used ONLY by the
-- automated smoke tests in tests/ (see tests/README.md) so the Model layer
-- can be exercised with real SQL in environments without a MySQL server.
-- This file is NOT used in production and is not a general-purpose SQLite
-- port of the application (e.g. it has no bearing on hosting choice).
--
-- Translation rules applied consistently from the MySQL original:
--   ENUM(...)            -> TEXT with a CHECK (col IN (...)) constraint
--   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY -> INTEGER PRIMARY KEY AUTOINCREMENT
--   ON UPDATE CURRENT_TIMESTAMP              -> omitted (not supported; the
--                                                application sets updated_at
--                                                explicitly on every UPDATE)
--   ENGINE/CHARSET clauses                   -> omitted (not applicable)
-- Column names, table names and nullability are kept identical to the
-- MySQL schema so Model code runs unchanged against either driver.

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS id_sequences;
CREATE TABLE id_sequences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prefix TEXT NOT NULL,
    year INTEGER NOT NULL,
    last_value INTEGER NOT NULL DEFAULT 0,
    UNIQUE (prefix, year)
);

DROP TABLE IF EXISTS staff;
CREATE TABLE staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_code TEXT NOT NULL UNIQUE,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('physiotherapist','receptionist','assistant','admin')),
    qualification TEXT,
    specialization TEXT,
    experience_years REAL,
    phone TEXT,
    email TEXT,
    salary REAL,
    joining_date TEXT,
    photo TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS therapists;
CREATE TABLE therapists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE CASCADE,
    slug TEXT NOT NULL UNIQUE,
    bio TEXT,
    default_session_minutes INTEGER NOT NULL DEFAULT 45,
    is_public INTEGER NOT NULL DEFAULT 1,
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER REFERENCES staff(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'receptionist' CHECK (role IN ('admin','receptionist','therapist_staff')),
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS patients;
CREATE TABLE patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_code TEXT NOT NULL UNIQUE,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    gender TEXT NOT NULL CHECK (gender IN ('male','female','other')),
    date_of_birth TEXT,
    mobile TEXT NOT NULL,
    alt_mobile TEXT,
    email TEXT UNIQUE,
    password_hash TEXT,
    address TEXT,
    city TEXT,
    state TEXT,
    country TEXT DEFAULT 'India',
    pincode TEXT,
    blood_group TEXT,
    allergies TEXT,
    existing_conditions TEXT,
    medical_history TEXT,
    emergency_contact_name TEXT,
    emergency_contact_relationship TEXT,
    emergency_contact_mobile TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS patient_documents;
CREATE TABLE patient_documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    document_type TEXT NOT NULL CHECK (document_type IN ('id_proof','medical_report','scan','xray','mri','other')),
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    uploaded_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS services;
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    category TEXT,
    short_description TEXT,
    description TEXT,
    fee REAL NOT NULL DEFAULT 0,
    gst_rate REAL NOT NULL DEFAULT 0,
    duration_minutes INTEGER NOT NULL DEFAULT 45,
    image TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS appointments;
CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    appointment_number TEXT NOT NULL UNIQUE,
    patient_id INTEGER REFERENCES patients(id) ON DELETE SET NULL,
    guest_name TEXT,
    guest_mobile TEXT,
    guest_email TEXT,
    therapist_id INTEGER REFERENCES therapists(id) ON DELETE SET NULL,
    service_id INTEGER REFERENCES services(id) ON DELETE SET NULL,
    scheduled_date TEXT NOT NULL,
    scheduled_time TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL DEFAULT 45,
    status TEXT NOT NULL DEFAULT 'scheduled' CHECK (status IN ('scheduled','confirmed','completed','cancelled','no_show')),
    source TEXT NOT NULL DEFAULT 'admin' CHECK (source IN ('public_website','admin','patient_portal')),
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS treatment_plans;
CREATE TABLE treatment_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_number TEXT NOT NULL UNIQUE,
    patient_id INTEGER NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    therapist_id INTEGER REFERENCES therapists(id) ON DELETE SET NULL,
    diagnosis TEXT NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','completed','discontinued')),
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS treatment_sessions;
CREATE TABLE treatment_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    treatment_plan_id INTEGER NOT NULL REFERENCES treatment_plans(id) ON DELETE CASCADE,
    session_number INTEGER NOT NULL,
    session_date TEXT NOT NULL,
    therapist_id INTEGER REFERENCES therapists(id) ON DELETE SET NULL,
    treatment_given TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL DEFAULT 30,
    pain_score_before INTEGER CHECK (pain_score_before IS NULL OR (pain_score_before BETWEEN 0 AND 10)),
    pain_score_after INTEGER CHECK (pain_score_after IS NULL OR (pain_score_after BETWEEN 0 AND 10)),
    mobility_score INTEGER,
    progress_notes TEXT,
    recommendations TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS assessments;
CREATE TABLE assessments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    therapist_id INTEGER REFERENCES therapists(id) ON DELETE SET NULL,
    assessment_date TEXT NOT NULL,
    diagnosis TEXT,
    symptoms TEXT,
    pain_scale INTEGER CHECK (pain_scale IS NULL OR (pain_scale BETWEEN 0 AND 10)),
    mobility_score INTEGER,
    recovery_status TEXT CHECK (recovery_status IS NULL OR recovery_status IN ('improving','stable','worsening')),
    clinical_notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS exercises;
CREATE TABLE exercises (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT,
    instructions TEXT NOT NULL,
    default_repetitions TEXT,
    default_frequency TEXT,
    video_link TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS exercise_assignments;
CREATE TABLE exercise_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    exercise_id INTEGER NOT NULL REFERENCES exercises(id) ON DELETE CASCADE,
    repetitions TEXT,
    frequency TEXT,
    start_date TEXT NOT NULL,
    end_date TEXT,
    notes TEXT,
    assigned_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS packages;
CREATE TABLE packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    total_sessions INTEGER NOT NULL,
    price REAL NOT NULL,
    discount_percent REAL NOT NULL DEFAULT 0,
    validity_days INTEGER NOT NULL DEFAULT 90,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS patient_packages;
CREATE TABLE patient_packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    package_id INTEGER NOT NULL REFERENCES packages(id) ON DELETE RESTRICT,
    invoice_id INTEGER,
    purchased_date TEXT NOT NULL,
    expiry_date TEXT NOT NULL,
    sessions_used INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','completed','expired')),
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT NOT NULL UNIQUE,
    patient_id INTEGER NOT NULL REFERENCES patients(id) ON DELETE RESTRICT,
    appointment_id INTEGER REFERENCES appointments(id) ON DELETE SET NULL,
    invoice_date TEXT NOT NULL,
    due_date TEXT,
    subtotal REAL NOT NULL DEFAULT 0,
    gst_amount REAL NOT NULL DEFAULT 0,
    discount_amount REAL NOT NULL DEFAULT 0,
    total_amount REAL NOT NULL DEFAULT 0,
    amount_paid REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'unpaid' CHECK (status IN ('unpaid','partially_paid','paid','cancelled')),
    notes TEXT,
    created_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS invoice_items;
CREATE TABLE invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    service_id INTEGER REFERENCES services(id) ON DELETE SET NULL,
    description TEXT NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price REAL NOT NULL,
    gst_rate REAL NOT NULL DEFAULT 0,
    line_total REAL NOT NULL
);

DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    amount REAL NOT NULL,
    payment_mode TEXT NOT NULL CHECK (payment_mode IN ('cash','upi','credit_card','debit_card','net_banking')),
    payment_date TEXT NOT NULL,
    reference_number TEXT,
    received_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS blog_categories;
CREATE TABLE blog_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS blogs;
CREATE TABLE blogs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES blog_categories(id) ON DELETE SET NULL,
    author_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    excerpt TEXT,
    content TEXT NOT NULL,
    featured_image TEXT,
    tags TEXT,
    meta_title TEXT,
    meta_description TEXT,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','published')),
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS gallery;
CREATE TABLE gallery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    media_type TEXT NOT NULL DEFAULT 'image' CHECK (media_type IN ('image','video')),
    file_path TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'clinic' CHECK (category IN ('clinic','treatments','events')),
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS testimonials;
CREATE TABLE testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_name TEXT NOT NULL,
    patient_photo TEXT,
    rating INTEGER NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
    content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    source TEXT NOT NULL DEFAULT 'admin_added' CHECK (source IN ('public_submission','admin_added')),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    approved_at TEXT
);

DROP TABLE IF EXISTS contact_inquiries;
CREATE TABLE contact_inquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    mobile TEXT,
    email TEXT,
    subject TEXT,
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new','read','responded','closed')),
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_name TEXT NOT NULL UNIQUE,
    value TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS audit_logs;
CREATE TABLE audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    user_type TEXT NOT NULL DEFAULT 'system' CHECK (user_type IN ('admin','patient','system')),
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER,
    description TEXT,
    ip_address TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

DROP TABLE IF EXISTS login_attempts;
CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifier TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    scope TEXT NOT NULL CHECK (scope IN ('admin','patient')),
    success INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
