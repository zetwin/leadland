{* Вкладки *}
{capture name=tabs}
	<li class="active"><a href="index.php?module=BannersAdmin&do=groups">Группы баннеров</a></li>
{/capture}

{* Title *}
{$meta_title='Управление баннерами сайта' scope=parent}

<link href="design/css/banners.css" rel="stylesheet" type="text/css" />
	
	<div id="header">
		{if !$function}
			<a class="add" href="{url module=BannersAdmin do=groups action=add return=$smarty.server.REQUEST_URI}">Создать группу баннеров</a><br/><br/><br/>
		{/if}
	</div>
	
	{* Основная форма *}
	{if $groups}
	<form id="list_form" method="post">
		<input type="hidden" name="session_id" value="{$smarty.session.id}">
	
		<div id="list">
		{foreach $groups as $group}
			<div class="row">
				<div class="checkbox cell">
					<input type="checkbox" name="check[]" value="{$group->id}"/>
				</div>
				
				<div class="cell group">
						<div class="banner_wrapper" style="cursor:pointer;" onclick="javascript:location.href='{url module=BannersAdmin do=banners group=$group->id return=$smarty.server.REQUEST_URI}';">
							<div class="group title">
								<a href="{url module=BannersAdmin do=banners group=$group->id return=$smarty.server.REQUEST_URI}">{$group->name|escape}</a>
								<span>Для отображения группы баннеров используйте вызов в шаблоне <span style="margin;0;padding:0;color:#000000">{literal}{get_banners group={/literal}{$group->id}}{literal}{if $banners_id{/literal}{$group->id}}...{literal}{/if}{/literal}</span></span>
							</div>
							{if $group->banner}
							<!--<div class="banner">
								<img src="/{$config->banners_images_dir}{$group->banner->image}" alt="">
							</div>-->
							{/if}
							<div class="tip">
									{if !$group->banner_count}<span style="color:#b61919">{/if}В группе находится: {$group->banner_count} {$group->banner_count|plural:'баннер':'баннеров':'баннера'}{if !$group->banner_count}</span>{/if}<br>
									{if $group->banner}
										{$img_url=$config->root_url|cat:'/'|cat:$config->banners_images_dir|cat:$group->banner->image}{assign var="info" value=$img_url|getimagesize}
										размер изображений баннеров:{$info.0}px X {$info.1}px
									{/if}
							</div>
						</div>
				</div>
				<div class="icons cell">
					<a class="edit" title="Изменить название группы" href="{url module=BannersAdmin do=groups action=edit id=$group->id return=$smarty.server.REQUEST_URI}"></a>
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

	// Удалить группу
	$("a.delete").click(function() {
		$('#list input[type="checkbox"][name*="check"]').attr('checked', false);
		$(this).closest("div.row").find('input[type="checkbox"][name*="check"]').attr('checked', true);
		$(this).closest("form").find('select[name="action"] option[value=delete]').attr('selected', true);
		$(this).closest("form").submit();
	});
	
	// Подтверждение удаления
	$("form").submit(function() {
		if($('select[name="action"]').val()=='delete' && !confirm('Подтвердите удаление'))
			return false;	
	});
});

</script>
{/literal}