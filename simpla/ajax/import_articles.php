<?php

require_once('../../api/Simpla.php');

class ImportAjax extends Simpla
{	
	
	// print_r('zzzz');
	// Соответствие полей в базе и имён колонок в файле
	private $columns_names = array(
			'name'=>             array('article', 'name', 'товар', 'название', 'наименование' , 'Товар' ),
			'url'=>              array('url', 'адрес'),
			'visible'=>          array('visible', 'published', 'видим'),
			'featured'=>         array('featured', 'hit', 'хит', 'рекомендуемый'),
			'category'=>         array('category', 'категория'),
			'tags'=>         array('теги', 'tags'),
			// 'brand'=>            array('brand', 'бренд'),
			
			// 'price'=>            array('price', 'Цена'),
			// 'currency'=>         array('currency', 'валюта id'),
			// 'currency_code'=>    array('currency_code', 'код валюты', 'Валюта'),
			// 'compare_price'=>    array('compare price', 'старая цена'),
			// 'sku'=>              array('sku', 'артикул'),
			// 'stock'=>            array('stock', 'склад', 'на складе'),
			'meta_title'=>       array('meta title', 'заголовок страницы'),
			'meta_keywords'=>    array('meta keywords', 'ключевые слова'),
			// 'demo'=>    array('Demo', 'Демо'),

			
			'meta_description'=> array('meta description', 'описание страницы'),
			
			// 'store'=>    array('store', 'Поставщик', 'Магазин'),
			// 'store_url'=>    array('Ссылка магазина'),
			// 'suppliers'=> array('Поставщик'),
			// 'supplier_price'=> array('supplier_price', 'Цена поставщика', 'Закупочная цена', 'Закупка'),
			// 'supplier_url'=>    array('supplier_url', 'урл поставщика', 'Ссылка на сайт поставщика', 'Поставщик ссылка', 'Ссылка поставщика'),
			// 'variant'=>          array('variant', 'вариант'),
			
			'annotation'=>       array('annotation', 'аннотация', 'краткое описание'),
			'description'=>      array('description', 'описание'),
			'images'=>           array('images', 'изображения')
			);
	
	// Соответствие имени колонки и поля в базе
	private $internal_columns_names = array();
	
	private $import_files_dir      = '../files/import/'; // Временная папка		
	private $import_file           = 'import.csv';           // Временный файл
	private $category_delimiter = ',,';                       // Разделитель каегорий в файле
	// private $store_delimiter = ',, ';                       // Разделитель каегорий в файле
	// private $supplier_delimiter = ',';                       // Разделитель suppliers в файле
	private $subcategory_delimiter = '/';                    // Разделитель подкаегорий в файле
	private $substore_delimiter = '/';                    // Разделитель подкаегорий в файле
	private $column_delimiter      = ';';
	private $articles_count        = 10;
	private $columns               = array();

	public function import()
	{
		if(!$this->managers->access('import'))
			return false;

		// Для корректной работы установим локаль UTF-8
		setlocale(LC_ALL, 'ru_RU.UTF-8');
		
		$result = new stdClass;
		
		// Определяем колонки из первой строки файла
		$f = fopen($this->import_files_dir.$this->import_file, 'r');
		$this->columns = fgetcsv($f, null, $this->column_delimiter);

		// Заменяем имена колонок из файла на внутренние имена колонок
		foreach($this->columns as &$column)
		{ 
			if($internal_name = $this->internal_column_name($column))
			{
				$this->internal_columns_names[$column] = $internal_name;
				$column = $internal_name;
			}
		}

		// Если нет названия товара - не будем импортировать
		if(!in_array('name', $this->columns) && !in_array('sku', $this->columns))
			return false;
	 	

		// Переходим на заданную позицию, если импортируем не сначала
		if($from = $this->request->get('from'))
			fseek($f, $from);
		
		// Массив импортированных товаров
		$imported_items = array();	
		
		// Проходимся по строкам, пока не конец файла
		// или пока не импортировано достаточно строк для одного запроса
		for($k=0; !feof($f) && $k<$this->articles_count; $k++)
		{ 
			// Читаем строку
			$line = fgetcsv($f, 0, $this->column_delimiter);

			$article = null;			

			if(is_array($line))			
			// Проходимся по колонкам строки
			foreach($this->columns as $i=>$col)
			{
				// Создаем массив item[название_колонки]=значение
 				if(isset($line[$i]) && !empty($line) && !empty($col))
					$article[$col] = $line[$i];
			}
			
			// Импортируем этот товар
	 		if($imported_item = $this->import_item($article))
				$imported_items[] = $imported_item;
		}
		
		// Запоминаем на каком месте закончили импорт
 		$from = ftell($f);
 		
 		// И закончили ли полностью весь файл
 		$result->end = feof($f);

		fclose($f);
		$size = filesize($this->import_files_dir.$this->import_file);
		
		// Создаем объект результата
		$result->from = $from;          // На каком месте остановились
		$result->totalsize = $size;     // Размер всего файла
		$result->items = $imported_items;   // Импортированные товары
	
		return $result;
	}
	
