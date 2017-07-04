<?php

require_once('../../api/Simpla.php');

class ExportAjax extends Simpla
{	
	private $columns_names = array(
			'category'=>         'Категория',
			'name'=>             'Товар',
			'variant'=>          'Вариант',
			'price'=>            'Цена',
			// 'currency'=>         'Валюта',
			'supplier_price'=>            'Цена поставщика',
			'compare_price'=>    'Старая цена',
			'currency_code'=>    'Валюта',
			

			'stock'=>            'Склад',			
			
			'store'=>            'Магазин',
			'store_url'=>            'Ссылка магазина',
			// 'supplier_url'=>            'Ссылка поставщика',
			
			'brand'=>            'Бренд',			
			'url'=>              'Адрес',
			'visible'=>          'Видим',
			'featured'=>         'Рекомендуемый',

			'sku'=>              'Артикул',			
			

			'meta_title'=>       'Заголовок страницы',
			'meta_keywords'=>    'Ключевые слова',
			'meta_description'=> 'Описание страницы',

	
			'annotation'=>       'Аннотация',
			'body'=>             'Описание',
			'images'=>           'Изображения',
			'tags'=>           'Теги'
			);
			
	private $column_delimiter = ';';
	private $subcategory_delimiter = '/';
	private $store_delimiter = '/';
	private $products_count = 5;
	private $export_files_dir = '../files/export/';
	private $filename = 'export.csv';

