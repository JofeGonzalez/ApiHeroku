<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->setBasePath('/app');


$app->get('/', function(Request $request, Response $response, $args){
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->run();