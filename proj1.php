#!/usr/bin/php
<?php

class CPPClass {
	public $name = "";
	public $kind = "concrete";	# concrete/abstract
	public $privacy = "";		# privat/public/pretected

	private $inh;
	private $atributes;
	private $methods;

	function __construct(){
		$this->atributes = array();
		$this->methods = array();
		$this->inh = array();
	}

	function add_inheritance($attr){
		array_push($this->inh, $attr);
	}

	function add_attr($attr){
		array_push($this->atributes, $attr);
	}

	function add_method($method){
		array_push($this->methods, $method);
	}

	function print_dump(){
		var_dump($this);
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
	public $parsed_classes;		#array of parsed classes
	public $class;					#actual class
	private $input_string;
	private $tokens;
	
	private $index;
	private $used;
	private $actual_name;

	private $sets;
	private $options;
	
	function __construct(){
		$this->parsed_classes = array();
		mb_internal_encoding("UTF-8"); 
		mb_regex_encoding('UTF-8');

		$this->sets = array( "help", "input:", "output:", "pretty-xml:", "details:", "search::" );
		$this->options = getopt("", $this->sets);

		$this->index = 0;
		$this->used = 0;
		$this->actual_name = "";
		$this->actual_type = "";

		var_dump($this->options);
	}

	function add_class($attr){
		array_push($this->parsed_classes, $attr);
	}

	function read_input(){
		$this->input = "";
		if (array_key_exists("input", $this->options)){
			if (!($myfile = fopen($this->options["input"], "r")))
				return 2;
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
		$this->tokens = preg_split('/([\s:,;{}&\*])/iu', $this->input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$this->tokens = array_filter(array_map('trim', $this->tokens));
		$this->tokens = array_values($this->tokens);
	}

	function print_tokens(){
		var_dump($this->tokens);
	}

	function check_help(){
		if (array_key_exists("help", $this->options)){
			if (count($this->options) != 1)
				return 1;
			echo "--help              prints help\n";
			echo "--input-file        input text file with classes, if it's not specified stdin will be used instead\n";
			echo "--output-file       output text file in xml format, if it's not specified stdout will be used instead\n";
			echo "--pretty-xml        set witdh of indentation\n";
			echo "--details           instead of printing tree of inheritance script prints details about class, if it's not specified prints all classes\n";
			echo "--search            result of searching by XPATH\n";
			return -1;
		}
		else return 0;
	}

	function rec_parser($param){
		
		if ($this->used){
			$this->index++;
			$this->used = 0;
		}

		if (!array_key_exists($this->index, $this->tokens))return 1;
		$ret = 0;
		echo "$param\n";

		switch ($param){
			
			case "finding":
				$this->used = 1;
				if ($this->tokens[$this->index] == "class")
					if ($ret = $this->rec_parser("start"))return $ret;
				if ($ret = $this->rec_parser("finding"))return $ret;
				break;
			
			case "start":
				$this->class = new CPPClass();
				$this->add_class($this->class);
				if ($ret = $this->rec_parser("class_name"))return $ret;
				if ($ret = $this->rec_parser("inh"))return $ret;
				if ($ret = $this->rec_parser("body"))return $ret;
				if ($ret = $this->rec_parser("semicolon"))return $ret;
				break;

			case "inh":
				if ($this->tokens[$this->index] == ":"){
					if ($ret = $this->rec_parser("colon"))return $ret;
					if ($ret = $this->rec_parser("inh_name"))return $ret;
					if ($ret = $this->rec_parser("inh_next"))return $ret;
				}
				break;

			case "inh_next":
				if ($this->tokens[$this->index] == ","){
					if ($ret = $this->rec_parser("comma"))return $ret;
					if ($ret = $this->rec_parser("inh_name"))return $ret;
					if ($ret = $this->rec_parser("inh_next"))return $ret;
				}
				break;

			case "body":
				if ($this->tokens[$this->index] == "{"){
					if ($ret = $this->rec_parser("curly_left"))return $ret;
					$this->rec_parser("privacy");
					if (($ret = $this->rec_parser("virtual"))){
						if ($ret = $this->rec_parser("v_method"))return $ret;
					}
					else if ($ret = $this->rec_parser("statement"))return $ret;
					if ($ret = $this->rec_parser("curly_right"))return $ret;
				}
				break;

			case "statement":
				if ($ret = $this->rec_parser("type"))return $ret;
				if ($ret = $this->rec_parser("actual_name"))return $ret;
				if ($ret = $this->rec_parser("stmt_tail"))return $ret;
				if ($ret = $this->rec_parser("semicolon"))return $ret;
				break;

			case "stmt_tail":
				if ($this->tokens[$this->index] == ",")
					if ($ret = $this->rec_parser("var_next"))return $ret;
				else {
					if ($ret = $this->rec_parser("bracket_left"))return $ret;
					if ($ret = $this->rec_parser("args"))return $ret;
					if ($ret = $this->rec_parser("args_n"))return $ret;
					if ($ret = $this->rec_parser("bracket_right"))return $ret;
				}

				break;

			case "var_next":
				if ($this->tokens[$this->index] == ","){
					if ($ret = $this->rec_parser("comma"))return $ret;
					if ($ret = $this->rec_parser("actual_name"))return $ret;
					if ($ret = $this->rec_parser("var_next"))return $ret;
				}
				break;

			case "v_method":
				if ($ret = $this->rec_parser("type"))return $ret;
				if ($ret = $this->rec_parser("actual_name"))return $ret;
				if ($ret = $this->rec_parser("bracket_left"))return $ret;		#*
				if ($ret = $this->rec_parser("args"))return $ret;				#*
				if ($ret = $this->rec_parser("args_n"))return $ret;				#*
				if ($ret = $this->rec_parser("bracket_right"))return $ret;		#*
				if ($ret = $this->rec_parser("v_tail"))return $ret;				#*
				break;

			

			case "type":
				$this->used = 1;
				$this->actual_type .= $this->tokens[$this->index];
				if ($ret = $this->rec_parser("type_next"))return $ret;
				break;

			case "type_next":
				$this->used = 1;
				if ($this->tokens[$this->index] == "*"){
					$this->actual_type .= $this->tokens[$this->index];
					if ($ret = $this->rec_parser("*type_next"))return $ret;
					return 0;
				}
				else if ($this->tokens[$this->index] == "&"){
					$this->actual_type .= $this->tokens[$this->index];
					if ($ret = $this->rec_parser("&type_next"))return $ret;
					return 0;
				}
				break;

			case "*type_next":
				$this->used = 1;
				if ($this->tokens[$this->index] == "*"){
					$this->actual_type .= $this->tokens[$this->index];
					if ($ret = $this->rec_parser("*type_next"))return $ret;
					return 0;
				}

			case "&type_next":
				$this->used = 1;
				if ($this->tokens[$this->index] == "&"){
					$this->actual_type .= $this->tokens[$this->index];
					if ($ret = $this->rec_parser("&type_next"))return $ret;
					return 0;
				}

			case "privacy":
				if ($this->tokens[$this->index] == "public" || $this->tokens[$this->index] == "private" || $this->tokens[$this->index] == "protected"){
					$this->used = 1;
					return 0;
				}
				else return 1;
				break;

			case "actual_name":
				$this->used = 1;
				$this->actual_name = $this->tokens[$this->index];
				break;

			case "class_name":
				$this->used = 1;
				$this->class->name = $this->tokens[$this->index];
				break;

			case "inh_name":
				$this->used = 1;
				$this->class->add_inheritance($this->tokens[$this->index]);
				break;

			case "curly_left":
				$this->used = 1;
				if ($this->tokens[$this->index] == '{')return 0;
				else return 1;
				break;

			case "curly_right":
				$this->used = 1;
				if ($this->tokens[$this->index] == '}')return 0;
				else return 1;
				break;

			case "comma":
				$this->used = 1;
				if ($this->tokens[$this->index] == ',')return 0;
				else return 1;
				break;

			case "colon":
				$this->used = 1;
				if ($this->tokens[$this->index] == ':')return 0;
				else return 1;
				break;

			case "semicolon":
				$this->used = 1;
				if ($this->tokens[$this->index] == ';')return 0;
				else return 1;
				break;

			case "virtual":
				$this->used = 1;
				if ($this->tokens[$this->index] == 'virtual')return 1;
				else return 0;
				break;
		}
	}

	function parse(){
		$this->tokenize();
		$this->print_tokens();
		$ret = 0;
		if ($ret = $this->rec_parser("finding"))return $ret;
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
$ret = 0;

if ($ret = $parser->check_help()){
	if ($ret == -1)
		exit(0);
	else 
		exit($ret);
}

if ($ret = $parser->read_input())exit($ret);
$parser->print_input();
if ($ret = $parser->parse())exit($ret);
$parser->class->print_dump();
var_dump($parser->parsed_classes);

?>
