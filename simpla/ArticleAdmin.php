<?PHP

require_once('api/Simpla.php');

############################################
# Class Article - edit the static section
############################################
class ArticleAdmin extends Simpla
{
	public function fetch()
	{
	
		$options = array();
		$article_categories = array();
		$images = array();
		$article_features = array();
		$related_articles = array();
	
		if($this->request->method('post') && !empty($_POST))
		{
			$article = new stdClass;
			$article->id = $this->request->post('id', 'integer');
			$article->name = $this->request->post('name');
			$article->visible = $this->request->post('visible', 'boolean');
			$article->featured = $this->request->post('featured');
			$article->brand_id = $this->request->post('brand_id', 'integer');

			$article->url = $this->request->post('url', 'string');
			$article->meta_title = $this->request->post('meta_title');
			$article->meta_keywords = $this->request->post('meta_keywords');
			$article->meta_description = $this->request->post('meta_description');
			
			$article->related_category = $this->request->post('related_category');
			$article->annotation = $this->request->post('annotation');
			$article->body = $this->request->post('body');


			// Категории товара
			$article_categories = $this->request->post('articlecategories');
			if(is_array($article_categories))
			{
				foreach($article_categories as $c)
				{
					$x = new stdClass;
					$x->id = $c;
					$pc[] = $x;
				}
				$article_categories = $pc;
			}

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
					$rp[$p]->article_id = $article->id;
					$rp[$p]->related_id = $p;
				}
				$related_products = $rp;
			}
				
