{* Вкладки *}
{capture name=tabs}
	<li><a href="index.php?module=BannersAdmin&do=groups">Группы баннеров</a></li>
	<li><a href="{$smarty.get.return}">Группа » {$banners_group->name}</a></li>
	<li class="active"><a href="{$smarty.server.REQUEST_URI}">{if $banner->image}Изменить баннер » "{$banner->name}"{else}Добавить баннер{/if}</a></li>
{/capture}

{* Title *}
{$meta_title='Добавить баннер' scope=parent}

	<!-- Листалка страниц -->
	{include file='pagination.tpl'}	
	<!-- Листалка страниц (The End) -->

	{* Заголовок *}
	<div id="header">	
		<h1>{if $banner->image}Изменить баннер "{$banner->name}"{else}Добавить баннер{/if}</h1>{if $smarty.get.return and !$message_success and !$message_error}<a class="button_yellow" href="{$smarty.get.return}">Вернуться</a>{/if}
	</div>

	<link href="design/css/banners.css" rel="stylesheet" type="text/css" />
	
	{if $message_success}
	<!-- Системное сообщение -->
	<div class="message message_success">
		<span>{if $message_success=='added'}Баннер добавлен{elseif $message_success=='updated'}Баннер изменен{else}{$message_success|escape}{/if}</span>
		{if $smarty.get.return}
		<a class="button" href="{$smarty.get.return}">Вернуться</a>
		{/if}
	</div>
	<!-- Системное сообщение (The End)-->
	{/if}

	{if $message_error}
	<!-- Системное сообщение -->
	<div class="message message_error">
		<span>{if $message_error=='error_uploading_image'}Ошибка загрузки изображения баннера{elseif $message_error=='empty_name'}Введите название баннера{elseif $message_error=='not_image'}Вы не указалии изображение баннера{elseif $message_error=='empty_url'}Вы не указали URL страницы на которую должен ссылаться баннер{else}{$message_error|escape}{/if}</span>
	</div>
	<!-- Системное сообщение (The End)-->
	{/if}
	
	{if !$message_success}
	{* Основная форма *}
	<form method=post id=product enctype="multipart/form-data">
	<input type=hidden name="session_id" value="{$smarty.session.id}">
	{if $banner->image}<input type=hidden name="image_exist" value="yes">{/if}
		
		<table><tr><td valign="top" style="padding:5px;">
			<div class="checkbox">
				<input name="visible" value="1" type="checkbox" id="active_checkbox"  {if $banner->visible}checked{/if}/> <label for="active_checkbox">Активен</label>
			</div>
			<div class="block layer">
				<ul>
					<li><label class=property>Название</label>						<input type="text" class="simpla_inp" value="{$banner->name}" name="name"/></li>
					<li><label class=property>Ссылка</label>						<input type="text" class="simpla_inp" value="{$banner->url}" name="url"/></li>
					<li><label class=property>Изображение баннера</label>			<input type="file" class="simpla_inp" value="" name="image"" id="imageFile"/></li>
					<li><label class=property>Описание</label>						<textarea class="simpla_inp" name="description"/>{$banner->description}</textarea></li>
				</ul>
			</div>
		</td><td id="imageThumb">{if $banner->image}<img src="/{$config->banners_images_dir}{$banner->image}" alt="">{else}БАННЕР<span>изображение отсутсвует</span>{/if}</td></tr></table>
		<br />
		<div class="block layer">
			<table><tr><td valign="top"  style="padding:5px;">
				<h2>Баннер отображать на:</h2>				
				<input name="show_all_pages" value="1" type="checkbox" {if $banner->show_all_pages}checked{/if} id="show_all_pages"/> <label for="show_all_pages" class="property" style="display:inline;float:none;color:black;">Показывать на всех страницах сайта</label>
				<br/><br/>
				<ul>
					<li>
						<label class=property>На страницах:</label>
						<select name="pages[]" multiple="multiple" size="10" style="width:450px;height:150px;">
							<option value='0' {if !$banner->page_selected OR 0|in_array:$banner->page_selected}selected{/if}>Не показывать на страницах</option>
							{foreach from=$pages item=page}
								{if $page->name != ''}<option value='{$page->id}' {if $banner->page_selected AND $page->id|in_array:$banner->page_selected}selected{/if}>{$page->name|escape}</option>{/if}
							{/foreach}
						</select>
					</li>
					<li>
						<label class=property>В брендах:</label>
						<select name="brands[]" multiple="multiple" size="10" style="width:450px;height:150px;">
							<option value='0' {if !$banner->brand_selected OR 0|in_array:$banner->brand_selected}selected{/if}>Не показывать в брендах</option>
							{foreach from=$brands item=brand}
								<option value='{$brand->id}' {if $banner->brand_selected AND $brand->id|in_array:$banner->brand_selected}selected{/if}>{$brand->name|escape}</option>
							{/foreach}
						</select>
					</li>
				</ul>
			</td><td valign="top" style="padding-top:75px; width:50%">
				<ul><li><label class=property>В категориях:</label>
				<select name="categories[]" multiple="multiple" size="10" style="width:450px;height:325px;">
					<option value='0' {if !$banner->category_selected OR 0|in_array:$banner->category_selected}selected{/if}>Не показывать в категориях</option>
					{function name=category_select level=0}
						{foreach from=$categories item=category}
								<option value='{$category->id}' {if $selected AND $category->id|in_array:$selected}selected{/if}>{section name=sp loop=$level}&nbsp;&nbsp;&nbsp;&nbsp;{/section}{$category->name|escape}</option>
								{category_select categories=$category->subcategories selected=$banner->category_selected  level=$level+1}
						{/foreach}
					{/function}
					{category_select categories=$categories selected=$banner->category_selected}
				</select></li></ul>
			</td></tr></table>
		</div>
		<input class="button_green button_save" type="submit" name="" value="Сохранить" />
	</form>
	{literal}
	<script>
	  function handleFileSelect(evt) {
		var files = evt.target.files; // FileList object

		// Loop through the FileList and render image files as thumbnails.
		for (var i = 0, f; f = files[i]; i++) {

		  // Only process image files.
		  if (!f.type.match('image.*')) {
			continue;
		  }

		  var reader = new FileReader();

		  // Closure to capture the file information.
		  reader.onload = (function(theFile) {
			return function(e) {
			  // Render thumbnail.
			  $("#imageThumb").html('<img valign="absmiddle" src="'+e.target.result+'" title="'+escape(theFile.name)+'"/>');
			};
		  })(f);

		  // Read in the image file as a data URL.
		  reader.readAsDataURL(f);
		}
	  }

	  document.getElementById('imageFile').addEventListener('change', handleFileSelect, false);
	</script>
	{/literal}
	{/if}