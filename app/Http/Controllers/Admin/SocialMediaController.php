<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialMedia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocialMediaController extends Controller
{
    public function index()
    {
        $socialMedias = SocialMedia::latest()->get();

        return Inertia::render('Admin/SocialMedia/Index', compact('socialMedias'));
    }

    public function create()
    {
        return Inertia::render('Admin/SocialMedia/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:100',
            'icon_svg' => 'nullable|string',
            'url' => 'required|url|max:255',
        ]);

        SocialMedia::create($validated);

        return redirect()->route('admin.social-media.index')
            ->with('success', 'Social media link added successfully.');
    }

    public function edit(SocialMedia $socialMedium)
    {
        return Inertia::render('Admin/SocialMedia/Edit', compact('socialMedium'));
    }

    public function update(Request $request, SocialMedia $socialMedium)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:100',
            'icon_svg' => 'nullable|string',
            'url' => 'required|url|max:255',
        ]);

        $socialMedium->update($validated);

        return redirect()->route('admin.social-media.index')
            ->with('success', 'Social media link updated successfully.');
    }

    public function destroy(SocialMedia $socialMedium)
    {
        $socialMedium->delete();

        return redirect()->route('admin.social-media.index')
            ->with('success', 'Social media link deleted successfully.');
    }
}
