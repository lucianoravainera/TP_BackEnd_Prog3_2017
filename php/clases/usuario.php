<?php
class usuario
{
	public static function esValido($usuario, $clave) {

	    $usuario = entidad::BuscarUsuario($usuario, $clave);

	    if(!$usuario)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
}