<?php

require_once './models/Trago.php';

/**
 * Cerveza
 * 
 * @SuppressWarnings(PHPMD)
 */
class Cerveza extends Trago
{   
    protected $table = 'cervezas';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['variedad']);
    }

    //OVERRIDES:
    static function validarParametro($key,$val){
        if($key == 'variedad'){
            return true;
        } else {
            return parent::validarParametro($key,$val);
        }        
    }
}