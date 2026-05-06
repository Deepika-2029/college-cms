<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService   $settings,
        private CloudinaryService $cloudinary,
    ) {}

    public function index()
    {
        return view('admin.settings.index', [
            'settings'           => $this->settings->all(),
            'cloudinaryReady'    => $this->cloudinary->isConfigured(),
            'cloudinaryFolder'   => $this->cloudinary->getFolder(),
        ]);
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can change settings.');
        $request->validate([
            'media_driver'             => ['required', 'in:local,cloudinary'],
            'cloudinary_cloud_name'    => ['nullable', 'string', 'max:100'],
            'cloudinary_api_key'       => ['nullable', 'string', 'max:200'],
            'cloudinary_api_secret'    => ['nullable', 'string', 'max:200'],
            'cloudinary_upload_preset' => ['nullable', 'string', 'max:100'],
            'cloudinary_folder'        => ['nullable', 'string', 'max:100'],
            'items_per_page'           => ['nullable', 'integer', 'min:5', 'max:100'],
            'timezone'                 => ['nullable', 'string', 'max:60'],
            'admin_theme'              => ['nullable', 'string', 'max:50'],
        ]);

        $this->settings->fill($request->only([
            'media_driver', 'cloudinary_cloud_name', 'cloudinary_api_key',
            'cloudinary_upload_preset', 'cloudinary_folder',
            'items_per_page', 'timezone', 'admin_theme'
        ]));

        // Don't overwrite API secret if left blank (masked)
        $secret = $request->input('cloudinary_api_secret');
        if ($secret && $secret !== '••••••••') {
            $this->settings->set('cloudinary_api_secret', $secret);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings saved successfully.');
    }

    public function switchTheme(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can change settings.');
        $request->validate(['theme' => ['required', 'string', 'max:50']]);
        $this->settings->set('admin_theme', $request->theme);
        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Test Cloudinary credentials and return connection status.
     */
    public function testCloudinary(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Allow testing with posted credentials (before saving)
        $cloudName = $request->input('cloudinary_cloud_name') ?: $this->settings->get('cloudinary_cloud_name');
        $apiKey    = $request->input('cloudinary_api_key')    ?: $this->settings->get('cloudinary_api_key');
        $apiSecret = $request->input('cloudinary_api_secret') ?: $this->settings->get('cloudinary_api_secret');

        // If credentials were posted, temporarily override service (test without saving)
        if ($cloudName && $apiKey && $apiSecret && $apiSecret !== '••••••••') {
            // Temporarily patch the service via reflection for this request only
            $tempSettings = clone $this->settings;
            $tempSettings->fill([
                'cloudinary_cloud_name'   => $cloudName,
                'cloudinary_api_key'      => $apiKey,
                'cloudinary_api_secret'   => $apiSecret,
            ]);
            $tempCloudinary = new CloudinaryService($tempSettings);
            $result = $tempCloudinary->testConnection();
        } else {
            $result = $this->cloudinary->testConnection();
        }

        return response()->json($result);
    }

    /** API: return the current media driver for JS — used by CRUD form */
    public function mediaConfig()
    {
        return response()->json([
            'driver'            => $this->settings->mediaDriver(),
            'cloudinaryReady'   => $this->settings->cloudinaryConfigured(),
        ]);
    }
}
