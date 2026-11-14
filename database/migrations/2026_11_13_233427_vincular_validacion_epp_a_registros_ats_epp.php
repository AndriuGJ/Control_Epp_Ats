<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registros_ats_epp', function (Blueprint $table) {
            $table->foreignId('id_validacion_epp')->nullable()->unique()->constrained('validaciones_epp', 'id_validacion_epp')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registros_ats_epp', function (Blueprint $table) {
            $table->dropForeign(['id_validacion_epp']);
            $table->dropUnique(['id_validacion_epp']);
            $table->dropColumn('id_validacion_epp');
        });
    }
};
