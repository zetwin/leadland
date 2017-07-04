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

class Instruction extends Simpla
{
	private	$allowed_extentions = array('pdf', 'doc', 'txt', 'xls', 'docx', 'zip', 'rar', 'psd');

	public function __construct()
	{		
		parent::__construct();
	}
	
	
	/**
	 * Создание превью изображения
	 * @param $filename файл с изображением (без пути к файлу)
	 * @param max_w максимальная ширина
	 * @param max_h максимальная высота
	 * @return $string имя файла превью
	 */
	function resizepost($filename)
	{
		list($source_file, $width , $height, $set_watermark) = $this->get_resize_params($filename);

		// Если вайл удаленный (http://), зальем его себе
		if(substr($source_file, 0, 7) == 'http://' || substr($source_file, 0, 8) == 'https://')
		{	
			// Имя оригинального файла
			if(!$original_file = $this->download_instruction($source_file))
				return false;
			
			$resized_file = $this->add_resize_params($original_file, $width, $height, $set_watermark);			
		}	
		else
		{
			$original_file = $source_file;
		}
		
		$resized_file = $this->add_resize_params($original_file, $width, $height, $set_watermark);					
	
		// Пути к папкам с картинками
		$originals_dir = $this->config->root_dir.$this->config->original_instructions_dir;

	}	 
	
	
	function resize($filename)
	{
		list($source_file, $width , $height, $set_watermark) = $this->get_resize_params($filename);

		// Если вайл удаленный (http://), зальем его себе
		if(substr($source_file, 0, 7) == 'http://' || substr($source_file, 0, 8) == 'https://')
		{	
			// Имя оригинального файла
			if(!$original_file = $this->download_instruction($source_file))
				return false;
			
			$resized_file = $this->add_resize_params($original_file, $width, $height, $set_watermark);			
		}	
		else
		{
			$original_file = $source_file;
		}
		
		$resized_file = $this->add_resize_params($original_file, $width, $height, $set_watermark);			
		
	
		// Пути к папкам с картинками
		$originals_dir = $this->config->root_dir.$this->config->original_instructions_dir;
		$preview_dir = $this->config->root_dir.$this->config->resized_instructions_dir;
		
		$watermark_offet_x = $this->settings->watermark_offset_x;
		$watermark_offet_y = $this->settings->watermark_offset_y;
		
		$sharpen = min(100, $this->settings->instructions_sharpen)/100;
		$watermark_transparency =  1-min(100, $this->settings->watermark_transparency)/100;
	
	
		if($set_watermark && is_file($this->config->root_dir.$this->config->watermark_file))
			$watermark = $this->config->root_dir.$this->config->watermark_file;
		else
			$watermark = null;

		if(class_exists('Imagick') && $this->config->use_imagick)
			$this->instruction_constrain_imagick($originals_dir.$original_file, $preview_dir.$resized_file, $width, $height, $watermark, $watermark_offet_x, $watermark_offet_y, $watermark_transparency, $sharpen);
		else
			$this->instruction_constrain_gd($originals_dir.$original_file, $preview_dir.$resized_file, $width, $height, $watermark, $watermark_offet_x, $watermark_offet_y, $watermark_transparency);
		
		return $preview_dir.$resized_file;
	}

	public function add_resize_params($filename, $width=0, $height=0, $set_watermark=false)
	{
		if('.' != ($dirname = pathinfo($filename,  PATHINFO_DIRNAME)))
			$file = $dirname.'/'.pathinfo($filename, PATHINFO_FILENAME);
		else
			$file = pathinfo($filename, PATHINFO_FILENAME);
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
	
		if($width>0 || $height>0)
			$resized_filename = $file.'.'.($width>0?$width:'').'x'.($height>0?$height:'').($set_watermark?'w':'').'.'.$ext;
		else
			$resized_filename = $file.'.'.($set_watermark?'w.':'').$ext;
			
		return $resized_filename;
	}

	public function get_resize_params($filename)
	{
		// Определаяем параметры ресайза
		if(!preg_match('/(.+)\.([0-9]*)x([0-9]*)(w)?\.([^\.]+)$/', $filename, $matches))
			return false;
			
		$file = $matches[1];					// имя запрашиваемого файла
		$width = $matches[2];					// ширина будущего изображения
		$height = $matches[3];					// высота будущего изображения
		$set_watermark = $matches[4] == 'w';	// ставить ли водяной знак
		$ext = $matches[5];						// расширение файла
			
		return array($file.'.'.$ext, $width, $height, $set_watermark);
	}
	
	
	public function download_instruction($filename)
	{
		// Заливаем только есть такой файл есть в базе
		$this->db->query('SELECT 1 FROM __instructions WHERE filename=? LIMIT 1', $filename);
		if(!$this->db->result())
			return false;
		
		// Имя оригинального файла
		$basename = explode('&', pathinfo($filename, PATHINFO_BASENAME));
		$uploaded_file = array_shift($basename);
		$base = urldecode(pathinfo($uploaded_file, PATHINFO_FILENAME));
		$ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);
		
