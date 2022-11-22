<?php
require_once './models/Empleado.php';
require_once './models/DetallePedido.php';
require_once './controllers/SoftAbstractController.php';
require_once './functions/setFechasInicioFin.php';
require_once './models/Consumible.php';


/**
 * EmpleadoController
 * 
 * @SuppressWarnings(PHPMD)
 */
class EmpleadoController extends SoftAbstractController
{

    function __construct()
    {
      $this->controlledClass = Empleado::class;
      $this->obligatoryParameters = ['nombre','apellido','rol','email', 'dni','fecha_nacimiento','direccion'];
    }



    #region funciones principales
    public function traerUnoConUsuarios($request, $response, $args){

      $id = $args['id'];

      $obj = Empleado::with('usuarios')->firstWhere('id',$id);

      $payload = json_encode($obj ?: array("error" => Empleado::errorNoEncontrado()));

      $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }



    public function traerFechasAlta($request, $response, $args){

      $parametros = $request->getParsedBody();

      //default
      $fechaInicio = Carbon\Carbon::minValue();
      $fechaFin = Carbon\Carbon::maxValue();

      if(isset($parametros)){
        $fechas = setFechasInicioFin($parametros);
        if(isset($fechas)){
          $fechaInicio = $fechas[0];
          $fechaFin = $fechas[1];
        }
      }     

      $empleados = Empleado::whereDate('fecha_alta','>=', $fechaInicio)->whereDate('fecha_alta','<=', $fechaFin)
        ->get(['nombre','apellido','rol','fecha_alta']);

      $payload = json_encode(array("empleados" => $empleados));

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }



  public function operacionesPorSector($request, $response, $args){

    $parametros = $request->getParsedBody();

    //default
    $fechaInicio = Carbon\Carbon::minValue();
    $fechaFin = Carbon\Carbon::maxValue();

    if(isset($parametros)){
      $fechas = setFechasInicioFin($parametros);
      if(isset($fechas)){
        $fechaInicio = $fechas[0];
        $fechaFin = $fechas[1];
      }
    }     

    $detalles = DetallePedido::where('estado',ESTADOS_CONSUMIBLE[3])
      ->whereDate('fecha_alta','>=', $fechaInicio)
      ->whereDate('fecha_alta','<=', $fechaFin)
      ->get(['pedido_id','consumible_id','consumible_tipo','cantidad','estado']);

    //GET BEBIDAS
    $bebidas = $detalles->whereIn('consumible_tipo',['bebida','vino','trago'])->sum('cantidad');

    //GET CERVEZA
    $cervezas = $detalles->where('consumible_tipo','cerveza')->sum('cantidad');

    //GET PLATOS
    $platos = $detalles->where('consumible_tipo','plato')->sum('cantidad');

    //GET POSTRES
    $postres = $detalles->where('consumible_tipo','postre')->sum('cantidad');

    //response
    $payload = json_encode(array(
      "Barra de tragos y vinos" => $bebidas,
      "Barra de choperas" => $cervezas,
      "Cocina" => $platos,
      "Candy Bar" => $postres,
    ));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }



  public function operacionesPorEmpelado($request, $response, $args){

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

    $cervezas = Consumible::getPorTipoGroupByEmpleado($fechaInicio,$fechaFin,['cerveza']);
    $bebidas = Consumible::getPorTipoGroupByEmpleado($fechaInicio,$fechaFin,['bebida','trago','vino']);
    $platos = Consumible::getPorTipoGroupByEmpleado($fechaInicio,$fechaFin,['plato']);
    $postres = Consumible::getPorTipoGroupByEmpleado($fechaInicio,$fechaFin,['postre']);

    $mozos = Usuario::getPedidosPorMozo($fechaInicio,$fechaFin);

    $payload = json_encode(array(
      "Bebidas, tragos y vinos" => $bebidas,
      "Cervezas" => $cervezas,
      "Platos" => $platos,
      "Postres" => $postres,
      "Mozos" => $mozos
    ));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }


  public function operacionesDeUno($request, $response, $args){
    $idEmpleado = $args['id'];

    $empleado = Empleado::firstWhere('id',$idEmpleado);

    if(isset($empleado)){

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

      $i = 0;
      foreach ($empleado->usuarios as $usuario) {
        switch($usuario->perfil){
          case 'socio':
            $respuesta[$i] = $usuario->getPagosRealizados($fechaInicio, $fechaFin);
            break;
          case 'mozo':
            $respuesta[$i] = $usuario->getPedidosRealizados($fechaInicio, $fechaFin);
            break;
          default:
            $respuesta[$i] = $usuario->getConsumiblesCompletados($fechaInicio, $fechaFin);
        }
        $i++;
      }
      $payload = json_encode(array("operaciones" => $respuesta));
    } else {
      $payload = json_encode(array("error" =>Empleado::errorNoEncontrado()));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

#endregion

#region funciones auxiliares

  #endregion
}
