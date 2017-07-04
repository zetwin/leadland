<?PHP

require_once('api/Simpla.php');

error_reporting(7);


class BannersAdmin extends Simpla
{	
	private $BannerOfPage = 10; //Количество выводимых по умолчанию баннеров
	private $allowed_image_extentions = array('png', 'gif', 'jpg', 'jpeg', 'ico');//Разрешенные расширения файлов для загрузки
	
	
	function fetch()
	{
		/*******
		Вывод групп баннеров
		********************/
		if(!$this->request->get('do') || $this->request->get('do') == 'groups')
		{
			if($this->request->get('action'))//Если есть действие "Добавить/изменить группу"
			{
				if($this->request->get('action') == 'edit' && $this->request->get('id'))//Если действие "изменить" подгружаем данные
				{
					$group = $this->banners->get_group((int)$this->request->get('id'));
				}
				
				if($this->request->post('session_id'))//Если получаем данные POST
				{
					if($this->request->post('name') && trim($this->request->post('name')) != '')
					{
						$group->name = $this->request->post('name');
						if($this->request->get('action') == 'edit' && $this->request->get('id'))
						{
							$this->banners->update_group((int)$this->request->get('id'),array('name'=>$this->request->post('name')));
							$this->design->assign('message_success', 'updated');
						}
						elseif($this->request->get('action') == 'add')
						{
							$this->db->query($this->db->placehold("INSERT INTO __banners_groups SET ?%", array('name'=>$this->request->post('name'))));
							$this->design->assign('message_success', 'added');
						}
					}
					else
					{
						$this->design->assign('message_error', 'empty_name');
					}
				}
				
				$this->design->assign('group', $group);
				$this->design->assign('action', $this->request->get('action'));
				return $this->body = $this->design->fetch('banners.groups.add.edit.tpl');
			}
			
			
			
			if($this->request->post('session_id')) $this->post_update(); //Если отправили данные с действием "удалить"
			
			list($groups,$groups_count) = $this->banners->get_groups();
			foreach($groups as $key=>$value)
			{
				list($banner,$banner_count) = $this->banners->get_banners(ARRAY("BannerOfPage"=>10000,"group"=>$groups[$key]->id));
				$groups[$key]->banner = $banner[0];
				$groups[$key]->banner_count = $banner_count;
			}

			$this->design->assign('groups',$groups);
			return $this->body = $this->design->fetch('banners.groups.tpl');
		}
		
		/*******
		Вывод баннеров
		********************/
		elseif($this->request->get('do') == 'banners')
		{
			if($this->request->get('action')) //Добавление или редактирование баннера
			{
				$categories = $this->categories->get_categories_tree();
				$brands     = $this->brands->get_brands();
				$pages      = $this->pages->get_pages();
				
				//Если была POST отправка данных формы добавления/редактирования
				if($this->request->post('session_id'))
				{
					$banner->name = $this->request->post('name');
					$banner->url = $this->request->post('url');
					$banner->description = $this->request->post('description');
					$banner->show_all_pages = (int)$this->request->post('show_all_pages');
					$banner->visible = (int)$this->request->post('visible');
					
					$banner->categories = implode(",",$this->request->post('categories'));
					$banner->brands = implode(",",$this->request->post('brands'));
					$banner->pages = implode(",",$this->request->post('pages'));
					$banner->id_group = $this->request->get('group');
					
					//Если есть ошибки
					$upload_file = $this->request->files('image');
					if($upload_file['name']=='' AND !$this->request->post('image_exist')) $error = 'not_image';
					//if(empty($banner->url) AND $banner->url=="") $error = 'empty_url';
					if(empty($banner->name) AND $banner->name=="") $error = 'empty_name';
					if($error)$this->design->assign('message_error', $error);
					
					
					
					if(!$error && $this->request->get('action') == "add" && $this->add_banner($banner))//Если добавление баннера
					{
						//Если данные успешно добавлены и успешно загружено изображение баннера, выводим сообщение
						$this->design->assign('message_success', 'added');
						return $this->body = $this->design->fetch('banners.add.edit.tpl');
						
					}elseif(!$error  //Если реактирование баннера
							AND $this->request->get('action') == "edit"
							AND $this->request->get('id')
							AND $this->banners->update_banner($this->request->get('id'),Array(
																						'name'=>$banner->name,
																						'url'=>$banner->url,
																						'description'=>$banner->description,
																						'visible'=>(int)$banner->visible,
																						'show_all_pages'=>$banner->show_all_pages,
																						'categories'=>$banner->categories,
																						'brands'=>$banner->brands,
																						'pages'=>$banner->pages
																					))
							AND $this->upload_image($this->request->get('id'),TRUE))
					{
						//Если данные успешно обновлены, и успешно загружено изображение баннера, выводим сообщение
						$this->design->assign('banners_group', $this->banners->get_group($this->request->get('group')));
						$this->design->assign('message_success', 'updated');
						return $this->body = $this->design->fetch('banners.add.edit.tpl');
					}
					
					$banner->category_selected = $this->request->post('categories');
					$banner->brand_selected = $this->request->post('brands');
					$banner->page_selected = $this->request->post('pages');
				}elseif($this->request->get('action') == "edit" && $this->request->get('id')) // если это редактирование баннера, получаем информацию из БД
				{
					$banner = $this->banners->get_banner($this->request->get('id'));
					$banner->image = $banner->image;
					$banner->category_selected = explode(",",$banner->categories);//Создаем массив категорий
					$banner->brand_selected = explode(",",$banner->brands);//Создаем массив брендов
					$banner->page_selected = explode(",",$banner->pages);//Создаем массив страниц

				}
				
				$this->design->assign('banners_group', $this->banners->get_group($this->request->get('group')));
				$this->design->assign('categories', $categories);
				$this->design->assign('banner',     $banner);
				$this->design->assign('brands',     $brands);
				$this->design->assign('pages',      $pages);
				
				return $this->body = $this->design->fetch('banners.add.edit.tpl');
			}
		
			/*******
			Вывод уже существующих баннеров
			***************/
			
			if($this->request->post('session_id')) $this->post_update(); //Если отправили данные с действием "Включить/выключить/удалить"
			
			list($banners,$banners_count) = $this->banners->get_banners(ARRAY("BannerOfPage"=>10000,"group"=>$this->request->get('group')));
			
			$current_page = max(1, $this->request->get('page', 'integer'));
			$pages_count = (int)($banners_count/$this->BannerOfPage);
			foreach($banners as $key=>$value){
				$banners[$key]->categories_count = ($banners[$key]->categories !=0)?(int)mb_substr_count($banners[$key]->categories,",","UTF-8")+1:0;
				$banners[$key]->brands_count = ($banners[$key]->brands != 0)?(int)mb_substr_count($banners[$key]->brands,",","UTF-8")+1:0;
				$banners[$key]->pages_count = ($banners[$key]->pages!=0)?(int)mb_substr_count($banners[$key]->pages,",","UTF-8")+1:0;
			}
			
			$this->design->assign('banners_group', $this->banners->get_group($this->request->get('group')));
			$this->design->assign('banners_count', $banners_count);
			$this->design->assign('banners_images_dir', $this->config->banners_images_dir);
			$this->design->assign('pages_count', $pages_count);
			$this->design->assign('current_page', $current_page);		
			$this->design->assign('banners', $banners);
			
			return $this->body = $this->design->fetch('banners.show.list.tpl');
		}
	}
	
	
	/****
		Функция добавления баннера
	****/
	
