<?php
require_once './models/Bebida.php';
require_once './controllers/AbstractController.php';

/**
 * BebidaController
 * 
 */
class BebidaController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Bebida::class;     
      $this->obligatoryParameters = ['nombre','precio','minutos_de_preparacion','mililitros'];
    }
}