			// Не допустить пустое название товара.
			if(empty($article->name))
			{			
				$this->design->assign('message_error', 'empty_name');
				if(!empty($article->id))
					$images = $this->articles->get_images(array('article_id'=>$article->id));
			}
			// Не допустить одинаковые URL разделов.
			elseif(($p = $this->articles->get_article($article->url)) && $p->id!=$article->id)
			{			
				$this->design->assign('message_error', 'url_exists');
				if(!empty($article->id))
					$images = $this->articles->get_images(array('article_id'=>$article->id));
			}
			else
			{
				if(empty($article->id))
				{
	  				$article->id = $this->articles->add_article($article);
	  				$article = $this->articles->get_article($article->id);
					$this->design->assign('message_success', 'added');
	  			}
  	    		else
  	    		{
  	    			$this->articles->update_article($article->id, $article);
  	    			$article = $this->articles->get_article($article->id);
					$this->design->assign('message_success', 'updated');
  	    		}	
   	    		
   	    		if($article->id)
   	    		{
	   	    		// Категории товара
	   	    		$query = $this->db->placehold('DELETE FROM __articles_in_categories WHERE article_id=?', $article->id);
	   	    		$this->db->query($query);
	 	  		    if(is_array($article_categories))
		  		    {
		  		    	foreach($article_categories as $i=>$articlecategory)
	   	    				$this->articlecategories->add_article_category($article->id, $articlecategory->id, $i);
	  	    		}
	
	
					// Удаление изображений
					$images = (array)$this->request->post('images');
					$current_images = $this->articles->get_images(array('article_id'=>$article->id));
					foreach($current_images as $image)
					{
						if(!in_array($image->id, $images))
	 						$this->articles->delete_image($image->id);
						}
	
					// Порядок изображений
					if($images = $this->request->post('images'))
					{
	 					$i=0;
						foreach($images as $id)
						{
							$this->articles->update_image($id, array('position'=>$i));
							$i++;
						}
					}
	   	    		// Загрузка изображений
		  		    if($images = $this->request->files('images'))
		  		    {
						for($i=0; $i<count($images['name']); $i++)
						{
				 			if ($image_name = $this->imagearticle->upload_image($images['tmp_name'][$i], $images['name'][$i]))
				 			{
			  	   				$this->articles->add_image($article->id, $image_name);
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
					 			$this->articles->add_image($article->id, $url);
					 		elseif($dropped_images = $this->request->files('dropped_images'))
					  		{
					 			$key = array_search($url, $dropped_images['name']);
							 	if ($key!==false && $image_name = $this->imagearticle->upload_image($dropped_images['tmp_name'][$key], $dropped_images['name'][$key]))
						  	   				$this->articles->add_image($article->id, $image_name);
							}
						}
					}
					$images = $this->articles->get_images(array('article_id'=>$article->id));
	
	   	    		// Характеристики товара
	   	    		
	   	    		// Удалим все из товара
					// foreach($this->features->get_article_options($article->id) as $po)
						// $this->features->delete_option($article->id, $po->feature_id);
						
					//Свойства текущей категории
					// $category_features = array();
					// foreach($this->features->get_features(array('category_id'=>$article_categories[0])) as $f)
						// $category_features[] = $f->id;
	
	  	    		if(is_array($options))
   foreach($options as $option)
   {
  	 if(in_array($option->feature_id, $category_features))
       	if(is_array($option->value))
           	foreach($option->value as $value)
               	$this->features->update_option($article->id, $option->feature_id, $value);
       	else
  		$this->features->update_option($article->id, $option->feature_id, $option->value);
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
								$this->features->add_feature_category($feature_id, reset($article_categories)->id);
								$this->features->update_option($article->id, $feature_id, $value);
							}
						}
						// Свойства товара
						$options = $this->features->get_article_options($article->id);
					}
					
					// Связанные товары
	   	    		$query = $this->db->placehold('DELETE FROM __related_products WHERE article_id=?', $article->id);
	   	    		$this->db->query($query);
	 	  		    if(is_array($related_products))
		  		    {
		  		    	$pos = 0;
		  		    	foreach($related_products  as $i=>$related_product)
	   	    				$this->articles->add_related_product($article->id, $related_product->related_id, $pos++);
	  	    		}
  	    		}
			}
			
			//header('Location: '.$this->request->url(array('message_success'=>'updated')));
		}
		else
		{
			$id = $this->request->get('id', 'integer');
			$article = $this->articles->get_article(intval($id));

			if($article)
			{
				
				// Категории товара
				$article_categories = $this->articlecategories->get_articlecategories(array('article_id'=>$article->id));
				
				// Изображения товара
				$images = $this->articles->get_images(array('article_id'=>$article->id));
				
				// Свойства товара
				$options = $this->features->get_options(array('article_id'=>$article->id));
				
				// Связанные товары
				$related_products = $this->articles->get_related_products(array('article_id'=>$article->id));
			}
			else
			{
				// Сразу активен
				$article = new stdClass;
				$article->visible = 1;			
			}
		}
		
		
		// if(empty($variants))
			// $variants = array(1);
			
		if(empty($article_categories))
		{
			if($articlecategory_id = $this->request->get('articlecategory_id'))
				$article_categories[0]->id = $articlecategory_id;		
			else
				$article_categories = array(1);
		}
		if(empty($article->brand_id) && $brand_id=$this->request->get('brand_id'))
		{
			$article->brand_id = $brand_id;
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
			
		if(is_array($options))
{
   $temp_options = array();
   foreach($options as $option) {
       $temp_options[$option->feature_id]->feature_id = $option->feature_id;
       if(is_array($option->value))  
           $temp_options[$option->feature_id]->values = $option->value;   
       else
           $temp_options[$option->feature_id]->values[] = $option->value;   
   }
       
   $options = $temp_options;
}

			

		$this->design->assign('article', $article);

		$this->design->assign('article_categories', $article_categories);
		$this->design->assign('article_variants', $variants);
		$this->design->assign('article_images', $images);
		$this->design->assign('options', $options);
		$this->design->assign('related_products', $related_products);
		
		// Все бренды
		$brands = $this->brands->get_brands();
		$this->design->assign('brands', $brands);
		
		// Все категории
		$articlecategories = $this->articlecategories->get_articlecategories_tree();
		$this->design->assign('articlecategories', $articlecategories);
		
		$categories = $this->categories->get_categories_tree();
		$this->design->assign('categories', $categories);
		
		// Все свойства товара
		$articlecategory = reset($article_categories);
		if(!is_object($articlecategory))
			$articlecategory = reset($articlecategories);		
		if(is_object($category))
		{
			$features = $this->features->get_features(array('category_id'=>$articlecategory->id));
			$this->design->assign('features', $features);
		}
		
 	  	return $this->design->fetch('article.tpl');
	}
}