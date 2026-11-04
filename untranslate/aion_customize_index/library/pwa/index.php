<?php
/*

Holy Bible Aionian Edition® Progressive Web Application, service worker
Publisher: https://NAINOIA-INC.signedon.net
Website: https://www.AionianBible.org
Resources: https://resources.AionianBible.org
Repository: https://github.com/Nainoia-Inc
Copyright: Creative Commons Attribution 4.0 International

The Aionian Bible project also serves all its translations as Progressive Web Apps.
Each Bible translation is contained in a single HTM file using javascript to paginate.
The PWA listing, manifests, service workers, and icons are served dynamically.
Dynamic files could be pre-generated, but dynamic results in a simpler GitHub package.
.htaccess rules masquerade each PWA into its own folder allowing multiple-installs.

*/

// PWA Dynamic File
// calculate the full URL
$url = trim(filter_var((empty($_SERVER['HTTPS']) ? 'http://' : 'https://')."{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", FILTER_SANITIZE_URL),'/\?');
// test for dynamic file creation
if (empty($_SERVER['QUERY_STRING']) &&
	preg_match("#^(.+)/(Holy-Bible---(.+)---(.+))/pwa\.(json|js|([0-9]+)\.png)$#", $url, $match) &&
	file_exists(($file=("{$match[2]}.htm"))) &&
	file_exists(($fonf=("fonts/anton-regular.ttf")))) {
	// parse filename
	$cach = $match[2];
	$base = "{$match[1]}/{$match[2]}/";
	$type = $match[5];
	$size = (empty($match[6]) ? 0 : (int)$match[6]);
	// parse htm header for meta data
	$SubTitle = "Aionian Bible";
	$Short = "AB";
	$Font = "";
	if (($handle = fopen($file, 'r'))) {
		$loop = 0;
		while ((++$loop)<30 && ($line = fgets($handle))) {
			if		(preg_match("#<!--\s*SubTitle:\s*(.+?)\s*-->#iu",	$line, $match2)) { $SubTitle = trim($match2[1]); }
			else if	(preg_match("#<!--\s*Short:\s*(.+?)\s*-->#iu",		$line, $match2)) { $Short = trim($match2[1]); }
			else if	(preg_match("#<!--\s*Font:\s*(.*?)\s*-->#iu",		$line, $match2)) { $Font = trim($match2[1]); }
		}
		fclose($handle);
	}

	// return the dynamic image icon requested
	if ($size>=16 && $size<=2048) {
		$IMG = imagecreatetruecolor($size, $size);
		$background = imagecolorallocate($IMG, 102,51, 153);
		$text_color = imagecolorallocate($IMG, 255,255,255);
		imagefill($IMG, 0, 0, $background);
		$font = $size * 0.28;
		$box = imagettfbbox($font, 0, $fonf, $Short);
		$width = $box[2] - $box[0];
		if ($width > $size * 0.62) { $font = $font * ($size * 0.62) / $width; $box = imagettfbbox($font, 0, $fonf, $Short); }
		$width = $box[2] - $box[0];
		$height = $box[1] - $box[7];
		$posx = (int)(($size - $width) / 2 - $box[0]);
		$posy = (int)(($size + $height) / 2 - $box[1]);
		imagettftext($IMG, $font, 0, $posx, $posy, $text_color, $fonf, $Short);
		header('Content-Type: image/png');
		header('Cache-Control: max-age=86400');
		imagepng($IMG);
		imagedestroy($IMG);
	}

	// return the dynamic webmanifest requested
	else if ($type=='json') {
		$manifest = array(
			'id'				=> $base,
			'dir'				=> 'ltr',
			'lang'				=> 'en',
			'name'				=> $SubTitle,
			'short_name'		=> $Short,
			'description'		=> "Holy Bible Aionian Edition® {$SubTitle}, Progressive Web Application",
			'start_url'			=> $base,
			'scope'				=> $base,
			'background_color'	=> '#FFFFFF',
			'theme_color'		=> '#2B1A42',
			'display'			=> 'standalone',
			'display_override'	=> array('minimal-ui', 'standalone'),
			'orientation'		=> 'any',
			'categories'		=> array('books', 'education', 'lifestyle'),
			'prefer_related_applications' => false,
			'icons'				=> array(
				array('src' => "{$base}pwa.192.png", 'type' => 'image/png', 'sizes' => '192x192', 'purpose' => 'any'),
				array('src' => "{$base}pwa.512.png", 'type' => 'image/png', 'sizes' => '512x512', 'purpose' => 'any'),
				array('src' => "{$base}pwa.512.png", 'type' => 'image/png', 'sizes' => '512x512', 'purpose' => 'maskable'),
			),
			'screenshots'		=> array(
				array('src' => "{$base}images/Aionian-Bible-PWA-Screenshot-Landscape.jpg", 'sizes' => '1301x708', 'type' => 'image/jpeg', 'form_factor' => 'wide',   'label' => 'Aionian Bible home screen wide format'),
				array('src' => "{$base}images/Aionian-Bible-PWA-Screenshot-Portrait.jpg",  'sizes' => '502x770',  'type' => 'image/jpeg', 'form_factor' => 'narrow', 'label' => 'Aionian Bible home screen narrow format'),
			),
		);
		header('Content-Type: application/manifest+json; charset=utf-8');
		header('Cache-Control: no-cache');
		echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// return the dynamic service worker requested
	else if ($type=='js') {
		$version = date('Y-m-d-His', filemtime($file));
		$resources = array(
			'./',
			'pwa.json',
			'pwa.192.png',
			'pwa.512.png',
		);
		$files = array(
			'fonts/gentiumplus-r.woff2',
			'fonts/notosans-basic-regular.woff2',
		);
		if (!empty($Font)) {
			$files[] = "fonts/{$Font}.woff";
		}
		$files = array_merge($files, array(
			'images/favicon.ico',
			'images/favicon-32x32.png',
			'images/favicon-16x16.png',
			'images/apple-touch-icon.png',
			'images/Holy-Bible-Aionian-Edition-PURPLE-HOME.png',
			'images/Holy-Bible-Aionian-Edition-PURPLE-LOGO-PWA.png',
			'images/Holy-Bible-Aionian-Edition-PURPLE-AB-PWA.png',
			'images/Gustave-Dore-Bible-Tour-Hebrew-OT-003-Adam-and-Eve-Are-Driven-out-of-Eden.jpg',
			'images/Gustave-Dore-Bible-Tour-NT-Gospel-215-The-Crucifixion-of-Jesus-and-Two-Criminals.jpg',
			'images/Gustave-Dore-Bible-Tour-NT-Gospel-241-The-New-Jerusalem.jpg',
			'images/Timeline-History-Aionian-Bible.jpg',
			'images/Timeline-Eschatology-Aionian-Bible.jpg',
			'images/MAP-Abrahams-Journey.jpg',
			'images/MAP-Israels-Exodus.jpg',
			'images/MAP-Jesus-Journeys.jpg',
			'images/MAP-Pauls-Missionary-Journeys.jpg',
			'images/MAP-World-Nations.jpg',
		));
		foreach($files as $f) { if (file_exists($f)) { $resources[] = $f; } }
		$list = "'" . implode("',\n'", $resources) . "'";

		header('Content-Type: text/javascript; charset=utf-8');
		header('Cache-Control: no-cache');
		echo <<<EOL
// Holy Bible Aionian Edition® Progressive Web Application, Service Worker
// Publisher: https://NAINOIA-INC.signedon.net
// Website: https://www.AionianBible.org
// Resources: https://resources.AionianBible.org
// Repository: https://github.com/Nainoia-Inc
// Copyright: Creative Commons Attribution 4.0 International
//
// The Aionian Bible project also serves all its translations as Progressive Web Apps.
// Each Bible translation is contained in a single HTM file using javascript to paginate.
// The PWA listing, manifests, service workers, and icons are served dynamically.
// .htaccess rules masquerade each PWA into its own folder allowing multiple-installs.
//
// Every translation shares one origin, and the Cache Storage API is per-origin, not
// per-folder. So every cache name starts with this translation's own prefix, and
// cleanup only ever touches caches with that same prefix.

'use strict';

const AionianBible_Debug = false;
const AionianBible_Prefix = '{$cach}-';
const AionianBible_Version = '{$version}';
const AionianBible_CacheName = AionianBible_Prefix + AionianBible_Version;

// Paths are relative to this service worker's folder (its scope).
// './' is the Bible page itself, the start_url in pwa.json.
const AionianBible_CacheResources = [
{$list}
];

// Full URLs, resolved against this service worker's scope
const AionianBible_Scope = self.registration.scope;
const AionianBible_PageURL = new URL('./', AionianBible_Scope).href;
const AionianBible_CacheURLs = new Set(AionianBible_CacheResources.map((path) => new URL(path, AionianBible_Scope).href));

function AionianBible_Log(message) {
	if (AionianBible_Debug) { console.log(`Aionian Bible App service worker: \${message}`); }
}

// Download every resource from the network bypassing HTTP cache into named cache
async function AionianBible_CacheFill(cacheName) {
	const cache = await caches.open(cacheName);
	await Promise.all([...AionianBible_CacheURLs].map(async (url) => {
		const response = await fetch(url, { cache: 'reload' });
		if (!response.ok) { throw new Error(`\${response.status} \${url}`); }
		await cache.put(url, response);
	}));
	AionianBible_Log(`cache filled: \${cacheName}`);
}

// Install: fill new versioned cache, failed download fails install and old version stays active
self.addEventListener('install', (event) => {
	event.waitUntil((async () => {
		await AionianBible_CacheFill(AionianBible_CacheName);
		await self.skipWaiting();
	})());
});

// Activate: delete only this translation's older caches, then take control of open pages
self.addEventListener('activate', (event) => {
	event.waitUntil((async () => {
		const names = await caches.keys();
		await Promise.all(names
			.filter((name) => name.startsWith(AionianBible_Prefix) && name !== AionianBible_CacheName)
			.map((name) => { AionianBible_Log(`cache deleted: \${name}`); return caches.delete(name); }));
		await self.clients.claim();
	})());
});

// Messages from the page
// AionianBible_PWA_ReInstall: re-download everything into the current cache and reply
self.addEventListener('message', (event) => {
	if (!event.data || event.data.type !== 'AionianBible_PWA_ReInstall') { return; }
	const reply = event.ports && event.ports[0];
	event.waitUntil((async () => {
		try {
			await AionianBible_CacheFill(AionianBible_CacheName);
			if (reply) { reply.postMessage({ ok: true }); }
		}
		catch (error) {
			AionianBible_Log(`re-install failed: \${error}`);
			if (reply) { reply.postMessage({ ok: false, error: String(error) }); }
		}
	})());
});

// Cache first, then network; a good network response is saved for next time.
async function AionianBible_CacheFirst(request, cacheKey) {
	const cache = await caches.open(AionianBible_CacheName);
	const cached = await cache.match(cacheKey);
	if (cached) { return cached; }
	try {
		const response = await fetch(request);
		if (response.ok && response.type === 'basic') { await cache.put(cacheKey, response.clone()); }
		return response;
	}
	catch (error) {
		AionianBible_Log(`offline and not cached: \${cacheKey}`);
		return Response.error();
	}
}

self.addEventListener('fetch', (event) => {
	const request = event.request;
	if (request.method !== 'GET') { return; }
	const url = new URL(request.url);
	if (url.origin !== self.location.origin || !url.href.startsWith(AionianBible_Scope)) { return; }

	// Page loads such as ?PWA, ?TOC, ?John-3 are all the same single HTML file
	if (request.mode === 'navigate') {
		event.respondWith(AionianBible_CacheFirst(request, AionianBible_PageURL));
		return;
	}

	// Fonts, images, manifest: match on the URL without its query string
	const key = url.origin + url.pathname;
	if (AionianBible_CacheURLs.has(key)) {
		event.respondWith(AionianBible_CacheFirst(request, key));
	}
});
EOL;
	}
	else {
		http_response_code(404);
	}
	exit;
}


// PWA Listing
$path = trim(strtok($_SERVER['REQUEST_URI'],'?'),'/\?');
$_Message = NULL;
// output error message
if (!empty($_SERVER['QUERY_STRING']) ||
	(!empty($path) && !preg_match("#".preg_quote($path, '#')."$#ui", dirname(__FILE__)))) {
	http_response_code(404);
	$_Message = "<span style='color:red;'>Sorry page requested not found<br><br></span>";
}
$path = htmlspecialchars($path, ENT_QUOTES);

// output list
$year = date("Y");
echo <<< EOF
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Holy Bible Aionian Edition® ~ PWA</title>
<meta name="description" content="Holy Bible Aionian Edition® ~ The world's first Holy Bible untranslation! ~ Progressive Web Application">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#663399">
<meta name="generator" content="ABCMS™">
<meta property="og:url" content="https://www.aionianbible.org/pwa">
<meta property="og:type" content="website">
<meta property="og:title" content="Holy Bible Aionian Edition® ~ PWA">
<meta property="og:description" content="Holy Bible Aionian Edition® ~ The world's first Holy Bible untranslation! ~ Progressive Web Application">
<meta property="og:image" content="https://www.aionianbible.org/images/MEME-AionianBible-The-Worlds-First-Bible-Untranslation-1.jpg">
<meta property="og:image" content="https://www.aionianbible.org/images/MEME-AionianBible-The-Worlds-First-Bible-Untranslation-2.jpg">
<meta property="og:image" content="https://www.aionianbible.org/images/MEME-AionianBible-The-Worlds-First-Bible-Untranslation-3.jpg">
<meta property="og:image" content="https://www.aionianbible.org/images/MEME-AionianBible-The-Worlds-First-Bible-Untranslation-4.jpg">
<link rel="shortcut icon" href="https://www.aionianbible.org/images/favicon.ico" type="image/x-icon">
<link rel="apple-touch-icon" sizes="180x180" href="https://www.aionianbible.org/images/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="https://www.aionianbible.org/images/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://www.aionianbible.org/images/favicon-16x16.png">
<style>
body { padding: 16px; } h1 { margin-top: 0; font-size: 1.2em; }
img.bible { max-width: 216px; height: auto; }
a.bible { margin-bottom: 5px; display: inline-block; text-decoration: none; font-size: 16px; }
</style>
</head>
<body>
<a href='https://www.AionianBible.org' title='Visit AionianBible.org' target='_blank'><img src='https://www.aionianbible.org/images/Holy-Bible-Aionian-Edition-PURPLE-HOME.png' alt='Aionian Bible' class='bible'></a><br>
The world's first Holy Bible untranslation! <a href='https://www.AionianBible.org/Preface' title='Check out the mission' target='_blank'>Check out the mission</a>.<br>
Licensed with <a href='https://creativecommons.org/licenses/by/4.0/' title='License' target='_blank'>Creative Commons Attribution 4.0 International license</a>, 2018-{$year}.<br>
<a href='https://www.AionianBible.org/Third-Party-Publisher-Resources' target='_blank'>Third Party Publisher resources here</a><br>
<br>
{$_Message}
<h1>Progressive Web Applications (PWA)</h1>
Available off-line on smart devices.<br>
<br>
EOF;

$files = array_diff(scandir('./'), array('.', '..'));
foreach($files as $file) {
	if (!preg_match("#^(Holy-Bible---(.+)---Aionian-Edition)\.htm$#", $file, $matches) ||
		!($bible=preg_replace("#---#", ":&nbsp;&nbsp;&nbsp;", $matches[2])) ||
		!($bible=preg_replace("#-#", "&nbsp;", $bible))) { continue; }

	if ($_Message) {	echo "$bible<br>"; }
	else {				echo "<a href='{$path}/{$matches[1]}/' target='_blank' class='bible'>$bible</a><br>"; }
}
echo "</body></html>";
exit;
