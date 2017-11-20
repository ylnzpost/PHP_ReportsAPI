<?php

include_once("includes/vr/html_functions.php");

function selectDateRange($start, $end, $type, $hide ){
  # Define default dates
  if (! $start ) $start = date("d/m/Y", strtotime("yesterday") ) ;
  if (! $end ) $end = date("d/m/Y", strtotime("now") ) ;

	if ($hide){
		$expander = expander_("daterangeselectfilterbody");
		$display = "display: none";
	}

  div('daterangeselect', 'select filter');

	t($expander);
	h2("Select Date Range");
	t("[Tick to ignore date range: <input type='checkbox' name='ignoredaterange' >]");

  div('daterangeselectfilterbody','filterbody');
  t("Dates are inclusive of the whole day unless time is also entered.");

  echo <<<EOH

    <table>
    <tr><td>
    Period start: </TD>
    <td> <input id=cal1_container type=text name=start value="$start" onclick="$onclick1"></td>
    </tr>

    <tr>
    <td>Period end:</td>
    <td><INPUT id=cal2_container type=text name=end value="$end" onclick="$onclick2" ></td>
    </tr>
    </table>

EOH;

	divend();
	divend();
}

?>
