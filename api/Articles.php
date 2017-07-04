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

class Articles extends Simpla
{
	/**
	* Функция возвращает товары
	* Возможные значения фильтра:
	* id - id товара или их массив
	* articlecategory_id - id категории или их массив
	* brand_id - id бренда или их массив
	* page - текущая страница, integer
	* limit - количество товаров на странице, integer
	* sort - порядок товаров, возможные значения: position(по умолчанию), name, price
	* keyword - ключевое слово для поиска
	* features - фильтр по свойствам товара, массив (id свойства => значение свойства)
	*/
	public function get_articles($filter = array())
	{		
		// По умолчанию
		$limit = 20;
		$page = 1;
		$articlecategory_id_filter = '';
		$brand_id_filter = '';
		$article_id_filter = '';
		$features_filter = '';
		$keyword_filter = '';
		$visible_filter = '';
		$is_featured_filter = '';
		$group_by = '';
		$order = 'p.position DESC';

		if(isset($filter['limit']))
			$limit = max(1, intval($filter['limit']));

		if(isset($filter['page']))
			$page = max(1, intval($filter['page']));

		$sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

		if(!empty($filter['id']))
			$article_id_filter = $this->db->placehold('AND p.id in(?@)', (array)$filter['id']);

		if(!empty($filter['articlecategory_id']))
		{
			$articlecategory_id_filter = $this->db->placehold('INNER JOIN __articles_in_categories pc ON pc.article_id = p.id AND pc.articlecategory_id in(?@)', (array)$filter['articlecategory_id']);
			$group_by = "GROUP BY p.id";
		}
		
		if(!empty($filter['brand_id']))
			$brand_id_filter = $this->db->placehold('AND p.brand_id in(?@)', (array)$filter['brand_id']);

		if(isset($filter['featured']))
			$is_featured_filter = $this->db->placehold('AND p.featured=?', intval($filter['featured']));

		if(isset($filter['visible']))
			$visible_filter = $this->db->placehold('AND p.visible=?', intval($filter['visible']));

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
			}

		if(isset($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				$keyword_filter .= $this->db->placehold('AND (p.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR p.meta_keywords LIKE "%'.$this->db->escape(trim($keyword)).'%") ');
		}

		if(!empty($filter['features']) && !empty($filter['features']))
			foreach($filter['features'] as $feature=>$value)
				$features_filter .= $this->db->placehold('AND p.id in (SELECT article_id FROM s_options WHERE feature_id=? AND value in (?@) ) ', $feature, $value);

		$query = "SELECT  
					p.id,
					p.url,
					p.brand_id,
					p.name,
					p.related_category,
					p.annotation,
					p.body,
					p.position,
					p.created as created,
					p.visible, 
					p.featured,
					p.rating,
					p.votes,
					p.meta_title, 
					p.meta_keywords, 
					p.meta_description,
      (SELECT articlecategory_id FROM __articles_in_categories WHERE article_id = p.id ORDER BY position ASC LIMIT 1) as articlecategory_id
				FROM __articles p		
				$articlecategory_id_filter 
				WHERE 
					1
					$article_id_filter
					$features_filter
					$brand_id_filter
					$keyword_filter
					$is_featured_filter
					$visible_filter
				$group_by
				ORDER BY $order
					$sql_limit";

		$this->db->query($query);
    return $this->db->results();
	}

