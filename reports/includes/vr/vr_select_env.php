<?php

include_once("includes/vr/html_functions.php");

function selectEnv(&$envs, $submit) {

  if ( ! is_array($envs) ) include_once("includes/vr/vrenvironments.php");

	div("selectform","vrenvselect");
	h2("Select VR Environment");

	t("<form id='serverselect' >");
	t("<select name='env' onchange=\"$submit\" >");

  foreach ( $envs as $env => $details ) {
    $host = $details["dathost"];
    t("<option  value='$env' > $env ($host) </option>");
  }

	t("\t</select>");
  t("<input type='button' value='Go' onclick=\"$submit\" >");
	t("</form>");
	divend();
}

?>
