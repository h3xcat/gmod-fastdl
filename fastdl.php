<?php
define("GAME_DIR", "/home/steam/gmod_server/garrysmod");
define("CACHE_DIR", "./fastdl_cache"); // Must be in public folder, and must start with "."
define("SCAN_ADDONS", true);
define("SCAN_GAMEMODES", true);
define("ATTEMPTS_LOG_FILE", "./attempts.log");

$content_dirs = [
	"maps",
	"resource",
	"materials",
	"sound",
	"models",
	"scripts",
	"particles"
];
$alllowed_extensions = [
	"pcf",
	"ztmp",
	"vtx",
	"vvd",
	"phy",
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
	"bsp",
	"ttc"
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
$cfile_path = CACHE_DIR.$file.".bz2";
$cfile_rpath = realpath($cfile_path);
$cache_rpath = realpath(CACHE_DIR);
if($cfile_rpath === false){ // Cached file doesn't exist
	$scan_dirs = [GAME_DIR];
	if(SCAN_ADDONS){
		$scan_dirs = array_merge($scan_dirs,glob(GAME_DIR.'/addons/*' , GLOB_ONLYDIR));
	}
	if(SCAN_GAMEMODES){
		$scan_dirs = array_merge($scan_dirs,glob(GAME_DIR.'/gamemodes/*/content' , GLOB_ONLYDIR));
	}
	$created = false;
	foreach($scan_dirs as $dir){
		$dir_rpath = realpath($dir);
		$file_rpath = realpath($dir_rpath.$file);
		$file_info = pathinfo($file_rpath);
		$cfile_info = pathinfo($cfile_path);
		if($file_rpath === false){
			continue;
		}elseif(strpos($file_rpath, $dir_rpath."/") !== 0) {
			attempt_log("Invalid directory: ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
			exit404();
		}elseif(!in_array(strtolower($file_info["extension"]), $alllowed_extensions)){
			attempt_log("Invalid file extension: ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
			exit404();
		}elseif(!in_array(explode("/",substr($file_rpath,strlen($dir_rpath)+1))[0], $content_dirs)){
			attempt_log("Invalid content directory: ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
			exit404();
		}
		if (!file_exists($cfile_info["dirname"])) {
			mkdir($cfile_info["dirname"], 0755, true);
		}

		$bz = fopen($cfile_path, "w");
		if (flock($bz, LOCK_EX|LOCK_NB)) { // Attempt to manage critical section
			$data = file_get_contents($file_rpath);
			fwrite($bz, bzcompress($data));
			flock($bz, LOCK_UN);
		}else{
			flock($bz, LOCK_EX);
			flock($bz, LOCK_UN);
		}
		fclose($bz);


		$created = true;
	}
	if(!$created){
		exit404();
	}
}elseif(strpos($cfile_rpath, $cache_rpath."/") !== 0) { // Cached file exists, but not where it should be. Possible traversal attack?
	attempt_log("Invalid cache file location: ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
	exit404();
}

$bz = fopen($cfile_path, "r+");
if (flock($bz, LOCK_EX|LOCK_NB)) {
	flock($bz, LOCK_UN);
}else{
	flock($bz, LOCK_EX);
	flock($bz, LOCK_UN);
}
fclose($bz);

header('Location: ' . $cfile_path , true, 302);
exit();
?>