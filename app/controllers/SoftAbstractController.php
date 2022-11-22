<?php

require_once './controllers/AbstractController.php';
require_once './interfaces/IApiSoftUsable.php';

/**
 * SoftAbstractController
 * 
 * Esta clase controlará modelos que hereden de SoftEloquentObject
 */
class SoftAbstractController extends AbstractController implements IApiSoftUsable {

    public function desactivarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $id = $parametros['id'];

        $obj = $this->controlledClass::withTrashed()->firstWhere('id',$id);

        if(isset($obj)){

            $payload = $obj->desactivar();

        } else {
          $payload = json_encode(array("mensaje" => $this->controlledClass::errorNoEncontrado()));
        }

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function reactivarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
  
          $id = $parametros['id'];
          
          $obj = $this->controlledClass::withTrashed()->firstWhere('id',$id);
  
          if(isset($obj)){
  
            $payload = $obj->reactivar();

          } else {
  
            $payload = json_encode(array("mensaje" => $this->controlledClass::errorNoEncontrado()));
  
          }
          
          $response->getBody()->write($payload);
          return $response
            ->withHeader('Content-Type', 'application/json');
      }


    //OVERRIDE BORRAR para poder utilizar método withTrashed().

    public function borrarUno($request, $response, $args){
    
        $parametros = $request->getParsedBody();

        $obj = $this->controlledClass::withTrashed()->firstWhere('id',$parametros['id']);

        if(isset($obj)){          
            $obj->borrar();
            $payload = json_encode(array("mensaje" => $this->controlledClass::exitoAlBorrar()));
        } else {
            $payload = json_encode(array("mensaje" => $this->controlledClass::errorNoEncontrado()));
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }      
}