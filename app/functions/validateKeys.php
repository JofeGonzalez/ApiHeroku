<?php

/**
 * array_keys_exist implement array_key_exists pero para muchas keys
 * 
 * @param array keys
 * @param array array
 */
function array_keys_exist($keys,$array){
    //
    $result = true;
    
    if(is_array($keys) && is_array($array)){
        foreach ($keys as $value) {
            if (!array_key_exists($value,$array)){
                $result = false;
                break;
            }
        }
    } else {
        $result = false;
    }
    //
    return $result;
}

