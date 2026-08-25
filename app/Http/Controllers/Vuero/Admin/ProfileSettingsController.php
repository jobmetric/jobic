<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProfileSettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('vuero.admin.profile-settings.index', [
            'settings' => [
                ['label' => 'Company', 'title' => 'Manage Company', 'icon' => 'building'],
                ['label' => 'Team', 'title' => 'Manage Team', 'icon' => 'users'],
                ['label' => 'Projects', 'title' => 'Project Settings', 'icon' => 'briefcase'],
                ['label' => 'Permissions', 'title' => 'Manage Permissions', 'icon' => 'lock'],
                ['label' => 'Document', 'title' => 'Document Policies', 'icon' => 'content'],
                ['label' => 'Upload', 'title' => 'Upload Policies', 'icon' => 'upload'],
                ['label' => 'Billing', 'title' => 'Billing and Plans', 'icon' => 'credit-card'],
                ['label' => 'Messaging', 'title' => 'Messaging Settings', 'icon' => 'message'],
                ['label' => 'Security', 'title' => 'Security Settings', 'icon' => 'shield'],
                ['label' => 'Preferences', 'title' => 'General Settings', 'icon' => 'settings'],
            ],
        ]);
    }
}
