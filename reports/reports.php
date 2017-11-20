<?php

HTML_Header();

include("includes/vr/html_functions.php");
include("includes/jquery/jqueryUI_autoload.php");

mainHtml();

  // List report files from reports dir
  $repdir="./reports";
	$GLOBALS['recentOnly'] = true;
	if (isset($_GET['recentOnly']) && $_GET['recentOnly'] == 'false' )
		$GLOBALS['recentOnly'] = false;

  if ( ! $dh = opendir($repdir) ){
    echo "Cannot open dir $repdir";
    exit;
  }

  $filearray = array();

  while ( ($file = readdir($dh)) !== false ) {
    if ( ! is_file("$repdir/$file") || ! preg_match( "/\.report/", $file) ) continue;
    list( $org, $startdate, $stopdate )= getDetailsFromFilename($file);
		$range = getRangeString($startdate, $stopdate);

    if ( ! array_key_exists( $org, $filearray ) ){
        $filearray[$org] = array();
    }


   	if ( ! isset($filearray[$org]['ranges'] ) || ! is_array($filearray[$org]['ranges']) ){
			$filearray[$org]['ranges'] = array();
		}
		
		if (! array_key_exists( $range, $filearray[$org]['ranges'] ) ){
 			$filearray[$org]['ranges'][$range] = array();
		}

    if ( ! array_push( $filearray["$org"]['ranges'][$range], "$file" ) ) {
			p("Error adding file '$file' to '$org, $range' array"); 
		}

  }

	#debug( $filearray );
	displayReports($filearray);

///////////////

function getDetailsFromFilename($filename) {
	   # get the orgname from the filename, 3rd field
    $array = explode("_",$filename);
    $org = $array[2];
    $startdate = $array[0];
    $stopdate = $array[1];

		return array($org, $startdate, $stopdate );
}

///////////////

function displayReports(&$filearray){
  // Display ALL org main reports first
	h2("Main Reports");
	br();
	h3("All VR2 (VRetrieve 11) organisations' standard monthly report");
	displayReportLinks("ALL", $filearray['ALL'] );

  // Display ALL RSP org main reports first
	//br();
	//h3("All VR RSP (VRetrieve 01) organisations' standard monthly report");
	//displayReportLinks("ALL-RSP", $filearray['ALL-RSP'] );

  // Display ALL 02 org main reports first
	//br();
	//h3("All VR 02 (TCL and GEN) organisations' standard monthly report");
	//displayReportLinks("ALL02", $filearray['ALL02'] );

	br();
  h2("Reports for organisations with special requirements");

  ksort($filearray);  // Sort into key/org order

  foreach ( $filearray as $org => $data ){
    // Skip ALL org reports because we've already displayed them
		$skiplist = array( "ALL", "ALL-RSP", "ALL02", "ACP" );
    if ( in_array($org, $skiplist)) continue; 

    // Print a title if this is an org we haven't already displayed
		div($org."_reports", "orgreports" );
    h3($org);
		displayReportLinks($org, $data);
		divend();
  }

}

///////////////////

function displayReportLinks($org,$data) {

	global $repdir ;
	$ranges = $data['ranges'];

 #sort($ranges); // sort the files into alphanumeric order
	ksort($ranges);

	foreach( $ranges as $range => $files ){
		rsort($files);
		$recent="";
		if ( $GLOBALS['recentOnly'] ){
			$filenames = array($files[0]);
			$recent = "recent";
		}
		else $filenames = $files;

		$rangeNs = str_replace(" ", "_", $range); // range with no space characters, for ID, class etc.
		$orgDivId = "${org}_${rangeNs}_xreportlist" ;
		$orgDivClass = "org_x${recent}reportlist" ;

		$rangeTitle=$range;
		if ( ! $GLOBALS['recentOnly'] ) $rangeTitle = expander_($orgDivId) . $rangeTitle;
		div("${org}_${rangeNs}_reports", "orgrange" );
		div("","rangeheader", h4_($rangeTitle) );

		div($orgDivId, $orgDivClass);

		foreach ( $filenames as $filename ) {
			list( $org, $start, $stop )= getDetailsFromFilename($filename);

			$stopMonthsAgo = getMonthsAgo(strtotime($stop), ctime("$repdir/$filename"));
			$startMonthsAgo = getMonthsAgo(strtotime($start), ctime("$repdir/$filename"));
	
			$linkLabel = "From $start to $stop  ( $stopMonthsAgo to $startMonthsAgo months before report created)";
			if ( $range == "Everything Before" )
			$linkLabel = " $stop ( $stopMonthsAgo months before report created)";

			span("", "reportlink",
				br_("<a class='reportlink' href='display_report.php?file=$filename' >$linkLabel</a>")
			);
		}
		divend();
	}
		divend();
}


////////////////////

function getRangeString($startdate, $stopdate) {
    $startTs = strtotime($startdate);
    $stopTs = strtotime($stopdate);
		$months = getMonthsAgo($startTs, $stopTs);
    $range = "1 month";

    if ( $months > 1 && $months < 300 )
        $range = "$months months";

    if ( $months > 300 )
       $range = "Everything Before";


	return $range;
}

////////////////////

function getMonthsAgo($pastEpoch, $nowEpoch ) {
	if ( ! $nowEpoch ) $nowEpoch = time();
	# inaccurate, need to improve on this.
	$monthsAgo = ( $nowEpoch - $pastEpoch ) / 60 / 60 / 24 / 30;

	return round($monthsAgo);
}

function ctime($file) {
	$stats = stat($file);
	return $stats[10];
}

function mtime($file) {
	$stats = stat($file);
	return $stats[9];
}

////////////////////

function HTML_Header(){

  echo <<<EOH
<HTML  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
    <HEAD>
    <LINK REL="stylesheet"    HREF="main.css" type="text/css" ></link>
    <LINK REL="SHORTCUT ICON" HREF="/images/datamail/aw_icon.bmp" ></link>

    <TITLE>Datamail Open Systems</TITLE>
    </HEAD>

    <body>


    <DIV id=header >
    <TABLE><TR><TD>
    <IMG src="/images/datamail/datamail_logo.gif" >
    <FONT color=#cccccc ><b> Open Systems </b></font>
    <H1 class=header >VR2 Document Stats</H1>
    <H4 class=header align=right ><FONT color=white >Prepared Reports</FONT></H4>

    </TD></TR></TABLE>
    </DIV>
    
EOH;
}

function mainHtml() {
    div("main");
    h2("Monthly VR2 disk usage and document count stats reports");
		div("toplinks");
    p("<a href='?recentOnly=true'>Latest Reports</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<a href='?recentOnly=false'>Historical Reports</a>");
		p("Click on the little arrow symbols below to expand the list of report links.");

}

?>

</div>
<br/>
<br/>
<br/>
</body>
</html>

