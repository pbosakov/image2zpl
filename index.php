<?php
require_once('image2zpl.inc.php');

if (is_uploaded_file($_FILES['image']['tmp_name'])) {
	$input = file_get_contents($_FILES['image']['tmp_name']);
	list($name, $ext) = explode('.', $_FILES['image']['name']);
	$name = preg_replace('/[^A-z0-9]/', '', $name);
	$out = wbmp_to_zpl($input, $name);
	header('Content-Type: text/plain');
	echo($out);
	exit;
} ?>
<!doctype html>
<html>
<head>
<title>Image to ZPL converter</title>
</head>
<body>
<form method="post" enctype="multipart/form-data">Upload image: <input type="file" name="image"><br><input type="submit"></form>
</body>
</html>
