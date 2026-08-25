<?php

namespace Database\Seeders;

use App\Models\Notice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NoticeSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Notice::create([
            'title' => '২০২৬ সালের পবিত্র ঈদুল আজহা উপলক্ষে',
            'content' => '২০২৬ সালের পবিত্র ঈদুল আজহা উপলক্ষে বাংলাদেশ সরকার ও ই-কমার্স খাত সংশ্লিষ্ট প্রতিষ্ঠানগুলোর সাধারণ ছুটির সময়সূচি ছিল ২৫ মে থেকে ৩১ মে, ২০২৬। এই সময় সরকারি ছুটি ও কুরিয়ার সার্ভিসের কার্যক্রম সীমিত থাকায় অনলাইন শপ বা ই-কমার্স প্রতিষ্ঠানগুলো গ্রাহকদের জন্য বিশেষ নোটিশ বা নির্দেশিকা প্রকাশ করেছিল।',
            'image' => null,
            'status' => true,
        ]);
    }
}
