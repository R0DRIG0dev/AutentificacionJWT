<?php 

require_once('constants.php');
class Rest 
{
	protected $consulta;
	protected $NombreDelServicio;
	protected $parametro;

	public function __construct()
	{
		// IMPORTANTE : SI TU RUTA ES ASI "http://localhost/z_PHP/apiJWT" sin el "/" al final, siempre caeras aqui, porque?
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->lanzarError(REQUEST_METHOD_NOT_VALID,'EL MÉTODO DE SOLICITUD NO ES VÁLIDO');
		}	
		$manipulador = fopen('php://input','r');
		// echo $consulta = stream_get_contents($manipulador);
		$this->consulta = stream_get_contents($manipulador);
		$this->validarSolicitud();
	}

	public function validarSolicitud(){
		// echo $_SERVER['CONTENT_TYPE']; exit;
		if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
			$this->lanzarError(REQUEST_CONTENTTYPE_NOT_VALID,'El tipo de contenido de la solicitud no es válido');
		}
		$data=json_decode($this->consulta, true);

		if (!isset($data['nombre']) || $data['nombre'] == "") {
			$this->lanzarError(API_NAME_REQUIRED,'el nombre en la api es obligatorio');
		}
		$this->NombreDelServicio = $data['nombre'];

		if (!isset($data['parametro'])) {
			$this->lanzarError(API_PARAM_REQUIRED,'el parametro en la api es obligatorio');
		}
		$this->parametro = $data['parametro'];
	}

	public function procesarApi(){
		try {
			$api = new API;
			$rMethod = new reflectionMethod('API', $this->NombreDelServicio);
			if(!method_exists($api, $this->NombreDelServicio)) {
				$this->lanzarError(API_DOST_NOT_EXIST, "API does not exist.");
			}
			$rMethod->invoke($api);
		} catch (Exception $e) {
			$this->lanzarError(API_DOST_NOT_EXIST, "API does not exist.");
		}
	}

	public function validarParametro($NombreDelCampo, $valor, $tipoDeDato, $requerido = true){
		if($requerido == true && empty($valor) == true) {
			$this->lanzarError(VALIDATE_PARAMETER_REQUIRED, $NombreDelCampo . ", es un parámetro es obligatorio");
		}

		switch ($tipoDeDato) {
			case BOOLEAN:
				if(!is_bool($valor)) {
					$this->lanzarError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $NombreDelCampo . '. It should be boolean.');
				}
				break;
			case INTEGER:
				if(!is_numeric($valor)) {
					$this->lanzarError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $NombreDelCampo . '. It should be numeric.');
				}
				break;

			case STRING:
				if(!is_string($valor)) {
					$this->lanzarError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $NombreDelCampo . '. It should be string.');
				}
				break;
			
			default:
				$this->lanzarError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $NombreDelCampo);
				break;
		}

		return $valor;
	}

	public function lanzarError($codigo, $mensaje){
		header("content-type: application/json");
		$mensajeDeError=json_encode(['error'=>['estado'=>$codigo,'mensaje'=>$mensaje]]);
		echo $mensajeDeError;
		EXIT;
	}

	public function retornarRespuesta($code, $data){
		header("content-type: application/json");
		$response = json_encode(['resonse' => ['status' => $code, "result" => $data]]);
		echo $response; exit;
	}

	/**
	* Get hearder Authorization
	* */
	public function obtenerHeaderAutorizante(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}
	/**
	 * get access token from header
	 * */
	public function ObtenerTokenPortador() {
		$headers = $this->obtenerHeaderAutorizante();
		// HEADER: Get the access token from the header
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		$this->throwError( ATHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found');
	}	

}

?>