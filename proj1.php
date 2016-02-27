<?php

$sets = array( "help", "input:", "output:", "pretty-xml:", "details:", "search::" );
$options = getopt("", $sets);

var_dump($options);

if (array_key_exists("help", $options) && count($options) == 1){
	echo "--help              prints help\n";
	echo "--input-file        input text file with classes, if it's not specified stdin will be used instead\n";
	echo "--output-file       output text file in xml format, if it's not specified stdout will be used instead\n";
	echo "--pretty-xml        set witdh of indentation\n";
	echo "--details           instead of printing tree of inheritance script prints details about class, if it's not specified prints all classes\n";
	echo "--search            result of searching by XPATH\n";
}
else exit(1);

?>