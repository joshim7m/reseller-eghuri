<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Slider::create([
            'title' => 'New Season Styles',
            'sub_title' => 'Fresh looks for the new season, curated for you.',
            'button_text' => 'Shop Now',
            'button_link' => '/products',
            'image' => 'images/instagram/1785428029_close-up-cool-stylish-girl-underwear-jeans-jacket-brunette-young-woman-big-fashionable-sunglasses-posing-camera-brutal-beautiful-model-holding-hand-up-gesturing-by-finger (1).jpg',
        ]);

        Slider::create([
            'title' => 'Premium Innerwear',
            'sub_title' => 'Comfort and quality in every piece.',
            'button_text' => 'Explore',
            'button_link' => '/products',
            'image' => 'images/instagram/1785428044_bra and panty.jpg',
        ]);

        Slider::create([
            'title' => 'Everyday Essentials',
            'sub_title' => 'Timeless basics you will reach for again and again.',
            'button_text' => 'Shop Now',
            'button_link' => '/products',
            'image' => 'images/instagram/1786327132_1783687396-h9c6199869cbb493195a784bfcf4e1284f.jpg',
        ]);

        Slider::create([
            'title' => 'Women Inner Wear',
            'sub_title' => 'Designed for a perfect fit and all-day comfort.',
            'button_text' => 'Discover',
            'button_link' => '/products',
            'image' => 'images/instagram/1786327142_women inner part.jpg',
        ]);

        Slider::create([
            'title' => 'Limited Time Offer',
            'sub_title' => 'Grab your favorites before they are gone.',
            'button_text' => 'Shop Sale',
            'button_link' => '/products',
            'image' => 'images/instagram/1786327154_1775896220-o1cn01iqjrzg2ldskqfnkgx_!!2212982509659-0-cib.jpg',
        ]);
    }
}
