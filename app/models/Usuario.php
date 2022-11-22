<?php

require_once './models/SoftEloquentObject.php';
require_once './interfaces/IApiValidable.php';
require_once './models/Empleado.php';
require_once './models/Pedido.php';

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Usuario
 * 
 * @SuppressWarnings(PHPMD)
 */
class Usuario extends SoftEloquentObject implements IApiValidable
{   

    protected $table = 'usuarios';

    protected $fillable = [
        'usuario', 'clave','email', 'perfil','empleado_id'
    ];

    protected $hidden = [ 'clave' ];

    //RELATIONSHIPS
    public function empleado(){
        return $this->belongsTo(Empleado::class);
    }

    public function pedidos(){
        return $this->hasMany(Pedido::class);
    }

    function __set($key, $value)
    {   
        if($key == 'clave'){
            $this[$key] = password_hash($value, PASSWORD_DEFAULT);
        } else {
            $this[$key] = $value;
        }
    }

    //VALIDACIONES
    static function validarClave($clave){

        $number = preg_match('@[0-9]@', $clave);
        $uppercase = preg_match('@[A-Z]@', $clave);
        $lowercase = preg_match('@[a-z]@', $clave);
        $specialChars = preg_match('@[^\w]@', $clave);
        
        return (strlen($clave) >= 8 && $number && $uppercase && $lowercase && $specialChars);
    }
    
    static function validarPerfil($perfil){
        return in_array($perfil, PERFILES);
    }

    static function validarParametro($key, $val)
    {
        switch($key){
            case 'usuario':
                $validado = true;
                break;
            case 'clave':
                $validado = self::validarClave($val);
                break;
            case 'email':
                $validado = true;
                break;
            case 'perfil':
                $validado = self::validarPerfil($val);
                break;
            case 'empleado_id':
                $validado = true;
                break;
            default:
                $validado = parent::validarParametro($key,$val);
        }
        return $validado;
    }

    static function getClaveError(){
        return "la clave debe tener al menos 8 caracteres y debe contener: un número, una mayúscula, una minúscula, y un caracter especial";
    }

    static function getPerfilError(){
        return "El perfil debe ser uno de los siguientes: " . implode(', ', PERFILES);
    }

    //OVERRIDES:

    static function errorAlValidar($key){
        switch ($key) {
            case 'clave':
                return self::getClaveError();
                break;
            case 'perfil':
                return self::getPerfilError();
                break;
            default:    
                return parent::errorAlValidar($key);   
        }
    }

    #region Queries

    static function getPedidosPorMozo($fechaInicio,$fechaFin){
      return  Capsule::table('pedidos as p')
      ->join('usuarios as u','p.mozo_id','=','u.id')
      ->leftJoin('empleados as e','u.empleado_id','=','e.id')
      ->select(Capsule::raw('count(p.mozo_id) as cantidad_de_pedidos, u.usuario, e.nombre, e.apellido'))
      ->whereDate('p.fecha_alta','>=', $fechaInicio)             
      ->whereDate('p.fecha_alta','<=', $fechaFin)
      ->groupBy('p.mozo_id')
      ->orderBy(Capsule::raw('count(p.mozo_id)'), 'desc')
      ->get();
    }


    function getPagosRealizados($fechaInicio,$fechaFin){
        return Capsule::table('pedidos as p')
                ->join('usuarios as u','p.realizador_pago','=','u.id')
                ->select(Capsule::raw('u.perfil, u.usuario , count(p.realizador_pago) as cantidad_de_pagos_realizados'))
                ->whereDate('p.fecha_alta','>=', $fechaInicio)             
                ->whereDate('p.fecha_alta','<=', $fechaFin)
                ->where('p.realizador_pago','=',$this->id)
                ->where('p.estado','finalizado')
                ->groupBy('p.realizador_pago')
                ->first();
    }


    function getPedidosRealizados($fechaInicio,$fechaFin){
        return Capsule::table('pedidos as p')
              ->join('usuarios as u','p.mozo_id','=','u.id')
              ->select(Capsule::raw('u.perfil, u.usuario , count(p.mozo_id) as cantidad_de_pedidos_realizados'))
              ->whereDate('p.fecha_alta','>=', $fechaInicio)             
              ->whereDate('p.fecha_alta','<=', $fechaFin)
              ->where('p.mozo_id','=',$this->id)
              ->where('p.estado','finalizado')
              ->groupBy('p.mozo_id')
              ->first();
    }

    
    function getConsumiblesCompletados($fechaInicio,$fechaFin){
        return Capsule::table('detalles_pedido as dp')
              ->join('usuarios as u','dp.usuario_id','=','u.id')
              ->select(Capsule::raw('u.perfil, u.usuario, sum(cantidad) as consumibles_completados'))
              ->whereDate('dp.fecha_alta','>=', $fechaInicio)             
              ->whereDate('dp.fecha_alta','<=', $fechaFin)
              ->where('dp.usuario_id',$this->id)
              ->groupBy('dp.usuario_id')
              ->first();
    }
    #endregion
}