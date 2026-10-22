# Control de EPP y ATS

Sistema web para el área de Logística de Makro, desarrollado desde TID. Los datos del proceso se organizan en español y conservan la separación entre operador y supervisor revisor.

## Funciones disponibles

- Acceso por correo y contraseña, cierre de sesión, cuentas activas y límite de intentos.
- Roles de administrador, supervisor y operador mediante Spatie Laravel Permission.
- Registro y edición de usuarios y máquinas, con activación y desactivación.
- Carga opcional y reemplazo de fotografías de máquinas, con miniaturas en el catálogo.
- Acceso a la vista inicial de Reportes para administrador y supervisor; su generación está pendiente.
- Asignación y retiro de máquinas autorizadas por operador.
- Panel con registros del usuario y máquinas activas autorizadas.
- Administración de preguntas ATS por etapa (Inicial/Final), posición y estado.
- Registro inicial con máquina autorizada, captura de cámara del EPP, respuestas Sí/No y firma dibujada.
- Cierre del mismo registro con respuestas finales Sí/No y firma final, pasando a estado Finalizado.
- Consulta del registro guardado; cada operador ve sus registros y el supervisor puede consultarlos.
- Migraciones, modelos y relaciones de `users`, `maquinas`, `usuarios_maquinas`, `registros_ats_epp`, `preguntas_ats` y `respuestas_ats`.

El registro inicial queda en estado `En curso`; al responder el ATS final y firmar pasa a `Finalizado`. La revisión del supervisor, los filtros de búsqueda y la generación de reportes PDF se desarrollarán en las siguientes etapas.

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

## Migraciones

Cada tabla del proceso tiene su propio archivo de migración: `maquinas`, `usuarios_maquinas`, `preguntas_ats`, `registros_ats_epp` y `respuestas_ats`. Los trabajadores utilizan únicamente la tabla `users` de Laravel, adaptada con sus datos en español y su clave primaria `id`. No se crea una tabla `usuarios`. Los modelos utilizan `created_at` y `updated_at`; la tabla intermedia de autorizaciones también los registra.

La migración original de Laravel conserva sus tablas técnicas y la migración publicada de Spatie conserva la estructura del paquete.

Esta reorganización modifica las migraciones iniciales de una aplicación en desarrollo. Si ya ejecutaste la versión anterior, `php artisan migrate` no convierte esa base automáticamente. Para una base local que contenga únicamente datos de prueba que puedas descartar, puedes reconstruirla con `php artisan migrate:fresh --seed` y volver a ejecutar `php artisan app:crear-administrador`. El primer comando elimina todas las tablas y sus datos; no debe ejecutarse sobre una base con información que necesites conservar.

## Uso de esta entrega

1. Ingresar con el administrador creado por consola.
2. Registrar las máquinas en el catálogo.
3. Crear usuarios, asignar un rol y marcar las máquinas autorizadas si son operadores.
4. En **Preguntas del ATS**, cargar las preguntas del formato real de la empresa, indicando etapa, posición y estado. No se precargan preguntas de seguridad de ejemplo.
5. Ingresar como operador y abrir **Iniciar registro ATS/EPP**. Seleccionar la máquina, abrir la cámara, tomar la foto usando EPP, marcar Sí o No en las preguntas iniciales y dibujar la firma.
6. Guardar para consultar el registro en estado `En curso`, junto con la fotografía, las respuestas y la firma. Al terminar la labor, volver al mismo registro y elegir **Continuar y finalizar labor**. Marcar las preguntas finales y firmar para dejarlo `Finalizado`. Solo su propietario puede finalizarlo y el cierre no se repite.

Las fotografías de máquinas admiten JPG, PNG y WEBP de hasta 4 MB. Se guardan en almacenamiento privado y se muestran mediante una ruta protegida con el permiso de gestión de máquinas; no requieren `storage:link`. Para admitir archivos de 4 MB, PHP debe tener `upload_max_filesize` de al menos `4M` y `post_max_size` de al menos `8M`. Al editar sin elegir una imagen, se conserva la anterior; al reemplazarla se elimina el archivo anterior después de guardar correctamente.

