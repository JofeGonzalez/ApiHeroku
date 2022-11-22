<?php
require_once './models/Plato.php';
require_once './controllers/AbstractController.php';

/**
 * PlatoController
 * 
 */
class PlatoController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Plato::class;     
      $this->obligatoryParameters = ['nombre','precio','minutos_de_preparacion'];
    }
}
