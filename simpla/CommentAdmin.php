<?PHP

require_once('api/Simpla.php');

class CommentAdmin extends Simpla
{
	public function fetch()
	{
		$comment = new stdClass;
		if($this->request->method('POST'))
		{
			$comment->id = $this->request->post('id', 'integer');
			$comment->name = $this->request->post('name');
			$comment->email = $this->request->post('email');
			
			$comment->type = $this->request->post('type');
			$comment->object_id = $this->request->post('object_id');
			$comment->approved = $this->request->post('approved', 'boolean');

			$comment->text = $this->request->post('text');
			$comment->answer = $this->request->post('answer');
			
			$is_notify = $this->request->post('notify_user');

  			$comment_id = $this->comments->update_comment($comment->id, $comment);
			if ($comment_id and $is_notify and $comment->email and $comment->answer)
				$this->notify->email_comment_user($comment_id);
				
  			$comment = $this->comments->get_comment($comment->id);
			$this->design->assign('message_success', 'updated');
		}
		else
		{
			$comment->id = $this->request->get('id', 'integer');
			$comment = $this->comments->get_comment(intval($comment->id));
		}

		// Выбирает объект, который прокомментирован:
		if($comment->type == 'product')
		{
			$products = array();
			$products_ids = array();
			$products_ids[] = $comment->object_id;
			foreach($this->products->get_products(array('id'=>$products_ids)) as $p)
				$products[$p->id] = $p;
			if(isset($products[$comment->object_id]))
				$comment->product = $products[$comment->object_id];
		}
		
		if($comment->type == 'article')
		{
			$articles = array();
			$articles_ids = array();
			$articles_ids[] = $comment->object_id;
			foreach($this->articles->get_articles(array('id'=>$articles_ids)) as $p)
				$articles[$p->id] = $p;
			if(isset($articles[$comment->object_id]))
				$comment->article = $articles[$comment->object_id];
		}
		
		if($comment->type == 'page')
		{
			$pages = array();
			$pages_ids = array();
			$pages_ids[] = $comment->object_id;
			foreach($this->pages->get_pages(array('id'=>$pages_ids)) as $p)
				$pages[$p->id] = $p;
			if(isset($pages[$comment->object_id]))
				$comment->page = $pages[$comment->object_id];
		}

		if($comment->type == 'blog')
		{
			$posts = array();
			$posts_ids = array();
			$posts_ids[] = $comment->object_id;
			foreach($this->blog->get_posts(array('id'=>$posts_ids)) as $p)
				$posts[$p->id] = $p;
			if(isset($posts[$comment->object_id]))
				$comment->post = $posts[$comment->object_id];
		}
 		
		$this->design->assign('comment', $comment);
		
 	  	return $this->design->fetch('comment.tpl');
	}
}