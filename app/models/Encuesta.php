<?php

require_once './models/EloquentObject.php';
require_once './models/Empleado.php';
require_once './interfaces/IApiValidable.php';

/**
 * Encuesta
 * 
 * @SuppressWarnings(PHPMD)
 */
class Encuesta extends EloquentObject implements IApiValidable
{   
    protected $table = 'encuestas';

    protected $fillable = [
        'general_puntaje', 'mesa_id', 'mesa_puntaje', 'mozo_id', 'mozo_puntaje', 'comida_puntaje','descripcion'
    ];


    //VALIDACIONES
    static function validarPuntaje($puntaje){
        return ctype_digit($puntaje) && $puntaje >= 0 && $puntaje <= 10;
    }
    
    static function validarDescripcion($desc){
        return strlen($desc) <= 66;
    }

    static function validarParametro($key,$val){
        switch ($key) {
            case 'general_puntaje':
                $validado =  self::validarPuntaje($val);
                break;
            case 'mesa_puntaje':
                $validado = self::validarPuntaje($val);
                break;
            case 'mozo_puntaje':
                $validado = self::validarPuntaje($val);
                break;
            case 'comida_puntaje':
                $validado = self::validarPuntaje($val);
                break;
            case 'mesa_id':
                $validado = true;
                break;
            case 'mozo_id':
                $validado = true;
                break;
            case 'descripcion':
                $validado = self::validarDescripcion($val);
                break;
            default:
                $validado = parent::validarParametro($key,$val);           
        }
        return $validado;
    } 

    static function getPuntajeError(){
        return "Puntaje inválido, debe ser un numero del 0 al 10";
    }
    
    static function getDescripcionError(){
        return "La descripción no puede superar los 66 caracteres";
    }    

    //OVERRIDES:

    static function errorAlValidar($key){
        switch ($key) {
            case 'general_puntaje':
                return self::getPuntajeError();
            case 'mesa_puntaje':
                return self::getPuntajeError();
            case 'mozo_puntaje':
                return self::getPuntajeError();
            case 'comida_puntaje':
                return self::getPuntajeError();
            case 'descripcion':
                return self::getDescripcionError();
            default:
                return parent::errorAlValidar($key);                   
        }
    }
}