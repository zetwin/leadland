<?PHP

/**
 * Simpla CMS
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 * Этот класс использует шаблон product.tpl
 *
 */

require_once('View.php');


class ProductView extends View
{


	function fetch()
	{   
		//Меняем вид URL
		$product_url = $this->request->get('product_url', 'string');
		
		
		
		if(empty($product_url))
			return false;

		// Выбираем товар из базы
		$product = $this->products->get_product((string)$product_url);
		if(empty($product) || (!$product->visible && empty($_SESSION['admin'])))
			return false;
			
			if(!empty($_COOKIE['products_viewed'])){
				$products_viewed = explode(',', $_COOKIE['products_viewed']);
				$product_viewed = in_array($product->id, $products_viewed);
			}
			// Добавим текущий товар
			if(!$product_viewed)
			{
			 $products_viewed[] = $product->id;
			 $cookie_val = implode(',', $products_viewed);
			 setcookie("products_viewed", $cookie_val, time() + (3600 * 6), "/");
			 if($product->visible && empty($_SESSION['admin']) && !$product_viewed)
			 $this->products->update_views($product->id);
			}

// Категория и бренд товара
		$product->categories = $this->categories->get_categories(array('product_id'=>$product->id));
		$this->design->assign('brand', $this->brands->get_brand(intval($product->brand_id)));		
		$this->design->assign('category', reset($product->categories));		

				// Поставщики товара
		$product->stores = $this->stores->get_stores(array('product_id'=>$product->id));	
		$this->design->assign('product_stores', $product->stores);
		// Все Поставщики
		$stores = $this->stores->get_stores_tree();
		$this->design->assign('stores', $stores);
    //Меняем вид URL  
		
		$product->images = $this->products->get_images(array('product_id'=>$product->id));
		$product->image = reset($product->images);
		
		$product->instructions = $this->products->get_instructions(array('product_id'=>$product->id));
		$product->instruction = reset($product->instructions);

		$variants_names = array();
		
		
		$supplier_price = array();
		$variants = array();
		
		function formatBytes($bytes, $precision = 2) { 
			$units = array('б', 'кб', 'мб', 'гб', 'Тб'); 

			$bytes = max($bytes, 0); 
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
			$pow = min($pow, count($units) - 1); 

			// Uncomment one of the following alternatives
			// $bytes /= pow(1024, $pow);
			$bytes /= (1 << (10 * $pow)); 

			return round($bytes, $precision) . ' ' . $units[$pow]; 
		} 
		
		foreach($this->variants->get_variants(array('product_id'=>$product->id, 'in_stock'=>true)) as $v) {
			$supplier_price[$v->supplier_price][] = $v->id;
			$variants_properties[$v->name] = $v->id;
			$v->attachment_size = formatBytes($v->attachment_size);

			$variants_properties[val][] = explode("/", $v->name);
			$variants[$v->id] = $v;
		}
		
		

		$product->supplier_price = $supplier_price;
		
		$product->variants = $variants;
	
	// $multivariant->name = explode('/', $product->variants_names); 
	// foreach($multivariant->name as $key=>$value){
			// $multivariant[name][] = $multivariant->name[$key];
			
			// $tempval[value][] = $variants_properties[$key];
			
			// foreach($variants as $v){
				// $tempval[val][] = $variants_properties[val][$key];
				
				// $tempval[val][$key] = explode("/", $v->name);
			// }
		// }

	
		// $multivariant->name = explode('/', $product->variants_names);
		// foreach($multivariant->name as $key=>$value){
			// $multivariant[name][] = $multivariant->name[$key];
			
			// $tempval[value][] = $variants_properties[$key];
			
			// foreach($variants as $v){
				// $tempval[val][] = $variants_properties[val][$key];
				
				// $tempval[val][$key] = explode("/", $v->name);
			// }
		// }
		
		// $multivariant->value = $tempval;
		// $this->design->assign('multivariant', $multivariant);
		// print_r($variants);
		// print_r($multivariant);
		
		// Вариант по умолчанию
		if(($v_id = $this->request->get('variant', 'integer'))>0 && isset($variants[$v_id]))
			$product->variant = $variants[$v_id];
		else
			$product->variant = reset($variants);
					
		$product->features = $this->features->get_product_options(array('product_id'=>$product->id));
		$temp_options = array();
			foreach($this->features->get_product_options(array('product_id'=>$product->id)) as $option) {
				if(empty($temp_options[$option->feature_id]))
				{
					$temp_options[$option->feature_id] = new stdClass;
					$temp_options[$option->feature_id]->feature_id = $option->feature_id;
					$temp_options[$option->feature_id]->name = $option->name;
					$temp_options[$option->feature_id]->values = array();
				}
				$temp_options[$option->feature_id]->values[] = $option->value;
			}
			foreach($temp_options as $id => $option)
				$temp_options[$id]->value = implode(', ', $temp_options[$id]->values);

			$product->features = $temp_options;
		  // Автозаполнение имени для формы комментария
        if(!empty($this->user))
            $this->design->assign('comment_name', $this->user->name);
            $this->design->assign('comment_email', $this->user->email);
        
        // Принимаем комментарий
        if ($this->request->method('post') && $this->request->post('comment'))
        {
            $comment->name = $this->request->post('name');
            $comment->email = $this->request->post('email');
            $comment->text = $this->request->post('text');
            $captcha_code =  $this->request->post('captcha_code', 'string');
            
            // Передадим комментарий обратно в шаблон - при ошибке нужно будет заполнить форму
            $this->design->assign('comment_text', $comment->text);
            $this->design->assign('comment_name', $comment->name);
            $this->design->assign('comment_email', $comment->email);
			
			// Проверяем капчу и заполнение формы
			if ($_SESSION['captcha_code'] != $captcha_code)
			{
				$this->design->assign('error', 'captcha');
			}
			elseif (empty($comment->name))
			{
				$this->design->assign('error', 'empty_name');
			}
			elseif (empty($comment->text))
			{
				$this->design->assign('error', 'empty_comment');
			}
			else
			{
				// Создаем комментарий
				$comment->object_id = $product->id;
				$comment->type      = 'product';
				$comment->ip        = $_SERVER['REMOTE_ADDR'];
				
				// Если были одобренные комментарии от текущего ip, одобряем сразу
				$this->db->query("SELECT 1 FROM __comments WHERE approved=1 AND ip=? LIMIT 1", $comment->ip);
				if($this->db->num_rows()>0)
					$comment->approved = 1;
				
				// Добавляем комментарий в базу
				$comment_id = $this->comments->add_comment($comment);
				
				// Отправляем email
				$this->notify->email_comment_admin($comment_id);				
				
				// Приберем сохраненную капчу, иначе можно отключить загрузку рисунков и постить старую
				unset($_SESSION['captcha_code']);
				header('location: '.$_SERVER['REQUEST_URI'].'#comment_'.$comment_id);
			}			
		}
				
		// Связанные товары
		$related_ids = array();
		$related_products = array();
		foreach($this->products->get_related_products($product->id) as $p)
		{
			$related_ids[] = $p->related_id;
			$related_products[$p->related_id] = null;
		}
		if(!empty($related_ids))
		{ //Меняем вид URL
      		$categories = $this->categories->get_categories(array('product_id'=>$related_ids));
      		//Меняем вид URL
			
			foreach($this->products->get_products(array('id'=>$related_ids, 'visible'=>1)) as $p)
				$related_products[$p->id] = $p;
			
			$related_products_images = $this->products->get_images(array('product_id'=>array_keys($related_products)));
			foreach($related_products_images as $related_product_image)
				if(isset($related_products[$related_product_image->product_id]))
					$related_products[$related_product_image->product_id]->images[] = $related_product_image;
			$related_products_variants = $this->variants->get_variants(array('product_id'=>array_keys($related_products), 'in_stock'=>1));
			foreach($related_products_variants as $related_product_variant)
			{
				if(isset($related_products[$related_product_variant->product_id]))
				{
					$related_products[$related_product_variant->product_id]->variants[] = $related_product_variant;
				}
			}
			foreach($related_products as $id=>$r)
			{
				if(is_object($r))
				{
					$r->image = &$r->images[0];
					$r->variant = &$r->variants[0];
				}
				else
				{
					unset($related_products[$id]);
				}
			}
			$this->design->assign('related_products', $related_products);
		}
		else { // генерируемые связанные товары
    $category = reset($product->categories);
 
    $related_products = array();
    $after = false;
 
    $products = $this->products->get_products(array('category_id' => $category->id, 'limit' => 4, 'sort'=>'random', 'visible'=>1));
 
    foreach($products as $p)
    {
        if($after && count($related_products) < 15)
            $related_products[$p->id] = $p;
        elseif($p->id == $product->id)
            $after = true;
    }
 
    if(count($related_products) < 15)
        foreach($products as $p)
            if($p->id != $product->id && count($related_products) < 15)
                $related_products[$p->id] = $p;
            else break;  
 
    $related_products_images = $this->products->get_images(array('product_id'=>array_keys($related_products)));
    foreach($related_products_images as $related_product_image)
        if(isset($related_products[$related_product_image->product_id]))
            $related_products[$related_product_image->product_id]->images[] = $related_product_image;
    $related_products_variants = $this->variants->get_variants(array('product_id'=>array_keys($related_products), 'instock'=>true));
    foreach($related_products_variants as $related_product_variant)
    {
        if(isset($related_products[$related_product_variant->product_id]))
        {
            $related_product_variant->price *= (100-$discount)/100;
            $related_products[$related_product_variant->product_id]->variants[] = $related_product_variant;
        }
    }
    foreach($related_products as $r)
    {
        $r->image = &$r->images[0];
        $r->variant = &$r->variants[0];
    }
    $this->design->assign('related_products', $related_products);
	} // end: генерируемые связанные товары

		// Отзывы о товаре
		$comments = $this->comments->get_comments(array('type'=>'product', 'object_id'=>$product->id, 'approved'=>1, 'ip'=>$_SERVER['REMOTE_ADDR']));
		
		// Соседние товары
		$this->design->assign('next_product', $this->products->get_next_product($product->id,$product->position,$category->id));
		$this->design->assign('prev_product', $this->products->get_prev_product($product->id,$product->position,$category->id));
		
		// Теги
	$product->tags = $this->tags->get_tags(array('item_id'=>$product->id, 'type' => 'product'));  

		// И передаем его в шаблон
		$this->design->assign('product', $product);
		$this->design->assign('comments', $comments);
		
		// Категория и бренд товара
		$product->categories = $this->categories->get_categories(array('product_id'=>$product->id));
		$this->design->assign('brand', $this->brands->get_brand(intval($product->brand_id)));		
		$this->design->assign('category', reset($product->categories));		

		// Добавление в историю просмотров товаров
		$max_visited_products = 50; // Максимальное число хранимых товаров в истории
		$expire = time()+60*60*24*30; // Время жизни - 30 дней
		if(!empty($_COOKIE['browsed_products']))
		{
			$browsed_products = explode(',', $_COOKIE['browsed_products']);
			// Удалим текущий товар, если он был
			if(($exists = array_search($product->id, $browsed_products)) !== false)
				unset($browsed_products[$exists]);
		}
		// Добавим текущий товар
		$browsed_products[] = $product->id;
		$cookie_val = implode(',', array_slice($browsed_products, -$max_visited_products, $max_visited_products));
		setcookie("browsed_products", $cookie_val, $expire, "/");
		
		$this->design->assign('meta_title', $product->meta_title);
		$this->design->assign('meta_keywords', $product->meta_keywords);
		$this->design->assign('meta_description', $product->meta_description);
		$this->design->assign('units', $this->variants->units);
		
		if($product->last_update)
                $this->last_update = $product->last_update;
		
		return $this->design->fetch('product.tpl');
	}
	


}
