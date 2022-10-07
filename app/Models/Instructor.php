<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rating',
        'is_deleted',
        'is_approved',
        'linkedin_profile_url',
        'country',
        'profile_url',
        'profile_picture_url',
        'is_email_notification_checked',
        'is_sms_notification_checked',
        'about_you',
        'professional_portfolio',
        'other_platforms',
        'course_category',
        'availability',
        'why_you_want_to_be_an_instructor',
        'why_do_you_want_to_teach',
        'speak_english_frequently',
        'where_did_you_hear_about_stevia'
    ];
}
