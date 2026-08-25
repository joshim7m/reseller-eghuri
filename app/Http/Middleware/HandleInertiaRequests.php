<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Page;
use App\Models\Setting;
use App\Models\SocialMedia;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->load('userDetail'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'settings' => Setting::all()->pluck('value', 'key'),
            'socialMedias' => SocialMedia::all(),
            'pages' => Page::active()->orderBy('title')->get(),
            'categories' => Category::with('children')->whereNull('parent_id')->orderBy('name')->get(),
        ];
    }
}
