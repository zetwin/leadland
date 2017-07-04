<?PHP

require_once('api/Simpla.php');


class StoresAdmin extends Simpla
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
						$this->stores->update_store($id, array('visible'=>0));    
					break;
			    }
			    case 'enable':
			    {
			    	foreach($ids as $id)
						$this->stores->update_store($id, array('visible'=>1));    
			        break;
			    }
			    case 'delete':
			    {
					$this->stores->delete_store($ids);    
			        break;
			    }
			}		
	  	
			// Сортировка
			$positions = $this->request->post('positions');
	 		$ids = array_keys($positions);
			sort($positions);
			foreach($positions as $i=>$position)
				$this->stores->update_store($ids[$i], array('position'=>$position)); 
		}  
  
		$stores = $this->stores->get_stores_tree();

		$this->design->assign('stores', $stores);
		return $this->design->fetch('stores.tpl');
	}
}