	// Импорт одного товара $item[column_name] = value;
	private function import_item($item)
	{
		$imported_item = new stdClass;
		
		// Проверим не пустое ли название и артинкул (должно быть хоть что-то из них)
		if(empty($item['name']))
			return false;

		// Подготовим товар для добавления в базу
		$article = array();
		
		if(isset($item['name']))
			$article['name'] = trim($item['name']);

		if(isset($item['meta_title']))
			$article['meta_title'] = trim($item['meta_title']);
		
		// if(isset($item['demo']))
			// $article['demo'] = trim($item['demo']);

		if(isset($item['meta_keywords']))
			$article['meta_keywords'] = trim($item['meta_keywords']);
			
		// if(isset($item['supplier_url']))
			// $article['supplier_url'] = trim($item['supplier_url']);

		if(isset($item['meta_description']))
			$article['meta_description'] = trim($item['meta_description']);

		// if(isset($item['supplier_price']))            
			// $article['supplier_price'] = trim($item['supplier_price']); 
				
		if(isset($item['annotation']))
			$article['annotation'] = trim($item['annotation']);

		if(isset($item['description']))
			$article['body'] = trim($item['description']);
	
		if(isset($item['visible']))
			$article['visible'] = intval($item['visible']);

		if(isset($item['featured']))
			$article['featured'] = intval($item['featured']);
	
		if(!empty($item['url'])){
			$article['url'] = trim($item['url']);
		}elseif(!empty($item['name'])){
			$article['url'] = $this->translit($item['name']);
		}
	
		// Если задан бренд
		// if(!empty($item['brand']))
		// {
			// $item['brand'] = trim($item['brand']);
			// Найдем его по имени
			// $this->db->query('SELECT id FROM __brands WHERE name=?', $item['brand']);
			// if(!$article['brand_id'] = $this->db->result('id'))
				// Создадим, если не найден
				// $article['brand_id'] = $this->brands->add_brand(array('name'=>$item['brand'], 'meta_title'=>$item['brand'], 'meta_keywords'=>$item['brand'], 'meta_description'=>$item['brand']));
		// }
		
		// Если задана категория
		$category_id = null;
		$categories_ids = array();
		if(!empty($item['category']))
		{
			foreach(explode($this->category_delimiter, $item['category']) as $c)
				if($cid = $this->import_category($c)) $categories_ids[] = $cid;
				
			$category_id = reset($categories_ids);
		}
		
		// Если задан магазин
		// $store_id = null;
		// $stores_ids = array();
		// if(!empty($item['store']))
		// {
			// foreach(explode($this->store_delimiter, $item['store']) as $c)
				// if($cid = $this->import_store($c)) $stores_ids[] = $cid;
				

					// return false;

			// $store_id = reset($stores_ids);
		// }
		
		// Подготовим вариант товара
		// $variant = array();
		
		// if(isset($item['variant']))
			// $variant['name'] = trim($item['variant']);

			
				// if(isset($item['currency']))
	// if($item['currency'] == '')
		// $variant['currency'] = null;
	// else
		// {
		// $variant['currency'] = str_replace(',', '.', trim($item['currency']));
		// $currency = $this->money->get_currency(intval($variant['currency']));
		// }
		
				// Если присутствует код валюты определяем её ID
        // if(isset($item['currency_code'])) {		        
            // $currencies = $this->money->get_currencies();
            // foreach($currencies as $currency) {
              // if (trim($item['currency_code']) == $currency->code) {
													
                // $variant['currency'] = $currency->id;
								
								// print_r($currency->code);
              // }
            // }           
						// $currency = $this->money->get_currency(intval($variant['currency']));
        // }    
 
// if(isset($item['price']))
	// {
	// $variant['base_price'] = str_replace(',', '.', trim($item['price']));
	// if ($currency){

		// $variant['price'] = floatval($variant['base_price'])*$currency->rate_to/$currency->rate_from;
	// }else {
		// $variant['price'] = $variant['base_price'];
	// }
	// }
 
// if(isset($item['compare_price']))
	// {
	// $variant['base_compare_price'] = str_replace(',', '.', trim($item['compare_price']));
	// if ($currency)
		// $variant['compare_price'] = floatval($variant['base_compare_price'])*$currency->rate_to/$currency->rate_from;
	// else 
		// $variant['compare_price'] = $variant['base_compare_price'];
	// }
	
	
			
		// if(isset($item['stock']))
			// if($item['stock'] == '')
				// $variant['stock'] = null;
			// else
				// $variant['stock'] = trim($item['stock']);
			
		// if(isset($item['sku']))
			// $variant['sku'] = trim($item['sku']);
			
			// if(isset($item['supplier_price']))
			// $variant['supplier_price'] = trim($item['supplier_price']);
		

		
		// Если задан артикул варианта, найдем этот вариант и соответствующий товар
		// if(!empty($variant['sku']))
		// {
			// $this->db->query('SELECT v.id as variant_id, v.article_id FROM __variants v, __articles p WHERE v.sku=? AND v.article_id = p.id LIMIT 1', $variant['sku']);
			// $this->db->query('SELECT id as variant_id, article_id FROM __variants, __articles WHERE sku=? AND __variants.article_id = __articles.id LIMIT 1', $variant['sku']);
			// $result = $this->db->result();
			// if($result)
			// {
				// и обновим товар
				// if(!empty($article))
					// $this->articles->update_article($result->article_id, $article);
				// и вариант
				// if(!empty($variant))
					// $this->variants->update_variant($result->variant_id, $variant);
				
				// $article_id = $result->article_id;
				// $variant_id = $result->variant_id;
				// Обновлен
				// $imported_item->status = 'updated';
			// }
		// }
		
		// Если на прошлом шаге товар не нашелся, и задано хотя бы название товара
		if(isset($item['name']))
		{
			// if(!empty($variant['sku']) && empty($variant['name']))
				// $this->db->query('SELECT v.id as variant_id, p.id as article_id FROM __articles p LEFT JOIN __variants v ON v.article_id=p.id WHERE v.sku=? LIMIT 1', $variant['sku']);			
			// elseif(isset($item['variant']))
				// $this->db->query('SELECT v.id as variant_id, p.id as article_id FROM __articles p LEFT JOIN __variants v ON v.article_id=p.id AND v.name=? WHERE p.name=? LIMIT 1', $item['variant'], $item['name']);
			// else
				$this->db->query('SELECT p.id as article_id FROM __articles p WHERE p.name=? LIMIT 1', $item['name']);			
			
			$r =  $this->db->result();
			if($r)
			{
				$article_id = $r->article_id;
				// $variant_id = $r->variant_id;
			}
			// Если вариант найден - обновляем,
			if(!empty($article_id))
			{
				// $this->variants->update_variant($variant_id, $variant);
				$this->articles->update_article($article_id, $article);				
				$imported_item->status = 'updated';		
			}
			// Иначе - добавляем
			elseif(empty($article_id))
			{
					$article_id = $this->articles->add_article($article);
                $this->db->query('SELECT max(v.position) as pos FROM _articles v WHERE v.id=? LIMIT 1', $article_id);
                $pos =  $this->db->result('pos');

				$imported_item->status = 'added';
			}
		}
							
		// if(!empty($variant_id) && empty($article_id)){
			// print_r('ZETT');
			
			// }
			
		if(!empty($article_id))
		{


			// Нужно вернуть обновленный товар
			$imported_item->variant = $this->variants->get_variant(intval($variant_id));			
			$imported_item->article = $this->articles->get_article(intval($article_id));						
	
			// Добавляем категории к товару
			if(!empty($categories_ids))
				foreach($categories_ids as $c_id)
					$this->articlecategories->add_article_category($article_id, $c_id);
					
					
			// Добавляем магазины к товару
			// if(!empty($stores_ids)){
			// $this->stores->delete_article_stores($article_id);
			
			
			
			// $store_urls[] = null;
				// if(!empty($item['store_url'])){
					// $store_urls = explode($this->store_delimiter, $item['store_url']);
					// }else{
					// $store_urls = null;
					// }
					
					// $i=0;

				// foreach($stores_ids as $c_id){
				// if(empty($store_urls[$i])){
				// $store_urls[$i] = '';
					// }
					// $this->stores->add_article_store($article_id, $c_id, $store_urls[$i]);
					// print_r($store_urls[$i]);
					// $i++;
				// }
			// }
			
			
				// Теги товаров
	 		if(isset($item['tags'])){
	 		$tags = explode(',', $item['tags']);		
				foreach($tags as $tag)
				{
					if(!empty($tag))
					{
						// Найдем тег по имени
						$this->db->query('SELECT id FROM __tags WHERE name=?', $tag);
						$tag_id = $this->db->result('id');
						
						// Если не найдена - добавим ее
						if(empty($tag_id)){
							$tag->url = $this->translit($tag);
							$tag_id = $this->tags->add_tag($tag);
						}
							$this->tags->add_tag_item('article',$article_id,$tag_id);
					}
				}
			}

	
	 		// Изображения товаров
	 		if(isset($item['images']))
	 		{
	 			// Изображений может быть несколько, через запятую
	 			$images = explode(',', $item['images']);
	 			foreach($images as $image)
	 			{
	 				$image = trim($image);
	 				if(!empty($image))
	 				{
		 				// Имя файла
						$image_filename = pathinfo($image, PATHINFO_BASENAME);
		 				
		 				// Добавляем изображение только если такого еще нет в этом товаре
						$this->db->query('SELECT filename FROM __images WHERE article_id=? AND (filename=? OR filename=?) LIMIT 1', $article_id, $image_filename, $image);
						if(!$this->db->result('filename'))
						{
							$this->articles->add_image($article_id, $image);
						}
					}
	 			}
	 		}
	 		// Характеристики товаров
	 		// foreach($item as $feature_name=>$feature_value)
	 		// {
	 			// Если нет такого названия колонки, значит это название свойства
	 			// if(!in_array($feature_name, $this->internal_columns_names))
	 			// { 
	 				// Свойство добавляем только если для товара указана категория
					// if($category_id)
					// {
						// $this->db->query('SELECT f.id FROM __features f WHERE f.name=? LIMIT 1', $feature_name);
						// if(!$feature_id = $this->db->result('id'))
							// $feature_id = $this->features->add_feature(array('name'=>$feature_name));
							
						// $this->features->add_feature_category($feature_id, $category_id);				
						// foreach(explode(',', $feature_value) as $f_value)        
                        // $this->features->update_option($article_id, $feature_id, $f_value);

					// }
					
	 			// }
	 		// } 	
			// print_r($imported_item);
 		return $imported_item;
	 	}	
	}
	
	
	// Отдельная функция для импорта категории
	private function import_category($category)
	{			
		// Поле "категория" может состоять из нескольких имен, разделенных subcategory_delimiter-ом
		// Только неэкранированный subcategory_delimiter может разделять категории
		$delimiter = $this->subcategory_delimiter;
		$names = preg_split('#\s*(?<!\\\)\\'.$delimiter.'\s*#', $category, 0, PREG_SPLIT_DELIM_CAPTURE);
		$id = null;
		$parent = 0; 
		
		
		
		// Для каждой категории
		foreach($names as $name)
		{
			// Заменяем \/ на /
			$name = trim(str_replace("\\$delimiter", $delimiter, $name));
			if(!empty($name))
			{
				// Найдем категорию по имени
				$this->db->query('SELECT id FROM __articlecategories WHERE name=? AND parent_id=?', $name, $parent);
				$id = $this->db->result('id');
				
				// Если не найдена - добавим ее
				if(empty($id))
					$id = $this->articlecategories->add_articlecategory(array('name'=>$name, 'parent_id'=>$parent, 'meta_title'=>$name,  'meta_keywords'=>$name,  'meta_description'=>$name, 'url'=>$this->translit($name)));

				$parent = $id;
			}	
		}
		return $id;
	}
	
	
		// Отдельная функция для импорта категории
	private function import_store($store)
	{
		// Поле "категория" может состоять из нескольких имен, разделенных substore_delimiter-ом
		// Только неэкранированный substore_delimiter может разделять категории
		$delimiter = $this->substore_delimiter;
		$regex = "/\\DELIMITER((?:[^\\\\\DELIMITER]|\\\\.)*)/";
		$names = preg_split('#\s*(?<!\\\)\\'.$delimiter.'\s*#', $store, 0, PREG_SPLIT_DELIM_CAPTURE);
		$id = null;   
		$parent = 0; 
		
		// Для каждой категории
		foreach($names as $name)
		{
			// Заменяем \/ на /
			$name = trim(str_replace("\\$delimiter", $delimiter, $name));
			if(!empty($name))
			{
				// Найдем категорию по имени
				$this->db->query('SELECT id FROM __stores WHERE name=? AND parent_id=?', $name, $parent);
				$id = $this->db->result('id');
				
				// Если не найдена - добавим ее
				if(empty($id))
					$id = $this->stores->add_store(array('name'=>$name, 'parent_id'=>$parent, 'meta_title'=>$name,  'meta_keywords'=>$name,  'meta_description'=>$name, 'url'=>$this->translit($name)));

				$parent = $id;
			}	
		}
		return $id;
	}
	
	

