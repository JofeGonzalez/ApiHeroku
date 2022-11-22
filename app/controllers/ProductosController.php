<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \App\models\Producto as Producto;

require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';
class ProductosController implements IApiUsable
{
  // private function ActualizarProductos($listaNueva)
  // {
  //   if (isset($listaNueva)) {
  //     $listaActual = Producto::all();
  //     foreach ($listaNueva as $prodNuevo) {
  //       $flagExists = 0;
  //       foreach ($listaActual as $prodViejo) {
  //         if ($this->Equals($prodNuevo, $prodViejo)) {
  //           $this->ModificarStock($prodNuevo->Stock, $prodViejo);
  //           $this->ModificarPrecio($prodNuevo->Precio, $prodViejo);
  //           $prodViejo->FechaUltimaModificacion = date("Y-m-d");
  //           $prodViejo->save();
  //           $flagExists = 1;
  //           continue;
  //         }
  //       }
  //       if ($flagExists == 0) {
  //         $newProducto = new Producto();
  //         $newProducto->Codigo = $prodNuevo->Codigo;
  //         $newProducto->TipoProductoId = $prodNuevo->TipoProductoId;
  //         $newProducto->Nombre = $prodNuevo->Nombre;
  //         $newProducto->Stock = $prodNuevo->Stock;
  //         $newProducto->Precio = $prodNuevo->Precio;
  //         $newProducto->FechaCreacion = date("Y-m-d");
  //         $newProducto->save();
  //       }
  //     }
  //   }
  // }
  public function ModificarPrecio($precioNuevo, $productoViejo)
  {
    if ($productoViejo->Precio < $precioNuevo) {
      $productoViejo->Stock = $precioNuevo;
    }
  }
  public function ModificarStock($cantidad, $productoViejo)
  {
    $productoViejo->Stock += $cantidad;
  }
  public function Equals($prod1, $prod2)
  {
    return $prod1->Codigo == $prod2->Codigo;
  }
  public function TraerUno(Request $request, Response $response, array $args)
  {
    try {
      $id = $args["id"];
      $producto = Producto::where('Id', '=', $id)->first();
      if (is_null($producto)) {
        throw new Exception("El producto no existe");
      }
      $datos = json_encode($producto);
      $response->getBody()->write($datos);
      return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
    } catch (Exception $ex) {
      $error = $ex->getMessage();
      $datosError = json_encode(array("Error" => $error));
      $response->getBody()->write($datosError);
      return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }
  public function TraerTodos(Request $request, Response $response, array $args)
  {
    try {
      $datos = json_encode(Producto::all());
      $response->getBody()->write($datos);
      return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
    } catch (Exception $ex) {
      $error = $ex->getMessage();
      $datosError = json_encode(array("Error" => $error));
      $response->getBody()->write($datosError);
      return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }
  public function CargarUno(Request $request, Response $response, array $args)
  {
    try {
      $datosIngresados = $request->getParsedBody()["body"];
      if (
        !isset($datosIngresados["codigo"])
        || !isset($datosIngresados["tipoProductoId"])
        || !isset($datosIngresados["nombre"])
        || !isset($datosIngresados["stock"])
        || !isset($datosIngresados["precio"])
      ) {
        $error = json_encode(array("Error" => "Datos incompletos"));
        $response->getBody()->write($error);
        return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(404);
      }
      $codigo = $datosIngresados["codigo"];
      $tipoProductoId = $datosIngresados["tipoProductoId"];
      $nombre = $datosIngresados["nombre"];
      $stock = $datosIngresados["stock"];
      $precio = $datosIngresados["precio"];
      $newProducto = new Producto();
      $newProducto->Codigo = $codigo;
      $newProducto->TipoProductoId = $tipoProductoId;
      $newProducto->Nombre = $nombre;
      $newProducto->Stock = $stock;
      $newProducto->Precio = $precio;
      $newProducto->FechaCreacion = date("Y-m-d");
      if ($newProducto->save()) {
        $payload = json_encode(array("Resultado" => "Agregado"));
      }
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
    } catch (Exception $ex) {
      $error = $ex->getMessage();
      $datosError = json_encode(array("Error" => $error));
      $response->getBody()->write($datosError);
      return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(500);
    }
  }
  public function BorrarUno(Request $request, Response $response, array $args)
  {
  }
  public function ModificarUno(Request $request, Response $response, array $args)
  {
  }
}
