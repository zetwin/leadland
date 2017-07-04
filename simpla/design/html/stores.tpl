{* Вкладки *}
{capture name=tabs}
	{if in_array('products', $manager->permissions)}<li><a href="index.php?module=ProductsAdmin">Товары</a></li>{/if}
	{if in_array('categories', $manager->permissions)}<li><a href="index.php?module=CategoriesAdmin">Категории</a></li>{/if}
	{if in_array('brands', $manager->permissions)}<li><a href="index.php?module=BrandsAdmin">Бренды</a></li>{/if}
	{if in_array('features', $manager->permissions)}<li><a href="index.php?module=FeaturesAdmin">Свойства</a></li>{/if}
	<li class="active"><a href="index.php?module=StoresAdmin">Поставщики</a></li>
{/capture}

{* Title *}
{$meta_title='Поставщики' scope=parent}

{* Заголовок *}
<div id="header">
	<h1>Поставщики</h1>
	<a class="add" href="{url module=StoreAdmin return=$smarty.server.REQUEST_URI}">Добавить магазин</a>
</div>	
<!-- Заголовок (The End) -->

{if $stores}
<div id="main_list" class="stores">

	<form id="list_form" method="post">
	<input type="hidden" name="session_id" value="{$smarty.session.id}">
		
		{function name=stores_tree level=0}
		{if $stores}
		<div id="list" class="sortable">
		
			{foreach $stores as $store}
			<div class="{if !$store->visible}invisible{/if} row">		
				<div class="tree_row">
					<input type="hidden" name="positions[{$store->id}]" value="{$store->position}">
					<div class="move cell" style="margin-left:{$level*20}px"><div class="move_zone"></div></div>
			 		<div class="checkbox cell">
						<input type="checkbox" name="check[]" value="{$store->id}" />				
					</div>
					<div class="cell">
						<a href="{url module=StoreAdmin id=$store->id return=$smarty.server.REQUEST_URI}">{$store->name|escape}</a>
						{if $store->www}<span style="color:rgba(0, 0, 0, 0.5);">({$store->www|escape})</span>{/if} 	 			
						<a style="color:rgba(0, 0, 0, 0.5);" href="/simpla/index.php?module=ProductsAdmin&store_id={$store->id}">({$store->products_count})</a>
					</div>
					<div class="icons cell">
						{if $store->www}<a class="preview" title="Предпросмотр в новом окне" href="https://anon.click/?{$store->www}" target="_blank"></a>{/if}
						<a class="enable" title="Активна" href="#"></a>
						<a class="delete" title="Удалить" href="#"></a>
					</div>
					<div class="clear"></div>
				</div>
				{stores_tree stores=$store->substores level=$level+1}
			</div>
			{/foreach}
	
		</div>
		{/if}
		{/function}
		{stores_tree stores=$stores}
		
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
Нет магазинов
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
			data: {'object': 'store', 'id': id, 'values': {'visible': state}, 'session_id': '{/literal}{$smarty.session.id}{literal}'},
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