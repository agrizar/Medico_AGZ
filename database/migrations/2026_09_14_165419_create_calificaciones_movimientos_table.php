<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_clinico_calificaciones_movimientos', function (Blueprint $table) {
            $table->increments('id')->comment('ID único de la tabla de movimientos');
            $table->unsignedBigInteger('historico_clinico_id')->comment('ID del historico clinico relacionado');
            $table->text('calificacion_anterior')->nullable()->comment('Calificacion anterior del historico');
            $table->text('calificacion_nueva')->nullable()->comment('Calificacion nueva del historico');
            $table->unsignedBigInteger('usuario_id')->comment('Usuario id relacionado que realiza el movimiento');
            $table->tinyInteger('borrado')->default('0')->comment('Borrado lógico 1=>Si 0=>No');
            $table->timestamps();

            $table->foreign('historico_clinico_id')->references('id')->on('historico_clinico');
            $table->foreign('usuario_id')->references('id')->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_clinico_calificaciones_movimientos');
    }
};