<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::forUser(Auth::id());
        return view('panel.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name'    => ['required', 'string', 'max:100'],
            'timezone'    => ['required', 'string', 'max:100'],
            'date_format' => ['required', 'string', 'max:20'],
            'theme_color' => ['required', 'in:wolf_red,deep_ocean,graphite_mono'],
            'dense_view'  => ['nullable', 'boolean'],
        ]);

        $data['dense_view'] = $request->boolean('dense_view');

        Setting::forUser(Auth::id())->update($data);

        return back()->with('success', 'Settings saved successfully.');
    }
}
