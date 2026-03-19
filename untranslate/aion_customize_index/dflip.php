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
	margin: 0;
}
div.df-ui-btn.df-ui-fullscreen {
	background-color: #663399;
}
#page { width: 100%; }
#head { width: 100%; max-width: 1280px; min-width: 360px; background-color: #E0D6EB; margin: 0 auto; }
#head-hi { max-height: 42px; margin-top: 10px; padding: 2px 15px; border: 1px solid #663399; border-radius: 7px; background-color: #663399; overflow: hidden; }
#logo1 { display: inline-block !important; float: left; }
#menu { display: block; float: right; white-space: nowrap; margin-top: 7px; }
#menu a { color: #FFFFFF; margin: 0px 0px 0px 15px; display: inline-block; font-size: 175%; text-decoration: none; }
#menu a:hover { color: #E0D6EB; }
@media screen and (max-width: 1279px) {
	#head-hi { margin-top: 0; }
	#head-hi { border: none; border-bottom: 1px solid #9966CC; border-radius: 0; }
}
#welcome { width: 100%;	text-align: center;	font-size: 1.25em; line-height: 1.4em; }
#welcome h3 { margin-bottom: 10px; }
	</style>
</head>

<body>

<div id="page">
<div id="head">
<div id="head-hi">
<div id="logo1"><a href="/" title="Aionian Bible homepage"><img src="/images/Holy-Bible-Aionian-Edition-PURPLE-LOGO.png" alt="Aionian Bible"></a></div>
<div id="menu"><a href="<? echo $return; ?>" title="Return to Table of Contents">Table of Contents</a></div>
</div>
</div>
<div id='welcome'>
<h3>DearFlip Flipbook Viewer</h3>
Click <span style='color: #663399;'>purple button</span> below for full screen.<br>
</div>
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