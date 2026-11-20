<?php

return [
    'GET '              => ['InicioController', 'index'],

    'GET login'         => ['AuthController', 'loginForm'],
    'POST login'        => ['AuthController', 'login'],
    'GET registro'      => ['AuthController', 'registroForm'],
    'POST registro'     => ['AuthController', 'registro'],
    'POST logout'       => ['AuthController', 'logout'],

    'GET cuenta'        => ['CuentaController', 'index'],
    'POST cuenta'       => ['CuentaController', 'guardar'],

    'GET galeria'           => ['GaleriaController', 'index'],
    'GET imagen'            => ['GaleriaController', 'archivo'],
    'GET api/imagenes'      => ['GaleriaController', 'listar'],
    'POST api/subir'        => ['GaleriaController', 'subir'],
    'POST api/renombrar'    => ['GaleriaController', 'renombrar'],
    'POST api/borrar'       => ['GaleriaController', 'borrar'],

    'GET admin'             => ['AdminController', 'usuarios'],
    'GET admin/editar'      => ['AdminController', 'editarForm'],
    'POST admin/editar'     => ['AdminController', 'editar'],
    'POST admin/borrar'     => ['AdminController', 'borrar'],
    'GET admin/galeria'     => ['AdminController', 'galeria'],
];
