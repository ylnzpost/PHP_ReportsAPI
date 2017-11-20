<?php

// 2007/02/13  Robin CJ

// User selects VR2 environment and script gets organisations
// User selects organisation and it gets the document types OR repositories
// User then selects document types/reps, period and whether to display grand totals
// Script gets the total number of documents loaded and bytes (if selected) 
// for each doc type or repository for this period, and totals since beginning (if selected).
//
// Yes it might have been clearer to break this script into mutliple scripts
// but it seemed more efficient and concise to re-use the same script, running
// different functions depending on the input.


//===========================
// Default/Constant variables
//===========================

$org = "NOTSET";
$search = "NOTSET" ;

$self = dirname($_SERVER['PHP_SELF'])."/".basename(__FILE__) ;

$ruser = "servreport" ;

$doccountscript = "vrdoctype_report.pl";

$GLOBALS[doctotal] = 0;
$GLOBALS[docperiodtotal] = 0;
$GLOBALS[bytestotal] = 0;
$GLOBALS[periodbytes] = 0;
$rdts;

//===========================
// Session + includes
//===========================

session_start();

include_once("includes/update_remote_file.php");
#include("vrenvironments.php");
include("includes/vr/vrenvironments.php");

//===========================
// MAIN
//===========================

// See if this session is still active.
if ( $_SESSION[active] !=  true && isset($_GET[action]) && $_GET[action] != "serverselect" ){
  echo "\n<span class='error'> Sorry, this session has expired, please refresh the page and start again</br/> </span>\n\n";
  exit;
}

if ( $_GET[action] == "doccount" ){
  // Need to reset checkbox info held in $_SESSION
  unset($_SESSION[totals]);
  unset($_SESSION[bytes]);
}

foreach ( array_keys($_GET) as $key ){
  $_SESSION[$key] = $_GET[$key];
}

if ( isset($_GET[reporttype]) ){ $reporttype=$_GET[reporttype] ; }
else $reporttype = "repository";

if ( $_GET[action] == "serverselect" ) { Select_Env(); }
elseif ( $_GET[action] == "orgselect" ) { Select_Org(); }
elseif ( $_GET[action] == "fieldselect" ) { Select_Doctype($reporttype); }
elseif ( $_GET[action] == "doccount" ) { Get_Doctype_Counts($reporttype); }
else {
  HTML_Header(); 
  HTML_Footer();
};

HTML_Footer();

// Set session active flag to true so we can detect whether 
// this session is alive next time I run
$_SESSION[active] = true;


///////////////
// FUNCTIONS //
///////////////

function Select_Env() {
  global $envs;

#  if ( $_SESSION[active] !=  true ){
#    echo "\n<span class='error'> Sorry, this session has expired, please refresh the page and start again</br/> </span>\n\n";
#    exit;
#  }

  $url = "action=orgselect";

  $onclick = "javascript: clearDivs('ajax3', 'ajax4');
    var params = getFormParms( 'serverselect' ) + '&$url';
    ajaxLoad( 'ajax2', 'serverselect', '$url' )";

  echo <<<ENDHTML
       <fieldset><legend> Select VR2 Environment </legend>
       <form id="serverselect" >
       <select name="env" > 

ENDHTML;

  foreach ( array_keys($envs) as $env ) {
		$host = $envs[$env]['dathost'];
	  $dbname = "vretrieve";
  	if ( isset($envs[$env]['dbname']) ) $dbname = $envs[$env]['dbname'];

    echo "<option  value='$env' > $env ($host, $dbname) </OPTION> <BR>\n";
  }

  echo <<<ENDHTML
  
        </select>
	<br/>
	<table>
	<!-- <tr><td>VRetrieve DB Name:</td><td><input type="text" name="dbname" value="vretrieve" /></td></tr> -->

	<tr><td>DB login:</td><td><input type="text" name="user" /></td></tr>

	<tr><td>DB password:</td><td><input type="password" name="password" />
	<input type="button" onClick="$onclick" value="Go" /><br/>
	</td></tr>

	</table>

	
      </fieldset>
    </form>
   
ENDHTML;

}

