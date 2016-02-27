<?php

class cpp_class {
	public $name = "";
	public $kind = "concrete";	# concrete/abstract
	public $privacy = "";		# privat/public/pretected
	public $atributes;
	public $methods;

	function __construct($name, $kind, $privacy){
		$this->name = $name;
		$this->kind = $kind;
		$this->privacy = $privacy;

		$this->atributes = array();
		$this->methods = array();
	}

	function add_attr($attr){
		array_push($this->atributes, $attr);
	}

	function add_method($method){
		array_push($this->methods, $method);
	}
}

class class_attr {
	public $header;				# arg_tpl
	public $scope = "";			# static/instance
	public $from_inh;			# class

	function __construct($header, $scope){
		$this->header = $header;
		$this->scope = $scope;
		$this->from_inh = array();
	}

	function add_inh($inh){
		array_push($this->from_inh, $inh);
	}
}

class class_methods {
	public $header;				# arg_tpl
	public $scope = "";			# static/instance
	public $virtual;			# pure yes/no
	public $arguments;			# tupples name and type

	function __construct($header, $scope, $virtual, $purity){
		$this->header = $header;
		$this->scope = $scope;

		if ($virtual)
			$this->virtual = $purity;
		else
			$this->virtual = "";

		$this->arguments = array();
	}

	function add_arg($arg){
		array_push($this->arguments, $arg);
	}
}

class arg_tpl {
	public $name = "";
	public $type = "";

	function __construct($name, $type){
		$this->name = $name;
		$this->type = $type;
	}
}

$sets = array( "help", "input:", "output:", "pretty-xml:", "details:", "search::" );
$options = getopt("", $sets);

var_dump($options);

if (array_key_exists("help", $options)){
	if (count($options) != 1)
		exit(1);
	echo "--help              prints help\n";
	echo "--input-file        input text file with classes, if it's not specified stdin will be used instead\n";
	echo "--output-file       output text file in xml format, if it's not specified stdout will be used instead\n";
	echo "--pretty-xml        set witdh of indentation\n";
	echo "--details           instead of printing tree of inheritance script prints details about class, if it's not specified prints all classes\n";
	echo "--search            result of searching by XPATH\n";
}

$input = "";

if (array_key_exists("input", $options)){
	$myfile = fopen($options["input"], "r") or exit(2);
	$input = fread($myfile,filesize($options["input"]));
	fclose($myfile);
}
else {
	while ($line = fgets(STDIN))
		$input .= $line; 
}

$abcd = new class_methods(new arg_tpl("abc", "int"), "instance", false, "");

echo $abcd->header->name;
?>