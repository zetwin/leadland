<?php

/**
 * Simpla CMS
 *
 * @copyright	2011 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('Simpla.php');

class Brands extends Simpla
{
	/*
	*
	* Функция возвращает массив брендов, удовлетворяющих фильтру
	* @param $filter
	*
	*/
	public function get_brands($filter = array())
	{
		$brands = array();
		$category_id_filter = '';
		$visible_filter = '';
		if(isset($filter['visible']))
			$visible_filter = $this->db->placehold('AND p.visible=?', intval($filter['visible']));
		
		if(!empty($filter['category_id']))
			$category_id_filter = $this->db->placehold("LEFT JOIN __products p ON p.brand_id=b.id LEFT JOIN __products_categories pc ON p.id = pc.product_id WHERE pc.category_id in(?@) $visible_filter", (array)$filter['category_id']);

		// Выбираем все бренды
		$query = $this->db->placehold("SELECT DISTINCT b.id, b.name, b.url, b.meta_title, b.meta_keywords, b.meta_description, b.description, b.country, b.image
								 		FROM __brands b $category_id_filter ORDER BY b.country");
		$this->db->query($query);
	// print_r($query);
		return $this->db->results();
	}

	/*
	*
	* Функция возвращает бренд по его id или url
	* (в зависимости от типа аргумента, int - id, string - url)
	* @param $id id или url поста
	*
	*/
	public function get_brand($id)
	{
		if(is_int($id))			
			$filter = $this->db->placehold('b.id = ?', $id);
		else
			$filter = $this->db->placehold('b.url = ?', $id);
		$query = "SELECT b.id, b.name, b.url, b.meta_title, b.meta_keywords, b.meta_description, b.description, b.very_big_description, b.country, b.image, b.last_update
								 FROM __brands b WHERE $filter LIMIT 1";
		$this->db->query($query);
		return $this->db->result();
	}
	
	// СВЯЗКА КАТЕГОРИЯ БРЕНД - ВЫБОР ОПИСАНИЯ БРЕНДА В ЗАВИСИМОСТИ ОТ КАТЕГОРИИ
	// public function get_brand_category($category_id, $brand_id)
	// {
		// $query = "SELECT description FROM __categories_brands WHERE category_id = 14 AND brand_id = 7 LIMIT 1";
		// $this->db->query($query);
		// print_r($this->db->result());
		// return $this->db->result();
	// }
	
	// public function get_brand_category($category_id, $brand_id)
	// {
		// $query = $this->db->placehold("SELECT description FROM __categories_brands WHERE category_id = ? AND brand_id = ? LIMIT 1", $category_id, $brand_id);
		// $this->db->query($query);
		////print_r($this->db->result());
		////print_r($brand_id);
		// return $this->db->result();
	// }

	/*
	*
	* Добавление бренда
	* @param $brand
	*
	*/
	public function add_brand($brand)
	{
		$brand = (array)$brand;
		if(empty($brand['url']))
		{
			$brand['url'] = preg_replace("/[\s]+/ui", '_', $brand['name']);
			$brand['url'] = strtolower(preg_replace("/[^0-9a-zа-я_]+/ui", '', $brand['url']));
		}
		$this->db->query("INSERT INTO __brands SET ?%, last_update=NOW()", $brand);
		return $this->db->insert_id();
	}

	/*
	*
	* Обновление бренда(ов)
	* @param $brand
	*
	*/		
	public function update_brand($id, $brand)
	{
		$query = $this->db->placehold("UPDATE __brands SET ?%, last_update=NOW() WHERE id=? LIMIT 1", $brand, intval($id));
		$this->db->query($query);
		return $id;
	}
	
	/*
	*
	* Удаление бренда
	* @param $id
	*
	*/	
	public function delete_brand($id)
	{
		if(!empty($id))
		{
			$this->delete_image($id);	
			$query = $this->db->placehold("DELETE FROM __brands WHERE id=? LIMIT 1", $id);
			$this->db->query($query);		
			$query = $this->db->placehold("UPDATE __products SET brand_id=NULL WHERE brand_id=?", $id);
			$this->db->query($query);	
		}
	}
	
	/*
	*
	* Удаление изображения бренда
	* @param $id
	*
	*/
	public function delete_image($brand_id)
	{
		$query = $this->db->placehold("SELECT image FROM __brands WHERE id=?", intval($brand_id));
		$this->db->query($query);
		$filename = $this->db->result('image');
		if(!empty($filename))
		{
			$query = $this->db->placehold("UPDATE __brands SET image=NULL WHERE id=?", $brand_id);
			$this->db->query($query);
			$query = $this->db->placehold("SELECT count(*) as count FROM __brands WHERE image=? LIMIT 1", $filename);
			$this->db->query($query);
			$count = $this->db->result('count');
			if($count == 0)
			{			
				@unlink($this->config->root_dir.$this->config->brands_images_dir.$filename);		
			}
		}
	}
	
	public function get_brand_categories($brand_id)
	{
        $this->db->query("
             SELECT c.id, c.meta_title, c.url, b.url AS brand
            FROM s_categories AS c
            LEFT JOIN s_products_categories AS pc
            ON pc.category_id = c.id
			JOIN s_categories_brands AS cb
            ON cb.brand_id = 1
            LEFT JOIN s_products AS p
            ON p.id = pc.product_id
            LEFT JOIN s_brands AS b
            ON b.id = p.brand_id
            WHERE b.id=? 
            AND c.visible = 1
            GROUP BY c.id
            ORDER BY c.name ASC
        ", $brand_id); 
        return  $this->db->results();
	}

}