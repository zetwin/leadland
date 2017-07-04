{* Вкладки *}
{capture name=tabs}
	<li class="active"><a href="{url module=ArticlesAdmin keyword=null category_id=null brand_id=null filter=null page=null}">Статьи</a></li>
	{if in_array('categories', $manager->permissions)}<li><a href="index.php?module=ArticleCategoriesAdmin">Категории</a></li>{/if}
{/capture}

{* Title *}
{if $category}
	{$meta_title=$category->name scope=parent}
{else}
	{$meta_title='Статьи' scope=parent}
{/if}

{* Поиск *}
<form method="get">
<div id="search">
	<input type="hidden" name="module" value="ArticlesAdmin">
	<input class="search" type="text" name="keyword" value="{$keyword|escape}" />
	<input class="search_button" type="submit" value=""/>
</div>
</form>
	
{* Заголовок *}
<div id="header">	
	{if $articles_count}
		{if $category->name || $brand->name}
			<h1>{$category->name} {$brand->name} ({$articles_count} {$articles_count|plural:'статья':'статтей':'статьи'})</h1>
		{elseif $keyword}
			<h1>{$articles_count|plural:'Найден':'Найдено':'Найдено'} {$articles_count} {$articles_count|plural:'статья':'статтей':'статьи'}</h1>
		{else}
			<h1>{$articles_count} {$articles_count|plural:'статья':'статтей':'статьи'}</h1>
		{/if}		
	{else}
		<h1>Нет статтей</h1>
	{/if}
	<a class="add" href="{url module=ArticleAdmin return=$smarty.server.REQUEST_URI}">Добавить статью</a>
</div>	

<div id="main_list">
	
	<!-- Листалка страниц -->
	{include file='pagination.tpl'}	
	<!-- Листалка страниц (The End) -->

	{* Основная форма *}
	<form id="list_form" method="post">
	<input type="hidden" name="session_id" value="{$smarty.session.id}">
	
	{if $articles}
	
		<div id="list">
		{foreach $articles as $article}
		<div class="{if !$article->visible}invisible{/if} {if $article->featured}featured{/if} row">
			<input type="hidden" name="positions[{$article->id}]" value="{$article->position}">
			<div class="move cell"><div class="move_zone"></div></div>
	 		<div class="checkbox cell">
				<input type="checkbox" name="check[]" value="{$article->id}"/>				
			</div>
			<div class="image cell">
				{$image = $article->images|@first}
				{if $image}
				<a href="{url module=ArticleAdmin id=$article->id return=$smarty.server.REQUEST_URI}"><img src="{$image->filename|escape|resizearticle:35:35}" /></a>
				{/if}
			</div>
			<div class="name article_name cell">
				<a href="{url module=ArticleAdmin id=$article->id return=$smarty.server.REQUEST_URI}">{$article->name|escape}</a>
			</div>
			<div class="icons cell">
				<a class="preview"   title="Предпросмотр в новом окне" href="../article/{$article->url}" target="_blank"></a>			
				<a class="enable"    title="Активен"                 href="#"></a>
				<a class="featured"  title="Рекомендуемый"           href="#"></a>
				<a class="duplicate" title="Дублировать"             href="#"></a>
				<a class="delete"    title="Удалить"                 href="#"></a>
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
				<option value="set_featured">Сделать рекомендуемым</option>
				<option value="unset_featured">Отменить рекомендуемый</option>
				<option value="duplicate">Создать дубликат</option>
				{if $pages_count>1}
				<option value="move_to_page">Переместить на страницу</option>
				{/if}
				{if $categories|count>1}
				<option value="move_to_category">Переместить в категорию</option>
				{/if}
				{if $brands|count>0}
				<option value="move_to_brand">Указать бренд</option>
				{/if}
				<option value="delete">Удалить</option>
				<option value="change_currency">Изменить валюту</option>
			</select>
			</span>
		
			<span id="move_to_page">
			<select name="target_page">
				{section target_page $pages_count}
				<option value="{$smarty.section.target_page.index+1}">{$smarty.section.target_page.index+1}</option>
				{/section}
			</select> 
			</span>
		
			<span id="move_to_category">
			<select name="target_category">
				{function name=category_select level=0}
				{foreach $categories as $category}
						<option value='{$category->id}'>{section sp $level}&nbsp;&nbsp;&nbsp;&nbsp;{/section}{$category->name|escape}</option>
						{category_select categories=$category->subcategories selected_id=$selected_id level=$level+1}
				{/foreach}
				{/function}
				{category_select categories=$categories}
			</select> 
			</span>
			
			<span id="move_to_brand">
			<select name="target_brand">
				<option value="0">Не указан</option>
				{foreach $all_brands as $b}
				<option value="{$b->id}">{$b->name}</option>
				{/foreach}
			</select> 
			</span>
			
			<span id="change_currency">
			<select disabled class="currencies_sel" name="currencies" style="display: none;">
                <option disabled selected value="0">Не менять</option>
                {foreach $currencies as $c}
                    <option value="{$c->id}">{$c->code} ({$c->rate_to/$c->rate_from})</option>
                {/foreach}
			</select>
			</span>
		
			<input id="apply_action" class="button_green" type="submit" value="Применить">		
		</div>
		{/if}
	</form>

	<!-- Листалка страниц -->
	{include file='pagination.tpl'}	
	<!-- Листалка страниц (The End) -->		
</div>


