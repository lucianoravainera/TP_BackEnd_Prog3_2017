<?php
require_once 'entidad.php';
require_once 'IApiUsuario.php';
require_once 'MWparaAutentificar.php';
require_once "AutentificadorJWT.php";


class usuariosApi extends entidad implements IApiUsuario
{
    public function Login($request, $response, $next) {
        $token = "";
        $ArrayDeParametros = $request->getParsedBody();
    
        if(isset( $ArrayDeParametros['email']) && isset( $ArrayDeParametros['password']) )
        {
            $email = $ArrayDeParametros['email'];
            $clave = $ArrayDeParametros['password'];
            
            if(usuario::esValido($email, $clave))
            {
                
                $usuario = entidad::BuscarUsuario($email, $clave);
                if($usuario->habilitado == 0)
                {
                    $retorno = array('error'=> "Usuario deshabilitado" );
                    return $response->withJson( $retorno, 409 );
                }
                
                entidad::GuardarIngreso($usuario->ID);

                $datos = array(
                    'ID' => $usuario->ID,
                    'email' => $usuario->email, 
                    'perfil' => $usuario->perfil,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'sexo' => $usuario->sexo,
                    'turno' => $usuario->turno,
                    'foto' => $usuario->foto,
                    'habilitado' => $usuario->habilitado,
                    'fecha_creado' => $usuario->fecha_creado
                );

                $token = AutentificadorJWT::CrearToken($datos);
                $retorno = array('token' => $token );
                //var_dump($retorno); die();
                $newResponse = $response->withJson( $retorno, 200 );
            }
            else
            {
                $retorno = array('error'=> "No es usuario valido" );
                $newResponse = $response->withJson( $retorno, 409 ); 
            }
        }
        elseif ((isset($request->getHeader('token')[0]))) // para renovar el token despues del timeout
        {
            $usr = AutentificadorJWT::ObtenerPayLoad($request->getHeader('token')[0]);
            //var_dump($usr); die ();
            
            $datos = (array) $usr->data;
            $token = AutentificadorJWT::CrearToken($datos);

            $retorno = array('token' => $token );
            //var_dump($retorno); die();
            $newResponse = $response->withJson( $retorno, 200 );
        }
        else
        {
            $retorno = array('error'=> "Faltan los datos de email y clave" );
            $newResponse = $response->withJson( $retorno ,409); 
        }
    
        return $newResponse;
    }


    public function ingresos($request, $response, $args)
    {
        $Arr = $request->getParsedBody();

        if(isset($args['id']))
        {
            $id = $args['id'];
            $entidad = entidad::TraerIngresos($id);
        }
        elseif(isset($Arr['fecha_desde']) && isset($Arr['fecha_hasta']))
        {
            $desde = $Arr['fecha_desde'];
            $hasta = $Arr['fecha_hasta'];
            $entidad = entidad::TraerIngresos(null, $desde, $hasta);
        }
        else
        {
            $entidad = entidad::TraerIngresos();
        }

        if(!$entidad)
        {
            $objDelaRespuesta = new stdclass();
            $objDelaRespuesta->error = "No hay ingresos registrados.";
            $NuevaRespuesta = $response->withJson($objDelaRespuesta, 409);
        }
        else
        {
            $NuevaRespuesta = $response->withJson($entidad, 200);
        }

        return $NuevaRespuesta;
    }

    public function registrarIngreso($request, $response, $args)
    {
        $objDelaRespuesta = new stdclass();
        if(isset($args['id']))
        {
            $id = $args['id'];
            $count = entidad::GuardarIngreso($id);
            if($count == 1)
            {
                $objDelaRespuesta->respuesta = "Ingreso registrado exitosamente.";
                $NuevaRespuesta = $response->withJson($objDelaRespuesta, 200);
            }
            else
            {
                $objDelaRespuesta->error = "No se pudo registrar el ingreso.";
                $NuevaRespuesta = $response->withJson($objDelaRespuesta, 500);
            }
        }
        else
        {
            $objDelaRespuesta->error = "Consulta no valida: falta el ID de usuario.";
            $NuevaRespuesta = $response->withJson($objDelaRespuesta, 500);
        }
        
        return $NuevaRespuesta;
    }

    public function esAdmin($request, $response, $next) {
        $token = apache_request_headers()["token"];
        $usuario = json_decode(MWparaAutentificar::VerificarToken($token));
        
        if($usuario->perfil == 'admin')
        {
            $resp = $next($request, $response);
        }
        else
        {
            $arr = array('error' => 'No es administrador.');
            $resp = $response->withJson($arr, 409);
        }
        
        return $resp;
    }

    public function TraerTodos($request, $response, $args) {
        $todosLosUsuarios = entidad::TraerTodoLosUsuarios();

        $newresponse = $response->withJson($todosLosUsuarios, 200);
        return $newresponse;
    }
    
