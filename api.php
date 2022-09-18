<?php 

class Api extends Rest
{
	public $dbConn;
	public function __construct()
	{
		parent::__construct();
		$db = new DbConnect;
		$this->dbConn = $db->connect();
	}

	public function generarToken() {
		$email = $this->validarParametro('email', $this->parametro['email'], STRING);
		$pass = $this->validarParametro('pass', $this->parametro['pass'], STRING);

		// echo "pasastes";
		try {
			$stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
			$stmt->bindParam(":email", $email);
			$stmt->bindParam(":pass", $pass);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!is_array($user)) {
				$this->retornarRespuesta(INVALID_USER_PASS, "Email o Contraseña incorrectos.");
			}
			
			if( $user['active'] == 0 ) {
				$this->retornarRespuesta(USER_NOT_ACTIVE, "El usuario no está activado. Por favor, póngase en contacto con el administrador.");
			}
			
			$paylod = [
				'iat' => time(),
				'iss' => 'localhost',
				'exp' => time() + (15*60),
				'userId' => $user['id']
			];
			
			$token = JWT::encode($paylod, SECRETE_KEY);
			// echo $token;
			
			$data = ['token' => $token];
			$this->retornarRespuesta(SUCCESS_RESPONSE, $data);
		} catch (Exception $e) {
			$this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
		}
	}

	public function agregarCustomer() {
		$name = $this->validarParametro('name', $this->parametro['name'], STRING, false);
		$email = $this->validarParametro('email', $this->parametro['email'], STRING, false);
		$addr = $this->validarParametro('addr', $this->parametro['addr'], STRING, false);
		$mobile = $this->validarParametro('mobile', $this->parametro['mobile'], INTEGER, false);

		// $cust = new Customer;
		// $cust->setName($name);
		// $cust->setEmail($email);
		// $cust->setAddress($addr);
		// $cust->setMobile($mobile);
		// $cust->setCreatedBy($this->userId);
		// $cust->setCreatedOn(date('Y-m-d'));

		// if(!$cust->insert()) {
		// 	$message = 'Failed to insert.';
		// } else {
		// 	$message = "Inserted successfully.";
		// }

		// $this->returnResponse(SUCCESS_RESPONSE, $message);

		try {
			$token = $this->ObtenerTokenPortador();
			$payload = JWT::decode($token, SECRETE_KEY, ['HS256']);
			$stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
			$stmt->bindParam(":userId", $payload->userId);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!is_array($user)) {
				$this->retornarRespuesta(INVALID_USER_PASS, "Este usuario no se encuentra en nuestra base de datos.");
			}
			if( $user['active'] == 0 ) {
				$this->retornarRespuesta(USER_NOT_ACTIVE, "Este usuario puede estar desactivado. Por favor, póngase en contacto con el administrador.");
			}
			$cust = new Customer;
			$cust->setName($name);
			$cust->setEmail($email);
			$cust->setAddress($addr);
			$cust->setMobile($mobile);
			$cust->setCreatedBy($payload->userId);
			$cust->setCreatedOn(date('Y-m-d'));
			if(!$cust->insert()) {
				$message = 'Failed to insert.';
			} else {
				$message = "Inserted successfully.";
			}
			$this->retornarRespuesta(SUCCESS_RESPONSE, $message);	
			// print_r($payload->userId);
		} catch (\Exception $e) {
			$this->lanzarError(ACCESS_TOKEN_ERRORS, $e->getMessage());
		}
	}	
}

?>