<!-- Меню -->
<div id="right_menu">
	
	<!-- Фильтры -->
	<ul>
		<li {if !$filter}class="selected"{/if}><a href="{url brand_id=null category_id=null keyword=null page=null filter=null}">Все товары</a></li>
		<li {if $filter=='featured'}class="selected"{/if}><a href="{url keyword=null brand_id=null category_id=null page=null filter='featured'}">Рекомендуемые</a></li>
		<li {if $filter=='discounted'}class="selected"{/if}><a href="{url keyword=null brand_id=null category_id=null page=null filter='discounted'}">Со скидкой</a></li>
		<li {if $filter=='visible'}class="selected"{/if}><a href="{url keyword=null brand_id=null category_id=null page=null filter='visible'}">Активные</a></li>
		<li {if $filter=='hidden'}class="selected"{/if}><a href="{url keyword=null brand_id=null category_id=null page=null filter='hidden'}">Неактивные</a></li>
		<li {if $filter=='outofstock'}class="selected"{/if}><a href="{url keyword=null brand_id=null category_id=null page=null filter='outofstock'}">Отсутствующие</a></li>
	</ul>
	<!-- Фильтры -->


	<!-- Категории товаров -->
	{function name=articlecategories_tree}
	{if $articlecategories}
	<ul>
		{if $articlecategories[0]->parent_id == 0}
		<li {if !$articlecategory->id}class="selected"{/if}><a href="{url articlecategory_id=null brand_id=null}">Все категории</a></li>	
		{/if}
		{foreach $articlecategories as $c}
		<li articlecategory_id="{$c->id}" {if $articlecategory->id == $c->id}class="selected"{else}class="droppable articlecategory"{/if}><a href='{url keyword=null brand_id=null page=null articlecategory_id={$c->id}}'>{$c->name}</a></li>
		{articlecategories_tree articlecategories=$c->subarticlecategories}
		{/foreach}
	</ul>
	{/if}
	{/function}
	{articlecategories_tree articlecategories=$articlecategories}
	<!-- Категории товаров (The End)-->
	
</div>
<!-- Меню  (The End) -->


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
	

	// Перенос товара на другую страницу
	$("#action select[name=action]").change(function() {
		if($(this).val() == 'move_to_page')
			$("span#move_to_page").show();
		else
			$("span#move_to_page").hide();
	});
	$("#pagination a.droppable").droppable({
		activeClass: "drop_active",
		hoverClass: "drop_hover",
		tolerance: "pointer",
		drop: function(event, ui){
			$(ui.helper).find('input[type="checkbox"][name*="check"]').attr('checked', true);
			$(ui.draggable).closest("form").find('select[name="action"] option[value=move_to_page]').attr("selected", "selected");		
			$(ui.draggable).closest("form").find('select[name=target_page] option[value='+$(this).html()+']').attr("selected", "selected");
			$(ui.draggable).closest("form").submit();
			return false;	
		}		
	});


	// Перенос товара в другую категорию
	$("#action select[name=action]").change(function() {
		if($(this).val() == 'move_to_category')
			$("span#move_to_category").show();
		else
			$("span#move_to_category").hide();
	});
	$("#right_menu .droppable.category").droppable({
		activeClass: "drop_active",
		hoverClass: "drop_hover",
		tolerance: "pointer",
		drop: function(event, ui){
			$(ui.helper).find('input[type="checkbox"][name*="check"]').attr('checked', true);
			$(ui.draggable).closest("form").find('select[name="action"] option[value=move_to_category]').attr("selected", "selected");	
			$(ui.draggable).closest("form").find('select[name=target_category] option[value='+$(this).attr('category_id')+']').attr("selected", "selected");
			$(ui.draggable).closest("form").submit();
			return false;			
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
	
	// Дублировать товар
	$("a.duplicate").click(function() {
		$('#list input[type="checkbox"][name*="check"]').attr('checked', false);
		$(this).closest("div.row").find('input[type="checkbox"][name*="check"]').attr('checked', true);
		$(this).closest("form").find('select[name="action"] option[value=duplicate]').attr('selected', true);
		$(this).closest("form").submit();
	});
	
	// Показать товар
	$("a.enable").click(function() {
		var icon        = $(this);
		var line        = icon.closest("div.row");
		var id          = line.find('input[type="checkbox"][name*="check"]').val();
		var state       = line.hasClass('invisible')?1:0;
		icon.addClass('loading_icon');
		$.ajax({
			type: 'POST',
			url: 'ajax/update_object.php',
			data: {'object': 'article', 'id': id, 'values': {'visible': state}, 'session_id': '{/literal}{$smarty.session.id}{literal}'},
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

	// Сделать хитом
	$("a.featured").click(function() {
		var icon        = $(this);
		var line        = icon.closest("div.row");
		var id          = line.find('input[type="checkbox"][name*="check"]').val();
		var state       = line.hasClass('featured')?0:1;
		icon.addClass('loading_icon');
		$.ajax({
			type: 'POST',
			url: 'ajax/update_object.php',
			data: {'object': 'article', 'id': id, 'values': {'featured': state}, 'session_id': '{/literal}{$smarty.session.id}{literal}'},
			success: function(data){
				icon.removeClass('loading_icon');
				if(state)
					line.addClass('featured');				
				else
					line.removeClass('featured');
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
	
	
	// Бесконечность на складе
	$("input[name*=stock]").focus(function() {
		if($(this).val() == '∞')
			$(this).val('');
		return false;
	});
	$("input[name*=stock]").blur(function() {
		if($(this).val() == '')
			$(this).val('∞');
	});
});


</script>
{/literal}