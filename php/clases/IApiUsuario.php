<?php 
interface IApiUsuario{ 	
   	public function TraerTodos($request, $response, $args); 
   	public function altaUsuario($request, $response, $args);
   	public function borrarUsuario($request, $response, $args);
   	public function suspenderUsuario($request, $response, $args);
}
?>