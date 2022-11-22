<?php

use Illuminate\Support\Facades\Date;
use \League\Csv\Writer;


require_once './models/Pedido.php';
require_once './models/Usuario.php';
require_once './models/Mesa.php';
require_once './models/DetallePedido.php';
require_once './controllers/AbstractController.php';


/**
 * PedidoController
 * 
 * @SuppressWarnings(PHPMD)
 */
class PedidoController extends AbstractController
{

    function __construct()
    {
      $this->controlledClass = Pedido::class;
      $this->obligatoryParameters = [
        'mesa_id',
        'mozo_id',
      ];
    }


    public function cargarUno($request, $response, $args){

      $parametros = $request->getParsedBody();

      $detalles_pedido = $parametros['detalles_pedido'];

      unset($parametros['detalles_pedido']);      

      if(array_keys_exist($this->obligatoryParameters,$parametros)){

          $codigoUnico = Pedido::generarCodigo();
          $parametros['codigo'] = $codigoUnico;

          //crear pedido
          $payload = Pedido::crear($parametros);     

          //
          $payload = json_decode($payload);
          
          if(property_exists($payload,'id')){

            $precioPedido = 0;
            $i = 0;
            //obtener id del pedido
            $idPedido = $payload->id;
            //setear codigo del pedido en mensaje de respuesta
            $payload->codigo = $codigoUnico;
            
            //Crear items del pedido              
            foreach ($detalles_pedido as $key => $detalle) {
              //agregar id del pedido al detalle para poder crearlo
                $detalle['pedido_id'] = $idPedido;

                //crear detalle
                $aux = json_decode(DetallePedido::crear($detalle));

                //revisar que se haya creado bien:
                if (isset($aux->id)){
                  if(isset($detalle['cantidad'])){
                   $cantidad = $detalle['cantidad'];
                  } else {
                    $cantidad = 1;
                  }
                  //conseguir valor del item * cantidad
                  $precioConsumible = DetallePedido::firstWhere('id', $aux->id)->consumible->precio;
                  $precioPedido += $precioConsumible * $cantidad;
                }
                $payload->detalles[$i] = $aux;
                $i++;
            }

            //agregar el precio total al pedido.
            $pedido = Pedido::firstWhere('id',$idPedido);
            $pedido->precio = $precioPedido;
            $pedido->save();

            $payload->precio = $precioPedido;

            //cambiar estado de la mesa:
            $mesa = $pedido->mesa;
            $mesa->estado = ESTADOS_MESA[0];
            $mesa->save();
          }
          $payload = json_encode($payload);

      } else {
          $payload = json_encode(array("mensaje" => "Parámetros inválidos"));
      }

        $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }


    public function agregarFoto($request, $response, $args){

      $parametros = $request->getParsedBody();

      $id = $parametros['id'];
      $pedido = Pedido::firstWhere('id',$id);      

      if(isset($pedido)){
        $urlImagen = PATH_IMAGENES_PEDIDOS . '/' . $pedido->codigo . '.jpg';

        $pedido->url_foto = $urlImagen;
        $pedido->save();

        //mover el archivo temporal a carpeta final.
        move_uploaded_file($_FILES['foto']['tmp_name'], $urlImagen); 
        
        $payload = json_encode(array("mensaje" => Pedido::exitoAlModificar()));
      } else {
        $payload = json_encode(array("error" => Pedido::errorNoEncontrado()));
      }     
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');  
    }


    public function traerUnoCompleto($request, $response, $args){

        $id = $args['id'];

        $obj = Pedido::with('mesa','mozo','realizadorPago')->firstWhere('id', $id);

        $payload = json_encode($obj ?: array("error" => Pedido::errorNoEncontrado()));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }


    public function cancelar($request, $response, $args){
      $parametros = $request->getParsedBody();

      $id = $parametros['id'];

      // Conseguimos el objeto
      $obj = Pedido::firstWhere('id',$id);
    
      // Si existe
      if (isset($obj)) {
        $obj->estado = 'cancelado';
        foreach ($obj->items as $item) {
          $item->estado = 'cancelado';
          $item->save();
        }
        $obj->save();
        $payload = json_encode(array("mensaje" => "Pedido Cancelado"));
      } else {
        // No existe
        $payload = json_encode(array("mensaje" => $this->controlledClass::errorNoEncontrado()));
      }
  
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }


    public function agregarItems($request, $response, $args){
      $parametros = $request->getParsedBody();

      $idPedido = $parametros['id'];

      $detalles_pedido = $parametros['detalles_pedido'];

      $pedido = Pedido::firstWhere('id', $idPedido);

      if(isset($pedido)){
        $i = 0;
        //agregar items al pedido          
        foreach ($detalles_pedido as $key => $detalle) {
          $detalle['pedido_id'] = $idPedido;
          $aux = json_decode(DetallePedido::crear($detalle));

          if (isset($aux->id)){
            if(isset($detalle['cantidad'])){
             $cantidad = $detalle['cantidad'];
            } else {
              $cantidad = 1;
            }
            //conseguir valor del item * cantidad
            $precioConsumible = DetallePedido::firstWhere('id', $aux->id)->consumible->precio;
            $pedido->precio += $precioConsumible * $cantidad;
          }
          $respuesta['detalles'][$i] = $aux;
          $i++;
        }

        $pedido->estado = ESTADOS_PEDIDO[0];
        $pedido->fecha_final_esperada = null;
        $pedido->save();

        $payload = json_encode($respuesta);
      } else {
        $payload = json_encode(array('error' => "No se encontró el pedido"));
      }      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }


