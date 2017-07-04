{$subject="Администратор магазина оставил ответ на Ваш комментарий" scope=parent}

<img style="display:block; border:1px solid #d0d0d0; margin:10px 0 10px 0;" src="/files/watermark/watermark.png?9497">

<h1 style="font-weight:normal;font-family:arial;">Ваш комментарий получил ответ администратора магазина</h1>

<div style="font-family:arial; font-size: 11pt;">Уважаемый(ая) {$comment->name|escape}! На ваш комментарий
{if $comment->type == 'product'}
к товару <a target="_blank" href="{$config->root_url}/products/{$comment->product->url}#comment_{$comment->id}">{$comment->product->name}</a>
{elseif $comment->type == 'blog'}
к статье <a target="_blank" href="{$config->root_url}/blog/{$comment->post->url}#comment_{$comment->id}">{$comment->post->name}</a>
{/if}
от {$comment->date|date} {$comment->date|time} с текстом:</div>
<span style="font-family:arial; font-size: 10pt; font-style:italic; padding-left:30px">{$comment->text|escape|nl2br}</span> 

<div style="font-family:arial; font-size: 11pt; padding-top: 10px;">получен официальный ответ:</div>
<span style="font-family:arial; font-size: 10pt; font-style:italic; padding-left:30px">{$comment->answer|escape|nl2br}</span> 

<div style="font-family:arial; font-size: 10pt; padding-top: 20px;">Посмотреть
{if $comment->type == 'product'}
<a target="_blank" href="{$config->root_url}/products/{$comment->product->url}#comment_{$comment->id}">комментарий к товару</a>
{elseif $comment->type == 'article'}
<a target="_blank" href="{$config->root_url}/article/{$comment->article->url}#comment_{$comment->id}">комментарий к статье</a>
{elseif $comment->type == 'page'}
<a target="_blank" href="{$config->root_url}/{$comment->page->url}#comment_{$comment->id}">комментарий к странице</a>
{elseif $comment->type == 'blog'}
<a target="_blank" href="{$config->root_url}/blog/{$comment->post->url}#comment_{$comment->id}">комментарий к статье</a>
{/if}
или перейти в <a href="{$config->root_url}">Магазин</a>
</div>