//=============

function Select_Doctype($reporttype) {
  global $ruser;
  global $doccountscript;
	global $envs;
	$env = $_SESSION['env'];
  $rhost = $envs[$env]['dathost'];
  $org = $_SESSION[org];
	$dbname = "vretrieve";
	if ( isset($envs[$env]['dbname']) ) $dbname = $envs[$env]['dbname'];
  $dbpass = $_SESSION[password];
  $dbuser = $_SESSION[user];

  echo <<<ENDHTML

    <form id="dtselect">
ENDHTML;


  $url = "action=doccount&reporttype=$reporttype&host=$rhost&org=$org&dbname=$dbname&user=$dbuser&dbpass=$dbpass";

  $onclick = "javascript: clearDivs('ajax4', 'ajax5');
    var params = getFormParms( 'dtselect' ) + '&$url';
    ajaxLoad( 'ajax4', 'dtselect', '$url' )";

  Select_Daterange();

  echo <<<EOH

    <fieldset>
    <legend> Select Display Options </legend>
    Display $reporttype totals for all time up to end time<input type="checkbox" name="totals" > <br/>
    Display number of bytes used by documents (according to database) <input type="checkbox" name="bytes" > <br/>
EOH;

  if ( preg_match("/all/i",$org) ) {
    echo <<<EOH
    
    <ul><li>
      All organisations selected so assuming every ${reporttype} also required for report.
    </li></ul>

    </fieldset>
    <input type="hidden" name="doctype[]" value="all" >
    <input type="button" onClick="$onclick" value="Get Counts" /><br/>
   </form>
EOH;

    return;
  }

  // Otherwise, if not ALL orgs selected, then display dt/repository list
  echo <<<EOH
    </fieldset>

    <fieldset><legend> Select VR2 $env $dbname $rhost $org $reporttype names </legend>

EOH;

  // Get list of doctypes/reps for this org in the remote db
  if ( $reporttype == "doctype" ){ 
    $listoption = "-listtypes";
		$process_line = "Load_rdts_array_with_doctypes";
  }
  else {
		$listoption = "-listrepositories";
  	$process_line = "Load_rdts_array_with_reps";
	}

  $command = "/tmp/$doccountscript -db $dbname -user $dbuser -password \"$dbpass\" -org \"$org\" $listoption ";
  #$process_line = "Print_Doc_Checkbox";

  $retval = update_remote_file( "$doccountscript", $rhost, $ruser, "/tmp", "executable" );

  if ( $retval != 0 ){
    echo "Problem pushing file ./$doccountscript to  $rhost as $ruser\n";
    exit;
  }

  Run_remote_command($command, $rhost, $ruser, $process_line);

	global $rdts;
	Sort_rdts_array($rdts);

  if ( $reporttype == "doctype" ){ Print_Doc_Checkboxes($rdts); }
	else { Print_Rep_Checkboxes($rdts); }

  echo <<<ENDHTML
		<br/>
    <input type="button" onClick="$onclick" value="Get Counts" /><br/>
   </form>
ENDHTML;

}

//=============

function Load_rdts_array_with_doctypes( $line, $count ) {
  $line = rtrim($line);

  $array = explode("|",$line);
  $dtid = $array[0];
  $dtname = $array[1];
	$rid = $array[2];
	$rname = $array[3];

	global $rdts ;
	#$rdts[$rname]['rid'] = $rid;
	#$rdts[$rname]['dt'][$dtname] = $dtid;
	$rdts[$rid]['rname'] = $rname;
	$rdts[$rid]['dt'][$dtid] = $dtname;

}

//=============