	private function translit($text)
	{
		$ru = explode('-', "А-а-Б-б-В-в-Ґ-ґ-Г-г-Д-д-Е-е-Ё-ё-Є-є-Ж-ж-З-з-И-и-І-і-Ї-ї-Й-й-К-к-Л-л-М-м-Н-н-О-о-П-п-Р-р-С-с-Т-т-У-у-Ф-ф-Х-х-Ц-ц-Ч-ч-Ш-ш-Щ-щ-Ъ-ъ-Ы-ы-Ь-ь-Э-э-Ю-ю-Я-я"); 
		$en = explode('-', "A-a-B-b-V-v-G-g-G-g-D-d-E-e-E-e-E-e-ZH-zh-Z-z-I-i-I-i-I-i-J-j-K-k-L-l-M-m-N-n-O-o-P-p-R-r-S-s-T-t-U-u-F-f-H-h-TS-ts-CH-ch-SH-sh-SCH-sch---Y-y---E-e-YU-yu-YA-ya");

	 	$res = str_replace($ru, $en, $text);
		$res = preg_replace("/[\s]+/ui", '-', $res);
		$res = preg_replace('/[^\p{L}\p{Nd}\d-]/ui', '', $res);
	 	$res = strtolower($res);
	    return $res;  
	}
	
	// Фозвращает внутреннее название колонки по названию колонки в файле
	private function internal_column_name($name)
	{
 		$name = trim($name);
 		$name = str_replace('/', '', $name);
 		$name = str_replace('\/', '', $name);
		foreach($this->columns_names as $i=>$names)
		{
			foreach($names as $n)
				if(!empty($name) && preg_match("/^".preg_quote($name)."$/ui", $n))
					return $i;
		}
		return false;				
	}
}

$import_ajax = new ImportAjax();
header("Content-type: application/json; charset=UTF-8");
header("Cache-Control: must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");		
		
$json = json_encode($import_ajax->import());
print $json;