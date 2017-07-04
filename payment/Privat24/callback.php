<?php

function response($code = 200, $response = "") {
    http_response_code($code);
	die($response);
}

// Работаем в корневой директории
chdir ('../../');
require_once('api/Simpla.php');
$simpla = new Simpla();

$payment = $_POST['payment'];
$signature = $_POST['signature'];

parse_str($payment, $payment_url);

if (empty($payment_url['order']))
    response(400, "Оплачиваемый заказ не найден");
else
    $order_url = $payment_url['order'];

////////////////////////////////////////////////
// Выберем заказ из базы
////////////////////////////////////////////////
$order = $simpla->orders->get_order((string)$order_url);
if(empty($order))
  response(400, 'Оплачиваемый заказ не найден');

////////////////////////////////////////////////
// Выбираем из базы соответствующий метод оплаты
////////////////////////////////////////////////
$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
if(empty($method))
	response(400, "Неизвестный метод оплаты");
	
$payment_currency = $simpla->money->get_currency(intval($method->currency_id));

// Нельзя оплатить уже оплаченный заказ
if($order->paid)
	response(400, 'Этот заказ уже оплачен');
	
$settings = $simpla->payment->get_payment_settings($order->payment_method_id);

if (empty($settings))
    response(400, 'Ошибка');

if ($signature != sha1(md5($payment . $settings['privat24_pass'])))
    response(400, "bad sign\n");

if ($payment_url['state'] == 'fail')
    response(400, "ошибка");


////////////////////////////////////
// Проверка наличия товара
////////////////////////////////////
$purchases = $simpla->orders->get_purchases(array('order_id' => intval($order->id)));
foreach($purchases as $purchase)
{
  $variant = $simpla->variants->get_variant(intval($purchase->variant_id));
  if(empty($variant) || (!$variant->infinity && $variant->stock < $purchase->amount))
  {
    response(400, "Нехватка товара $purchase->product_name $purchase->variant_name");
  }
}

// Установим статус оплачен
$simpla->orders->update_order(intval($order->id), array('paid' => 1));

// Спишем товары
$simpla->orders->close(intval($order->id));
$simpla->notify->email_order_user(intval($order->id));
$simpla->notify->email_order_admin(intval($order->id));


response(200, "ok");