function Load_rdts_array_with_reps( $line, $count ) {
  $line = rtrim($line);

  $array = explode("|",$line);
	$rid = $array[0];
	$rname = $array[1];

	global $rdts ;
	$rdts[$rid]['rname'] = $rname;

}

//=============

function Sort_rdts_array(&$rdts) {
	// Sort rdts array by repository name and sort sub-dimension doc type by dt name.
	
	$reps;
	$rdts2;
	foreach ( $rdts as $rid => $value ) {
		asort($rdts[$rid]['dt']); // sort doc types by name
		$reps[$rid] = $value['rname'];
	}

	// Sort reps by rname
	asort($reps);
	foreach ( $reps as $rid => $rname ){
		$rdts2["$rid"]['rname'] = $rname;
		$rdts2["$rid"]['dt'] = $rdts[$rid]['dt'];
	}

	$rdts = $rdts2;
	
} 

//=============

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}


//=============

function Print_Doc_Checkboxes( &$rdts ){

	print <<<EOH
		Select All<input type='checkbox' onClick="checkBoxToggleAllByClass(this, 'dtcheckbox' )" checked="" /><br/>
EOH;

	#foreach ( array_keys($rdts) as $key ) {
	#	asort($rdts[$key]['dt']); // sort doc types by name
	#}

	#foreach ( array_keys($rdts) as $rname ) {
	foreach ( array_keys($rdts) as $rid ) {
		#$rid = $rdts[$rname]['rid'];
		$rname = $rdts[$rid]['rname'];
		print <<<EOH

			<h4>Doctypes for Repository: $rname (ID: $rid)
				<input type='checkbox' class='dtcheckbox' onClick="checkBoxToggleAllByClass(this, 'r$rid' )" checked="" /><br/>
			</h4>
EOH;

		#foreach ( $rdts[$rname]['dt'] as $dtname => $dtid ) {
		foreach ( $rdts[$rid]['dt'] as $dtid => $dtname ) {
			print <<<EOH

			 <input type="checkbox" class="r$rid dtcheckbox" name="doctype[]" value=$dtid checked="" /> $dtname (ID: $dtid)<br/>

EOH;
		}
	}

}

//=============

function Print_Rep_Checkboxes( $rdts ){

	asort($rdts);

	print <<<EOH
		Select All<input type='checkbox' onClick="checkBoxToggleAllByName(this, 'doctype[]' )" checked="" /><br/>
EOH;
	#foreach ( array_keys($rdts) as $rname ) {
	#foreach ( $rdts as $rid => $rname ) {
	foreach ( array_keys($rdts) as $rid ) {
		#$rid = $rdts[$rname]['rid'];
		$rname = $rdts[$rid]['rname'];
		print <<<EOH

			 <input type="checkbox" name="doctype[]" value=$rid checked="" /> $rname (ID: $rid)<br/>

EOH;
	}

}

//=============

function Print_Doc_Checkbox( $line, $count ){
  // Prints the doctype or repository checkboxes all with the same name so they should
  // be treated as an html array.
  $line = rtrim($line);

  $array = explode("|",$line);
  $dtid = $array[0];
  $dtname = $array[1];
	$rid = $array[2];
	$rname = $array[3];

	global $rdts;
	#$rdts[$rname]['rid'] = $rid;
	#$rdts[$rname]['dt'][$dtname] = $dtid;
	$rdts[$rid]['rname'] = $rname;
	$rdts[$rid]['dt'][$dtid] = $dtname;

  echo <<<EOH

    <input type="checkbox" name="doctype[]" value=$dtid checked="" /> $dtname (ID: $dtid), Rep: $rname (ID: $rid)<br/>

EOH;

}

//=============

