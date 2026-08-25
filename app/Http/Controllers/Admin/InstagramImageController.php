<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramImage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstagramImageController extends Controller
{
    public function index()
    {
        $images = InstagramImage::latest()->get();

        return Inertia::render('Admin/InstagramImages/Index', compact('images'));
    }

    public function create()
    {
        return Inertia::render('Admin/InstagramImages/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $file = $request->file('image');
        $filename = time().'_'.$file->getClientOriginalName();
        $file->move(public_path('images/instagram'), $filename);
        $validated['image'] = 'images/instagram/'.$filename;

        InstagramImage::create($validated);

        return redirect()->route('admin.instagram-images.index')
            ->with('success', 'Instagram image added successfully.');
    }

    public function edit(InstagramImage $instagramImage)
    {
        return Inertia::render('Admin/InstagramImages/Edit', compact('instagramImage'));
    }

    public function update(Request $request, InstagramImage $instagramImage)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($instagramImage->image && file_exists(public_path($instagramImage->image))) {
                unlink(public_path($instagramImage->image));
            }
            $file = $request->file('image');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('images/instagram'), $filename);
            $validated['image'] = 'images/instagram/'.$filename;
        } else {
            unset($validated['image']);
        }

        $instagramImage->update($validated);

        return redirect()->route('admin.instagram-images.index')
            ->with('success', 'Instagram image updated successfully.');
    }

    public function destroy(InstagramImage $instagramImage)
    {
        if ($instagramImage->image && file_exists(public_path($instagramImage->image))) {
            unlink(public_path($instagramImage->image));
        }

        $instagramImage->delete();

        return redirect()->route('admin.instagram-images.index')
            ->with('success', 'Instagram image deleted successfully.');
    }
}
