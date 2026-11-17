<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_empresa', 120)->default('Macro');
            $table->string('area', 100)->default('Logística');
            $table->string('logo')->nullable();
            $table->boolean('avisos_epp')->default(false);
            $table->string('servidor_smtp')->default('smtp.gmail.com');
            $table->unsignedSmallInteger('puerto_smtp')->default(465);
            $table->string('usuario_smtp')->nullable();
            $table->text('contrasena_smtp')->nullable();
            $table->timestamps();
        });

        DB::table('configuracion')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
