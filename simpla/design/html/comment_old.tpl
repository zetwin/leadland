{* Вкладки *}
{capture name=tabs}
	<li class="active"><a href="index.php?module=CommentsAdmin">Комментарии</a></li>
	{if in_array('feedbacks', $manager->permissions)}<li><a href="index.php?module=FeedbacksAdmin">Обратная связь</a></li>{/if}
{/capture}

{if $delivery->id}
{$meta_title = 'Комментарий от $comment->name' scope=parent}
{else}
{$meta_title = '...' scope=parent}
{/if}

{literal}
<script src="design/js/jquery/jquery.js"></script>
<script src="design/js/jquery/jquery-ui.min.js"></script>
{/literal}


{if $message_success == 'added'}
	{if $smarty.get.return}
		<script>
			window.location.href = "{$smarty.get.return}";
		</script>
	{/if}
{/if}
	
<!-- Основная форма -->
<form method=post id=product enctype="multipart/form-data">

<input type=hidden name="session_id" value="{$smarty.session.id}">
<input name=id type="hidden" value="{$comment->id}"/> 

	<div id="name">
		<h2>Комментарий</h2>
		{$comment->text|escape|nl2br}
		<div class="checkbox">
			<input name=approved value='1' type="checkbox" id="active_checkbox" {if $comment->approved}checked{/if}/> <label for="active_checkbox">Одобрен</label>
		</div>
	</div> 

	<!-- Описагние товара -->
	<div class="block layer">
		<h2>Ответ</h2>	
		
		<textarea name="answer" style="width: 100%; min-height: 120px;">{$comment->answer|escape|nl2br}</textarea>
	</div>
	<!-- Описание товара (The End)-->
	<input class="button_green button_save" type="submit" name="" value="Сохранить" />
	
</form>
<!-- Основная форма (The End) -->

