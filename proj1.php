<?php

class CPPClass {
	private $name = "";
	private $kind = "concrete";	# concrete/abstract
	private $privacy = "";		# privat/public/pretected
	private $atributes;
	private $methods;

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

class ClassAttr {
	private $header;				# arg_tpl
	private $scope = "";			# static/instance
	private $from_inh;				# class

	function __construct($header, $scope){
		$this->header = $header;
		$this->scope = $scope;
		$this->from_inh = array();
	}

	function add_inh($inh){
		array_push($this->from_inh, $inh);
	}
}

class ClassMethods {
	private $header;				# arg_tpl
	private $scope = "";			# static/instance
	private $virtual;				# pure yes/no
	private $arguments;				# tupples name and type

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

class ArgTpl {
	private $name = "";
	private $type = "";

	function __construct($name, $type){
		$this->name = $name;
		$this->type = $type;
	}
}

class CLSParser {
	private $parsed_classes;		#array of parsed classes
	private $class;					#actual class
	private $input_string;
	private $tokens;

	private $sets;
	private $options;
	
	function __construct(){
		$parsed_classes = array();
		mb_internal_encoding("UTF-8"); 
		mb_regex_encoding('UTF-8');

		$this->sets = array( "help", "input:", "output:", "pretty-xml:", "details:", "search::" );
		$this->options = getopt("", $this->sets);
		var_dump($this->options);
	}

	function read_input(){
		$this->input = "";
		if (array_key_exists("input", $this->options)){
			$myfile = fopen($this->options["input"], "r") or exit(2);
			$this->input = fread($myfile,filesize($this->options["input"]));
			fclose($myfile);
		}
		else {
			while ($line = fgets(STDIN))
			$this->input .= $line; 
		}
	}

	function print_input(){
		var_dump($this->input);
	}

	function tokenize(){
		$this->tokens = preg_split('/([\s:,;{}])/iu', $this->input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$this->tokens = array_filter(array_map('trim', $this->tokens));
	}

	function print_tokens(){
		var_dump($this->tokens);
	}

	function parse(){
		if (array_key_exists("help", $this->options)){
			if (count($options) != 1)
				exit(1);
			echo "--help              prints help\n";
			echo "--input-file        input text file with classes, if it's not specified stdin will be used instead\n";
			echo "--output-file       output text file in xml format, if it's not specified stdout will be used instead\n";
			echo "--pretty-xml        set witdh of indentation\n";
			echo "--details           instead of printing tree of inheritance script prints details about class, if it's not specified prints all classes\n";
			echo "--search            result of searching by XPATH\n";
		}
		
		$this->tokenize();
		$this->print_tokens();
	}
}


#$abcd = new class_methods(new arg_tpl("abc", "int"), "instance", false, "");

#echo $abcd->header->name;
#$regexp = "class\s+[^\W\d][\w]*[\s]*([:][\s]*[^\W\d][\w]*([\s]*[,][\s]*[^\W\d][\w]*)*)?[\s]*({(\s*|.*)});";
#echo $input;
#$parsed = preg_split('/([\s:,;{}])/iu', $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

#$parsed = array_filter(array_map('trim', $parsed));

#var_dump($parsed);

$parser = new CLSParser();
$parser->read_input();
$parser->print_input();
$parser->parse();

?>
