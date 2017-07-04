<?PHP

require_once('api/Simpla.php');

############################################
# Class Product - edit the static section
############################################
class ProductAdmin extends Simpla
{
	public function fetch()
	{
	
		$options = array();
		$product_categories = array();
		$product_stores = array();
		$variants = array();
		$images = array();
		$instructions = array();
		$product_features = array();
		$related_products = array();
		
		
		
		if($this->request->method('post') && !empty($_POST))
		{
			$product = new stdClass;
			$product->id = $this->request->post('id', 'integer');
			$product->name = $this->request->post('name');
			$product->visible = $this->request->post('visible', 'boolean');
			$product->featured = $this->request->post('featured');
			$product->brand_id = $this->request->post('brand_id', 'integer');

			$product->url = $this->request->post('url', 'string');
			$product->meta_title = $this->request->post('meta_title');
			$product->variants_names = $this->request->post('variants_names');
			$product->meta_keywords = $this->request->post('meta_keywords');
			$product->demo = $this->request->post('demo');
			$product->meta_description = $this->request->post('meta_description');
			
			$product->video = $this->request->post('video');
			$product->annotation = $this->request->post('annotation');
			$product->body = $this->request->post('body');

			// Варианты товара
			if($this->request->post('variants'))
			foreach($this->request->post('variants') as $n=>$va)
			{
				foreach($va as $i=>$v)
				{
					if(empty($variants[$i]))
						$variants[$i] = new stdClass;
					$variants[$i]->$n = $v;
				}
			}

			// Категории товара
			$product_categories = $this->request->post('categories');
			if(is_array($product_categories))
			{
				foreach($product_categories as $c)
				{
					$x = new stdClass;
					$x->id = $c;
					$pc[] = $x;
				}
				$product_categories = $pc;
			}
			

			
		// Магазины товара
			$product_stores = $this->request->post('stores');

$id = $product_stores['id'];
$url = $product_stores['url'];

foreach( $id as $key => $n ) {
	$product_stores_urls[$n]['id'] = $n;
	$product_stores_urls[$n]['url'] = $url[$key];
}

			if(is_array($product_stores_urls))
			{
				foreach($product_stores_urls as $s)
				{
					$xs = new stdClass;
					$xs->id = $s['id'];
					$xs->url = $s['url'];
					$ps[] = $xs;

				}
				$product_stores = $ps;
			}
								// print_r($product_stores);


			// Свойства товара
   	    	$options = $this->request->post('options');
			if(is_array($options))
			{
				foreach($options as $f_id=>$val)
				{
					$po[$f_id] = new stdClass;
					$po[$f_id]->feature_id = $f_id;
					$po[$f_id]->value = $val;
				}
				$options = $po;
			}

			// Связанные товары
			if(is_array($this->request->post('related_products')))
			{
				foreach($this->request->post('related_products') as $p)
				{
					$rp[$p] = new stdClass;
					$rp[$p]->product_id = $product->id;
					$rp[$p]->related_id = $p;
				}
				$related_products = $rp;
			}
				
			// Не допустить пустое название товара.
			if(empty($product->name))
			{			
				$this->design->assign('message_error', 'empty_name');
				if(!empty($product->id))
					$images = $this->products->get_images(array('product_id'=>$product->id));
					$instructions = $this->products->get_instructions(array('product_id'=>$product->id));
			}
			// Не допустить одинаковые URL разделов.
			elseif(($p = $this->products->get_product($product->url)) && $p->id!=$product->id)
			{			
				$this->design->assign('message_error', 'url_exists');
				if(!empty($product->id))
					$images = $this->products->get_images(array('product_id'=>$product->id));
					$instructions = $this->products->get_instructions(array('product_id'=>$product->id));
			}
			else
			{
				if(empty($product->id))
				{
	  				$product->id = $this->products->add_product($product);
	  				$product = $this->products->get_product($product->id);
					$this->design->assign('message_success', 'added');
	  			}
  	    		else
  	    		{
  	    			$this->products->update_product($product->id, $product);
  	    			$product = $this->products->get_product($product->id);
					$this->design->assign('message_success', 'updated');
  	    		}	
   	    		
   	    		if($product->id)
   	    		{
	   	    		// Категории товара
	   	    		$query = $this->db->placehold('DELETE FROM __products_categories WHERE product_id=?', $product->id);
	   	    		$this->db->query($query);
	 	  		    if(is_array($product_categories))
		  		    {
		  		    	foreach($product_categories as $i=>$category)
	   	    				$this->categories->add_product_category($product->id, $category->id, $i);
	  	    		}
							
							// Магазины товара
	   	    		$query = $this->db->placehold('DELETE FROM __products_stores WHERE product_id=?', $product->id);
	   	    		$this->db->query($query);
	 	  		    if(is_array($product_stores))
		  		    {
		  		    	foreach($product_stores as $i=>$store)
	   	    				$this->stores->add_product_store($product->id, $store->id, $store->url, $i);
	  	    		}
	
	   	    		// Варианты
		  		    if(is_array($variants))
		  		    { 
	 					$variants_ids = array();
						foreach($variants as $index=>&$variant)
						{
							if($variant->stock == '∞' || $variant->stock == '')
								$variant->stock = null;
								
							// Удалить файл
							if(!empty($_POST['delete_attachment'][$index]))
							{
								$this->variants->delete_attachment($variant->id);
							}
	
		 					// Загрузить файлы
		 					if(!empty($_FILES['attachment']['tmp_name'][$index]) && !empty($_FILES['attachment']['name'][$index]))
		 					{
			 					$attachment_tmp_name = $_FILES['attachment']['tmp_name'][$index];					
			 					$attachment_name = $_FILES['attachment']['name'][$index];
			 					$attachment_size = $_FILES['attachment']['size'][$index];
		 						move_uploaded_file($attachment_tmp_name, $this->config->root_dir.'/'.$this->config->downloads_dir.$attachment_name);
		 						$variant->attachment = $attachment_name;
		 						$variant->attachment_size = $attachment_size;
		 					}
	
							if(!empty($variant->id))
								$this->variants->update_variant($variant->id, $variant);
							else
							{
								$variant->product_id = $product->id;
								$variant->id = $this->variants->add_variant($variant);
							}
							$variant = $this->variants->get_variant($variant->id);
							if(!empty($variant->id))
					 			$variants_ids[] = $variant->id;
						}
						
	
						// Удалить непереданные варианты
						$current_variants = $this->variants->get_variants(array('product_id'=>$product->id));
						foreach($current_variants as $current_variant)
							if(!in_array($current_variant->id, $variants_ids))
	 							$this->variants->delete_variant($current_variant->id);
	 							 					
	 					//if(!empty($))
						
						// Отсортировать  варианты
						asort($variants_ids);
						$i = 0;
						foreach($variants_ids as $variant_id)
						{ 
							$this->variants->update_variant($variants_ids[$i], array('position'=>$variant_id));
							$i++;
						}
					}
	
					// Удаление изображений
					$images = (array)$this->request->post('images');
					$current_images = $this->products->get_images(array('product_id'=>$product->id));
					foreach($current_images as $image)
					{
						if(!in_array($image->id, $images))
	 						$this->products->delete_image($image->id);
						}
	
					// Порядок изображений
					if($images = $this->request->post('images'))
					{
	 					$i=0;
						foreach($images as $id)
						{	
							$this->products->update_image($id, array('position'=>$i));
							$i++;
						}
					}
	   	    		// Загрузка изображений
		  		    if($images = $this->request->files('images'))
		  		    {
						for($i=0; $i<count($images['name']); $i++)
						{
				 			if ($image_name = $this->image->upload_image($images['tmp_name'][$i], $images['name'][$i]))
				 			{
			  	   				$this->products->add_image($product->id, $image_name);
			  	   			}
							else
							{
								$this->design->assign('error', 'error uploading image');
							}
						}
					}
	   	    		// Загрузка изображений из интернета и drag-n-drop файлов
		  		    if($images = $this->request->post('images_urls'))
		  		    {
						foreach($images as $url)
						{
							// Если не пустой адрес и файл не локальный
							if(!empty($url) && $url != 'http://' && strstr($url,'/')!==false)
					 			$this->products->add_image($product->id, $url);
					 		elseif($dropped_images = $this->request->files('dropped_images'))
					  		{
					 			$key = array_search($url, $dropped_images['name']);
							 	if ($key!==false && $image_name = $this->image->upload_image($dropped_images['tmp_name'][$key], $dropped_images['name'][$key]))
						  	   				$this->products->add_image($product->id, $image_name);
							}
						}
					}
					$images = $this->products->get_images(array('product_id'=>$product->id));
	
	//ТЕХ ДОКУМЕНТАЦИЯ
	// Удаление тех документации
					$instructions = (array)$this->request->post('instructions');
					
					
					
					$current_instructions = $this->products->get_instructions(array('product_id'=>$product->id));
					foreach($current_instructions as $instruction)
					{
						if(!in_array($instruction->id, $instructions))
	 						$this->products->delete_instruction($instruction->id);
						}
					
					// Порядок тех документации
					if($instructions = $this->request->post('instructions'))
					{
	 					$i=0;
						foreach($instructions as $id)
						{
							$names = $this->request->post('names');
							$this->products->update_instruction($id, array('position'=>$i, 'name'=>$names[$i]));
							$i++;
						}
					}
					
	   	    		// Загрузка тех документации
		  		    if($instructions = $this->request->files('instructions'))
		  		    {
						for($i=0; $i<count($instructions['name']); $i++)
						{
				 			if ($instruction_name = $this->instruction->upload_instruction($instructions['tmp_name'][$i], $instructions['name'][$i]))
				 			{
			  	   				$this->products->add_instruction($product->id, $instruction_name, $names);
			  	   			}
							else
							{
								$this->design->assign('error', 'error uploading instruction');
							}
						}
					}
	   	    		// Загрузка тех документации из интернета и drag-n-drop файлов
		  		    if($instructions = $this->request->post('instructions_urls'))
		  		    {
						foreach($instructions as $url)
						{
							// Если не пустой адрес и файл не локальный
							if(!empty($url) && $url != 'http://' && strstr($url,'/')!==false)
					 			$this->products->add_instruction($product->id, $url);
					 		elseif($dropped_instructions = $this->request->files('dropped_instructions'))
					  		{
					 			$key = array_search($url, $dropped_instructions['name']);
							 	if ($key!==false && $instruction_name = $this->instruction->upload_instruction($dropped_instructions['tmp_name'][$key], $dropped_instructions['name'][$key]))
						  	   				$this->products->add_instruction($product->id, $instruction_name);
							}
						}
					}
					$instructions = $this->products->get_instructions(array('product_id'=>$product->id));	
	//ТЕХ ДОКУМЕНТАЦИЯ КОНЕЦ
	
	
	
	
	
	   	    		// Характеристики товара
	   	    		
	   	    		// Удалим все из товара
					foreach($this->features->get_product_options($product->id) as $po)
						$this->features->delete_option($product->id, $po->feature_id);
						
					// Свойства текущей категории
					$category_features = array();
					foreach($this->features->get_features(array('category_id'=>$product_categories[0])) as $f)
						$category_features[] = $f->id;
	
	  	    		if(is_array($options))
   foreach($options as $option)
   {
  	 if(in_array($option->feature_id, $category_features))
       	if(is_array($option->value))
           	foreach($option->value as $value)
               	$this->features->update_option($product->id, $option->feature_id, $value);
       	else
  		$this->features->update_option($product->id, $option->feature_id, $option->value);
   }

					
					// Новые характеристики
					$new_features_names = $this->request->post('new_features_names');
					$new_features_values = $this->request->post('new_features_values');
					if(is_array($new_features_names) && is_array($new_features_values))
					{
						foreach($new_features_names as $i=>$name)
						{
							$value = trim($new_features_values[$i]);
							if(!empty($name) && !empty($value))
							{
								$query = $this->db->placehold("SELECT * FROM __features WHERE name=? LIMIT 1", trim($name));
								$this->db->query($query);
								$feature_id = $this->db->result('id');
								if(empty($feature_id))
								{
									$feature_id = $this->features->add_feature(array('name'=>trim($name)));
								}
								$this->features->add_feature_category($feature_id, reset($product_categories)->id);
								$this->features->update_option($product->id, $feature_id, $value);
							}
						}
						// Свойства товара
						$options = $this->features->get_product_options($product->id);
					}
					
					// Связанные товары
	   	    		$query = $this->db->placehold('DELETE FROM __related_products WHERE product_id=?', $product->id);
	   	    		$this->db->query($query);
	 	  		    if(is_array($related_products))
		  		    {
		  		    	$pos = 0;
		  		    	foreach($related_products  as $i=>$related_product)
	   	    				$this->products->add_related_product($product->id, $related_product->related_id, $pos++);
	  	    		}
  	    		}
			}
			
				// Теги
		$this->tags->delete_tags($product->id, 'product');
		$this->tags->add_tags('product', $product->id, $this->request->post('tags'));
		
		
			
			//header('Location: '.$this->request->url(array('message_success'=>'updated')));
		}
		else
		{
			$id = $this->request->get('id', 'integer');
			$product = $this->products->get_product(intval($id));

			if($product)
			{
				
				// Категории товара
				$product_categories = $this->categories->get_categories(array('product_id'=>$product->id));
				
				// Категории товара
				$product_stores = $this->stores->get_stores(array('product_id'=>$product->id));
				// $product_stores['store_url'] =77;
							// print_r($product_stores);
				// Варианты товара
				$variants = $this->variants->get_variants(array('product_id'=>$product->id));
				
				// Изображения товара
				$images = $this->products->get_images(array('product_id'=>$product->id));
				//Документация товара
				$instructions = $this->products->get_instructions(array('product_id'=>$product->id));
				
				// Свойства товара
				$options = $this->features->get_options(array('product_id'=>$product->id));
				
				// Связанные товары
				$related_products = $this->products->get_related_products(array('product_id'=>$product->id));
				
			}
			else
			{
				// Сразу активен
				$product = new stdClass;
				$product->visible = 1;			
			}
		}
		
		// Теги
		$product->tags = $this->tags->get_tags(array('item_id'=>$product->id, 'type' => 'product'));
		
		// Все теги
	  $alltags = $this->tags->get_alltags();
		// print_r($alltags);
	 	$this->design->assign('alltags', $alltags);
		
		if(empty($variants))
			$variants = array(1);
			
		if(empty($product_categories))
		{
			if($category_id = $this->request->get('category_id')){
				$product_categories[0] = new stdClass();
				$product_categories[0]->id = $category_id;		
			}
			else{
				$product_categories = array(1);
			}
		}
		
		
		
		
		
		if(empty($product_stores))
		{
			if($store_id = $this->request->get('store_id')){
				$product_stores = new stdClass();
				$product_stores->id = $store_id;
			}else{
				$product_stores = array(1);
			}
		}
		
		if(empty($product->brand_id) && $brand_id=$this->request->get('brand_id'))
		{
			$product->brand_id = $brand_id;
		}
			
		if(!empty($related_products))
		{
			foreach($related_products as &$r_p)
				$r_products[$r_p->related_id] = &$r_p;
			$temp_products = $this->products->get_products(array('id'=>array_keys($r_products)));
			foreach($temp_products as $temp_product)
				$r_products[$temp_product->id] = $temp_product;
		
			$related_products_images = $this->products->get_images(array('product_id'=>array_keys($r_products)));
			foreach($related_products_images as $image)
			{
				$r_products[$image->product_id]->images[] = $image;
			}
		}
			
if(is_array($options)){
		$temp_options = array();
		foreach($options as $option) {
			if(empty($temp_options[$option->feature_id]))
				$temp_options[$option->feature_id] = new stdClass;

			$temp_options[$option->feature_id]->feature_id = $option->feature_id;
			if(is_array($option->value))
				$temp_options[$option->feature_id]->values = $option->value;
			else
				$temp_options[$option->feature_id]->values[] = $option->value;
		}

		$options = $temp_options;
	}

			

		$this->design->assign('product', $product);
		$this->design->assign('product_categories', $product_categories);
		$this->design->assign('product_stores', $product_stores);
					// print_r($product_stores);
		$this->design->assign('product_variants', $variants);
		$this->design->assign('product_images', $images);
		$this->design->assign('product_instructions', $instructions);
		$this->design->assign('options', $options);
		$this->design->assign('related_products', $related_products);		
		
		// Все бренды
		$brands = $this->brands->get_brands();
		$this->design->assign('brands', $brands);
		
		// Все категории
		$categories = $this->categories->get_categories_tree();
		$this->design->assign('categories', $categories);
		
		// Все магазины
		$stores = $this->stores->get_stores_tree();
		$this->design->assign('stores', $stores);
		
		// Все валюты
		$currencies = $this->money->get_currencies();
		$this->design->assign('currencies', $currencies); 
		
		// Все свойства товара
		$category = reset($product_categories);
		if(!is_object($category))
			$category = reset($categories);		
		if(is_object($category))
		{
			$features = $this->features->get_features(array('category_id'=>$category->id));
			$this->design->assign('features', $features);
		}
		$this->design->assign('units', $this->variants->units);
 	  	return $this->design->fetch('product.tpl');
	}
}

