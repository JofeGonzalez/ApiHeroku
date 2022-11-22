<?php

// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

#region use
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
#endregion

#region require
require __DIR__ . '/../vendor/autoload.php';
require_once './db/AccesoDatos.php';
require_once './globals.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/EmpleadoController.php';
require_once './controllers/MesaController.php';
require_once './controllers/PlatoController.php';
require_once './controllers/BebidaController.php';
require_once './controllers/CervezaController.php';
require_once './controllers/PostreController.php';
require_once './controllers/TragoController.php';
require_once './controllers/VinoController.php';
require_once './controllers/EncuestaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/AutentificadorController.php';

require_once './middlewares/AutentificadorJWT.php';
#endregion

#region initialize

//TIMEZONE
date_default_timezone_set(TIMEZONE);

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();
//$app->setBasePath('/app');

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

//
$app->addRoutingMiddleware();

// Eloquent
$container=$app->getContainer();
iniciarCapsula();

#endregion

#region routes
// Routes
$app->group('/api/usuarios/regenerarToken', function (RouteCollectorProxy $group) {
  $group->get('[/]', AutentificadorController::class . ':regenerarToken');
});

$app->group('/api/usuarios/crearNuevoToken', function (RouteCollectorProxy $group) {
  $group->get('[/]', AutentificadorController::class . ':crearNuevoToken');
});

