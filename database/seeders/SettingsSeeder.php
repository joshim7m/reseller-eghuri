<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $settings = [
            'company_name' => 'Eghuri',
            'company_description' => 'Eghuri is a trusted online clothing marketplace in Bangladesh. We import quality fashion from China and sell locally to individuals and resellers.',
            'company_email' => 'support@eghuri.com',
            'company_mobile' => '01779967919',
            'company_address' => '6/C, Unite-2, Confidence Center, Shahjadpur, Gulshan, Dhaka-1212',
            'company_working_hours' => 'Sat-Thu 9AM-6PM',
            'company_copyright' => 'Copyright &copy; '.date('Y').' Eghuri. All rights reserved.',
            'company_marquee_text' => 'WhatsApp 01779967919 Support 24/7',
            'company_logo' => '',
            'company_favicon' => '',
            'facebook_handler' => '',
            'instagram_handler' => '',
            'tread_handler' => '',
            'youtube_handler' => '',
            'x_handler' => '',
            'whatsapp_number' => '+8801779967919',
            'telegram_number' => '+8801779967919',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
