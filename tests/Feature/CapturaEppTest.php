<?php

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use App\Services\CapturaEppService;
use App\Services\RegistroAtsService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function fotoCapturaEppPrueba(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('captura.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
}

it('recibe la captura con fecha del servidor y sustituye la captura pendiente anterior', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $this->seed(RolesPermisosSeeder::class);
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $primera = $this->actingAs($operador)->post(route('registros.captura'), ['fotografia' => fotoCapturaEppPrueba(), 'fecha' => '2000-01-01'], ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('fecha_hora', now()->format('d/m/Y H:i:s'))->assertJsonMissingPath('ruta');
    $anterior = session('ats.captura');
    Storage::disk('local')->assertExists($anterior['ruta']);
    $this->travel(1)->minutes();
    $segunda = $this->post(route('registros.captura'), ['fotografia' => fotoCapturaEppPrueba()], ['Accept' => 'application/json'])->assertOk();
    expect($segunda->json('captura'))->not->toBe($primera->json('captura'));
    Storage::disk('local')->assertMissing($anterior['ruta']);
    expect(Storage::disk('local')->allFiles('registros/capturas'))->toHaveCount(1);
    $this->assertDatabaseCount('registros_ats_epp', 0);
});

it('conserva la hora de la captura aunque el ATS se guarde minutos después y consume la captura', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $this->seed(RolesPermisosSeeder::class);
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create();
    $token = $this->actingAs($operador)->post(route('registros.captura'), ['fotografia' => fotoCapturaEppPrueba()], ['Accept' => 'application/json'])->assertOk()->json('captura');
    $horaCaptura = now()->copy();
    $this->travel(5)->minutes();
    $datos = ['id_maquina' => $maquina->getKey(), 'captura_epp' => $token, 'firma_inicial' => firmaAtsPrueba(), 'respuestas' => [$pregunta->getKey() => 'Sí'], 'version_preguntas' => app(RegistroAtsService::class)->prepararInicio($operador)['versionPreguntas']];
    $this->post(route('registros.store'), $datos)->assertSessionHasNoErrors()->assertRedirect();
    $registro = RegistroAtsEpp::query()->sole();
    expect($registro->fecha_fotografia->toDateTimeString())->toBe($horaCaptura->toDateTimeString());
    expect($registro->fecha_inicio->toDateTimeString())->toBe(now()->toDateTimeString());
    expect(session('ats.captura'))->toBeNull();
    expect(Storage::disk('local')->allFiles('registros/capturas'))->toBeEmpty();
    $this->get(route('registros.show', $registro))->assertOk()->assertSee($horaCaptura->format('d/m/Y H:i:s'));
});

it('rechaza capturas sin sesión correspondiente de otro usuario o vencidas', function (string $caso): void {
    Storage::fake('local');
    $this->freezeTime();
    $this->seed(RolesPermisosSeeder::class);
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create();
    $captura = app(CapturaEppService::class)->tomar($operador, fotoCapturaEppPrueba());
    if ($caso === 'otro usuario') {
        $captura['id_usuario'] = User::factory()->create()->getKey();
    }
    if ($caso !== 'sin sesión') {
        $this->withSession(['ats.captura' => $captura]);
    }
    if ($caso === 'vencida') {
        $this->travel(31)->minutes();
    }
    $datos = ['id_maquina' => $maquina->getKey(), 'captura_epp' => $captura['token'], 'firma_inicial' => firmaAtsPrueba(), 'respuestas' => [$pregunta->getKey() => 'Sí'], 'version_preguntas' => app(RegistroAtsService::class)->prepararInicio($operador)['versionPreguntas']];
    $this->actingAs($operador)->post(route('registros.store'), $datos)->assertSessionHasErrors('captura_epp');
    $this->assertDatabaseCount('registros_ats_epp', 0);
    expect(Storage::disk('local')->allFiles('registros/epp'))->toBeEmpty();
})->with(['sin sesión', 'otro usuario', 'vencida']);

it('no permite adjuntar una imagen directamente al formulario de inicio ni respuestas libres', function (): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create();
    $captura = app(CapturaEppService::class)->tomar($operador, fotoCapturaEppPrueba());
    $this->withSession(['ats.captura' => $captura]);
    $datos = ['id_maquina' => $maquina->getKey(), 'captura_epp' => $captura['token'], 'firma_inicial' => firmaAtsPrueba(), 'fotografia_epp' => fotoCapturaEppPrueba(), 'respuestas' => [$pregunta->getKey() => 'Texto libre'], 'version_preguntas' => app(RegistroAtsService::class)->prepararInicio($operador)['versionPreguntas']];
    $this->actingAs($operador)->post(route('registros.store'), $datos)->assertSessionHasErrors(['fotografia_epp', 'respuestas.'.$pregunta->getKey()]);
    $this->assertDatabaseCount('registros_ats_epp', 0);
});

it('protege la captura y rechaza archivos inválidos', function (): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $this->post(route('registros.captura'), [])->assertRedirect(route('login'));
    $this->actingAs($supervisor)->post(route('registros.captura'), ['fotografia' => fotoCapturaEppPrueba()])->assertForbidden();
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $this->actingAs($operador)->post(route('registros.captura'), ['fotografia' => UploadedFile::fake()->create('archivo.txt', 1, 'text/plain')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('fotografia');
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('elimina capturas temporales abandonadas sin borrar archivos definitivos ni capturas recientes', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('registros/capturas/antigua.jpg', 'antigua');
    Storage::disk('local')->put('registros/capturas/reciente.jpg', 'reciente');
    Storage::disk('local')->put('registros/epp/definitiva.jpg', 'definitiva');
    touch(Storage::disk('local')->path('registros/capturas/antigua.jpg'), now()->subHours(2)->timestamp);
    expect(app(CapturaEppService::class)->limpiarVencidas())->toBe(1);
    Storage::disk('local')->assertMissing('registros/capturas/antigua.jpg');
    Storage::disk('local')->assertExists(['registros/capturas/reciente.jpg', 'registros/epp/definitiva.jpg']);
});