Las cuentas y máquinas se desactivan para mantener el historial. Las fotografías de EPP se guardan en `storage/app/private/registros/epp` y las firmas iniciales en `storage/app/private/registros/firmas`; la base de datos conserva sus rutas. Las imágenes solo se entregan mediante rutas protegidas. La fecha de la foto se toma del servidor al recibir la captura de cámara, y el inicio al guardar el ATS; no se verifica automáticamente el uso correcto del EPP. La tabla de autorizaciones evita duplicados y las respuestas tienen una restricción única por registro y pregunta.

Las tablas de soporte de Spatie están configuradas como `roles`, `permisos`, `usuarios_roles`, `usuarios_permisos` y `roles_permisos`. Los campos internos exigidos por ese paquete mantienen sus nombres originales para asegurar su compatibilidad. Las tablas técnicas de sesión, caché y cola que incluye Laravel también son de soporte; el entorno de ejemplo utiliza sesiones y caché en archivos y ejecución de cola síncrona.

## Actualizar esta versión

```bash
git pull
php artisan db:seed --class=RolesPermisosSeeder
npm run build
php artisan view:clear
```

El seeder incorpora el permiso de gestión de preguntas al administrador. Esta etapa utiliza las tablas ya existentes y no requiere reconstruir la base. Después, el administrador debe registrar las preguntas iniciales para habilitar el formulario.

## Cámara del EPP

El formulario ATS utiliza `getUserMedia` y muestra una vista en vivo, **Tomar foto** y **Volver a tomar**. No presenta un selector de archivos ni una opción de galería. Los formularios inicial y final usan opciones excluyentes Sí/No; no permiten respuestas escritas.

Para probar desde un celular se necesita una URL HTTPS con certificado válido y permiso de cámara. Una dirección HTTP con IP de la red local no permite esta API; en la computadora puede probarse desde localhost. No se solicita acceso al micrófono.

Al tomar la foto, se registra inmediatamente su fecha y hora en el servidor y se muestra sobre la vista previa y en el detalle guardado, en horario de Perú. Esa fecha se almacena separadamente de la imagen; el archivo original no lleva una marca de agua incrustada. La captura queda vinculada a la sesión y al trabajador y vence a los 30 minutos; al iniciar el ATS se consume y se elimina su copia temporal.

Las capturas pendientes se guardan en `storage/app/private/registros/capturas`. El programador de Laravel elimina las abandonadas que superen una hora; durante el desarrollo puede ejecutarse `php artisan schedule:work`, y en el servidor debe configurarse `schedule:run` cada minuto. Las fotos y firmas definitivas conservan sus carpetas privadas.

La interfaz ofrece únicamente cámara, pero una web no certifica el origen físico del dispositivo ni detecta una cámara virtual o un cliente HTTP manipulado. La comprobación del EPP continúa siendo manual por el supervisor.

## Lógica de negocio

`app/Services/RegistroAtsService.php` comprueba autorización de la máquina, una sola labor en curso por operador, preguntas completas con respuestas Sí/No, firma PNG válida y guardado conjunto de registros/respuestas. El cierre verifica propiedad y estado, conserva el inicio y guarda respuestas finales, firma y fecha en una sola transacción. Si falla el guardado, revierte la transacción y elimina los archivos nuevos. Los controladores delegan estas operaciones al servicio; los Form Requests validan el formato de las entradas.

`app/Services/PreguntaAtsService.php` protege el texto y la etapa de preguntas ya respondidas. Pueden desactivarse o cambiarse de posición, pero para sustituir su contenido se registra otra pregunta. Si las preguntas cambian mientras un operador completa el formulario, se solicita recargar antes de guardar.

## Verificación

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

Las pruebas usan una base SQLite en memoria y no modifican la base configurada para el uso normal de la aplicación. El flujo de integración continua también verifica la instalación de las migraciones y los roles en MySQL.
