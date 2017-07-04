<?php

require_once('api/Simpla.php');
$simpla = new Simpla();

header("Content-type: text/xml; charset=UTF-8");

// Заголовок
print
"<?xml version='1.0'?>
<rss xmlns:g='http://base.google.com/ns/1.0' version='2.0'>

<channel>
<title>".$simpla->settings->site_name."</title>
<url>".$simpla->config->root_url."</url>
<description>".$simpla->settings->company_name."</description>
";

// Валюты
$currencies = $simpla->money->get_currencies(array('enabled'=>1));
$main_currency = reset($currencies);

// Товары
$simpla->db->query("SET SQL_BIG_SELECTS=1");
// Товары
$simpla->db->query("SELECT v.price, v.id AS variant_id, p.name AS product_name, v.name AS variant_name, v.position AS variant_position, v.sku AS variant_sku, p.id AS product_id, p.url, p.annotation, p.meta_description, p.body, pc.category_id, i.filename AS image, b.name AS brand
		    FROM __variants v LEFT JOIN __products p ON v.product_id=p.id
		    LEFT JOIN s_brands b ON b.id = p.brand_id
		    LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)	
		    LEFT JOIN __images i ON p.id = i.product_id AND i.position=(SELECT MIN(position) FROM __images WHERE product_id=p.id LIMIT 1)	
					WHERE p.visible AND (v.stock >0 OR v.stock is NULL) GROUP BY v.id ORDER BY p.id, v.position ");
print "
";
 

$currency_code = reset($currencies)->code;

// В цикле мы используем не results(), a result(), то есть выбираем из базы товары по одному,
// так они нам одновременно не нужны - мы всё равно сразу же отправляем товар на вывод.
// Таким образом используется памяти только под один товар
$prev_product_id = null;
while($p = $simpla->db->result())
{
$variant_url = '';
if ($prev_product_id === $p->product_id)
	$variant_url = '?variant='.$p->variant_id;
$prev_product_id = $p->product_id;

$price = round($simpla->money->convert($p->price, $main_currency->id, false),2);
print
"
<item>
<g:id>".$p->variant_id."</g:id>
<g:title>".htmlspecialchars($p->product_name).($p->variant_name?' '.htmlspecialchars($p->variant_name):'')."</g:title>";
if($p->meta_description){
print "<g:description>".htmlspecialchars(strip_tags($p->meta_description))."</g:description>
";}elseif($p->annotation){
print "<g:description>".htmlspecialchars(strip_tags($p->annotation))."</g:description>
";}elseif($p->body){
print "<g:description>".htmlspecialchars(strip_tags($p->body))."</g:description>
";}else{
print "<g:description>".htmlspecialchars($p->product_name).($p->variant_name?' '.htmlspecialchars($p->variant_name):'')."</g:description>
";}
print "<g:link>".$simpla->config->root_url.'/products/'.$p->url.$variant_url."</g:link>";
print "
<g:price>".$price." ".$currency_code."</g:price>
<g:condition>new</g:condition>
<g:availability>in stock</g:availability>
";


//if(in_array($p->category_id, array('24', '25', '46', '26', '51', '13'))) print "<g:google_product_category>Apparel &amp; Accessories > Jewelry > Watches</g:google_product_category><g:product_type>Часы</g:product_type>";
//if($p->category_id == 8) print "<g:google_product_category>Apparel &amp; Accessories > Clothing > Shirts &amp; Tops</g:google_product_category><g:product_type>Рубашки</g:product_type>";


if($p->image)
print "<g:image_link>".$simpla->design->resize_modifier($p->image, 200, 200)."</g:image_link>
";
if($p->brand){
print "<g:brand>".$p->brand."</g:brand>
";}else{
print "<g:brand>unknown</g:brand>
";}
print "
</item>

";
}
print "</channel>
";

print "</rss>
";