		// Если такой файл существует, нужно придумать другое название
		$new_name = urldecode($uploaded_file);
			
		while(file_exists($this->config->root_dir.$this->config->original_instructions_dir.$new_name))
		{
			$new_base = pathinfo($new_name, PATHINFO_FILENAME);
			if(preg_match('/_([0-9]+)$/', $new_base, $parts))
				$new_name = $base.'_'.($parts[1]+1).'.'.$ext;
			else
				$new_name = $base.'_1.'.$ext;
		}
		$this->db->query('UPDATE __instructions SET filename=?, name=? WHERE filename=?', $new_name, $names, $filename);
		
		// Перед долгим копированием займем это имя
		fclose(fopen($this->config->root_dir.$this->config->original_instructions_dir.$new_name, 'w'));
		copy($filename, $this->config->root_dir.$this->config->original_instructions_dir.$new_name);
		return $new_name;
	}

	public function upload_instruction($filename, $name)
	{
		// Имя оригинального файла
		$name = $this->correct_filename($name);
		$uploaded_file = $new_name = pathinfo($name, PATHINFO_BASENAME);
		$base = pathinfo($uploaded_file, PATHINFO_FILENAME);
		$ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);
		
		if(in_array(strtolower($ext), $this->allowed_extentions))
		{			
			while(file_exists($this->config->root_dir.$this->config->original_instructions_dir.$new_name))
			{	
				$new_base = pathinfo($new_name, PATHINFO_FILENAME);
				if(preg_match('/_([0-9]+)$/', $new_base, $parts))
					$new_name = $base.'_'.($parts[1]+1).'.'.$ext;
				else
					$new_name = $base.'_1.'.$ext;
			}
			if(move_uploaded_file($filename, $this->config->root_dir.$this->config->original_instructions_dir.$new_name))			
				return $new_name;
		}

		return false;
	}
	
	
	
	/**
	 * Создание превью средствами gd
	 * @param $src_file исходный файл
	 * @param $dst_file файл с результатом
	 * @param max_w максимальная ширина
	 * @param max_h максимальная высота
	 * @return bool
	 */
	private function instruction_constrain_gd($src_file, $dst_file, $max_w, $max_h, $watermark=null, $watermark_offet_x=0, $watermark_offet_y=0, $watermark_opacity=1)
	{
		$quality = 100;
	
		// Параметры исходного изображения
		@list($src_w, $src_h, $src_type) = array_values(getinstructionsize($src_file));
		$src_type = instruction_type_to_mime_type($src_type);	
		
		if(empty($src_w) || empty($src_h) || empty($src_type))
			return false;
	
		// Нужно ли обрезать?
		if (!$watermark && ($src_w <= $max_w) && ($src_h <= $max_h))
	    {
			// Нет - просто скопируем файл
			if (!copy($src_file, $dst_file))
				return false;
			return true;
	    }
				
		// Размеры превью при пропорциональном уменьшении
		@list($dst_w, $dst_h) = $this->calc_contrain_size($src_w, $src_h, $max_w, $max_h);
	
		// Читаем изображение
		switch ($src_type)
		{
		case 'instruction/jpeg':	
			$src_img = instructionCreateFromJpeg($src_file);		
			break;
		case 'instruction/gif':
			$src_img = instructionCreateFromGif($src_file);		
			break;
		case 'instruction/png':
			$src_img = instructionCreateFromPng($src_file);					
			instructionalphablending($src_img, true);
			break;
		default:
			return false;
		}
		
		if(empty($src_img))
			return false;
			
		$src_colors = instructioncolorstotal($src_img);
		
		// create destination instruction (indexed, if possible)
		if ($src_colors > 0 && $src_colors <= 256)
			$dst_img = instructioncreate($dst_w, $dst_h);
		else
			$dst_img = instructioncreatetruecolor($dst_w, $dst_h);
		
		if (empty($dst_img))
			return false;
	
		$transparent_index = instructioncolortransparent($src_img);
		if ($transparent_index >= 0 && $transparent_index <= 128)
		{
			$t_c = instructioncolorsforindex($src_img, $transparent_index);
			$transparent_index = instructioncolorallocate($dst_img, $t_c['red'], $t_c['green'], $t_c['blue']);
			if ($transparent_index === false)
				return false;
			if (!instructionfill($dst_img, 0, 0, $transparent_index))
				return false;
			instructioncolortransparent($dst_img, $transparent_index);
	    }
	    // or preserve alpha transparency for png
		elseif ($src_type === 'instruction/png')
	    {
			if (!instructionalphablending($dst_img, false))
				return false;
			$transparency = instructioncolorallocatealpha($dst_img, 0, 0, 0, 127);
			if (false === $transparency)
				return false;
			if (!instructionfill($dst_img, 0, 0, $transparency))
				return false;
			if (!instructionsavealpha($dst_img, true))
				return false;
	    }		
			
	    // resample the instruction with new sizes
		if (!instructioncopyresampled($dst_img, $src_img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h))
			return false;	
			
		// Watermark
		if(!empty($watermark) && is_readable($watermark))
		{	
			$overlay = instructioncreatefrompng($watermark);
			
			// Get the size of overlay 
			$owidth = instructionsx($overlay); 
			$oheight = instructionsy($overlay);
			
			$watermark_x = min(($dst_w-$owidth)*$watermark_offet_x/100, $dst_w); 
			$watermark_y = min(($dst_h-$oheight)*$watermark_offet_y/100, $dst_h); 
	
			instructioncopy($dst_img, $overlay, $watermark_x, $watermark_y, 0, 0, $owidth, $oheight);		
			//instructioncopymerge($dst_img, $overlay, $watermark_x, $watermark_y, 0, 0, $owidth, $oheight, $watermark_opacity*100); 
			
		}	
				
			
		// recalculate quality value for png instruction
		if ('instruction/png' === $src_type)
		{
			$quality = round(($quality / 100) * 10);
			if ($quality < 1)
				$quality = 1;
			elseif ($quality > 10)
				$quality = 10;
			$quality = 10 - $quality;
		}
	
		// Сохраняем изображение
		switch ($src_type)
		{
		case 'instruction/jpeg':	
			return instructionJpeg($dst_img, $dst_file, $quality);
		case 'instruction/gif':
			return instructionGif($dst_img, $dst_file, $quality);
		case 'instruction/png':
			instructionsavealpha($dst_img, true);
			return instructionPng($dst_img, $dst_file, $quality);
		default:
			return false;
		}
	}
	
	/**
	 * Создание превью средствами imagick
	 * @param $src_file исходный файл
	 * @param $dst_file файл с результатом
	 * @param max_w максимальная ширина
	 * @param max_h максимальная высота
	 * @return bool
	 */
	private function instruction_constrain_imagick($src_file, $dst_file, $max_w, $max_h, $watermark=null, $watermark_offet_x=0, $watermark_offet_y=0, $watermark_opacity=1, $sharpen=0.2)
	{
		$thumb = new Imagick();
		
		// Читаем изображение
		if(!$thumb->readinstruction($src_file))
			return false;
		
		// Размеры исходного изображения
		$src_w = $thumb->getinstructionWidth();
		$src_h = $thumb->getinstructionHeight();
		
		// Нужно ли обрезать?
		if (!$watermark && ($src_w <= $max_w) && ($src_h <= $max_h))
	    { 
			// Нет - просто скопируем файл
			if (!copy($src_file, $dst_file))
				return false;
			return true;
	    }	
			
		// Размеры превью при пропорциональном уменьшении
		list($dst_w, $dst_h) = $this->calc_contrain_size($src_w, $src_h, $max_w, $max_h);
	
		// Уменьшаем
		$thumb->thumbnailinstruction($dst_w, $dst_h);
		
		// Устанавливаем водяной знак
		if($watermark && is_readable($watermark))
		{
			$overlay = new Imagick($watermark);
			//$overlay->setinstructionOpacity($watermark_opacity);
			//$overlay_compose = $overlay->getinstructionCompose();
			$overlay->evaluateinstruction(Imagick::EVALUATE_MULTIPLY, $watermark_opacity, Imagick::CHANNEL_ALPHA);
			
			// Get the size of overlay 
			$owidth = $overlay->getinstructionWidth(); 
			$oheight = $overlay->getinstructionHeight();
			
			$watermark_x = min(($dst_w-$owidth)*$watermark_offet_x/100, $dst_w); 
			$watermark_y = min(($dst_h-$oheight)*$watermark_offet_y/100, $dst_h); 
			
		}
		
		
		// Анимированные gif требуют прохода по фреймам
		foreach($thumb as $frame)
		{
			// Уменьшаем
			$frame->thumbnailinstruction($dst_w, $dst_h);
			
	    	/* Set the virtual canvas to correct size */
	    	$frame->setinstructionPage($dst_w, $dst_h, 0, 0);
	    	
			// Наводим резкость
			if($sharpen > 0)		
				$thumb->adaptiveSharpeninstruction($sharpen, $sharpen);
				
			if(isset($overlay) && is_object($overlay))
			{
				// $frame->compositeinstruction($overlay, $overlay_compose, $watermark_x, $watermark_y, imagick::COLOR_ALPHA);
				$frame->compositeinstruction($overlay, imagick::COMPOSITE_OVER, $watermark_x, $watermark_y, imagick::COLOR_ALPHA);
			}
				
		}	
		
		// Убираем комменты и т.п. из картинки
		$thumb->stripinstruction();
		
		//		$thumb->setinstructionCompressionQuality(100);
		
		// Записываем картинку
		if(!$thumb->writeinstructions($dst_file, true))
			return false;
		
		// Уборка
		$thumb->destroy();
		if(isset($overlay) && is_object($overlay))
			$overlay->destroy();
		
		return true;
	}
	
	
	/**
	 * Вычисляет размеры изображения, до которых нужно его пропорционально уменьшить, чтобы вписать в квадрат $max_w x $max_h
	 * @param src_w ширина исходного изображения
	 * @param src_h высота исходного изображения
	 * @param max_w максимальная ширина
	 * @param max_h максимальная высота
	 * @return array(w, h)
	 */
	function calc_contrain_size($src_w, $src_h, $max_w = 0, $max_h = 0)
	{
		if($src_w == 0 || $src_h == 0)
			return false;
			
		$dst_w = $src_w;
		$dst_h = $src_h;
	
		if($src_w > $max_w && $max_w>0)
		{
			$dst_h = $src_h * ($max_w/$src_w);
			$dst_w = $max_w;
		}
		if($dst_h > $max_h && $max_h>0)
		{
			$dst_w = $dst_w * ($max_h/$dst_h);
			$dst_h = $max_h;
		}
		return array($dst_w, $dst_h);
	}	
	
	
	private function files_identical($fn1, $fn2)
	{
		$buffer_len = 1024;
	    if(!$fp1 = fopen(dirname(dirname(__FILE__)).'/'.$fn1, 'rb'))
	        return FALSE;
	
	    if(!$fp2 = fopen($fn2, 'rb')) {
	        fclose($fp1);
	        return FALSE;
	    }
	
	    $same = TRUE;
	    while (!feof($fp1) and !feof($fp2))
	        if(fread($fp1, $buffer_len) !== fread($fp2, $buffer_len)) {
	            $same = FALSE;
	            break;
	        }
	
	    if(feof($fp1) !== feof($fp2))
	        $same = FALSE;
	
	    fclose($fp1);
	    fclose($fp2);
	
	    return $same;
	}

	private function correct_filename($filename)
	{
		$ru = explode('-', "А-а-Б-б-В-в-Ґ-ґ-Г-г-Д-д-Е-е-Ё-ё-Є-є-Ж-ж-З-з-И-и-І-і-Ї-ї-Й-й-К-к-Л-л-М-м-Н-н-О-о-П-п-Р-р-С-с-Т-т-У-у-Ф-ф-Х-х-Ц-ц-Ч-ч-Ш-ш-Щ-щ-Ъ-ъ-Ы-ы-Ь-ь-Э-э-Ю-ю-Я-я"); 
		$en = explode('-', "A-a-B-b-V-v-G-g-G-g-D-d-E-e-E-e-E-e-ZH-zh-Z-z-I-i-I-i-I-i-J-j-K-k-L-l-M-m-N-n-O-o-P-p-R-r-S-s-T-t-U-u-F-f-H-h-TS-ts-CH-ch-SH-sh-SCH-sch---Y-y---E-e-YU-yu-YA-ya");

	 	$res = str_replace($ru, $en, $filename);
		$res = preg_replace("/[\s]+/ui", '-', $res);
		$res = preg_replace("/[^a-zA-Z0-9\.\-\_]+/ui", '', $res);
	 	$res = strtolower($res);
	    return $res;  
	}
	
}