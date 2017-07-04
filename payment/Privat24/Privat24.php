<?php

require_once('api/Simpla.php');

class Privat24 extends Simpla
{
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';

		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
		$settings = $this->payment->get_payment_settings($payment_method->id);

		$price = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);


		// описание заказа
		// order description
		$desc = 'Оплата заказа №'.$order->id;

        $success_url = $this->config->root_url . '/order/' . $order->url;
		$result_url = $this->config->root_url.'/payment/Privat24/callback.php';

		$currency = $payment_currency->code;

        if ($currency == 'RUR')
          $currency = 'RUB';

        $button =   '<form action="https://api.privatbank.ua/p24api/ishop" method="POST"/>'.
                    '<input type="hidden" name="amt" value="' . $price . '"/>'.
                    '<input type="hidden" name="ccy" value="' . $currency . '" />'.
                    '<input type="hidden" name="merchant" value="' . $settings['privat24_merchantid'] . '" />'.
                    '<input type="hidden" name="order" value="' . $order->url . '" />'.
                    '<input type="hidden" name="details" value="' . $desc . '" />'.
                    '<input type="hidden" name="ext_details" value="" />'.
                    '<input type="hidden" name="pay_way" value="privat24" />'.
                    '<input type="hidden" name="return_url" value="' . $success_url . '" />'.
                    '<input type="hidden" name="server_url" value="' . $result_url . '" />'.
                    '<input type="submit" value="' . $button_text . '" />'.
                    '</form>';

        return $button;
    }
}