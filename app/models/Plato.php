<?php

require_once './models/Consumible.php';

/**
 * Plato
 * 
 * @SuppressWarnings(PHPMD)
 */
class Plato extends Consumible
{   
    protected $table = 'platos';

    protected $attributes = [ 'apto_veganos' => 'N' ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['apto_veganos']);
    }

    //OVERRIDES:
    static function validarParametro($key,$val){
        if($key == 'apto_veganos'){
            return true;
        } else {
            return parent::validarParametro($key,$val);
        }        
    }
}