<?php

require_once('api/Simpla.php');
$simpla = new Simpla();

header("Content-type: text/xml; charset=UTF-8");
print '<?xml version="1.0" encoding="UTF-8"?>'."\n";
print '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

// Главная страница
$url = $simpla->config->root_url;
$lastmod = date("Y-m-d");
print "\t<url>"."\n";
print "\t\t<loc>$url</loc>"."\n";
//print "\t\t<lastmod>$lastmod</lastmod>"."\n";
print "\t</url>"."\n";

// Страницы
foreach($simpla->pages->get_pages() as $p)
{
	if($p->visible && $p->menu_id == 1)
	{
		$url = $simpla->config->root_url.'/'.esc($p->url);
		print "\t<url>"."\n";
		print "\t\t<loc>$url</loc>"."\n";
		print "\t</url>"."\n";
	}
}

// Блог
foreach($simpla->blog->get_posts(array('visible'=>1)) as $p)
{
	$url = $simpla->config->root_url.'/blog/'.esc($p->url);
	print "\t<url>"."\n";
	print "\t\t<loc>$url</loc>"."\n";
	print "\t</url>"."\n";
}

// Категории
foreach($simpla->categories->get_categories() as $c)
{
	if($c->visible)
	{
		$url = $simpla->config->root_url.'/catalog/'.esc($c->url);
		print "\t<url>"."\n";
		print "\t\t<loc>$url</loc>"."\n";
		//if($c->last_update > 0){
		//$date=date("Y-m-d", strtotime("$c->last_update"));
		//print "\t\t<lastmod>$date</lastmod>"."\n";}
		print "\t</url>"."\n";
	}
}

// Товары
$simpla->db->query("SELECT url FROM __products WHERE visible=1");
foreach($simpla->db->results() as $p)
{
	$url = $simpla->config->root_url.'/products/'.esc($p->url);
	print "\t<url>"."\n";
	print "\t\t<loc>$url</loc>"."\n";
	print "\t</url>"."\n";
}

// Статьи
$simpla->db->query("SELECT url FROM __articles WHERE visible=1");
foreach($simpla->db->results() as $p)
{
	$url = $simpla->config->root_url.'/article/'.esc($p->url);
	print "\t<url>"."\n";
	print "\t\t<loc>$url</loc>"."\n";
	print "\t</url>"."\n";
}

// Категории статтей
foreach($simpla->articlecategories->get_articlecategories() as $c)
{
	if($c->visible)
	{
		$url = $simpla->config->root_url.'/articles/'.esc($c->url);
		print "\t<url>"."\n";
		print "\t\t<loc>$url</loc>"."\n";
		print "\t</url>"."\n";
	}
}

// Категории по брендам
foreach($simpla->categories->get_categories() as $c)
{	foreach ($simpla->brands->get_brands(array('category_id'=>$c->id, 'visible'=>1)) as $b){
	if($c->visible)
	{
		$url = $simpla->config->root_url.'/catalog/'.esc($c->url).'/'.esc($b->url);
		print "\t<url>"."\n";
		print "\t\t<loc>$url</loc>"."\n";
		print "\t</url>"."\n";
	}
}
}

// Бренды
foreach($simpla->brands->get_brands() as $b)
{
	$url = $simpla->config->root_url.'/brands/'.esc($b->url);
	print "\t<url>"."\n";
	print "\t\t<loc>$url</loc>"."\n";
	print "\t</url>"."\n";
}



print '</urlset>'."\n";

function esc($s)
{
	return(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));	
}