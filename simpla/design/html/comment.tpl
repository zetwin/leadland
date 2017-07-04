{* Вкладки *}
{capture name=tabs}
	<li class="active"><a href="index.php?module=CommentsAdmin">Комментарии</a></li>
	{if in_array('feedbacks', $manager->permissions)}<li><a href="index.php?module=FeedbacksAdmin">Обратная связь</a></li>{/if}
{/capture}


{* Title *}
{$meta_title = "Изменение комментария" scope=parent}

{* On document load *}
<script src="design/js/jquery/datepicker/jquery.ui.datepicker-ru.js"></script>

{literal}
<script>
$(function() {

	$('input[name="date"]').datepicker({
		regional:'ru'
	});
	
	// отлавливаем передачу формы и если отмечено уведомление, но ответ пустой - ругаемся!
	$('#product').submit(function(){
		var notify = $('#notify_user').prop("checked");
		var answer  = $('textarea[name=answer]').val();
		if (notify && answer == '') {
			alert ("Укажите ответ на комментарий или отключите уведомление!");
			return false;
		}
	});
	
	// отлавливаем каждое нажатие кнопки клавиатуры и если после этого нажатие ответ остается пустой,
	// мы снимаем отметку уведомление, а если в ответ что-то написали - то ставим уведомление
	$('textarea[name=answer]').keyup(function(){
		if ($(this).val()=='')
			$('#notify_user').prop("checked",false);
		else
			$('#notify_user').prop("checked",true);
		
	});
});
</script>
{/literal}

{if $message_success}
<!-- Системное сообщение -->
<div class="message message_success">
	<span>{if $message_success == 'updated'}Комментарий изменен{/if}</span>
	{if $smarty.get.return}
	<a class="button" href="{$smarty.get.return}">Вернуться</a>
	{/if}
</div>
<!-- Системное сообщение (The End)-->
{/if}

<!-- Основная форма -->
<form method="post" id="product" enctype="multipart/form-data">
<input type="hidden" name="session_id" value="{$smarty.session.id}">
	<div id="name">
		<input class="name" name="name" type="text" value="{$comment->name|escape}"/> 
		<input name="id" type="hidden" value="{$comment->id|escape}"/> 
		<input name="type" type="hidden" value="{$comment->type|escape}">
		<input name="email" type="hidden" value="{$comment->email|escape}">
		<input name="object_id" type="hidden" value="{$comment->object_id|escape}">
		<div class="checkbox">
			<input name="approved" value="1" type="checkbox" id="active_checkbox" {if $comment->approved}checked{/if}/> <label for="active_checkbox">Одобрен</label>
		</div>
	</div> 

	<!-- Левая колонка -->
	<div id="column_left">
			
		<!-- Параметры страницы -->
		<div class="block">
			<ul>
				<li><label class="property">E-mail комментируемого</label> {if $comment->email}<a href="{$comment->email|escape}">{$comment->email|escape}</a></li>{else}не указан{/if}
					{if $comment->type == 'product'}
				<li><label class="property">Комментарий к товару</label><a target="_blank" href="{$config->root_url}/products/{$comment->product->url}#comment_{$comment->id}">{$comment->product->name}</a></li>
					{elseif $comment->type == 'article'}
				<li><label class="property">Комментарий к статье</label><a target="_blank" href="{$config->root_url}/article/{$comment->article->url}#comment_{$comment->id}">{$comment->article->name}</a></li>
					{elseif $comment->type == 'blog'}
				<li><label class="property">Комментарий к статье</label><a target="_blank" href="{$config->root_url}/blog/{$comment->post->url}#comment_{$comment->id}">{$comment->post->name}</a></li>
					{/if}

			</ul>
		</div>
	</div>
	<!-- Левая колонка (The End)--> 
	
	<!-- Правая колонка -->	
	<div id="column_right">
		<div class="block">
			<ul>
				<li><label class="property">Дата комментария</label>{$comment->date|date} {$comment->date|time}</li>
				<li><label class="property">IP комментатора</label>{$comment->ip}</li>
			</ul>
		</div>
	</div>
	<!-- Правая колонка (The End)--> 

	<!-- Комментарий -->
	<div class="block layer">
		<h2>Текст комментария</h2>
		<textarea name="text" class="editor_small" style="width:100%; height:100px;">{$comment->text|escape}</textarea>
		</br></br>
		<h2>Ответ администратора</h2> 
		<textarea name="answer" class="editor_small" style="width:100%; height:100px;">{$comment->answer|escape}</textarea>
		{if $comment->email}		
			<input type="checkbox" value="1" id="notify_user" name="notify_user">
			<label for="notify_user">Уведомить пользователя об ответе на комментарий</label>
		{/if}
	</div>

	<input class="button_green button_save" type="submit" name="" value="Изменить" />
	
</form>
<!-- Основная форма (The End) -->