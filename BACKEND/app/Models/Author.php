<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'course',
    ];

    // An author can be part of many theses
    public function theses()
    {
        return $this->belongsToMany(Thesis::class, 'author_thesis', 'author_id', 'thesis_id')->withTimestamps();
    }
}
