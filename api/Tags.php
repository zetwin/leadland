<?php
/**
 * Работа с товарами
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 */

require_once('Simpla.php');

class Tags extends Simpla
{
	
		public function get_alltags()
	{
		$query = $this->db->placehold("SELECT * FROM __tags ORDER BY items_count DESC"); //ORDER BY name
		$this->db->query($query);
		return $this->db->results();
	}
	
		// Функция возвращает тег
	public function get_tag($id)
	{		
		if(is_int($id))
			$filter = $this->db->placehold('id = ?', $id);
		else
			$filter = $this->db->placehold('url = ?', $id);
		
		$query = "SELECT *
				FROM __tags 
				WHERE $filter
				LIMIT 1";
		$this->db->query($query);
		$tag = $this->db->result();
		return $tag;
	}
	
	    /*
    *
    * Получаем список тегов
    *
    */        
		
	function get_tags($filter = array())
	{	
	
	// if(empty($filter['item_id']))
		// return false;
	
				$tags = array();
				$type_filter = '';
        $item_id_filter = '';
        // $group = '';
				
									
				
				// $category_id_filter = '';
				// $visible_filter = '';
				// if(isset($filter['visible']))
					// $visible_filter = $this->db->placehold('AND p.visible=?', intval($filter['visible']));
				
				// if(isset($filter['group']))
            // $group = 'GROUP BY url';

   

    if(!empty($filter['item_id'])){
      $item_id_filter = $this->db->placehold('AND item_id in(?@)', (array)$filter['item_id']);
			
		if(isset($filter['type']))
			$type_filter = $this->db->placehold('AND type=?', $filter['type']);
		
		$query = $this->db->placehold("SELECT t.id, t.name, t.url
					FROM __tags t LEFT JOIN __tags_items i ON i.tag_id = t.id
					WHERE 
					1
					$item_id_filter $type_filter $keyword_filter GROUP BY url
					");
					
					// print_r($item_id_filter);
					
		// Выбираем все бренды
		// $query = $this->db->placehold("SELECT t.id, t.name, t.url
												// FROM __tags t LEFT JOIN __tags_items i ON i.tag_id = t.id $category_id_filter WHERE 1
					 // $item_id_filter $type_filter ORDER BY t.items_count");
												
		$this->db->query($query);

		$res = $this->db->results();
				// print_r($query);
		return $res;
		}
	}
	
	// Добавляем тег

	public function add_tag($tag)
	{
		$query = $this->db->placehold(" WHERE name=?", $tag);
		$this->db->query($query);
		$id = $this->db->result('id');
		if(empty($id))
		{
			$query = $this->db->placehold("INSERT INTO __tags SET name=?", $tag);
			$this->db->query($query);
			$id = $this->db->insert_id();
			$query = $this->db->placehold("UPDATE __tags SET ?% position=id, WHERE id=?", $tag, $id);
			$this->db->query($query);
		}
		return($id);
	}
	
		// public function update_tag($id, $tag)
	// {
		// $query = $this->db->placehold("UPDATE __tags SET ?% WHERE id in (?@) LIMIT ?", $tag, (array)$id, count((array)$id));
		// if($this->db->query($query))
			// return $id;
	// }
	
		public function update_tag($id, $tag)
	{

		$query = $this->db->placehold("UPDATE __tags SET ?% WHERE id=? LIMIT 1", $tag, intval($id));
		$this->db->query($query);
		return $id;
	}
	
	
	// генерация url тегов
	public function tags_urls()
	{
		$query = $this->db->placehold("SELECT * FROM __tags ORDER BY id");
		$this->db->query($query);
		$tags = $this->db->results();
		
		if(!empty($tags)){
			foreach($tags as $tag){
				if(empty($tag->url)){
						$tag->url = $this->translit($tag->name);
						$query = $this->db->placehold("UPDATE __tags SET url=? WHERE id=?", $tag->url, $tag->id);
						$this->db->query($query);
						// print_r($query);
				}
			}
		}
	}
	

	function get_tag_items($filter = array())
	{
	        $keyword_filter = '';
	        if(!empty($filter['keyword']))
        {
            $keywords = explode(',', $filter['keyword']);
            foreach($keywords as $keyword)
            {
                $kw = $this->db->escape(trim($keyword));
                $keyword_filter .= " AND tag LIKE '%$kw%'";
            }
        }
				
				$query = $this->db->placehold("SELECT id FROM __tags WHERE 1 $keyword_filter"); 
        $this->db->query($query); 
         
        $tagid = $this->db->result('id'); 
	
				$type_filter = '';
        $group = '';
				
				if(isset($filter['group']))
            $group = 'GROUP BY tag';

        if(isset($filter['type']))
            $type_filter = $this->db->placehold('AND type=?', $filter['type']);

		$query = $this->db->placehold("SELECT item_id FROM __tags_items WHERE tag_id=? $tag_id_filter $type_filter $group", $tagid);

		$this->db->query($query);
		$res = $this->db->results();
		
		return $res;
	}
	
	
	
	
//Считаем все теги
	public function tags_count()
	{		
		$query = "SELECT count(distinct t.id) as count
				FROM __tags AS t
				WHERE 1
					$keyword_filter
					$visible_filter";
					
		$this->db->query($query);	
		return $this->db->result('count');
	}
	
	//Считаем все елементы тегов
	public	function tags_recount() {
	    $query = $this->db->placehold("SELECT id FROM __tags ORDER BY id");
	    $this->db->query($query);
	    $tags = $this->db->results('id');
				
	    if(!empty($tags)){
				foreach($tags as $tag){
							
	                $query = $this->db->placehold("SELECT COUNT(t.c) as count FROM (SELECT 1 AS c FROM __tags_items WHERE __tags_items.tag_id=?) AS t", $tag);
	                $this->db->query($query);
									$items_count = $this->db->result('count');
									
									
	                $query = $this->db->placehold("UPDATE __tags SET items_count=? WHERE id=?", $items_count, $tag);
	                $this->db->query($query);
									// print_r($query);
	            }
	            // $query = $this->db->placehold("INSERT INTO __tags SET name=?", $tag);
	            // $this->db->query($query);
	            // $id = $this->db->insert_id();
	            // $query = $this->db->placehold("UPDATE __tags SET ?% position=id, WHERE id=?", $tag, $id);
	            // $this->db->query($query);
	    }
	}
	
	

	
	
 	// Добавление тега к елементу
	public function add_tag_item($type, $item_id, $tag_id)
	{
		$query = $this->db->placehold("SELECT id FROM __tags_items WHERE item_id=? AND tag_id=?", $item_id, $tag_id);
		$this->db->query($query);
		$id = $this->db->result('id');
		if(empty($id))
		{
			$query = $this->db->placehold("INSERT INTO __tags_items SET item_id=?, tag_id=?", $item_id, $tag_id);
			$this->db->query($query);
			// $id = $this->db->insert_id();
			// $query = $this->db->placehold("UPDATE __tags_items SET position=id WHERE id=?", $id);
			// $this->db->query($query);
		}
		return($id);
	}
		

	
	

    /*
    *
    * Добавляем теги
    *
    */    
	
public function add_tags($type, $item_id, $tags) 
    {
			if(empty($tags))
				return false;
			
        $tags = explode(',', $tags); 
        foreach($tags as $tag)  
        {
				
				$tagurl = $this->translit($tag);
        $query = $this->db->placehold("SELECT id FROM __tags WHERE name=? ORDER BY id", $tag); 
        $this->db->query($query); 
         
        $tagid = $this->db->result('id'); 
         
        if(!empty($tagid)){ 
				
					$query = $this->db->placehold("UPDATE __tags SET position=?, url=? WHERE id=?", $tagid, $tagurl, $tagid);
					$this->db->query($query);
					$query = $this->db->placehold("INSERT INTO __tags_items SET tag_id=?, item_id=?, type=?", $tagid, $item_id, $type);                                 
					$this->db->query($query);  
            }else{
							$query = $this->db->placehold("INSERT INTO __tags SET name=?, url=?", $tag, $tagurl); 
							$this->db->query($query); 
							$newtagid = $this->db->insert_id(); 
							$query = $this->db->placehold("INSERT INTO __tags_items SET tag_id=?, item_id=?, type=?", $newtagid, $item_id, $type);                                 
							$this->db->query($query);  
							// print_r($query); 
            } 
        } 
        return count($tags); 
    }
    
    /*
    *
    * Удаляем все теги
    *
    */    
    public function delete_tags($item_id, $type)
    {
		 // if(isset($filter['type']))
            // $type_filter = $this->db->placehold('AND type=?', $filter['type']);
        $query = $this->db->placehold("DELETE FROM __tags_items WHERE item_id=? AND type=?", intval($item_id), $type);
        // $query = $this->db->placehold("DELETE FROM __tags WHERE id=?", intval($item_id));
        $this->db->query($query);
    }
		

    
	private function translit($text)
	{
		$ru = explode('-', "А-а-Б-б-В-в-Ґ-ґ-Г-г-Д-д-Е-е-Ё-ё-Є-є-Ж-ж-З-з-И-и-І-і-Ї-ї-Й-й-К-к-Л-л-М-м-Н-н-О-о-П-п-Р-р-С-с-Т-т-У-у-Ф-ф-Х-х-Ц-ц-Ч-ч-Ш-ш-Щ-щ-Ъ-ъ-Ы-ы-Ь-ь-Э-э-Ю-ю-Я-я"); 
		$en = explode('-', "A-a-B-b-V-v-G-g-G-g-D-d-E-e-E-e-E-e-ZH-zh-Z-z-I-i-I-i-I-i-J-j-K-k-L-l-M-m-N-n-O-o-P-p-R-r-S-s-T-t-U-u-F-f-H-h-TS-ts-CH-ch-SH-sh-SCH-sch---Y-y---E-e-YU-yu-YA-ya");

	 	$res = str_replace($ru, $en, $text);
		$res = preg_replace("/[\s]+/ui", '-', $res);
		$res = preg_replace('/[^\p{L}\p{Nd}\d-]/ui', '', $res);
	 	$res = strtolower($res);
	    return $res;  
	}
	
	
	
	
}