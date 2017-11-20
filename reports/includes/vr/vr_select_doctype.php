<?php

// Requires: "checkBoxToggle.js" javascript library to be loaded in the HTML head stanza.
// Requires: "vr_select_doctype.css" CSS file to be loaded in the HTML head stanza.

function Select_Doctype(&$envs, $env, $org, $remscript ) {
	return selectDocType($envs, $env, $org, $remscript );
}

function selectDocType(&$envs, $env, $org, $remscript ) {

  if ( ! $remscript ) $remscript = "/data/vretrieve/bin/vrinfo.pl";
  if ( ! is_array($envs) ) include_once("includes/vr/vrenvironments.php");
  if ( ! $env ) { h2("ERROR: Environment not selected") ; return false; }
  if ( ! $org ) { h2("ERROR: Organisation not selected") ; return false;}
  include_once("includes/vr/html_functions.php");

  if ( ! isset($envs[$env]['dbname']) ) {
    $envs[$env]['dbname'] = 'vretrieve';
  }
  $dbname = $envs[$env]['dbname'];
  $rhost = $envs[$env]['dathost'];

  // Otherwise, if not ALL orgs selected, then display dt/repository list
  div("doctypefilter_$org", 'doctypefilter filter');
	h2(expander("doctypefilterbody_$org") . "Select VR $env $org Document Types");

  $command = "$remscript -listtypes -dbhost $rhost -db $dbname -org $org";
	comment("Running command: $command");

	exec($command, $output, $retval);

  if ( $retval != 0 ){
    print "Problem running command: $command\nOutput: $output\nReturn value: $retval\n";
    exit;
  }

  $dtarray = loadDTArray($output);
	#debug($dtarray);

  div("doctypefilterbody_$org", 'filterbody');
  Print_Doc_Checkboxes($dtarray);
	divend();
	divend();

}

//=============

function loadDTArray( &$output ) {
	// Load the document type/store/repository output from the command into the dtarray
	/*
	Output lines look like this:
	#orgid,org,repid,rep,docstoreid,docstore,doctypeid,doctype
	2,STHX,144,Southern Cross,212,Scanned Documents,501,Complaint Letters
	*/

	$delimiter = ",";
	$firstline = array_shift($output);
	$firstline = trim($firstline, "#");
	$colheaders = explode($delimiter, $firstline);

	$dtarray = array();

	foreach ( $output as $line ){
	  $line = rtrim($line);
		comment($line);
		list($orgid, $org, $repid, $rep, $docstoreid, $docstore, $doctypeid, $doctype ) = explode($delimiter, $line);
		
		$dtarray[$org]['id'] = $orgid;
		$dtarray[$org]['reps'][$rep]['id'] = $repid;
		$dtarray[$org]['reps'][$rep]['docstores'][$docstore]['id'] = $docstoreid;
		$dtarray[$org]['reps'][$rep]['docstores'][$docstore]['doctypes'][$doctype]['id'] = $doctypeid;

	}

	sortDTArray($dtarray);

	return $dtarray;

}

//=============

function sortDTArray(&$dtarray) {
  // Sort dtarray array by repository name and sort sub-dimension doc type by dt name.

	ksort($dtarray);
  foreach ( array_keys($dtarray) as $org ) {
    ksort($dtarray[$org]['reps']);
  	foreach ( array_keys($dtarray[$org]['reps']) as $rep ) {
			ksort($dtarray[$org]['reps'][$rep]['docstores']);
  		foreach ( array_keys($dtarray[$org]['reps'][$rep]['docstores']) as $docstore ) {
				ksort($dtarray[$org]['reps'][$rep]['docstores'][$docstore]['doctypes']);
  		}
  	}
  }

	return $dtarray;

}

//=============

function Print_Doc_Checkboxes( &$dtarray ){

	/* dtarray structure:
		$dtarray[$org]['reps'][$rep]['docstores'][$docstore]['doctypes']
		$dtarray[$org]['id']
		$dtarray[$org]['reps'][$rep]['id']
		$dtarray[$org]['reps'][$rep]['docstores'][$docstore]['id']
		$dtarray[$org]['reps'][$rep]['docstores'][$docstore]['doctypes'][$doctype]['id']
	*/
  print ' <script src="checkBoxToggle.js" type="text/javascript" ></script>';

  #foreach ( $dtarray as $org => $orgarray ) {
  foreach ( array_keys($dtarray) as $org ) {
		$orgarray = $dtarray[$org];	
		$orgid = $orgarray['id'];
		div("","selectrepositories");
		h4("Repositories for org $org (ID: $orgid)
    <input type='checkbox' class='dtcheckbox' onClick=\"checkBoxToggleAllByClass(this, 'o$orgid' )\" checked='' />");

		foreach ( $orgarray['reps'] as $rep => $reparray ){
    	$repid = $reparray['id'];
			div("","selectdocstores");
			h4("Repository: $rep (ID: $repid) <input type='checkbox' class='o$orgid dtcheckbox' onClick=\"checkBoxToggleAllByClass(this, 'r$repid' )\" checked='' name='repositoryid[]' value='$repid' /> ");

			foreach ( $reparray['docstores'] as $ds => $dsarray ){
    		$dsid = $dsarray['id'];
				div("","selectdoctypes");
				h4("Document Store: $ds (ID: $dsid) <input type='checkbox' class='o$orgid r$repid dtcheckbox' onClick=\"checkBoxToggleAllByClass(this, 'ds$dsid' )\" checked='' name='docstoreid[]' value='$dsid' />");

				foreach ( $dsarray['doctypes'] as $dt => $dtarray ){
    			$dtid = $dtarray['id'];
    			print "<input type='checkbox' class='selectdoctype o$orgid r$repid ds$dsid dtcheckbox' checked='' name='doctypeid[]' value='$dtid' /> $dt (ID: $dtid) <br/>";

				}
				divend();
			}	
			divend();
		}
		divend();
	}	

}

?>
