<?php

return [
    'db' => [
        'host'    => 'localhost',
        'puerto'  => 3306,
        'nombre'  => 'galeria_mvc',
        'usuario' => 'root',
        'clave'   => '',
    ],

    'app' => [
        'nombre' => 'Mi Galería',
        'depurar' => false,
        'minutos_sesion' => 60,
    ],

    'subidas' => [
        'mb_por_archivo'     => 5,
        'archivos_por_envio' => 10,
        'mb_por_usuario'     => 50,
    ],
];
