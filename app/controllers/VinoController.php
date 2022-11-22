<?php
require_once './models/Vino.php';
require_once './controllers/AbstractController.php';


/**
 * VinoController
 * 
 */
class VinoController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Vino::class;     
      $this->obligatoryParameters = ['nombre','precio','minutos_de_preparacion','mililitros', 'porcentaje_alcohol', 'tipo_uva'];
    }
}