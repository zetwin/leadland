<?php

/**
 * Работа с товарами
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 */

require_once('Simpla.php');

class Products extends Simpla
{
	/**
	* Функция возвращает товары
	* Возможные значения фильтра:
	* id - id товара или их массив
	* category_id - id категории или их массив
	* brand_id - id бренда или их массив
	* page - текущая страница, integer
	* limit - количество товаров на странице, integer
	* sort - порядок товаров, возможные значения: position(по умолчанию), name, price
	* keyword - ключевое слово для поиска
	* features - фильтр по свойствам товара, массив (id свойства => значение свойства)
	*/
	public function get_products($filter = array())
	{		
		// По умолчанию
		$limit = 50;
		$page = 1;
		$category_id_filter = '';
		$store_id_filter = '';
		$brand_id_filter = '';
		$product_id_filter = '';
		$features_filter = '';
		$keyword_filter = '';
		$visible_filter = '';
		$is_featured_filter = '';
		$discounted_filter = '';
		$in_stock_filter = '';
		$group_by = '';
		$order = 'p.position DESC';

		if(isset($filter['limit']))
			$limit = max(1, intval($filter['limit']));

		if(isset($filter['page']))
			$page = max(1, intval($filter['page']));

		$sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

		if(!empty($filter['id']))
			$product_id_filter = $this->db->placehold('AND p.id in(?@)', (array)$filter['id']);

		if(!empty($filter['category_id']))
		{
			$category_id_filter = $this->db->placehold('INNER JOIN __products_categories pc ON pc.product_id = p.id AND pc.category_id in(?@)', (array)$filter['category_id']);
			$group_by = "GROUP BY p.id";
		}
		
		if(!empty($filter['store_id']))
		{
			$store_id_filter = $this->db->placehold('INNER JOIN __products_stores ps ON ps.product_id = p.id AND ps.store_id in(?@)', (array)$filter['store_id']);
			$group_by = "GROUP BY p.id";
		}

		if(!empty($filter['brand_id']))
			$brand_id_filter = $this->db->placehold('AND p.brand_id in(?@)', (array)$filter['brand_id']);

		if(isset($filter['featured']))
			$is_featured_filter = $this->db->placehold('AND p.featured=?', intval($filter['featured']));

		if(isset($filter['discounted']))
			$discounted_filter = $this->db->placehold('AND (SELECT 1 FROM __variants pv WHERE pv.product_id=p.id AND pv.compare_price>0 LIMIT 1) = ?', intval($filter['discounted']));

		if(isset($filter['in_stock']))
			$in_stock_filter = $this->db->placehold('AND (SELECT count(*)>0 FROM __variants pv WHERE pv.product_id=p.id AND pv.price>0 AND (pv.stock IS NULL OR pv.stock>0) LIMIT 1) = ?', intval($filter['in_stock']));

		if(isset($filter['visible']))
			$visible_filter = $this->db->placehold('AND p.visible=?', intval($filter['visible']));
		// if(!empty($filter['visible']))
			// $visible_filter = $this->db->placehold('AND p.visible=? AND (SELECT count(*) FROM __categories, __products_categories WHERE __categories.id = __products_categories.category_id AND __categories.visible=1 AND p.id=__products_categories.product_id) > 0', intval($filter['visible']));
		
		//$products_stock_null_sort = "";
 		if(!empty($filter['sort']))
			switch ($filter['sort'])
			{
				case 'position':
				$order = 'p.position DESC';
				break;
				case 'name':
				$order = 'p.name';
				break;
				case 'random':
				$order = 'RAND()';
				break;
				case 'created':
				$order = 'p.created DESC';
				break;
				
						// по цене Низкие > Высокие
				case 'price_asc':
				$order = '(SELECT pv.price FROM __variants pv WHERE (pv.stock IS NULL OR pv.stock>0) AND p.id = pv.product_id AND pv.position=(SELECT MIN(position) FROM __variants WHERE (stock>0 OR stock IS NULL) AND product_id=p.id LIMIT 1) LIMIT 1)';
				break;
		 
				// по цене Высокие < Низкие
				case 'price_desc':
				$order = '(SELECT pv.price FROM __variants pv WHERE (pv.stock IS NULL OR pv.stock>0) AND p.id = pv.product_id AND pv.position=(SELECT MIN(position) FROM __variants WHERE (stock>0 OR stock IS NULL) AND product_id=p.id LIMIT 1) LIMIT 1) DESC';
				break;
				
			}
			
			// if(!empty($filter['sort'])){
			// $order = 'IF(v.stock < 1,1,0),'.$order;
			// $group_by = 'GROUP BY p.id';
			// $products_stock_null_sort = 'INNER JOIN __variants v ON p.id = v.product_id';
			// } 

		if(!empty($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				$keyword_filter .= $this->db->placehold('AND (p.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR p.meta_keywords LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR p.annotation LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR p.body LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR p.id in (SELECT __tags_items.item_id FROM __tags_items WHERE __tags_items.tag_id IN (SELECT __tags.id FROM __tags WHERE __tags.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%")))');
		}

		if(!empty($filter['features']) && !empty($filter['features']))
			foreach($filter['features'] as $feature=>$value)
				$features_filter .= $this->db->placehold('AND p.id in (SELECT product_id FROM s_options WHERE feature_id=? AND value in (?@) ) ', $feature, $value);
				
		$query = "SELECT  
					p.id,
					p.url,
					p.brand_id,
					p.name,
					p.video,
					p.annotation,
					p.body,
					p.position,
					p.created as created,
					p.visible, 
					p.demo, 
					p.views,
					p.featured,
					p.rating,
					p.votes,
					p.meta_title, 
					p.meta_keywords, 
					p.meta_description, 
					p.variants_names,
					b.name as brand,
					b.url as brand_url
				FROM __products p		
				$category_id_filter 
				$store_id_filter 
				LEFT JOIN __brands b ON p.brand_id = b.id
				$products_stock_null_sort
				WHERE 
					1
					$product_id_filter
					$brand_id_filter
					$features_filter
					$keyword_filter
					$is_featured_filter
					$discounted_filter
					$in_stock_filter
					$visible_filter
				$group_by
				ORDER BY $order
					$sql_limit";

								// print_r($query);
		$this->db->query($query);
		// print_r($query);
		// $products = $this->db->results();
		
    // if(!empty($products))
    // {
	    // $all_category = $this->categories->get_all_categories();
	    // foreach($products as $p)
	    // {
		    // foreach($all_category as $c)
		    // {
			    // if($c->id == $p->category_id)
			    // {
			     // $p->full_url = $c->full_url;
			     // if($p->brand_url)
			     // $p->full_url = $p->full_url.$p->brand_url."/".$p->url;
			    // }
		    // }
	    // }
	// }

		return $this->db->results();
	}

	/**
	* Функция возвращает количество товаров
	* Возможные значения фильтра:
	* category_id - id категории или их массив
	* brand_id - id бренда или их массив
	* keyword - ключевое слово для поиска
	* features - фильтр по свойствам товара, массив (id свойства => значение свойства)
	*/
	public function count_products($filter = array())
	{		
		$category_store_filter = '';
		$store_id_filter = '';
		$brand_id_filter = '';
		$product_id_filter = '';
		$keyword_filter = '';
		$visible_filter = '';
		$is_featured_filter = '';
		$in_stock_filter = '';
		$discounted_filter = '';
		$features_filter = '';
		
	if(!empty($filter['category_id']) && !empty($filter['store_id'])){
			$category_store_filter = $this->db->placehold('INNER JOIN __products_categories pc ON pc.product_id = p.id AND pc.category_id in(?@) INNER JOIN __products_stores ps ON ps.product_id = p.id AND ps.store_id in(?@)', (array)$filter['category_id'], (array)$filter['store_id']);
	}else{
	if(!empty($filter['category_id']))
			$category_store_filter = $this->db->placehold('INNER JOIN __products_categories pc ON pc.product_id = p.id AND pc.category_id in(?@)', (array)$filter['category_id']);
		
		if(!empty($filter['store_id']))
			$category_store_filter = $this->db->placehold('INNER JOIN __products_stores pc ON pc.product_id = p.id AND pc.store_id in(?@)', (array)$filter['store_id']);
	} 
		if(!empty($filter['brand_id']))
			$brand_id_filter = $this->db->placehold('AND p.brand_id in(?@)', (array)$filter['brand_id']);

		if(!empty($filter['id']))
			$product_id_filter = $this->db->placehold('AND p.id in(?@)', (array)$filter['id']);
		
		if(isset($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				$keyword_filter .= $this->db->placehold('AND (p.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR p.meta_keywords LIKE "%'.$this->db->escape(trim($keyword)).'%") ');
		}

		if(isset($filter['featured']))
			$is_featured_filter = $this->db->placehold('AND p.featured=?', intval($filter['featured']));

		if(isset($filter['in_stock']))
			$in_stock_filter = $this->db->placehold('AND (SELECT count(*)>0 FROM __variants pv WHERE pv.product_id=p.id AND pv.price>0 AND (pv.stock IS NULL OR pv.stock>0) LIMIT 1) = ?', intval($filter['in_stock']));

		if(isset($filter['discounted']))
			$discounted_filter = $this->db->placehold('AND (SELECT 1 FROM __variants pv WHERE pv.product_id=p.id AND pv.compare_price>0 LIMIT 1) = ?', intval($filter['discounted']));

		if(isset($filter['visible']))
			$visible_filter = $this->db->placehold('AND p.visible=?', intval($filter['visible']));
		
		
		if(!empty($filter['features']) && !empty($filter['features']))
			foreach($filter['features'] as $feature=>$value)
				$features_filter .= $this->db->placehold('AND p.id in (SELECT product_id FROM s_options WHERE feature_id=? AND value in (?@) ) ', $feature, $value);
		
		$query = "SELECT count(distinct p.id) as count
				FROM __products AS p
				$category_store_filter
				WHERE 1
					$brand_id_filter
					$product_id_filter
					$keyword_filter
					$is_featured_filter
					$in_stock_filter
					$discounted_filter
					$visible_filter
					$features_filter ";

				
		$this->db->query($query);	
		return $this->db->result('count');
		
	}


	/**
	* Функция возвращает товар по id
	* @param	$id
	* @retval	object
	*/
	public function get_product($id)
	{
		if(is_int($id))
			$filter = $this->db->placehold('p.id = ?', $id);
		else
			$filter = $this->db->placehold('p.url = ?', $id);
			
		$query = "SELECT DISTINCT
					p.id,
					p.url,
					p.brand_id,
					p.name,
					p.video,
					p.annotation,
					p.body,
					p.position,
					p.created as created,
					p.visible, 
					p.views,
					p.demo,
					p.featured,
					p.rating,
					p.votes,
					p.meta_title, 
					p.meta_keywords, 
					p.meta_description,
					p.variants_names,
					p.last_update
				FROM __products AS p
                LEFT JOIN __brands b ON p.brand_id = b.id
                WHERE $filter
                GROUP BY p.id
                LIMIT 1";
		$this->db->query($query);
		$product = $this->db->result();
    // if (!empty($product))
    // {	 		
    	// $all_category = $this->categories->get_all_categories();
	    // foreach($all_category as $c)
	    // {
		    // if($c->id == $product->category_id)
		    // {
		    //// echo $c->full_url;
		     // $product->full_url = $c->full_url;
		     // if($product->brand_url)
		     // $product->full_url = $product->full_url.$product->brand_url."/".$product->url;
		    // }
	    // }
	// }	
		return $product;
	}

	public function update_product($id, $product)
	{
		$query = $this->db->placehold("UPDATE __products SET ?%, last_update=NOW() WHERE id in (?@) LIMIT ?", $product, (array)$id, count((array)$id));
		if($this->db->query($query))
			return $id;
		else
			return false;
	}
	
	public function add_product($product)
	{	
		$product = (array) $product;

		if(empty($product['url']))
		{
			$product['url'] = preg_replace("/[\s]+/ui", '-', $product['name']);
			$product['url'] = strtolower(preg_replace("/[^0-9a-zа-я\-]+/ui", '', $product['url']));
		}

		// Если есть товар с таким URL, добавляем к нему число
		while($this->get_product((string)$product['url']))
		{
			if(preg_match('/(.+)_([0-9]+)$/', $product['url'], $parts))
				$product['url'] = $parts[1].'_'.($parts[2]+1);
			else
				$product['url'] = $product['url'].'_2';
		}
// print_r($product);
		if($this->db->query("INSERT INTO __products SET ?%", $product))
		{
	// print_r('zettt');

			$id = $this->db->insert_id();
			$this->db->query("UPDATE __products SET position=id, last_update=NOW() WHERE id=?", $id);		
			return $id;
			
		}
		else
			return false;
	}
	
	
	/*
	*
	* Удалить товар
	*
	*/	
	public function delete_product($id)
	{
		if(!empty($id))
		{
			// Удаляем варианты
			$variants = $this->variants->get_variants(array('product_id'=>$id));
			foreach($variants as $v)
				$this->variants->delete_variant($v->id);
			
			// Удаляем изображения
			$images = $this->get_images(array('product_id'=>$id));
			foreach($images as $i)
				$this->delete_image($i->id);
			
			// Удаляем категории
			$categories = $this->categories->get_categories(array('product_id'=>$id));
			foreach($categories as $c)
				$this->categories->delete_product_category($id, $c->id);

			// Удаляем свойства
			$options = $this->features->get_options(array('product_id'=>$id));
			foreach($options as $o)
				$this->features->delete_option($id, $o->feature_id);
			
			// Удаляем связанные товары
			$related = $this->get_related_products($id);
			foreach($related as $r)
				$this->delete_related_product($id, $r->related_id);
			
			// Удаляем товар из связанных с другими
			$query = $this->db->placehold("DELETE FROM __related_products WHERE related_id=?", intval($id));
			$this->db->query($query);
			
			// Удаляем отзывы
			$comments = $this->comments->get_comments(array('object_id'=>$id, 'type'=>'product'));
			foreach($comments as $c)
				$this->comments->delete_comment($c->id);
			
			// Удаляем из покупок
			$this->db->query('UPDATE __purchases SET product_id=NULL WHERE product_id=?', intval($id));
			
			// Удаляем товар
			$query = $this->db->placehold("DELETE FROM __products WHERE id=? LIMIT 1", intval($id));
			if($this->db->query($query))
				return true;			
		}
		return false;
	}	
	
	public function duplicate_product($id)
	{
    	$product = $this->get_product($id);
    	$product->id = null;

		$product->created = date('Y-m-d H:i:s');
    	/*new*/
    	unset($product->category_id);
    	unset($product->url);
    	unset($product->brand_url);
    	/*new*/
		// Сдвигаем товары вперед и вставляем копию на соседнюю позицию
    	$this->db->query('UPDATE __products SET position=position+1 WHERE position>?', $product->position);
    	$new_id = $this->products->add_product($product);
    	$this->db->query('UPDATE __products SET position=? WHERE id=?', $product->position+1, $new_id);
    	
    	// Очищаем url
    	$this->db->query('UPDATE __products SET url="" WHERE id=?', $new_id);
    	
		// Дублируем категории
		$categories = $this->categories->get_product_categories($id);
		foreach($categories as $c)
			$this->categories->add_product_category($new_id, $c->category_id);
    	
    	// Дублируем изображения
    	$images = $this->get_images(array('product_id'=>$id));
    	foreach($images as $image)
    		$this->add_image($new_id, $image->filename);
    		
    	// Дублируем варианты
    	$variants = $this->variants->get_variants(array('product_id'=>$id));
    	foreach($variants as $variant)
    	{
    		$variant->product_id = $new_id;
    		unset($variant->id);
    		if($variant->infinity)
    			$variant->stock = null;
    		unset($variant->infinity);
    		$this->variants->add_variant($variant);
    	}
    	
    	// Дублируем свойства
		$options = $this->features->get_options(array('product_id'=>$id));
		foreach($options as $o)
			$this->features->update_option($new_id, $o->feature_id, $o->value);
			
		// Дублируем связанные товары
		$related = $this->get_related_products($id);
		foreach($related as $r)
			$this->add_related_product($new_id, $r->related_id);
			
    		
    	return $new_id;
	}

	
	public function get_related_products($product_id = array())
	{
		if(empty($product_id))
			return array();

		$product_id_filter = $this->db->placehold('AND product_id in(?@)', (array)$product_id);
				
		$query = $this->db->placehold("SELECT product_id, related_id, position
					FROM __related_products
					WHERE 
					1
					$product_id_filter   
					ORDER BY position       
					");
		
		$this->db->query($query);
		return $this->db->results();
	}
	
	// Функция возвращает связанные товары
	public function add_related_product($product_id, $related_id, $position=0)
	{
		$query = $this->db->placehold("INSERT IGNORE INTO __related_products SET product_id=?, related_id=?, position=?", $product_id, $related_id, $position);
		$this->db->query($query);
		return $related_id;
	}
	
	// Удаление связанного товара
	public function delete_related_product($product_id, $related_id)
	{
		$query = $this->db->placehold("DELETE FROM __related_products WHERE product_id=? AND related_id=? LIMIT 1", intval($product_id), intval($related_id));
		$this->db->query($query);
	}
	
	
	function get_images($filter = array())
	{		
		$product_id_filter = '';
		$group_by = '';

		if(!empty($filter['product_id']))
			$product_id_filter = $this->db->placehold('AND i.product_id in(?@)', (array)$filter['product_id']);

		// images
		$query = $this->db->placehold("SELECT i.id, i.product_id, i.name, i.filename, i.position
									FROM __images AS i WHERE 1 $product_id_filter $group_by ORDER BY i.product_id, i.position");
		$this->db->query($query);
		return $this->db->results();
	}
	
	public function add_image($product_id, $filename, $name = '')
	{
		$query = $this->db->placehold("SELECT id FROM __images WHERE product_id=? AND filename=?", $product_id, $filename);
		$this->db->query($query);
		$id = $this->db->result('id');
		if(empty($id))
		{
			$query = $this->db->placehold("INSERT INTO __images SET product_id=?, filename=?", $product_id, $filename);
			$this->db->query($query);
			$id = $this->db->insert_id();
			$query = $this->db->placehold("UPDATE __images SET position=id WHERE id=?", $id);
			$this->db->query($query);
		}
		return($id);
	}
	
	public function update_image($id, $image)
	{
	
		$query = $this->db->placehold("UPDATE __images SET ?% WHERE id=?", $image, $id);
		$this->db->query($query);
		
		return($id);
	}
	
	public function delete_image($id)
	{
		$query = $this->db->placehold("SELECT filename FROM __images WHERE id=?", $id);
		$this->db->query($query);
		$filename = $this->db->result('filename');
		$query = $this->db->placehold("DELETE FROM __images WHERE id=? LIMIT 1", $id);
		$this->db->query($query);
		$query = $this->db->placehold("SELECT count(*) as count FROM __images WHERE filename=? LIMIT 1", $filename);
		$this->db->query($query);
		$count = $this->db->result('count');
		if($count == 0)
		{			
			$file = pathinfo($filename, PATHINFO_FILENAME);
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			
			// Удалить все ресайзы
			$rezised_images = glob($this->config->root_dir.$this->config->resized_images_dir.$file.".*x*.".$ext);
			if(is_array($rezised_images))
			foreach (glob($this->config->root_dir.$this->config->resized_images_dir.$file.".*x*.".$ext) as $f)
				@unlink($f);

			@unlink($this->config->root_dir.$this->config->original_images_dir.$filename);		
		}
	}
		
	
		//NДокументация товара
	function get_instructions($filter = array())
	{		
		$product_id_filter = '';
		$group_by = '';

		if(!empty($filter['product_id']))
			$product_id_filter = $this->db->placehold('AND i.product_id in(?@)', (array)$filter['product_id']);

		// instructions
		$query = $this->db->placehold("SELECT i.id, i.product_id, i.name, i.instructionsnames, i.filename, i.position
									FROM __instructions AS i WHERE 1 $product_id_filter $group_by ORDER BY i.product_id, i.position");
		$this->db->query($query);
		return $this->db->results();
	}
	
	public function add_instruction($product_id, $filename)
	{
		$query = $this->db->placehold("SELECT id FROM __instructions WHERE product_id=? AND filename=?", $product_id, $filename);
		$this->db->query($query);
		$id = $this->db->result('id');
		
		if(empty($id))
		{
			$query = $this->db->placehold("INSERT INTO __instructions SET product_id=?, filename=?", $product_id, $filename);
			$this->db->query($query);
			$id = $this->db->insert_id();
			$query = $this->db->placehold("UPDATE __instructions SET position=id WHERE id=?", $id);
			$this->db->query($query);
		}
		return($id);
	}
	
	public function update_instruction($id, $instruction)
	{
		$query = $this->db->placehold("UPDATE __instructions SET ?% WHERE id=?", $instruction, $id);
		$this->db->query($query);
		return($id);
	}
	
	public function delete_instruction($id)
	{
		$query = $this->db->placehold("SELECT filename FROM __instructions WHERE id=?", $id);
		$this->db->query($query);
		$filename = $this->db->result('filename');
		$query = $this->db->placehold("DELETE FROM __instructions WHERE id=? LIMIT 1", $id);
		$this->db->query($query);
		$query = $this->db->placehold("SELECT count(*) as count FROM __instructions WHERE filename=? LIMIT 1", $filename);
		$this->db->query($query);
		$count = $this->db->result('count');
		if($count == 0)
		{			
			$file = pathinfo($filename, PATHINFO_FILENAME);
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			
			// Удалить все ресайзы
			$rezised_instructions = glob($this->config->root_dir.$this->config->instructions_preview_dir.$file.".*x*.".$ext);
			if(is_array($rezised_instructions))
			foreach (glob($this->config->root_dir.$this->config->instructions_preview_dir.$file.".*x*.".$ext) as $f)
				@unlink($f);

			@unlink($this->config->root_dir.$this->config->original_instructions_dir.$filename);		
		}
	}
	
	//NДокументация товара
		
	/*
	*
	* Следующий товар
	*
	*/	
	public function get_next_product($id,$position,$category_id)
	{
		$this->db->query("SELECT position FROM __products WHERE id=? LIMIT 1", $id);
		$position = $this->db->result('position');
		
		
		$this->db->query("SELECT pc.category_id FROM __products_categories pc WHERE product_id=? ORDER BY position LIMIT 1", $id);
		$category_id = $this->db->result('category_id');

		$query = $this->db->placehold("SELECT id FROM __products p, __products_categories pc
										WHERE pc.product_id=p.id AND p.position>? 
										AND pc.position=(SELECT MIN(pc2.position) FROM __products_categories pc2 WHERE pc.product_id=pc2.product_id)
										AND pc.category_id=? 
										AND p.visible ORDER BY p.position limit 1", $position, $category_id);
		$this->db->query($query);
		$n=$this->get_product((integer)$this->db->result('id'));
		if($n != 0){
			$n->images = $this->get_images(array('product_id'=>$n->id));
			$n->image = &$n->images[0];
			$n->variants = $this->variants->get_variants(array('product_id'=>$n->id));
			$n->variant = $n->variants[0];
			// print_r($n->variant);
		        return $n; 
		}
 
		// return $this->get_product((integer)$this->db->result('id'));
	}
	
	/*
	*
	* Предыдущий товар
	*
	*/	
	public function get_prev_product($id,$position,$category_id)
	{
		$this->db->query("SELECT position FROM __products WHERE id=? LIMIT 1", $id);
		$position = $this->db->result('position');
		
		$this->db->query("SELECT pc.category_id FROM __products_categories pc WHERE product_id=? ORDER BY position LIMIT 1", $id);
		$category_id = $this->db->result('category_id');

		$query = $this->db->placehold("SELECT id FROM __products p, __products_categories pc
										WHERE pc.product_id=p.id AND p.position<? 
										AND pc.position=(SELECT MIN(pc2.position) FROM __products_categories pc2 WHERE pc.product_id=pc2.product_id)
										AND pc.category_id=? 
										AND p.visible ORDER BY p.position DESC limit 1", $position, $category_id);
		$this->db->query($query);
		$n=$this->get_product((integer)$this->db->result('id'));
		if($n != 0){
			$n->images = $this->get_images(array('product_id'=>$n->id));
			$n->image = &$n->images[0];
			$n->variants = $this->variants->get_variants(array('product_id'=>$n->id));
			$n->variant = $n->variants[0];
		        return $n; 
		}
 
		// return $this->get_product((integer)$this->db->result('id'));	
	}
	
	//функция позволяет узнать все категории, в которых есть товары определенной компании
    // public function brands_category($bandd_id) 
        // {
        // $this->db->query("
            // SELECT c.id, c.name, c.meta_title, c.url, b.url AS brand, count(p.id) as `products`
            // FROM s_categories AS c
            // INNER JOIN s_products_categories AS pc
            // ON pc.category_id = c.id
            // INNER JOIN s_products AS p
            // ON p.id = pc.product_id
            // INNER JOIN s_brands AS b
            // ON b.id = p.brand_id
            // WHERE b.id=? 
            // AND c.visible = 1
            // GROUP BY c.id
            // ORDER BY c.name ASC
        // ", $bandd_id); 
        // $brand_categories = $this->db->results();
        // return $brand_categories;
        // }    
	    /*
    *
    * Добавляем теги
    *
    */    
    // public function add_tags($type, $object_id, $values)
    // {     
        // $tags = explode(',', $values);
        // foreach($tags as $value) 
        // {
            // $query = $this->db->placehold("INSERT IGNORE INTO __tags SET type=?, object_id=?, value=?", $type, intval($object_id), $value);
            // $this->db->query($query);   
        // }
        // return count($tags);
    // }
    
    /*
    *
    * Удаляем все теги
    *
    */    
    // public function delete_tags($type, $object_id)
    // {
        // $query = $this->db->placehold("DELETE FROM __tags WHERE type=? AND object_id=?", $type, intval($object_id));
        // $this->db->query($query);
    // }
    
    /*
    *
    * Получаем список тегов
    *
    */        
    // public function get_tags($filter = array())
    // {
        // $type_filter = '';
        // $object_id_filter = '';
        // $keyword_filter = '';
        // $group = '';

        // if(isset($filter['group']))
            // $group = 'GROUP BY value';

        // if(isset($filter['object_id']))
            // $object_id_filter = $this->db->placehold('AND object_id in(?@)', (array)$filter['object_id']);

        // if(!empty($filter['keyword']))
        // {
            // $keywords = explode(',', $filter['keyword']);
            // foreach($keywords as $keyword)
            // {
                // $kw = $this->db->escape(trim($keyword));
                // $keyword_filter .= " AND value LIKE '%$kw%'";
            // }
        // }

        // $query = $this->db->placehold("SELECT id, name FROM __tags WHERE 1 $object_id_filter $keyword_filter $group ORDER BY id");
	 // print_r($query);
		// $this->db->query($query);
		// return $this->db->results();
    // }
		
		/**
		* Функция вносит +1 к просмотру товара
		* @param $id
		* @retval object
		*/
		public function update_views($id)
		{
		$this->db->query("UPDATE s_products SET views=views+1 WHERE id=?", $id);
		return true;
		} 
}