<?php
define("GAME_DIR", "/home/steam/gmod_server/garrysmod");
define("CACHE_DIR", "./fastdl_cache");
define("SCAN_ADDONS", true);
define("ATTEMPTS_LOG_FILE", "./attempts.log");

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


function attempt_log($str){
	if(!empty(ATTEMPTS_LOG_FILE)){
		file_put_contents(ATTEMPTS_LOG_FILE, date('d.m.Y H:i:s')." - ".$str."\r\n",FILE_APPEND);
	}
}

function exit404(){
	header("HTTP/1.0 404 Not Found");
	die('404 Not Found');
}


$file = $_SERVER["QUERY_STRING"];
if( substr($file, -4) != ".bz2" ){ exit404(); }
$file = substr($file, 0, -4);;

$file_info = pathinfo($file);
$cfile_path = CACHE_DIR."/".$file.".bz2";
$cfile_rpath = realpath($cfile_path);
$cache_rpath = realpath(CACHE_DIR);

if($cfile_rpath === false){ // Cached file doesn't exist
	$scan_dirs = [];
	if(SCAN_ADDONS){
		$scan_dirs = glob(GAME_DIR.'/addons/*' , GLOB_ONLYDIR);
	}
	array_unshift($scan_dirs,GAME_DIR);

	$created = false;
	foreach($scan_dirs as $dir){
		$dir_rpath = realpath($dir);
		$file_rpath = realpath($dir_rpath."/".$file);
		if($file_rpath === false){
			continue;
		}elseif(strpos($file_rpath, $dir_rpath."/") !== 0) {
			attempt_log($_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
			exit404();
		}
		// Create cached file
		mkdir($cache_rpath."/".$file_info["dirname"], 0755, true);

		$data = file_get_contents($file_rpath);
		$bz = bzopen($cfile_path, "w");
		bzwrite($bz, $data, strlen($data));
		bzclose($bz);
		$created = true;
	}
	if(!$created){
		exit404();
	}
}elseif(strpos($cfile_rpath, $cache_rpath."/") !== 0) { // Cached file exists, but not where it should be. Possible traversal attack?
	attempt_log($_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
	exit404();
}

header('Location: ' . $cfile_path , true, 302);
exit();
?>
