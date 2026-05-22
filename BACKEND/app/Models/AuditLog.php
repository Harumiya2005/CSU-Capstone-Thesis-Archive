<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
    ];

    // An audit log belongs to a specific admin
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
