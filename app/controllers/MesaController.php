<?php
require_once './models/Mesa.php';
require_once './models/Pedido.php';
require_once './controllers/AbstractController.php';

/**
 * MesaController
 * 
 */
class MesaController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Mesa::class;
      $this->obligatoryParameters = ['codigo'];
    }

    #region INFORMES

    //USO
    function traerMasUsada($request, $response, $args){

      $mesas =  self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorUso');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa mas usada" => $mesas));
      } else {
        $payload = json_encode(array("mesas mas usadas" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    function traerMenosUsada($request, $response, $args){

      $mesas =  self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorUso', 'min');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa menos usada" => $mesas));
      } else {
        $payload = json_encode(array("mesas menos usadas" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    //FACTURACION 

    function traerMayorFacturacion($request, $response, $args){

      $mesas =  self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorFacturacion');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa con mayor facturacion" => $mesas));
      } else {
        $payload = json_encode(array("mesas con mayor facturacion" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');   
    }

    function traerMenorFacturacion($request, $response, $args){

      $mesas =  self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorFacturacion', 'min');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa con menor facturacion" => $mesas));
      } else {
        $payload = json_encode(array("mesas con menor facturacion" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');   
    }

    //IMPORTE

    function traerMayorImporte($request, $response, $args){

      $mesas =  self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorImporte');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa con mayor importe" => $mesas));
      } else {
        $payload = json_encode(array("mesas con mayor importe" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } 

        
    function traerMenorImporte($request, $response, $args){

      $mesas =  self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorImporte', 'min');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa con menor importe" => $mesas));
      } else {
        $payload = json_encode(array("mesas con menor importe" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } 

    
    //COMENTARIOS

    function traerMejoresComentarios($request, $response, $args){
     
      $mesas = self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorComentarios');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa con mejores comentarios" => $mesas));
      } else {
        $payload = json_encode(array("mesas con mejores comentarios" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');    
    } 

        
    function traerPeoresComentarios($request, $response, $args){

      $mesas = self::getMaxMinMesas($request, $response, $args, 'Mesa::getPorComentarios','min');

      if($mesas->count() === 1){
        $payload = json_encode(array("mesa con peores comentarios" => $mesas));
      } else {
        $payload = json_encode(array("mesas con peores comentarios" => $mesas));
      };      
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } 

    function traerFacturacionTodas($request, $response, $args){
      $parametros = $request->getParsedBody();

      //default
      $fechaInicio = Carbon\Carbon::minValue();
      $fechaFin = Carbon\Carbon::maxValue();

      if(isset($parametros)){
        $fechas = setFechasInicioFin($parametros);
        if(isset($fechas)){
          $fechaInicio = $fechas[0]->format('Y-m-d');
          $fechaFin = $fechas[1]->format('Y-m-d');
        }
      }

      $mesas = Mesa::getTodasPorFacturacion($fechaInicio, $fechaFin);

      $payload = json_encode(array("mesas" => $mesas));

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    function traerFacturacionUna($request, $response, $args){
      $id = $args['id'];

      $parametros = $request->getParsedBody();

      //default
      $fechaInicio = Carbon\Carbon::minValue();
      $fechaFin = Carbon\Carbon::maxValue();

      if(isset($parametros)){
        $fechas = setFechasInicioFin($parametros);
        if(isset($fechas)){
          $fechaInicio = $fechas[0]->format('Y-m-d');
          $fechaFin = $fechas[1]->format('Y-m-d');
        }
      }

      $mesa = Mesa::getUnaPorFacturacion($id,$fechaInicio, $fechaFin);

      $payload = json_encode(array("facturacion" => $mesa));

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    /**
     * getMaxMinMesas devuelve todas las mesas que tengan mayor o menor de algo.
     * 
     * @param $request
     * @param $response
     * @param $args
     * @param $anonFunc Funcion a usar para ejecutar la query deseada
     * @param $keyDescription el nombre que usaremos para el mensaje de devolución
     * @param $maxOrMin 'max' by default, or 'min'
     */
    static function getMaxMinMesas($request, $response, $args, $anonFunc, $maxOrMin = 'max'){
      $parametros = $request->getParsedBody();

      //default
      $fechaInicio = Carbon\Carbon::minValue();
      $fechaFin = Carbon\Carbon::maxValue();

      if(isset($parametros)){
        $fechas = setFechasInicioFin($parametros);
        if(isset($fechas)){
          $fechaInicio = $fechas[0]->format('Y-m-d');
          $fechaFin = $fechas[1]->format('Y-m-d');
        }
      }  

      $mesas = $anonFunc($fechaInicio,$fechaFin,$maxOrMin);

      return $mesas;
    }

  #endregion
}
