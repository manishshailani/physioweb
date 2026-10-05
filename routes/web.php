<?php

declare(strict_types=1);

/**
 * All HTTP routes for the application. Grouped by area: public website,
 * shared auth, admin CMS, patient portal, and small JSON API endpoints used
 * by AJAX (calendar drag-drop, dashboard charts, global search).
 */

use App\Core\Router;

use App\Controllers\Public\HomeController;
use App\Controllers\Public\AboutController;
use App\Controllers\Public\ServiceController as PublicServiceController;
use App\Controllers\Public\TeamController;
use App\Controllers\Public\GalleryController as PublicGalleryController;
use App\Controllers\Public\BlogController;
use App\Controllers\Public\TestimonialController as PublicTestimonialController;
use App\Controllers\Public\ContactController;
use App\Controllers\Public\BookingController;
use App\Controllers\Public\SeoController;

use App\Controllers\Auth\AdminAuthController;
use App\Controllers\Auth\PatientAuthController;

use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PatientController as AdminPatientController;
use App\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Controllers\Admin\TreatmentPlanController;
use App\Controllers\Admin\AssessmentController;
use App\Controllers\Admin\ExerciseController;
use App\Controllers\Admin\ServiceController as AdminServiceController;
use App\Controllers\Admin\InvoiceController;
use App\Controllers\Admin\PaymentController;
use App\Controllers\Admin\PackageController;
use App\Controllers\Admin\StaffController;
use App\Controllers\Admin\TherapistController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\BlogController as AdminBlogController;
use App\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Controllers\Admin\InquiryController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\AuditLogController;
use App\Controllers\Admin\SearchController;
use App\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Controllers\Admin\UserController;

use App\Controllers\Patient\PortalController;
use App\Controllers\Patient\PatientAppointmentController;
use App\Controllers\Patient\PatientBillingController;
use App\Controllers\Patient\PatientProfileController;
use App\Controllers\Patient\DocumentController as PatientDocumentController;

$router = new Router();

/* ------------------------------------------------------------------ *
 * Public website
 * ------------------------------------------------------------------ */
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [AboutController::class, 'index']);
$router->get('/services', [PublicServiceController::class, 'index']);
$router->get('/services/{slug}', [PublicServiceController::class, 'show']);
$router->get('/team', [TeamController::class, 'index']);
$router->get('/team/{slug}', [TeamController::class, 'show']);
$router->get('/gallery', [PublicGalleryController::class, 'index']);
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/category/{slug}', [BlogController::class, 'category']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);
$router->get('/testimonials', [PublicTestimonialController::class, 'index']);
$router->get('/contact', [ContactController::class, 'index']);
$router->post('/contact', [ContactController::class, 'store']);
$router->get('/book-appointment', [BookingController::class, 'index']);
$router->post('/book-appointment', [BookingController::class, 'store']);

$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);

/* ------------------------------------------------------------------ *
 * Authentication
 * ------------------------------------------------------------------ */
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

$router->get('/patient/login', [PatientAuthController::class, 'showLogin']);
$router->post('/patient/login', [PatientAuthController::class, 'login']);
$router->get('/patient/register', [PatientAuthController::class, 'showRegister']);
$router->post('/patient/register', [PatientAuthController::class, 'register']);
$router->post('/patient/logout', [PatientAuthController::class, 'logout']);

/* ------------------------------------------------------------------ *
 * Admin CMS
 * ------------------------------------------------------------------ */
$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/dashboard/chart/{name}', [DashboardController::class, 'chartData']);

$router->get('/admin/patients', [AdminPatientController::class, 'index']);
$router->get('/admin/patients/create', [AdminPatientController::class, 'create']);
$router->post('/admin/patients', [AdminPatientController::class, 'store']);
$router->get('/admin/patients/{id}', [AdminPatientController::class, 'show']);
$router->get('/admin/patients/{id}/edit', [AdminPatientController::class, 'edit']);
$router->post('/admin/patients/{id}', [AdminPatientController::class, 'update']);
$router->post('/admin/patients/{id}/delete', [AdminPatientController::class, 'destroy']);
$router->post('/admin/patients/{id}/documents', [AdminPatientController::class, 'uploadDocument']);

