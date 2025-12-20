<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES Y HELPERS
    |--------------------------------------------------------------------------
    */

    public function getActionLabelAttribute(): string
    {
        // Puedes mapear códigos a textos amigables si quieres
        return $this->action;
    }

    public function getShortAuditableAttribute(): string
    {
        if (!$this->auditable_type || !$this->auditable_id) {
            return '-';
        }

        $short = class_basename($this->auditable_type);
        return $short . ' #' . $this->auditable_id;
    }
}
