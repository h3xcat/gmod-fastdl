<?php
define("GAME_DIR", "/home/steam/gmod_server/garrysmod");
define("CACHE_DIR", "./fastdl_cache");
define("SCAN_ADDONS", true);
define("ATTEMPTS_LOG_FILE", "");

$resource_dirs = [
	"maps",
	"resource",
	"materials",
	"sound",
	"models"
];

$alllowed_extensions = [
	"wav",
	"mp3",
	"vmt",
	"vtf",
	"mdl",
	"ttf",
	"res",
	"ico",
	"png",
	"jpg",
	"txt",
	"ain",
	"bsp"
];

$file = $_GET["f"];
if( substr($file, -4) != ".bz2" ){
	header("HTTP/1.0 404 Not Found");
	die('404 Not Found');
}
$file = substr($file, 0, -4);;
$file_info = pathinfo($file);

//Valide the request
if(
	empty($file) ||
	strpos($file,"..") ||
	!in_array($file_info["extension"], $alllowed_extensions) ||
	!in_array(explode("/",$file)[0], $resource_dirs)
){
	if(!empty(ATTEMPTS_LOG_FILE)){
		file_put_contents(ATTEMPTS_LOG_FILE, date('d.m.Y H:i:s')." - ".$_SERVER['REMOTE_ADDR']." ".$file."\r\n",FILE_APPEND);
	}
	header("HTTP/1.0 404 Not Found");
	die('404 Not Found');
}
//Redirect if cached file already exist
if(file_exists(CACHE_DIR."/".$file.".bz2")){
	header('Location: ' .CACHE_DIR."/".$file.".bz2" , true, 302);
	exit();
}

//If file doesn't exist, try find it and cache it.
$scan_dirs = [];
if(SCAN_ADDONS){
	$scan_dirs = glob(GAME_DIR.'/addons/*' , GLOB_ONLYDIR);
}
array_unshift($scan_dirs,GAME_DIR);
foreach($scan_dirs as $dir){
	if(file_exists($dir."/".$file)){
		mkdir(CACHE_DIR."/".$file_info["dirname"], 0755, true);
		$data = file_get_contents($dir."/".$file);
		$bz = bzopen(CACHE_DIR."/".$file.".bz2", "w");
		bzwrite($bz, $data, strlen($data));
		bzclose($bz);

		header('Location: ' .CACHE_DIR."/".$file.".bz2" , true, 302);
		exit();
	}
}

header("HTTP/1.0 404 Not Found");
die('404 Not Found'.$file);
?>