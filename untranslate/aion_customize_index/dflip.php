<?php
// Aionian Bible Flipbook with Dearflip
// https://github.com/dearhive/dearflip-js-flipbook
if (empty($_GET['f'])) { exit(header('Location: /dearflip-not-found',true,302)); }
$flip = $_GET['f'];
$flipbook = 'resources/'.trim(str_ireplace('/xcdn','',$flip),'/');
if (!is_file($flipbook)) { exit(header('Location: /dearflip-not-found',true,302)); }
$return = '/Bibles/'.preg_replace('/^Holy-Bible---/u','',preg_replace('/---(Source|Aionian)-Edition.pdf/u','',$flip));

/* Dearflip */
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Dearflip flipbook ~ <? echo $flip; ?></title>
	<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
	<!-- Flipbook StyleSheet -->
	<link href="./dflip/css/dflip.min.css" rel="stylesheet" type="text/css">
	<!-- Icons Stylesheet -->
	<link href="./dflip/css/themify-icons.min.css" rel="stylesheet" type="text/css">
	<style>
body {
	font-family: 'Arial', 'sans-serif';
	background-color: #E0D6EB;
}
div.welcome {
	font-size: 1.25em;
	line-height: 1.4em;	
}
div.df-ui-btn.df-ui-fullscreen {
	background-color: red;
}
	</style>
</head>

<body>
<div class='welcome'>
Welcome to DearFlip!<br>
<span style='color: red;'>Red button</span> for full screen.<br>
Return to <a href='<? echo $return; ?>'>Table of Contents</a>.
</div>
<div class="container">
<div class="row">
	<div class="col-xs-12">
		<div class="col-xs-12" style="padding-bottom:30px">
			<!--Normal FLipbook-->
			<div class="_df_book" height="100%" webgl="true" backgroundcolor="#E0D6EB" source="<? echo $flipbook; ?>" id="df_manual_book">
			</div>
		</div>
	</div>
</div>
<!-- jQuery  -->
<script src="./dflip/js/libs/jquery.min.js" type="text/javascript"></script>
<!-- Flipbook main Js file -->
<script src="./dflip/js/dflip.min.js" type="text/javascript"></script>
</body>
</html>
<?
exit;