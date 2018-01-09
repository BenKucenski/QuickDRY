<?php

/**
 * Class BarcodeClass
 */
class BarcodeClass
{
    /**
     * @param $width
     * @param $height
     * @param $code
     *
     * @return resource
     */
    public static function Generate($width, $height, $code)
	{
		global $Request;
		
		$root = BASEDIR;
		if(substr($root,-8) !== 'barcode/')
			$root .= 'barcode/';
		
		$number = strtoupper(base64_decode($code));
		
		$barcode_font = $root . 'FREE3OF9.TTF';

		if($Request->d)
			Debug($barcode_font, false);
		
		if(!is_dir($root . $width))
			mkdir($root . $width);
		
		if(!is_dir($root . $width . '/' . $height))
			mkdir($root . $width . '/' . $height);
		
		$font_size = $width / (strlen($number)/1.9);
	
		$img = imagecreate($width, $height);
	
		// First call to imagecolorallocate is the background color
		//$white = imagecolorallocate($img, 255, 255, 255);
		$black = imagecolorallocate($img, 0, 0, 0);
	
		// Reference for the imagettftext() function
		// imagettftext($img, $fontsize, $angle, $xpos, $ypos, $color, $fontfile, $text);
		imagettftext($img, $font_size, 0, 0, $font_size, $black, $barcode_font, $number);
	
		imagepng($img, $root . $width . '/' . $height . '/' . $code . '.png');
		return $img;
	}	
}