	function add_banner($banner)
	{
		$query = $this->db->placehold("INSERT INTO __banners SET ?%", $banner);
		//exit($query);
		$this->db->query($query);
		
		return $this->upload_image($this->db->insert_id())?true:false;
	}
	
	/****
		Функция загрузки изображения баннера
	****/
	function upload_image($idBanner,$deleteOldImageBanner = FALSE)
	{
		// Загрузка изображения
		$image = $this->request->files('image');
		$image = preg_replace("/\s+/", '_', $this->request->files('image'));
		$imageFilename = "";

		if(isset($image['name']) && in_array(strtolower(pathinfo($image['name'], PATHINFO_EXTENSION)), $this->allowed_image_extentions))
		{
			if($deleteOldImageBanner)
			{
				$banner = $this->banners->get_banner($idBanner);
				$this->banners->delete_image($banner->image);
			}
			$imageFilename = $idBanner.'-'.$image['name'];
			if(!move_uploaded_file($image['tmp_name'], $this->config->root_dir.$this->config->banners_images_dir.$imageFilename))
			{
				$this->design->assign('message_error', 'error_uploading_image');
				return false;
			}
			$query = $this->db->placehold("UPDATE __banners SET position=id, image=? WHERE id=? LIMIT 1", $imageFilename ,$idBanner);
			return $this->db->query($query)?true:false;
		}
		
		return true;
	}
	
	/****
		Функция обновления баннера, а именно: показать/скрыть/удалить баннер
	****/
	function post_update()
	{
		if($this->request->get('do') == 'groups')
		{
			// Действия с выбранными
			$ids = $this->request->post('check');
			if(!empty($ids))
			{
				switch($this->request->post('action'))
				{
					case 'delete':
					{
						foreach($ids as $id)
							$this->banners->delete_group($id);
						break;
					}
				}
			}
		}
		elseif($this->request->get('do') == 'banners')
		{
			// Сортировка
			$positions = $this->request->post('positions'); 
			$ids = array_keys($positions);
			sort($positions);
			
			foreach($positions as $i=>$position)
			{
				$this->banners->update_banner($ids[$i], array('position'=>$position));
			}

			// Действия с выбранными
			$ids = $this->request->post('check');
			if(!empty($ids))
			{
				switch($this->request->post('action'))
				{
					case 'disable':
					{
						$this->banners->update_banner($ids, array('visible'=>0));
						break;
					}
					case 'enable':
					{
						$this->banners->update_banner($ids, array('visible'=>1));
						break;
					}
					case 'delete':
					{
						foreach($ids as $id)
							$this->banners->delete_banner($id);  
						break;
					}
				}
			}
		}
	}
}