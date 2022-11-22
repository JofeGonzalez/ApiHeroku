<?php
require_once './models/Cerveza.php';
require_once './controllers/AbstractController.php';

/**
 * CervezaController
 * 
 */
class CervezaController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Cerveza::class;     
      $this->obligatoryParameters = ['nombre','precio','minutos_de_preparacion','mililitros','porcentaje_alcohol', 'variedad'];
    }
}