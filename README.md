# Control de EPP y ATS

Sistema web para el área de Logística de Makro, desarrollado desde TID. Los datos del proceso se organizan en español y conservan la separación entre operador y supervisor revisor.

## Primera entrega

- Acceso por correo y contraseña, cierre de sesión, cuentas activas y límite de intentos.
- Roles de administrador, supervisor y operador mediante Spatie Laravel Permission.
- Registro y edición de usuarios y máquinas, con activación y desactivación.
- Asignación y retiro de máquinas autorizadas por operador.
- Panel inicial con registros del usuario y máquinas activas autorizadas.
- Migraciones, modelos y relaciones de `usuarios`, `maquinas`, `usuarios_maquinas`, `registros_ats_epp`, `preguntas_ats` y `respuestas_ats`.

Esta entrega prepara los datos para las etapas inicial y final del ATS y su revisión. La captura de fotografía y firmas, el llenado del ATS, la revisión y los reportes PDF todavía no tienen pantallas ni acciones de registro.

## Requisitos

PHP 8.5 con las extensiones habituales de Laravel y PDO MySQL, Composer, Node.js 22.12 o superior y MySQL 8.0 o superior. Para ejecutar las pruebas también se necesita PDO SQLite. Las versiones exactas de los paquetes se encuentran en los archivos de bloqueo de Composer y npm.

## Instalación local

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Crear una base de datos MySQL llamada `control_epp_ats`. Configurar en `.env` el servidor, puerto, nombre de la base, usuario y contraseña propios:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=control_epp_ats
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contrasena
```

Después:

```bash
php artisan migrate --seed
php artisan app:crear-administrador
npm run build
php artisan serve
```

El comando solicita los datos y una contraseña elegida por el responsable, sin crear credenciales predeterminadas. Para trabajar los estilos durante el desarrollo, ejecutar `npm run dev` en otra terminal en lugar de compilar después de cada cambio. El punto de entrada de acceso es `/ingresar`.

## Uso de esta entrega

1. Ingresar con el administrador creado por consola.
2. Registrar las máquinas en el catálogo.
3. Crear usuarios, asignar un rol y marcar las máquinas autorizadas si son operadores.
4. Ingresar con un operador y comprobar sus equipos disponibles en el panel.

Las cuentas y máquinas se desactivan para mantener el historial. Las fotografías y firmas del futuro flujo ATS se conservarán como archivos privados; la base de datos almacenará las rutas. La tabla de autorizaciones evita duplicados y las respuestas tienen una restricción única por registro y pregunta.

Las tablas de soporte de Spatie están configuradas como `roles`, `permisos`, `usuarios_roles`, `usuarios_permisos` y `roles_permisos`. Los campos internos exigidos por ese paquete mantienen sus nombres originales para asegurar su compatibilidad. Las tablas técnicas de sesión, caché y cola que incluye Laravel también son de soporte; el entorno de ejemplo utiliza sesiones y caché en archivos y ejecución de cola síncrona.

## Verificación

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

Las pruebas usan una base SQLite en memoria y no modifican la base configurada para el uso normal de la aplicación. El flujo de integración continua también verifica la instalación de las migraciones y los roles en MySQL.
