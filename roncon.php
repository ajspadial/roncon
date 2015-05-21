<?php

function strcat($a, $b) {
	return $a . $b;
}

function num_to_sxg($n) {
 $s = "";
 $m = "0123456789ABCDEFGHJKLMNPQRSTUVWXYZ_abcdefghijkmnopqrstuvwxyz";
 if ($n===undefined || $n===0) { return 0; }
 while ($n>0) {
   $d = $n % 60;
   $s = strcat($m[$d],$s);
   $n = ($n-$d)/60;
 }
 return $s;
}

function num_to_sxgf($n, $f) {
 $s = num_to_sxg($n);
 if ($f===undefined) { 
   $f=1; 
 }
 $f -= strlen($s);
 while ($f > 0) { 
   $s = strcat("0",$s); 
   --$f; 
 }
 return $s;
}

function sxg_to_num($s) {
 $n = 0;
 $j = strlen($s);
 for ($i=0;$i<$j;$i++) { // iterate from first to last char of $s
   $c = ord($s[$i]); //  put current ASCII of char into $c  
   if ($c>=48 && $c<=57) { $c=$c-48; }
   else if ($c>=65 && $c<=72) { $c-=55; }
   else if ($c==73 || $c==108) { $c=1; } // typo capital I, lowercase l to 1
   else if ($c>=74 && $c<=78) { $c-=56; }
   else if ($c==79) { $c=0; } // error correct typo capital O to 0
   else if ($c>=80 && $c<=90) { $c-=57; }
   else if ($c==95) { $c=34; } // underscore
   else if ($c>=97 && $c<=107) { $c-=62; }
   else if ($c>=109 && $c<=122) { $c-=63; }
   else { $c = 0; } // treat all other noise as 0
   $n = 60*$n + $c;
 }
 return $n;
}

function decode_path($encoded_path) {
	$aux = sxg_to_num($encoded_path);
	return date('Y/m/d', $aux);
}

$requestURI = $_SERVER['REQUEST_URI'];
$t = mktime(0, 0, 0, 5, 19, 2015);
$requestURI = 't/' . num_to_sxg($t, 20) . '/1/';
print($requestURI . "\n");

$config = json_decode(file_get_contents('config.json'));

$request_blobs = explode('/', $requestURI);

$type = $request_blobs[0];
$type_path = "";
foreach ($config->paths as $p) {
	if ($p->source == $type) {
		$type_path = $p->target;
		break;
	}
}

$encoded_path = $request_blobs[1];

$article_number = $request_blobs[2];

$target_path = decode_path($encoded_path);

$target = "$type_path/$target_path/$article_number";

print($target);
