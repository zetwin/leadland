<?PHP 

require_once('api/Simpla.php');

########################################
class CurrencyAdmin extends Simpla
{


  public function fetch()
  {
  
	   	// Обработка действий
	  	if($this->request->method('post'))
	  	{
	  	
			foreach($this->request->post('currency') as $n=>$va)
				foreach($va as $i=>$v)
					{	
						if(empty($currencies[$i]))
							$currencies[$i] = new stdClass;
						$currencies[$i]->$n = $v;
					}
  		    
							$products = array();
				foreach($this->products->get_products($filter) as $p)
					$products[$p->id] = $p;
				 
				if(!empty($products))
				{
					// Товары 
					$products_ids = array_keys($products);
					foreach($products as &$product)
					{
						$product->variants = array();
					}
				 
					$variants = $this->variants->get_variants(array('product_id'=>$products_ids));
				 
					foreach($variants as &$variant)
					{
						$products[$variant->product_id]->variants[] = $variant;
					}
				}
				 
				$currencies_ids = array();
				foreach($currencies as $currency)
				{
					if($currency->id)				
					{
						$this->money->update_currency($currency->id, $currency);										
						// Мультивалютность
						$this->db->query("UPDATE __variants SET price=base_price*?, compare_price=base_compare_price*? WHERE currency=?"  
											, $currency->rate_to/$currency->rate_from
											, $currency->rate_to/$currency->rate_from
											, $currency->id);
						// Мультивалютность end
					}
					else
						$currency->id = $this->money->add_currency($currency);
						$currencies_ids[] = $currency->id;
				}

			// Удалить непереданные валюты
			$query = $this->db->placehold('DELETE FROM __currencies WHERE id NOT IN(?@)', $currencies_ids);
			$this->db->query($query);
			
			// Пересчитать курсы
			$old_currency = $this->money->get_currency();
			$new_currency = reset($currencies);
			if($old_currency->id != $new_currency->id)
			{
				$coef = $new_currency->rate_from/$new_currency->rate_to;

				if($this->request->post('recalculate') == 1)
				{
					// Мультивалютность
					$this->db->query("UPDATE __variants SET price=IFNULL(base_price, price)*?", $coef);
					$this->db->query("UPDATE __variants SET currency=IFNULL(currency, ?)", $old_currency);
					// Мультивалютность end
					$this->db->query("UPDATE __variants SET price=price*?", $coef);   
					$this->db->query("UPDATE __delivery SET price=price*?, free_from=free_from*?", $coef, $coef);        
					$this->db->query("UPDATE __orders SET delivery_price=delivery_price*?", $coef);        
					$this->db->query("UPDATE __orders SET total_price=total_price*?", $coef);        
					$this->db->query("UPDATE __purchases SET price=price*?", $coef);
					$this->db->query("UPDATE __coupons SET value=value*? WHERE type='absolute'", $coef);
					$this->db->query("UPDATE __coupons SET min_order_price=min_order_price*?", $coef);
					$this->db->query("UPDATE __orders SET coupon_discount=coupon_discount*?", $coef);
				}          
			}
			
			// Отсортировать валюты
			asort($currencies_ids);
			$i = 0;
			foreach($currencies_ids as $currency_id)
			{ 
				$this->money->update_currency($currencies_ids[$i], array('position'=>$currency_id));
				$i++;
			}

			// Действия с выбранными
			$action = $this->request->post('action');
			$id = $this->request->post('action_id');
			
			if(!empty($action) && !empty($id))
			switch($action)
			{
			    case 'disable':
			    {
					$this->money->update_currency($id, array('enabled'=>0));	      
					break;
			    }
			    case 'enable':
			    {
					$this->money->update_currency($id, array('enabled'=>1));	      
			        break;
			    }
			    case 'show_cents':
			    {
					$this->money->update_currency($id, array('cents'=>2));	      
					break;
			    }
			    case 'hide_cents':
			    {
					$this->money->update_currency($id, array('cents'=>0));	      
			        break;
			    }
			    case 'delete':
			    {
					// Мультивалютность
					$this->db->query("UPDATE __variants SET base_price=price, base_compare_price=compare_price, currency=NULL WHERE currency=?", $id);
					// Мультивалютность end
				    $this->money->delete_currency($id);    
			        break;
			    }
			}		
			
	 	}

  

		// Отображение
	  	$currencies = $this->money->get_currencies();
	  	$currency = $this->money->get_currency();
	 	$this->design->assign('currency', $currency);
	 	$this->design->assign('currencies', $currencies);
		return $this->design->fetch('currency.tpl');
	}
}