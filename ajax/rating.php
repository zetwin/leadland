<?php
    session_start();
    chdir('..');
    require_once('api/Simpla.php');
    $simpla = new Simpla();
    
    //получаем ID продукта
    $product_id = $simpla->request->get('id', 'integer');
    
    if($product_id) {
        $product = $simpla->products->get_product($product_id);
       
        if(!empty($product)) {
            if(!isset($_SESSION['rating_ids'])) $_SESSION['rating_ids'] = array();
            
            if(!in_array($product_id, $_SESSION['rating_ids'])) { //учитываем рейтинг

                $_SESSION['rating_ids'][] = $product_id; 

                $rating = $simpla->request->get('rating', 'integer');

                $votes = $product->votes + 1; //наращиваем количество голосов
                $rate = ($product->rating * $product->votes + $rating) / ($product->votes + 1); //наращиваем количество голосов

                $simpla->products->update_product($product_id, array('votes' => $votes, 'rating' => $rate));

                echo $rate; // возвращаем рейтинг
            }
            else echo -1; // пользователь уже голосовал
        }
        else echo 0; // не найден продукт
    }
    else echo 0; // не указан ID