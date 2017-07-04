{* Вкладки *}
{capture name=tabs}
	<li><a href="index.php?module=BannersAdmin&do=groups">Группы баннеров</a></li><li class="active"><a href="index.php?module=BannersAdmin&do=banners">Группа » {$banners_group->name}</a></li>
{/capture}

{* Title *}
{$meta_title='Группа » '|cat:$banners_group->name|cat:' « Управление баннерами сайта' scope=parent}

	<!-- Листалка страниц -->
	{include file='pagination.tpl'}	
	<!-- Листалка страниц (The End) -->
	
	{* Заголовок *}
	<div id="header">
		{if !$function}
			<a class="add" href="{url module=BannersAdmin action=add return=$smarty.server.REQUEST_URI}">Добавить баннер</a><br/><br/><br/>
		{/if}
		{if $banners_count}
				<h1>В группе {$banners_count} {$banners_count|plural:'баннер':'баннеров':'баннера'}</h1>{if $smarty.get.return and !$message_success and !$message_error}<a class="button_yellow" href="{$smarty.get.return}">Вернуться</a>{/if}
		{else}
			<h1>Нет баннеров</h1>
		{/if}
	</div>
	
	<link href="design/css/banners.css" rel="stylesheet" type="text/css" />
	
	{* Основная форма *}
	{if $banners}
	<form id="list_form" method="post">
		<input type="hidden" name="session_id" value="{$smarty.session.id}">
	
		<div id="list">
		{foreach $banners as $banner}
		<div class="{if !$banner->visible}invisible{/if} row">
			<input type="hidden" name="positions[{$banner->id}]" value="{$banner->position}">
			<div class="move cell"><div class="move_zone"></div></div>
	 		<div class="checkbox cell">
				<input type="checkbox" name="check[]" value="{$banner->id}"/>
			</div>
			
			<div class="cell banner">
				<div class="banner_wrapper" style="cursor:pointer;" onclick="javascript:location.href='{url module=BannersAdmin action=edit id=$banner->id return=$smarty.server.REQUEST_URI}';">
					<div class="title">
						<a href="{url module=BannersAdmin action=edit id=$banner->id return=$smarty.server.REQUEST_URI}">
						{$banner->name|escape}
						</a>
					</div>
					<div class="banner">
						{if $banner->image}
						<img src="/{$banners_images_dir}{$banner->image}" alt="">
						{/if}
					</div>
					{if $banner->image}
					<div class="tip">
						Размер изображения: {$img_url=$config->root_url|cat:'/'|cat:$config->banners_images_dir|cat:$banner->image}
						{assign var="info" value=$img_url|getimagesize}
						{$info.0}px X {$info.1}px<br />
						Ссылается на страницу: <a href="{$banner->url}" title="">{$banner->url}</a>
						{if $banner->show_all_pages}<br/><span style="font-family:Arial;font-weight:bold;color:green;">Отображается на всех страницах сайта</span>
						{else}{if $banner->pages_count OR $banner->categories_count OR $banner->brands_count}Отображается:{/if}
							{if $banner->pages_count}на {$banner->pages_count} {$banner->pages_count|plural:'странице':'страницах':'страницах'}, {/if}
							{if $banner->categories_count}в {$banner->categories_count} {$banner->categories_count|plural:'категории':'категориях':'категориях'}, {/if}
							{if $banner->brands_count}в {$banner->brands_count} {$banner->brands_count|plural:'бренде':'брендах':'брендах'}{/if}
							{if !$banner->pages_count AND !$banner->categories_count AND !$banner->brands_count}<br/><span style="font-family:Arial;font-weight:bold;color:red">не отображается</span>{/if}
						{/if}
					</div>
					{/if}
				</div>
			</div>
			<div class="icons cell">
				<a class="enable" {if $banner->visible == 1}title="Активен{else}Выключен{/if}" href="#"></a>
				<a class="delete" title="Удалить" href="#"></a>
			</div>
			<div class="clear"></div>
		</div>
		{/foreach}
		</div>

		<div id="action">
			<label id="check_all" class="dash_link">Выбрать все</label>
		
			<span id="select">
			<select name="action">
				<option value="enable">Сделать видимыми</option>
				<option value="disable">Сделать невидимыми</option>
				<option value="delete">Удалить</option>
			</select>
			</span>
			
			<input id="apply_action" class="button_green" type="submit" value="Применить">		
		</div>
	</form>{/if}
	
	<!-- Листалка страниц -->
	{include file='pagination.tpl'}	
	<!-- Листалка страниц (The End) -->
	
	
	
	
	
