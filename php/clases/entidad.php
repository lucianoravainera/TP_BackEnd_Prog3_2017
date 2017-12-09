<?php
include_once ("ingreso.php");

class entidad
{
	
	public $ID;
	public $nombre;
	public $apellido;
	public $sexo;
	public $perfil;
	public $turno;
	public $email;
	public $password;
	public $habilitado;
	public $fecha_creado;
	public $foto;
	public $discapacitados;
	private static $db = "Estacionamiento_2017";

	public static function BuscarUsuario($email, $pwd)
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
		$consulta = $objetoAccesoDato->RetornarConsulta("select * from ".self::$db.".usuarios where email = :email and password = :pwd;");
		$consulta->bindValue(':email', $email, PDO::PARAM_STR);
		$consulta->bindValue(':pwd', $pwd, PDO::PARAM_STR);
		$consulta->execute();
		$entidadBuscado = $consulta->fetchObject('entidad');
		return $entidadBuscado;
	}

  	public function borrarEntidad()
	{
	 	$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
		$consulta = $objetoAccesoDato->RetornarConsulta("delete from ".self::$db.".usuarios WHERE id =:id;");

		$consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
		$consulta->execute();
		return $consulta->rowCount();
	}

	public function HabilitarUsuario() // habilitar o deshabilitar un usuario, baja lógica
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
		$consulta =$objetoAccesoDato->RetornarConsulta(
			"UPDATE ".self::$db.".usuarios SET habilitado = :habilitado WHERE ID = :id;"
		);

		$consulta->bindValue(':id', $this->ID, PDO::PARAM_INT);
		$consulta->bindValue(':habilitado', $this->habilitado, PDO::PARAM_INT);
		$consulta->execute();
		return ($consulta->rowCount() > 0);
	}

	public function InsertarParametros()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();

		$query = "INSERT into ".self::$db.".usuarios (nombre, apellido, sexo, turno, email, password"
		. (isset($this->perfil) ? ", perfil" : "")
		. (isset($this->foto) ? ", foto" : "")
		. (isset($this->habilitado) ? ", habilitado" : "")
		. ") values (:nombre, :apellido, :sexo, :turno, :email, :password"
		. (isset($this->perfil) ? ", :perfil" : "")
		. (isset($this->foto) ? ", :foto" : "")
		. (isset($this->habilitado) ? ", :habilitado" : "")
		. ");";

		//var_dump($query); die();

		$consulta = $objetoAccesoDato->RetornarConsulta($query);

		$consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
		$consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
		$consulta->bindValue(':sexo', $this->sexo, PDO::PARAM_INT);
		$consulta->bindValue(':turno', $this->turno, PDO::PARAM_STR);
		$consulta->bindValue(':email', $this->email, PDO::PARAM_STR);
		$consulta->bindValue(':password', $this->password, PDO::PARAM_STR);

		if(isset($this->perfil)) $consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
		if(isset($this->foto)) $consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
		if(isset($this->habilitado)) $consulta->bindValue(':habilitado', $this->habilitado, PDO::PARAM_INT);

		$consulta->execute();
		return $objetoAccesoDato->RetornarUltimoIdInsertado();
	}

  	public static function TraerTodoLosUsuarios()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
		$consulta =$objetoAccesoDato->RetornarConsulta("select * from ".self::$db.".usuarios");
		$consulta->execute();
		return $consulta->fetchAll(PDO::FETCH_CLASS, "entidad");
	}

	public function GuardarIngreso($id)
	{
		if(!is_numeric($id))
		{
			$obj = new stdClass();
			$obj->error = "ID invalido.";
			return ($obj);
		}
		
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
		
		$query = "SET time_zone = '-3:00';"; // zona horaria ARG distinta server
		$consulta = $objetoAccesoDato->RetornarConsulta($query);
		$consulta->execute();

		$ingreso = new ingreso();
		$ingreso->ID = $id;

		$query = "INSERT into ".self::$db.".ingresos (ingreso_usuarioID) values (:id);";
		$consulta = $objetoAccesoDato->RetornarConsulta($query);
		$consulta->bindValue(':id', $ingreso->ID, PDO::PARAM_INT);
		$consulta->execute();
		//return $objetoAccesoDato->RetornarUltimoIdInsertado();
		return $consulta->rowCount();
	}

	public static function TraerIngresos($id = null, $desde = null, $hasta = null)
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
		
		if($id != null)
		{
			if(!is_numeric($id))
			{
				$obj = new stdClass();
				$obj->error = "ID invalido.";
				return ($obj);
			}
			//$query = "select ID, ingreso_usuarioID, fecha_ingreso from ".self::$db.".ingresos where ingreso_usuarioID = :id;";
			$query = "SELECT ing.ID, ing.ingreso_usuarioID, usu.nombre, usu.apellido, ing.fecha_ingreso FROM ".self::$db.".ingresos as ing, ".self::$db.".usuarios as usu WHERE ing.ingreso_usuarioID = usu.ID and ing.ingreso_usuarioID = :id order by ing.fecha_ingreso asc;";
		}
		elseif($desde != null && ($hasta != null))
		{
			//$query = "select * from ".self::$db.".ingresos where (fecha_ingreso between :desde and :hasta);";
			$query = "SELECT ing.ID, ing.ingreso_usuarioID, usu.nombre, usu.apellido, ing.fecha_ingreso FROM ".self::$db.".ingresos as ing, ".self::$db.".usuarios as usu WHERE ing.ingreso_usuarioID = usu.ID and (ing.fecha_ingreso between :desde and :hasta) order by ing.fecha_ingreso asc;";
		}
		else
		{
			//$query = "select * from ".self::$db.".ingresos";
			$query = "SELECT ing.ID, ing.ingreso_usuarioID, usu.nombre, usu.apellido, ing.fecha_ingreso FROM ".self::$db.".ingresos as ing, ".self::$db.".usuarios as usu WHERE ing.ingreso_usuarioID = usu.ID order by ing.fecha_ingreso asc;";
		}
			
		$consulta = $objetoAccesoDato->RetornarConsulta($query);
		if($id != null) $consulta->bindValue(':id', $id, PDO::PARAM_INT);
		if($desde != null && $hasta != null)
		{
			$consulta->bindValue(':desde', $desde, PDO::PARAM_STR);
			$consulta->bindValue(':hasta', $hasta, PDO::PARAM_STR);
		}
		$consulta->execute();
		$entidadBuscado = $consulta->fetchAll(PDO::FETCH_CLASS, "ingreso");

		return $entidadBuscado;				
	}
	
}
