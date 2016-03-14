#!/usr/bin/php
<?php

class CPPClass {
	public $name = "";
	public $kind = "concrete";	# concrete/abstract

	public $inh;
	public $atributes;
	public $methods;

	public $generated = 0;

	function __construct(){
		$this->atributes = array();
		$this->methods = array();
		$this->inh = array();
	}

	function add_inheritance($attr){
		array_push($this->inh, $attr);
	}

	function add_attr($attr){
		#array_push($this->atributes, $attr);
		$this->atributes[$attr->header->name] = $attr;
	}

	function method_exists($m){
		if (!array_key_exists($m->header->name, $this->methods))return -1;
		foreach ($this->methods[$m->header->name] as $key => $method) {
			if ($m->header->name == $method->header->name){
				if (count($m->arguments) != count($method->arguments))continue;
				foreach ($m->arguments as $key => $arg) {
					if ($arg->type != $method->arguments[$key]->type)break;
				}
				return $key;
			}
		}
		return -1;
	}

	function method_normalize(){
		foreach ($this->methods as $name => $polymorf) {
			foreach ($polymorf as $key => $method) {
				if (count($polymorf) < 2)break;
				foreach ($polymorf as $key2 => $method2) {
					if ($key != $key2){
						
						if (count($method->arguments) != count($method2->arguments))continue;
						
						$not_found = 0;
						foreach ($method->arguments as $key => $arg)
							if ($arg->type != $method2->arguments[$key]->type){
								$not_found = 1;
								break;
							}
						
						if ($not_found && $key > $key2)
							unset($polymorf[$key2]);
						else 
							if ($not_found && $key < $key2)
								unset($polymorf[$key]);
					}
				}
			}
		}
	}

	function add_method($method){
		if (!array_key_exists($method->header->name, $this->methods)){
			$this->methods[$method->header->name] = array();
		}
		array_push($this->methods[$method->header->name], $method);
		#array_push($this->methods, $method);
	}

	function print_dump(){
		var_dump($this);
	}
}

class ClassAttr {
	public $header;					# arg_tpl
	public $from_inh = "";			# name of class

	function add_inh($inh){
		array_push($this->from_inh, $inh);
	}
}

class ClassMethod {
	public $header;				# arg_tpl
	public $virtual = "";				# pure yes/no
	public $arguments;				# tupples name and type

	function __construct(){
		$this->arguments = array();
	}

	function add_arg($arg){
		array_push($this->arguments, $arg);
	}
}

class ArgTpl {
	public $name = "";
	public $type = "";
	public $scope = "instance";		# static/instance
	public $privacy = "private";	# private/public/pretected
}

function trim_value(&$value){ 
    $value = trim($value); 
}

class CLSParser {
	public $parsed_classes;		#array of parsed classes
	public $input_string;
	public $tokens;
	
	public $index;
	public $used;
	public $actual_name;
	public $actual_type;
	public $actual_privacy;

	public $class;					#actual class
	public $tuple;
	public $attr;
	public $method;

	public $sets;
	public $options;
	
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
		$this->actual_privacy = "private";

