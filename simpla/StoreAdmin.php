<?php

require_once('api/Simpla.php');


############################################
# Class Store - Edit the good gategory
############################################
class StoreAdmin extends Simpla
{
  private	$allowed_image_extentions = array('png', 'gif', 'jpg', 'jpeg', 'ico');
  
  function fetch()
  {
		$store = new stdClass;
		if($this->request->method('post'))
		{
			$store->id = $this->request->post('id', 'integer');
			$store->parent_id = $this->request->post('parent_id', 'integer');
			$store->name = $this->request->post('name');
			$store->visible = $this->request->post('visible', 'boolean');

			$store->adress = $this->request->post('adress');
			$store->phone = $this->request->post('phone');
			$store->phone = implode(';', $store->phone);
			$store->www = $this->request->post('www');
			$store->schedule = $this->request->post('schedule');
			$store->email = $this->request->post('email');
			$store->info = $this->request->post('info');
			$store->latlongmet = $this->request->post('latlongmet');
			$store->mapzoom = $this->request->post('mapzoom');
			
			$store->url = $this->request->post('url', 'string');
			$store->meta_title = $this->request->post('meta_title');
			$store->meta_keywords = $this->request->post('meta_keywords');
			$store->meta_description = $this->request->post('meta_description');
			
			$store->description = $this->request->post('description');
			$features = $this->request->post('features');
			$copy_features = $this->request->post('copy_features', 'boolean');
	
	// print_r($store);
	
			// Не допустить одинаковые URL разделов.
			if(($c = $this->stores->get_store($store->url)) && $c->id!=$store->id)
			{			
				$this->design->assign('message_error', 'url_exists');
			}
			else
			{
				if(empty($store->id)){
							$store->id = $this->stores->add_store($store, $copy_features);
							$this->design->assign('message_success', 'added');
						}else{
  	    			$this->stores->update_store($store->id, $store);
							$this->design->assign('message_success', 'updated');
  	    		}
  	    		// Удаление изображения
  	    		if($this->request->post('delete_image'))
  	    		{
  	    			$this->stores->delete_image($store->id);
  	    		}
  	    		// Загрузка изображения
  	    		$image = $this->request->files('image');
  	    		if(!empty($image['name']) && in_array(strtolower(pathinfo($image['name'], PATHINFO_EXTENSION)), $this->allowed_image_extentions))
  	    		{
  	    			$this->stores->delete_image($store->id);
  	    			move_uploaded_file($image['tmp_name'], $this->root_dir.$this->config->stores_images_dir.$image['name']);
  	    			$this->stores->update_store($store->id, array('image'=>$image['name']));
  	    		}
				// $this->features->update_store_features($store->id, $features);
  	    		$store = $this->stores->get_store(intval($store->id));
						
			}
		}
		else
		{
			$store->id = $this->request->get('id', 'integer');
			$store = $this->stores->get_store($store->id);
			$stores = $this->stores->get_stores_tree();
		}
		
		if($store){
		$store->phones = explode(';', $store->phone);
		}
		
		// print_r($store);
		// $store->phones = explode(';', $store->phone);
		// $store->phone = implode(';', $store->phone);
		
		// $store = new stdClass();
		
		// print_r($store->phones);
		// $store['phone'] = 11;
		
		// $products_viewed = explode(',', $_COOKIE['products_viewed']);
		
		// $features = $this->features->get_features();
			// $this->design->assign('features', $features);

			// $feature_stores_tmp = array();
			// if (!empty($store->id)) {
				// $feature_stores = $this->features->get_features(array('store_id' => $store->id));
				// foreach ($feature_stores as $f_cat) {
					// $feature_stores_tmp[] = $f_cat->id;
				// }
			// }
			// $this->design->assign('feature_stores', $feature_stores_tmp);
		

		// $this->design->assign('store', $store);
		$this->design->assign('store', $store);
		$this->design->assign('stores', $stores);
		return  $this->design->fetch('store.tpl');
	}
}