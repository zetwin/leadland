<?php

	header("Content-type: image/jpeg");

	session_start();

// image config

	$code = rand(10000,99999);

	$_SESSION["captcha_code"] = $code;

	$bg_image = "blank.jpg";
	$font = "./maturasc.ttf";

	$size = 20;
	$rotation = rand(-4,8);
	$pad_x = 10;
	$pad_y = 27;

// generate image

	$img_path	= $bg_image;
	$img_size	= getimagesize($img_path);

	$width  = $img_size[0];
	$height = $img_size[1];

	$img = ImageCreateFromJpeg($img_path);

	$fg = ImageColorAllocate($img, 26, 188, 156);
	for($i=0; $i<rand(1,3); $i++) {
	    imageline($img, 20, rand()%10, 80, rand()%90, $fg);
	}

	ImageTTFText($img, $size, $rotation, $pad_x, $pad_y, $fg, $font, $code);

	imagejpeg($img);


?>