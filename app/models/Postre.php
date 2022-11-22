<?php

require_once './models/Consumible.php';


/**
 * Postre
 * 
 * @SuppressWarnings(PHPMD)
 */
class Postre extends Consumible
{   
    protected $table = 'postres';

    protected $attributes = [ 'apto_celiacos' => 'N' ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['apto_celiacos']);
    }

    //OVERRIDES:
    static function validarParametro($key,$val){
        if($key == 'apto_celiacos'){
            return true;
        } else {
            return parent::validarParametro($key,$val);
        }        
    }
}