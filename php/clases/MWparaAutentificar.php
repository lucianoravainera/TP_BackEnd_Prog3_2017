<?php
require_once "AutentificadorJWT.php";

class MWparaAutentificar
{
	public function VerificarUsuario($request, $response, $next) {
		if(isset($request->getHeader('token')[0])) // si envia token en el header
		{
			$token = $request->getHeader('token')[0];
		}
		else
		{
			$error = array('tipo' => 'acceso', 'descripcion' => "Acceso denegado no hay token en el header.");
			return $response->withJson( $error , 403);
		}
	
		try
		{
			AutentificadorJWT::VerificarToken($token);
			$newResponse = $next($request, $response);
		}
		catch (Exception $e) 
		{
			$textoError="error ".$e->getMessage();
			$error = array('tipo' => 'acceso','descripcion' => $textoError);
			$newResponse = $response->withJson( $error , 403); 
		}
		
		return $newResponse;
	}

	public static function VerificarToken($token) {
		try
		{
			$payload = AutentificadorJWT::ObtenerPayLoad($token);
			
			$response = json_encode($payload->data);
		} 
		catch (Exception $e)
		{
			$textoError = "error ".$e->getMessage();
			$error = array('tipo' => 'acceso','descripcion' => $textoError);
			$response = json_encode($error);
		}

		return $response;   
	}
}