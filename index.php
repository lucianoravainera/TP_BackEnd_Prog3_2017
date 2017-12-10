<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

//APACHE_REQUEST_HEADERS();

require './composer/vendor/autoload.php';
require_once './php/clases/AccesoDatos.php';
require_once './php/clases/usuariosApi.php';
//require_once './php/clases/operacionesAPI.php';
require_once './php/clases/AutentificadorJWT.php';
require_once './php/clases/MWparaCORS.php';
require_once './php/clases/MWparaAutentificar.php';
require_once './php/clases/usuario.php';
//require_once './php/clases/operacion.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = new \Slim\App(["settings" => $config]);

// ruta por defecto, no hay autenticacion
$app->get('[/]', function(){
    echo "Bienvenido TP Estacionamiento(get)";
});

// ruta de login, en caso de existir el usuario, devuelve token 
$app->post('/login', \usuariosApi::class . ':Login')->add(\MWparaCORS::class . ':HabilitarCORSTodos');

// rutas de usuarios, ABM
$app->group('/usuarios', function () {
    $this->get('[/]', \usuariosApi::class . ':traerTodos');  // traer todos los usuarios
    $this->post('[/]', \usuariosApi::class . ':altaUsuario'); // Dar de alta un nuevo usuario
    $this->delete('[/]', \usuariosApi::class . ':borrarUsuario'); // Eliminar un usuario, baja física (id constraint en db, no usar)
    $this->put('[/]', \usuariosApi::class . ':suspenderUsuario'); // Suspender un usuario, no puede loguear, baja lógica
    $this->group('/ingresos', function() {              // Grupo de ingresos al sistema, se registra solo si está autentificado
        //$this->get('/cantidad/getpdf[/]', \usuariosApi::class . ':generarPDF'); // Devuelve de todos los ingresos por usuario en pdf
        $this->get('/cantidad[/]', \operacionesAPI::class . ':cantidadOperaciones'); // Devuelve cantidad de operaciones por usuario
        $this->post('/getpdf[/]', \usuariosApi::class . ':generarPDF'); // Devuelve informe de todos los ingresos en PDF
        $this->post('/getexcel[/]', \usuariosApi::class . ':generarExcel'); // Devuelve informe de todos los ingresos en Excel
        $this->get('/{id}', \usuariosApi::class . ':ingresos'); // Devuelve todos los ingresos del ID pasado
        $this->post('[/]', \usuariosApi::class . ':ingresos'); // Devuelve todos los ingresos
    });
})->add(\usuariosApi::class . ':esAdmin')->add(\MWparaAutentificar::class . ':VerificarUsuario')->add(\MWparaCORS::class . ':HabilitarCORSTodos');

// rutas de operaciones y cocheras, ABM
$app->group('/operaciones', function () {
    $this->get('[/]', \operacionesAPI::class . ':traerTodas');  // Trae todas las operaciones
    $this->post('/getpdf[/]', \operacionesAPI::class . ':generarPDF'); // Devuelve un archivo de todas las operaciones en PDF
    $this->post('[/]', \operacionesAPI::class . ':altaOperacion'); // Da de alta una nueva operacion
    $this->delete('[/]', \operacionesAPI::class . ':eliminarOperacion'); // Elimina una operacion por ID
    $this->put('[/]', \operacionesAPI::class . ':bajaOperacion'); // Operacion finalizada, egreso de vehiculo
    $this->group('/cocheras', function() {
        $this->post('/estadisticas[/]', \operacionesAPI::class . ':estadisticas'); // Devuelve todas las estadisticas de uso de las cocheras
        //$this->get('/estadisticas/getpdf[/]', \operacionesAPI::class . ':generarPDF'); // Devuelve un archivo de todas las operaciones en PDF
        $this->get('/{id}', \operacionesAPI::class . ':estaOcupada');  // Devuelve si la cochera esta ocupada o no
        $this->get('[/]', \operacionesAPI::class . ':TraerEstacionados');    // Devuelve todas las cocheras ocupadas
    });
})->add(\MWparaAutentificar::class . ':VerificarUsuario')->add(\MWparaCORS::class . ':HabilitarCORSTodos');

$app->run();




























//
?>