<?PHP

/**
 * Simpla CMS
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 * Этот класс использует шаблон products.tpl
 *
 */
 
require_once('View.php');

class ProductsView extends View
{
 	/**
	 *
	 * Отображение списка товаров
	 *
	 */	
	function fetch()
	{
		
			// GET-Параметры
			
				
		$category_url = $this->request->get('category', 'string');
		$brand_url    = $this->request->get('brand', 'string');
		$tag_url    = $this->request->get('tag', 'string');
			
			
		
		
	//Меняем вид URL	
	// $all_category = $this->categories->get_all_categories();
	// foreach($all_category as $c)
    	// $array_category[$c->url] = $c->url;
    
		$filter = array();
		$filter['visible'] = 1;	
		$mode = $this->request->get('mode', 'string');
         
        if ($mode == 'hits')
			{$filter['featured'] = 1;}
        if ($mode == 'sale')
			{$filter['discounted'] = 1;} 
		
		// echo $mode;
		
		// foreach($this->urls as $i=>$url)
     	// {     
        // if($i == 0)
        // {    
       	// if($brand = $this->brands->get_brand((string)$url))
       	// {
        // $brand_url = $url;
		
		// Если задан бренд, выберем его из базы
		if (!empty($brand_url))
		{
			$brand = $this->brands->get_brand((string)$brand_url);
			if (empty($brand))
				return false;
			$this->design->assign('brand', $brand);
			$filter['brand_id'] = $brand->id;
			
			//Получаем категории бренда и передаем их в шаблон
			// $brand_cat= $this->products->brands_category($brand->id);
			// $this->design->assign('brand_cat', $brand_cat);
		}
		
			
			if (!empty($tag_url))
			{
			$tag = $this->tags->get_tag((string)$tag_url);
			if (empty($tag))
				return false;
			$this->design->assign('tag', $tag);
			$filter['keyword'] = $tag->name;
		}
					
		 // if($this->urls[1])
        // {
        // if(in_array($this->urls[1],$array_category))
         // $category_url = $this->urls[1];
         // else
         // return false;
         // }
        // }
        // elseif(in_array($url,$array_category)) 
         // $category_url = $url;
		
		// Выберем текущую категорию
		if (!empty($category_url))
		{
			$category = $this->categories->get_category((string)$category_url);
			if (empty($category) || (!$category->visible && empty($_SESSION['admin'])))
				return false;
			$this->design->assign('category', $category);
			$filter['category_id'] = $category->children;
			
			$categories = $this->categories->get_categories();
			   foreach($category->children as $id){
					if($categories[$id]->visible>0)
						$filter['category_id'][] = $id;
				}
          // $path = array();
          // foreach($category->path as $p)
          // $path[] = $p->url;      
		// }         
         
         // }
          // else          
          // { 
         // if(!in_array($url,$path))
		 
         // return false;
         // }
       }
       //Меняем вид URL
		
		// Если задано ключевое слово
		$keyword = $this->request->get('keyword');
		if (!empty($keyword))
		{
			$this->design->assign('keyword', $keyword);
			$filter['keyword'] = $keyword;
		}

		// Сортировка товаров, сохраняем в сесси, чтобы текущая сортировка оставалась для всего сайта
		if($sort = $this->request->get('sort', 'string'))
			$_SESSION['sort'] = $sort;		
		if (!empty($_SESSION['sort']))
			$filter['sort'] = $_SESSION['sort'];			
		else
			$filter['sort'] = 'position';			
		$this->design->assign('sort', $filter['sort']);
		
		// Свойства товаров
		if(!empty($category))
		{
			$features = array();
			$filter['features'] = array();
			foreach($this->features->get_features(array('category_id'=>$category->id, 'in_filter'=>1)) as $feature)
			{ 
				$features[$feature->id] = $feature;
				if(($val = $this->request->get($feature->id))!='')
						$filter['features'][$feature->id] = $val;
			}
			
			$options_filter['visible'] = 1;
			
			$features_ids = array_keys($features);
			if(!empty($features_ids))
				$options_filter['feature_id'] = $features_ids;
			$options_filter['category_id'] = $category->children;
			if(isset($filter['features']))
				$options_filter['features'] = $filter['features'];
			if(!empty($brand))
				$options_filter['brand_id'] = $brand->id;
			
			$options = $this->features->get_options($options_filter);

			foreach($options as $option)
			{
				if(isset($features[$option->feature_id]))
					$features[$option->feature_id]->options[] = $option;
			}
			
			foreach($features as $i=>&$feature)
			{ 
				if(empty($feature->options))
					unset($features[$i]);
			}
			$this->design->assign('filter_features', $filter['features']);
			$this->design->assign('features', $features);
 		}

		// $brand_category = $this->brands->get_brand_category($category->id, $brand->id);
		$this->design->assign('brand_category', $brand_category->description);
		
		// Постраничная навигация
		$items_per_page = $this->settings->products_num;		
		// Текущая страница в постраничном выводе
		$current_page = $this->request->get('page', 'int');	
		// Если не задана, то равна 1
		$current_page = max(1, $current_page);
		$this->design->assign('current_page_num', $current_page);
		// Вычисляем количество страниц
		$products_count = $this->products->count_products($filter);
		
		// Показать все страницы сразу
		if($this->request->get('page') == 'all')
			$items_per_page = $products_count;	
		
		$pages_num = ceil($products_count/$items_per_page);
		$this->design->assign('total_pages_num', $pages_num);
		$this->design->assign('total_products_num', $products_count);

		$filter['page'] = $current_page;
		$filter['limit'] = $items_per_page;
		
		///////////////////////////////////////////////
		// Постраничная навигация END
		///////////////////////////////////////////////
		

		$discount = 0;
		if(isset($_SESSION['user_id']) && $user = $this->users->get_user(intval($_SESSION['user_id'])))
			$discount = $user->discount;
			
		// Товары 
		$products = array();
		foreach($this->products->get_products($filter) as $p)
			$products[$p->id] = $p;
			
		// Если искали товар и найден ровно один - перенаправляем на него
		// if(!empty($keyword) && $products_count == 1)
			// header('Location: '.$this->config->root_url."/".$p->full_url);
		
		if(!empty($products))
		{
			$products_ids = array_keys($products);
			
			//Меняем вид URL
      		// $categories = $this->categories->get_categories(array('product_id'=>$products_ids));
      		//Меняем вид URL
			
			foreach($products as &$product)
			{
				$product->variants = array();
				$product->images = array();
				$product->properties = array();
				//Меняем вид URL
			        // foreach($categories as &$c)
			        // if($c->id == $product->category_id)
			        // $product->category = $c;
			        //Меняем вид URL
			}
	
			$variants = $this->variants->get_variants(array('product_id'=>$products_ids, 'in_stock'=>true));
			
			foreach($variants as &$variant)
			{
				//$variant->price *= (100-$discount)/100;
				$products[$variant->product_id]->variants[] = $variant;
			}
	
			$images = $this->products->get_images(array('product_id'=>$products_ids));
			foreach($images as $image)
				$products[$image->product_id]->images[] = $image;

			foreach($products as &$product)
			{
				if(isset($product->variants[0]))
					$product->variant = $product->variants[0];
				if(isset($product->images[0]))
					$product->image = $product->images[0];
			}
				
	
			/*
			$properties = $this->features->get_options(array('product_id'=>$products_ids));
			foreach($properties as $property)
				$products[$property->product_id]->options[] = $property;
			*/
			$this->design->assign('near_categories', $this->categories->get_categories());
			$this->design->assign('products', $products);
 		}
		
		// Выбираем бренды, они нужны нам в шаблоне	
		if(!empty($category) /*&& empty($brand)*/)
		{
			$brands = $this->brands->get_brands(array('category_id'=>$category->children, 'visible'=>1));
			$category->brands = $brands;	
			
				

		//print_r($category->brands);
							
		//ОТВЕТ СЕРВЕРА 304 ВЫБИРАЕМ ИЗ БАЗЫ
		if($category->last_update)
                $this->last_update = $category->last_update;
		}elseif(!empty($brand) && empty($category)){
			if($brand->last_update)
					$this->last_update = $brand->last_update;
		}
		
		$tags = $this->tags->get_tags(array('item_id'=>$products_ids, 'visible'=>1));
		$this->design->assign('tags', $tags);

		
		// Устанавливаем мета-теги в зависимости от запроса
		if($this->page)
		{
			$this->design->assign('meta_title', $this->page->meta_title);
			$this->design->assign('meta_keywords', $this->page->meta_keywords);
			$this->design->assign('meta_description', $this->page->meta_description);
		}
		elseif(isset($category))
		{
			$this->design->assign('meta_title', $category->meta_title);
			$this->design->assign('meta_keywords', $category->meta_keywords);
			$this->design->assign('meta_description', $category->meta_description);

		}
		elseif(isset($brand))
		{
			$this->design->assign('meta_title', $brand->meta_title);
			$this->design->assign('meta_keywords', $brand->meta_keywords);
			$this->design->assign('meta_description', $brand->meta_description);
		}
		elseif(isset($tag))
    {
				$this->design->assign('meta_title', $tag->meta_title);
        $this->design->assign('meta_keywords', $tag->meta_keywords);
        $this->design->assign('meta_description', $tag->meta_description);
    }
		elseif(isset($keyword))
		{
			$this->design->assign('meta_title', $keyword);
		}
        
		$this->body = $this->design->fetch('products.tpl');
		return $this->body;
	}
	
	

}
