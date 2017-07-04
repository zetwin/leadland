<?PHP
require_once('api/Simpla.php');

class ExportAdmin extends Simpla
{	
	private $export_files_dir = 'simpla/files/export/';

	public function fetch()
	{
		$categories = $this->categories->get_categories_tree();
		$this->design->assign('categories', $categories);
		$this->design->assign('export_files_dir', $this->export_files_dir);
		if(!is_writable($this->export_files_dir))
			$this->design->assign('message_error', 'no_permission');
			
					// Обработка действия
		if($this->request->method('post') && $this->request->post('xml_export'))
		{
			$file_name = 'facebook.xml';
			// $file_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$file_name;
			$file_url = "http://$_SERVER[HTTP_HOST]/$file_name";

			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary"); 
			header("Content-disposition: attachment; filename=\"".$file_name."\""); 
			readfile($file_url);
			exit;			
		}
	return $this->design->fetch('export.tpl');
	}
}

