<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeoSettingsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $settings = [
            'seo_title_suffix' => '| Eghuri',
            'seo_keywords' => 'nightdress, night wear, sexy clothes, sexy women clothing, women secret wear, reseller website in bangladesh, resell kori, chinese bra panty, chinese nightdress in bangladesh, lingerie online bangladesh, reseller shop in dhaka, wholesale dress in dhaka, নাইটড্রেস, রিসেল করি',
            'seo_home_title' => 'Eghuri - Online Shopping in Bangladesh for Women & Kids',
            'seo_home_description' => 'Shop the latest nightdress, lingerie and fashion for women in Bangladesh at Eghuri. Best price, cash on delivery, reseller price and 24/7 WhatsApp support.',
            'seo_products_title' => 'All Products - Eghuri',
            'seo_products_description' => 'Browse all products at Eghuri. Quality clothing and fashion imported from China, available at the best price in Bangladesh with COD and reseller pricing.',
            'seo_new_arrivals_title' => 'New Arrivals - Eghuri',
            'seo_new_arrivals_description' => 'Discover the latest new arrivals at Eghuri. Fresh fashion and clothing styles for women, available at the best price in Bangladesh.',
            'seo_hot_sale_title' => 'Hot Sale - Eghuri',
            'seo_hot_sale_description' => 'Grab the hottest deals at Eghuri. Discounted nightdress, lingerie and women clothing at the best price in Bangladesh.',
            'seo_categories_title' => 'All Categories - Eghuri',
            'seo_categories_description' => 'Explore all categories at Eghuri. Nightwear, lingerie, bra panty and women fashion at the best price in Bangladesh.',
            'seo_category_title_pattern' => '{name} in Bangladesh - Eghuri',
            'seo_category_description_pattern' => 'Buy {name} online in Bangladesh from Eghuri. Best price, COD available, reseller price and 24/7 WhatsApp support.',
            'seo_product_title_pattern' => '{title} - Best Price in BD - Eghuri',
            'seo_product_description_pattern' => 'Buy {title} online in Bangladesh from Eghuri. Best price available, cash on delivery, reseller price. WhatsApp for order.',
            'seo_page_description_pattern' => 'Learn more about {title} at Eghuri. Trusted online shopping in Bangladesh.',
            'seo_not_found_title' => 'Page Not Found - Eghuri',
            'seo_not_found_description' => 'The page you are looking for does not exist or has been moved. Return to the homepage to browse our products.',
            'seo_twitter_handle' => '@eghuribd',
            'seo_enable_sitemap' => '1',
            'seo_schema_org' => '1',
            'seo_schema_website' => '1',
            'seo_robots_custom' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
