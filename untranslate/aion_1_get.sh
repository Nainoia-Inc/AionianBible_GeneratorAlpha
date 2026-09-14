#!/usr/local/bin/php
<?php



/*** init ***/
require_once('./aion_common.php');
AION_ECHO("START " . basename(__FILE__, '.php'));


define('DESTINATION',	'../source-stage');
define('PRODUCTION',	'../www-stageresources');
define('DIFFERENCE',	'../diff-source-stage-with-source-production-BEFORE-UPDATE');
define('COPYRIGHT_S',	'../copyright-source');
define('COPYRIGHT_P',	'../copyright-production');
define('COPYRIGHT_D',	'../copyright-diff');


/*** utility functions ***/
AION_ECHO("DEFINE FUNCTIONS");
function postcopy( $source_url, $post_variable, $post_value, $destiny ) {
	$handle = fopen($destiny, "w");
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, str_replace(" ","%20",$source_url)); 
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_FILE, $handle);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_variable . "=" . $post_value);
	$result = curl_exec($ch);
	curl_close($ch);
	fclose($handle);
	return $result;
}

function checksource( $url, &$status, &$redirect, &$size, &$date, &$stime, &$ignore) {
	$ignore = NULL;
	$status = $redirect = $size = 0;
	$date = date("m/d/Y H:i:s");
	$stime = time();
	$resURL = curl_init(); 
	curl_setopt($resURL, CURLOPT_URL, $url); 
	curl_setopt($resURL, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($resURL, CURLOPT_NOBODY, true );	
	curl_setopt($resURL, CURLOPT_HEADER, true);
	curl_setopt($resURL, CURLOPT_FAILONERROR, 1); 
	curl_setopt($resURL, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($resURL, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($resURL, CURLOPT_FILETIME, true);
	if ( curl_exec ($resURL) != FALSE ) {
		$status = curl_getinfo($resURL, CURLINFO_HTTP_CODE); 
		$redirect = curl_getinfo($resURL, CURLINFO_EFFECTIVE_URL);
		$size = curl_getinfo($resURL, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$date_raw = curl_getinfo($resURL, CURLINFO_FILETIME);
		if (is_numeric($date_raw) && $date_raw > 0) {
			$date = date("m/d/Y H:i:s", (int)$date_raw);
			$stime = (int)$date_raw;
		}
	}
	curl_close ($resURL); 
	return $status;
}


/*** read data table ***/
AION_ECHO("READ FILE SOURCE TABLE AND BIBLE VERSIONS");
$database = array();
AION_FILE_DATA_GET( './aion_database/SOURCES.txt', 'T_SOURCES', $database, FALSE, FALSE );
AION_FILE_DATA_GET(	'./aion_database/VERSIONS.txt',	'T_VERSIONS', $database, 'BIBLE', FALSE );
$args = array('database' => $database);


/*** get bibles! ***/
AION_ECHO("LOOP THROUGH FILE RETRIEVALS");
$retrieved = $skipped = $errors = 0;
foreach( $database[T_SOURCES] as $data ) {

	/* BIBLE */
	$bible = $data[C_FILE];

	/* SKIP */
	if ($data[C_FLAG]=='SKIP') { continue; }
	

	/* RETRIEVE */
	$retrieve = FALSE;
	$source = $data[C_SOURCE];
	$source_size = 0;
	$source_date = date("m/d/Y H:i:s");
	$source_time = time();
	$destination = DESTINATION . '/' . $data[C_DESTINATION];
	
	/* POST retrieve */
	if (!empty($data[C_POST])) {
		if (postcopy ($source, $data[C_POST], $data[C_VALUE], "$destination.tmp") &&
			($stat=stat("$destination.tmp")) &&
			$stat['size']>0 &&
			rename("$destination.tmp", $destination)) {
			AION_ECHO("POST $source post(" . $data[C_POST] . "=" . $data[C_VALUE] . ") to $destination");
			if (file_exists($destination) && ($stat=stat($destination))) {
				$source_size = $stat['size'];
				$source_date = date("m/d/Y H:i:s", $stat['mtime']);
				$source_time = $stat['mtime'];
			}
			$retrieve = TRUE;
			++$retrieved;
		}
		else {
			if (file_exists("$destination.tmp")) { unlink("$destination.tmp"); }
			AION_ECHO("WARN! failed post retrieval size=" . $stat['size'] . " ~ $source post(" . $data[C_POST] . "=" . $data[C_VALUE] . ") to $destination"); }
			++$errors;
	}
	
	/* HTTP retrieve */
	else if (stripos($source,"http://")===0 || stripos($source,"https://")===0) {
		checksource($source, $source_status, $source_redirect, $source_size, $source_date, $source_time, $errorreturn);
		if ($errorreturn) {
			AION_ECHO("WARN! CHECK ERROR RETURN: $errorreturn");
			AION_ECHO("WARN! SKIPPING ERROR SOURCE: {$data[C_FILE]} $source"); 
			continue;
		}
		if ($source_size<=0) {
			AION_ECHO("WARN! ZERO SIZE SOURCE: $source");
			if (!preg_match("#STEPBible#u",$source)) { AION_ECHO("WARN! SKIPPING ZERO SIZE SOURCE: {$data[C_FILE]} $source"); continue; }
		}
		$destination_size = $destination_date = "unknown";
		if (file_exists($destination) && ($stat=stat($destination))) {
			$destination_size = $stat['size'];
			$destination_date = date("m/d/Y H:i:s", $stat['mtime'] );
		}
		if ($source_size != $destination_size || $source_date != $destination_date) {
			if (copy($source, $destination)) {
				touch($destination, $source_time);
				AION_ECHO("COPIED $source to $destination");
				$retrieve = TRUE;
				++$retrieved;
			}
			else {
				$error = error_get_last();
				AION_ECHO("WARN! ". $error['message'] . " ~ $source to $destination");
				++$errors;
			}
		}
		else {
			++$skipped;
		}
	}

	/* local retrieval */
	else {
		if (file_exists($source) && ($stat=stat($source))) {
			$source_size = $stat['size'];
			$source_date = date("m/d/Y H:i:s", $stat['mtime']);
			$source_time = $stat['mtime'];
		}
		$destination_size = $destination_date = "unknown";
		if (file_exists($destination) && ($stat=stat($destination))) {
			$destination_size = $stat['size'];
			$destination_date = date("m/d/Y H:i:s", $stat['mtime'] );
		}
		if ($source_size != $destination_size || $source_date != $destination_date) {
			if (copy($source, $destination)) {
				touch($destination, $source_time);
				AION_ECHO("COPIED $source to $destination");
				$retrieve = TRUE;
				++$retrieved;
			}
			else {
				$error = error_get_last();
				AION_ECHO("WARN! ". $error['message'] . " ~ $source to $destination");
				++$errors;
			}
		}
		else {
			++$skipped;
		}
	}

	/* SWORD unpack */
	if ($data[C_FLAG]=='SWORD') {
		if (!preg_match("/\.SWORD\.zip$/", $destination)) {
			AION_ECHO("ERROR! FLAG=SWORD, but Sword file extension not found: $destination");
		}
		$unpack = str_replace('.zip','.txt',$destination);
		if ($retrieve || !file_exists($unpack)) {
			if ($source_size<10000) { AION_ECHO("WARN! TOO SMALL SWORD SOURCE FILE SIZE: $source_size, $source"); continue; }
			else if ($source_size<30000) { AION_ECHO("WARN! SMALL SWORD SOURCE FILE SIZE: $source_size, $source"); }
			system("unzip $destination -d /usr/share/sword");
			$module = basename(str_replace('.zip','',$source));
			system("mod2vpl $module 1 > $unpack");
			system("installmgr -u $module");
			if (file_exists($unpack)) {
				AION_LOOP_UNPACK_STAMP($bible, $source, $source_time, $unpack, $args);
			}
			else {
				AION_ECHO("SWORD UNPACK FAILED $unpack");
				++$errors;
			}
		}
	}
}


/*** diff and done ***/
AION_LOOP_DIFF( DESTINATION, PRODUCTION, DIFFERENCE, '', '', '', '', 'removexml+' );
AION_LOOP_COPYRIGHT( DESTINATION, COPYRIGHT_S );
AION_LOOP_COPYRIGHT( PRODUCTION, COPYRIGHT_P );
AION_LOOP_DIFF( COPYRIGHT_S, COPYRIGHT_P, COPYRIGHT_D );
AION_ECHO("ALSO CHECK MANUAL BIBLES FOR CHANGES");
AION_ECHO("DONE! Skipped=$skipped  Retrieved=$retrieved  Errors=$errors");





/**
 * checksource() - fetch remote file size + modification date via HTTP.
 *
 * Backward compatible with the original signature; $error is new and optional,
 * so existing call sites keep working unchanged.
 *
 * Changes from the original:
 *   - Timeouts (the original had none: PHP defaults to ~300s, so one flaky
 *     host hangs the whole run until max_execution_time kills the script).
 *   - CURLOPT_FAILONERROR removed - it turned every 4xx/5xx into a silent
 *     false, discarding the status code you actually want.
 *   - curl_errno()/curl_error() surfaced via $error instead of being dropped.
 *   - Retries with backoff on transient network/TLS failures.
 *   - CURLOPT_BINARYTRANSFER removed (no-op since PHP 5.1.3, REMOVED in 8.0
 *     where referencing it is a fatal "Undefined constant" error).
 *   - Uses CURLINFO_CONTENT_LENGTH_DOWNLOAD_T where available
 *     (CURLINFO_CONTENT_LENGTH_DOWNLOAD is deprecated as of PHP 8.4).
 *   - Falls back to parsing Content-Length / Last-Modified from the raw
 *     headers, then to a ranged GET parsing Content-Range, for servers that
 *     refuse HEAD or omit Content-Length.
 *
 * @return int HTTP status code, or 0 with $error explaining the failure.
 */
function checksource2($url, &$status, &$redirect, &$size, &$date, &$stime, &$error = null) {

    $status   = 0;
    $redirect = $url;
    $size     = 0;
    $date     = date("m/d/Y H:i:s");
    $stime    = time();
    $error    = '';

    // Transient conditions worth a retry. Anything else (bad URL, cert
    // rejected, too many redirects) will fail identically on attempt two.
    $retryable = array(
        CURLE_COULDNT_RESOLVE_HOST,  // 6
        CURLE_COULDNT_CONNECT,       // 7
        CURLE_OPERATION_TIMEOUTED,   // 28  <- what ebible.org is throwing
        CURLE_SSL_CONNECT_ERROR,     // 35
        CURLE_SEND_ERROR,            // 55
        CURLE_GOT_NOTHING,           // 52
        CURLE_RECV_ERROR,            // 56
    );

    $attempts = 3;

    for ($try = 1; $try <= $attempts; $try++) {

        $r = checksource_request($url, false);   // HEAD

        // HEAD refused, or answered without a usable length -> one ranged GET.
        if ($r['errno'] === 0 && ($r['status'] >= 400 || $r['length'] <= 0)) {
            $g = checksource_request($url, true);
            if ($g['errno'] === 0 && $g['length'] > 0) {
                $r = $g;
            }
        }

        if ($r['errno'] === 0) {
            $status   = $r['status'];
            $redirect = $r['url'];
            $size     = $r['length'];
            if ($r['mtime'] > 0) {
                $stime = $r['mtime'];
                $date  = date("m/d/Y H:i:s", $r['mtime']);
            }
            if ($try > 1) {
                $error = "recovered on attempt {$try}";
            }
            return $status;
        }

        $error = "curl errno {$r['errno']}: {$r['error']} (attempt {$try} of {$attempts})";

        if (!in_array($r['errno'], $retryable, true)) {
            break;
        }
        if ($try < $attempts) {
            sleep($try * 2);   // 2s, then 4s
        }
    }

    return $status;   // 0 - and $error now tells you why
}


/**
 * One request. $ranged=false issues a HEAD; true issues GET with Range: 0-0
 * and aborts as soon as the body starts, so nothing large is downloaded.
 */
function checksource_request($url, $ranged) {

    // Identify yourself honestly. Swap in your own domain and contact address:
    // sites that rate-limit are far more willing to whitelist a named client
    // than one pretending to be Chrome.
    $ua = 'AionianBible-linkcheck/1.0 (+https://AionianBible.org)';

    $headers = '';
    $ch      = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,          // collected via HEADERFUNCTION
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_FILETIME       => true,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_ENCODING       => 'identity',     // real byte count, not gzipped
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
            $headers .= $line;
            return strlen($line);
        },
    ));

    // Uncomment if IPv6 routing turns out to be part of the problem:
    // curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    if ($ranged) {
        curl_setopt($ch, CURLOPT_RANGE, '0-0');
        // If the server ignores Range and starts sending the whole file,
        // returning 0 here aborts immediately (surfaces as errno 23).
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) {
            return 0;
        });
    } else {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }

    curl_exec($ch);
    $errno = curl_errno($ch);

    // A deliberate write abort in ranged mode is success, not failure.
    if ($ranged && $errno === CURLE_WRITE_ERROR) {
        $errno = 0;
    }

    $out = array(
        'errno'  => $errno,
        'error'  => $errno ? curl_error($ch) : '',
        'status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'url'    => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
        'length' => 0,
        'mtime'  => 0,
    );

    if ($errno === 0) {

        $ft = curl_getinfo($ch, CURLINFO_FILETIME);
        if (is_numeric($ft) && $ft > 0) {
            $out['mtime'] = (int) $ft;
        }

        if ($ranged) {
            // curl reports 1 byte here; the true size is in Content-Range.
            $out['length'] = checksource_length_from_headers($headers);
        } else {
            $len = defined('CURLINFO_CONTENT_LENGTH_DOWNLOAD_T')
                 ? curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD_T)
                 : curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            if ($len > 0) {
                $out['length'] = (int) $len;
            } else {
                $out['length'] = checksource_length_from_headers($headers);
            }
        }

        if ($out['mtime'] <= 0) {
            $out['mtime'] = checksource_mtime_from_headers($headers);
        }
    }

    curl_close($ch);
    return $out;
}


/** With FOLLOWLOCATION the buffer holds every hop; only the last one counts. */
function checksource_last_block($raw) {
    $blocks = preg_split("/\r\n\r\n|\n\n/", trim($raw));
    return end($blocks);
}

function checksource_length_from_headers($raw) {
    $h = checksource_last_block($raw);
    // Ranged reply:  Content-Range: bytes 0-0/289088
    if (preg_match('#^content-range:\s*bytes\s+\S+/(\d+)#mi', $h, $m)) {
        return (int) $m[1];
    }
    if (preg_match('#^content-length:\s*(\d+)#mi', $h, $m)) {
        return (int) $m[1];
    }
    return 0;
}

function checksource_mtime_from_headers($raw) {
    $h = checksource_last_block($raw);
    if (preg_match('#^last-modified:\s*(.+)$#mi', $h, $m)) {
        $t = strtotime(trim($m[1]));
        if ($t !== false && $t > 0) {
            return $t;
        }
    }
    return 0;
}
