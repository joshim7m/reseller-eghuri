<?php

namespace Database\Seeders;

use App\Models\InstagramImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstagramImagesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        InstagramImage::create([
            'title' => 'New Collection Drop',
            'image' => 'images/instagram/placeholder-0.jpg',
        ]);

        InstagramImage::create([
            'title' => 'Style of the Week',
            'image' => 'images/instagram/placeholder-1.jpg',
        ]);

        InstagramImage::create([
            'title' => 'Customer Favorites',
            'image' => 'images/instagram/placeholder-2.jpg',
        ]);
        InstagramImage::create([
            'title' => 'New Collection Drop',
            'image' => 'images/instagram/placeholder-3.jpg',
        ]);

        InstagramImage::create([
            'title' => 'Style of the Week',
            'image' => 'images/instagram/placeholder-4.jpg',
        ]);

        InstagramImage::create([
            'title' => 'Customer Favorites',
            'image' => 'images/instagram/placeholder-5.jpg',
        ]);
    }
}
