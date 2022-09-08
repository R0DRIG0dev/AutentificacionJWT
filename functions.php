<?php

spl_autoload_register(function($nombreDelaClase){
	// echo $path=strtolower($nombreDelaClase) . ".php";
	$path=strtolower($nombreDelaClase) . ".php";
	if (file_exists($path)) {
		require_once($path);
	}else{
		echo "no se encuentra el archivo $path";
	}
})

?>