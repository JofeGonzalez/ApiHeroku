<?php

require_once './functions/validateKeys.php';
require_once './interfaces/IApiUsable.php';

use \League\Csv\Writer;
use \League\Csv\Reader;

class AbstractController implements IApiUsable{

    protected $controlledClass;
    protected $obligatoryParameters;

    #region CRUD
    public function cargarUno($request, $response, $args)
    {
      $parametros = $request->getParsedBody();

      if(
      array_keys_exist($this->obligatoryParameters,$parametros) || 
      !isset($this->obligatoryParameters)){

        $payload = $this->controlledClass::crear($parametros);

      } else {
        $payload = json_encode(array("mensaje" => "Parámetros inválidos"));
      }

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    public function traerUno($request, $response, $args)
    {
        $id = $args['id'];

        $payload = $this->controlledClass::fetch($id);

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function traerTodos($request, $response, $args)
    {
        $payload = $this->controlledClass::fetchAll();

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function modificarUno($request, $response, $args)
    { 
      $parametros = $request->getParsedBody();

      $id = $parametros['id'];

      // Conseguimos el objeto
      $obj = $this->controlledClass::firstWhere('id',$id);
  
      // Si existe
      if (isset($obj)) {
        $payload = $obj->modificar($parametros);
      } else {
        // No existe
        $payload = json_encode(array("mensaje" => $this->controlledClass::errorNoEncontrado()));
      }
  
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    public function borrarUno($request, $response, $args){
        
        $parametros = $request->getParsedBody();

        $obj = $this->controlledClass::firstWhere('id',$parametros['id']);

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

    #endregion

    #region CSV
    public function export($request, $response, $args){

      $items = $this->controlledClass::all();

      $csv = Writer::createFromFileObject(new \SplTempFileObject);

      $csv->insertOne(array_keys($items[0]->getAttributes()));

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

    public function import($request, $response, $args){

      $newPath = PATH_UPLOADS . '/' . time() . '.csv';

      //mover el archivo temporal a carpeta final.
      move_uploaded_file($_FILES['archivo']['tmp_name'], $newPath);

      //load the CSV document from a file path
      $csv = Reader::createFromPath($newPath , 'r');
      $csv->setHeaderOffset(0);

      $header = $csv->getHeader(); 
      $records = $csv->getRecords(); 

      //crear los modelos e insertar en base de datos
      $i = 0;
      foreach ($records as $record) {
        $payload[$i] = json_decode($this->controlledClass::crear($record));
        $i++;
      }

      $payload = json_encode($payload);
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    #endregion
}