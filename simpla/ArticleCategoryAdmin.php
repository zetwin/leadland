<?php

require_once('api/Simpla.php');


############################################
# Class Category - Edit the good gategory
############################################
class ArticleCategoryAdmin extends Simpla
{
  private	$allowed_image_extentions = array('png', 'gif', 'jpg', 'jpeg', 'ico');
  
  function fetch()
  {
		$articlecategory = new stdClass;
		if($this->request->method('post'))
		{
			$articlecategory->id = $this->request->post('id', 'integer');
			$articlecategory->parent_id = $this->request->post('parent_id', 'integer');
			$articlecategory->name = $this->request->post('name');
			$articlecategory->visible = $this->request->post('visible', 'boolean');

			$articlecategory->url = $this->request->post('url', 'string');
			$articlecategory->meta_title = $this->request->post('meta_title');
			$articlecategory->meta_keywords = $this->request->post('meta_keywords');
			$articlecategory->meta_description = $this->request->post('meta_description');
			
			$articlecategory->description = $this->request->post('description');
			//$features = $this->request->post('features');
			//$copy_features = $this->request->post('copy_features', 'boolean');
	
			// Не допустить одинаковые URL разделов.
			if(($c = $this->articlecategories->get_articlecategory($articlecategory->url)) && $c->id!=$articlecategory->id)
			{			
				$this->design->assign('message_error', 'url_exists');
			}
			else
			{
				if(empty($articlecategory->id))
				{
	  				$articlecategory->id = $this->articlecategories->add_articlecategory($articlecategory, $copy_features);
					$this->design->assign('message_success', 'added');
	  			}
  	    		else
  	    		{
  	    			$this->articlecategories->update_articlecategory($articlecategory->id, $articlecategory);
					$this->design->assign('message_success', 'updated');
  	    		}
  	    		// Удаление изображения
  	    		if($this->request->post('delete_image'))
  	    		{
  	    			$this->articlecategories->delete_image($articlecategory->id);
  	    		}
  	    		// Загрузка изображения
  	    		$image = $this->request->files('image');
  	    		if(!empty($image['name']) && in_array(strtolower(pathinfo($image['name'], PATHINFO_EXTENSION)), $this->allowed_image_extentions))
  	    		{
  	    			$this->articlecategories->delete_image($articlecategory->id);
  	    			move_uploaded_file($image['tmp_name'], $this->root_dir.$this->config->articlecategories_images_dir.$image['name']);
  	    			$this->articlecategories->update_articlecategory($articlecategory->id, array('image'=>$image['name']));
  	    		}
  	    		$articlecategory = $this->articlecategories->get_articlecategory(intval($articlecategory->id));
			}
		}
		else
		{
			$articlecategory->id = $this->request->get('id', 'integer');
			$articlecategory = $this->articlecategories->get_articlecategory($articlecategory->id);
		}
		

		$articlecategories = $this->articlecategories->get_articlecategories_tree();
		
		$features = $this->features->get_features();
			$this->design->assign('features', $features);

			$feature_articlecategories_tmp = array();
			if (!empty($articlecategory->id)) {
				$feature_articlecategories = $this->features->get_features(array('articlecategory_id' => $articlecategory->id));
				foreach ($feature_articlecategories as $f_cat) {
					$feature_articlecategories_tmp[] = $f_cat->id;
				}
			}
			$this->design->assign('feature_articlecategories', $feature_articlecategories_tmp);
		

		$this->design->assign('articlecategory', $articlecategory);
		$this->design->assign('articlecategories', $articlecategories);
		return  $this->design->fetch('articlecategory.tpl');
	}
}