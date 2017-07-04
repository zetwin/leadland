<?PHP

// include_once('htracer/HTracer.php'); 
// htracer_start(); 


/**
 * Simpla CMS
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simp.la
 * @author 		Denis Pikusov
 *
 */

// Засекаем время
$time_start = microtime(true);

session_start();

require_once('view/IndexView.php');

// print_r($_SESSION['visitor_country']);

// Выбор текущей валюты в зависимости от страны
if(empty($_SESSION['visitor_country'])){
include("SxGeo.php");
$SxGeo = new SxGeo('SxGeo.dat', SXGEO_BATCH | SXGEO_MEMORY);
$country = $SxGeo->getCountry($_SERVER['REMOTE_ADDR']);

	if($country == 'RU'){
			$_SESSION['currency_id'] = 5;
	}elseif($country == 'UA'){
			$_SESSION['currency_id'] = 1;
	}else{
			$_SESSION['currency_id'] = 2;
	}
	$_SESSION['visitor_country'] = $country;
	unset($SxGeo);
}

$view = new IndexView();


if(isset($_GET['logout']))
{
    header('WWW-Authenticate: Basic realm="Simpla CMS"');
    header('HTTP/1.0 401 Unauthorized');
	unset($_SESSION['admin']);
}

// Если все хорошо
if(($res = $view->fetch()) !== false)
{
	// Выводим результат
	header("Content-type: text/html; charset=UTF-8");
	// $LastModified_unix = strtotime($view->last_update); // время последнего изменения страницы

    // if(!empty($LastModified_unix) && $LastModified_unix > 0)
    // {
        // $LastModified = gmdate("D, d M Y H:i:s \G\M\T", $LastModified_unix);
        // $IfModifiedSince = false;
        // if (isset($_ENV['HTTP_IF_MODIFIED_SINCE']))
            // $IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));
        // if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            // $IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
        // if ($IfModifiedSince && $IfModifiedSince >= $LastModified_unix) {
            // header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
        // }
        // header('Last-Modified: '. $LastModified);
    // }
	
	print $res;

	// Сохраняем последнюю просмотренную страницу в переменной $_SESSION['last_visited_page']
	if(empty($_SESSION['last_visited_page']) || empty($_SESSION['current_page']) || $_SERVER['REQUEST_URI'] !== $_SESSION['current_page'])
	{
		$_SESSION['last_visited_page'] = $_SESSION['current_page'];
		$_SESSION['current_page'] = $_SERVER['REQUEST_URI'];
	}		
}
else 
{ 
	// Иначе страница об ошибке
	header("http/1.0 404 not found");
	
	// Подменим переменную GET, чтобы вывести страницу 404
	$_GET['page_url'] = '404';
	$_GET['module'] = 'PageView';
	print $view->fetch();   
}


// Отладочная информация
if(1)
{
	print "<!--\r\n";
	$time_end = microtime(true);
	$exec_time = $time_end-$time_start;
  
  	if(function_exists('memory_get_peak_usage'))
		print "memory peak usage: ".memory_get_peak_usage()." bytes\r\n";  
	print "page generation time: ".$exec_time." seconds\r\n";  
	print "-->";
}
