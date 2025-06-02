<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    protected $connection = 'dynamic'; // اتصال به دیتابیس داینامیک

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url',
        'source_url',
        'is_processed',
        'product_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_processed' => 'boolean',
    ];
}
