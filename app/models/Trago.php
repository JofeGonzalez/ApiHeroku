<?php

require_once './models/Bebida.php';

/**
 * Trago
 * 
 * @SuppressWarnings(PHPMD)
 */
class Trago extends Bebida
{   
    protected $table = 'tragos';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['porcentaje_alcohol']);
    }

    //OVERRIDES:
    static function validarParametro($key,$val){
        if($key == 'porcentaje_alcohol'){
            return true;
        } else {
            return parent::validarParametro($key,$val);
        }        
    }
}