<?PHP

/**
 * Simpla CMS
 *
 * @copyright     2012-2014 Redline Studio
 * @link         http://simplashop.com
 * @author         Artiom Mitrofanov
 *
 */

require_once('View.php');

class TagsView extends View
{
	public function fetch()
	{
	
			$tag_url    = $this->request->get('tag', 'string');
			
			
			
			if (!empty($tag_url))
			{
			$tag = $this->tags->get_tag((string)$tag_url);
			if (empty($tag))
				return false;
			$this->design->assign('tag', $tag);
			$filter['keyword'] = $tag->name;
		}
			
			// print_r($tag);
			
        // Если задано ключевое слово
        $keyword = $this->request->get('keyword');
        if (empty($keyword))
            $this->design->assign('tags', $this->tags->get_tags(array('group'=>1)));
        else {
            $this->design->assign('keyword', $keyword);  
            // $tags = $this->tags->get_tags(array('keyword'=>$keyword));
						
            $tag_items = $this->tags->get_tag_items(array('keyword'=>$keyword, 'type' => 'product'));
            	
						
            // Выбирает объекты, которые привязаны к тегу:
            $products_ids = array();
            // $products_ids[] = $tag_items;
            $posts_ids = array();
            foreach($tag_items as $item)
            {
                // if($item->type == 'product')
                    $products_ids[] = $item->item_id;
                // if($tag->type == 'blog')
                    // $posts_ids[] = $item->item_id;
            }
						
						                // print_r($products_ids);
            
            if(count($products_ids) > 0) {
                $products = array();
                foreach($this->products->get_products(array('id'=>$products_ids)) as $p)
                    $products[$p->id] = $p;
                    
                // Выбираем варианты товаров
                $variants = $this->variants->get_variants(array('product_id'=>$products_ids, 'in_stock'=>true));
                
                // Для каждого варианта
                foreach($variants as &$variant)
                {
                    // добавляем вариант в соответствующий товар
                    $products[$variant->product_id]->variants[] = $variant;
                }
                
                // Выбираем изображения товаров
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

                // Передаем в шаблон
                $this->design->assign('products', $products); 
            } 
             
             
            //Блог
            // if(count($posts_ids) > 0) {
                // $posts = array();
                // foreach($this->blog->get_posts(array('id'=>$posts_ids)) as $p)
                    // $posts[$p->id] = $p;
                
                // Передаем в шаблон    
                // $this->design->assign('posts', $posts);  
            // }
            
        }
        
        // Устанавливаем мета-теги в зависимости от запроса
        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }
        elseif(isset($tag))
        {
						$this->design->assign('meta_title', $tag->meta_title);
            $this->design->assign('meta_keywords', $tag->meta_keywords);
            $this->design->assign('meta_description', $tag->meta_description);
        }
        
        return $this->design->fetch('products.tpl');
	}

}