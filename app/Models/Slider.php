<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = [
        'title',
        'sub_title',
        'button_text',
        'button_link',
        'image',
    ];
}
