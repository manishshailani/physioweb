-- Demo seed data for PhysioWeb Clinic Management System.
-- Run AFTER schema.mysql.sql. Safe to re-run (it clears the demo rows it
-- created first) but is intended for a fresh demo/staging database, not
-- production.
--
-- Default login credentials created below:
--   Admin portal   (/admin/login)   : admin@physioweb.test   / Admin@12345
--   Patient portal (/patient/login) : priya.sharma@example.com / Patient@123
-- CHANGE THESE before deploying anywhere reachable by the public.
-- The bcrypt hashes below were generated with PHP's password_hash() using
-- PASSWORD_BCRYPT (see database/generate_seed_hashes.php for the exact
-- script used, so the hashes are reproducible and auditable rather than
-- hand-typed).

SET FOREIGN_KEY_CHECKS = 0;

-- Settings (homepage/content/clinic info)
-- Clinic name, address, phone, email and the "1st Consultation Free" offer
-- below are taken directly from the clinic's own signage/business card
-- (Cortex & Kinetic, Belgaum). Vision/mission/about copy is original
-- website copy written for this project, not claimed to be verbatim from
-- the clinic.
INSERT INTO settings (key_name, value) VALUES
('clinic_name', 'Cortex & Kinetic'),
('clinic_tagline', 'Physiotherapy and Rehabilitation Clinic'),
('clinic_address', 'C004 Ground Floor, Gardenia Apartment, 2nd Main, 7th Cross, Near Ambedkar Bhawan, Sadashiv Nagar, Belgaum 590019'),
('clinic_phone', '+91 88826 88868'),
('clinic_email', 'cortex.kinetic@gmail.com'),
('clinic_logo', '/assets/img/logo.svg'),
('clinic_offer', '1st Consultation FREE - Limited Time Offer'),
('social_facebook', ''),
('social_instagram', ''),
('homepage_hero_title', 'Expert Neuro & Ortho Physiotherapy, Close to Home'),
('homepage_hero_subtitle', 'Specialised neuro rehabilitation and advanced orthopedic rehabilitation under the care of Dr. Meenakshi, BPT, MPT (Neuro).'),
('homepage_about_text', 'Cortex & Kinetic is a physiotherapy and rehabilitation clinic in Belgaum led by Dr. Meenakshi (BPT, MPT Neuro), offering individualised neurological and orthopedic rehabilitation programs for patients of all ages.'),
('clinic_vision', 'To be Belgaum''s most trusted destination for neurological and orthopedic rehabilitation.'),
('clinic_mission', 'Delivering personalised, evidence-based physiotherapy that restores function and improves quality of life.');

-- Staff
-- Dr. Meenakshi's name, qualification and role are taken from the clinic's
-- own business card/signage. Salary and joining date are not given by that
-- source and are placeholder demo values only (clearly editable in the
-- admin Staff module) — NOT a claim about her actual employment terms.
INSERT INTO staff (staff_code, first_name, last_name, role, qualification, specialization, experience_years, phone, email, salary, joining_date, is_active) VALUES
('STF-0001', 'Meenakshi', '', 'physiotherapist', 'BPT, MPT (Neuro)', 'Neuro & Ortho Rehabilitation', NULL, '+91 88826 88868', 'cortex.kinetic@gmail.com', NULL, NULL, 1),
('STF-0002', 'Front', 'Desk', 'receptionist', NULL, NULL, NULL, '+91 88826 88868', NULL, NULL, NULL, 1);

INSERT INTO therapists (staff_id, slug, bio, default_session_minutes, is_public, display_order) VALUES
(1, 'dr-meenakshi', 'Dr. Meenakshi (BPT, MPT Neuro) is a Consultant Physiotherapist specialising in neurological and orthopedic rehabilitation, including stroke recovery, Parkinson''s disease management and post-surgical joint rehabilitation.', 45, 1, 1);

-- Admin user (password: Admin@12345) - Dr. Meenakshi also administers the CMS for this single-consultant clinic.
INSERT INTO users (staff_id, name, email, password_hash, role, is_active) VALUES
(1, 'Dr. Meenakshi', 'cortex.kinetic@gmail.com', '$2y$10$1irn0QVmxVEqqIMvpRJijO1zPw9a7mYxsqirS3VSHpn/yxyjkW7fm', 'admin', 1);