    public function servir($request, $response, $args){
      $parametros = $request->getParsedBody();

      $id = $parametros['id'];

      $pedido = Pedido::firstWhere('id',$id);
      
      if(isset($pedido)){
        if($pedido->estado == ESTADOS_PEDIDO[2]){
          $pedido->estado = ESTADOS_PEDIDO[4];
          $pedido->save();

          //cambiar el estado de la mesa a "con cliente comiendo"
          $mesa = $pedido->mesa;
          $mesa->estado = ESTADOS_MESA[1];
          $mesa->save();

          $payload = json_encode(array("mensaje" => "Pedido Servido"));          
        } else {
          $payload = json_encode(array("error" => "El pedido no está listo para servir"));
        }
      } else {
        $payload = json_encode(array("error" => Pedido::errorNoEncontrado()));
      }
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');

    }


    public function cancelarItem($request, $response, $args){

      $parametros = $request->getParsedBody();
      $id = $parametros['id'];

      //item
      $item = DetallePedido::find($id);

      //si existe y no está cancelado
      if(isset($item) && $item->estado != ESTADOS_CONSUMIBLE[2]){
        
        //cancelar item
        $item->estado = ESTADOS_CONSUMIBLE[2];
        $item->save();

        //Vamos a tener que preguntar si todos los otros items ya están en preparación para dejar el pedido en preparacion, o si están listos para servir para dejarlo en listo para servir.

        //pedido
        $pedido = $item->pedido;

        if($pedido->estaEnPreparacion()){
          $pedido->estado = ESTADOS_PEDIDO[1];

          $fechaFinal = $pedido->obtenerFechaFinalEsperada();
          if(isset($fechaFinal)){
            $pedido->fecha_final_esperada = $fechaFinal;
          }
        }

        if($pedido->listoParaServir()){
          $pedido->estado = ESTADOS_PEDIDO[2];
        }

        //updatear precio del pedido al que pertenece
        $pedido->precio -= $item->consumible->precio * $item->cantidad;

        //guardar cambios        
        $pedido->save();

        $payload = json_encode(array("mensaje" => "Se cancelo el item"));
      } else {
        $payload = json_encode(array("error" => DetallePedido::errorNoEncontrado()));
      }

      $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }


    public function prepararItem($request, $response, $args){

      $parametros = $request->getParsedBody();
      $id = $parametros['id'];
      $minutos = $parametros['minutos'];

      $item = DetallePedido::firstWhere('id', $id);
      
      if(isset($item) && $item->estado == ESTADOS_CONSUMIBLE[0]){
        //Get Token data, necesitamos el usuario para guardarlo en el detalle del pedido.
        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);
        try {
          //obtener id del usuario que realizó el request
          $data = AutentificadorJWT::ObtenerData($token);
          $usuario = Usuario::firstWhere('usuario', $data->usuario);
          

          $item->usuario_id = $usuario->id;

          //setear estado a 'en preparacion'
          $item->estado = ESTADOS_CONSUMIBLE[1];

          //setear hora de inicio y hora esperada de finalizacion
          $ahora = new DateTime(); 
          $luego = new DateTime();
          $luego->add(new DateInterval('PT' . $minutos . 'M'));

          $item->fecha_inicio = $ahora;    
          $item->fecha_final_estimada =  $luego;

          //guardar cambios
          $item->save();

          $pedido = $item->pedido;
          //setear estado del pedido a 'en preparacion' si todos los items están en preparacion
          if($pedido->estaEnPreparacion()){
            
            $pedido->estado = ESTADOS_PEDIDO[1];

            //setear la fecha final esperada: va a ser la maayor fecha final esperada de entre todos sus items.
            $pedido->fecha_final_esperada = $pedido->obtenerFechaFinalEsperada();

            $pedido->save();
          }          

          $payload = json_encode(array('mensaje' => "Se está preparando el item"));

        } catch (Exception $e) {
          $payload = json_encode(array('error' => $e->getMessage()));
        }
      } else {
        $payload = json_encode(array('error' => DetallePedido::errorNoEncontrado()));
      }      

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }


    public function finalizarItem($request, $response, $args){

      $parametros = $request->getParsedBody();
      $id = $parametros['id'];

      //item
      $item = DetallePedido::find($id);

      if(isset($item)){
        //finalizar item
        $item->estado = ESTADOS_CONSUMIBLE[3];

        //guardar cambios
        $item->save();   

        //pedido
        $pedido = $item->pedido;

        //chequear si hace falta marcar pedido como listo para servir.
        if($pedido->listoParaServir()){
          //marcar como listo para servir
          $pedido->estado = ESTADOS_PEDIDO[2];
          $pedido->fecha_final = new DateTime();
          $pedido->save();
        }           

        $payload = json_encode(array("mensaje" => "Se finalizo el item"));
      } else {
        $payload = json_encode(array("error" => DetallePedido::errorNoEncontrado()));
      }

      $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }


    public function getTiempoEstimadoEspera($request, $response, $args){
      $parametros = $request->getParsedBody();

      $codigoMesa = $parametros['codigo_mesa'];
      $codigoPedido = $parametros['pedido'];
      
      $mesa = Mesa::firstWhere('codigo',$codigoMesa);      

      if(isset($mesa)){

        $pedido =  $mesa->pedidos->firstWhere('codigo', $codigoPedido);

        if(isset($pedido)){
          $estado = $pedido->estado;
          if($estado == ESTADOS_PEDIDO[1]){
            $fechaFinal = new DateTime($pedido->fecha_final_esperada);
            $ahora  = new DateTime();
            $diff = $ahora->diff($fechaFinal);

            $tiempoDeEspera = $diff->h . " horas : " . $diff->i . " minutos";
            if($diff->invert == 0){
              $payload = json_encode(array("tiempo de espera" => $tiempoDeEspera));            
            } else {
              $payload = json_encode(array("tiempo de espera" => "Su pedido está atrasado por " . $tiempoDeEspera));
            }
          } else {
            $payload = json_encode(array(
              "mensaje" => "El pedido no está en preparacion",
              "estado" => $estado ));
          }
        } else {
          $payload = json_encode(array("error" => Pedido::errorNoEncontrado()));
        }
      } else {
        $payload = json_encode(array("error" => Mesa::errorNoEncontrado()));
      }

      $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    

    public function borrarUno($request, $response, $args)
    {
      $parametros = $request->getParsedBody();

      $pedido = Pedido::firstWhere('id',$parametros['id']);

      if(isset($pedido)){
        if($pedido->estado == ESTADOS_PEDIDO[3] || $pedido->estado == ESTADOS_PEDIDO[5]){

          foreach ($pedido->items as $item) {
            $item->delete();
          }          
          $pedido->delete();
          $payload = json_encode(array("mensaje" => Pedido::exitoAlBorrar()));
          
        } else {
          $payload = json_encode(array("mensaje" => "El pedido debe estar cancelado o finalizado para poder borrarse de la base de datos"));
        }        
      } else {
        $payload = json_encode(array("mensaje" => Pedido::errorNoEncontrado()));
      }

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }


    public function realizarPago($request, $response, $args){

      $parametros = $request->getParsedBody();
      $id = $parametros['id'];

      $pedido = Pedido::firstWhere('id', $id);
      
      if(isset($pedido)){
        if($pedido->estado == ESTADOS_PEDIDO[4]){
          //Get Token data, necesitamos el usuario para guardarlo en el pedido.
          $header = $request->getHeaderLine('Authorization');
          $token = trim(explode("Bearer", $header)[1]);
          try {
            //obtener id del usuario que realizó el request
            $data = AutentificadorJWT::ObtenerData($token);
            $usuario = Usuario::firstWhere('usuario', $data->usuario);            

            $pedido->realizador_pago = $usuario->id;

            //setear estado a 'finalizado'
            $pedido->estado = ESTADOS_PEDIDO[5];

            //guardar cambios
            $pedido->save();      

            //cerrar mesa
            $mesa = $pedido->mesa;
            $mesa->estado = ESTADOS_MESA[3];
            $mesa->save();

            $payload = json_encode(array('mensaje' => "Se realizó el pago. Pedido finalizado"));

          } catch (Exception $e) {
            $payload = json_encode(array('error' => $e->getMessage()));
          }
        } else {
          $payload = json_encode(array('error' => "El pedido no está listo para pagar"));
        }
      } else {
        $payload = json_encode(array('error' => Pedido::errorNoEncontrado()));
      }      

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    //OVERRIDE CONTROLLER, NO QUIERO EXPORTAR LOS DETALLES DEL PEDIDO
    public function export($request, $response, $args){

      $items = Pedido::without('items')->get();

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

    //INFORMES
    function traerMasVendido($request, $response, $args){

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

      $consumibles =  DetallePedido::getPorVentas($fechaInicio,$fechaFin);

      $payload = json_encode(array("mas vendido" => $consumibles)); 
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    function traerMenosVendido($request, $response, $args){

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

      $consumibles =  DetallePedido::getPorVentas($fechaInicio,$fechaFin,'min');

      $payload = json_encode(array("menos vendido" => $consumibles));     
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

    function traerAtrasados($request, $response, $args){
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
      
      $pedidos = Pedido::getAtrasados($fechaInicio,$fechaFin);

      $payload = json_encode(array("pedidos atrasados" => $pedidos));     
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json'); 
    }

    function traerCancelados($request, $response, $args){
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
      $pedidos = Pedido::getCancelados($fechaInicio,$fechaFin);

      $payload = json_encode(array("pedidos cancelados" => $pedidos));     
      
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

}
