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

class ArticleCategories extends Simpla
{
	// Список указателей на категории в дереве категорий (ключ = id категории)
	private $all_articlecategories;
	// Дерево категорий
	private $articlecategories_tree;

	// Функция возвращает массив категорий
	public function get_articlecategories($filter = array())
	{
		if(!isset($this->articlecategories_tree))
			$this->init_articlecategories();
 
		if(!empty($filter['article_id']))
		{
			$query = $this->db->placehold("SELECT articlecategory_id FROM __articles_in_categories WHERE article_id in(?@) ORDER BY position", (array)$filter['article_id']);
			$this->db->query($query);
			$articlecategories_ids = $this->db->results('articlecategory_id');
			$result = array();
			foreach($articlecategories_ids as $id)
				if(isset($this->all_articlecategories[$id]))
					$result[$id] = $this->all_articlecategories[$id];
			return $result;
		}
		
		return $this->all_articlecategories;
	}	
	
	//Меняем вид URL
public function get_all_articlecategories()
{
	$query = $this->db->placehold("SELECT id,url,parent_id FROM __articlecategories WHERE visible=1 ORDER BY position");
   	$this->db->query($query);
		$categories = $this->db->results();
		// Указатели на узлы дерева
		$pointers = array();
		$pointers[0] = new stdClass();
		$pointers[0]->path = array();
		$pointers[0]->level = 0;		
		$finish = false;
		// Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
		while(!empty($categories)  && !$finish)
		{
			$flag = false;
			// Проходим все выбранные категории
			foreach($categories as $k=>$category)
			{
				if(isset($pointers[$category->parent_id]))
				{
					// В дерево категорий (через указатель) добавляем текущую категорию
					$pointers[$category->id] = $pointers[$category->parent_id]->subcategories[] = $category;
					
					// Путь к текущей категории
					$curr = $pointers[$category->id];
					$pointers[$category->id]->path = array_merge((array)$pointers[$category->parent_id]->path, array($curr));
					foreach($pointers[$category->id]->path as $p)
					$pointers[$category->id]->full_url .= $p->url.'/';

					// Уровень вложенности категории
					$pointers[$category->id]->level = 1+$pointers[$category->parent_id]->level;
          
					// Убираем использованную категорию из массива категорий
					unset($categories[$k]);
					$flag = true;
				}
			}
			if(!$flag) $finish = true;
		}
	return $pointers;
}
//Меняем вид URL	
	
	// Функция возвращает id категорий для заданного товара
	public function get_article_categories($article_id)
	{
		$query = $this->db->placehold("SELECT article_id, articlecategory_id, position FROM __articles_in_categories WHERE article_id in(?@) ORDER BY position", (array)$article_id);
		$this->db->query($query);
		return $this->db->results();
	}	

	// Функция возвращает id категорий для всех товаров
	public function get_articles_categories()
	{
		$query = $this->db->placehold("SELECT article_id, articlecategory_id, position FROM __articles_in_categories ORDER BY position");
		$this->db->query($query);
		return $this->db->results();
	}	

	// Функция возвращает дерево категорий
	public function get_articlecategories_tree()
	{
		if(!isset($this->articlecategories_tree))
			$this->init_articlecategories();
			
		return $this->articlecategories_tree;
	}

	// Функция возвращает заданную категорию
	public function get_articlecategory($id)
	{
		if(!isset($this->all_articlecategories))
			$this->init_articlecategories();
		if(is_int($id) && array_key_exists(intval($id), $this->all_articlecategories))
			return $articlecategory = $this->all_articlecategories[intval($id)];
		elseif(is_string($id))
			foreach ($this->all_articlecategories as $articlecategory)
				if ($articlecategory->url == $id)
					return $this->get_articlecategory((int)$articlecategory->id);	
		
		return false;
	}
	
	// Добавление категории
	public function add_articlecategory($articlecategory, $copy_features = false)
	{
		$articlecategory = (array)$articlecategory;
		if(empty($articlecategory['url']))
		{
			$articlecategory['url'] = preg_replace("/[\s]+/ui", '_', $articlecategory['name']);
			$articlecategory['url'] = strtolower(preg_replace("/[^0-9a-zа-я_]+/ui", '', $articlecategory['url']));
		}	

		// Если есть категория с таким URL, добавляем к нему число
		while($this->get_articlecategory((string)$articlecategory['url']))
		{
			if(preg_match('/(.+)_([0-9]+)$/', $articlecategory['url'], $parts))
				$articlecategory['url'] = $parts[1].'_'.($parts[2]+1);
			else
				$articlecategory['url'] = $articlecategory['url'].'_2';
		}

		$this->db->query("INSERT INTO __articlecategories SET ?%", $articlecategory);
		$id = $this->db->insert_id();
		$this->db->query("UPDATE __articlecategories SET position=id WHERE id=?", $id);	
			if ($copy_features && $articlecategory['parent_id'] > 0) {
			$query = $this->db->placehold("INSERT INTO __articlecategories_features SELECT ?, f.feature_id FROM __articlecategories_features f WHERE f.articlecategory_id = ?", $id, $articlecategory['parent_id']);
				$this->db->query($query);			
}
		unset($this->articlecategories_tree);	
		unset($this->all_articlecategories);	
		return $id;
	}
	
