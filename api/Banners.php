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
class Banners extends Simpla
{

	/*
	*
	* Функция возвращает пост по его id или url
	* (в зависимости от типа аргумента, int - id, string - url)
	* @param $id id или url поста
	*
	*/
	
	/******
	Получить список групп баннеров
	*********/
	public function get_groups()
	{
		$this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM s_banners_groups ORDER BY `id`;");
		$banner_groups = $this->db->results();
		$this->db->query("SELECT FOUND_ROWS() as count");
		$count_banner_groups = $this->db->result('count');
		
		return array($banner_groups,$count_banner_groups);
	}
	
	/******
	Получить информацию о группе и список баннеров группы
	*********/
	public function get_group($id)
	{
		$this->db->query("SELECT * FROM s_banners_groups WHERE `id` = ? ",(int)$id);
		return $this->db->result();
	}

	/******
	Обновление группы
	*********/
	public function update_group($id, $values)
	{
		$query = $this->db->placehold("UPDATE __banners_groups SET ?% WHERE id in (?@) LIMIT ?", $values, (array)$id, count((array)$id));
		if($this->db->query($query))
		{
			return $id;
		}
	}
	
	/******
	Удаление группы
	*********/
	public function delete_group($id)
	{
		//Находим и удаляем все баннеры ииз группы
		list($banners,$counts) = $this->get_banners(array('BannerOfPage'=>1000,'group'=>$id));

		foreach($banners as $key=>$value)
		{
			$this->delete_banner($banners[$key]->id);
		}
		
		list($banners,$counts) = $this->get_banners(array('BannerOfPage'=>1000,'group'=>$id)); //Проверяем, все ли баннеры удалены
		if($counts > 0)
			exit("<h3>ГРУППУ НЕ УДАЛОСЬ УДАЛИТЬ<br>В ГРУППЕ ОСТАЛИСЬ БАННЕРЫ КОТОРЫЕ НЕ УДАЛОСЬ УДАЛИТЬ<br>ПОПРОБУЙТЕ УДАЛИТЬ ВРУЧНУЮ!</h3>");
		else
			$this->db->query("DELETE FROM __banners_groups WHERE id=? LIMIT 1", intval($id));
	}
	
	/******
	Получить список баннеров
	*********/
	public function get_banners($filter = ARRAY())
	{
		$filter['BannerOfPage'] = isset($filter['BannerOfPage'])?$filter['BannerOfPage']:100;		
		$sql_limit = $this->db->placehold(' LIMIT ?, ? ', (max(1, $this->request->get('page', 'integer'))-1)*$filter['BannerOfPage'], $filter['BannerOfPage']);
		
		//Фильтруем по группе баннеров
		$filter['query'][0] = $this->db->placehold("`id_group`='?'", (int)$filter['group']);
		
		//Фильтруем баннеры где указан параметр "показывать на всех страницах" и "активен"
		if(isset($filter['show_all_pages']))
		{
			$filter['query'][0] .= " AND `visible`='1' AND ( `show_all_pages`='1'";
		}
		
		//Фильтруем по категории, бренду и странице
		if(isset($filter['category']) && $filter['category']!='')
			$filter['query'][] = $this->db->placehold("`categories` regexp '[[:<:]](?)[[:>:]]'", (int)$filter['category']);
			
		if(isset($filter['brand']) && $filter['brand']!='')
			$filter['query'][] = $this->db->placehold("`brands` regexp '[[:<:]](?)[[:>:]]'", (int)$filter['brand']);
			
		if(isset($filter['page']) && $filter['page']!='')
			$filter['query'][] = $this->db->placehold("`pages` regexp '[[:<:]](?)[[:>:]]'", (int)$filter['page']);
		
		//Собираем значение фильтра в запрос
		$filter['query'] = ((isset($filter['query']) && count($filter['query'])>0)?"WHERE ".implode(" OR ",$filter['query']):$filter['query']).(isset($filter['show_all_pages'])?")":'');
		
		//Выполнение запроса
		$this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM s_banners ".$filter['query']." ORDER BY position ".$sql_limit);
		$banners = $this->db->results();
		$this->db->query("SELECT FOUND_ROWS() as count");
		$count_banners = $this->db->result('count');
		return array($banners,$count_banners);
	}
	
	
	/******
	Получить информацию баннера
	*********/
	public function get_banner($id)
	{
		$this->db->query("SELECT * FROM s_banners WHERE `id` = ? ",(int)$id);
		return $this->db->result();
	}
	
	/******
	Обновление баннера
	*********/
	public function update_banner($id, $values)
	{
		$query = $this->db->placehold("UPDATE __banners SET ?% WHERE id in (?@) LIMIT ?", $values, (array)$id, count((array)$id));
		if($this->db->query($query))
		{
			return $id;
		}
	}
	
	/******
	Удаление баннера
	*********/
	public function delete_banner($id)
	{
		$banner = $this->get_banner($id);
		$query = $this->db->placehold("DELETE FROM __banners WHERE id=? LIMIT 1", intval($id));
		if($this->delete_image($banner->image) && $this->db->query($query))
		{
			return true;				
		}else{
			return false;
		}
	}
	
	/******
	Удаление изображения баннера
	*********/
	function delete_image($imageFileName)
	{
		if($imageFileName!='' && file_exists($this->config->root_dir.$this->config->banners_images_dir.$imageFileName))
			@unlink($this->config->root_dir.$this->config->banners_images_dir.$imageFileName);
		return true;
	}
}
