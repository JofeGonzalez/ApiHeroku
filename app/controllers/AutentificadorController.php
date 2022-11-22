<?php

require_once './middlewares/AutentificadorJWT.php';
require_once './functions/validateKeys.php';
require_once './models/Usuario.php';

use GuzzleHttp\Psr7\Response;


class AutentificadorController{

    public static function regenerarToken($request, $response, $args){

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        try {
            AutentificadorJWT::verificarToken($token);
            $payload = json_encode(array('mensaje' => "el token actual es válido"));
            $status = 200;
        } catch (Exception $e) {
            if($e->getMessage() == "Expired token"){
                list($header, $payload, $signature) = explode(".", $token);
                $payload = json_decode(base64_decode($payload));
                $payload = json_encode(array('nuevo token' => AutentificadorJWT::CrearToken($payload->data)));
                $status = 201;
            } else {
                $payload = json_encode(array('error' => $e->getMessage()));
                $status = 400;
            }
        }

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public static function verificarCredenciales($request, $handler)
    {
        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);
        $esValido = false;

        try {
            AutentificadorJWT::verificarToken($token);
            $esValido = true;
        } catch (Exception $e) {
            $payload = json_encode(array('error' => $e->getMessage()));
        }

        if ($esValido) {
            $response = $handler->handle($request);
        } else {
            $response->getBody()->write($payload);
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarAdmin($request, $handler){

        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        if(isset($usuario) && $usuario->perfil === 'admin'){
            $response = $handler->handle($request);
        } else {
            $payload = json_encode(array('error' => "permiso denegado"));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarSocio($request, $handler){

        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        if(isset($usuario) && 
        ($usuario->perfil === 'admin' || $usuario->perfil === 'socio'))
        {
            $response = $handler->handle($request);
        } else {
            $payload = json_encode(array('error' => "permiso denegado"));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    
    public static function verificarMozo($request, $handler){

        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        if(isset($usuario) && 
        ($usuario->perfil === 'admin' || $usuario->perfil === 'socio' || $usuario->perfil === 'mozo'))
        {
            $response = $handler->handle($request);
        } else {
            $payload = json_encode(array('error' => "permiso denegado"));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarCocinero($request, $handler){

        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        if(isset($usuario) && 
        ($usuario->perfil === 'admin' || $usuario->perfil === 'socio' || $usuario->perfil === 'cocinero'))
        {
            $response = $handler->handle($request);
        } else {
            $payload = json_encode(array('error' => "permiso denegado"));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarBartender($request, $handler){

        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        if(isset($usuario) && 
        ($usuario->perfil === 'admin' || $usuario->perfil === 'socio' || $usuario->perfil === 'bartender'))
        {
            $response = $handler->handle($request);
        } else {
            $payload = json_encode(array('error' => "permiso denegado"));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarCervecero($request, $handler){

        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        if(isset($usuario) && 
        ($usuario->perfil === 'admin' || $usuario->perfil === 'socio' || $usuario->perfil === 'cervecero'))
        {
            $response = $handler->handle($request);
        } else {
            $payload = json_encode(array('error' => "permiso denegado"));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarUsuario($request, $handler){
        $response = new Response();

        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);

        $data = AutentificadorJWT::ObtenerData($token);

        $usuario = Usuario::firstWhere('usuario',$data->usuario);

        $parametros = $request->getParsedBody();

        if(isset($usuario)){            
            if(array_key_exists('id', $parametros)){
                /*
                rechazar petición si se quiere modificar password y perfil. Solo el mismo usuario puede modificar su contraseña, 
                pero nunca podrá modificar su perfil
                */
                if(array_keys_exist(['clave','perfil'],$parametros)){
                    $payload = json_encode(array('error' => "Ningún usuario puede cambiar clave y perfil al mismo tiempo"));
                    $response->getBody()->write($payload);
                    //$response = $handler->handle($request);
                } else if(array_key_exists('clave',$parametros)){
                    //verificar self
                    $usuarioAModificar = Usuario::firstWhere('id',$parametros['id']); 
                    if($usuarioAModificar == $usuario){
                        $response = $handler->handle($request);
                    } else {
                        $payload = json_encode(array('error' => "Solo el propio usuario puede modificar su clave"));
                        $response->getBody()->write($payload);
                    }
                } else if(array_key_exists('perfil',$parametros)){
                    //verificar admin
                    if($usuario->perfil === 'admin'){
                        $response = $handler->handle($request);
                    } else {
                        $payload = json_encode(array('error' => "permiso denegado"));
                        $response->getBody()->write($payload);
                    }
                } else {
                    //verificar socio
                    if($usuario->perfil === 'admin' || $usuario->perfil === 'socio'){
                        $response = $handler->handle($request);
                    } else {
                        $payload = json_encode(array('error' => "permiso denegado"));
                        $response->getBody()->write($payload);
                    }
                }
            } else {
                $payload = json_encode(array('error' => "No se recibió ID del usuario a modificar"));
                $response->getBody()->write($payload);
            }
        } else {
            $payload = json_encode(array('error' => 'permiso denegado'));
            $response->getBody()->write($payload);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function crearNuevoToken($request, $response, $args){
        $parametros = $request->getParsedBody();

        if(array_keys_exist(['usuario','clave'],$parametros)){
  
          $usuario = Usuario::firstWhere('usuario',$parametros['usuario']);
          
          if(isset($usuario)){

            if(password_verify($parametros['clave'],$usuario->clave)){
                $usuarioParams['usuario'] = $usuario->usuario;
                $usuarioParams['clave'] = $usuario->clave;
                $usuarioParams['email'] = $usuario->email;   
    
                $payload =  json_encode(array("nuevo token" => AutentificadorJWT::CrearToken($usuarioParams)));
            } else {
                $payload = json_encode(array("error" => 'Clave incorrecta'));
            }
          } else {
            $payload = json_encode(array("error" => Usuario::errorNoEncontrado()));
          }     
  
        } else {
          $payload = json_encode(array("error" => "Parámetros inválidos"));
        }
  
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}