		var_dump($this->options);
	}

	function add_class($attr){
		#array_push($this->parsed_classes, $attr);
		$this->parsed_classes[$attr->name] = $attr;
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
		$this->tokens = preg_split('/([\(\)\s:,;{}&\*=])/iu', $this->input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$this->tokens = array_map('trim', $this->tokens);
		foreach ($this->tokens as $key => $value) 
			if ($value == "")unset($this->tokens[$key]);
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

		if (!array_key_exists($this->index, $this->tokens))return 0;
		$ret = 0;
		echo "expected = $param | get = " . $this->tokens[$this->index] . "\n";

		switch ($param){
			
			case "finding":
				$this->used = 1;
				if ($this->tokens[$this->index] == "class")
					if ($ret = $this->rec_parser("start"))return $ret;
				if ($ret = $this->rec_parser("finding"))return $ret;
				break;
			
			case "start":
				$this->class = new CPPClass();
				$this->actual_privacy = "private";
				if ($ret = $this->rec_parser("class_name"))return $ret;
				$this->add_class($this->class);
				if ($ret = $this->rec_parser("inh"))return $ret;
				if ($ret = $this->rec_parser("body"))return $ret;
				if ($ret = $this->rec_parser(";"))return $ret;
				break;

			case "inh":
				if ($this->tokens[$this->index] == ":"){
					if ($ret = $this->rec_parser(":"))return $ret;
					if (($this->rec_parser("privacy")))$this->actual_privacy = "";
					if ($ret = $this->rec_parser("inh_name"))return $ret;
					if ($ret = $this->rec_parser("inh_next"))return $ret;
				}
				break;

			case "inh_next":
				if ($this->tokens[$this->index] == ","){
					if ($ret = $this->rec_parser(","))return $ret;
					if (($this->rec_parser("privacy")))$this->actual_privacy = "";
					if ($ret = $this->rec_parser("inh_name"))return $ret;
					if ($ret = $this->rec_parser("inh_next"))return $ret;
				}
				break;

			case "body":
				if ($this->tokens[$this->index] == "{"){
					if ($ret = $this->rec_parser("{"))return $ret;
					if ($ret = $this->rec_parser("body_line"))return $ret;
					if ($ret = $this->rec_parser("}"))return $ret;					
				}
				break;

			case "body_line":
				if ($this->tokens[$this->index] == "}")return 0;
				$this->tuple = new ArgTpl();
				if (!($this->rec_parser("privacy")))if ($ret = $this->rec_parser(":"))return $ret;
				$this->tuple->privacy = $this->actual_privacy;
				if (!($ret = $this->rec_parser("static"))){
					$this->tuple->scope = "static";
					if ($ret = $this->rec_parser("statement"))return $ret;
				}
				else if (!($ret = $this->rec_parser("virtual"))){
					$this->method = new ClassMethod();
					$this->method->header = $this->tuple;
					$this->method->virtual = "no";
					if ($ret = $this->rec_parser("v_method"))return $ret;
				}
				else if (!($ret = $this->rec_parser("using"))){
					$this->attr = new ClassAttr();
					$this->attr->header = $this->tuple;
					if ($ret = $this->rec_parser("actual_name"))return $ret;
					$this->attr->from_inh = $this->actual_name;
					if ($ret = $this->rec_parser(":"))return $ret;
					if ($ret = $this->rec_parser(":"))return $ret;
					if ($ret = $this->rec_parser("actual_name"))return $ret;
					$this->tuple->name = $this->actual_name;
					$this->class->add_attr($this->attr);
					if ($ret = $this->rec_parser(";"))return $ret;
				}
				else {
					if ($ret = $this->rec_parser("statement"))return $ret;
				}
				if ($ret = $this->rec_parser("body_line"))return $ret;
				break;

			case "statement":
				if ($ret = $this->rec_parser("type"))return $ret;
				$this->tuple->type = $this->actual_type;
				if ($ret = $this->rec_parser("actual_name"))return $ret;
				$this->tuple->name = $this->actual_name;
				if ($ret = $this->rec_parser("stmt_tail"))return $ret;
				if ($ret = $this->rec_parser(";"))return $ret;
				break;

			case "stmt_tail":
				if ($this->tokens[$this->index] == ";"){
					$this->attr = new ClassAttr();
					$this->attr->header = $this->tuple;
					$this->class->add_attr($this->attr);
				}
				else if ($this->tokens[$this->index] == ","){
					$this->attr = new ClassAttr();
					$this->attr->header = $this->tuple;
					$this->class->add_attr($this->attr);
					if ($ret = $this->rec_parser("var_next"))return $ret;
				}
				else if ($this->tokens[$this->index] == "("){
					$this->method = new ClassMethod();
					$this->method->header = $this->tuple;
					$this->class->add_method($this->method);
					if ($ret = $this->rec_parser("("))return $ret;
					if ($ret = $this->rec_parser("args"))return $ret;
					if ($ret = $this->rec_parser(")"))return $ret;
					if (!($this->rec_parser("{")))if ($ret = $this->rec_parser("}"))return $ret;
				}
				break;

			case "var_next":
				if ($this->tokens[$this->index] == ","){
					if ($ret = $this->rec_parser(","))return $ret;
					$this->attr = new ClassAttr();
					$this->attr->header = clone $this->tuple;
					$this->class->add_attr($this->attr);
					if ($ret = $this->rec_parser("actual_name"))return $ret;
					$this->attr->header->name = $this->actual_name;
					if ($ret = $this->rec_parser("var_next"))return $ret;
				}
				break;

			case "v_method":
				if ($ret = $this->rec_parser("type"))return $ret;
				$this->tuple->type = $this->actual_type;
				if ($ret = $this->rec_parser("actual_name"))return $ret;
				$this->tuple->name = $this->actual_name;
				$this->class->add_method($this->method);
				if ($ret = $this->rec_parser("("))return $ret;		
				if ($ret = $this->rec_parser("args"))return $ret;
				if ($ret = $this->rec_parser(")"))return $ret;
				if ($ret = $this->rec_parser("v_tail"))return $ret;
				if ($ret = $this->rec_parser(";"))return $ret;
				break;

			case "v_tail":
				if ($this->tokens[$this->index] == "="){
					if ($ret = $this->rec_parser("="))return $ret;
					if ($ret = $this->rec_parser("0"))return $ret;
					$this->method->virtual = "yes";
					$this->class->kind = "abstract";
				}
				break;

			case "args":
				if ($this->tokens[$this->index] == ")")return 0;
				if (!($ret = $this->rec_parser("void")))return 0;
				$this->tuple = new ArgTpl();
				$this->tuple->scope = "";
				$this->tuple->privacy = "";
				$this->method->add_arg($this->tuple);
				if ($ret = $this->rec_parser("type"))return $ret;
				$this->tuple->type = $this->actual_type;
				if ($ret = $this->rec_parser("actual_name"))return $ret;
				$this->tuple->name = $this->actual_name;
				if ($ret = $this->rec_parser("args_n"))return $ret;
				break;

			case "args_n":
				if ($this->tokens[$this->index] == ","){
					if ($ret = $this->rec_parser(","))return $ret;
					$this->tuple = new ArgTpl();
					$this->tuple->scope = "";
					$this->tuple->privacy = "";
					$this->method->add_arg($this->tuple);
					if ($ret = $this->rec_parser("type"))return $ret;
					$this->tuple->type = $this->actual_type;
					if ($ret = $this->rec_parser("actual_name"))return $ret;
					$this->tuple->name = $this->actual_name;
					if ($ret = $this->rec_parser("args_n"))return $ret;	
				}
				break;

			case "type":
				$this->used = 1;
				$this->actual_type = "";
				$this->actual_type .= $this->tokens[$this->index];
				if ($ret = $this->rec_parser("type_next"))return $ret;
				break;

			case "type_next":
				if ($this->tokens[$this->index] == "*"){
					$this->actual_type .= " " . $this->tokens[$this->index];
					$this->used = 1;
					if ($ret = $this->rec_parser("*type_next"))return $ret;
					return 0;
				}
				else if ($this->tokens[$this->index] == "&"){
					$this->actual_type .= " " . $this->tokens[$this->index];
					$this->used = 1;
					if ($ret = $this->rec_parser("&type_next"))return $ret;
					return 0;
				}
				break;

			case "*type_next":
				if ($this->tokens[$this->index] == "*"){
					$this->used = 1;
					$this->actual_type .= $this->tokens[$this->index];
					if ($ret = $this->rec_parser("*type_next"))return $ret;
					return 0;
				}
				break;

			case "&type_next":
				if ($this->tokens[$this->index] == "&"){
					$this->used = 1;
					$this->actual_type .= $this->tokens[$this->index];
					if ($ret = $this->rec_parser("&type_next"))return $ret;
					return 0;
				}
				break;

			case "privacy":
				if ($this->tokens[$this->index] == "public" || $this->tokens[$this->index] == "private" || $this->tokens[$this->index] == "protected"){
					$this->used = 1;
					$this->actual_privacy = $this->tokens[$this->index];
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
				$this->class->add_inheritance(array("privacy" => $this->actual_privacy, "name" => $this->tokens[$this->index]));
				break;

			default :
				if ($this->tokens[$this->index] == $param){
					$this->used = 1;
					return 0;
				}
				else return 1;
				break;
		}
	}

	function gen_class($class){
		if($class->generated)return;
		
		$class->method_normalize();
		
		foreach ($class->inh as $index => $inh_class){
			$class_from = $this->parsed_classes[$inh_class["name"]];
			$this->gen_class($class_from);

			foreach ($class_from->atributes as $key => $value){
				if (!array_key_exists($key, $class->atributes)){
					$class->atributes[$key] = clone $value;
					$class->atributes[$key]->header = clone $value->header;
					if ($inh_class["privacy"] != "")$class->atributes[$key]->header->privacy = $inh_class["privacy"];
				}
			}

			foreach ($class_from->methods as $key => $polymorf){
				foreach ($polymorf as $index => $method){
					$ret = 0;
					if (($ret = $class->method_exists($method)) == -1){
						$tmp = clone $method;
						$tmp->header = clone $method->header;
						$class->add_method($tmp);
						if ($inh_class["privacy"] != "")$tmp->header->privacy = $inh_class["privacy"];
					}


				}
			}
		}
		$class->generated = 1;
	}

	function generate(){
		foreach ($this->parsed_classes as $name => $class){
			$this->gen_class($class);
		}
	}

	function parse(){
		$this->tokenize();
		$this->print_tokens();
		$ret = 0;
		if ($ret = $this->rec_parser("finding"))return $ret;
		if ($ret = $this->generate())return $ret;
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
var_dump($parser->parsed_classes);

?>
