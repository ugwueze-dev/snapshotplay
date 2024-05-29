<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	require '../includes/php_file_tree.php';
	?>
	<html>
		<head>
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
			<script src="includes/php_file_tree_jquery.js" type="text/javascript"></script>
		</head>
	</html>
	<?php

echo php_file_tree('./','[link]');
?>