{* Вкладки *}
{capture name=tabs}
	{if in_array('products', $manager->permissions)}<li><a href="index.php?module=ArticlesAdmin">Статьи</a></li>{/if}
	<li class="active"><a href="index.php?module=ArticleCategoriesAdmin">Категории</a></li>
{/capture}

{* Title *}
{$meta_title='Категории статтей' scope=parent}

{* Заголовок *}
<div id="header">
	<h1>Категории статтей</h1>
	<a class="add" href="{url module=ArticleCategoryAdmin return=$smarty.server.REQUEST_URI}">Добавить категорию</a>
</div>	
<!-- Заголовок (The End) -->

{if $articlecategories}
<div id="main_list" class="categories">

	<form id="list_form" method="post">
	<input type="hidden" name="session_id" value="{$smarty.session.id}">
		
		{function name=articlecategories_tree level=0}
		{if $articlecategories}
		<div id="list" class="sortable">
		
			{foreach $articlecategories as $articlecategory}
			<div class="{if !$articlecategory->visible}invisible{/if} row">		
				<div class="tree_row">
					<input type="hidden" name="positions[{$articlecategory->id}]" value="{$articlecategory->position}">
					<div class="move cell" style="margin-left:{$level*20}px"><div class="move_zone"></div></div>
			 		<div class="checkbox cell">
						<input type="checkbox" name="check[]" value="{$articlecategory->id}" />				
					</div>
					<div class="cell">
						<a href="{url module=ArticleCategoryAdmin id=$articlecategory->id return=$smarty.server.REQUEST_URI}">{$articlecategory->name|escape}</a> 	 			
					</div>
					<div class="icons cell">
						<a class="preview" title="Предпросмотр в новом окне" href="../articles/{$articlecategory->url}" target="_blank"></a>				
						<a class="enable" title="Активна" href="#"></a>
						<a class="delete" title="Удалить" href="#"></a>
					</div>
					<div class="clear"></div>
				</div>
				{articlecategories_tree articlecategories=$articlecategory->subarticlecategories level=$level+1}
			</div>
			{/foreach}
	
		</div>
		{/if}
		{/function}
		{articlecategories_tree articlecategories=$articlecategories}
		
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
	
	</form>
</div>
{else}
Нет категорий
{/if}

{literal}
<script>
$(function() {

	// Сортировка списка
	$(".sortable").sortable({
		items:".row",
		handle: ".move_zone",
		tolerance:"pointer",
		scrollSensitivity:40,
		opacity:0.7, 
		axis: "y",
		update:function()
		{
			$("#list_form input[name*='check']").attr('checked', false);
			$("#list_form").ajaxSubmit();
		}
	});
 
	// Выделить все
	$("#check_all").click(function() {
		$('#list input[type="checkbox"][name*="check"]:not(:disabled)').attr('checked', $('#list input[type="checkbox"][name*="check"]:not(:disabled):not(:checked)').length>0);
	});	

	// Показать категорию
	$("a.enable").click(function() {
		var icon        = $(this);
		var line        = icon.closest(".row");
		var id          = line.find('input[type="checkbox"][name*="check"]').val();
		var state       = line.hasClass('invisible')?1:0;
		icon.addClass('loading_icon');
		$.ajax({
			type: 'POST',
			url: 'ajax/update_object.php',
			data: {'object': 'articlecategory', 'id': id, 'values': {'visible': state}, 'session_id': '{/literal}{$smarty.session.id}{literal}'},
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

	// Удалить 
	$("a.delete").click(function() {
		$('#list input[type="checkbox"][name*="check"]').attr('checked', false);
		$(this).closest("div.row").find('input[type="checkbox"][name*="check"]:first').attr('checked', true);
		$(this).closest("form").find('select[name="action"] option[value=delete]').attr('selected', true);
		$(this).closest("form").submit();
	});

	
	// Подтвердить удаление
	$("form").submit(function() {
		if($('select[name="action"]').val()=='delete' && !confirm('Подтвердите удаление'))
			return false;	
	});

});
</script>
{/literal}