<?php

use Illuminate\Database\Eloquent\SoftDeletes;

require_once './exceptions/parametrosInvalidosException.php';
require_once './models/EloquentObject.php';

class SoftEloquentObject extends EloquentObject {

    use SoftDeletes;
    
    const DELETED_AT = 'fecha_baja';

    //OVERRIDE DELETE
    public function borrar(){      
        $this->forceDelete();
    }

    public function desactivar(){

        if(!$this->trashed()){
            $this->delete();
            $payload = json_encode(array("mensaje" => self::exitoAlDesactivar()));
        } else {
            $payload = json_encode(array("mensaje" => self::errorAlDesactivar()));
        }
        return $payload;
    }

    public function reactivar(){
        if($this->trashed()){
            $this->restore();
            $payload = json_encode(array("mensaje" => self::exitoAlReactivar()));
        } else {
            $payload = json_encode(array("mensaje" => self::errorAlReactivar()));
        }
        return $payload;
    }

    //Mensajes de error
    static function errorAlReactivar(){
        return "Ya se encuentra activo";
    }

    static function errorAlDesactivar(){
        return "Ya se encuentra desactivado";
    }

    //Mensajes de exito
    static function exitoAlReactivar(){
        return "Reactivado con exito";
    }
    
    static function exitoAlDesactivar(){
        return "Desactivado con exito";
    }
    
}