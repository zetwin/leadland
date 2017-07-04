<?PHP
require_once('api/Simpla.php');

class CommentAdmin extends Simpla
{	

	public function fetch()
	{	
		if($this->request->method('post'))
		{
			$comment->id 			= $this->request->post('id', 'intgeger');
			$comment->approved       = $this->request->post('approved', 'boolean');
			$comment->answer   = $this->request->post('answer');
				$get_comment = $this->comments->get_comment($comment->id);
			$comment->text = $get_comment->text;
			
			if($comment->id)
			{
				$this->comments->update_comment($comment->id, $comment);
				$this->design->assign('message_success', 'added');
			}

		}
		else
		{
			$comment_id = $this->request->get('id', 'integer');
			if(!empty($comment_id))
			{
				$comment = $this->comments->get_comment($comment_id);
			}

		}	
		
		$this->design->assign('comment', $comment);

		
  	  	return $this->design->fetch('comment.tpl');
	}
	
}

