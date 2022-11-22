<?php

use Carbon\Carbon as Carbon;

/**
 * setea las fechas de inicio y fin para buscar en un rango, recibidas por parámetros.
 * Puede recibir:
 * 1) fecha -> el rango será de 00:00-23:59hs de esa fecha
 * 2) fecha_min -> el rango será de esa fecha a las 00:00hs-infinito
 * 3) fecha_max -> el rango será de -infinito hasta las 23:59hs de esa fecha
 * 4) recibe ambas fechas y devuelve el start of day y end of day respectivamente.
 * 
 * @param mixed array que contiene fechas en formato d/m/y
 */
function setFechasInicioFin($parametros){

    $fechaInicio = null;
    $fechaFin = null;

    if(isset($parametros)){
        //Nos pasaron una fecha específica
        if(array_key_exists('fecha',$parametros)){
            $fechaInicio = Carbon::createFromFormat('d/m/Y',$parametros['fecha'])->startOfDay();
            $fechaFin = Carbon::createFromFormat('d/m/Y',$parametros['fecha'])->endOfDay();
        } else {
            //nos pasaron un rango de fechas
            if(array_keys_exist(['fecha_min','fecha_max'], $parametros)){
                $fechaInicio = Carbon::createFromFormat('d/m/Y',$parametros['fecha_min'])->startOfDay();
                $fechaFin = Carbon::createFromFormat('d/m/Y',$parametros['fecha_max'])->endOfDay();
            }
            //nos pasaron solo fecha minima
            else if(array_key_exists('fecha_min', $parametros)){
                $fechaInicio = Carbon::createFromFormat('d/m/Y',$parametros['fecha_min'])->startOfDay();
                $fechaFin = Carbon::maxValue();
            }
            //nos pasaron solo fecha máxima
            else if(array_key_exists('fecha_max', $parametros)){
                $fechaInicio = Carbon::minValue();
                $fechaFin = Carbon::createFromFormat('d/m/Y',$parametros['fecha_max'])->endOfDay();
            }
        }
        return [$fechaInicio, $fechaFin];
    } else {
        return null;
    }   
}