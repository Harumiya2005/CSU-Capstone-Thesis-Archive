<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thesis extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'abstract',
        'publication_year',
        'course_category',
        'file_path',
        'uploaded_by',
    ];

    // A thesis belongs to the Admin who uploaded it
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // A thesis can have many authors (Many-to-Many relationship)
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_thesis', 'thesis_id', 'author_id')->withTimestamps();
    }
}
