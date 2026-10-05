<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\FileUpload;
use App\Core\Request;
use App\Models\Setting;

final class SettingsController extends Controller
{
    /** Keys this form manages, in display order. Anything else in the settings table is left untouched. */
    private const KEYS = [
        'clinic_name', 'clinic_tagline', 'clinic_address', 'clinic_phone', 'clinic_email',
        'clinic_offer', 'social_facebook', 'social_instagram',
        'homepage_hero_title', 'homepage_hero_subtitle', 'homepage_about_text',
        'clinic_vision', 'clinic_mission',
    ];

    public function index(): void
    {
        $this->requireAdminRole('admin');
        $this->view('admin.settings.index', [
            'page_title' => 'Site Settings',
            'settings' => Setting::allAsMap(),
        ], 'layouts.admin');
    }

    public function update(Request $request): void
    {
        $this->requireAdminRole('admin');
        $this->verifyCsrf($request);

        $values = [];
        foreach (self::KEYS as $key) {
            $value = trim((string) $request->input($key, ''));
            $values[$key] = $value !== '' ? $value : null;
        }

        $logo = $request->file('clinic_logo_file');
        if ($logo !== null) {
            $uploaded = FileUpload::store($logo, dirname(__DIR__, 3) . '/public/uploads/branding', 'image', 5);
            if (!$uploaded['ok']) {
                $this->flashError($uploaded['error']);
                $this->redirect('/admin/settings');
            }
            $values['clinic_logo'] = '/uploads/branding/' . $uploaded['stored_name'];
        }

        Setting::setMany($values);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'settings', null, 'Updated site settings');

        $this->flashSuccess('Settings saved.');
        $this->redirect('/admin/settings');
    }
}