function Select_Daterange(){

  # Define default dates
  $start = date("d/m/Y", strtotime("yesterday") ) ;
  $end = date("d/m/Y", strtotime("now") ) ;

  echo <<<EOH

    <img src="x.gif" width=0 height=0 style="display: none;" onError="javascript: load_calendars();" >

    <fieldset><legend>Select Date Range</legend>
      Dates are inclusive of the whole day.
    <table>
    <tr><td>
    Period start: </TD>
    <TD><INPUT id=cal1_container type=text name=start value="$start" ></TD>
    </TR>

    <TR>
    <TD>Period end:</TD>
    <TD><INPUT id=cal2_container type=text name=end value="$end" ></TD>
    </TR>
    </table>
    </fieldset>

EOH;


}


//=============

function Select_Org() {
	global $ruser;
	global $doccountscript;
	global $envs;
	$env = $_SESSION['env'];
  $rhost = $envs[$env]['dathost'];
  $dbname = "vretrieve";
  if ( isset($envs[$env]['dbname']) ) $dbname = $envs[$env]['dbname'];

   $dbpass = $_SESSION[password];
   $dbuser = $_SESSION[user];


  // Don't rely on session vars because if the user has 2 windows open
  // to compare results then sessions can overlap each other.
  $url = "action=fieldselect&host=$rhost&dbname=$dbname&user=$dbuser&dbpass=$dbpass";

  $onclick = "javascript: clearDivs('ajax3', 'ajax4', 'ajax5');
    var params = getFormParms( 'orgselect' ) + '&$url';
    ajaxLoad( 'ajax3', 'orgselect', '$url' )";

  echo <<<ENDHTML
       <fieldset><legend> Select VR2 $env $rhost Organisation and Search Type</legend>
       <form id="orgselect" >
ENDHTML;


  // Get list of organisations in the remote db
  $command = "/tmp/$doccountscript -db $dbname -user $dbuser -password \"$dbpass\" -listorgs";
  $process_line = "Print_Org_Option";

  $retval = update_remote_file( "$doccountscript", $rhost, $ruser, "/tmp", "executable" );

  if ( $retval != 0 ){
    echo "Problem pushing file ./$doccountscript to  $rhost as $ruser\n";
    exit;
  }

  echo "<select name=org  >\n";
  echo "\n<option value='ALL' >All Organisations</option>\n";

  Run_remote_command($command, $rhost, $ruser, $process_line);

  echo <<<ENDHTML

    </select>
    <br/>
    Report counts by Document Type
    <input type="radio" name="reporttype" value="doctype">
    or Repository
    <input type="radio" name="reporttype" value="repository" checked="" >

    <input type="hidden" name="env" value="$env">
    <input type="button" onClick="$onclick" value="Go" /><br/>
    
   </form>
ENDHTML
;

  #global $start ; global $end ; 
  #if ( $org != "" && $org != "NOTSET" && $rhost != "NOTSET" ) { VR2_Batch($env,$rhost,$org,$start,$end); }

}

//=============

function Print_Org_Option( $line, $trash ){
  $line = rtrim($line);

  $array = explode("|",$line);
  $orglong = $array[1];
  $orgshort = $array[2];

  echo <<<EOH

    <option value="$orgshort" >$orglong ($orgshort)</option>

EOH;

}

//=============

