<?php

	require_once('../../api/Simpla.php');
	$simpla = new Simpla();
	
	$category_id = $simpla->request->get('category_id', 'integer');
	$product_id = $simpla->request->get('product_id', 'integer');
	
	if(!empty($category_id))
		$features = $simpla->features->get_features(array('category_id'=>$category_id));
	else
		$features = $simpla->features->get_features();
		
	$options = array();
	if(!empty($product_id))
	{
		$opts = $simpla->features->get_product_options($product_id);
		
		$temp_options = array();
     foreach($opts as $option) {
         if(empty($temp_options[$option->feature_id]))
             $temp_options[$option->feature_id] = new stdClass;           
            $temp_options[$option->feature_id]->feature_id = $option->feature_id;
            $temp_options[$option->feature_id]->product_id = $option->product_id;
            $temp_options[$option->feature_id]->name = $option->name;
            $temp_options[$option->feature_id]->value[] = $option->value;   
     }
        $opts = $temp_options;
		
		
		foreach($opts as $opt)
			$options[$opt->feature_id] = $opt;
	}
		
	foreach($features as &$f)
	{
		if(isset($options[$f->id]))
			$f->value = $options[$f->id]->value;
		else
			$f->value = '';
	}

	header("Content-type: application/json; charset=UTF-8");
	header("Cache-Control: must-revalidate");
	header("Pragma: no-cache");
	header("Expires: -1");		
	print json_encode($features);
