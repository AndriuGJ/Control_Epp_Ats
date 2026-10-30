<?php

namespace Database\Seeders;

use App\Models\PreguntaAts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreguntasAtsSeeder extends Seeder
{
    public function run(): void
    {
        $preguntasPorEtapa = [
            'Inicial' => [
                '¿Cuentas con autorización para utilizar la máquina seleccionada?',
                '¿Conoces la tarea que vas a realizar y el procedimiento de trabajo seguro?',
                '¿Has identificado los peligros y riesgos de la tarea?',
                '¿Llevas correctamente colocado el EPP requerido para esta labor?',
                '¿El EPP que utilizarás se encuentra en buen estado?',
                '¿Realizaste la inspección previa de la máquina según el procedimiento establecido?',
                '¿La máquina se encuentra en condiciones adecuadas para iniciar la labor?',
                '¿El área de trabajo y las vías de circulación están libres de obstáculos?',
                '¿Se han establecido las medidas de control necesarias para realizar la tarea?',
                '¿Conoces cómo comunicar una emergencia y a quién avisar?',
            ],
            'Final' => [
                '¿Has concluido la tarea asignada?',
                '¿Dejaste la máquina en la condición de cierre indicada en el procedimiento de trabajo?',
                '¿La máquina quedó ubicada en el lugar autorizado?',
                '¿El área de trabajo quedó limpia y ordenada?',
                '¿Las vías de circulación quedaron libres de materiales y obstáculos?',
                '¿Las herramientas y materiales utilizados quedaron guardados en sus lugares correspondientes?',
                '¿El EPP utilizado quedó guardado o dispuesto según el procedimiento establecido?',
                '¿Se retiraron o dispusieron correctamente los residuos generados durante la labor?',
                '¿La labor terminó sin incidentes ni accidentes?',
                '¿Comunicaste al supervisor el resultado de la labor y cualquier novedad?',
            ],
        ];

        DB::transaction(function () use ($preguntasPorEtapa): void {
            foreach ($preguntasPorEtapa as $etapa => $preguntas) {
                $posicion = (int) PreguntaAts::query()->where('etapa', $etapa)->max('posicion');
                foreach ($preguntas as $texto) {
                    $pregunta = PreguntaAts::query()->firstOrCreate(
                        ['etapa' => $etapa, 'pregunta' => $texto],
                        ['posicion' => $posicion + 1, 'estado' => true],
                    );
                    if ($pregunta->wasRecentlyCreated) {
                        $posicion++;
                    }
                }
            }
        });
    }
}
