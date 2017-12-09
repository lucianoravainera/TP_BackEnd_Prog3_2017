<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

//APACHE_REQUEST_HEADERS();

require './composer/vendor/autoload.php';
require_once './php/clases/AccesoDatos.php';
require_once './php/clases/empleadosApi.php';
require_once './php/clases/productosAPI.php';
require_once './php/clases/AutentificadorJWT.php';
require_once './php/clases/MWparaCORS.php';
require_once './php/clases/MWparaAutentificar.php';
require_once './php/clases/usuario.php';
require_once './php/clases/producto.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = new \Slim\App(["settings" => $config]);

// ruta por defecto, no hay autenticacion
$app->get('/', function(){
    echo "Bienvenido";
});

// ruta de login, en caso de existir el usuario, devuelve token (JSON) {'token': 'abcdef1234567890'}
$app->post('/login', \empleadosApi::class . ':Login')->add(\MWparaCORS::class . ':HabilitarCORSTodos');

// rutas de usuarios, ABM
$app->group('/empleados', function () {
    //$this->get('/{id}', \empleadosApi::class . ':traerUno');
    $this->get('[/]', \empleadosApi::class . ':traerTodos');  // traer todos los usuarios que no sean administradores
    $this->post('[/]', \empleadosApi::class . ':altaEmpleado'); // Dar de alta un nuevo usuario
    $this->delete('/', \empleadosApi::class . ':borrarUsuario'); // Eliminar un usuario, baja física
    $this->put('/', \empleadosApi::class . ':suspenderUsuario'); // Suspender un usuario, no puede loguear, baja lógica
    $this->group('/ingresos', function() {
        $this->get('/{id}', \empleadosApi::class . ':ingresos'); // Devuelve todos los ingresos del ID pasado
        $this->get('[/]', \empleadosApi::class . ':ingresos'); // Devuelve todos los ingresos
    });
})->add(\empleadosApi::class . ':esAdmin')->add(\MWparaAutentificar::class . ':VerificarUsuario')->add(\MWparaCORS::class . ':HabilitarCORSTodos');

$app->group('/productos', function () {
    $this->post('[/]', \productosAPI::class . ':altaProducto')->add(\empleadosApi::class . ':esAdmin');
    $this->get('[/]', \productosAPI::class . ':traerTodos');
    $this->delete('[/]', \productosAPI::class . ':borrarProducto')->add(\empleadosApi::class . ':esAdmin');
    $this->put('[/]', \productosAPI::class . ':modificarProducto')->add(\empleadosApi::class . ':esAdmin');
})->add(\MWparaAutentificar::class . ':contadorLogin')->add(\MWparaAutentificar::class . ':VerificarUsuario')->add(\MWparaCORS::class . ':HabilitarCORSTodos');

$app->run();




























//
?>