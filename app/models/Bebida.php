<?php

require_once './models/Consumible.php';


/**
 * Bebida
 * 
 * @SuppressWarnings(PHPMD)
 */
class Bebida extends Consumible
{   
    protected $table = 'bebidas';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['mililitros']);
    }

    //OVERRIDES:
    static function validarParametro($key,$val){
        if($key == 'mililitros'){
            return true;
        } else {
            return parent::validarParametro($key,$val);
        }        
    }

}