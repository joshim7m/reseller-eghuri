<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function companyInfo()
    {
        return Inertia::render('Admin/Settings/Company');
    }

    public function siteConfig()
    {
        return Inertia::render('Admin/Settings/SiteConfig');
    }

    public function seo()
    {
        return Inertia::render('Admin/Settings/Seo');
    }

    public function update(Request $request)
    {
        $request->validate([
            'delivery_areas' => 'nullable|array',
            'delivery_areas.*.name' => 'required|string|max:100',
            'delivery_areas.*.charge' => 'required|integer|min:0|max:9999999',
        ]);

        $data = $request->except('_token', '_method');

        foreach ($data as $key => $value) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $filename = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('images/settings'), $filename);
                $value = 'images/settings/'.$filename;
            }
            Setting::set($key, is_array($value) ? json_encode(array_values($value)) : $value);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
