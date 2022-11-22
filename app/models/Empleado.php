<?php

require_once './models/SoftEloquentObject.php';
require_once './interfaces/IApiValidable.php';
require_once './models/Usuario.php';
require_once './functions/validateDate.php';


/**
 * Empleado
 * 
 */
class Empleado extends SoftEloquentObject implements IApiValidable
{   

    protected $table = 'empleados';

    protected $fillable = [
        'nombre', 
        'apellido',
        'rol', 
        'dni',
        'fecha_nacimiento',
        'direccion',
        'email'
    ];

    // RELATIONSHIP
    public function usuarios(){
        return $this->hasMany(Usuario::class);
    }

    //VALIDACIONES

    static function validarDNI($dni){
        return is_numeric($dni) && strlen($dni) == 8;
    }

    static function validarParametro($key, $val)
    {
        switch($key){
            case 'dni':
                $validado = self::validarDNI($val);
                break;
            case 'nombre':
                $validado = true;
                break;
            case 'apellido':
                $validado = true;
                break;
            case 'rol':
                $validado = true;
                break;
            case 'fecha_nacimiento':
                $validado = validateDate($val);
                break;
            case 'direccion':
                $validado = true;
                break;
            case 'email':
                $validado = true;
                break;
            default:
                $validado = parent::validarParametro($key,$val);
        }
        return $validado;
    }

    static function getDNIError(){
        return "El dni ingresado no cumple con el formato adecuado: numero de 8 caracteres";
    }

    //OVERRIDES:

    static function errorAlValidar($key){
        switch ($key) {
            case 'dni':
                return self::getDNIError();
                break;
            case 'fecha_nacimiento':
                return "La fecha es inválida o no está en el formato correcto: YYYY-MM-DD";
                break;
            default:
                return parent::errorAlValidar($key);                   
        }
    }
}