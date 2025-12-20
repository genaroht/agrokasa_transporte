<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'horarios';

    protected $fillable = [
        'nombre',       // ej. "Turno día 07:30 a 16:30"
        'tipo',         // SALIDA | RECOJO
        'hora',         // TIME inicio, ej. "07:30:00"
        'hora_fin',     // TIME fin, ej. "16:30:00"
        'sucursal_id',  // null = global, o por sucursal
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function programaciones()
    {
        return $this->hasMany(Programacion::class);
    }

    /**
     * Hora inicio formateada HH:ii.
     */
    public function getHoraFormateadaAttribute(): ?string
    {
        if (!$this->hora) {
            return null;
        }

        try {
            return Carbon::parse($this->hora)->format('H:i');
        } catch (\Throwable $e) {
            return $this->hora;
        }
    }

    /**
     * Hora fin formateada HH:ii.
     */
    public function getHoraFinFormateadaAttribute(): ?string
    {
        if (!$this->hora_fin) {
            return null;
        }

        try {
            return Carbon::parse($this->hora_fin)->format('H:i');
        } catch (\Throwable $e) {
            return $this->hora_fin;
        }
    }

    /**
     * Texto de rango:
     * - "DE 06:30 a 15:30" si tiene inicio y fin
     * - "06:30" si solo tiene inicio
     */
    public function getDescripcionRangoAttribute(): string
    {
        $inicio = $this->hora_formateada;
        $fin    = $this->hora_fin_formateada;

        if ($inicio && $fin) {
            return "DE {$inicio} a {$fin}";
        }

        return $inicio ?? '';
    }

    /**
     * Etiqueta tipo humano: "Salida" / "Recojo".
     */
    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'SALIDA' ? 'Salida' : 'Recojo';
    }

    /**
     * Etiqueta completa para combos:
     * - Si tiene nombre y rango: "Turno día (DE 07:30 a 16:30)"
     * - Si tiene nombre solo: "Turno día"
     * - Si no tiene nombre: "DE 07:30 a 16:30" o "07:30"
     */
    public function getEtiquetaCompletaAttribute(): string
    {
        $rango = $this->descripcion_rango;

        if ($this->nombre && $rango) {
            return "{$this->nombre} ({$rango})";
        }

        if ($this->nombre) {
            return $this->nombre;
        }

        return $rango ?: '';
    }
}
