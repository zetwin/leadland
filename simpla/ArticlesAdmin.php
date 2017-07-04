<?PHP 

require_once('api/Simpla.php');

class ArticlesAdmin extends Simpla
{
	function fetch()
	{		

		$filter = array();
		$filter['page'] = max(1, $this->request->get('page', 'integer'));
			
		$filter['limit'] = $this->settings->articles_num_admin;
	
		// Категории
		$articlecategories = $this->articlecategories->get_articlecategories_tree();
		$this->design->assign('articlecategories', $articlecategories);
		
		// Текущая категория
		$articlecategory_id = $this->request->get('articlecategory_id', 'integer'); 
		if($articlecategory_id && $articlecategory = $this->articlecategories->get_articlecategory($articlecategory_id))
	  		$filter['articlecategory_id'] = $articlecategory->children;
		    
	
		// Текущий фильтр
		if($f = $this->request->get('filter', 'string'))
		{
			if($f == 'featured')
				$filter['featured'] = 1; 
			elseif($f == 'visible')
				$filter['visible'] = 1; 
			elseif($f == 'hidden')
				$filter['visible'] = 0;  
			$this->design->assign('filter', $f);
		}
	
		// Поиск
		$keyword = $this->request->get('keyword');
		if(!empty($keyword))
		{
	  		$filter['keyword'] = $keyword;
			$this->design->assign('keyword', $keyword);
		}
			
		// Обработка действий 	
		if($this->request->method('post'))
		{
					
			// Сортировка
			$positions = $this->request->post('positions'); 		
				$ids = array_keys($positions);
			sort($positions);
			$positions = array_reverse($positions);
			foreach($positions as $i=>$position)
				$this->articles->update_article($ids[$i], array('position'=>$position)); 
		
			
			// Действия с выбранными
			$ids = $this->request->post('check');
			if(!empty($ids))
			switch($this->request->post('action'))
			{
			    case 'disable':
			    {
			    	$this->articles->update_article($ids, array('visible'=>0));
					break;
			    }
			    case 'enable':
			    {
			    	$this->articles->update_article($ids, array('visible'=>1));
			        break;
			    }
			    case 'set_featured':
			    {
			    	$this->articles->update_article($ids, array('featured'=>1));
					break;
			    }
			    case 'unset_featured':
			    {
			    	$this->articles->update_article($ids, array('featured'=>0));
					break;
			    }
			    case 'delete':
			    {
				    foreach($ids as $id)
						$this->articles->delete_article($id);    
			        break;
			    }
			    case 'duplicate':
			    {
				    foreach($ids as $id)
				    	$this->articles->duplicate_article(intval($id));
			        break;
			    }
			    case 'move_to_page':
			    {
		
			    	$target_page = $this->request->post('target_page', 'integer');
			    	
			    	// Сразу потом откроем эту страницу
			    	$filter['page'] = $target_page;
		
				    // До какого товара перемещать
				    $limit = $filter['limit']*($target_page-1);
				    if($target_page > $this->request->get('page', 'integer'))
				    	$limit += count($ids)-1;
				    else
				    	$ids = array_reverse($ids, true);
		

					$temp_filter = $filter;
					$temp_filter['page'] = $limit+1;
					$temp_filter['limit'] = 1;
					$target_article = array_pop($this->articles->get_articles($temp_filter));
					$target_position = $target_article->position;
				   	
				   	// Если вылезли за последний товар - берем позицию последнего товара в качестве цели перемещения
					if($target_page > $this->request->get('page', 'integer') && !$target_position)
					{
				    	$query = $this->db->placehold("SELECT distinct p.position AS target FROM __articles p LEFT JOIN __articles_articlecategories AS pc ON pc.article_id = p.id WHERE 1 $articlecategory_id_filter ORDER BY p.position DESC LIMIT 1", count($ids));	
				   		$this->db->query($query);
				   		$target_position = $this->db->result('target');
					}
				   	
			    	foreach($ids as $id)
			    	{		    	
				    	$query = $this->db->placehold("SELECT position FROM __articles WHERE id=? LIMIT 1", $id);	
				    	$this->db->query($query);	      
				    	$initial_position = $this->db->result('position');
		
				    	if($target_position > $initial_position)
				    		$query = $this->db->placehold("	UPDATE __articles set position=position-1 WHERE position>? AND position<=?", $initial_position, $target_position);	
				    	else
				    		$query = $this->db->placehold("	UPDATE __articles set position=position+1 WHERE position<? AND position>=?", $initial_position, $target_position);	
				    		
			    		$this->db->query($query);	      			    	
			    		$query = $this->db->placehold("UPDATE __articles SET __articles.position = ? WHERE __articles.id = ?", $target_position, $id);	
			    		$this->db->query($query);	
				    }
			        break;
				}
			    case 'move_to_articlecategory':
			    {
			    	$articlecategory_id = $this->request->post('target_articlecategory', 'integer');
			    	$filter['page'] = 1;
					$articlecategory = $this->articlecategories->get_articlecategory($articlecategory_id);
	  				$filter['articlecategory_id'] = $articlecategory->children;
			    	
			    	foreach($ids as $id)
			    	{
			    		$query = $this->db->placehold("DELETE FROM __articles_articlecategories WHERE articlecategory_id=? AND article_id=? LIMIT 1", $articlecategory_id, $id);	
			    		$this->db->query($query);	      			    	
			    		$query = $this->db->placehold("UPDATE IGNORE __articles_articlecategories set articlecategory_id=? WHERE article_id=? ORDER BY position DESC LIMIT 1", $articlecategory_id, $id);	
			    		$this->db->query($query);
			    		if($this->db->affected_rows() == 0)
							$query = $this->db->query("INSERT IGNORE INTO __articles_articlecategories set articlecategory_id=?, article_id=?", $articlecategory_id, $id);	

				    }
			        break;
				}
			 }			
		}

		// Отображение
		if(isset($articlecategory))
			$this->design->assign('articlecategory', $articlecategory);
		
	  	$articles_count = $this->articles->count_articles($filter);
		// Показать все страницы сразу
		if($this->request->get('page') == 'all')
			$filter['limit'] = $articles_count;
		
		if($filter['limit']>0)	  	
		  	$pages_count = ceil($articles_count/$filter['limit']);
		else
		  	$pages_count = 0;
	  	$filter['page'] = min($filter['page'], $pages_count);
	 	$this->design->assign('articles_count', $articles_count);
	 	$this->design->assign('pages_count', $pages_count);
	 	$this->design->assign('current_page', $filter['page']);
	 	
		$articles = array();
		foreach($this->articles->get_articles($filter) as $p)
			$articles[$p->id] = $p;
	 	
	
		if(!empty($articles))
		{
		  	
			// Товары 
			$articles_ids = array_keys($articles);
			foreach($articles as &$article)
			{
				$article->images = array();
				$article->properties = array();
			}
		
			$images = $this->articles->get_images(array('article_id'=>$articles_ids));
			foreach($images as $image)
				$articles[$image->article_id]->images[$image->id] = $image;
		}
	 
		$this->design->assign('articles', $articles);
	
		return $this->design->fetch('articles.tpl');
	}
}
