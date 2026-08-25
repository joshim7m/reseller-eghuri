<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function index()
    {
        $notices = Notice::latest()->get();

        return Inertia::render('Admin/Notices/Index', compact('notices'));
    }

    public function create()
    {
        return Inertia::render('Admin/Notices/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('images/notices'), $filename);
            $validated['image'] = 'images/notices/'.$filename;
        } else {
            $validated['image'] = null;
        }

        $validated['status'] = $request->boolean('status');

        Notice::create($validated);

        return redirect()->route('admin.notices.index')
            ->with('success', 'Notice added successfully.');
    }

    public function edit(Notice $notice)
    {
        return Inertia::render('Admin/Notices/Edit', compact('notice'));
    }

    public function update(Request $request, Notice $notice)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($notice->image && file_exists(public_path($notice->image))) {
                unlink(public_path($notice->image));
            }
            $file = $request->file('image');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('images/notices'), $filename);
            $validated['image'] = 'images/notices/'.$filename;
        } else {
            unset($validated['image']);
        }

        $validated['status'] = $request->boolean('status');

        $notice->update($validated);

        return redirect()->route('admin.notices.index')
            ->with('success', 'Notice updated successfully.');
    }

    public function destroy(Notice $notice)
    {
        if ($notice->image && file_exists(public_path($notice->image))) {
            unlink(public_path($notice->image));
        }

        $notice->delete();

        return redirect()->route('admin.notices.index')
            ->with('success', 'Notice deleted successfully.');
    }
}
