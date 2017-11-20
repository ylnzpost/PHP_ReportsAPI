
<?php
  
  $repdir="./reports";
  
  //jlou added:
  //define globals var, $a as line total count to be processed.
  $GLOBALS = array('docperiodtotal' => 0, 'doctotal' => 0, 'periodbytes' => 0, 'bytestotal' => 0, 'orgname' => '');

  if ( isset($_GET['file']) ){

    $file = "$repdir/".$_GET['file'];

    if ( ! is_file($file) ){
      echo "Cannot find report file $file";
      exit;
    }
  }
  else exit;

  $bytype = getByType($file);

  HTMLHeader();

  displayTitle($file);
  
  processFile($file);

//===================

function getByType($file){
  // Try to determine from the filename whether this data is divided by 
  // repository or document type
  
  if ( preg_match( "/(doctype|document.type)/i", $file) ){
    // This is by document type
    return "DocType";
  }
  else return "Repository";
}

//===================

function displayTitle($file){
  // The period should be in the filename
  // eg.  2008-11-26_2008-12-26_ALL_vr2-wg-prod-adm-01.report

  $file = basename($file);
  list( $start, $end, $org, $host) = explode("_", $file);
  
  if     ( preg_match( "/-prod-/i", $host ) ) { $env = "Production"; }
  elseif ( preg_match( "/-uat-/i", $host  ) ) { $env = "UAT"; }
  elseif ( preg_match( "/-test-/i", $host ) ) { $env = "Test"; }
  elseif ( preg_match( "/-dev-/i", $host  ) ) { $env = "Development"; }
  elseif ( preg_match( "/-dr-/i", $host   ) ) { $env = "DR"; }

  
  echo "<h3>$env report for period:  $start to $end.</h3>";
}


//===================

function processFile($file){
  $fh = fopen($file, "r");
  while( !feof($fh)){
    $line = fgets($fh);
    echo "\n<!-- $line -->\n";
    processLine($line);
  }

  fclose($fh);  

}

//===================

function processLine($line){
    // input line format
  // orgid|org|repositoryid|repname|period doc total| doc total | period bytes total | bytes total
  // sample input:
  // BNZ|Administrative Documents|11|71|895665|6927486|Bank of New Zealand
  // TCL|Billing Operations|699606|25133686|17626541415|660005437717|
  
  if ( ! preg_match( "/\w/", $line) ){ 
  	$data[0] = '';
  	$data[1] = '';
  	$data[2] = 0;
  	$data[3] = 0;
  	$data[4] = 0;
  	$data[5] = 0;
  	$data[6] = '';
  } else {
	  // pipe delimited output
	  $data = explode('|', $line );	
	  // Iterate through all 4 numeric vars and if any are blank then set them to 0
	  foreach ( array(2,3,4,5) as $nfield ){
	    if ( $data[$nfield] == '' ) $data[$nfield] = 0;
	  }
	}

  #$orgid = $data[0];
  $orgname = $data[0];

  #$id = $data[2];
  $name = $data[1];
  $perioddocs = "<td class=\"datar\" > ".$data[2]." docs </td>";
  $totaldocs = "<td class=\"datar\" > ".$data[3]." docs </td>";
  $periodbytes = buildBytefield( $data[4], "datar" );
  $totalbytes  = buildBytefield( $data[5], "datar" );
  $orgfullname  = $data[6];


  // See if this is the same org as the last line
  if ( $GLOBALS['orgname'] != $orgname ){
    // It is a new org 
    // See if this is the first org, if not then close the preceding table
    if ( isset($GLOBALS['orgname']) && $GLOBALS['orgname'] != "" ) {
      // This is not the first org
      //Print_Totals_Row();
      //jlou added: Font will not have total count:
      if ( $GLOBALS['orgname'] == 'FONT' ) {      	
	  		Print_Font_Totals_Row();
	  	} else {
	  		Print_Totals_Row();
	  	}
      echo " <!-- End of org ".$GLOBALS['orgname']." -->\n </table>\n\n";
    }

    // Set the global org so we can make this same comparison on the next iteration
    $GLOBALS['orgname'] = $orgname;

    // Check whether it is a blank line, usually the last line
    if ( ! preg_match( "/\w/", $line) ){ return; }

    // This is a new org so start a new table
    global $bytype;
    //FONT don't want "Total Docs" and "Total Size" column:
    if($orgname == 'FONT') {
    	echo <<<EOH
	      <h2>$orgfullname ($orgname)</h2>
	      <table>
	      <tr>
	        <th> $bytype </th>
	        <th> Period Count  </th>	        
	        <th> Period Size </th>	        
	      </tr>
EOH;
    } else {    
	    echo <<<EOH
	      <h2>$orgfullname ($orgname)</h2>
	      <table>
	      <tr>
	        <th> $bytype </th>
	        <th> Period Count  </th>
	        <th> Total Docs </th>
	        <th> Period Size </th>
	        <th> Total Size </th>
	      </tr>
EOH;
		}


  }
	

// output the data row
  if($orgname == 'FONT') {
		  echo <<<EOH
		    <tr>
		        <td class="data" > $name </td>
		        $perioddocs
		        $periodbytes
		    </tr>		
EOH;
	} else {
		  echo <<<EOH
		    <tr>
		        <td class="data" > $name </td>
		        $perioddocs
		        $totaldocs
		        $periodbytes
		        $totalbytes
		    </tr>		
EOH;
	}

  // Add the new values to the global values
  $GLOBALS['docperiodtotal'] += $data[2] ;
  $GLOBALS['doctotal'] += $data[3] ;
  $GLOBALS['periodbytes'] += $data[4] ;
  $GLOBALS['bytestotal'] += $data[5] ;

}

