<?php

require_once './models/EloquentObject.php';
require_once './interfaces/IApiValidable.php';

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Mesa
 * 
 * @SuppressWarnings(PHPMD)
 */
class Mesa extends EloquentObject implements IApiValidable
{   
    protected $table = 'mesas';

    protected $fillable = [
        'codigo', 'estado'
    ];

    protected $attributes = [
        'estado' => 'cerrada'
    ];

    //RELACIONES
    public function pedidos(){
        return $this->hasMany(Pedido::class);
    }

    //VALIDACIONES
    static function validarCodigo($codigo){
        return strlen($codigo) === 5 && ctype_alnum($codigo);
    }
    
    static function validarEstado($estado){
        return in_array($estado, ESTADOS_MESA);
    }

    static function validarParametro($key,$val){
        switch ($key) {
            case 'codigo':
                $validado =  self::validarCodigo($val);
                break;
            case 'estado':
                $validado = self::validarEstado($val);
                break;
            default:
                $validado = parent::validarParametro($key,$val);           
        }
        return $validado;
    } 

    static function getCodigoError(){
        return "El código es inválido. Solo puede contener números y letras y debe tener 5 caracteres";
    }
    
    static function getEstadoMesaError(){
        return "El estado de la mesa no es válido, debe ser uno de los siguientes: " . 
        implode(', ',ESTADOS_MESA);
    }    

    //OVERRIDES:

    static function errorAlValidar($key){
        switch ($key) {
            case 'codigo':
                return self::getCodigoError();
                break;
            case 'estado':
                return self::getEstadoMesaError();
                break;
            default:
                return parent::errorAlValidar($key);                   
        }
    }

    //funciones
    
    /**
     * Trae las mesas más o menos usadas
     *
     * @param mixed  $fechaInicio incluye el dia
     * @param mixed  $fechaFin incluye el dia
     * @param string $maxOrMin determina si queremos las que más se usaron o las que menos se usaron
     */ 
    static function getPorUso($fechaInicio, $fechaFin, $maxOrMin = 'max'){
        $mesas = Capsule::table('pedidos as p')
            ->join('mesas as m','p.mesa_id','=','m.id')
            ->select(Capsule::raw('count(p.mesa_id) as cantidad_de_pedidos, m.codigo'))
            ->where('p.estado','finalizado')
            ->whereDate('p.fecha_alta','>=', $fechaInicio)             
            ->whereDate('p.fecha_alta','<=', $fechaFin)
            ->groupBy('p.mesa_id')
            //->orderBy(Capsule::raw('count(p.mesa_id)'), 'desc')
            ->get();
  
        //tomar las que mas pedidos tienen
        if ($maxOrMin == 'max'){
            $maxOrMin = $mesas->max('cantidad_de_pedidos');
        } else {
            $maxOrMin = $mesas->min('cantidad_de_pedidos');
        }

        $mesas = $mesas->where('cantidad_de_pedidos',$maxOrMin);
  
        return $mesas;
    }

    static function getTodasPorFacturacion($fechaInicio, $fechaFin){
        $mesas = Capsule::table('pedidos as p')
            ->join('mesas as m','p.mesa_id','=','m.id')
            ->select(Capsule::raw('sum(p.precio) as facturacionTotal, m.codigo'))
            ->where('p.estado','finalizado')
            ->whereDate('p.fecha_alta','>=', $fechaInicio)             
            ->whereDate('p.fecha_alta','<=', $fechaFin)
            ->groupBy('p.mesa_id')
            ->orderBy(Capsule::raw('sum(p.precio)'), 'desc')
            ->get();
        return $mesas;
    }


    static function getPorFacturacion($fechaInicio, $fechaFin, $maxOrMin = 'max'){
        $mesas = self::getTodasPorFacturacion($fechaInicio, $fechaFin);
  
        //tomar las que mas pedidos tienen
        if ($maxOrMin == 'max'){
            $maxOrMin = $mesas->max('facturacionTotal');
        } else {
            $maxOrMin = $mesas->min('facturacionTotal');
        }

        $mesas = $mesas->where('facturacionTotal',$maxOrMin);
  
        return $mesas;
    }


    static function getUnaPorFacturacion($idMesa,$fechaInicio, $fechaFin){
        $mesa = Capsule::table('pedidos as p')
        ->join('mesas as m','p.mesa_id','=','m.id')
        ->select(Capsule::raw('sum(p.precio) as facturacionTotal, m.codigo'))
        ->where('p.estado','finalizado')
        ->where('m.id',$idMesa)
        ->whereDate('p.fecha_alta','>=', $fechaInicio)             
        ->whereDate('p.fecha_alta','<=', $fechaFin)
        ->groupBy('p.mesa_id')
        ->get();
        return $mesa;
    }


    static function getPorImporte($fechaInicio, $fechaFin, $maxOrMin = 'max'){
  
        //tomar las que mas pedidos tienen
        if ($maxOrMin == 'max'){
            $mesas = Capsule::table('pedidos as p')
                ->join('mesas as m','p.mesa_id','=','m.id')
                ->select(Capsule::raw('max(p.precio) as mayorImporte, m.codigo'))
                ->where('p.estado','finalizado')
                ->whereDate('p.fecha_alta','>=', $fechaInicio)             
                ->whereDate('p.fecha_alta','<=', $fechaFin)
                ->groupBy('p.mesa_id')
                ->get();
            $maxOrMin = $mesas->max('mayorImporte');

            $mesas = $mesas->where('mayorImporte',$maxOrMin);
        } else {
            $mesas = Capsule::table('pedidos as p')
                ->join('mesas as m','p.mesa_id','=','m.id')
                ->select(Capsule::raw('min(p.precio) as menorImporte, m.codigo'))
                ->where('p.estado','finalizado')
                ->whereDate('p.fecha_alta','>=', $fechaInicio)             
                ->whereDate('p.fecha_alta','<=', $fechaFin)
                ->groupBy('p.mesa_id')
                ->get();
            $maxOrMin = $mesas->min('menorImporte');

            $mesas = $mesas->where('menorImporte',$maxOrMin);
        }  
        return $mesas;
    }


    static function getPorComentarios($fechaInicio, $fechaFin, $maxOrMin = 'max'){
        $mesas = Capsule::table('encuestas as e')
            ->join('mesas as m','e.mesa_id','=','m.id')
            ->select(Capsule::raw('avg(e.mesa_puntaje) as puntajePromedio, m.codigo'))
            ->whereDate('e.fecha_alta','>=', $fechaInicio)             
            ->whereDate('e.fecha_alta','<=', $fechaFin)
            ->groupBy('e.mesa_id')
            ->get();
  
        //tomar las que mas pedidos tienen
        if ($maxOrMin == 'max'){
            $maxOrMin = $mesas->max('puntajePromedio');
        } else {
            $maxOrMin = $mesas->min('puntajePromedio');
        }

        $mesas = $mesas->where('puntajePromedio',$maxOrMin);
  
        return $mesas;
    }

}