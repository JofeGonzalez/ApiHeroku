<?php

require_once './models/Trago.php';


/**
 * Vino
 * 
 * @SuppressWarnings(PHPMD)
 */
class Vino extends Trago
{   
    protected $table = 'vinos';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['tipo_uva']);
    }

    //OVERRIDES:
    static function validarParametro($key,$val){
        if($key == 'tipo_uva'){
            return true;
        } else {
            return parent::validarParametro($key,$val);
        }        
    }
}