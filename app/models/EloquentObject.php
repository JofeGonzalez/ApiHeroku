<?php

use Illuminate\Database\Eloquent\Model;

require_once './exceptions/parametrosInvalidosException.php';

class EloquentObject extends Model {

    protected $clavePrimaria = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [];

    const CREATED_AT = 'fecha_alta';
    const UPDATED_AT = 'fecha_modificacion';  

    //cambio de timezone para cuando lo accedemos manualmente
    public function __get($key)
    {
        if($key == 'fecha_alta' || $key == 'fecha_modificacion'){
            return Carbon\Carbon::parse($this[$key])->timezone(TIMEZONE);
        } else {
            return $this[$key];
        }
    }

    //cambio de timezone para cuando lo serializamos a json
    protected $casts = [
        'fecha_alta' => 'datetime:Y-m-d H:i:s',
        'fecha_modificacion' => 'datetime:Y-m-d H:i:s'
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon\Carbon::parse($date)->timezone(TIMEZONE);
    }

    //ERRORES

    static function errorAlValidar($key){
        return "parámetro inválido: ${key}";
    }

    static function exitoAlCrear(){
        return "se creó con éxito";
    }

    static function exitoAlModificar(){
        return "se modificó con éxito";
    }

    static function exitoAlBorrar(){
        return "se borró con éxito";
    }

    static function errorYaExiste(){
        return "ya existe";
    }

    static function errorNoEncontrado(){
        return "no existe";
    }

    static function validarParametro($key,$value){
        if($key == 'id'){
            return true;
        } else {
            return false;
        }
    }

    #region CRUD
    
    public static function crear($parametros)
    {   
        $type = static::class;

        // Creamos la instancia
        $obj = new $type();
        try {
            foreach ($parametros as $key => $value) {
                if(static::class::validarParametro($key, $value)){
                    $obj->$key = $value;
                } else {
                    throw new parametrosInvalidosException(static::class::errorAlValidar($key));
                }
            }
            $obj->save();

            $payload = json_encode(array("mensaje" => static::class::exitoAlCrear(), "id" => $obj->id)); 
        } 
        catch (Exception $ex) {
            $payload = json_encode(array("error" => $ex->getMessage())); 
        }
    return $payload;
    }

    public static function fetch($valorUnico, $clavePrimaria = 'id')
    {
        // Buscamos instancia
        $obj = static::class::firstWhere($clavePrimaria, $valorUnico);

        return json_encode($obj ?: array("error" => static::class::errorNoEncontrado()));
    }

    public static function fetchAll()
    {
        $lista = static::class::all();

        return json_encode(array("lista" => $lista));
    }

    public function modificar($atributos)
    {   
        try {
            foreach ($atributos as $key => $value) {
                if(static::class::validarParametro($key, $value)){
                    $this->$key = $value;
                } else {
                    throw new parametrosInvalidosException(static::class::errorAlValidar($key));
                }
            }
            $this->save();
            $payload = json_encode(array("mensaje" => static::class::exitoAlModificar()));
        } 
        catch (Exception $ex) {
            $payload = json_encode(array("error" => $ex->getMessage()));
        }

    return $payload;
    }

    public function borrar(){      
        $this->delete();
    }

    #endregion
}