    public function altaUsuario($request, $response, $args) {
        $objDelaRespuesta= new stdclass();
        $ArrayDeParametros = $request->getParsedBody();
        //var_dump($ArrayDeParametros);
        if( 
            isset($ArrayDeParametros['email']) &&
            isset($ArrayDeParametros['password']) &&
            isset($ArrayDeParametros['nombre']) &&
            isset($ArrayDeParametros['apellido']) &&
            isset($ArrayDeParametros['turno']) &&
            isset($ArrayDeParametros['sexo'])
        ) {
            $entidad = new entidad();

            $archivos = $request->getUploadedFiles();
            $destino="./images/";
            $foto = null;
            //var_dump($archivos);
            //var_dump($archivos['foto']);
            if(isset($archivos['foto']))
            {
                $nombreAnterior = $archivos['foto']->getClientFilename();
                $extension = explode(".", $nombreAnterior);
                //var_dump($nombreAnterior);
                $extension = array_reverse($extension);
                $foto = $destino.$ArrayDeParametros['email'].".".$extension[0];
                $archivos['foto']->moveTo($foto);
            }
            
            $entidad->email = $ArrayDeParametros['email'];
            $entidad->password = $ArrayDeParametros['password'];
            $entidad->nombre = $ArrayDeParametros['nombre'];
            $entidad->apellido = $ArrayDeParametros['apellido'];
            $entidad->turno = $ArrayDeParametros['turno'];
            $entidad->sexo = $ArrayDeParametros['sexo'];

            $entidad->perfil = isset($ArrayDeParametros['perfil']) ? $ArrayDeParametros['perfil'] : null; // por defecto en db es "usuario"
            $entidad->perfil = isset($ArrayDeParametros['discapacitados']) ? $ArrayDeParametros['discapacitados'] : 0; // por defecto en db es false o 0
            $entidad->foto = isset($foto) ? $foto : null;      // por defecto en db es ""
            $entidad->habilitado = isset($ArrayDeParametros['habilitado']) ? $ArrayDeParametros['habilitado'] : null; // por defecto en db es "1"
            $entidad->fecha_creado = null; //isset($ArrayDeParametros['fecha_creado']) ? $ArrayDeParametros['fecha_creado'] : null; // por defecto en db es now()
        }
        else
        {
            return $response->withJson(array("error" => "Faltan parametros obligatorios del usuario."), 409);
        }
        
        $entidad->InsertarParametros();
        
        $objDelaRespuesta->respuesta = "Se guardo la entidad.";   
        return $response->withJson($objDelaRespuesta, 200);
    }
    
    public function borrarUsuario($request, $response, $args) { 
        $ArrayDeParametros = $request->getParsedBody();      // pasar por 'x-www-form-urlencoded'
        $objDelaRespuesta = new stdclass();
        
        if(isset($ArrayDeParametros['id']))
        {
            $id = $ArrayDeParametros['id'];

            $entidad = new entidad();
            $entidad->id = $id;
            $cantidadDeBorrados = $entidad->borrarEntidad();
            
            $objDelaRespuesta->cantidad = $cantidadDeBorrados;
            if($cantidadDeBorrados > 0)
            {
                $objDelaRespuesta->resultado = "Se ha eliminado el usuario exitosamente.";
                $newResponse = $response->withJson($objDelaRespuesta, 200);
                return $newResponse;
            }
            else
            {
                $objDelaRespuesta->resultado = "Error: no se pudo eliminar el usuario.";
                $newResponse = $response->withJson($objDelaRespuesta, 409);
                return $newResponse;
            }
        }
        else
        {
            $objDelaRespuesta->resultado = "No se pasó el ID del usuario a eliminar.";
            $newResponse = $response->withJson($objDelaRespuesta, 404);
            return $newResponse;
        }
    }
    
