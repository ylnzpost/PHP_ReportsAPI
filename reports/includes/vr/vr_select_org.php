<?php

function selectOrg(&$envs, $env, $type, $submit) {

  $remscript = "/data/vretrieve/bin/vrinfo.pl";
  if ( ! is_array($envs) ) include_once("includes/vr/vrenvironments.php");
  if ( ! $env ) return false;
  include_once("includes/vr/html_functions.php");

  $rhost = $envs[$env]['dathost'];

	// DB connection defaults are built into the vrinfo script, but can be
	// overridden by the vrenvironments.php envs array
  if ( isset($envs[$env]['dbname']) ) $opts .= " -db ". $envs[$env]['dbname'];
  if ( isset($envs[$env]['dbuser']) ) $opts .= " -dbuser ". $envs[$env]['dbuser'];
	// DB Password should not really be held in the envs array, but can be if required.
	// apache's ~/.pgpass file should be used instead, or the default set in vrinfo
  if ( isset($envs[$env]['dbpass']) ) $opts .= " -dbpass ". $envs[$env]['dbpass'];

  span();
  h2("Select VR $env Organisation");

	if ( preg_match("/^multi/i", $type) ) {
		$multiple="multiple='multiple' size=7 ";
  	p("Use shift or ctrl to select multiple organisations");
	}

  // Get list of organisations in the remote db
  #$command = "$remscript $rhost details |sort";
  $command = "$remscript -dbhost $rhost -listorgs $opts 2>&1";
  print "\n\n<!-- DEBUG: running system command: $command -->\n";

  exec($command, $output, $retval);
  #debug($output);

  if ( $retval < 0 ){
    span("", "error", 
			br_("There was a problem running the script $remscript,") 
			. br_("Retval: $retval")
			. br_("Output:")
			. br_(implode("<br/>\n", $output))
		);
    exit;
  }



  print <<<EOH
	<form id="selectorgform" >
		<select name='orgs[]' ondblclick="$submit" $multiple >
EOH;
	if ( preg_match("/^multi/i", $type) ) t("<option value='ALL' selected='selected' >All Organisations</option>");

  foreach ( $output as $line ) {
    list($shortname, $longname) = explode(",",$line);
    t("<option value='$shortname' >$shortname ($longname)</option>\n");
  }

  t("	</select>");

	t("	<input type='button' onClick=\"$submit\" value='Go' class='submit' />");
  t("</form>");
  spanend();

}

?>