	public function fetch()
	{

		if(!$this->managers->access('export'))
			return false;

		// Эксель кушает только 1251
		setlocale(LC_ALL, 'ru_RU.1251');
		$this->db->query('SET NAMES cp1251');
		$category_id = $this->request->get('category_id');
		$category_children = array();
		if(!empty($category_id))
		{
			$category = $this->categories->get_category((int)$category_id);
			if (isset($category))
				$category_children = $category->children;
		}
		
		//Магазины
		$store_id = $this->request->get('store_id');
		$store_children = array();
		if(!empty($store_id))
		{
			$store = $this->stores->get_store((int)$store_id);
			if (isset($store))
				$store_children = $store->children;
		}
	
		// Страница, которую экспортируем
		$page = $this->request->get('page');
		if(empty($page) || $page==1)
		{
			$page = 1;
			// Если начали сначала - удалим старый файл экспорта
			if(is_writable($this->export_files_dir.$this->filename))
				unlink($this->export_files_dir.$this->filename);
		}
		
		// Открываем файл экспорта на добавление
		$f = fopen($this->export_files_dir.$this->filename, 'ab');
		
		// Добавим в список колонок свойства товаров
		$features = $this->features->get_features();
		foreach($features as $feature)
			$this->columns_names[$feature->name] = $feature->name;
		
		// Если начали сначала - добавим в первую строку названия колонок
		if($page == 1)
		{
			fputcsv($f, $this->columns_names, $this->column_delimiter);
		}
		
		// Все товары
		$products = array();
 		foreach($this->products->get_products(array('page'=>$page, 'category_id'=>$category_children, 'limit'=>$this->products_count)) as $p)
 		{
 			$products[$p->id] = (array)$p;
 			
	 		// Свойства товаров
	 		$options = $this->features->get_product_options($p->id);
	 		foreach($options as $option)
	 		{
	 			                if(!isset($products[$option->product_id][$option->name]))
                    $products[$option->product_id][$option->name] = $option->value;
                else
                    $products[$option->product_id][$option->name] .= ','.$option->value; 
	 		}

 			
 		}
 		
 		if(empty($products))
 			return false;
 		
 		// Категории товаров
 		foreach($products as $p_id=>&$product)
 		{
	 		$categories = array();
	 		$cats = $this->categories->get_product_categories($p_id);
	 		foreach($cats as $category)
	 		{
	 			$path = array();
	 			$cat = $this->categories->get_category((int)$category->category_id);
	 			if(!empty($cat))
 				{
	 				// Вычисляем составляющие категории
	 				foreach($cat->path as $p)
	 					$path[] = str_replace($this->subcategory_delimiter, '\\'.$this->subcategory_delimiter, $p->name);
	 				// Добавляем категорию к товару 
	 				$categories[] = implode('/', $path);
 				}
	 		}
	 		$product['category'] = implode(',, ', $categories);
			// }
		
			// Магазины товаров
			// foreach($products as $p_id=>&$product)
			// {
	 		$stores = array();
	 		$cats = $this->stores->get_product_stores($p_id);
			$stores_url =null;
			
	 		foreach($cats as $store)
	 		{
				
	 			$path = array();
	 			$cat = $this->stores->get_store((int)$store->store_id);
	 			if(!empty($cat))
 				{
	 				// Вычисляем составляющие категории
	 				foreach($cat->path as $p){
	 					$path[] = str_replace($this->substore_delimiter, '\\'.$this->substore_delimiter, $p->name);
						// print_r($p->name);
	 				// Добавляем категорию к товару 
					}
					$stores[] = implode('/', $path);

 				}
					if($store->store_url){
	 				$stores_url[] = $store->store_url;
					}
	 		}
			if(!empty($stores_url))
			$product['store_url'] = implode(',, ', $stores_url);
	 		$product['store'] = implode(',, ', $stores);
			
		
			
			
			
			
			
			$tags = array();
			$product_tags = null;
			
			$tags = $this->tags->get_tags(array('item_id'=>$p_id, 'type' => 'product'));
			
			if(!empty($tags)){
			foreach($tags as $tag){
					$product_tags[] = $tag->name;
			}			
			$res_arr = implode(',',$product_tags);			
			// print_r('zzzz'.$res_arr);
			
			if(!empty($res_arr))
	 		$product['tags'] = $res_arr;
			}
		}
		
		// print_r($product);
 		
 		// Изображения товаров
 		$images = $this->products->get_images(array('product_id'=>array_keys($products)));
 		foreach($images as $image)
 		{
 			// Добавляем изображения к товару чезер запятую
 			if(empty($products[$image->product_id]['images']))
 				$products[$image->product_id]['images'] = $image->filename;
 			else
 				$products[$image->product_id]['images'] .= ', '.$image->filename;
 		}
 
 		$variants = $this->variants->get_variants(array('product_id'=>array_keys($products)));

		 // Формируем массив с сопоставлением ID валют их коду
        $currencies = $this->money->get_currencies();        
        $currencies_list = array();
        foreach($currencies as $currency) {
            $currencies_list[$currency->id] = $currency->code;
        }
		
		foreach($variants as $variant)
 		{
 			if(isset($products[$variant->product_id]))
 			{
	 			$v = array();
	 			$v['variant']         = $variant->name;
				$v['price']           = $variant->price;
				$v['currency'] 		  = $variant->currency;
				$v['currency_code']   = $currencies_list[$variant->currency];
						
					if ($variant->base_price)
						$v['price']           = $variant->base_price;
					else
						$v['price']           = $variant->price;
						
					if ($variant->base_price)
						$v['base_compare_price']           = $variant->base_compare_price;
					else
						$v['compare_price']   = $variant->compare_price;
	 			$v['supplier_price']           = $variant->supplier_price;
	 			$v['compare_price']   = $variant->compare_price;
	 			$v['sku']             = $variant->sku;
	 			$v['stock']           = $variant->stock;
	 			if($variant->infinity)
	 				$v['stock']           = '';
				$products[$variant->product_id]['variants'][] = $v;
	 		}
		}
		
		foreach($products as &$product)
 		{
 			$variants = $product['variants'];
 			unset($product['variants']);
 			
 			if(isset($variants))
 			foreach($variants as $variant)
 			{
 				$result = array();
 				$result =  $product;
 				foreach($variant as $name=>$value)
 					$result[$name]=$value;

	 			foreach($this->columns_names as $internal_name=>$column_name)
	 			{
	 				if(isset($result[$internal_name]))
		 				$res[$internal_name] = $result[$internal_name];
	 				else
		 				$res[$internal_name] = '';
	 			}
	 			fputcsv($f, $res, $this->column_delimiter);

	 		}
		}
		
		$total_products = $this->products->count_products(array('category_id'=>$category_children));
		
		if($this->products_count*$page < $total_products)
			return array('end'=>false, 'page'=>$page, 'category_id'=>$category_children, 'totalpages'=>$total_products/$this->products_count);
		else
			return array('end'=>true, 'page'=>$page, 'category_id'=>$category_children, 'totalpages'=>$total_products/$this->products_count);	

		fclose($f);

	}
	
}

$export_ajax = new ExportAjax();
$data = $export_ajax->fetch();
if($data)
{
	header("Content-type: application/json; charset=utf-8");
	header("Cache-Control: must-revalidate");
	header("Pragma: no-cache");
	header("Expires: -1");
	$json = json_encode($data);
	print $json;
}