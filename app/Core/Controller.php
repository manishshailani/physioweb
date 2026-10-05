<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller. Provides the handful of operations every controller in
 * this app needs: rendering a view (optionally inside a layout), redirects
 * with flash messages, CSRF verification, and auth/role guards.
 */
abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = null): void
    {
        $data['csrf_field'] = Csrf::field();
        $data['csrf_token'] = Csrf::token();
        $data['current_admin'] = Auth::adminUser();
        $data['current_patient'] = Auth::patientUser();
        $data['flash_success'] = Session::getFlash('success');
        $data['flash_error'] = Session::getFlash('error');
        $data['flash_errors'] = Session::getFlash('errors', []);
        $data['old'] = Session::getFlash('old', []);

        if ($layout !== null) {
            echo View::renderWithLayout($layout, $template, $data);
        } else {
            echo View::render($template, $data);
        }
    }

    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }

    protected function redirectBack(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        Response::redirect($referer);
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function flashErrors(array $errors, array $old = []): void
    {
        Session::flash('errors', $errors);
        Session::flash('old', $old);
    }

    /**
     * Verify the CSRF token on state-changing requests. Call this as the
     * first line of every POST/PUT/DELETE action. On failure, the request
     * is rejected with 419-style behavior (we use 400 for simplicity) and
     * never proceeds to touch the database.
     */
    protected function verifyCsrf(Request $request): void
    {
        if (!Csrf::verify($request->csrfToken())) {
            Logger::warning('CSRF token mismatch', ['path' => $request->path(), 'ip' => $request->ip()]);
            http_response_code(400);
            echo 'Invalid or expired form submission. Please go back, refresh the page, and try again.';
            exit;
        }
    }

    protected function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Please log in to continue.');
            Response::redirect('/admin/login');
        }
    }

    protected function requireAdminRole(string ...$roles): void
    {
        $this->requireAdmin();
        $user = Auth::adminUser();
        if ($user === null || !in_array($user['role'], $roles, true)) {
            Response::forbidden('You do not have permission to access this page.');
        }
    }

    protected function requirePatient(): void
    {
        if (!Auth::isPatient()) {
            Session::flash('error', 'Please log in to continue.');
            Response::redirect('/patient/login');
        }
    }

    /** The logged-in admin user's id, or null (used for audit log / created_by columns). */
    protected function currentAdminId(): ?int
    {
        $user = Auth::adminUser();
        return $user !== null ? (int) $user['id'] : null;
    }

    /** The logged-in patient's id, or null. */
    protected function currentPatientId(): ?int
    {
        $patient = Auth::patientUser();
        return $patient !== null ? (int) $patient['id'] : null;
    }
}
