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
		// try {
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
		// } catch (Exception $e) {
		// 	$this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
		// }
	}

}

?>