<?php
/*
Copyright 2015 Petko V. Bossakov

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

/**
 * Converts image file contents to ZPL "download graphic" command
 *
 * @param string $in image binary string
 *
 * @param string $name arbitrary filename
 *
 * @return string ZPL "download graphic" command
 */
function wbmp_to_zpl($in, $name='') {

	if(empty($in)) return '';

	// Load image
	$im = imagecreatefromstring($in);
	if ($im === false) return false;

	// Black and white only
	imagefilter($im, IMG_FILTER_GRAYSCALE); //first, convert to grayscale
	imagefilter($im, IMG_FILTER_CONTRAST, -255); //then, apply a full contrast

	// Convert to WBMP
	ob_start();
	imagewbmp($im);
	$wbmp = ob_get_contents();
	ob_end_clean();


	$type = uintvar_shift($wbmp);
	$fixed = uintvar_shift($wbmp);
	$w = uintvar_shift($wbmp);
	$h = uintvar_shift($wbmp);

	$bitmap = ~$wbmp; // Black is white, white is black

	$total_bytes = strlen($bitmap);
	$bytes_per_line = ceil($w/8);

	if($w % 8 > 0) {

		// End of line is padded with black; make that white

		// Get last byte of each line
		$period = ceil($w/8);
		for($i=$bytes_per_line; $i <= $total_bytes; $i+=$bytes_per_line) {
			$byte = ord($bitmap[$i-1]);
			for($j=1; $j<=$w%8; $j++) {
				// Flip j-th bit
				$byte = $byte & (~(1<<($j-1)));
			}
			$bitmap[$i-1] = chr($byte);
		}

	}


	$uncompressed = strtoupper(bin2hex($bitmap));
	$compressed = preg_replace_callback('/(.)\1{2,}/', "zpl_rle_compress_helper", $uncompressed); 

	$name = preg_replace('/[^A-z0-9]/', '', $name);
	if(strlen($name) > 8) $name = substr($name, 0, 8);
	$name = strtoupper($name);
	if(empty($name)) $name = rand(10000000,99999999);

	$r = "~DG" . $name . ".GRF," . $total_bytes . "," . $bytes_per_line . "," . $compressed;

	return $r;

}

/**
 * Compresses a run of repeating hexadecimal characters (0-F) using the basic ZPL II RLE compression scheme
 *
 * @param array $repeating_characters array with ASCII hex string as the first element
 *
 * @return string
 */
function zpl_rle_compress_helper($repeating_characters) {

	$map = array(
		'G' => 1,
		'H' => 2,
		'I' => 3,
		'J' => 4,
		'K' => 5,
		'L' => 6,
		'M' => 7,
		'N' => 8,
		'O' => 9,
		'P' => 10,
		'Q' => 11,
		'R' => 12,
		'S' => 13,
		'T' => 14,
		'U' => 15,
		'V' => 16,
		'W' => 17,
		'X' => 18,
		'Y' => 19,
		'g' => 20,
		'h' => 40,
		'i' => 60,
		'j' => 80,
		'k' => 100,
		'l' => 120,
		'm' => 140,
		'n' => 160,
		'o' => 180,
		'p' => 200,
		'q' => 220,
		'r' => 240,
		's' => 260,
		't' => 280,
		'u' => 300,
		'v' => 320,
		'w' => 340,
		'x' => 360,
		'y' => 380,
		'z' => 400
	);
	arsort($map);

	$r = '';
	$remainder = strlen($repeating_characters[0]);

	while($remainder > 0) {
		foreach($map as $key=>$value) {

			if($remainder >= $value) {
				$remainder -= $value;
				$r .= $key;
			}
		}
	}

	$r .= $repeating_characters[1];
	return $r;
}

/**
 * Decodes a WBMP variable-length unsigned integer 
 * located at the beginning of the given string,
 * and strips it off the string.
 *
 * @param string $data binary string
 *
 * @return integer
 */
function uintvar_shift(&$data) {

	$index = 0;
	$r = 0;

	while ((ord($data[$index]) & 0x80) != 0) {
		if ($index >= 4) {
		    return false;
		}
		$r = ($r << 7) | (ord($data[$index]) & 0x7f);
		$index++;
	}

	$r = ($r << 7) | (ord($data[$index]) & 0x7f);

	$data = substr($data, $index + 1);
	return $r;

}