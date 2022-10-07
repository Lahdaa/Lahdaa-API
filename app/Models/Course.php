<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'course_name',
        'class_size',
        'course_category',
        'outcome',
        'about_course',
        'course_availability',
        'thumbnail_file_url',
        'promo_video_url',
        'course_rating',
        'price',
        'start_date',
        'end_date',
        'is_discounted',
        'discount_price',
        'is_published',
        'created_by',
        'created_at',
        'is_deleted',
        'is_featured_course',
    ];
}
