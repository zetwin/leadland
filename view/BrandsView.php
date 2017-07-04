<?PHP
require_once('View.php');
class BrandsView extends View
{
    function fetch()
    {   
        // Выбираем товар из базы
        $this->design->assign('meta_title', 'Все производители');
        $this->design->assign('meta_keywords', 'Все производители');
        $this->design->assign('meta_description', 'Все производители');
 
        $brands = $this->brands->get_brands();
        $this->design->assign('brands', $brands);
		
		if($brand->last_update)
                $this->last_update = $brand->last_update;
 
        return $this->design->fetch('brands.tpl');
    }
}