function Get_Doctype_Counts($reporttype){
   global $ruser;
   global $doccountscript;
   #global $dtarray;
   $doctype = $_SESSION[doctype];

  global $envs;
  $env = $_SESSION['env'];
  $rhost = $envs[$env]['dathost'];
  $dbname = "vretrieve";
  if ( isset($envs[$env]['dbname']) ) $dbname = $envs[$env]['dbname'];

   $dbpass = $_SESSION[password];
   $dbuser = $_SESSION[user];
   $org = $_SESSION[org];
   $start = $_SESSION[start];
   $end = $_SESSION[end];

  // We want to display totals, ie. total counts since the beginning of time
  if ( isset($_SESSION[totals]) ){ $totals = "-totals"; } 

  // We want to display bytes, 
  if ( isset($_SESSION[bytes]) ){ 
    $bytes = "-bytes";
    if ( isset($_SESSION[totals]) ){ $totalbytestitle = "<th> Total Size </th>"; }
  }
  
  // Get count data
  $command = "/tmp/$doccountscript -db $dbname -user $dbuser -password \"$dbpass\" -org $org  -start \"$start\"  -end \"$end\" -category $reporttype $totals $bytes";

  // Doctype Ids should be provided via an html array doctype[]
  // except I can't quite get that to work, so split with comma delimiter
  if ( ! is_array( $doctype ) ) {
    $doctype =  explode(",", $doctype);
  }

  foreach ( $doctype as $dtid ){
    if ( preg_match( "/all/i", $dtid ) ){
      // Default to all ids
      break;
    }

    $ids = explode(",", $dtid);
    foreach ( $ids as $id ){
      $command = $command." -id $id ";
    }
  }

  echo <<<EOH
    <hr>
    <br/>
    <h1>VR2 Document Load Counts </h1>
    Grouped by organisation and $reporttype.
    Period: <i>$start</i> to <i>$end</i><br>
    Host: <i>$rhost</i><br>
    <br>

    <table>

EOH;

  $process_line = "Print_Doctype_Count"; 
  $retval = update_remote_file( "$doccountscript", $rhost, $ruser, "/tmp", "executable" );
  Run_remote_command($command, $rhost, $ruser, $process_line);

  // Print totals row for last organisation (otherwise it gets missed by
  // the process_line function.
  Print_Totals_Row();
}

//===================

function Print_Totals_Row() {

  $GLOBALS[bytestotal] = buildBytefield( $GLOBALS[bytestotal], "datatotal" );
  $GLOBALS[doctotal] = " <td class=\"datatotal\" >". $GLOBALS[doctotal] ." docs </td>" ;
  $GLOBALS[periodbytes] = buildBytefield( $GLOBALS[periodbytes], "datatotal" );

  if ( ! isset($_SESSION[totals]) ){
    $GLOBALS[doctotal] = "";
    $GLOBALS[bytestotal] = "";
  }

  if ( ! isset($_SESSION[bytes]) ){
    $GLOBALS[periodbytes] = "";
    $GLOBALS[bytestotal] = "";
  }

  echo <<<EOH
    <tr>
      <td></td><td><b>TOTALS:</b></td>
      <td class="datatotal" >$GLOBALS[docperiodtotal] docs</td>
      $GLOBALS[doctotal]
      $GLOBALS[periodbytes]
      $GLOBALS[bytestotal]
    </tr>

EOH;

  //Reset global totals
  foreach ( array('docperiodtotal', 'doctotal', 'periodbytes', 'bytestotal') as $var ){
    $GLOBALS[$var] = 0;
    echo "<!-- Resetting GLOBALS[$var] to ". $GLOBALS[$var] ."-->\n";
  }

}

//=================