$app->group('/api/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', UsuarioController::class . ':traerTodos')->add(AutentificadorController::class . ':verificarSocio');
    $group->get('/{id}', UsuarioController::class . ':traerUno')->add(AutentificadorController::class . ':verificarSocio');
    $group->get('/extendido/{id}', UsuarioController::class . ':traerUnoConEmpleado')->add(AutentificadorController::class . ':verificarSocio');
    $group->post('[/]', UsuarioController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarAdmin');
    $group->put('/modificar', UsuarioController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarUsuario');
    $group->put('/desactivar', UsuarioController::class . ':desactivarUno')->add(AutentificadorController::class . ':verificarSocio');
    $group->put('/reactivar', UsuarioController::class . ':reactivarUno')->add(AutentificadorController::class . ':verificarSocio');
    $group->delete('/borrar', UsuarioController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
  })
  ->add(AutentificadorController::class . ':verificarCredenciales');
;


// empleados
$app->group('/api/empleados', function (RouteCollectorProxy $group) {
  $group->get('[/]', EmpleadoController::class . ':traerTodos')->add(AutentificadorController::class . ':verificarSocio');
  $group->get('/{id}', EmpleadoController::class . ':traerUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->get('/extendido/{id}', EmpleadoController::class . ':traerUnoConUsuarios')->add(AutentificadorController::class . ':verificarSocio');
  $group->post('[/]', EmpleadoController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarAdmin');
  $group->put('/modificar', EmpleadoController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->put('/desactivar', EmpleadoController::class . ':desactivarUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->put('/reactivar', EmpleadoController::class . ':reactivarUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->delete('/borrar', EmpleadoController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})
->add(AutentificadorController::class . ':verificarCredenciales');

$app->group('/api/empleados/informes', function (RouteCollectorProxy $group) {
  $group->get('/fechasIngreso', EmpleadoController::class . ':traerFechasAlta');
  $group->get('/operaciones/sector', EmpleadoController::class . ':operacionesPorSector'); 
  $group->get('/operaciones/sector/empleados', EmpleadoController::class . ':operacionesPorEmpelado'); 
  $group->get('/operaciones/{id}', EmpleadoController::class . ':operacionesDeUno'); 
})
->add(AutentificadorController::class . ':verificarSocio')
->add(AutentificadorController::class . ':verificarCredenciales');


//mesas
$app->group('/api/mesas', function (RouteCollectorProxy $group) {
  $group->get('[/]', MesaController::class . ':traerTodos')->add(AutentificadorController::class . ':verificarMozo');
  $group->get('/{id}', MesaController::class . ':traerUno')->add(AutentificadorController::class . ':verificarMozo');
  $group->post('[/]', MesaController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->put('/modificar', MesaController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->delete('/borrar', MesaController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarSocio');
})->add(AutentificadorController::class . ':verificarCredenciales');

$app->group('/api/mesas/informes', function (RouteCollectorProxy $group) {
  $group->get('/masUsada', MesaController::class . ':traerMasUsada');
  $group->get('/menosUsada', MesaController::class . ':traerMenosUsada'); 
  $group->get('/mayorFacturacion', MesaController::class . ':traerMayorFacturacion'); 
  $group->get('/menorFacturacion', MesaController::class . ':traerMenorFacturacion'); 
  $group->get('/mayorImporte', MesaController::class . ':traerMayorImporte');
  $group->get('/menorImporte', MesaController::class . ':traerMenorImporte'); 
  $group->get('/facturacionPorFechas/{id}', MesaController::class . ':traerFacturacionUna'); 
  $group->get('/facturacionPorFechas', MesaController::class . ':traerFacturacionTodas'); 
  $group->get('/mejoresComentarios', MesaController::class . ':traerMejoresComentarios'); 
  $group->get('/peoresComentarios', MesaController::class . ':traerPeoresComentarios'); 
})
->add(AutentificadorController::class . ':verificarSocio')
->add(AutentificadorController::class . ':verificarCredenciales');

//platos
$app->group('/api/platos', function (RouteCollectorProxy $group) {
  $group->get('[/]', PlatoController::class . ':traerTodos');
  $group->get('/{id}', PlatoController::class . ':traerUno');
  $group->post('[/]', PlatoController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio');
  $group->put('/modificar', PlatoController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarCocinero');
  $group->delete('/borrar', PlatoController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');

//bebidas
$app->group('/api/bebidas', function (RouteCollectorProxy $group) {
  $group->get('[/]', BebidaController::class . ':traerTodos');
  $group->get('/{id}', BebidaController::class . ':traerUno'); 
  $group->post('[/]', BebidaController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio'); 
  $group->put('/modificar', BebidaController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarBartender');
  $group->delete('/borrar', BebidaController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');

//cervezas
$app->group('/api/cervezas', function (RouteCollectorProxy $group) {
  $group->get('[/]', CervezaController::class . ':traerTodos');
  $group->get('/{id}', CervezaController::class . ':traerUno');
  $group->post('[/]', CervezaController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio'); 
  $group->put('/modificar', CervezaController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarCervecero');
  $group->delete('/borrar', CervezaController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');


//postres
$app->group('/api/postres', function (RouteCollectorProxy $group) {
  $group->get('[/]', PostreController::class . ':traerTodos');
  $group->get('/{id}', PostreController::class . ':traerUno'); 
  $group->post('[/]', PostreController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio'); 
  $group->put('/modificar', PostreController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarCocinero');
  $group->delete('/borrar', PostreController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');


$app->group('/api/tragos', function (RouteCollectorProxy $group) {
  $group->get('[/]', TragoController::class . ':traerTodos');
  $group->get('/{id}', TragoController::class . ':traerUno');
  $group->post('[/]', TragoController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio'); 
  $group->put('/modificar', TragoController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarBartender');
  $group->delete('/borrar', TragoController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');

$app->group('/api/vinos', function (RouteCollectorProxy $group) {
  $group->get('[/]', VinoController::class . ':traerTodos');
  $group->get('/{id}', VinoController::class . ':traerUno'); 
  $group->post('[/]', VinoController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarSocio'); 
  $group->put('/modificar', VinoController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarBartender');
  $group->delete('/borrar', VinoController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');


$app->group('/api/encuestas', function (RouteCollectorProxy $group) {
  $group->get('[/]', EncuestaController::class . ':traerTodos')
    ->add(AutentificadorController::class . ':verificarSocio')
    ->add(AutentificadorController::class . ':verificarCredenciales');
  $group->get('/{id}', EncuestaController::class . ':traerUno')
    ->add(AutentificadorController::class . ':verificarSocio')
    ->add(AutentificadorController::class . ':verificarCredenciales'); 
  $group->post('[/]', EncuestaController::class . ':cargarUno');
  $group->put('/modificar', EncuestaController::class . ':modificarUno')
    ->add(AutentificadorController::class . ':verificarSocio')
    ->add(AutentificadorController::class . ':verificarCredenciales');
  $group->delete('/borrar', EncuestaController::class . ':borrarUno')
    ->add(AutentificadorController::class . ':verificarAdmin')
    ->add(AutentificadorController::class . ':verificarCredenciales');
});

$app->group('/api/pedidos', function (RouteCollectorProxy $group) {
  $group->get('[/]', PedidoController::class . ':traerTodos')->add(AutentificadorController::class . ':verificarSocio');
  $group->get('/{id}', PedidoController::class . ':traerUno');
  $group->get('/extendido/{id}', PedidoController::class . ':traerUnoCompleto')->add(AutentificadorController::class . ':verificarSocio');
  $group->post('[/]', PedidoController::class . ':cargarUno')->add(AutentificadorController::class . ':verificarMozo');
  $group->post('/foto', PedidoController::class . ':agregarFoto')->add(AutentificadorController::class . ':verificarMozo');
  $group->put('/modificar', PedidoController::class . ':modificarUno')->add(AutentificadorController::class . ':verificarMozo');
  $group->put('/servir', PedidoController::class . ':servir')->add(AutentificadorController::class . ':verificarMozo');
  $group->put('/cancelar', PedidoController::class . ':cancelar')->add(AutentificadorController::class . ':verificarMozo');
  $group->put('/prepararItem', PedidoController::class . ':prepararItem');
  $group->put('/agregarItems', PedidoController::class . ':agregarItems')->add(AutentificadorController::class . ':verificarMozo');
  $group->put('/cancelarItem', PedidoController::class . ':cancelarItem');
  $group->put('/finalizarItem', PedidoController::class . ':finalizarItem');
  $group->put('/pagar', PedidoController::class . ':realizarPago')->add(AutentificadorController::class . ':verificarSocio');
  $group->delete('/borrar', PedidoController::class . ':borrarUno')->add(AutentificadorController::class . ':verificarAdmin');
})->add(AutentificadorController::class . ':verificarCredenciales');

$app->group('/api/pedidos/consultas', function (RouteCollectorProxy $group) {
  $group->get('/tiempoDeEspera', PedidoController::class . ':getTiempoEstimadoEspera');
}); 

$app->group('/api/pedidos/informes', function (RouteCollectorProxy $group) {
  $group->get('/masVendido', PedidoController::class . ':traerMasVendido');
  $group->get('/menosVendido', PedidoController::class . ':traerMenosVendido'); 
  $group->get('/atrasados', PedidoController::class . ':traerAtrasados'); 
  $group->get('/cancelados', PedidoController::class . ':traerCancelados'); 
})
->add(AutentificadorController::class . ':verificarSocio')
->add(AutentificadorController::class . ':verificarCredenciales');

$app->group('/api/exports', function (RouteCollectorProxy $group) {
  $group->get('/bebidas', BebidaController::class . ':export');
  $group->get('/cervezas', CervezaController::class . ':export');
  $group->get('/empleados', EmpleadoController::class . ':export');
  $group->get('/encuestas', EncuestaController::class . ':export');
  $group->get('/mesas', MesaController::class . ':export');
  $group->get('/pedidos', PedidoController::class . ':export');
  $group->get('/platos', PlatoController::class . ':export');
  $group->get('/postres', PostreController::class . ':export');
  $group->get('/tragos', TragoController::class . ':export');
  $group->get('/usuarios', UsuarioController::class . ':export');
  $group->get('/vinos', VinoController::class . ':export');
})
->add(AutentificadorController::class . ':verificarSocio')
->add(AutentificadorController::class . ':verificarCredenciales');

$app->group('/api/imports', function (RouteCollectorProxy $group) {
  $group->post('/bebidas', BebidaController::class . ':import');
  $group->post('/cervezas', CervezaController::class . ':import');
  $group->post('/empleados', EmpleadoController::class . ':import');
  $group->post('/encuestas', EncuestaController::class . ':import');
  $group->post('/mesas', MesaController::class . ':import');
  $group->post('/pedidos', PedidoController::class . ':import');
  $group->post('/platos', PlatoController::class . ':import');
  $group->post('/postres', PostreController::class . ':import');
  $group->post('/tragos', TragoController::class . ':import');
  $group->post('/usuarios', UsuarioController::class . ':import');
  $group->post('/vinos', VinoController::class . ':import');
})
->add(AutentificadorController::class . ':verificarSocio')
->add(AutentificadorController::class . ':verificarCredenciales');

$app->get('[/]', function (Request $request, Response $response) {    
    $response->getBody()->write("BackEnd de Restaurant de Agustin Coria");
    return $response;
});

#endregion

//RUN
$app->run();