$router->get('/admin/appointments', [AdminAppointmentController::class, 'index']);
$router->get('/admin/appointments/calendar', [AdminAppointmentController::class, 'calendar']);
$router->get('/admin/appointments/calendar/feed', [AdminAppointmentController::class, 'calendarFeed']);
$router->get('/admin/appointments/create', [AdminAppointmentController::class, 'create']);
$router->post('/admin/appointments', [AdminAppointmentController::class, 'store']);
$router->get('/admin/appointments/{id}/edit', [AdminAppointmentController::class, 'edit']);
$router->post('/admin/appointments/{id}', [AdminAppointmentController::class, 'update']);
$router->post('/admin/appointments/{id}/reschedule', [AdminAppointmentController::class, 'reschedule']);
$router->post('/admin/appointments/{id}/status', [AdminAppointmentController::class, 'updateStatus']);
$router->post('/admin/appointments/{id}/delete', [AdminAppointmentController::class, 'destroy']);

$router->get('/admin/patients/{patientId}/treatment-plans', [TreatmentPlanController::class, 'index']);
$router->post('/admin/patients/{patientId}/treatment-plans', [TreatmentPlanController::class, 'store']);
$router->get('/admin/treatment-plans/{id}/edit', [TreatmentPlanController::class, 'edit']);
$router->post('/admin/treatment-plans/{id}', [TreatmentPlanController::class, 'update']);
$router->post('/admin/treatment-plans/{id}/delete', [TreatmentPlanController::class, 'destroy']);
$router->post('/admin/treatment-plans/{id}/sessions', [TreatmentPlanController::class, 'addSession']);
$router->post('/admin/treatment-sessions/{id}/delete', [TreatmentPlanController::class, 'deleteSession']);

$router->get('/admin/patients/{patientId}/assessments', [AssessmentController::class, 'index']);
$router->post('/admin/patients/{patientId}/assessments', [AssessmentController::class, 'store']);
$router->post('/admin/assessments/{id}/delete', [AssessmentController::class, 'destroy']);
$router->get('/admin/patients/{patientId}/progress', [AssessmentController::class, 'progressData']);

$router->get('/admin/exercises', [ExerciseController::class, 'index']);
$router->post('/admin/exercises', [ExerciseController::class, 'store']);
$router->get('/admin/exercises/{id}/edit', [ExerciseController::class, 'edit']);
$router->post('/admin/exercises/{id}', [ExerciseController::class, 'update']);
$router->post('/admin/exercises/{id}/delete', [ExerciseController::class, 'destroy']);
$router->post('/admin/patients/{patientId}/exercises', [ExerciseController::class, 'assign']);
$router->post('/admin/exercise-assignments/{id}/delete', [ExerciseController::class, 'unassign']);

$router->get('/admin/services', [AdminServiceController::class, 'index']);
$router->post('/admin/services', [AdminServiceController::class, 'store']);
$router->get('/admin/services/{id}/edit', [AdminServiceController::class, 'edit']);
$router->post('/admin/services/{id}', [AdminServiceController::class, 'update']);
$router->post('/admin/services/{id}/delete', [AdminServiceController::class, 'destroy']);

