<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedLink extends Model
{
    protected $connection = 'dynamic'; // اتصال به دیتابیس داینامیک

    protected $fillable = [
        'url',
        'attempts',
        'error_message',
    ];

    protected $casts = [
        'attempts' => 'integer',
    ];
}