function Print_Doctype_Count($line, $trash){
  
  global $reporttype;
  // input line format
  // orgid|org|repositoryid|repname|period doc total| doc total | period bytes total | bytes total
  // sample input:
  // 23|BNZ|191|Administrative Documents|11|71|895665|6927486

  // pipe delimited output
  $data = explode('|', $line );

  // Iterate through all 4 numeric vars and if any are blank then set them to 0
  foreach ( array(4,5,6,7) as $nfield ){
    if ( $data[$nfield] == '' ) $data[$nfield] = 0;
  }

  $orgid = $data[0];
  $orgname = $data[1];

  $id = $data[2];
  $dtname = $data[3];
  $dtperiodtotal = "<td class=\"datar\" > ".$data[4]." docs </td>";
  $dttotal = "<td class=\"datar\" > ".$data[5]." docs </td>";
  $dtperiodbytes = buildBytefield( $data[6], "datar" );
  $dttotalbytes  = buildBytefield( $data[7], "datar" );

  if ( ! isset($_SESSION[totals]) ){
    $dttotal = "";
    $dttotalbytes = "";
  }

  if ( ! isset($_SESSION[bytes]) ){
    $dtperiodbytes = "";
    $dttotalbytes = "";
  }


  // See if this is the same org as the last line

  if ( $GLOBALS[orgname] != $orgname ){

    // See if this is the first org, if not then close the preceding table
    if ( isset($GLOBALS[orgname]) && $GLOBALS[orgname] != "" ) {
      // This is not the first org
      Print_Totals_Row();
      echo " <!-- End of org ".$GLOBALS[orgname]." -->\n </table>\n\n";
    }

    // Set the global org so we can make this same comparison on the next iteration
    $GLOBALS[orgname] = $orgname;

    // Set totals header if required
    if ( isset($_SESSION[totals]) ) $totaltitle = "<th> Total Docs </th>";

    // Set bytes headers if required
    if ( isset($_SESSION[bytes]) ){
      $bytestitle = "<th> Period Size </th>";
      if ( isset($_SESSION[totals]) ) $totalbytestitle = "<th> Total Size </th>";
    }

    // This is a new org so start a new table
    echo <<<EOH
      <h2>Document counts for $orgname</h2>
      <table>
      <tr>
      	<th> ID </th>
	<th> $reporttype </th>
	<th> Period Count  </th> 
	$totaltitle 
	$bytestitle 
	$totalbytestitle 
      </tr>
EOH;
  }

  // output the data row
  echo <<<EOH

    <!-- $line -->
    <tr><td class="data" > $id </td>
	<td class="data" > $dtname </td> 
	$dtperiodtotal 
	$dttotal 
	$dtperiodbytes 
	$dttotalbytes 
    </tr>

EOH;

  // Add the new values to the global values
  $GLOBALS[docperiodtotal] += $data[4] ;
  $GLOBALS[doctotal] += $data[5] ;
  $GLOBALS[periodbytes] += $data[6] ;
  $GLOBALS[bytestotal] += $data[7] ;
 

}

//=================

function buildBytefield( $bytes, $class ){
  // Takes byte value and returns a string with table cell containing 
  // value in kB, MB or GB size

  $retstring = "<td class=\"$class\" > " ;

  if ( $bytes > 1024 && $bytes < 1024*1024 ){
    $retstring = $retstring . round($bytes/1024, 2 ) ." kB ";
  } 
  elseif ( $bytes > 1024*1024 && $bytes < 1024*1024*1024 ){
    $retstring = $retstring . round($bytes/(1024*1024), 2) ." MB ";
  } 
  elseif ( $bytes > 1024*1024*1024 ) {
    $retstring = $retstring . round($bytes/(1024*1024*1024), 2) ." GB ";
  } 
  else {
    $retstring = $retstring . $bytes ." B ";
  }

  $retstring = $retstring . " </td> ";
  return $retstring;
}

//=================