    public function suspenderUsuario($request, $response, $args) {
        $params = $request->getParsedBody();
        $objDelaRespuesta = new stdclass();
        //var_dump($params); die();
        
        if(isset($params['id']) && isset($params['habilitado']) && ($params['habilitado'] == 0 || $params['habilitado'] == 1))
        {
            $entidad = new entidad();
            $entidad->ID = $params['id'];
            $entidad->habilitado = $params['habilitado'];
            //var_dump($entidad); die();

            $resultado = $entidad->HabilitarUsuario();

            if(!$resultado)
            {
                $objDelaRespuesta->error = "No se pudo cambiar el estado del usuario o ya estaba en el mismo estado.";
                $objDelaRespuesta->tarea = "Habilitar";
                return $response->withJson($objDelaRespuesta, 200);
            }
            else
            {
                $objDelaRespuesta->respuesta = "Se ha cambiado el estado del usuario exitosamente.";
                $objDelaRespuesta->tarea = "Habilitar";
                return $response->withJson($objDelaRespuesta, 200);
            }
        }
        else
        {
            $objDelaRespuesta->error = "Parametros o valores de usuario no validos.";
            $objDelaRespuesta->tarea = "Habilitar";
            return $response->withJson($objDelaRespuesta, 409);
        }
    }
    public function generarExcel($request, $response, $args)
    {
        $entidad;
        $buff = " -  historico";
        $Arr = $request->getParsedBody();
        
        if(isset($args['id']) && $args['id'] != '')
        {
            $id = $args['id'];
            $entidad = entidad::TraerIngresos($id);
            $buff = " -  ingresos del usuario (ID): ".$id;
        }
        elseif(isset($Arr['fecha_desde']) && isset($Arr['fecha_hasta']) && $Arr['fecha_desde'] != '' && $Arr['fecha_hasta'] != '')
        {
            $desde = $Arr['fecha_desde'];
            $hasta = $Arr['fecha_hasta'];
            $entidad = entidad::TraerIngresos(null, $desde, $hasta);
            $buff = " -  entre fechas ".$desde." hasta ".$hasta;
        }
        else
        {
            $entidad = entidad::TraerIngresos();
        }

        if(!$entidad)
        {
            $entidad = array(array('Sin datos', 'Sin datos', 'Sin datos', 'Sin datos', 'Sin datos'));
        }

        $header = array('ID', 'ID ingreso Usuario', 'Fecha', 'Nombre','Apellido');
        $datos = "Ingresos;\n\n";
        
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $datos .= "Fecha:  ;".date('Y-m-d H:i:s').$buff.";\n\n\n";

        //echo $datos; die();

        foreach ($header as $key => $value) {
            $datos .= $value.";";
        }
        $datos .= "\n";

        foreach ($entidad as $keya => $elem) {
            foreach ($elem as $key => $value) {
                $datos .= $value.";";
            }
            $datos .= "\n";
        }
        $datos .= "\n;";
        
        //echo $datos; die();

        $file = "tmp/Ingresos.csv";
        file_put_contents($file, $datos);

        $res = $response->withHeader('Content-Description', 'File Transfer')
        ->withHeader('Content-Type', 'application/pdf')
        ->withHeader('Content-Disposition', 'attachment;filename="'.basename($file).'"')
        ->withHeader('Expires', '0')
        ->withHeader('Cache-Control', 'must-revalidate')
        ->withHeader('Pragma', 'public')
        ->withHeader('Content-Length', filesize($file));
    
        readfile($file);
        return $res;
    }
    public function generarPDF($request, $response, $args)
    {
        $entidad;
        $buff = " -  histórico";
        $Arr = $request->getParsedBody();
        
        if(isset($args['id']) && $args['id'] != '')
        {
            $id = $args['id'];
            $entidad = entidad::TraerIngresos($id);
            $buff = " -  ingresos del usuario (ID): ".$id;
        }
        elseif(isset($Arr['fecha_desde']) && isset($Arr['fecha_hasta']) && $Arr['fecha_desde'] != '' && $Arr['fecha_hasta'] != '')
        {
            $desde = $Arr['fecha_desde'];
            $hasta = $Arr['fecha_hasta'];
            $entidad = entidad::TraerIngresos(null, $desde, $hasta);
            $buff = " -  entre fechas ".$desde." hasta ".$hasta;
        }
        else
        {
            $entidad = entidad::TraerIngresos();
        }

        if(!$entidad)
        {
            $entidad = array(array('Sin datos', 'Sin datos', 'Sin datos', 'Sin datos', 'Sin datos'));
        }

        $pdf = new PDF('P','mm','A3');
        $header = array('ID', 'ID ingreso Usuario', 'Fecha', 'Nombre','Apellido');
        
        $pdf->AddPage();
        $pdf->Image('utn.png',10,8,33);
        $pdf->SetY(25);
        $pdf->SetFont('Arial','B',20);
        
        $pdf->Cell(0, 0, utf8_decode('Ingresos'), 0, 0, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial','',8);
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $pdf->Cell(0, 0, utf8_decode('Fecha:  '.date('Y-m-d H:i:s').$buff ), 0, 0, 'C');
        $pdf->Ln(4);
        
        $pdf->SetFont('Arial','',10);
        $pdf->Ln(10);
        //$pdf->Cell(0, 80);
        $pdf->BasicTable($header, $entidad);

        $pdf->SetY(10);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 0, utf8_decode('Página '.$pdf->PageNo()), 0, 0);

        $file = "tmp/Ingresos.pdf";
        $pdf->Output("F", $file);

        $res = $response->withHeader('Content-Description', 'File Transfer')
        ->withHeader('Content-Type', 'application/pdf')
        ->withHeader('Content-Disposition', 'attachment;filename="'.basename($file).'"')
        ->withHeader('Expires', '0')
        ->withHeader('Cache-Control', 'must-revalidate')
        ->withHeader('Pragma', 'public')
        ->withHeader('Content-Length', filesize($file));
    
        readfile($file);
        return $res;
    }
}
