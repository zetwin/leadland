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

class Comments extends Simpla
{

	// Возвращает комментарий по id
	public function get_comment($id)
	{
		$query = $this->db->placehold("SELECT c.id, c.object_id, c.name, c.ip, c.type, c.text, c.answer, c.date, c.email, c.image, c.rating, c.approved FROM __comments c WHERE id=? LIMIT 1", intval($id));

		if($this->db->query($query))
			return $this->db->result();
		else
			return false; 
	}
	
	// Возвращает комментарии, удовлетворяющие фильтру
	public function get_comments($filter = array())
	{	
		// По умолчанию
		$limit = 0;
		$page = 1;
		$object_id_filter = '';
		$type_filter = '';
		$keyword_filter = '';
		$approved_filter = '';		
		
        $products_fields = '';
        $products_join = '';
		if(!empty($filter['type']))
            if($filter['type'] == 'product')
		    {
                $products_fields = ', p.url, p.name product';
			    $products_join = 'INNER JOIN __products p ON c.object_id=p.id';
		    }
            elseif($filter['type'] == 'blog')
            {
                $products_fields = ', b.url, b.name product';
                $products_join = 'INNER JOIN __blog b ON c.object_id=b.id';
            }

		if(isset($filter['limit']))
			$limit = max(1, intval($filter['limit']));

		if(isset($filter['page']))
			$page = max(1, intval($filter['page']));

		if(isset($filter['ip']))
			$ip = $this->db->placehold("OR c.ip=?", $filter['ip']);
		if(isset($filter['approved']))
			$approved_filter = $this->db->placehold("AND (c.approved=? $ip)", intval($filter['approved']));
			
		if($limit)
			$sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
		else
			$sql_limit = '';

		if(!empty($filter['object_id']))
			$object_id_filter = $this->db->placehold('AND c.object_id in(?@)', (array)$filter['object_id']);

		if(!empty($filter['type']))
			$type_filter = $this->db->placehold('AND c.type=?', $filter['type']);

		if(!empty($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				// $keyword_filter .= $this->db->placehold('AND c.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR c.text LIKE "%'.$this->db->escape(trim($keyword)).'%" ');
				$keyword_filter .= $this->db->placehold('AND c.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR c.text LIKE "%'.$this->db->escape(trim($keyword)).'%" OR c.email LIKE "%'.$this->db->escape(trim($keyword)).'%" ');
		}

			
		$sort='DESC';
		
		$query = $this->db->placehold("SELECT c.id, c.object_id, c.ip, c.name, c.text, c.type, c.answer, c.date, c.email, c.rating, c.approved
										$products_fields, u.image FROM __comments c $products_join LEFT JOIN __users u ON c.email=u.email WHERE 1 $object_id_filter $type_filter $keyword_filter $approved_filter ORDER BY id $sort $sql_limit");
	
		$this->db->query($query);
		return $this->db->results();
	}
	
	// Количество комментариев, удовлетворяющих фильтру
	public function count_comments($filter = array())
	{	
		$object_id_filter = '';
		$type_filter = '';
		$approved_filter = '';
		$keyword_filter = '';

		if(!empty($filter['object_id']))
			$object_id_filter = $this->db->placehold('AND c.object_id in(?@)', (array)$filter['object_id']);

		if(!empty($filter['type']))
			$type_filter = $this->db->placehold('AND c.type=?', $filter['type']);

		if(isset($filter['approved']))
			$approved_filter = $this->db->placehold('AND c.approved=?', intval($filter['approved']));

		if(!empty($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				// $keyword_filter .= $this->db->placehold('AND c.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR c.text LIKE "%'.$this->db->escape(trim($keyword)).'%" ');
				$keyword_filter .= $this->db->placehold('AND c.name LIKE "%'.$this->db->escape(trim($keyword)).'%" OR c.text LIKE "%'.$this->db->escape(trim($keyword)).'%" OR c.email LIKE "%'.$this->db->escape(trim($keyword)).'%" ');
		}

		$query = $this->db->placehold("SELECT count(distinct c.id) as count
										FROM __comments c WHERE 1 $object_id_filter $type_filter $keyword_filter $approved_filter", $this->settings->date_format);
	
		$this->db->query($query);	
		return $this->db->result('count');

	}
	
	// Количество комментариев, удовлетворяющих фильтру
public function count_comments_only($filter = array())
{	
	$object_id_filter = '';
	$type_filter = '';
	$approved_filter = '';

	if(!empty($filter['object_id']))
		$object_id_filter = $this->db->placehold('AND c.object_id in(?@)', (array)$filter['object_id']);

	if(!empty($filter['type']))
		$type_filter = $this->db->placehold('AND c.type=?', $filter['type']);

	if(isset($filter['approved']))
		$approved_filter = $this->db->placehold('AND c.approved=?', intval($filter['approved']));

	$query = $this->db->placehold("SELECT c.object_id, count(distinct c.id) as count
	FROM __comments c WHERE 1 $object_id_filter $type_filter $approved_filter 
	GROUP BY c.object_id");

	$this->db->query($query);
	$comm = $this->db->results();
	$comments = array();
	foreach ($comm as $c) {
		$comments[$c->object_id] = $c->count;
	}
	
	return $comments;

}
	
	// Добавление комментария
	public function add_comment($comment)
	{	
		$query = $this->db->placehold('INSERT INTO __comments
		SET ?%,
		date = NOW()',
		$comment);

		if(!$this->db->query($query))
			return false;

		$id = $this->db->insert_id();
		return $id;
	}
	
	// Изменение комментария
	public function update_comment($id, $comment)
	{
		$date_query = '';
		if(isset($comment->date))
		{
			$date = $comment->date;
			unset($comment->date);
			$date_query = $this->db->placehold(', date=STR_TO_DATE(?, ?)', $date, $this->settings->date_format);
		}
		$query = $this->db->placehold("UPDATE __comments SET ?% $date_query WHERE id in(?@) LIMIT 1", $comment, (array)$id);
		$this->db->query($query);
		return $id;
	}

	// Удаление комментария
	public function delete_comment($id)
	{
		if(!empty($id))
		{
			$query = $this->db->placehold("DELETE FROM __comments WHERE id=? LIMIT 1", intval($id));
			$this->db->query($query);
		}
	}	
}
