{capture name=tabs}
	<li class="active"><a href="index.php?module=ImportAdmin">Импорт</a></li>
	{if in_array('export', $manager->permissions)}<li><a href="index.php?module=ExportAdmin">Экспорт</a></li>{/if}
	{if in_array('backup', $manager->permissions)}<li><a href="index.php?module=BackupAdmin">Бекап</a></li>{/if}
{/capture}
{$meta_title='Импорт товаров' scope=parent}

<script src="{$config->root_url}/simpla/design/js/piecon/piecon.js"></script>
<script>
{if $filename}
	
{literal}
	
	var in_process=false;
	var count=1;

	// On document load
	$(function(){
 		Piecon.setOptions({fallback: 'force'});
 		Piecon.setProgress(0);
    	$("#progressbar").progressbar({ value: 1 });
		in_process=true;
		do_import();	    
	});
  
	function do_import(from)
	{
		from = typeof(from) != 'undefined' ? from : 0;
		$.ajax({
			{/literal}{if $import_articles}url: "ajax/import_articles.php",{else}url: "ajax/import.php",{/if}{literal}
 			 	data: {from:from},
 			 	dataType: 'json',
  				success: function(data){
  					for(var key in data.items)
  					{
							// console.log(data);
							{/literal}{if $import_articles}{literal}
							$('ul#import_result').prepend('<li><span class=count>'+count+'</span> <span title='+data.items[key].status+' class="status '+data.items[key].status+'"></span> <a target=_blank href="index.php?module=ArticleAdmin&id='+data.items[key].article.id+'">'+data.items[key].article.name+'</a></li>');
							{/literal}{else}{literal}
							$('ul#import_result').prepend('<li><span class=count>'+count+'</span> <span title='+data.items[key].status+' class="status '+data.items[key].status+'"></span> <a target=_blank href="index.php?module=ProductAdmin&id='+data.items[key].product.id+'">'+data.items[key].product.name+'</a> '+data.items[key].variant.name+'</li>');
							{/literal}{/if}{literal}
    					
    					count++;
    				}

    				Piecon.setProgress(Math.round(100*data.from/data.totalsize));
   					$("#progressbar").progressbar({ value: 100*data.from/data.totalsize });
  				
    				if(data != false && !data.end)
    				{
    					do_import(data.from);
    				}
    				else
    				{
    					Piecon.setProgress(100);
    					$("#progressbar").hide('fast');
    					in_process = false;
    				}
  				},
				error: function(xhr, status, errorThrown) {
					alert(errorThrown+'\n'+xhr.responseText);
        		}  				
		});
	
	} 
{/literal}
{/if}
</script>

<style>
	.ui-progressbar-value { background-color:#b4defc; background-image: url(design/images/progress.gif); background-position:left; border-color: #009ae2;}
	#progressbar{ clear: both; height:29px;}
	#result{ clear: both; width:100%;}
</style>

{if $message_error}
<!-- Системное сообщение -->
<div class="message message_error">
	<span class="text">
	{if $message_error == 'no_permission'}Установите права на запись в папку {$import_files_dir}
	{elseif $message_error == 'convert_error'}Не получилось сконвертировать файл в кодировку UTF8
	{elseif $message_error == 'locale_error'}На сервере не установлена локаль {$locale}, импорт может работать некорректно
	{else}{$message_error}{/if}
	</span>
</div>
<!-- Системное сообщение (The End)-->
{/if}

	{if $message_error != 'no_permission'}
	
	{if $filename}
	<div>
		<h1>Импорт {if $import_articles}статтей {/if}{$filename|escape}</h1>
	</div>
	<div id='progressbar'></div>
	<ul id='import_result'></ul>
	{else}
	
	
	<div id="column_left">
		
			<h1>Импорт товаров</h1>

			<div class="block">	
				<form method=post id=product enctype="multipart/form-data">
				<div class="block layer">
					<input type=hidden name="session_id" value="{$smarty.session.id}">
					<input name="file" class="import_file" type="file" value="" />
					</div>
					<input class="button_green" type="submit" name="" value="Загрузить" />
					

			
				</form>
			
		</div>
	</div>
		
		
		<div id="column_right">

		<h1>Импорт статтей</h1>
			<div class="block">	
			<form method=post id=product enctype="multipart/form-data">
						<div class="block layer">
				<input type=hidden name="session_id" value="{$smarty.session.id}">
				<input type=hidden name="import_articles" value="1">
				<input name="file" class="import_file" type="file" value="" />
				</div>
				<input class="button_green" type="submit" name="" value="Загрузить" />
			</form>	
		</div>	
		</div>	
		
		<p>(максимальный размер файла &mdash; {if $config->max_upload_filesize>1024*1024}{$config->max_upload_filesize/1024/1024|round:'2'} МБ{else}{$config->max_upload_filesize/1024|round:'2'} КБ{/if})</p>
	
		<div class="block block_help">
		<p>
			Создайте бекап на случай неудачного импорта. 
		</p>
		<p>
			Сохраните таблицу в формате CSV
		</p>
		<p>
			В первой строке таблицы должны быть указаны названия колонок в таком формате:
	
			<ul>
				<li><label>Товар</label> название товара</li>
				<li><label>Категория</label> категория товара</li>
				<li><label>Бренд</label> бренд товара</li>
				<li><label>Вариант</label> название варианта</li>
				<li><label>Цена</label> цена товара</li>
				<li><label>Старая цена</label> старая цена товара</li>
				<li><label>Склад</label> количество товара на складе</li>
				<li><label>Артикул</label> артикул товара</li>
				<li><label>Видим</label> отображение товара на сайте (0 или 1)</li>
				<li><label>Рекомендуемый</label> является ли товар рекомендуемым (0 или 1)</li>
				<li><label>Аннотация</label> краткое описание товара</li>
				<li><label>Адрес</label> адрес страницы товара</li>
				<li><label>Описание</label> полное описание товара</li>
				<li><label>Изображения</label> имена локальных файлов или url изображений в интернете, через запятую</li>
				<li><label>Заголовок страницы</label> заголовок страницы товара (Meta title)</li>
				<li><label>Ключевые слова</label> ключевые слова (Meta keywords)</li>
				<li><label>Описание страницы</label> описание страницы товара (Meta description)</li>
			</ul>
		</p>
		<p>
			Любое другое название колонки трактуется как название свойства товара
		</p>
		<p>
			<a href='files/import/example.csv'>Скачать пример файла</a>
		</p>
		</div>		
	
	{/if}


{/if}