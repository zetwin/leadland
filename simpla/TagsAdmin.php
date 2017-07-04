<?PHP

require_once('api/Simpla.php');


class TagsAdmin extends Simpla
{
	function fetch()
	{
		if($this->request->method('post'))
		{
			// Действия с выбранными
			$ids = $this->request->post('check');
			if(is_array($ids))
			switch($this->request->post('action'))
			{
			    case 'disable':
			    {
			    	foreach($ids as $id)
						$this->tags->update_tag($id, array('visible'=>0));    
					break;
			    }
			    case 'enable':
			    {
			    	foreach($ids as $id)
						$this->tags->update_tag($id, array('visible'=>1));    
			        break;
			    }
			    case 'delete':
			    {
					$this->tags->delete_tags(array('ids'=>$ids));    
			        break;
			    }
			}		
	  	
			//Пересчитаем елементы к тегам
			if($this->request->post('recount')){
				$this->tags->tags_recount();
				// $this->tags->tags_urls();
				}
			
			// Сортировка
			// $positions = $this->request->post('positions');
	 		// $ids = array_keys($positions);
			// sort($positions);
			// foreach($positions as $i=>$position)
				// $this->tags->update_tag($ids[$i], array('position'=>$position)); 

		}  
  
		$tags = $this->tags->get_alltags();
		$tags_count = $this->tags->tags_count();

		$this->design->assign('tags', $tags);
		$this->design->assign('tags_count', $tags_count);
		return $this->design->fetch('tags.tpl');
	}
}
