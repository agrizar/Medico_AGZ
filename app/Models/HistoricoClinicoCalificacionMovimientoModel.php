<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoClinicoCalificacionMovimientoModel extends Model
{
    protected $table = 'historico_clinico_calificaciones_movimientos';

    protected $fillable = [
        'historico_clinico_id',
        'calificacion_anterior',
        'calificacion_nueva',
        'usuario_id',
        'fecha_movimiento'
    ];
}