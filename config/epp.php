<?php

return [
    'minutos_revision' => 10,
    'minutos_recordatorio' => 5,
    'servidores_smtp' => array_map('trim', explode(',', (string) env('EPP_SERVIDORES_SMTP', 'smtp.gmail.com'))),
    'minutos_aprobacion' => 15,
];
