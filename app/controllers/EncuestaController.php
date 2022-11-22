<?php
require_once './models/Encuesta.php';
require_once './controllers/AbstractController.php';


/**
 * EncuestaController
 * 
 * @SuppressWarnings(PHPMD)
 */
class EncuestaController extends AbstractController
{
    function __construct()
    {
      $this->controlledClass = Encuesta::class;
      $this->obligatoryParameters = ['general_puntaje', 'mesa_id', 'mozo_id'];
    }
}
