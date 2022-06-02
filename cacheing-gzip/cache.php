<?php 
/**
 *  Cacheing routines
 *
 *  php pages can call cacheStart and cacheEnd around where their content is
 *  produced to cache the output. That produces .html and .html.gz files,
 *  which can be served straight from Apache using mod_rewrite - see the
 *  .htaccess files to see how that is done.
 *  
 *  Stale cached files are deleted using clean-cached-pages.pl, which is run
 *  as a git post-update hook
 *  
 *  IMPORTANT if this file changes, and it will effect the .html or .gz
 *  files produced, then the cached files on the server must be manually
 *  deleted.    
 */

// try and return cached file - if it can then routine exits after sending not-modified
// header, or complete page
function cacheTry($file,$cachePath = '/.cache') {
	$cacheFile = dirname($_SERVER['SCRIPT_FILENAME']) . $cachePath . '/' . $file;
	if (file_exists($cacheFile)) {
		$mtime = filemtime($cacheFile);
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
			if (substr(php_sapi_name(), 0, 3) == 'cgi') {
				header('Status: 304 Not Modified');
			} else {
		  		header('HTTP/1.1 304 Not Modified');
		  	}
		    exit;
		}
		// NB Don't set Content-Length, as we use zlib.output_compression, so 
		// Content-Length would need to be the compressed size
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $mtime).' GMT');
		flush();
		readfile($cacheFile);
		exit;	
	}
}
function cacheStart() {
	ob_start();
}
function cacheEnd($file,$createGz = true,$cachePath = '/.cache') {
	$cacheDir = dirname($_SERVER['SCRIPT_FILENAME']) . $cachePath;
	if (!file_exists($cacheDir)) {
		mkdir($cacheDir, 0705, true);
	}
	$cacheFile = $cacheDir . '/' . $file;
	
	// write to a temp file so Apache doesn't try to return a half created file
	$tmpf = tempnam('/tmp','SLC');
	$fp = fopen($tmpf,'w');
	fwrite($fp, ob_get_contents());
	fclose($fp);
	// TODO - chmod before copy, need to make sure this works OK
	chmod($tmpf, 0604); // temp files default to 0600
	rename($tmpf, $cacheFile);
	// chmod($cacheFile, 0604); // temp files default to 0600
	
 	header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($cacheFile)).' GMT');
	ob_end_flush();	// Send browser output
	if ($createGz) {
		cacheCreateGz($cacheFile);
	}
}
function cacheData($file,$data,$createGz = true,$cachePath = '/.cache') {
	$cacheDir = dirname($_SERVER['SCRIPT_FILENAME']) . $cachePath;
	if (!file_exists($cacheDir)) {
		mkdir($cacheDir, 0705, true);
	}
	$cacheFile = $cacheDir . '/' . $file;

	// write to a temp file so Apache doesn't try to return a half created file
	$tmpf = tempnam('/tmp','SLC');
	$fp = fopen($tmpf,'w');
	fwrite($fp, $data);
	fclose($fp);
	rename($tmpf, $cacheFile);
	chmod($cacheFile, 0604); // temp files default to 0600

	header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($cacheFile)).' GMT');
	echo $data;	// Send browser output
	if ($createGz) {
		cacheCreateGz($cacheFile);
	}
}
function cacheCreateGz($cacheFile) {
	// create .gz version of file so we can send minimal data
	// run async so we don't hold up user
	exec('nohup nice -n 10 "' . __DIR__ . '/create-gzip.sh" "' . $cacheFile . '"');
}
