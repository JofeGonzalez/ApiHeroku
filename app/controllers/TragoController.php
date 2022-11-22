<?php
require_once './models/Trago.php';
require_once './controllers/AbstractController.php';


/**
 * TragoController
 * 
 */
class TragoController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Trago::class;     
      $this->obligatoryParameters = ['nombre','precio','minutos_de_preparacion','mililitros','porcentaje_alcohol'];
    }
}