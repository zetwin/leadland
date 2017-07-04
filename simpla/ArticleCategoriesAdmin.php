<?PHP

require_once('api/Simpla.php');


class ArticleCategoriesAdmin extends Simpla
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
						$this->articlecategories->update_articlecategory($id, array('visible'=>0));    
					break;
			    }
			    case 'enable':
			    {
			    	foreach($ids as $id)
						$this->articlecategories->update_articlecategory($id, array('visible'=>1));    
			        break;
			    }
			    case 'delete':
			    {
					$this->articlecategories->delete_articlecategory($ids);    
			        break;
			    }
			}		
	  	
			// Сортировка
			$positions = $this->request->post('positions');
	 		$ids = array_keys($positions);
			sort($positions);
			foreach($positions as $i=>$position)
				$this->articlecategories->update_articlecategory($ids[$i], array('position'=>$position)); 

		}  
  
		$articlecategories = $this->articlecategories->get_articlecategories_tree();

		$this->design->assign('articlecategories', $articlecategories);
		return $this->design->fetch('articlecategories.tpl');
	}
}