function HTML_Header(){


  // Get Browser type to solve some IE problems
  $browser=$_SERVER['HTTP_USER_AGENT'];
  if ( preg_match("/MSIE/",$browser) ) {
    $classfloatmenu = "floatlinkIE" ;
    }
    else {
      $classfloatmenu = "floatlink" ;
    }

  echo <<<EOH

<HTML  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
    <HEAD>
    <LINK REL="stylesheet"    HREF="main.css" type="text/css" ></link>
    <LINK REL="SHORTCUT ICON" HREF="/images/datamail/aw_icon.bmp" ></link>
    <script type="text/javascript" src="includes/ajax/XMLHttpRequest.js" ></script>
    <script type="text/javascript" src="includes/ajax/getFormParms.js" ></script>
EOH;

  include_once("includes/datepicker/datepicker.php");

  echo <<<EOH

    <script language="javascript" >

     function clearDivs( div1, div2, div3, div4, div5 ){
      if ( div1 ) { document.getElementById( div1 ).innerHTML = ''; }
      if ( div2 ) { document.getElementById( div2 ).innerHTML = ''; }
      if ( div3 ) { document.getElementById( div3 ).innerHTML = ''; }
      if ( div4 ) { document.getElementById( div4 ).innerHTML = ''; }
      if ( div5 ) { document.getElementById( div5 ).innerHTML = ''; }
     }

     function ajaxLoad( divid, formid, plusparams ){
       document.getElementById( divid ).innerHTML = '<h3>Fetching data. Please wait.</h3>' ;
       var params = getFormParms( formid ) + '&' + plusparams ;
       doAJAX( params , divid, 'noclear' );
     }

	function getElementsByClass( searchClass, domNode, tagName) { 
		if (domNode == null) domNode = document;
		if (tagName == null) tagName = '*';
		var el = new Array();
		var tags = domNode.getElementsByTagName(tagName);
		var tcl = " "+searchClass+" ";
		for(i=0,j=0; i<tags.length; i++) { 
			var test = " " + tags[i].className + " ";
			if (test.indexOf(tcl) != -1) 
				el[j++] = tags[i];
			} 
		return el;
	}	 

		function checkBoxToggleAllByName( source, cbname ){
 		 checkboxes = document.getElementsByName(cbname);
 		 for (var box in checkboxes)
 		   checkboxes[box].checked = source.checked;
		}

		function checkBoxToggleAllByClass( source, searchClass ){
 		 checkboxes = getElementsByClass(searchClass);
 		 for (var box in checkboxes)
 		   checkboxes[box].checked = source.checked;
		}

    </script>



EOH;

  echo <<<EOH

    <TITLE>Datamail Open Systems</TITLE>
    </HEAD>

    <body onLoad='javascript: doAJAX( "?action=serverselect", "ajax1", "noclear" )' >


    <DIV id=header >
    <TABLE><TR><TD>
    <IMG src="/images/datamail/datamail_logo.gif" >
    <FONT color=#cccccc ><b> Open Systems </b></font>
    <H1 class=header >VR2 Document Stats (from "usage" table)</H1>
    <H4 class=header align=right ><FONT color=white ><span id="ajaxtitle"></span></FONT></H4>
    </TD></TR></TABLE>
    </DIV>

    <DIV id=$classfloatmenu ><a class=floatmenu href=index.php > Reset page </a><br>
    </DIV>

    <DIV id=main>

    <div id="ajax1" >Loading data, please wait.</div>

    <div id="ajax2" ></div>

    <div id="ajax3" ></div>

    <div id="ajax4" ></div>

    <div id="ajax5" ></div>

EOH;

}

//=================


function HTML_Footer(){
  echo <<<EOH

  </div><!-- end of div:main -->
  </body>
  </html>

EOH;

}


//=================

function push_file($file, $remotehost, $ruser){

    $remotepath = "/tmp/$scriptname";

    // Test whether ftp_dir.pl exists on remote host and push if required
    $retval = update_remote_file( $filetopush, $remotehost, $ruser, $remotepath, "executable" );

    return $retval;
}

//=============

function Run_remote_command($command, $host, $ruser, $process_line){
     print "<!-- Using remote script $command. -->\n";
     flush();
     $handle = popen("
      ssh $ruser@$host \"
        $command 2>&1 ;
        \"
      ", "r" );

    // now suck the data from the pipe
    // The idea of using a pipe is so that we can process output line by line, but
    // flushing it doesn't seem to work as intended.
    $count=1;
    while ($line = fgets($handle) ){
      $result =  $result.${process_line}($line, $count ) ;
      $count++ ;
      flush() ;
      // Sometimes there is a problem if the command backgrounds processes.
      // Modify the remote command to print "FINISHED" when complete
      // detect that here and exit the loop.
      if ( $line == "FINISHED" ){ break; }
    }
  pclose($handle);

  return $result;
}

//=============

?>
