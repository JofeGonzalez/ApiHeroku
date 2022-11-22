<?php

/**
 * validateDate valida que la fecha venga en formato requerido, default = YYYY-MM-DD
 * 
 * @param string date fecha a validar
 * @param string format formato para validar la fecha
 */
function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);

    return $d && $d->format($format) === $date;
}