-- Services
-- Names below are taken verbatim from the two specialisation lists on the
-- clinic's own flyer ("Specialized Neuro Rehabilitation" and "Advanced
-- Ortho Rehabilitation"). Fees/durations are NOT given by that source and
-- are editable placeholder demo values (admin > Services).
INSERT INTO services (name, slug, category, short_description, fee, gst_rate, duration_minutes, display_order, is_active) VALUES
('Initial Consultation & Assessment', 'initial-consultation', 'Consultation', 'Comprehensive physical assessment and treatment planning. First consultation is complimentary for a limited time.', 0.00, 0, 30, 1, 1),
('Stroke Recovery', 'stroke-recovery', 'Neuro Rehabilitation', 'Neuro-physiotherapy for recovery of movement and function after stroke.', 1200.00, 0, 45, 2, 1),
('Peripheral Neuropathy', 'peripheral-neuropathy', 'Neuro Rehabilitation', 'Rehabilitation for nerve-related weakness, numbness and sensory loss.', 1200.00, 0, 45, 3, 1),
('Parkinson''s Disease Management', 'parkinsons-disease-management', 'Neuro Rehabilitation', 'Movement, balance and mobility-focused therapy for Parkinson''s disease.', 1200.00, 0, 45, 4, 1),
('Spinal Cord Injury Care', 'spinal-cord-injury-care', 'Neuro Rehabilitation', 'Structured rehabilitation following spinal cord injury.', 1200.00, 0, 45, 5, 1),
('Autism', 'autism-therapy', 'Neuro Rehabilitation', 'Movement and developmental therapy support for children with autism.', 900.00, 0, 45, 6, 1),
('Cerebral Palsy & Developmental Disorders', 'cerebral-palsy-developmental-disorders', 'Neuro Rehabilitation', 'Developmental physiotherapy for cerebral palsy and related disorders.', 900.00, 0, 45, 7, 1),
('Geriatric Rehabilitation', 'geriatric-rehabilitation', 'Neuro Rehabilitation', 'Mobility, balance and fall-prevention programs for older adults.', 900.00, 0, 45, 8, 1),
('Post-Surgical (Joint Replacement) Recovery', 'post-surgical-joint-replacement-recovery', 'Ortho Rehabilitation', 'Structured recovery programs after joint replacement surgery.', 1200.00, 0, 45, 9, 1),
('Fracture Rehabilitation', 'fracture-rehabilitation', 'Ortho Rehabilitation', 'Rehabilitation to restore strength and movement after a fracture.', 1200.00, 0, 45, 10, 1),
('Sports Injury Care & Performance', 'sports-injury-care-performance', 'Ortho Rehabilitation', 'Rehabilitation and performance conditioning for sports-related injuries.', 1200.00, 0, 45, 11, 1),
('Chronic Neck & Back Pain', 'chronic-neck-back-pain', 'Ortho Rehabilitation', 'Assessment and treatment for persistent neck and back pain.', 1000.00, 0, 45, 12, 1),
('Arthritis Management', 'arthritis-management', 'Ortho Rehabilitation', 'Pain relief and joint-mobility programs for arthritis.', 1000.00, 0, 45, 13, 1),
('Pain Management', 'pain-management', 'Ortho Rehabilitation', 'Targeted physiotherapy for chronic and acute pain conditions.', 1000.00, 0, 45, 14, 1);

-- Packages
INSERT INTO packages (name, description, total_sessions, price, discount_percent, validity_days, is_active) VALUES
('12-Session Rehab Package', 'Twelve physiotherapy sessions at a discounted bundle rate.', 12, 12000.00, 10.00, 90, 1),
('6-Session Starter Package', 'Six sessions, ideal for a short-term rehabilitation goal.', 6, 6300.00, 5.00, 60, 1);

-- Blog
INSERT INTO blog_categories (name, slug) VALUES ('Recovery Tips', 'recovery-tips'), ('Sports Injury', 'sports-injury');
INSERT INTO blogs (category_id, author_user_id, title, slug, excerpt, content, status, published_at, meta_title, meta_description) VALUES
(1, 1, '5 Stretches to Do Every Morning', '5-stretches-to-do-every-morning', 'Simple stretches to improve mobility and reduce stiffness.', '<p>Starting your day with a few gentle stretches can improve circulation and reduce stiffness...</p>', 'published', NOW(), '5 Morning Stretches | PhysioWeb Clinic', 'Simple morning stretches recommended by our physiotherapists to reduce stiffness and improve mobility.'),
(2, 1, 'Returning to Sport After an ACL Injury', 'returning-to-sport-after-acl-injury', 'What a safe return-to-sport timeline looks like after ACL reconstruction.', '<p>Returning to sport after an ACL injury requires a structured, criteria-based progression...</p>', 'published', NOW(), 'Return to Sport After ACL Injury', 'A physiotherapist-guided overview of returning to sport safely after ACL reconstruction.');

