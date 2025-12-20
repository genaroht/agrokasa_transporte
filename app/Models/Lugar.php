<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lugar extends Model
{
    use HasFactory;

    protected $table = 'lugares';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function paraderos()
    {
        return $this->hasMany(Paradero::class);
    }
}
