<?php
require_once './models/Postre.php';
require_once './controllers/AbstractController.php';


/**
 * PostreController
 * 
 */
class PostreController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Postre::class;     
      $this->obligatoryParameters = ['nombre','precio','minutos_de_preparacion'];
    }
}