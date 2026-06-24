<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::all()->groupBy(function ($setting) {
            return explode('.', $setting->key)[0] ?? 'general';
        });

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $oldPath = Setting::get('store.logo_path');
            if ($oldPath) {
                Storage::disk(config('filesystems.default'))->delete($oldPath);
            }

            $path = $request->file('logo')->store('brand', config('filesystems.default'));
            Setting::set('store.logo_path', $path);
        }

        foreach ($request->input('settings', []) as $key => $value) {
            if ($key === 'store.logo_path') {
                continue;
            }
            Setting::set($key, $value ?? '');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
}