{* On document load *}
{literal}
<script>

$(function() {

	// Сортировка списка
	$("#list").sortable({
		items:             ".row",
		tolerance:         "pointer",
		handle:            ".move_zone",
		scrollSensitivity: 40,
		opacity:           0.7, 
		
		helper: function(event, ui){		
			if($('input[type="checkbox"][name*="check"]:checked').size()<1) return ui;
			var helper = $('<div/>');
			$('input[type="checkbox"][name*="check"]:checked').each(function(){
				var item = $(this).closest('.row');
				helper.height(helper.height()+item.innerHeight());
				if(item[0]!=ui[0]) {
					helper.append(item.clone());
					$(this).closest('.row').remove();
				}
				else {
					helper.append(ui.clone());
					item.find('input[type="checkbox"][name*="check"]').attr('checked', false);
				}
			});
			return helper;			
		},	
 		start: function(event, ui) {
  			if(ui.helper.children('.row').size()>0)
				$('.ui-sortable-placeholder').height(ui.helper.height());
		},
		beforeStop:function(event, ui){
			if(ui.helper.children('.row').size()>0){
				ui.helper.children('.row').each(function(){
					$(this).insertBefore(ui.item);
				});
				ui.item.remove();
			}
		},
		update:function(event, ui)
		{
			$("#list_form input[name*='check']").attr('checked', false);
			$("#list_form").ajaxSubmit(function() {
				colorize();
			});
		}
	});

	// Раскраска строк
	function colorize()
	{
		$("#list div.row:even").addClass('even');
		$("#list div.row:odd").removeClass('even');
	}
	// Раскрасить строки сразу
	colorize();

	// Выделить все
	$("#check_all").click(function() {
		$('#list input[type="checkbox"][name*="check"]').attr('checked', $('#list input[type="checkbox"][name*="check"]:not(:checked)').length>0);
	});	

	// Удалить товар
	$("a.delete").click(function() {
		$('#list input[type="checkbox"][name*="check"]').attr('checked', false);
		$(this).closest("div.row").find('input[type="checkbox"][name*="check"]').attr('checked', true);
		$(this).closest("form").find('select[name="action"] option[value=delete]').attr('selected', true);
		$(this).closest("form").submit();
	});
	
	// Показать баннер
	$("a.enable").click(function() {
		var icon        = $(this);
		var line        = icon.closest("div.row");
		var id          = line.find('input[type="checkbox"][name*="check"]').val();
		var state       = line.hasClass('invisible')?1:0;
		icon.addClass('loading_icon');
		
		$.ajax({
			type: 'POST',
			url: 'ajax/update_object.php',
			data: {'object': 'banner', 'id': id, 'values': {'visible': state}, 'session_id': '{/literal}{$smarty.session.id}{literal}'},
			success: function(data){
				icon.removeClass('loading_icon');
				if(state)
					line.removeClass('invisible');
				else
					line.addClass('invisible');				
			},
			dataType: 'json'
		});	
		return false;	
	});


	// Подтверждение удаления
	$("form").submit(function() {
		if($('select[name="action"]').val()=='delete' && !confirm('Подтвердите удаление'))
			return false;	
	});
});

</script>
{/literal}