<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeWindow extends Model
{
    use HasFactory;

    protected $table = 'time_windows';

    protected $fillable = [
        'sucursal_id',
        'tipo',          // salida | recojo
        'area_id',
        'user_id',
        'role_id',
        'horario_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'reabierto_hasta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'reabierto_hasta' => 'datetime',
    ];

    public const ESTADO_ACTIVO    = 'activo';
    public const ESTADO_INACTIVO  = 'inactivo';
    public const ESTADO_REABIERTO = 'reabierto';

    /**
     * Scope: ventanas vigentes (no expiradas).
     * Opcionalmente filtra por tipo (salida / recojo).
     */
    public function scopeVigentes($query, ?string $tipo = null)
    {
        $query->whereIn('estado', [self::ESTADO_ACTIVO, self::ESTADO_REABIERTO]);

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        return $query;
    }

    // ---------------- Relaciones ----------------

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Accessor: true si la ventana está activa (estado activo o reabierto).
     */
    public function getEstaActivaAttribute(): bool
    {
        return in_array($this->estado, [self::ESTADO_ACTIVO, self::ESTADO_REABIERTO], true);
    }
}
