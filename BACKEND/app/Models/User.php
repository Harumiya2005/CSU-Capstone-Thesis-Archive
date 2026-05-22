<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- Added the Sanctum import here

#[Fillable(['identifier', 'name', 'password', 'role', 'course', 'year_level'])] // <-- Replaced 'email' with your custom columns
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // <-- Added HasApiTokens trait here

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Removed 'email_verified_at' since we use 'identifier' instead
            'password' => 'hashed',
        ];
    }

    // --- RELATIONSHIPS ---

    // A user (Admin) can upload many theses
    public function theses()
    {
        return $this->hasMany(Thesis::class, 'uploaded_by');
    }

    // A user (Admin) can have many audit logs
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }
}