	// Изменение категории
	public function update_articlecategory($id, $articlecategory)
	{
		$query = $this->db->placehold("UPDATE __articlecategories SET ?% WHERE id=? LIMIT 1", $articlecategory, intval($id));
		$this->db->query($query);
		unset($this->articlecategories_tree);			
		unset($this->all_articlecategories);	
		return intval($id);
	}
	
	// Удаление категории
	public function delete_articlecategory($ids)
	{
		$ids = (array) $ids;
		foreach($ids as $id)
		{
			if($articlecategory = $this->get_articlecategory(intval($id)))
			$this->delete_image($articlecategory->children);
			if(!empty($articlecategory->children))
			{
				$query = $this->db->placehold("DELETE FROM __articlecategories WHERE id in(?@)", $articlecategory->children);
				$this->db->query($query);
				$query = $this->db->placehold("DELETE FROM __articles_in_categories WHERE articlecategory_id in(?@)", $articlecategory->children);
				$this->db->query($query);
			}
		}
		unset($this->articlecategories_tree);			
		unset($this->all_articlecategories);	
		return $id;
	}
	
	// Добавить категорию к заданному товару
	public function add_article_category($article_id, $articlecategory_id, $position=0)
	{
		$query = $this->db->placehold("INSERT IGNORE INTO __articles_in_categories SET article_id=?, articlecategory_id=?, position=?", $article_id, $articlecategory_id, $position);
		$this->db->query($query);
	}

	// Удалить категорию заданного товара
	public function delete_article_category($article_id, $articlecategory_id)
	{
		$query = $this->db->placehold("DELETE FROM __articles_in_categories WHERE article_id=? AND articlecategory_id=? LIMIT 1", intval($article_id), intval($articlecategory_id));
		$this->db->query($query);
	}
	
	// Удалить изображение категории
	public function delete_image($articlecategories_ids)
	{
		$articlecategories_ids = (array) $articlecategories_ids;
		$query = $this->db->placehold("SELECT image FROM __articlecategories WHERE id in(?@)", $articlecategories_ids);
		$this->db->query($query);
		$filenames = $this->db->results('image');
		if(!empty($filenames))
		{
			$query = $this->db->placehold("UPDATE __articlecategories SET image=NULL WHERE id in(?@)", $articlecategories_ids);
			$this->db->query($query);
			foreach($filenames as $filename)
			{
				$query = $this->db->placehold("SELECT count(*) as count FROM __articlecategories WHERE image=?", $filename);
				$this->db->query($query);
				$count = $this->db->result('count');
				if($count == 0)
				{			
					@unlink($this->config->root_dir.$this->config->articlecategories_images_dir.$filename);		
				}
			}
			unset($this->articlecategories_tree);
			unset($this->all_articlecategories);	
		}
	}


	// Инициализация категорий, после которой категории будем выбирать из локальной переменной
	private function init_articlecategories()
	{
		// Дерево категорий
		$tree = new stdClass();
		$tree->subarticlecategories = array();
		
		// Указатели на узлы дерева
		$pointers = array();
		$pointers[0] = &$tree;
		$pointers[0]->path = array();
		$pointers[0]->level = 0;
		
		// Выбираем все категории
		$query = $this->db->placehold("SELECT c.id, c.parent_id, c.name, c.description, c.url, c.meta_title, c.meta_keywords, c.meta_description, c.image, c.visible, c.position
										FROM __articlecategories c ORDER BY c.parent_id, c.position");
											
		// Выбор категорий с подсчетом количества товаров для каждой. Может тормозить при большом количестве товаров.
		// $query = $this->db->placehold("SELECT c.id, c.parent_id, c.name, c.description, c.url, c.meta_title, c.meta_keywords, c.meta_description, c.image, c.visible, c.position, COUNT(p.id) as articles_count
		//                               FROM __articlecategories c LEFT JOIN __articles_in_categories pc ON pc.articlecategory_id=c.id LEFT JOIN __articles p ON p.id=pc.article_id AND p.visible GROUP BY c.id ORDER BY c.parent_id, c.position");
		
		
		$this->db->query($query);
		$articlecategories = $this->db->results();
		
				
		$finish = false;
		// Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
		while(!empty($articlecategories)  && !$finish)
		{
			$flag = false;
			// Проходим все выбранные категории
			foreach($articlecategories as $k=>$articlecategory)
			{
				if(isset($pointers[$articlecategory->parent_id]))
				{
					// В дерево категорий (через указатель) добавляем текущую категорию
					$pointers[$articlecategory->id] = $pointers[$articlecategory->parent_id]->subarticlecategories[] = $articlecategory;
					
					// Путь к текущей категории
					$curr = $pointers[$articlecategory->id];
					$pointers[$articlecategory->id]->path = array_merge((array)$pointers[$articlecategory->parent_id]->path, array($curr));
					foreach($pointers[$articlecategory->id]->path as $p)
					$pointers[$articlecategory->id]->full_url .= $p->url.'/';
					
					// Уровень вложенности категории
					$pointers[$articlecategory->id]->level = 1+$pointers[$articlecategory->parent_id]->level;

					// Убираем использованную категорию из массива категорий
					unset($articlecategories[$k]);
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
				//		$pointers[$pointers[$id]->parent_id]->articles_count += $pointers[$id]->articles_count;
			}
		}
		unset($pointers[0]);
		unset($ids);

		$this->articlecategories_tree = $tree->subarticlecategories;
		$this->all_articlecategories = $pointers;	
	}
}