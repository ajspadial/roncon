<?php

class Translations {
	private $source_to_target;
	private $target_to_source;

	private $translations;
	private $path_map;

	public function __construct($translations, $path_map) {
		$this->translations = $translations;
		$this->path_map = $path_map;
		$this->source_to_target['none'] = function($s) {return $s;};
		$this->source_to_target['map'] = $this->build_map('source', 'target');
		$this->target_to_source['none'] = $source_to_target['none'];
		$this->target_to_source['map'] = $this->build_map('target', 'source');
	}

	private function build_map($key_field, $value_field) {
		return function($s) {
			foreach($this->path_map as $p) {
				if ($p->$key_field === $s) {
					return $p->$value_field;
				}
			}
		};
	}

	public function encode($s, $index) {
		$function = $this->translations[$index];
		return $this->source_to_target[$function]($s);
	}

	public function decode($s, $index) {
		$function = $this->translations[$index];
		return $this->target_to_source[$function]($s);
	}
}

class Source {
	private $blobs;
	private $read_mask;
	private $write_mask;

	public function __construct($mask) {
		$this->read_mask = $mask[0];
		$this->write_mask = $mask[1];
	}

	public function read_blobs($request_uri) {
		preg_replace_callback($this->read_mask,
			function($blobs) {
				$this->blobs = [];
				for ($i = 1; $i < count($blobs); $i++) {
					$this->blobs[] = $blobs[$i];
				}
				return $blobs[0];
			},
		$request_uri);
		return $this->blobs;
	}

	public function create_instance($blobs) {
		$aux = $this->write_mask;
		for ($i = 0; $i < count($blobs); $i++) {
			$aux = str_replace("$" . $i++, $blobs[--$i], $aux);
		}
		return $aux;
	}
}

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

$config = json_decode(file_get_contents('config.json'));
$requestURI = $_SERVER['REQUEST_URI'];
$uri = 'article/2015/05/20/1/writing-a-shortener';
$target = new Source($config->target_mask);
//$target = new Source(['/(article)\/([0-9]{4}\/[0-9]{2}\/[0-9]{2}\/[1-9]+)/', '']);
$t = mktime(0, 0, 0, 5, 19, 2015);
$requestURI = 't/' . num_to_sxg($t, 20) . '/1/';
print($requestURI . "\n");

$source = new Source($config->source_mask);
$translator = new Translations($config->translations, $config->path);

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