	/**
	* Функция возвращает количество товаров
	* Возможные значения фильтра:
	* articlecategory_id - id категории или их массив
	* brand_id - id бренда или их массив
	* keyword - ключевое слово для поиска
	* features - фильтр по свойствам товара, массив (id свойства => значение свойства)
	*/
	public function count_articles($filter = array())
	{		
		$articlecategory_id_filter = '';
		$article_id_filter = '';
		$brand_id_filter = '';
		$keyword_filter = '';
		$visible_filter = '';
		$is_featured_filter = '';
		$features_filter = '';
		
		if(!empty($filter['articlecategory_id']))
			$articlecategory_id_filter = $this->db->placehold('INNER JOIN __articles_in_categories pc ON pc.article_id = p.id AND pc.articlecategory_id in(?@)', (array)$filter['articlecategory_id']);
			
			if(!empty($filter['brand_id']))
			$brand_id_filter = $this->db->placehold('AND p.brand_id in(?@)', (array)$filter['brand_id']);

		if(!empty($filter['id']))
			$article_id_filter = $this->db->placehold('AND p.id in(?@)', (array)$filter['id']);
		
		if(isset($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				$keyword_filter .= $this->db->placehold('AND (p.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR p.meta_keywords LIKE "%'.$this->db->escape(trim($keyword)).'%") ');
		}

		if(isset($filter['featured']))
			$is_featured_filter = $this->db->placehold('AND p.featured=?', intval($filter['featured']));

		if(isset($filter['visible']))
			$visible_filter = $this->db->placehold('AND p.visible=?', intval($filter['visible']));
		
		
		if(!empty($filter['features']) && !empty($filter['features']))
			foreach($filter['features'] as $feature=>$value)
				$features_filter .= $this->db->placehold('AND p.id in (SELECT article_id FROM s_options WHERE feature_id=? AND value in (?@) ) ', $feature, $value);
		
		$query = "SELECT count(distinct p.id) as count
				FROM __articles AS p
				$articlecategory_id_filter
				WHERE 1
					$article_id_filter
					$brand_id_filter
					$keyword_filter
					$is_featured_filter
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
	public function get_article($id)
	{
		if(is_int($id))
			$filter = $this->db->placehold('p.id = ?', $id);
		else
			$filter = $this->db->placehold('p.url = ?', $id);
			
		$query = "SELECT DISTINCT
					p.id,
					p.url,
					p.name,
					p.brand_id,
					p.related_category,
					p.annotation,
					p.body,
					p.position,
					p.created as created,
					p.visible, 
					p.featured,
					p.rating,
					p.votes,
					p.meta_title, 
					p.meta_keywords, 
					p.meta_description,
          				(SELECT articlecategory_id FROM __articles_in_categories WHERE article_id = p.id ORDER BY position ASC LIMIT 1) as articlecategory_id
				FROM __articles AS p
				LEFT JOIN __brands b ON p.brand_id = b.id
                WHERE $filter
                GROUP BY p.id
                LIMIT 1";
		$this->db->query($query);
		$article = $this->db->result();
		// if (!empty($article))
    // { 		
    	// $all_articlecategory = $this->articlecategories->get_all_articlecategories();
	    // foreach($all_articlecategory as $c)
	    // {
		    // if($c->id == $article->articlecategory_id)
		    // {
		    // echo $c->full_url;
		     // $article->full_url = $c->full_url;
		     // if($article->brand_url)
		     // $article->full_url = $article->full_url.$article->brand_url."/".$article->url;
		    // }
	    // }
	// } 
		return $article;
	}

	public function update_article($id, $article)
	{
		$query = $this->db->placehold("UPDATE __articles SET ?% WHERE id in (?@) LIMIT ?", $article, (array)$id, count((array)$id));
		if($this->db->query($query))
			return $id;
		else
			return false;
	}
	
	public function add_article($article)
	{	
		$article = (array) $article;
		
		if(empty($article['url']))
		{
			$article['url'] = preg_replace("/[\s]+/ui", '-', $article['name']);
			$article['url'] = strtolower(preg_replace("/[^0-9a-zа-я\-]+/ui", '', $article['url']));
		}

		// Если есть товар с таким URL, добавляем к нему число
		while($this->get_article((string)$article['url']))
		{
			if(preg_match('/(.+)_([0-9]+)$/', $article['url'], $parts))
				$article['url'] = $parts[1].'_'.($parts[2]+1);
			else
				$article['url'] = $article['url'].'_2';
		}

		if($this->db->query("INSERT INTO __articles SET ?%", $article))
		{
			$id = $this->db->insert_id();
			$this->db->query("UPDATE __articles SET position=id WHERE id=?", $id);		
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
	public function delete_article($id)
	{
		if(!empty($id))
		{
			
			// Удаляем изображения
			$images = $this->get_images(array('article_id'=>$id));
			foreach($images as $i)
				$this->delete_image($i->id);
			
			// Удаляем категории
			$articlecategories = $this->articlecategories->get_articlecategories(array('article_id'=>$id));
			foreach($articlecategories as $c)
				$this->articlecategories->delete_article_category($id, $c->id);

			// Удаляем свойства
			$options = $this->features->get_options(array('article_id'=>$id));
			foreach($options as $o)
				$this->features->delete_option($id, $o->feature_id);
			
			// Удаляем связанные товары
			$related = $this->get_related_products($id);
			foreach($related as $r)
				$this->delete_related_article($id, $r->related_id);
			
			// Удаляем товар из связанных с другими
			$query = $this->db->placehold("DELETE FROM __related_articles WHERE related_id=?", intval($id));
			$this->db->query($query);
			
			// Удаляем отзывы
			$comments = $this->comments->get_comments(array('object_id'=>$id, 'type'=>'article'));
			foreach($comments as $c)
				$this->comments->delete_comment($c->id);
			
			// Удаляем из покупок
			$this->db->query('UPDATE __purchases SET article_id=NULL WHERE article_id=?', intval($id));
			
			// Удаляем товар
			$query = $this->db->placehold("DELETE FROM __articles WHERE id=? LIMIT 1", intval($id));
			if($this->db->query($query))
				return true;			
		}
		return false;
	}	


		public function duplicate_article($id)
	{
    	$article = $this->get_article($id);
    	$article->id = null;
    	$article->created = null;
    	/*new*/
    	unset($article->category_id);
    	unset($article->url);
    	/*new*/
		// Сдвигаем товары вперед и вставляем копию на соседнюю позицию
    	$this->db->query('UPDATE __articles SET position=position+1 WHERE position>?', $article->position);
    	$new_id = $this->articles->add_article($article);
    	$this->db->query('UPDATE __articles SET position=? WHERE id=?', $article->position+1, $new_id);
    	
    	// Очищаем url
    	$this->db->query('UPDATE __articles SET url="" WHERE id=?', $new_id);
    	
		// Дублируем категории
		$articlecategories = $this->articlecategories->get_article_categories($id);
		foreach($articlecategories as $c)
			$this->articlecategories->add_article_category($new_id, $c->category_id);
    	
    	// Дублируем изображения
    	$images = $this->get_images(array('article_id'=>$id));
    	foreach($images as $image)
    		$this->add_image($new_id, $image->filename);
    		
    	// Дублируем варианты
    	// $variants = $this->variants->get_variants(array('article_id'=>$id));
    	// foreach($variants as $variant)
    	// {
    		// $variant->article_id = $new_id;
    		// unset($variant->id);
    		// if($variant->infinity)
    			// $variant->stock = null;
    		// unset($variant->infinity);
    		// $this->variants->add_variant($variant);
    	// }
    	
    	// Дублируем свойства
		// $options = $this->features->get_options(array('article_id'=>$id));
		// foreach($options as $o)
			// $this->features->update_option($new_id, $o->feature_id, $o->value);
			
		// Дублируем связанные товары
		// $related = $this->get_related_products($id);
		// foreach($related as $r)
			// $this->add_related_product($new_id, $r->related_id);
			
    		
    	return $new_id;
	}
	
	
	public function get_related_products($article_id = array())
	{
		if(empty($article_id))
			return array();

		$article_id_filter = $this->db->placehold('AND article_id in(?@)', (array)$article_id);
				
		$query = $this->db->placehold("SELECT article_id, related_id, position
					FROM __related_products
					WHERE 
					1
					$article_id_filter   
					ORDER BY position       
					");
		
		$this->db->query($query);
		return $this->db->results();
	}
	
	// Функция возвращает связанные товары
	public function add_related_product($article_id, $related_id, $position=0)
	{
		$query = $this->db->placehold("INSERT IGNORE INTO __related_products SET article_id=?, related_id=?, position=?", $article_id, $related_id, $position);
		$this->db->query($query);
		return $related_id;
	}
	
	// Удаление связанного товара
	public function delete_related_product($article_id, $related_id)
	{
		$query = $this->db->placehold("DELETE FROM __related_products WHERE article_id=? AND related_id=? LIMIT 1", intval($article_id), intval($related_id));
		$this->db->query($query);
	}
	
	
	function get_images($filter = array())
	{		
		$article_id_filter = '';
		$group_by = '';

		if(!empty($filter['article_id']))
			$article_id_filter = $this->db->placehold('AND i.article_id in(?@)', (array)$filter['article_id']);

		// images
		$query = $this->db->placehold("SELECT i.id, i.article_id, i.name, i.filename, i.position
									FROM __imagesarticle AS i WHERE 1 $article_id_filter $group_by ORDER BY i.article_id, i.position");
		$this->db->query($query);
		return $this->db->results();
	}
	
	public function add_image($article_id, $filename, $name = '')
	{
		$query = $this->db->placehold("SELECT id FROM __imagesarticle WHERE article_id=? AND filename=?", $article_id, $filename);
		$this->db->query($query);
		$id = $this->db->result('id');
		if(empty($id))
		{
			$query = $this->db->placehold("INSERT INTO __imagesarticle SET article_id=?, filename=?", $article_id, $filename);
			$this->db->query($query);
			$id = $this->db->insert_id();
			$query = $this->db->placehold("UPDATE __imagesarticle SET position=id WHERE id=?", $id);
			$this->db->query($query);
		}
		return($id);
	}
	
	public function update_image($id, $image)
	{
	
		$query = $this->db->placehold("UPDATE __imagesarticle SET ?% WHERE id=?", $image, $id);
		$this->db->query($query);
		
		return($id);
	}
	
	public function delete_image($id)
	{
		$query = $this->db->placehold("SELECT filename FROM __imagesarticle WHERE id=?", $id);
		$this->db->query($query);
		$filename = $this->db->result('filename');
		$query = $this->db->placehold("DELETE FROM __imagesarticle WHERE id=? LIMIT 1", $id);
		$this->db->query($query);
		$query = $this->db->placehold("SELECT count(*) as count FROM __imagesarticle WHERE filename=? LIMIT 1", $filename);
		$this->db->query($query);
		$count = $this->db->result('count');
		if($count == 0)
		{			
			$file = pathinfo($filename, PATHINFO_FILENAME);
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			
			// Удалить все ресайзы
			$rezised_images = glob($this->config->root_dir.$this->config->resized_articleimages_dir.$file.".*x*.".$ext);
			if(is_array($rezised_images))
			foreach (glob($this->config->root_dir.$this->config->resized_articleimages_dir.$file.".*x*.".$ext) as $f)
				@unlink($f);

			@unlink($this->config->root_dir.$this->config->original_articleimages_dir.$filename);		
		}
	}
		
	/*
	*
	* Следующий товар
	*
	*/	
	public function get_next_article($id)
	{
		$this->db->query("SELECT position FROM __articles WHERE id=? LIMIT 1", $id);
		$position = $this->db->result('position');
		
		$this->db->query("SELECT pc.articlecategory_id FROM __articles_in_categories pc WHERE article_id=? ORDER BY position LIMIT 1", $id);
		$articlecategory_id = $this->db->result('articlecategory_id');

		$query = $this->db->placehold("SELECT id FROM __articles p, __articles_in_categories pc
										WHERE pc.article_id=p.id AND p.position>? 
										AND pc.position=(SELECT MIN(pc2.position) FROM __articles_in_categories pc2 WHERE pc.article_id=pc2.article_id)
										AND pc.articlecategory_id=? 
										AND p.visible ORDER BY p.position limit 1", $position, $articlecategory_id);
		$this->db->query($query);
		$n=$this->get_article((integer)$this->db->result('id'));
		if($n != 0){
			$n->images = $this->get_images(array('article_id'=>$n->id));
			$n->image = &$n->images[0];
		        return $n; 
		}
 
		// return $this->get_article((integer)$this->db->result('id'));
	}
	
	/*
	*
	* Предыдущий товар
	*
	*/	
	public function get_prev_article($id)
	{
		$this->db->query("SELECT position FROM __articles WHERE id=? LIMIT 1", $id);
		$position = $this->db->result('position');
		
		$this->db->query("SELECT pc.articlecategory_id FROM __articles_in_categories pc WHERE article_id=? ORDER BY position LIMIT 1", $id);
		$articlecategory_id = $this->db->result('articlecategory_id');

		$query = $this->db->placehold("SELECT id FROM __articles p, __articles_in_categories pc
										WHERE pc.article_id=p.id AND p.position<? 
										AND pc.position=(SELECT MIN(pc2.position) FROM __articles_in_categories pc2 WHERE pc.article_id=pc2.article_id)
										AND pc.articlecategory_id=? 
										AND p.visible ORDER BY p.position DESC limit 1", $position, $articlecategory_id);
		$this->db->query($query);
		
		$n=$this->get_article((integer)$this->db->result('id'));
		if($n != 0){
			$n->images = $this->get_images(array('article_id'=>$n->id));
			$n->image = &$n->images[0];
		        return $n; 
		}
 
		// return $this->get_article((integer)$this->db->result('id'));	
		}
}