<?php

require_once('api/Simpla.php');


############################################
# Class Category - Edit the good gategory
############################################
class TagAdmin extends Simpla
{
  // private	$allowed_image_extentions = array('png', 'gif', 'jpg', 'jpeg', 'ico');
  
  function fetch()
  {
		$tag = new stdClass;
		if($this->request->method('post'))
		{
			$tag->id = $this->request->post('id', 'integer');
			$tag->name = $this->request->post('name');
			$tag->visible = $this->request->post('visible', 'boolean');

			$tag->url = $this->request->post('url', 'string');
			$tag->meta_title = $this->request->post('meta_title');
			// $tag->meta_keywords = $this->request->post('meta_keywords');
			$tag->meta_description = $this->request->post('meta_description');
			
			$tag->description = $this->request->post('description');

			// print_r($tag);
	
			// Не допустить одинаковые URL разделов.
			if(($c = $this->tags->get_tag($tag->name)) && $c->id!=$tag->id)
			{			
				$this->design->assign('message_error', 'url_exists');
			}
			else
			{
				if(empty($tag->id))
				{
	  				$tag->id = $this->tag->add_tag($tag);
					$this->design->assign('message_success', 'added');
	  			}
  	    		else
  	    		{
  	    			$this->tags->update_tag($tag->id, $tag);
							$this->design->assign('message_success', 'updated');
  	    		}
  	    		// Удаление изображения
  	    		// if($this->request->post('delete_image'))
  	    		// {
  	    			// $this->tags->delete_image($tag->id);
  	    		// }
  	    		// Загрузка изображения
  	    		// $image = $this->request->files('image');
  	    		// if(!empty($image['name']) && in_array(strtolower(pathinfo($image['name'], PATHINFO_EXTENSION)), $this->allowed_image_extentions))
  	    		// {
  	    			// $this->tags->delete_image($category->id);
  	    			// move_uploaded_file($image['tmp_name'], $this->root_dir.$this->config->categories_images_dir.$image['name']);
  	    			// $this->tags->update_category($category->id, array('image'=>$image['name']));
  	    		// }
				
  	    		$tag = $this->tags->get_tag(intval($tag->id));
			}
		}
		else
		{
			$tag->id = $this->request->get('id', 'integer');
			$tag = $this->tags->get_tag($tag->id);
		}
		

		// $categories = $this->categories->get_categories_tree();
		
		// $features = $this->features->get_features();
			// $this->design->assign('features', $features);

			// $feature_categories_tmp = array();
			// if (!empty($category->id)) {
				// $feature_categories = $this->features->get_features(array('category_id' => $category->id));
				// foreach ($feature_categories as $f_cat) {
					// $feature_categories_tmp[] = $f_cat->id;
				// }
			// }
			// $this->design->assign('feature_categories', $feature_categories_tmp);
		

		$this->design->assign('tag', $tag);
		// $this->design->assign('categories', $categories);
		return  $this->design->fetch('tag.tpl');
	}
}