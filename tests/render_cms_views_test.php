<?php

declare(strict_types=1);

/**
 * Execution-based verification for Task 10: staff, therapists, CMS users,
 * settings, blog (+categories), gallery, testimonials, inquiries admin
 * views. Renders each with real data through the Model layer against an
 * in-memory SQLite DB; any PHP warning/notice is a hard failure.
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\View;
use App\Core\Database;
use App\Core\Auth;
use App\Models\Staff;
use App\Models\Therapist;
use App\Models\User;
use App\Models\Setting;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Gallery;
use App\Models\Testimonial;
use App\Models\ContactInquiry;

physioweb_test_db();
View::init(PHYSIOWEB_ROOT . '/app/Views');

$warnings = [];
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use (&$warnings) {
    $warnings[] = "$errstr in $errfile:$errline";
    return true;
});

function render_and_report(string $template, array $data, string $label): void
{
    try {
        $html = View::render($template, $data);
        echo "  ok - rendered {$label} (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "  FAIL - {$label} threw: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

$common = [
    'csrf_field' => '<input type="hidden" name="_csrf" value="test">',
    'csrf_token' => 'test',
    'current_admin' => ['id' => 1, 'name' => 'Dr. Meenakshi', 'role' => 'admin'],
    'current_patient' => null,
    'flash_success' => null,
    'flash_error' => null,
    'flash_errors' => [],
    'old' => [],
];

// ===== Staff =====
render_and_report('admin.staff.index', $common + ['page_title' => 'Staff', 'staff' => [], 'roles' => ['physiotherapist', 'receptionist', 'assistant', 'admin']], 'staff/index (empty)');

$staffId = Staff::register([
    'first_name' => 'Meenakshi', 'last_name' => '', 'role' => 'physiotherapist',
    'qualification' => 'BPT, MPT (Neuro)', 'specialization' => 'Neuro Rehab', 'experience_years' => 8.0,
    'phone' => '8882688868', 'email' => 'meenakshi@example.com', 'joining_date' => '2020-01-01', 'is_active' => 1,
]);
$frontDeskId = Staff::register([
    'first_name' => 'Front', 'last_name' => 'Desk', 'role' => 'receptionist',
    'qualification' => null, 'specialization' => null, 'experience_years' => null,
    'phone' => '8882688868', 'email' => null, 'joining_date' => null, 'is_active' => 1,
]);
$staffList = Staff::all('is_active DESC, first_name');
render_and_report('admin.staff.index', $common + ['page_title' => 'Staff', 'staff' => $staffList, 'roles' => ['physiotherapist', 'receptionist', 'assistant', 'admin']], 'staff/index (2 staff)');
render_and_report('admin.staff.form', $common + ['page_title' => 'Edit Staff', 'staff' => Staff::find($staffId['id']), 'roles' => ['physiotherapist', 'receptionist', 'assistant', 'admin']], 'staff/form (edit)');

// ===== Therapists =====
render_and_report('admin.therapists.index', $common + ['page_title' => 'Therapists', 'therapists' => [], 'eligibleStaff' => Staff::activeByRole('physiotherapist')], 'therapists/index (none yet, eligible staff shown)');

$therapistId = Therapist::create([
    'staff_id' => $staffId['id'], 'slug' => 'dr-meenakshi', 'bio' => 'Bio text',
    'default_session_minutes' => 45, 'is_public' => 1, 'display_order' => 0,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$therapists = Therapist::allWithStaff(false);
render_and_report('admin.therapists.index', $common + ['page_title' => 'Therapists', 'therapists' => $therapists, 'eligibleStaff' => []], 'therapists/index (1 therapist, no eligible staff left)');
render_and_report('admin.therapists.form', $common + ['page_title' => 'Edit Therapist', 'therapist' => Therapist::find($therapistId)], 'therapists/form (edit)');

// ===== Users =====
render_and_report('admin.users.index', $common + ['page_title' => 'Users', 'users' => [], 'staff' => $staffList, 'roles' => ['admin', 'receptionist', 'therapist_staff']], 'users/index (empty)');
$userId = User::create([
    'staff_id' => $staffId['id'], 'name' => 'Dr. Meenakshi', 'email' => 'meenakshi@example.com',
    'password_hash' => Auth::hashPassword('Admin@12345'), 'role' => 'admin', 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$users = User::all('is_active DESC, name');
render_and_report('admin.users.index', $common + ['page_title' => 'Users', 'users' => $users, 'staff' => $staffList, 'roles' => ['admin', 'receptionist', 'therapist_staff']], 'users/index (1 user, marked "You")');
render_and_report('admin.users.form', $common + ['page_title' => 'Edit User', 'editUser' => User::find($userId), 'staff' => $staffList, 'roles' => ['admin', 'receptionist', 'therapist_staff']], 'users/form (edit)');

// ===== Settings =====
render_and_report('admin.settings.index', $common + ['page_title' => 'Settings', 'settings' => []], 'settings/index (no settings saved yet)');
Setting::setMany(['clinic_name' => 'Cortex & Kinetic', 'clinic_phone' => '+91 88826 88868', 'clinic_logo' => '/assets/img/logo.svg']);
render_and_report('admin.settings.index', $common + ['page_title' => 'Settings', 'settings' => Setting::allAsMap()], 'settings/index (with saved values + logo preview)');

// ===== Blog =====
render_and_report('admin.blog.index', $common + ['page_title' => 'Blog', 'posts' => []], 'blog/index (empty)');
render_and_report('admin.blog.categories', $common + ['page_title' => 'Categories', 'categories' => []], 'blog/categories (empty)');

$catId = BlogCategory::create(['name' => 'Recovery Tips', 'slug' => 'recovery-tips', 'created_at' => Database::now()]);
$categories = BlogCategory::allOrdered();
render_and_report('admin.blog.categories', $common + ['page_title' => 'Categories', 'categories' => $categories], 'blog/categories (1 category)');
render_and_report('admin.blog.form', $common + ['page_title' => 'New Post', 'post' => null, 'categories' => $categories], 'blog/form (create)');

$blogId = Blog::create([
    'category_id' => $catId, 'author_user_id' => $userId, 'title' => '5 Tips for Knee Recovery',
    'slug' => Blog::uniqueSlug('5 Tips for Knee Recovery'), 'excerpt' => 'Short excerpt', 'content' => 'Full content here',
    'featured_image' => null, 'tags' => 'knee,recovery', 'meta_title' => null, 'meta_description' => null,
    'status' => 'published', 'published_at' => Database::now(), 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$posts = Database::fetchAll('SELECT b.*, bc.name AS category_name FROM blogs b LEFT JOIN blog_categories bc ON bc.id = b.category_id ORDER BY b.created_at DESC');
render_and_report('admin.blog.index', $common + ['page_title' => 'Blog', 'posts' => $posts], 'blog/index (1 published post with category)');
render_and_report('admin.blog.form', $common + ['page_title' => 'Edit Post', 'post' => Blog::find($blogId), 'categories' => $categories], 'blog/form (edit)');

// draft post (no published_at) to exercise the "&mdash;" branch
Blog::create([
    'category_id' => null, 'author_user_id' => null, 'title' => 'Draft Post', 'slug' => Blog::uniqueSlug('Draft Post'),
    'excerpt' => null, 'content' => 'Draft content', 'featured_image' => null, 'tags' => null, 'meta_title' => null,
    'meta_description' => null, 'status' => 'draft', 'published_at' => null, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$postsWithDraft = Database::fetchAll('SELECT b.*, bc.name AS category_name FROM blogs b LEFT JOIN blog_categories bc ON bc.id = b.category_id ORDER BY b.created_at DESC');
render_and_report('admin.blog.index', $common + ['page_title' => 'Blog', 'posts' => $postsWithDraft], 'blog/index (published + uncategorized draft)');

// ===== Gallery =====
render_and_report('admin.gallery.index', $common + ['page_title' => 'Gallery', 'items' => [], 'categories' => ['clinic', 'treatments', 'events']], 'gallery/index (empty)');
Gallery::create(['title' => 'Reception Area', 'media_type' => 'image', 'file_path' => '/uploads/gallery/test.jpg', 'category' => 'clinic', 'display_order' => 0, 'created_at' => Database::now()]);
Gallery::create(['title' => null, 'media_type' => 'image', 'file_path' => '/uploads/gallery/test2.jpg', 'category' => 'treatments', 'display_order' => 1, 'created_at' => Database::now()]);
render_and_report('admin.gallery.index', $common + ['page_title' => 'Gallery', 'items' => Gallery::all('display_order'), 'categories' => ['clinic', 'treatments', 'events']], 'gallery/index (2 images, one untitled)');

// ===== Testimonials =====
render_and_report('admin.testimonials.index', $common + ['page_title' => 'Testimonials', 'pending' => [], 'approved' => [], 'rejected' => []], 'testimonials/index (all empty)');
Testimonial::create(['patient_name' => 'Ravi K', 'patient_photo' => null, 'rating' => 5, 'content' => 'Great care!', 'status' => 'pending', 'source' => 'public_submission', 'created_at' => Database::now(), 'approved_at' => null]);
Testimonial::create(['patient_name' => 'Sunita P', 'patient_photo' => null, 'rating' => 4, 'content' => 'Very helpful.', 'status' => 'approved', 'source' => 'admin_added', 'created_at' => Database::now(), 'approved_at' => Database::now()]);
Testimonial::create(['patient_name' => 'Spam Bot', 'patient_photo' => null, 'rating' => 1, 'content' => 'xxx', 'status' => 'rejected', 'source' => 'public_submission', 'created_at' => Database::now(), 'approved_at' => null]);
render_and_report('admin.testimonials.index', $common + [
    'page_title' => 'Testimonials', 'pending' => Testimonial::pending(), 'approved' => Testimonial::approved(),
    'rejected' => Database::fetchAll("SELECT * FROM testimonials WHERE status = 'rejected' ORDER BY created_at DESC"),
], 'testimonials/index (1 pending, 1 approved, 1 rejected)');

// ===== Inquiries =====
render_and_report('admin.inquiries.index', $common + ['page_title' => 'Inquiries', 'inquiries' => [], 'statuses' => ['new', 'read', 'responded', 'closed'], 'filterStatus' => ''], 'inquiries/index (empty)');
ContactInquiry::create(['name' => 'Priya S', 'mobile' => '9988776655', 'email' => 'priya@example.com', 'subject' => 'Appointment query', 'message' => 'Do you have slots this week?', 'status' => 'new', 'created_at' => Database::now()]);
render_and_report('admin.inquiries.index', $common + ['page_title' => 'Inquiries', 'inquiries' => Database::fetchAll('SELECT * FROM contact_inquiries ORDER BY created_at DESC'), 'statuses' => ['new', 'read', 'responded', 'closed'], 'filterStatus' => ''], 'inquiries/index (1 new inquiry)');

// ===== Logic checks =====
assert_true(count(array_filter(Staff::activeByRole('physiotherapist'), static fn ($s) => (int) $s['id'] === $staffId['id'])) === 1, 'activeByRole(physiotherapist) finds the registered physiotherapist');
assert_true(User::whereFirst('email', 'meenakshi@example.com') !== null, 'User::whereFirst finds the created CMS user by email');
assert_true(password_verify('Admin@12345', User::find($userId)['password_hash']), 'the stored password hash verifies against the original plaintext password');
assert_true(BlogCategory::findBySlug('recovery-tips') !== null, 'BlogCategory::findBySlug finds the created category');
assert_true(Blog::findPublishedBySlug(Blog::find($blogId)['slug']) !== null, 'the published post is findable via findPublishedBySlug');
assert_true(Blog::findPublishedBySlug(Blog::find($blogId)['slug'])['title'] === '5 Tips for Knee Recovery', 'findPublishedBySlug returns the correct row');

restore_error_handler();

if ($warnings !== []) {
    echo "\n" . count($warnings) . " PHP warning(s)/notice(s) emitted during rendering:\n";
    foreach ($warnings as $w) {
        echo " - {$w}\n";
    }
    exit(1);
}

exit(physioweb_test_summary());
