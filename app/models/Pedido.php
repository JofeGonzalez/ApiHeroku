<?php

require_once './models/EloquentObject.php';
require_once './interfaces/IApiValidable.php';
require_once './models/Usuario.php';
require_once './models/Mesa.php';
require_once './models/DetallePedido.php';


class Pedido extends EloquentObject implements IApiValidable {

    protected $table = 'pedidos';

    protected $fillable = [
        'codigo',
        'mesa_id',
        'estado',
        'mozo_id',
        'precio',
        'fecha_final_esperada',
        'realizador_pago',
        'url_foto',
        'fecha_final'
    ];

    protected $attributes = [ 'estado' => ESTADOS_PEDIDO[0]];

    protected $with = ['items'];

    //RELATIONSHIPS
    public function mesa(){
        return $this->belongsTo(Mesa::class);
    }

    public function mozo(){
        return $this->belongsTo(Usuario::class);
    }   

    public function realizadorPago(){
        return $this->belongsTo(Usuario::class,"realizador_pago",'id');
    }

    public function items(){
        return $this->hasMany(DetallePedido::class);
    }
    
    static function validarEstado($estado){
        return in_array($estado, ESTADOS_PEDIDO);
    }

    static function generarCodigo(){
        return substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'),0,5);
    }

    static function validarParametro($key, $val)
    {
        switch($key){
            case 'codigo':
                $validado = true;
            break;
            case 'mesa_id':
                $validado = true;
                break;
            case 'estado':
                $validado = self::validarEstado($val);
                break;
            case 'mozo_id':
                $validado = true;
                break;
            case 'precio':
                $validado = true;
                break;
            case 'fecha_final_esperada':
                $validado = true;
                break;
            case 'realizador_pago':
                $validado = true;
                break;
            case 'url_foto':
                $validado = true;
                break;
            case 'fecha_final':
                $validado = true;
                break;
            default:
                $validado = parent::validarParametro($key,$val);
        }
        return $validado;
    }

    static function getCodigoError(){
        return "El código es inválido. Solo puede contener números y letras y debe tener 5 caracteres";
    }
    
    static function getEstadoPedidoError(){
        return "El estado del pedido no es válido, debe ser uno de los siguientes: " . 
        implode(', ',ESTADOS_PEDIDO);
    } 

    static function errorAlValidar($key)
    {
        switch ($key) {
            case 'codigo':
                return self::getCodigoError();
                break;
            case 'estado':
                return self::getEstadoPedidoError();
                break;
            default:
                return parent::errorAlValidar($key);                   
        }
    }

    /**
     * Si cada item del pedido está terminado o cancelado, entonces se puede servir.
     */
    function listoParaServir(){
        $pedidoListoParaServir = true;
        foreach ($this->items as $item) {
          if($item->estado != ESTADOS_CONSUMIBLE[2] && $item->estado != ESTADOS_CONSUMIBLE[3]){
            $pedidoListoParaServir = false;
            break;
          }
        }
        return $pedidoListoParaServir;
    }

    //Devuelve true si NO hay consumibles en estado 'pedido'
    function estaEnPreparacion(){
        $enPreparacion = true;
        foreach ($this->items as $item) {
          if($item->estado == ESTADOS_CONSUMIBLE[0]){
            $enPreparacion = false;
            break;
          }
        }
        return $enPreparacion;
    }

    function obtenerFechaFinalEsperada(){
        $firstIteration = true;
        $fechaMasGrande = null;
        foreach ($this->items as $item) {
            //solo quiero los items que están en preparacion para calcular la fecha esperada de finalizacion.
            if($item->estado == ESTADOS_CONSUMIBLE[1]){
                if($firstIteration){
                    $fechaMasGrande = $item->fecha_final_estimada;
                    $firstIteration = false;
                }
                if($item->fecha_final_estimada > $fechaMasGrande){
                    $fechaMasGrande = $item->fecha_final_estimada;
                }
            }            
        }
        return $fechaMasGrande;
    }

    function seAtraso(){
        return $this->fecha_final_esperada < $this->fecha_final;
    }

    static function getAtrasados($fechaInicio,$fechaFin){

        $pedidos = Pedido::selectRaw('id, codigo, mesa_id, mozo_id, fecha_final_esperada, fecha_final, TIMESTAMPDIFF(MINUTE, fecha_final_esperada, fecha_final) as minutos_de_atraso')
            ->where('estado','finalizado')
            ->whereDate('fecha_alta','>=', $fechaInicio)             
            ->whereDate('fecha_alta','<=', $fechaFin)
            ->withOut('items')
            ->get();     
        $pedidosAtrasados = $pedidos->filter->seAtraso()->values();
        return $pedidosAtrasados;
      }

      static function getCancelados($fechaInicio,$fechaFin){

        $pedidos = Pedido::selectRaw('id, codigo, mesa_id, mozo_id, precio, fecha_alta')
            ->where('estado','cancelado')
            ->whereDate('fecha_alta','>=', $fechaInicio)             
            ->whereDate('fecha_alta','<=', $fechaFin)
            ->withOut('items')
            ->get();     
        return $pedidos;
      }
}
