<?php
	session_start();
	require_once('../api/Simpla.php');
	$simpla = new Simpla();
	
	$variant_id = $simpla->request->post('variant', 'integer');
	$amount = $simpla->request->post('amount', 'integer');

	$order = new StdClass;
	$order->name = $simpla->request->post('name', 'string');
	$order->email = $simpla->request->post('email', 'string');
	
	
	// добавляем заказ
	$order_id = $simpla->orders->add_order($order);
	
	// добавляем товар в заказ
	$simpla->orders->add_purchase(array('order_id'=>$order_id, 'variant_id'=>intval($variant_id), 'amount'=>intval($amount)));

	// отправляем письмо администратору
	$simpla->notify->email_order_admin($order_id);	