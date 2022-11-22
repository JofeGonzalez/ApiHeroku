<?php

require_once './models/Usuario.php';
require_once './controllers/SoftAbstractController.php';

use \League\Csv\Writer;


/**
 * UsuarioController
 * 
 * @SuppressWarnings(PHPMD)
 */
class UsuarioController extends SoftAbstractController
{ 
  function __construct()
    {
      $this->controlledClass = Usuario::class;
      $this->obligatoryParameters = ['usuario','clave','email','perfil'];
    }
    
    public function cargarUno($request, $response, $args)
    {
      $parametros = $request->getParsedBody();

      if(
      array_keys_exist($this->obligatoryParameters,$parametros) || 
      !isset($this->obligatoryParameters)){

        $payload = json_decode(Usuario::crear($parametros));
        if(property_exists($payload,'id')){
          unset($parametros['perfil']);
          $payload->token = AutentificadorJWT::CrearToken($parametros);
        }        
        $payload = json_encode($payload);

      } else {
        $payload = json_encode(array("mensaje" => "Parámetros inválidos"));
      }

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    public function traerUnoConEmpleado($request, $response, $args){
      
      $id = $args['id'];

      $obj = Usuario::with('empleado')->firstWhere('id',$id);

      $payload = json_encode($obj ?: array("error" => Usuario::errorNoEncontrado()));

      $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    //OVERRIDE EXPORT PARA NO SACAR HEADER DE CLAVE.
    public function export($request, $response, $args){

      $items = $this->controlledClass::all();

      $csv = Writer::createFromFileObject(new \SplTempFileObject);

      $headers = array_keys($items[0]->getAttributes());
      $indexClave = array_search('clave',$headers);
      unset($headers[$indexClave ]);
      $csv->insertOne($headers);

      foreach ($items as $item) {
          $csv->insertOne($item->toArray());
      }

      header('Content-Description: File Transfer');
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename=' . $this->controlledClass . 's.csv');
      header('Content-Transfer-Encoding: binary');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Expires: 0');
      header('Pragma: public');

      $csv->output();

      exit;
    }
}
