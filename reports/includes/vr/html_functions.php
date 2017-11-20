<?php

//=================
// HTML functions
//=================

// Functions ending in underscore return the HTML output value,
// functions not ending in underscore print the output.

// Plain text print, with newlines in HTML source

//========
$GLOBALS['html_functions_depth']=0; // track indentation depth for html code formatting, where possible; only works when html output functions are not embedded in each other.

function tb() { // return as many tabs as the depth counter requires and increment counter
	return str_repeat( "\t", ++$GLOBALS['html_functions_depth'] );
}

function tn() { // return as many tabs as the depth counter requires without changing counter
	return str_repeat( "\t", $GLOBALS['html_functions_depth'] );
}

function te() { //tagend - newline and decrement depth counter
	--$GLOBALS['html_functions_depth'];
	return "\n";
}

//==========

#function t($text) { print t_($text) ; }
#function t_($text) { return "$text\n" ; }
function t() { $args = func_get_args(); print t_($args); }
function t_() {
	$args = func_get_args();
	if (is_array($args[0])) $args = array( implode(" ", $args[0])) ;
	return tn() . implode("\n", $args) . "\n";
}


// HTML element shortcut/wrapper functions
function br($text="") { print br_($text) ; }
function br_($text="") { return tb() ."$text<br/>".te(); }

function hr() { print hr_(); }
function hr_() { return tb() ."<hr/>".te(); }

function h1($text) { print h1_($text); }
function h1_($text) { return hx_($text, 1); }
function h2($text) { print h2_($text); }
function h2_($text) { return hx_($text, 2); }
function h3($text) { print h3_($text); }
function h3_($text) { return hx_($text, 3); }
function h4($text) { print h4_($text); }
function h4_($text) { return hx_($text, 4); }
function hx($text, $n ) { print hx_($text, $n); }
function hx_($text, $n ) { return tb() ."<h$n>$text</h$n>".te(); }

#function p($text="") { print p_($text); }
#function p_($text="") { return tb() ."<p>$text</p>".te(); }
function p() { $args = func_get_args(); print p_($args); }
function p_() { $args = func_get_args(); 
	if (is_array($args[0])) $args = array( implode("</p>\n".tn()."<p>", $args[0])) ;
  return tn() . "<p>" . implode("</p>\n".tn()."<p>", $args) . "</p>\n";
}

function div($id="", $classes="", $text="" ) { print div_($id, $classes, $text ); }
function div_($id="", $classes="", $text="" ) {
       if ( is_array($classes) ) $div = tb() ."<div id='$id' class='". implode(" ", $classes) ."' >\n";
       else $div = tb() ."<div id='$id' class='$classes' >\n";
       // Only close the div if content is provided, otherwise an explicit divend() should be used.
       if ($text)  $div .= "$text".divend_();

       return $div;
}
function divend() { print divend_(); }
function divend_() { return tn() ."</div>".te(); };

function span($id="", $classes="", $text="" ) { print span_($id, $classes, $text ); }
function span_($id="", $classes="", $text="" ) {
       if ( is_array($classes) ) $span = tb() ."<span id='$id' class='". implode(" ", $classes) ."' >\n";
       else $span = tb() ."<span id='$id' class='$classes' >\n";
       // Only close the span if content is provided, otherwise an explicit spanend() should be used.
       if ($text)  $span .= "$text".spanend_();

       return $span;
}
function spanend() { print spanend_(); }
function spanend_() { return tn() ."</span>".te(); };

function fieldset($text) { print fieldset_($text); }
function fieldset_($text) { return tb() ."<fieldset><legend>$text</legend>\n"; }
function fieldsetend() { print fieldsetend_(); }
function fieldsetend_() { return tn() ."</fieldset>".te(); }

#function comment($text) { print comment_($text); }
#function comment_($text) { return tn() . "<!-- $text -->\n"; }

function comment() { $args = func_get_args(); print comment_($args); }
function comment_() { $args = func_get_args(); if (is_array($args[0])) $args = array( implode("	", $args[0])) ;  return tn() . "<!-- " . implode("	", $args) . " -->\n"; }

function script($src) { print script_($src); }
function script_($src){ return tb() ."<script  type='text/javascript' >". tb() .$src. tn() ."</script>".te(); }

function scriptsrc($src) { print scriptsrc_($src); }
function scriptsrc_($src){ return tb() ."<script  type='text/javascript' src='$src' ></script>".te(); }

function stylehref($href) { print stylehref_($href); }
function stylehref_($href) { return tb() ."<link rel='stylesheet' href='$href' type='text/css' media='all' />".te(); }
//=============

function debug(&$array) {
 print debug_($array);
}

function debug_(&$array) {
 return tb() ."<pre>". print_r($array, true) ."</pre>".te();
}

function expander($elementid, $type="blind") { print expander_($elementid, $type); }

function expander_($elementid, $type="blind") {
	include_once("expander_js.php");
       // $elementid is the id of the element that should be revealed/hidden when the expander symbol is clicked on.
		//style='border: 1px solid #202020 ; -moz-border-radius: 3px ; text-align: center; display: inline;' 
 $tag = <<<EOH
  <div 
		style='text-align: center; display: inline;' 
		class='expander'
		onclick=' showhide("$elementid", this, "$type"); '
	>
	<span class="expander-icon ui-icon ui-icon-triangle-1-e" style="float: left;" ></span>
	</div>
EOH;

	#span("","ui-icon ui-icon-triangle-1-e",></span>
 return $tag;
}

//=============
/**
* HTML Functions
tb
tn
te
t
t_
br
br_
hr
hr_
h1
h1_
h2
h2_
h3
h3_
h4
h4_
hx
hx_
p
p_
div
div_
divend
divend_
span
span_
spanend
spanend_
fieldset
fieldset_
fieldsetend
fieldsetend_
comment
comment_
script
script_
scriptsrc
scriptsrc_
stylehref
stylehref_
debug
debug_
expander
expander_
**/

?>
