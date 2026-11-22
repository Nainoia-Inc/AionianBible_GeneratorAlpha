#!/usr/local/bin/php
<?php
// Generate One PDF Proofer PDFs
require_once('./aion_common.php');
AION_ECHO("PDF ONE PROOFER GENERATION: BEGIN");
if (!chdir("../www-stageresources")) { AION_ECHO("ERROR! chdir()"); }

// BUILD PDFTK - ONE BY ONE
// pdftk A=in1.pdf cat A# output A.pdf
// pdftk A=in1.pdf... cat A B C ... output Final.pdf
$tempdir = "./temp.proofone";
if (is_dir($tempdir)) { system("rm -rf $tempdir"); }
if (!mkdir($tempdir)) { exit("Proofer failed to make temp one directory"); }
$counter = 1;
$countermax = 7;
$filesize = 0;
$filemax = 40000000;
$tag = "AA";
$temp1 = $temp2 = "";
$last1 = $last2 = "";
$files = glob('./Holy-Bible---*---Aionian-Edition.pdf');
if (empty($files)) { exit("No PDF files found.\n"); }
foreach($files as $file) {
	//setup
	$numb = "11";
	$filesize += filesize($file);
	// build queue
	$savet = $tag;
	$temp1 .= " $savet=$file";
	$temp2 .= " $savet$numb";
	$output = "$tempdir/$savet.pdf";
	// run queued
	if ($filesize >= $filemax || $counter >= $countermax) {
		system("pdftk $temp1 cat $temp2 output $output");
		$counter = 0;
		$filesize = 0;
		$temp1 = $temp2 = "";
		$last1 .= " $savet=$output";
		$last2 .= " $savet";
	}
	// increment
	$counter++;
	$tag++;
}
// run last
if (!empty($temp1)) {
	system("pdftk $temp1 cat $temp2 output $output");
	$last1 .= " $savet=$output";
	$last2 .= " $savet";
}
//run all
echo "pdftk $last1 cat $last2 output ./BIBLE-PROOF-ONE.pdf";
system("pdftk $last1 cat $last2 output ./BIBLE-PROOF-ONE.pdf");
system("rm -rf $tempdir");
return;
