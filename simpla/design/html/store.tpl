{* Вкладки *}
{capture name=tabs}
	{if in_array('products', $manager->permissions)}<li><a href="index.php?module=ProductsAdmin">Товары</a></li>{/if}
	{if in_array('categories', $manager->permissions)}<li><a href="index.php?module=CategoriesAdmin">Категории</a></li>{/if}
	{if in_array('brands', $manager->permissions)}<li><a href="index.php?module=BrandsAdmin">Бренды</a></li>{/if}
	{if in_array('features', $manager->permissions)}<li><a href="index.php?module=FeaturesAdmin">Свойства</a></li>{/if}
	<li class="active"><a href="index.php?module=StoresAdmin">Поставщики</a></li>
{/capture}

{if $store->id}
{$meta_title = $store->name scope=parent}
{else}
{$meta_title = 'Новый магазин' scope=parent}
{/if}

{* Подключаем Tiny MCE *}
{include file='tinymce_init.tpl'}

{* On document load *}
{literal}
<script src="design/js/jquery/jquery.js"></script>
<script src="design/js/jquery/jquery-ui.min.js"></script>
<script src="design/js/autocomplete/jquery.autocomplete-min.js"></script>
<style>
.autocomplete-w1 { background:url(img/shadow.png) no-repeat bottom right; position:absolute; top:0px; left:0px; margin:6px 0 0 6px; /* IE6 fix: */ _background:none; _margin:1px 0 0 0; }
.autocomplete { border:1px solid #999; background:#FFF; cursor:default; text-align:left; overflow-x:auto; min-width: 300px; overflow-y: auto; margin:-6px 6px 6px -6px; /* IE6 specific: */ _height:350px;  _margin:0; _overflow-x:hidden; }
.autocomplete .selected { background:#F0F0F0; }
.autocomplete div { padding:2px 5px; white-space:nowrap; }
.autocomplete strong { font-weight:normal; color:#3399FF; }
</style>

<script>
$(function() {


	// Удаление изображений
	$(".images a.delete").click( function() {
		$("input[name='delete_image']").val('1');
		$(this).closest("ul").fadeOut(200, function() { $(this).remove(); });
		return false;
	});

	// Автозаполнение мета-тегов
	meta_title_touched = true;
	meta_keywords_touched = true;
	meta_description_touched = true;
	url_touched = true;
	
	if($('input[name="meta_title"]').val() == generate_meta_title() || $('input[name="meta_title"]').val() == '')
		meta_title_touched = false;
	if($('input[name="meta_keywords"]').val() == generate_meta_keywords() || $('input[name="meta_keywords"]').val() == '')
		meta_keywords_touched = false;
	if($('textarea[name="meta_description"]').val() == generate_meta_description() || $('textarea[name="meta_description"]').val() == '')
		meta_description_touched = false;
	if($('input[name="url"]').val() == generate_url() || $('input[name="url"]').val() == '')
		url_touched = false;
		
	$('input[name="meta_title"]').change(function() { meta_title_touched = true; });
	$('input[name="meta_keywords"]').change(function() { meta_keywords_touched = true; });
	$('textarea[name="meta_description"]').change(function() { meta_description_touched = true; });
	$('input[name="url"]').change(function() { url_touched = true; });
	
	$('input[name="name"]').keyup(function() { set_meta(); });
	  
});

function set_meta()
{
	if(!meta_title_touched)
		$('input[name="meta_title"]').val(generate_meta_title());
	if(!meta_keywords_touched)
		$('input[name="meta_keywords"]').val(generate_meta_keywords());
	if(!meta_description_touched)
		$('textarea[name="meta_description"]').val(generate_meta_description());
	if(!url_touched)
		$('input[name="url"]').val(generate_url());
}

function generate_meta_title()
{
	name = $('input[name="name"]').val();
	return name;
}

function generate_meta_keywords()
{
	name = $('input[name="name"]').val();
	return name;
}

function generate_meta_description()
{
	if( typeof tinymce != "undefined" )
	{
		return myCustomGetContent( "description" );
	}
	else
		return $('textarea[name=description]').val().replace(/(<([^>]+)>)/ig," ").replace(/(\&nbsp;)/ig," ").replace(/^\s+|\s+$/g, '').substr(0, 512);
}

	//Добавление нового телефона
$('.phones .add').live('click', function() {
  // $($(this).prev()).clone().val('').after(' <span class="delete"></span>').insertAfter($(this).parent().find('span:last'));
	$(".phones li:last").clone(true).appendTo(".phones").find( ".add" ).css( "display", "none" );
	$(".phones li:last").find( ".delete" ).show();
	
});
//Удаление значения свойства
$('.phones .delete').live('click', function() {
	$(this).closest(".phones li").remove();
});

function generate_url()
{
	url = $('input[name="name"]').val();
	url = url.replace(/[\s]+/gi, '-');
	url = translit(url);
	url = url.replace(/[^0-9a-z_\-]+/gi, '').toLowerCase();	
	return url;
}

function translit(str)
{
	var ru=("А-а-Б-б-В-в-Ґ-ґ-Г-г-Д-д-Е-е-Ё-ё-Є-є-Ж-ж-З-з-И-и-І-і-Ї-ї-Й-й-К-к-Л-л-М-м-Н-н-О-о-П-п-Р-р-С-с-Т-т-У-у-Ф-ф-Х-х-Ц-ц-Ч-ч-Ш-ш-Щ-щ-Ъ-ъ-Ы-ы-Ь-ь-Э-э-Ю-ю-Я-я").split("-")   
	var en=("A-a-B-b-V-v-G-g-G-g-D-d-E-e-E-e-E-e-ZH-zh-Z-z-I-i-I-i-I-i-J-j-K-k-L-l-M-m-N-n-O-o-P-p-R-r-S-s-T-t-U-u-F-f-H-h-TS-ts-CH-ch-SH-sh-SCH-sch-'-'-Y-y-'-'-E-e-YU-yu-YA-ya").split("-")   
 	var res = '';
	for(var i=0, l=str.length; i<l; i++)
	{ 
		var s = str.charAt(i), n = ru.indexOf(s); 
		if(n >= 0) { res += en[n]; } 
		else { res += s; } 
    } 
    return res;  
}
</script>
 
{/literal}


{if $message_success}
<!-- Системное сообщение -->
<div class="message message_success">
	<span class="text">{if $message_success=='added'}Категория добавлена{elseif $message_success=='updated'}Категория обновлена{else}{$message_success}{/if}</span>
	<a class="link" target="_blank" href="/catalog/{$store->url}">Открыть категорию на сайте</a>
	{if $smarty.get.return}
	<a class="button" href="{$smarty.get.return}">Вернуться</a>
	{/if}
	
	<span class="share">		
		<a href="#" onClick='window.open("http://vkontakte.ru/share.php?url={$config->root_url|urlencode}/catalog/{$store->url|urlencode}&title={$store->name|urlencode}&description={$store->description|urlencode}&image={$config->root_url|urlencode}/files/stores/{$store->image|urlencode}&noparse=true","displayWindow","width=700,height=400,left=250,top=170,status=no,toolbar=no,menubar=no");return false;'>
  		<img src="{$config->root_url}/simpla/design/images/vk_icon.png" /></a>
		<a href="#" onClick='window.open("http://www.facebook.com/sharer.php?u={$config->root_url|urlencode}/catalog/{$store->url|urlencode}","displayWindow","width=700,height=400,left=250,top=170,status=no,toolbar=no,menubar=no");return false;'>
  		<img src="{$config->root_url}/simpla/design/images/facebook_icon.png" /></a>
		<a href="#" onClick='window.open("http://twitter.com/share?text={$store->name|urlencode}&url={$config->root_url|urlencode}/catalog/{$store->url|urlencode}&hashtags={$store->meta_keywords|replace:' ':''|urlencode}","displayWindow","width=700,height=400,left=250,top=170,status=no,toolbar=no,menubar=no");return false;'>
  		<img src="{$config->root_url}/simpla/design/images/twitter_icon.png" /></a>
	</span>
	
	
</div>
<!-- Системное сообщение (The End)-->
{/if}

{if $message_error}
<!-- Системное сообщение -->
<div class="message message_error">
	<span class="text">{if $message_error=='url_exists'}Категория с таким адресом уже существует{else}{$message_error}{/if}</span>
	<a class="button" href="">Вернуться</a>
</div>
<!-- Системное сообщение (The End)-->
{/if}


<!-- Основная форма -->
<form method=post id=product enctype="multipart/form-data">
<input type=hidden name="session_id" value="{$smarty.session.id}">
	<div id="name">
		<input class="name" name=name type="text" value="{$store->name|escape}"/> 
		<input name=id type="hidden" value="{$store->id|escape}"/> 
		<div class="checkbox">
			<input name=visible value='1' type="checkbox" id="active_checkbox" {if $store->visible}checked{/if}/> <label for="active_checkbox">Активна</label>
		</div>
	</div> 

	<div id="product_categories">
			<select name="parent_id">
				<option value='0'>Корневая категория</option>
				{function name=store_select level=0}
				{foreach from=$cats item=cat}
					{if $store->id != $cat->id}
						<option value='{$cat->id}' {if $store->parent_id == $cat->id}selected{/if}>{section name=sp loop=$level}&nbsp;&nbsp;&nbsp;&nbsp;{/section}{$cat->name}</option>
						{store_select cats=$cat->substores level=$level+1}
					{/if}
				{/foreach}
				{/function}
				{store_select cats=$stores}
			</select>
	</div>
		
	<!-- Левая колонка свойств товара -->
	<div id="column_left">
			
		<!-- Параметры страницы -->
		<div class="block layer">
			<h2>Параметры магазина</h2>
			<ul>
				<li><label class=property>Адрес</label><input id="adress" name="adress"  type="text" value="{$store->adress|escape}" /></li>
				<li><label class=property>Телефон</label>
					<ul class="phones">
						{if $store->phones}
							{foreach  $store->phones as $phone name=foo}
								<li><input name="phone[]"  type="text" value="{$phone}" />
								<span {if $smarty.foreach.foo.iteration > 1}style='display:none;'{/if} class="add"><i class="dash_link"></i></span>
								<span {if $smarty.foreach.foo.iteration == 1}style='display:none;'{/if} class="delete"><i class="dash_link"></i></span>
								</li>
								{/foreach}
						{else}
								<li><input name="phone[]"  type="text" value="" /><span class="add"><i class="dash_link"></i></span>
								<span style='display:none;' class="delete"><i class="dash_link"></i></span>
								</li>
						{/if}
					</ul>
				</li>
				<li><label class=property>Сайт</label>
				<span class="icons cell" style="float: left;line-height: 24px;margin-right: 3px;"><a class="preview" title="Предпросмотр в новом окне" href="https://anon.click/?{$store->www|escape}" target="_blank"></a></span>
				<input name="www"  type="text" value="{$store->www|escape}"  style="width: 242px;"/></li>
				<li><label class=property>Email</label><input name="email"  type="text" value="{$store->email|escape}" /></li>
				<li><label class=property>Время работы</label><input name="schedule"  type="text" value="{$store->schedule|escape}" /></li>
				<li><label class=property>Описание</label><textarea name="info"  type="text" class="simpla_inp" />{$store->info|escape}</textarea></li>
	<br>
				<li style="display:none"><label class=property>Координаты</label><input id="latlongmet" name="latlongmet"  type="text" value="{$store->latlongmet|escape}" /></li>
				<li style="display:none"><label class=property>mapzoom</label><input id="mapzoom" name="mapzoom"  type="text" value="{$store->mapzoom|escape}" /></li>
	<br>
				<h2>Параметры страницы</h2>
				<li><label class=property>URL</label><div class="page_url">/store/</div><input name="url" class="page_url" type="text" value="{$store->url|escape}" /></li>
				<li><label class=property>Заголовок</label><input name="meta_title" class="simpla_inp" type="text" value="{$store->meta_title|escape}" /></li>
				<li><label class=property>Ключевые слова</label><input name="meta_keywords" class="simpla_inp" type="text" value="{$store->meta_keywords|escape}" /></li>
				<li><label class=property>Описание</label><textarea name="meta_description" class="simpla_inp" />{$store->meta_description|escape}</textarea></li>
				
			</ul>
		</div>
		
		<!-- Параметры страницы (The End)-->
		
 		{*
		<!-- Экспорт-->
		<div class="block">
			<h2>Экспорт товара</h2>
			<ul>
				<li><input id="exp_yad" type="checkbox" /> <label for="exp_yad">Яндекс Маркет</label> Бид <input class="simpla_inp" type="" name="" value="12" /> руб.</li>
				<li><input id="exp_goog" type="checkbox" /> <label for="exp_goog">Google Base</label> </li>
			</ul>
		</div>
		<!-- Свойства товара (The End)-->
		*}
			
	</div>
	<!-- Левая колонка свойств товара (The End)--> 
	
	<!-- Правая колонка свойств товара -->	
	<div id="column_right">
		
		<!-- Свойства категории -->	
			<div class="block layer">
				<h2>Карта</h2>
				{literal}
				<style>
       #map {
        margin: 0;
        padding: 0;
        height: 445px;
      }
    </style>
<script src="http://api-maps.yandex.ru/2.1.22/?lang=ru_RU" type="text/javascript"></script>
<script type="text/javascript">


var myMap, myPlacemark, coords;
ymaps.ready(init);

function init () {
    myMap = new ymaps.Map('map', {        
        center: [{/literal}{if $store->latlongmet}{$store->latlongmet}{else}50.7572,30.4994{/if}{literal}], 
        zoom: {/literal}{if $store->mapzoom}{$store->mapzoom}{else}19{/if}{literal},
		controls: ['zoomControl', 'typeSelector']
    });
		
	var searchControl = new ymaps.control.SearchControl({
     options: {
         float: 'left',
         floatIndex: 100,
         noPlacemark: true
     }
});
myMap.controls.add(searchControl);

    coords = [50.7572,30.4994];
	//Определяем метку и добавляем ее на карту				
	myPlacemark = new ymaps.Placemark([{/literal}{if $store->latlongmet}{$store->latlongmet}{else}50.7572,30.4994{/if}{literal}],{}, {preset: "islands#redIcon", draggable: true});	
	myMap.geoObjects.add(myPlacemark);	
	//Отслеживаем событие перемещения метки
			myPlacemark.events.add("dragend", function (e) {			
			coords = this.geometry.getCoordinates();
			savecoordinats();
			}, myPlacemark);
			//Отслеживаем событие щелчка по карте
			myMap.events.add('click', function (e) {        
            coords = e.get('coords');
			savecoordinats();
			});	
//Отслеживаем событие выбора результата поиска
	searchControl.events.add("resultselect", function (e) {
		coords = searchControl.getResultsArray()[0].geometry.getCoordinates();
		savecoordinats();
	});
	//Ослеживаем событие изменения области просмотра карты - масштаб и центр карты
	myMap.events.add('boundschange', function (event) {
    if (event.get('newZoom') != event.get('oldZoom')) {		
        savecoordinats();
    }
	  if (event.get('newCenter') != event.get('oldCenter')) {		
        savecoordinats();
    }
	});	
}
//Функция для передачи полученных значений в форму
	function savecoordinats (){
		var new_coords = [coords[0].toFixed(4),  coords[1].toFixed(4)];	
		myPlacemark.getOverlaySync().getData().geometry.setCoordinates(new_coords);
		// document.getElementById("adress").value = searchControl.showResult(0);
		document.getElementById("latlongmet").value = new_coords;
		document.getElementById("mapzoom").value = myMap.getZoom();	
	};
	</script>
{/literal}
			<div id="map"></div>			
		</div>
		
				<!-- Изображение -->	
		<div class="block layer images">
			<h2>Изображение</h2>
			<input class='upload_image' name=image type=file>			
			<input type=hidden name="delete_image" value="">
			{if $store->image}
			<ul>
				<li>
					<a href='#' class="delete"><img src='design/images/cross-circle-frame.png'></a>
					<img src="../{$config->stores_images_dir}{$store->image}" alt="" />
				</li>
			</ul>
			{/if}
		</div>
		
	</div>
	<!-- Правая колонка свойств товара (The End)--> 

	<!-- Описагние категории -->
	<div class="block layer">
		<h2>Описание</h2>
		<textarea name="description" class="editor_small">{$store->description|escape}</textarea>
	</div>
	<!-- Описание категории (The End)-->
	<input class="button_green button_save" type="submit" name="" value="Сохранить" />
	
</form>
<!-- Основная форма (The End) -->

