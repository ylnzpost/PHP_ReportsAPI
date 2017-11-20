<!DOCTYPE HTML>
<html>
    <head>
		<title>VR Data Reports</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="./bootstrap_lib/bootstrap.min.css">
		<link rel="stylesheet" href="./bootstrap_lib/bootstrap-dialog.min.css">
		<script src="./bootstrap_lib/jquery.min.js"></script>
		<script src="./bootstrap_lib/bootstrap.min.js"></script>
		<script src="./bootstrap_lib/bootstrap-dialog.min.js"></script>
		<script>
		</script>
    </head>
    <body>
		<?php
			$reportVar = $_GET['report_name'];
			$GLOBALS['reportsDir'] = "./reports/";
			//echo '<h4>'.$GLOBALS['reportsDir'].$reportVar.'</h4>';
			$GLOBALS['DocPeriodTotal'] = "";
			$GLOBALS['DocTotal'] = "";
			$GLOBALS['PeriodBytes'] = "";
			$GLOBALS['BytesTotal'] = "";
			
			$report_path_array = (explode(".", $reportVar));
			//print_r ($report_path_array);
			//echo '<br />';
			
			$report_name_no_ext = $report_path_array[0];
			$report_name_no_ext_arr = (explode("_", $report_name_no_ext));
			//print_r ($report_name_no_ext_arr);
			//echo '<br />';
			
			$report_type = strtoupper($report_name_no_ext_arr[2]);
			//echo $report_type.'<br />';
			
			//report client code array
			$client_report_code_arr = array("ALL", "BNZ", "MSD", "MNRG", "STHX", "TCL", "FONT", "NZP");
			
			//write page BODY HEADER			
			echo '<div class="jumbotron panel-heading text-center">';
			echo '<h3>'.$report_type.' '.'Report'.'</h3>';
			echo '<hr width="88%"/>';
			echo '<blockquote>';
			echo '<footer>'.$reportVar.'</footer>';
			echo '</blockquote>';
			echo '<hr width="88%"/>';
			echo '<p>';
			echo '<img class="center-block" style="padding-top:0px; padding-bottome:0px;" src="nzpost.png">';
			echo '</p>';
			echo '</div>';
			
			//write page BODY START
			echo '<div class="container">';
			echo '<div class="row">';
			echo '<div class="col-md-12">';

			if ($report_type === $client_report_code_arr[0])	//ALL
			{
				displayReport($reportVar, 2, 3);	//display report for ALL
			}
			else
			{
				if ($report_type === $client_report_code_arr[1])	//BNZ
				{
					displayReport($reportVar, 1, 0);	//display normal report for individual clicnt
				}
				else if ($report_type === $client_report_code_arr[2])	//MSD
				{
					displayReport($reportVar, 1, 1);	//display normal report for individual clicnt
				}
				else if ($report_type === $client_report_code_arr[3])	//MNRG
				{
					displayReport($reportVar, 1, 1);	//display normal report for individual clicnt
				}
				else if ($report_type === $client_report_code_arr[4])	//STHX
				{
					displayReport($reportVar, 1, 1);	//display normal report for individual clicnt
				}
				else if ($report_type === $client_report_code_arr[5])	//TCL
				{
					displayReport($reportVar, 1, 0);	//display normal report for individual clicnt
				}
				else if ($report_type === $client_report_code_arr[6])	//FONT
				{
					displayReport($reportVar, 1, 2);	//display normal report for individual clicnt
				}
				else if ($report_type === $client_report_code_arr[7])	//NZP
				{
					displayReport($reportVar, 1, 1);	//display normal report for individual clicnt
				}
				else
				{}
			}
	
			//write page BODY END
			echo '</div>';
			echo '</div>';
			echo '</div>';
			//---------------------------------------
			//Create display report on page
			//---------------------------------------
			function displayReport($for_report, $flag, $template)
			{
				$reportsDir = $GLOBALS['reportsDir'];
				$current_working_dir = getcwd();;
				$reports_dir = '/reports/';
				$report_path = $current_working_dir.$reports_dir.$for_report;
				//echo '<h5>'.$report_path.'</h5>';
				
				if ( !$dir_handler = opendir($reportsDir) ){
					echo '<h4>'."Cannot open dir $reportsDir".'</h4>';
					exit;
				}
				
				if (file_exists($report_path)) {
					$read_file_handler = fopen($report_path,"r");		
					if ($flag === 1)	//normal report for individual clicnt
					{
						//------------------------------------------
						//write page
						//------------------------------------------
						//write table START
						echo '<table class="table table-striped">';
						if ($template === 0)
						{
							//write table HEADER
							echo '<thead>';
							echo '<tr>';
							echo '<th>Doc Type</th>';
							echo '<th>Period Count</th>';
							echo '<th>Total Docs</th>';
							echo '<th>Period Size</th>';
							echo '<th><div class="pull-right">Total Size</div></th>';
							echo '</tr>';
							echo '</thead>';
						}
						else if ($template === 1)
						{
							//write table HEADER
							echo '<thead>';
							echo '<tr>';
							echo '<th>Repository</th>';
							echo '<th>Period Count</th>';
							echo '<th>Total Docs</th>';
							echo '<th>Period Size</th>';
							echo '<th><div class="pull-right">Total Size</div></th>';
							echo '</tr>';
							echo '</thead>';
						}
						else if ($template === 2)
						{
							//write table HEADER
							echo '<thead>';
							echo '<tr>';
							echo '<th>Repository</th>';
							echo '<th>Period Count</th>';
							echo '<th><div class="pull-right">Period Size</div></th>';
							echo '</tr>';
							echo '</thead>';
						}
						else{}
						
						//write table BODY
						echo '<tbody>';
						if ($read_file_handler !== FALSE) {
							while (($line = fgets($read_file_handler)) !== false) {
								//process the line read
								//echo $line."<br />";
								//print_r (explode("|",$line));
								$field_array = (explode("|",$line));
								//echo $flag.' - '.$field_array[0].' - '.$template.'<br />';
								writeTableBody($flag, $field_array, $template);
							}
							fclose($read_file_handler);
						}
						echo '</tbody>';

						//write table FOOTER
						echo '<tfoot>';
						echo '<tr>';
						echo '<td>'.'</td>';
						if ($template === 0)
						{
							echo '<td>'.buildDocsField($GLOBALS['DocPeriodTotal']).'</td>';
							echo '<td>'.buildDocsField($GLOBALS['DocTotal']).'</td>';
							echo '<td>'.buildByteField($GLOBALS['PeriodBytes']).'</td>';
							echo '<td><div class="pull-right">'.buildByteField($GLOBALS['BytesTotal']).'</div></td>';
						}
						else if ($template === 1)
						{
							echo '<td>'.buildDocsField($GLOBALS['DocPeriodTotal']).'</td>';
							echo '<td>'.buildDocsField($GLOBALS['DocTotal']).'</td>';
							echo '<td>'.buildByteField($GLOBALS['PeriodBytes']).'</td>';
							echo '<td><div class="pull-right">'.buildByteField($GLOBALS['BytesTotal']).'</div></td>';
						}
						else if ($template === 2)
						{
							echo '<td>'.buildDocsField($GLOBALS['DocPeriodTotal']).'</td>';
							echo '<td><div class="pull-right">'.buildByteField($GLOBALS['PeriodBytes']).'</div></td>';
						}
						else
						{}
						echo '</tr>';
						echo '</tfoot>';
						//write table END
						echo '</table>';
						//reset GLOBAL Variables
						RESET_GLOBAL_VARS();
					}
					else if($flag === 2)//report for ALL
					{
						//------------------------------------------
						//write page
						//------------------------------------------
						if ($read_file_handler !== FALSE) {
							$line_count = 0;
							$current_client_code = "";
							$previous_client_code = "";
							while (($line = fgets($read_file_handler)) !== false) {
								//process the line read
								//echo $line."<br />";
								//print_r (explode("|",$line));
								//echo '<br />';
								$line_count += 1;
								$field_array = (explode("|",$line));
								//------------------------------------------
								//check line by line for different clients
								//------------------------------------------
								$client_full_name = $field_array[6];
								$current_client_code = $field_array[0];
								if ($line_count === 1)
								{
									$previous_client_code = $field_array[0];
									echo '<h3>'.$client_full_name.' ('.$current_client_code.') '.'</h3>';
									writeTableHeaderForReportALL();	//new tale header
									writeTableBody($flag, $field_array, 1);
								}
								else
								{
									if ($current_client_code === $previous_client_code)
									{
										/*
										echo '-----------------------'.'<br />';
										echo 'SAME CLIENT CODE'.' => '.$current_client_code.'<br />';
										echo '-----------------------'.'<br />';
										*/
										writeTableBody($flag, $field_array, 1);
									}
									else
									{
										/*
										echo '----------------------'.'<br />';
										echo 'LINE COUNT => '.$line_count.'<br />';
										echo 'DIFFERENT CLIENT CODE FOUND'.'<br />';
										echo '$current_client_code'.' => '.$current_client_code.'<br />';
										echo '$previous_client_code'.' => '.$previous_client_code.'<br />';
										echo '----------------------'.'<br />';
										*/
										writeTableFooterForReportALL();	//current table footer
										RESET_GLOBAL_VARS();	//reset GLOBAL Variables for next client code before writing new Header
										echo '<h3>'.$client_full_name.' ('.$current_client_code.') '.'</h3>';
										writeTableHeaderForReportALL();	//new table header
										writeTableBody($flag, $field_array, 1); //write first line of new table body for next client code
									}
								}
								$previous_client_code = $current_client_code;
							}
							writeTableFooterForReportALL();	//current table footer
							fclose($read_file_handler);
							RESET_GLOBAL_VARS();
						}
						//------------------------------------------
					}
					else
					{
					}
				}
				else{
					echo '<h5>'.'Error => '.$report_path.' does not exist'.'</h5>';
				}
			}
			
			function writeTableHeaderForReportALL()
			{
				//write table START
				echo '<table class="table table-striped">';
				//write table HEADER
				echo '<thead>';
				echo '<tr>';
				echo '<th>Repository</th>';
				echo '<th>Period Count</th>';
				echo '<th>Total Docs</th>';
				echo '<th>Period Size</th>';
				echo '<th><div class="pull-right">Total Size</div></th>';
				echo '</tr>';
				echo '</thead>';
			}
			
			function writeTableFooterForReportALL()
			{
				//write table FOOTER
				echo '<tfoot>';
				echo '<tr>';
				echo '<td>'.'</td>';
				echo '<td>'.buildDocsField($GLOBALS['DocPeriodTotal']).'</td>';
				echo '<td>'.buildDocsField($GLOBALS['DocTotal']).'</td>';
				echo '<td>'.buildByteField($GLOBALS['PeriodBytes']).'</td>';
				echo '<td><div class="pull-right">'.buildByteField($GLOBALS['BytesTotal']).'</div></td>';
				echo '</tr>';
				echo '</tfoot>';
				//write table END
				echo '</table>';
			}
			
			function writeTableBody($flag, $line_arr, $template)
			{
				if ($flag === 1)		//write table for nomal individual client
				{
					if ($template === 0)
					{
						echo '<tr>';
						echo '<td>'.$line_arr[1].'</td>';
						echo '<td>'.buildDocsField($line_arr[2]).'</td>';
						echo '<td>'.buildDocsField($line_arr[3]).'</td>';
						echo '<td>'.buildByteField($line_arr[4]).'</td>';
						echo '<td><div class="pull-right">'.buildByteField($line_arr[5]).'</div></td>';
						echo '</tr>';
					}
					else if ($template === 1)
					{
						echo '<tr>';
						echo '<td>'.$line_arr[1].'</td>';
						echo '<td>'.buildDocsField($line_arr[2]).'</td>';
						echo '<td>'.buildDocsField($line_arr[3]).'</td>';
						echo '<td>'.buildByteField($line_arr[4]).'</td>';
						echo '<td><div class="pull-right">'.buildByteField($line_arr[5]).'</div></td>';
						echo '</tr>';
					}
					else if ($template === 2)
					{
						echo '<tr>';
						echo '<td>'.buildByteField($line_arr[1]).'</td>';
						echo '<td>'.buildDocsField($line_arr[2]).'</td>';
						echo '<td><div class="pull-right">'.buildByteField($line_arr[4]).'</div></td>';
						echo '</tr>';
					}
					else
					{}
					//update GLOBAL Variables
					UPDATE_GLOBALS(0, $line_arr);
				}
				else if ($flag === 2)	//write table for ALL
				{
					if ($template === 1)
					{
						echo '<tr>';
						echo '<td>'.$line_arr[1].'</td>';
						echo '<td>'.buildDocsField($line_arr[2]).'</td>';
						echo '<td>'.buildDocsField($line_arr[3]).'</td>';
						echo '<td>'.buildByteField($line_arr[4]).'</td>';
						echo '<td><div class="pull-right">'.buildByteField($line_arr[5]).'</div></td>';
						echo '</tr>';
					}
					//update GLOBAL Variables
					UPDATE_GLOBALS(0, $line_arr);
				}
				else{
				}
			}
			
						
			//---------------------------------------
			//Convert bytes into string
			//---------------------------------------
			function buildByteField($bytes){
				//echo $bytes."<br />";
				//Takes byte value and returns a string with table cell containing
				//value in kB, MB or GB size
				$retstring = 0;
				if ( $bytes > 1024 && $bytes < 1024*1024 ){
					//$retstring = $retstring . round($bytes/1024, 2 ) ." KB ";
					$retstring = round($bytes/1024, 2 ) ." KB ";
				}
				else if ( $bytes > 1024*1024 && $bytes < 1024*1024*1024 ){
					//$retstring = $retstring . round($bytes/(1024*1024), 2) ." MB ";
					$retstring = round($bytes/(1024*1024), 2) ." MB ";
				}
				else if ( $bytes > 1024*1024*1024 ) {
					//$retstring = $retstring . round($bytes/(1024*1024*1024), 2) ." GB ";
					$retstring = round($bytes/(1024*1024*1024), 2) ." GB ";
				}
				else {
					//$retstring = $retstring . $bytes ." B ";
					$retstring = $bytes ." B ";
				}
				return $retstring;
			}
			
			//---------------------------------------
			//Build docs field in XML
			//---------------------------------------
			function buildDocsField($docs_count){
				$docs = $docs_count;
				if (($docs === null) || (intval($docs) === 0))
				{
					//echo $docs."<br />";
					$docs = "0 docs";
				}
				else
				{
					$docs = $docs." docs";
				}
				return $docs;
			}
			
			function UPDATE_GLOBALS($_template, $_record_array)
			{
				switch ($_template) {
					case 0:
						$GLOBALS['DocPeriodTotal'] += $_record_array[2];
						$GLOBALS['DocTotal'] += $_record_array[3];
						$GLOBALS['PeriodBytes'] += $_record_array[4];
						$GLOBALS['BytesTotal'] += $_record_array[5];
						break;
					case 1:
						$GLOBALS['DocPeriodTotal'] += $_record_array[2];
						$GLOBALS['DocTotal'] += $_record_array[3];
						$GLOBALS['PeriodBytes'] += $_record_array[4];
						$GLOBALS['BytesTotal'] += $_record_array[5];
						break;
					case 2:
						$GLOBALS['DocPeriodTotal'] += $_record_array[2];
						$GLOBALS['PeriodBytes'] += $_record_array[4];
						break;
					//default full name
					default:
						break;
				}
			}
			
			function RESET_GLOBAL_VARS()
			{
				//------------------------------------------
				//reset GLOBALS
				//------------------------------------------
				foreach (array('DocPeriodTotal', 'DocTotal', 'PeriodBytes', 'BytesTotal') as $var)
				{
					$GLOBALS[$var] = 0;
				}
			}
		?>
	</body>
</html>