//===================

function Print_Totals_Row() {

  $GLOBALS['bytestotal'] = buildBytefield( $GLOBALS['bytestotal'], "datatotal" );
  $GLOBALS['periodbytes'] = buildBytefield( $GLOBALS['periodbytes'], "datatotal" );

  echo <<<EOH
    <tr>
      <td><b>TOTALS:</b></td>
      <td class="datatotal" >$GLOBALS[docperiodtotal] docs</td>
      <td class="datatotal" >$GLOBALS[doctotal] docs</td>
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

//jlou added: for Font as it not displaying total count:
function Print_Font_Totals_Row() {

  $GLOBALS['bytestotal'] = buildBytefield( $GLOBALS['bytestotal'], "datatotal" );
  $GLOBALS['periodbytes'] = buildBytefield( $GLOBALS['periodbytes'], "datatotal" );

  echo <<<EOH
    <tr>
      <td><b>TOTALS:</b></td>
      <td class="datatotal" >$GLOBALS[docperiodtotal] docs</td>
      $GLOBALS[periodbytes]      
    </tr>

EOH;

  //Reset global totals
  foreach ( array('docperiodtotal', 'doctotal', 'periodbytes', 'bytestotal') as $var ){
    $GLOBALS[$var] = 0;
    echo "<!-- Resetting GLOBALS[$var] to ". $GLOBALS[$var] ."-->\n";
  }

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

function HTMLHeader() {

  echo <<<EOH
  <HTML  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
    <HEAD>
EOH;

  // Embed the main.css so that people can save the doc as one file 
  //    <LINK REL="stylesheet"    HREF="main.css" type="text/css" ></link>

  echo "     <style type=\"text/css\" > \n";

  $fh = fopen("main.css", "r");

  while ( $line = fgets($fh) ){ echo $line ; }

  echo "     </style> \n";


  echo <<<EOH
      <LINK REL="SHORTCUT ICON" HREF="/images/datamail/aw_icon.bmp" ></link>
    <TITLE>Datamail VRetrieve2 Monthly Disk Usage Report </TITLE>
    </HEAD>

    <body>

    <DIV id=header >
    <TABLE><TR><TD>
    <IMG src="/images/datamail/datamail_logo.gif" >
    <FONT color=#cccccc ><b> Open Systems </b></font>
    <H1 class=header >VRetrieve2 Disk Usage Report </H1>
    </TD></TR></TABLE>
    </DIV>

EOH;


}

?>

</body>
</html>
