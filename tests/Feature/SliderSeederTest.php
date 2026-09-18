<?php

use App\Models\Slider;
use Database\Seeders\SliderSeeder;

it('seeds five sliders', function () {
    $this->seed(SliderSeeder::class);

    expect(Slider::count())->toBe(5);
});

it('seeds sliders with images from the clothing business', function () {
    $this->seed(SliderSeeder::class);

    Slider::query()->get()->each(function (Slider $slider) {
        expect($slider->image)->toStartWith('images/');

        expect(file_exists(public_path($slider->image)))->toBeTrue();
    });
});
