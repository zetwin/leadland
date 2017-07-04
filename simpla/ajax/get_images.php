<?php

// $use_curl = true; // Использовать CURL

// $keyword = $_GET['keyword'];
// $keyword = str_replace(' ', '+', $keyword);

// $start=0;
// if(isset($_GET['start']))
        // $start = intval($_GET['start']);

// $url = 'http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q='.urlencode($keyword).'&start='.$start.'&rsz=8&imgsz=xlarge';
// if($use_curl && function_exists('curl_init'))
// {
        // $ch = curl_init(); 
        // curl_setopt($ch, CURLOPT_URL, $url); 
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // curl_setopt($ch, CURLOPT_REFERER, 'http://google.com');
        // curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.9.168 Version/11.51");
        // curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        // Для использования прокси используйте строки:
        // curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); 
        // curl_setopt($ch, CURLOPT_PROXY, '88.85.108.16:8080'); 
        // curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'user:password'); 
        
        // $page = curl_exec($ch);
        // curl_close($ch); 
// }
// else
// {
        // $page = file_get_contents($url);
// }
// $data = json_decode($page);
// $images = array();
// if($data)
        // foreach ($data->responseData->results as $result)
                // $images[] =  $result->url;

// header("Content-type: application/json; charset=UTF-8");
// header("Cache-Control: must-revalidate");
// header("Pragma: no-cache");
// header("Expires: -1");                

// print(json_encode($images));


$keyword = $_GET['keyword'];
	$keyword = str_replace(' ', '+', $keyword);
		
	$api_key = 'AIzaSyC7-EG9AkMVUeOLTXSy5jpLvOvXqjk3HGU'; // пример AIza6HyYFkklFfnktTuj4cGRVO-5HHX4qHAj9m5l0
	$cx = '016224451772556403038:4s2opyqw3rg'; // пример 056625995hy086069515:rtup3d2ppfm

	$start=1;
	if(!empty($_GET['start']))
			$start = intval($_GET['start']);

	$url = 'https://www.googleapis.com/customsearch/v1?q='.urlencode($keyword).'&searchType=image&start='.$start.'&num=8&fields=items%2Flink&cx='.$cx.'&key='.$api_key;
	$page = file_get_contents($url);
	$data = json_decode($page);
	$images = array();
	if($data)
	foreach ($data->items as $result)
		$images[] = $result->link;

	header("Content-type: application/json; charset=UTF-8");
	header("Cache-Control: must-revalidate");
	header("Pragma: no-cache");
	header("Expires: -1");                

	print(json_encode($images));