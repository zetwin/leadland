<?PHP

/**
 * Simpla CMS
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 * Этот класс использует шаблон articles.tpl
 *
 */
 
require_once('View.php');

class ArticlesView extends View
{
    function fetch()
    {   
       
	   
	   
	   
	   
	   
	   // GET-Параметры
		$articlecategory_url = $this->request->get('articlecategory', 'string');
		
		$filter = array();
		$filter['visible'] = 1;
		
		// Выберем текущую категорию
		if (!empty($articlecategory_url))
		{
			$articlecategory = $this->articlecategories->get_articlecategory((string)$articlecategory_url);
			if (empty($articlecategory) || (!$articlecategory->visible && empty($_SESSION['admin'])))
				return false;
			$this->design->assign('articlecategory', $articlecategory);
			$filter['articlecategory_id'] = $articlecategory->children;
		}

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
	   
	   
	   
	   // Постраничная навигация
		$items_per_page = 10;		
		// Текущая страница в постраничном выводе
		$current_page = $this->request->get('page', 'int');	
		// Если не задана, то равна 1
		$current_page = max(1, $current_page);
		$this->design->assign('current_page_num', $current_page);
		// Вычисляем количество страниц
		$articles_count = $this->articles->count_articles($filter);
		
		// Показать все страницы сразу
		if($this->request->get('page') == 'all')
			$items_per_page = $articles_count;	
		
		$pages_num = ceil($articles_count/$items_per_page);
		$this->design->assign('total_pages_num', $pages_num);
		$this->design->assign('total_articles_num', $articles_count);

		$filter['page'] = $current_page;
		$filter['limit'] = $items_per_page;
	   
	   $discount = 0;
		if(isset($_SESSION['user_id']) && $user = $this->users->get_user(intval($_SESSION['user_id'])))
			$discount = $user->discount;
			
		// Товары 
		$articles = array();
		foreach($this->articles->get_articles($filter) as $p)
			$articles[$p->id] = $p;
			
		// Если искали товар и найден ровно один - перенаправляем на него
		if(!empty($keyword) && $articles_count == 1)
			header('Location: '.$this->config->root_url.'/articles/'.$p->url);
	   
	   if(!empty($articles))
		{
			$articles_ids = array_keys($articles);
			$comments_count = $this->comments->count_comments_only(array('object_id'=>$articles_ids, 'approved'=>1,'type'=>'article'));

			foreach($articles as &$article)
			{
				$article->images = array();
				$article->properties = array();
					if(isset($comments_count[$article->id]))
					$article->comments_count = $comments_count[$article->id];
				else
					$article->comments_count = 0;
			}

			$images = $this->articles->get_images(array('article_id'=>$articles_ids));
			foreach($images as $image)
				$articles[$image->article_id]->images[] = $image;
				
				foreach($articles as &$article)
			{
				if(isset($article->images[0]))
					$article->image = $article->images[0];
			}
			/*
			$properties = $this->features->get_options(array('article_id'=>$articles_ids));
			foreach($properties as $property)
				$articles[$property->article_id]->options[] = $property;
			*/
	
			$this->design->assign('articles', $articles);
 		}
	   
	   	// Устанавливаем мета-теги в зависимости от запроса
		if($this->page)
		{
			$this->design->assign('meta_title', $this->page->meta_title);
			$this->design->assign('meta_keywords', $this->page->meta_keywords);
			$this->design->assign('meta_description', $this->page->meta_description);
			
			if($this->page->last_update)
                $this->last_update = $this->page->last_update;
		}
		elseif(isset($articlecategory))
		{
			$this->design->assign('meta_title', $articlecategory->meta_title);
			$this->design->assign('meta_keywords', $articlecategory->meta_keywords);
			$this->design->assign('meta_description', $articlecategory->meta_description);
		}
		elseif(isset($keyword))
		{
			$this->design->assign('meta_title', $keyword);
		}
 
        return $this->design->fetch('articles.tpl');
    }
}
