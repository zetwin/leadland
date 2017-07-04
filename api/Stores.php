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

class Stores extends Simpla
{
	// Список указателей на категории в дереве категорий (ключ = id категории)
	private $all_stores;
	// Дерево категорий
	private $stores_tree;

	// Функция возвращает массив поставщиков
	public function get_stores($filter = array())
	{
		if(!isset($this->stores_tree))
			$this->init_stores();
 
		if(!empty($filter['product_id']))
		{
			$query = $this->db->placehold("SELECT store_id, store_url FROM __products_stores WHERE product_id in(?@) ORDER BY store_id", (array)$filter['product_id']);
			$this->db->query($query);
			$stores = $this->db->results();
			// $stores_ids = $this->db->results('store_id');
			// $stores_urls = $this->db->results('store_url');
		 

			$result = array();
					 
			foreach($stores as $store){
				if(isset($this->all_stores[$store->store_id]))
					$result[$store->store_id] = $this->all_stores[$store->store_id];	
					$result[$store->store_id]->url = $store->store_url;	
			}
			// print_r($stores);
			return $result;
		}
		return $this->all_stores;
	}
	
	// Функция возвращает id категорий для заданного товара
	public function get_product_stores($product_id)
	{
		$query = $this->db->placehold("SELECT product_id, store_id, store_url, position FROM __products_stores WHERE product_id in(?@) ORDER BY store_id", (array)$product_id);
		$this->db->query($query);
		return $this->db->results();
	}	

	// Функция возвращает id категорий для всех товаров
	public function get_products_stores()
	{
		$query = $this->db->placehold("SELECT product_id, store_id, position FROM __products_stores ORDER BY position");
		$this->db->query($query);
		return $this->db->results();
	}	

	// Функция возвращает дерево категорий
	public function get_stores_tree()
	{
		if(!isset($this->stores_tree))
			$this->init_stores();
			
		return $this->stores_tree;
	}

	// Функция возвращает заданную категорию
	public function get_store($id)
	{

		if(!isset($this->all_stores))
			$this->init_stores();
		if(is_int($id) && array_key_exists(intval($id), $this->all_stores))
			return $store = $this->all_stores[intval($id)];
		elseif(is_string($id))
			foreach ($this->all_stores as $store)
				if ($store->url == $id)
					return $this->get_store((int)$store->id);	
		
		return false;
	}
	
	// Добавление категории
	public function add_store($store, $copy_features = false)
	{
		$store = (array)$store;
		if(empty($store['url']))
		{
			$store['url'] = preg_replace("/[\s]+/ui", '_', $store['name']);
			$store['url'] = strtolower(preg_replace("/[^0-9a-zа-я_]+/ui", '', $store['url']));
		}

		// Если есть категория с таким URL, добавляем к нему число
		while($this->get_store((string)$store['url']))
		{
			if(preg_match('/(.+)_([0-9]+)$/', $store['url'], $parts))
				$store['url'] = $parts[1].'_'.($parts[2]+1);
			else
				$store['url'] = $store['url'].'_2';
		}

		$this->db->query("INSERT INTO __stores SET ?%, last_update=NOW()", $store);
		$id = $this->db->insert_id();
		$this->db->query("UPDATE __stores SET position=id, last_update=NOW() WHERE id=?", $id);	
			if ($copy_features && $store['parent_id'] > 0) {
			$query = $this->db->placehold("INSERT INTO __stores_features SELECT ?, f.feature_id FROM __stores_features f WHERE f.store_id = ?", $id, $store['parent_id']);
				$this->db->query($query);			
}
		unset($this->stores_tree);	
		unset($this->all_stores);	
		return $id;
	}
	
	// Изменение категории
	public function update_store($id, $store)
	{
		$query = $this->db->placehold("UPDATE __stores SET ?%, last_update=NOW() WHERE id=? LIMIT 1", $store, intval($id));
		$this->db->query($query);

		unset($this->stores_tree);			
		unset($this->all_stores);	
		return intval($id);
		
		
	}
	
	// Удаление категории
	public function delete_store($ids)
	{
		$ids = (array) $ids;
		foreach($ids as $id)
		{
			if($store = $this->get_store(intval($id)))
			$this->delete_image($store->children);
			if(!empty($store->children))
			{
				$query = $this->db->placehold("DELETE FROM __stores WHERE id in(?@)", $store->children);
				$this->db->query($query);
				$query = $this->db->placehold("DELETE FROM __products_stores WHERE store_id in(?@)", $store->children);
				$this->db->query($query);
			}
		}
		unset($this->stores_tree);			
		unset($this->all_stores);	
		return $id;
	}
	
	// Добавить категорию к заданному товару
	public function add_product_store($product_id, $store_id, $store_url, $position=0)
	{
		if($store_id>0){
		$query = $this->db->placehold("INSERT IGNORE INTO __products_stores SET product_id=?, store_id=?, store_url=?, position=?", $product_id, $store_id, $store_url, $position);
		$this->db->query($query);
		}
	}

