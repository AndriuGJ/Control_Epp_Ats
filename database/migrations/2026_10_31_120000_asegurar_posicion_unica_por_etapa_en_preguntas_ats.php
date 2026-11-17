<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['Inicial', 'Final'] as $etapa) {
                $preguntas = DB::table('preguntas_ats')->where('etapa', $etapa)->orderBy('posicion')->orderBy('id_pregunta')->lockForUpdate()->get();
                $ultima = (int) $preguntas->max('posicion');
                $ocupadas = [];
                foreach ($preguntas as $pregunta) {
                    if (isset($ocupadas[$pregunta->posicion])) {
                        DB::table('preguntas_ats')->where('id_pregunta', $pregunta->id_pregunta)->update(['posicion' => ++$ultima]);
                    }
                    $ocupadas[$pregunta->posicion] = true;
                }
            }
        });

        Schema::table('preguntas_ats', function (Blueprint $table): void {
            $table->unique(['etapa', 'posicion'], 'preguntas_ats_etapa_posicion_unique');
        });
    }

    public function down(): void
    {
        Schema::table('preguntas_ats', function (Blueprint $table): void {
            $table->dropUnique('preguntas_ats_etapa_posicion_unique');
        });
    }
};