$router->get('/admin/invoices', [InvoiceController::class, 'index']);
$router->get('/admin/invoices/create', [InvoiceController::class, 'create']);
$router->post('/admin/invoices', [InvoiceController::class, 'store']);
$router->get('/admin/invoices/{id}', [InvoiceController::class, 'show']);
$router->get('/admin/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
$router->post('/admin/invoices/{id}/email', [InvoiceController::class, 'email']);
$router->post('/admin/invoices/{id}/delete', [InvoiceController::class, 'destroy']);

$router->post('/admin/invoices/{invoiceId}/payments', [PaymentController::class, 'store']);
$router->post('/admin/payments/{id}/delete', [PaymentController::class, 'destroy']);

$router->get('/admin/packages', [PackageController::class, 'index']);
$router->post('/admin/packages', [PackageController::class, 'store']);
$router->get('/admin/packages/{id}/edit', [PackageController::class, 'edit']);
$router->post('/admin/packages/{id}', [PackageController::class, 'update']);
$router->post('/admin/packages/{id}/delete', [PackageController::class, 'destroy']);
$router->post('/admin/patients/{patientId}/packages', [PackageController::class, 'subscribe']);

$router->get('/admin/staff', [StaffController::class, 'index']);
$router->post('/admin/staff', [StaffController::class, 'store']);
$router->get('/admin/staff/{id}/edit', [StaffController::class, 'edit']);
$router->post('/admin/staff/{id}', [StaffController::class, 'update']);
$router->post('/admin/staff/{id}/delete', [StaffController::class, 'destroy']);

$router->get('/admin/therapists', [TherapistController::class, 'index']);
$router->post('/admin/therapists', [TherapistController::class, 'store']);
$router->get('/admin/therapists/{id}/edit', [TherapistController::class, 'edit']);
$router->post('/admin/therapists/{id}', [TherapistController::class, 'update']);
$router->post('/admin/therapists/{id}/delete', [TherapistController::class, 'destroy']);

$router->get('/admin/users', [UserController::class, 'index']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit']);
$router->post('/admin/users/{id}', [UserController::class, 'update']);
$router->post('/admin/users/{id}/delete', [UserController::class, 'destroy']);

$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings', [SettingsController::class, 'update']);

$router->get('/admin/blog', [AdminBlogController::class, 'index']);
$router->get('/admin/blog/create', [AdminBlogController::class, 'create']);
$router->post('/admin/blog', [AdminBlogController::class, 'store']);
$router->get('/admin/blog/{id}/edit', [AdminBlogController::class, 'edit']);
$router->post('/admin/blog/{id}', [AdminBlogController::class, 'update']);
$router->post('/admin/blog/{id}/delete', [AdminBlogController::class, 'destroy']);
$router->get('/admin/blog-categories', [AdminBlogController::class, 'categories']);
$router->post('/admin/blog-categories', [AdminBlogController::class, 'storeCategory']);
$router->post('/admin/blog-categories/{id}/delete', [AdminBlogController::class, 'destroyCategory']);

$router->get('/admin/gallery', [AdminGalleryController::class, 'index']);
$router->post('/admin/gallery', [AdminGalleryController::class, 'store']);
$router->post('/admin/gallery/{id}/delete', [AdminGalleryController::class, 'destroy']);

$router->get('/admin/testimonials', [AdminTestimonialController::class, 'index']);
$router->post('/admin/testimonials/{id}/approve', [AdminTestimonialController::class, 'approve']);
$router->post('/admin/testimonials/{id}/reject', [AdminTestimonialController::class, 'reject']);
$router->post('/admin/testimonials/{id}/delete', [AdminTestimonialController::class, 'destroy']);

$router->get('/admin/inquiries', [InquiryController::class, 'index']);
$router->post('/admin/inquiries/{id}/status', [InquiryController::class, 'updateStatus']);
$router->post('/admin/inquiries/{id}/delete', [InquiryController::class, 'destroy']);

$router->get('/admin/reports/patients', [ReportController::class, 'patients']);
$router->get('/admin/reports/appointments', [ReportController::class, 'appointments']);
$router->get('/admin/reports/revenue', [ReportController::class, 'revenue']);
$router->get('/admin/reports/treatments', [ReportController::class, 'treatments']);
$router->get('/admin/reports/{type}/export/{format}', [ReportController::class, 'export']);

$router->get('/admin/audit-logs', [AuditLogController::class, 'index']);

$router->get('/admin/search', [SearchController::class, 'index']);

$router->get('/admin/documents/{id}', [AdminDocumentController::class, 'download']);

/* ------------------------------------------------------------------ *
 * Patient portal
 * ------------------------------------------------------------------ */
$router->get('/patient', [PortalController::class, 'index']);
$router->get('/patient/appointments', [PatientAppointmentController::class, 'index']);
$router->get('/patient/appointments/book', [PatientAppointmentController::class, 'create']);
$router->post('/patient/appointments', [PatientAppointmentController::class, 'store']);
$router->post('/patient/appointments/{id}/cancel', [PatientAppointmentController::class, 'cancel']);
$router->get('/patient/treatment-plan', [PortalController::class, 'treatmentPlan']);
$router->get('/patient/exercises', [PortalController::class, 'exercises']);
$router->get('/patient/billing', [PatientBillingController::class, 'index']);
$router->get('/patient/billing/invoices/{id}/pdf', [PatientBillingController::class, 'invoicePdf']);
$router->get('/patient/profile', [PatientProfileController::class, 'edit']);
$router->post('/patient/profile', [PatientProfileController::class, 'update']);
$router->get('/patient/documents/{id}', [PatientDocumentController::class, 'download']);

return $router;