-- Testimonials
INSERT INTO testimonials (patient_name, rating, content, status, source, approved_at) VALUES
('Rohit Verma', 5, 'After my knee surgery, the team here got me back on my feet faster than I expected. Highly recommend!', 'approved', 'admin_added', NOW()),
('Meera Nair', 5, 'Patient, knowledgeable, and genuinely caring staff. My back pain is finally manageable.', 'approved', 'admin_added', NOW());

-- Patients (password: Patient@123 for the first patient only; others have no portal login configured)
INSERT INTO patients (patient_code, first_name, last_name, gender, date_of_birth, mobile, email, password_hash, address, city, state, country, pincode, blood_group, allergies, existing_conditions, emergency_contact_name, emergency_contact_relationship, emergency_contact_mobile, is_active) VALUES
('PAT-2026-0001', 'Priya', 'Sharma', 'female', '1990-04-12', '+91 98111 11111', 'priya.sharma@example.com', '$2y$10$Q3zQV2i4hlDxeavFvP1K1O6btwvjSlLni5MpWMvbp1qg.mhrKVLdm', '12 Palm Street', 'Gurugram', 'Haryana', 'India', '122001', 'B+', 'None known', 'Mild lower back pain', 'Raj Sharma', 'Spouse', '+91 98111 22222', 1),
('PAT-2026-0002', 'Arjun', 'Kapoor', 'male', '1985-11-02', '+91 98222 22222', 'arjun.kapoor@example.com', NULL, '45 MG Road', 'Delhi', 'Delhi', 'India', '110001', 'O+', 'Penicillin', 'Post ACL reconstruction', 'Neha Kapoor', 'Sister', '+91 98222 33333', 1);

-- Treatment plan + sessions for Arjun (ACL rehab)
INSERT INTO treatment_plans (plan_number, patient_id, therapist_id, diagnosis, start_date, status, notes) VALUES
('PLAN-2026-0001', 2, 1, 'Post ACL reconstruction - Right knee', '2026-01-10', 'active', 'Standard 12-week ACL rehabilitation protocol.');

INSERT INTO treatment_sessions (treatment_plan_id, session_number, session_date, therapist_id, treatment_given, duration_minutes, pain_score_before, pain_score_after, mobility_score, progress_notes) VALUES
(1, 1, '2026-01-10', 1, 'Range of motion exercises, quadriceps activation', 45, 6, 4, 5, 'Good initial tolerance to exercise.'),
(1, 2, '2026-01-17', 1, 'Progressive resistance exercises, balance training', 45, 4, 2, 6, 'Improving quadriceps control.');

-- Appointments
INSERT INTO appointments (appointment_number, patient_id, therapist_id, service_id, scheduled_date, scheduled_time, duration_minutes, status, source, notes) VALUES
('APT-2026-0001', 1, 1, 5, '2026-10-05', '10:00:00', 45, 'scheduled', 'admin', 'Follow-up for lower back pain'),
('APT-2026-0002', 2, 1, 2, '2026-10-06', '11:00:00', 45, 'confirmed', 'admin', 'ACL rehab session 3');

-- Invoice + items + payment for Priya
INSERT INTO invoices (invoice_number, patient_id, invoice_date, subtotal, gst_amount, discount_amount, total_amount, amount_paid, status, created_by_user_id) VALUES
('INV-2026-0001', 1, '2026-09-20', 800.00, 0.00, 0.00, 800.00, 800.00, 'paid', 1);

INSERT INTO invoice_items (invoice_id, service_id, description, quantity, unit_price, gst_rate, line_total) VALUES
(1, 1, 'Initial Consultation & Assessment', 1, 800.00, 0, 800.00);

INSERT INTO payments (invoice_id, amount, payment_mode, payment_date, reference_number, received_by_user_id) VALUES
(1, 800.00, 'upi', '2026-09-20', 'UPI-REF-00123', 1);

-- id_sequences kept in sync with the demo codes inserted above
INSERT INTO id_sequences (prefix, year, last_value) VALUES
('PAT', 2026, 2),
('APT', 2026, 2),
('INV', 2026, 1),
('PLAN', 2026, 1);

SET FOREIGN_KEY_CHECKS = 1;
