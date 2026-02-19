<?php
error_reporting(0);
session_start();
getCode(4,50,20);

function getCode($num,$w,$h) {
	$code = "";
	for ($i = 0; $i < $num; $i++) {
		$code .= rand(0, 9);
	}

	$_SESSION['PHP_M2T'] = $code;
	setcookie("mimi", md5($code), time()+1200);

	Header("Content-type: image/PNG");
	$im = imagecreate($w, $h);
	$black = imagecolorallocate($im, 255, 0, 63);
	$gray = imagecolorallocate($im, 200, 200, 200);
	$bgcolor = imagecolorallocate($im, 255, 255, 255);

	imagefill($im, 0, 0, $bgcolor);


	for ($i = 0; $i < 10; $i++) {
		imagesetpixel($im, rand(0, $w), rand(0, $h), $black);
	}

	$strx = rand(1,3);
	for ($i = 0; $i < $num; $i++) {
		$strpos = rand(2, 6);
		imagestring($im, 5, $strx, $strpos, substr($code, $i, 1), $black);
		$strx += rand(8, 12);
	}
	imagepng($im);
	imagedestroy($im);
}
?>