	// Удалить категорию заданного товара
	public function delete_product_store($product_id, $store_id)
	{
		$query = $this->db->placehold("DELETE FROM __products_stores WHERE product_id=? AND store_id=? LIMIT 1", intval($product_id), intval($store_id));
		$this->db->query($query);
	}
	
		// Удалить категории заданного товара
	public function delete_product_stores($product_id)
	{
		$query = $this->db->placehold("DELETE FROM __products_stores WHERE product_id=?", intval($product_id));
		$this->db->query($query);
	}
	
	// Удалить изображение категории
	public function delete_image($stores_ids)
	{
		$stores_ids = (array) $stores_ids;
		$query = $this->db->placehold("SELECT image FROM __stores WHERE id in(?@)", $stores_ids);
		$this->db->query($query);
		$filenames = $this->db->results('image');
		if(!empty($filenames))
		{
			$query = $this->db->placehold("UPDATE __stores SET image=NULL WHERE id in(?@)", $stores_ids);
			$this->db->query($query);
			foreach($filenames as $filename)
			{
				$query = $this->db->placehold("SELECT count(*) as count FROM __stores WHERE image=?", $filename);
				$this->db->query($query);
				$count = $this->db->result('count');
				if($count == 0)
				{			
					@unlink($this->config->root_dir.$this->config->stores_images_dir.$filename);		
				}
			}
			unset($this->stores_tree);
			unset($this->all_stores);	
		}
	}


	// Инициализация категорий, после которой категории будем выбирать из локальной переменной
	private function init_stores()
	{
		// Дерево категорий
		$tree = new stdClass();
		$tree->substores = array();
		
		// Указатели на узлы дерева
		$pointers = array();
		$pointers[0] = &$tree;
		$pointers[0]->path = array();
		$pointers[0]->level = 0;
		
		if(empty($_SESSION['admin'])){
		// Выбираем все категории
		$query = $this->db->placehold("SELECT c.id, c.parent_id, c.name, c.adress, c.phone, c.www, c.email, c.schedule, c.info, c.latlongmet, c.mapzoom, c.description, c.url, c.meta_title, c.meta_keywords, c.meta_description, c.image, c.visible, c.position, c.last_update
										FROM __stores c ORDER BY c.parent_id, c.position");
		}else{
		// Выбор категорий с подсчетом количества товаров для каждой. Может тормозить при большом количестве товаров.
		$query = $this->db->placehold("SELECT c.id, c.parent_id, c.name, c.adress, c.phone, c.www, c.email, c.schedule, c.info, c.latlongmet, c.mapzoom, c.description, c.url, c.meta_title, c.meta_keywords, c.meta_description, c.image, c.visible, c.position, COUNT(p.id) as products_count
		                              FROM __stores c LEFT JOIN __products_stores pc ON pc.store_id=c.id LEFT JOIN __products p ON p.id=pc.product_id AND p.visible GROUP BY c.id ORDER BY c.parent_id, c.position");
		}
		
		$this->db->query($query);
		$stores = $this->db->results();
						 
		$finish = false;
		// Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
		while(!empty($stores)  && !$finish)
		{
			$flag = false;
			// Проходим все выбранные категории
			foreach($stores as $k=>$store)
			{
				if(isset($pointers[$store->parent_id]))
				{
					// В дерево категорий (через указатель) добавляем текущую категорию
					$pointers[$store->id] = $pointers[$store->parent_id]->substores[] = $store;
					
					// Путь к текущей категории
					$curr = $pointers[$store->id];
					$pointers[$store->id]->path = array_merge((array)$pointers[$store->parent_id]->path, array($curr));
					// foreach($pointers[$store->id]->path as $p)
					// $pointers[$store->id]->full_url .= $p->url.'/';
					
					// Уровень вложенности категории
					$pointers[$store->id]->level = 1+$pointers[$store->parent_id]->level;

					// Убираем использованную категорию из массива категорий
					unset($stores[$k]);
					$flag = true;
				}
			}
			if(!$flag) $finish = true;
		}
		
		// Для каждой категории id всех ее деток узнаем
		$ids = array_reverse(array_keys($pointers));
		foreach($ids as $id)
		{
			if($id>0)
			{
				$pointers[$id]->children[] = $id;

				if(isset($pointers[$pointers[$id]->parent_id]->children))
					$pointers[$pointers[$id]->parent_id]->children = array_merge($pointers[$id]->children, $pointers[$pointers[$id]->parent_id]->children);
				else
					$pointers[$pointers[$id]->parent_id]->children = $pointers[$id]->children;
					
				// Добавляем количество товаров к родительской категории, если текущая видима
				// if(isset($pointers[$pointers[$id]->parent_id]) && $pointers[$id]->visible)
				//		$pointers[$pointers[$id]->parent_id]->products_count += $pointers[$id]->products_count;
			}
		}
		unset($pointers[0]);
		unset($ids);

		$this->stores_tree = $tree->substores;
		$this->all_stores = $pointers;	
	}
}