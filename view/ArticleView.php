<?PHP

/**
 * Simpla CMS
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 * Этот класс использует шаблон article.tpl
 *
 */

require_once('View.php');


class ArticleView extends View
{

	function fetch()
	{   
		$article_url = $this->request->get('article_url', 'string');
		
		if(empty($article_url))
			return false;

		// Выбираем товар из базы
		$article = $this->articles->get_article((string)$article_url);
		if(empty($article) || (!$article->visible && empty($_SESSION['admin'])))
			return false;
		
		
		if(!empty($_COOKIE['browsed_articles'])){
			$browsed_articles = explode(',', $_COOKIE['browsed_articles']);
			$browsed_article = in_array($article->id, $browsed_articles);
			}
				// Добавим текущий товар
			$browsed_articles[] = $article->id;
			$cookie_val = implode(',', $browsed_articles);
			setcookie("browsed_articles", $cookie_val, time() + (3600 * 6), "/");
			
		//Не считаем просмотры админа и выключеного товара
		// if($article->visible && empty($_SESSION['admin']) && !$browsed_article)
		// $this->articles->update_views($article->id);
	
		
		$article->images = $this->articles->get_images(array('article_id'=>$article->id));
		$article->image = reset($article->images);
					
		//$article->features = $this->features->get_article_options(array('article_id'=>$article->id));
	// $temp_options = array();
        // foreach($article->features as $option) {
           // $temp_options[$option->feature_id]->feature_id = $option->feature_id;
           // $temp_options[$option->feature_id]->name = $option->name;
           // $temp_options[$option->feature_id]->values[] = $option->value;   
        // }
               
        // foreach($temp_options as $id => $option)
           // $temp_options[$id]->value = implode(', ', $temp_options[$id]->values);        
                   
        // $article->features = $temp_options;

	  // Автозаполнение имени для формы комментария
        if(!empty($this->user))
            $this->design->assign('comment_name', $this->user->name);
            $this->design->assign('comment_email', $this->user->email);
        
        // Принимаем комментарий
        if ($this->request->method('post') && $this->request->post('comment'))
        {
            $comment->name = $this->request->post('name');
            $comment->email = $this->request->post('email');
            $comment->text = $this->request->post('text');
            $captcha_code =  $this->request->post('captcha_code', 'string');
            
            // Передадим комментарий обратно в шаблон - при ошибке нужно будет заполнить форму
            $this->design->assign('comment_text', $comment->text);
            $this->design->assign('comment_name', $comment->name);
            $this->design->assign('comment_email', $comment->email);
			
			// Проверяем капчу и заполнение формы
			if ($_SESSION['captcha_code'] != $captcha_code || empty($captcha_code))
			{
				$this->design->assign('error', 'captcha');
			}
			elseif (empty($comment->name))
			{
				$this->design->assign('error', 'empty_name');
			}
			elseif (empty($comment->text))
			{
				$this->design->assign('error', 'empty_comment');
			}
			else
			{
				// Создаем комментарий
				$comment->object_id = $article->id;
				$comment->type      = 'article';
				$comment->ip        = $_SERVER['REMOTE_ADDR'];
				
				// Если были одобренные комментарии от текущего ip, одобряем сразу
				$this->db->query("SELECT 1 FROM __comments WHERE approved=1 AND ip=? LIMIT 1", $comment->ip);
				if($this->db->num_rows()>0)
					$comment->approved = 1;
				
				// Добавляем комментарий в базу
				$comment_id = $this->comments->add_comment($comment);
				
				// Отправляем email
				$this->notify->email_comment_admin($comment_id);				
				
				// Приберем сохраненную капчу, иначе можно отключить загрузку рисунков и постить старую
				unset($_SESSION['captcha_code']);
				header('location: '.$_SERVER['REQUEST_URI'].'#comment_'.$comment_id);
			}			
		}
		
		
		
		
// Связанные товары
		$related_ids = array();
		$related_products = array();
		foreach($this->articles->get_related_products($article->id) as $p)
		{
			$related_ids[] = $p->related_id;
			$related_products[$p->related_id] = null;
		}
		if(!empty($related_ids))
		{
			foreach($this->products->get_products(array('id'=>$related_ids, 'visible'=>1)) as $p)
				$related_products[$p->id] = $p;
			
			$related_products_images = $this->products->get_images(array('product_id'=>array_keys($related_products)));
			foreach($related_products_images as $related_product_image)
				if(isset($related_products[$related_product_image->product_id]))
					$related_products[$related_product_image->product_id]->images[] = $related_product_image;
			$related_products_variants = $this->variants->get_variants(array('product_id'=>array_keys($related_products), 'in_stock'=>1));
			foreach($related_products_variants as $related_product_variant)
			{
				if(isset($related_products[$related_product_variant->product_id]))
				{
					$related_products[$related_product_variant->product_id]->variants[] = $related_product_variant;
				}
			}
			foreach($related_products as $id=>$r)
			{
				if(is_object($r))
				{
					$r->image = &$r->images[0];
					$r->variant = &$r->variants[0];
				}
				else
				{
					unset($related_products[$id]);
				}
			}
		}
		
	//ГЕНЕРАЦИЯ СВЯЗАННЫХ ТОВАРОВ ПО КАТЕГОРИИ
		$after = false;
			
			if($article->related_category && $article->brand_id){
				$relatedcategory = $this->categories->get_category((int)$article->related_category);
				$products = $this->products->get_products(array('category_id' => $relatedcategory->children, 'brand_id'=> $article->brand_id, 'limit' => 5, 'sort'=>random, 'visible'=>1));
			}elseif($article->related_category){
				$relatedcategory = $this->categories->get_category((int)$article->related_category);
				$products = $this->products->get_products(array('category_id' => $relatedcategory->children, 'limit' => 5, 'sort'=>random, 'visible'=>1));
			}elseif($article->brand_id){
				$products = $this->products->get_products(array('brand_id' => $article->brand_id, 'limit' => 5, 'sort'=>random, 'visible'=>1));
			}
		 
		 if($products){
			foreach($products as $p)
			{
				if($after && count($related_products) < 10)
					$related_products[$p->id] = $p;
				elseif($p->id == $product->id)
					$after = true;
			}
		 
			if(count($related_products) < 10)
				foreach($products as $p)
					if($p->id != $product->id && count($related_products) < 10)
						$related_products[$p->id] = $p;
					else break;  
		 
			$related_products_images = $this->products->get_images(array('product_id'=>array_keys($related_products)));
			foreach($related_products_images as $related_product_image)
				if(isset($related_products[$related_product_image->product_id]))
					$related_products[$related_product_image->product_id]->images[] = $related_product_image;
			$related_products_variants = $this->variants->get_variants(array('product_id'=>array_keys($related_products), 'instock'=>true));
			foreach($related_products_variants as $related_product_variant)
			{
				if(isset($related_products[$related_product_variant->product_id]))
				{
					$related_product_variant->price *= (100-$discount)/100;
					$related_products[$related_product_variant->product_id]->variants[] = $related_product_variant;
				}
			}
			foreach($related_products as $r)
			{
				$r->image = &$r->images[0];
				$r->variant = &$r->variants[0];
			}
		
    $this->design->assign('related_products', $related_products);
		}

	//ГЕНЕРАЦИЯ СВЯЗАННЫХ ТОВАРОВ ПО КАТЕГОРИИ END

		// Отзывы о товаре
		$comments = $this->comments->get_comments(array('type'=>'article', 'object_id'=>$article->id, 'approved'=>1, 'ip'=>$_SERVER['REMOTE_ADDR']));
		
		// Соседние товары
		$this->design->assign('next_article', $this->articles->get_next_article($article->id));
		$this->design->assign('prev_article', $this->articles->get_prev_article($article->id));

		// И передаем его в шаблон
		$this->design->assign('article', $article);
		$this->design->assign('comments', $comments);
		
		// Категория и бренд товара
		$article->articlecategories = $this->articlecategories->get_articlecategories(array('article_id'=>$article->id));
		$this->design->assign('brand', $this->brands->get_brand(intval($article->brand_id)));		
		$this->design->assign('articlecategory', reset($article->articlecategories));		
		

		// Добавление в историю просмотров товаров
		$max_visited_articles = 100; // Максимальное число хранимых товаров в истории
		$expire = time()+60*60*24*30; // Время жизни - 30 дней
		if(!empty($_COOKIE['browsed_articles']))
		{
			$browsed_articles = explode(',', $_COOKIE['browsed_articles']);
			// Удалим текущий товар, если он был
			if(($exists = array_search($article->id, $browsed_articles)) !== false)
				unset($browsed_articles[$exists]);
		}
		// Добавим текущий товар
		$browsed_articles[] = $article->id;
		$cookie_val = implode(',', array_slice($browsed_articles, -$max_visited_articles, $max_visited_articles));
		setcookie("browsed_articles", $cookie_val, $expire, "/");
		
		$this->design->assign('meta_title', $article->meta_title);
		$this->design->assign('meta_keywords', $article->meta_keywords);
		$this->design->assign('meta_description', $article->meta_description);
		$this->design->assign('units', $this->variants->units);
		
		return $this->design->fetch('article.tpl');
	}
	


}