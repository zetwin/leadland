<?PHP

/**
 * Simpla CMS
 *
 * @copyright     2012 t Mitrofanov
 * @link         http://rlstudio.com
 * @author         Art Mitrofanov
 *
 * Этот класс использует шаблон sitemap.tpl
 *
 */
require_once('View.php');

class SitemapView extends View
{
    function fetch()
    {
        
       	// Страницы
		$pages = $this->pages->get_pages(array('visible'=>1));		
		$this->design->assign('pages', $pages);
        
        $posts = $this->blog->get_posts(array('visible'=>1));
        $this->design->assign('posts', $posts);
        
        $categories = $this->categories->get_categories_tree();
        $categories = $this->cat_tree($categories);
        $this->design->assign('cats', $categories);
        
        $brands = $this->brands->get_brands();
        $this->design->assign('brands', $brands);
		
		 // Выбираем товар из базы
        $this->design->assign('meta_title', 'Карта сайта html. Магазин конвекторов.');
        $this->design->assign('meta_keywords', 'Карта сайта html. Магазин конвекторов.');
        $this->design->assign('meta_description', 'Для посетителей. Продажа дизайнерских отопительных приборов. Елитный обогрев.');
        
        return $this->design->fetch('sitemap.tpl');
    }
    
    private function cat_tree($categories) {

        foreach($categories AS $k=>$v) {
            if(isset($v->subcategories)) $this->cat_tree($v->subcategories);
            $categories[$k]->products = $this->products->get_products(array('category_id' => $v->id, 'visible'=>1));  
        } 
        
        return $categories;
    }
	
	
}