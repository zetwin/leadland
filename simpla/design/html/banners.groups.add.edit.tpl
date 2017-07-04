{* Вкладки *}
{capture name=tabs}
	<li><a href="index.php?module=BannersAdmin&do=groups">Группы баннеров</a></li>
{/capture}

{* Title *}
{$meta_title='Добавить/редактировать группу баннеров' scope=parent}

	<!-- Листалка страниц -->
	{include file='pagination.tpl'}	
	<!-- Листалка страниц (The End) -->

	{* Заголовок *}
	<div id="header">	
		{if $smarty.get.return and !$message_success}<a class="button_yellow" href="{$smarty.get.return}">Вернуться</a>{/if}
	</div>

	<link href="design/css/banners.css" rel="stylesheet" type="text/css" />
	
	{if $message_success}
	<!-- Системное сообщение -->
	<div class="message message_success">
		<span>{if $message_success=='added'}Группа "{$group->name}" добавлена{elseif $message_success=='updated'}Группа "{$group->name}" изменена{else}{$message_success|escape}{/if}</span>
		{if $smarty.get.return}
		<a class="button" href="{$smarty.get.return}">Вернуться</a>
		{/if}
	</div>
	<!-- Системное сообщение (The End)-->
	{/if}

	{if $message_error}
	<!-- Системное сообщение -->
	<div class="message message_error">
		<span>{if $message_error=='empty_name'}Введите название группы баннеров{else}{$message_error|escape}{/if}</span>
	</div>
	<!-- Системное сообщение (The End)-->
	{/if}
	
	{if !$message_success}
	<form method=post enctype="multipart/form-data">
		<div class="cell group">
			<div class="banner_wrapper">
				<div class="group title"><a href="#">{if $action == 'add'}Добавить баннер{else}Редактировать баннер "{$group->name}"{/if}</a></div>
				<form method="get">
					<input type=hidden name="session_id" value="{$smarty.session.id}">
				<div style="padding:10px 0;">
					<label for="group_name" class="property">Название группы:</label>&nbsp;&nbsp;&nbsp;<input type="text" style="width:300px;" value="{$group->name}" name="name">
				</div>
				<div class="tip">
					<input type="submit" style="float:none;" value="{if $action == 'add'}Добавить баннер{else}Редактировать"{/if}" id="submit" class="button_green button_save">
					<input type="button" style="float:none;" value="Отменить" id="submit" class="button_red button_save" onclick="javascript:location.href='{$smarty.get.return}'">
				</div></form>
			</div>
		</div>
	</form>
	{/if}