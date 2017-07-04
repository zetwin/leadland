<?php

require_once('api/Simpla.php');


############################################
# Class Category - Edit the good gategory
############################################
class CategoryAdmin extends Simpla
{
  private	$allowed_image_extentions = array('png', 'gif', 'jpg', 'jpeg', 'ico');
  
  function fetch()
  {
		$category = new stdClass;
		if($this->request->method('post'))
		{
			$category->id = $this->request->post('id', 'integer');
			$category->parent_id = $this->request->post('parent_id', 'integer');
			$category->name = $this->request->post('name');
			$category->visible = $this->request->post('visible', 'boolean');

			$category->url = $this->request->post('url', 'string');
			$category->meta_title = $this->request->post('meta_title');
			$category->meta_keywords = $this->request->post('meta_keywords');
			$category->meta_description = $this->request->post('meta_description');
			
			$category->description = $this->request->post('description');
			$features = $this->request->post('features');
			$copy_features = $this->request->post('copy_features', 'boolean');
	
			// Не допустить одинаковые URL разделов.
			if(($c = $this->categories->get_category($category->url)) && $c->id!=$category->id)
			{			
				$this->design->assign('message_error', 'url_exists');
			}
			else
			{
				if(empty($category->id))
				{
	  				$category->id = $this->categories->add_category($category, $copy_features);
					$this->design->assign('message_success', 'added');
	  			}
  	    		else
  	    		{
  	    			$this->categories->update_category($category->id, $category);
					$this->design->assign('message_success', 'updated');
  	    		}
  	    		// Удаление изображения
  	    		if($this->request->post('delete_image'))
  	    		{
  	    			$this->categories->delete_image($category->id);
  	    		}
  	    		// Загрузка изображения
  	    		$image = $this->request->files('image');
  	    		if(!empty($image['name']) && in_array(strtolower(pathinfo($image['name'], PATHINFO_EXTENSION)), $this->allowed_image_extentions))
  	    		{
  	    			$this->categories->delete_image($category->id);
  	    			move_uploaded_file($image['tmp_name'], $this->root_dir.$this->config->categories_images_dir.$image['name']);
  	    			$this->categories->update_category($category->id, array('image'=>$image['name']));
  	    		}
				$this->features->update_category_features($category->id, $features);
  	    		$category = $this->categories->get_category(intval($category->id));
			}
		}
		else
		{
			$category->id = $this->request->get('id', 'integer');
			$category = $this->categories->get_category($category->id);
		}
		

		$categories = $this->categories->get_categories_tree();
		
		$features = $this->features->get_features();
			$this->design->assign('features', $features);

			$feature_categories_tmp = array();
			if (!empty($category->id)) {
				$feature_categories = $this->features->get_features(array('category_id' => $category->id));
				foreach ($feature_categories as $f_cat) {
					$feature_categories_tmp[] = $f_cat->id;
				}
			}
			$this->design->assign('feature_categories', $feature_categories_tmp);
		

		$this->design->assign('category', $category);
		$this->design->assign('categories', $categories);
		return  $this->design->fetch('category.tpl');
	}
}