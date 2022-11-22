<?php

require_once './models/EloquentObject.php';
require_once './interfaces/IApiValidable.php';
require_once './models/Usuario.php';
require_once './models/Pedido.php';
require_once './models/Bebida.php';

use Illuminate\Database\Capsule\Manager as Capsule;


class DetallePedido extends EloquentObject implements IApiValidable {

    protected $table = 'detalles_pedido';    

    protected $fillable = [
        'pedido_id',
        'consumible_id',
        'consumible_tipo',
        'cantidad',
        'estado',        
        'usuario_id',
        'fecha_inicio',
        'fecha_final_estimada',
    ];

    protected $attributes = [
        'estado' => ESTADOS_CONSUMIBLE[0],
        'cantidad' => 1
    ];

    protected $with = [
        'consumible'
    ];

    //RELATIONSHIPS
    public function pedido(){
        return $this->belongsTo(Pedido::class);
    }

    public function consumible(){
        return $this->morphTo('consumible','consumible_tipo','consumible_id');
    }

    //VALIDACIONES

    static function validarEstado($estado){
        return in_array($estado, ESTADOS_CONSUMIBLE);
    }

    static function validarTipoConsumible($tipo){
        return in_array($tipo, TIPOS_CONSUMIBLE);
    }

    static function validarCantidad($cantidad){
        return is_int($cantidad) && $cantidad > 0;
    }

    public function realizador(){
        return $this->hasOne(Usuario::class);
    }

    static function validarParametro($key,$val){
        switch ($key) {
            case 'consumible_tipo':
                $validado = self::validarTipoConsumible($val);
                break;
            case 'pedido_id':
                $validado = true;
                break;
            case 'consumible_id':
                $validado = true;
                break;
            case 'cantidad':
                $validado = self::validarCantidad($val);
                break;
            case 'estado':
                $validado = self::validarEstado($val);
                break;
            case 'usuario_id':
                $validado = true;
                break;
            case 'fecha_inicio':
                $validado = true;
                break;
            case 'fecha_final_estimada':
                $validado = true;
                break;
            default:
                $validado = parent::validarParametro($key,$val);
                break;
        }
        return $validado;
    }

    static function getTipoConsumibleError(){
        return "El tipo de consumible no es válido, debe ser uno de los siguientes: " . 
        implode(', ',TIPOS_CONSUMIBLE);
    }
    
    static function getEstadoError(){
        return "El estado no es válido, debe ser uno de los siguientes: " . 
        implode(', ',ESTADOS_CONSUMIBLE);
    } 

    static function getCantidadError(){
        return "La cantidad debe ser mayor a 0";
    } 

    static function errorAlValidar($key)
    {
        switch ($key) {
            case 'consumible_tipo':
                return self::getTipoConsumibleError();
                break;
            case 'estado':
                return self::getEstadoError();
                break;
            case 'cantidad':
                return self::getCantidadError();
            default:
                return parent::errorAlValidar($key);                   
        }
    }

    // FUNCIONES

    /**
     * getPorVentas trae todas las sumas de consumibles vendidos. Solo incluye terminados. 
     * Trae información acotada del consumible
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param string $maxOrMin determina si queremos los que más se vendieron o los que menos se vendieron
     */
    static function getPorVentas($fechaInicio, $fechaFin, $maxOrMin = 'max'){

        //traer todas las sumas de consumibles vendidos. Solo incluye terminados
        $consumibles = DetallePedido::selectRaw('sum(cantidad) as cantidad_vendida, consumible_id, consumible_tipo')
            ->where('estado','terminado')
            ->whereDate('fecha_alta','>=', $fechaInicio)             
            ->whereDate('fecha_alta','<=', $fechaFin)
            ->groupBy(['consumible_id','consumible_tipo'])
            ->orderBy(Capsule::raw('sum(cantidad)'), 'desc')
            ->get();

        //get max o min
        if($maxOrMin == 'max'){
            $consumibles = $consumibles->where('cantidad_vendida',$consumibles->max('cantidad_vendida'));
        } else {
            $consumibles = $consumibles->where('cantidad_vendida',$consumibles->min('cantidad_vendida'));
        }
        
        //remover atributos no deseados
        $consumibles->map(function ($array) {
            unset($array['consumible_id']);
            unset($array['consumible']['id']);
            unset($array['consumible']['minutos_de_preparacion']);
            unset($array['consumible']['fecha_alta']);
            unset($array['consumible']['fecha_modificacion']);
            return $array;
         });
  
        return $consumibles;
    }

}