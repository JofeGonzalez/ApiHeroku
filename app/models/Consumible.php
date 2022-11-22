<?php

require_once './models/EloquentObject.php';
require_once './interfaces/IApiValidable.php';
require_once './models/DetallePedido.php';
require_once './models/Usuario.php';

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Consumible
 * 
 * @SuppressWarnings(PHPMD)
 */
class Consumible extends EloquentObject implements IApiValidable
{  
    protected $fillable = [
        'nombre','descripcion','minutos_de_preparacion','precio'
    ];

    //RELATIONSHIPS:
    public function detalles_pedido(){
        return $this->morphMany('App\Models\DetallePedido', 'consumible');
    }
    

    //VALIDACIONES
    static function validarMinutos($minutos){
        return is_numeric($minutos) && $minutos >= 0 && $minutos < 1000;
    }
    
    static function validarPrecio($precio){
        return is_numeric($precio) && $precio >= 0;
    }

    static function validarParametro($key,$val){
        switch ($key) {
            case 'nombre':
                $validado = true;
                break;
            case 'descripcion':
                $validado = true;
                break;
            case 'minutos_de_preparacion':
                $validado =  self::validarMinutos($val);
                break;
            case 'precio':
                $validado = self::validarPrecio($val);
                break;
            default:
                $validado = parent::validarParametro($key,$val);           
        }
        return $validado;
    }    

      /**
     * Trae todos los consumibles de 'detalles_pedido' agrupados por usuario y que se encuentren entre las fechas recibidas. Además devuelve información del empleado del usuario
     *
     * @param mixed  $fechaInicio incluye el dia
     * @param mixed  $fechaFin incluye el dia
     * @param array  $tiposConsumible array de tipos de consumible, i.e. ['oneType','otherType']
     * @return \Illuminate\Support\Collection
     */ 
  static function getPorTipoGroupByEmpleado($fechaInicio, $fechaFin, $tiposConsumible){
    if(!is_array($tiposConsumible)){
      return null;
    }
    return Capsule::table('detalles_pedido as dp')
      ->join('usuarios as u','dp.usuario_id','=','u.id')
      ->leftJoin('empleados as e','u.empleado_id','=','e.id')
      ->select(Capsule::raw('sum(cantidad) as total, u.usuario, e.nombre, e.apellido'))
      ->whereIn('dp.consumible_tipo',$tiposConsumible)
      ->whereDate('dp.fecha_alta','>=', $fechaInicio)             
      ->whereDate('dp.fecha_alta','<=', $fechaFin)
      ->groupBy('usuario_id')
      ->orderBy(Capsule::raw('SUM(cantidad)'))
      ->get();
  }
}