<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $pages = [
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'content' => '<h2>Terms and Conditions</h2><p>These terms and conditions outline the rules and regulations for the use of our website.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2><p>This privacy policy explains how we collect, use, and protect your personal information.</p>',
            ],
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h2>About Us</h2><p>We are a wholesale seller dedicated to providing quality products at affordable prices.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::create([
                ...$page,
                'status' => true,
            ]);
        }
    }
}
