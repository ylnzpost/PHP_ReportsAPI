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
		<!--
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.35.4/css/bootstrap-dialog.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.35.4/js/bootstrap-dialog.min.js"></script>
		-->
		<script>	
			function ftp_transfer_progress()
			{
				$('#FTPTansferDialog').modal('show');
			}
		</script>
    </head>
    <body>
        <?php
			$reports_dir = "./reports/";
			//define variables for TOTAL
			//add the new values to the global values
			$DocPeriodTotal = 0;
			$DocTotal = 0;
			$PeriodBytes = 0;
			$BytesTotal = 0;
			
			if ( !$dir_handler = opendir($reports_dir) ){
				echo "Cannot open dir $reports_dir";
				exit;
			}
			
			$file_array = array();	
			//---------------------------------
			//Report Object
			//---------------------------------
			class Report implements JsonSerializable
			{
				protected $client;
				protected $date_range;
				protected $report_file;
				//from to date
				protected $from_date;
				protected $to_date;
				//details
				protected $doc_type;
				protected $repository;
				protected $period_count;
				protected $total_docs;
				protected $period_size;
				protected $total_size;

				public function __construct($client, $date_range, $report_file)
				{
					$this->client = $client;
					$this->date_range = $date_range;
					$this->report_file = $report_file;
				}
				
				public function setFromDate($fromdate_value)
				{
					$this->from_date = $fromdate_value;
				}
				
				public function setToDate($todate_value)
				{
					$this->to_date = $todate_value;
				}
				
				public function setDocType($doctype_value)
				{
					$this->doc_type = $doctype_value;
				}
				
				public function setRepository($repository_value)
				{
					$this->repository = $repository_value;
				}
				
				public function setPeriodCount($periodcount_value)
				{
					$this->period_count = $periodcount_value;
				}
				
				public function setTotalDocs($totaldocs_value)
				{
					$this->total_docs = $totaldocs_value;
				}
				
				public function setPeriodSize($periodsize_value)
				{
					$this->period_size = $periodsize_value;
				}
				
				public function setTotalSize($totalsize_value)
				{
					$this->total_size = $totalsize_value;
				}
				
				public function getClient(){
					return $this->client;
				}
				
				public function getDateRange(){
					return $this->date_range;
				}
				
				public function getReportFile(){
					return $this->report_file;
				}
				
				public function getFromDate(){
					return $this->from_date;
				}
				
				public function getToDate(){
					return $this->to_date;
				}
				
				public function jsonSerialize(){
                    return $this;
                }
			}
			//---------------------------------
			
			//---------------------------------
			//Write HTML table
			//---------------------------------
			function buildHTMLTable($reportsArr, $reportsDir, $xmlDir)
			{
				reset($reportsArr);
				$refresh_page_button = 					
					'<span>'.
					'<a href="./reports_api.php" data-toggle="tooltip" title="Refresh Page" class="btn btn-primary" role="button">'.
					'<span class="glyphicon glyphicon-refresh"></span>'.
					'</a>'.
					'</span>';
				$download_PDF_button = 					
					'<span>'.
					'<a href="" data-toggle="tooltip" title="Download All PDFs" class="btn btn-primary" role="button">'.
					'<span class="glyphicon glyphicon-save"></span>'.
					'</a>'.
					'</span>';
				$ftp_transfer_XML_button = 					
					'<span>'.
					'<a href="./reports_api.php?ftp_transfer=true" onclick="return ftp_transfer_progress();" data-toggle="tooltip" title="FTP Transfer XML" class="btn btn-warning pull-right" role="button">'.
					'<span class="glyphicon glyphicon-open"></span>'.
					'</a>'.
					'</span>';
				$html_table =
					'<table class="table table-striped">'.
					'<thead>'.
					'<tr>'.
					'<th><h4>Client</h4></th>'.
					'<th><h4>Date Range</h4></th>'.
					'<th><h4>Report</h4></th>'.
					'<th><h4>View</h4></th>'.
					'<th><h4>PDF</h4></th>'.
					'<th><h4 class="pull-right">XML</h4></th>'.
					'</tr>'.
					'</thead>'.
					'<tfoot>'.
					'<tr>'.
					'<td></td>'.
					'<td></td>'.
					'<td class="text-center">'.'<span style="color:#999999;">'.'<small>'."Copyright © 2017 NEW ZEALAND POST LTD".'<br />'."All Rights Reserved".'</small>'.'</span>'.'</td>'.
					//'<td class="text-center">'.'<span style="color:#b3b3b3; font-size:10px;">'."Copyright © 2017 NEW ZEALAND POST LTD".'<br />'."All Rights Reserved".'</span>'.'</td>'.
					'<td></td>'.
					'<td></td>'.
					'<td></td>'.
					'</tr>'.
					'</tfoot>'.
					'<tbody>'.
					'<tr>'.
					'<th><h4></h4></th>'.
					'<th><h4></h4></th>'.
					'<th><h4></h4></th>'.
					'<th><h4>'.$refresh_page_button.'</h4></th>'.
					'<th><h4>'.$download_PDF_button.'</h4></th>'.
					'<th><h4 class="pull-right">'.$ftp_transfer_XML_button.'</h4></th>'.
					'</tr>';
				///foreach ($reportsArr as $key => $value) {
				//	echo "Key: $key; Value: $value<br />\n";
				//}
				foreach ($reportsArr as $reportObj) {
				    //write html button
				    //$view_report_button = '<a href='.$reportsDir.$reportObj->getReportFile().' class="btn btn-info btn-sm" data-toggle="modal" data-target="#popupModal"><span class="glyphicon glyphicon-eye-open"></span></a>';
				    $view_report_button = '<a href='.'"'.$reportsDir.$reportObj->getReportFile().'"'.' class="btn btn-info btn-sm" target="_blank" data-toggle="tooltip" title="View Report Data"><span class="glyphicon glyphicon-eye-open"></span></a>';
					$view_pdf_button = '<a href='.'"'.$reportsDir.$reportObj->getReportFile().'"'.' class="btn btn-info btn-sm" download="'.$reportsDir.$reportObj->getReportFile().'" data-toggle="tooltip" title="Download PDF"><span class="glyphicon glyphicon-download-alt"></span></a>';
					$view_xml_button = '<a href='.'"'.$xmlDir.$reportObj->getReportFile().'.xml'.'"'.' class="btn btn-info btn-sm pull-right" target="_blank" data-toggle="tooltip" title="View XML"><span class="glyphicon glyphicon-list"></span></a>';
					$view_json_button = '<a href="#" class="btn btn-info btn-sm pull-right"><span class="glyphicon glyphicon-list"></span></a>';
				    //write html table
				    $html_table .=
						'<tr>'.
						'<td>'.$reportObj->getClient().'</td>'.
						'<td>'.$reportObj->getDateRange().'</td>'.
						'<td>'.$reportObj->getReportFile().'</td>'.
						'<td>'.$view_report_button.'</td>'.
						'<td>'.$view_pdf_button.'</td>'.
						'<td>'.$view_xml_button.'</td>'.
						'</tr>';			
				}
				$html_table .= 								
					'</tbody>'.
					'</table>';
				return $html_table;
			}
			//---------------------------------
			
			//$obj_array = json_decode(json_encode($reportObj), true);
			//echo $obj_array."<br />";
			//echo json_encode($reportObj)."<br />";

			$html_table = "";
			$reportsDir = "./reports/";
			$xmlDir = "./xml/";
			$reportsArr = array();
			$reportObjForALL = null;
			while (($file = readdir($dir_handler)) !== false ) {
				//$report_file_path = "$reports_dir/$file";
				$report_file_path = $reportsDir.$file;
				if (file_exists($report_file_path)) {
					//echo $report_file_path." => "."Last modified: ".date("F d Y H:i:s.",filemtime($report_file_path))."<br />";
					//echo $file." was last modified: ".date("F d Y H:i:s.", filemtime($report_file_path))."<br />";
					//echo $file." was last modified: ".date("Y-m-d H:i:s", filemtime($report_file_path))."<br />";
					//echo $file." was last modified: ".date("Y-m-d H:i:s", filemtime($report_file_path))."<br />";
				}
				//if (!is_file("$reports_dir/$file") || !preg_match( "/\.report/", $file)) continue;
				if (!is_file($reportsDir.$file) || !preg_match( "/\.report/", $file)) continue;
				list($org, $startdate, $stopdate)= getDetailsFromFilename($file);
				$range = getRangeString($startdate, $stopdate);
				$reportObj = null;
				//if it is a most recent file younger than 1 month
				if (time()-filemtime($report_file_path) < (24 * 3600 * 30)) {
					//skip these reports
					//$skiplist = array("ALL", "ALL-RSP", "ALL02", "ACP");
					$skiplist = array("ALL-RSP", "ALL02", "ACP");
					if (in_array($org, $skiplist)) continue;
					//shift report object to the beginning of array if found ALL
					if (strtoupper($org) === "ALL")
					{
						$reportObjForALL = new Report($org, $range, $file);
						$reportObjForALL->setFromDate($startdate);
						$reportObjForALL->setToDate($stopdate);
						//------------------------
						//create xml report for ALL
						//------------------------
						$xml = $file.".xml";
						buildXMLReportForAllOthers($report_file_path, $xml, $reportObjForALL);
						//------------------------
					}
					else
					{
						$reportObj = new Report($org, $range, $file);
						$reportObj->setFromDate($startdate);
						$reportObj->setToDate($stopdate);
						//add report object into array					
						array_push($reportsArr, $reportObj);
						//------------------------
						//create xml report
						//------------------------
						$xml = $file.".xml";
						createXML($report_file_path, $xml, $reportObj);
						//------------------------
					}
				} 
				else {
				}
			}
			
			//---------------------------------------
			//Sort report objects in array
			//---------------------------------------
			function sortReports($a, $b)
			{
				{
					if ($a->getFromDate() == $b->getFromDate()) 
					{
						return 0;
					}
					//ASC order
					//return ($a->getFromDate() < $b->getFromDate()) ? -1 : 1;
					//DESC order
					return ($a->getFromDate() < $b->getFromDate()) ? 1 : -1;
				}
			}
			
			usort($reportsArr, "sortReports");
			//move this report object to the beginning of array
			array_unshift($reportsArr, $reportObjForALL);
			//echo var_dump($reportsArr[0])."<br />";
			
			//---------------------------------------------------------
			//Define a GLOBAL Variable holds an Array of Reports on page
			//---------------------------------------------------------
			$GLOBALS['REPORTS_ARRAR_ON_PAGE'] = $reportsArr;
			function GET_REPORTS_ON_PAGE()
			{
				return $GLOBALS['REPORTS_ARRAR_ON_PAGE'];
			}
			//---------------------------------------------------------
			//HTTP GET to transfer files via FTP
			//---------------------------------------------------------
			if (isset($_GET['ftp_transfer']))
			{
				$isFTPTransfer = $_GET['ftp_transfer'];
				if ($isFTPTransfer)
				{
					//print_r(GET_REPORTS_ON_PAGE());
					foreach (GET_REPORTS_ON_PAGE() as $key => $value) 
					{
						//echo '<br />'.'<br />';
						//echo $value->getReportFile().'<br />'.'<br />';
			
						$transfer_xml_dir = "./xml/";
						$transfer_xml_file = $value->getReportFile().".xml";
						$transfer_xml_full_path = $transfer_xml_dir.$transfer_xml_file;
						if (file_exists($transfer_xml_full_path)) 
						{
							//file exists
							//echo "FTP transfer => "."[ ".$transfer_xml_full_path." ]"." in progress ...".'<br />';
							$arg_01 = TRUE;
							$arg_02 = $transfer_xml_full_path;
							//echo "arg_01 => ".$arg_01.'<br />'."arg_02 => ".$arg_02.'<br />';
							//call_user_func('FTP_TransferXMLReport', $arg_01, $arg_02);
							call_user_func_array('FTP_TransferXMLReport', array($arg_01, $arg_02));
						} 
					}
				}
			}
			/*
			print_r(GET_REPORTS_ON_PAGE());
			echo '<br />'.'<br />';
			foreach (GET_REPORTS_ON_PAGE() as $key => $value) {
				echo $value->getClient().'<br />'.'<br />';
			}
			*/
			//----------------------------------------------------------
			$html_table = buildHTMLTable($reportsArr, $reportsDir, $xmlDir);
			//----------------------------------------------------------
			
			//---------------------------------------
			//SSH transfer file
			//---------------------------------------
			function SSH_TransferXMLReport($xml_report)
			{
				$connection = ssh2_connect('Molan', 22);
				ssh2_auth_password($connection, 'yunli', 'thurs123');
				ssh2_scp_send($connection, '/xml/'.$xml_report, '/tmp/xml_reports/'.$xml_report, 0644);
			}
			
			//---------------------------------------
			//FTP transfer file
			//---------------------------------------
			function FTP_TransferXMLReport($is_new_dir, $xml_report)
			{
				//echo "prepare to transfer xml report => ".$xml_report.'<br />';
				//connect and login to FTP server
				$ftp_server = "molan.datamail.co.nz";
				$ftp_username = "yunli";
				$ftp_userpass = "thurs123";
				$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
				$login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);

				//transfer files via FTP
				$ftp_xml_reports_root_dir  = "xml_reports";
				$ftp_sub_dir_name = date("Y-m-d");
				$ftp_xml_reports_full_dir_path = $ftp_xml_reports_root_dir.'/'.$ftp_sub_dir_name;
				//echo $ftp_xml_reports_full_dir_path.'<br />';
				$ftp_home_dir = 'ftp://molan/';
				$FTP_UPLOAD_PATH = $ftp_home_dir.$ftp_xml_reports_full_dir_path;
				//echo "Connected to $ftp_server"."<br />";
				//echo "Today is " . date("Y/m/d") . "<br />";
				//echo "Today is " . date("Y.m.d") . "<br />";
				//echo "Today is " . date("Y-m-d") . "<br />";
				//echo "Today is " . date("l") . "<br />";
				
				//------------------------------
				//FTP uploads file
				//------------------------------
				//print_r(explode("/", $xml_report)).'<br />';
				$xml_report_path_array = (explode("/", $xml_report));
				$xml_report_on_server = $xml_report_path_array[2];
				if ($is_new_dir)
				{
					//access to connect to FTP server
					//using @ to stop warning
					$ftp_folder_exists = @ftp_chdir($ftp_conn, $ftp_xml_reports_full_dir_path);
					//echo "Current FTP directory => ".ftp_pwd($ftp_conn)."<br />";
					if ($ftp_folder_exists) 
					{
						//FTP uploads file
						if (ftp_put($ftp_conn, $xml_report_on_server, $xml_report, FTP_ASCII))
						{
							//echo "Successfully uploaded "."[ ".$ftp_home_dir.ftp_pwd($ftp_conn).$xml_report_on_server." ]".'<br />';
						}
						else
						{
							echo "Error uploading "."[ ".$ftp_home_dir.ftp_pwd($ftp_conn).$xml_report_on_server." ]".'<br />';
						}
					}
					else 
					{
						$mkdir = ftp_mkdir($ftp_conn, $ftp_xml_reports_full_dir_path);
						if (!$mkdir)
						{
							echo "Failed to create new directory => ".$ftp_home_dir.$ftp_xml_reports_full_dir_path.'<br />';
						}
						else
						{
							REDIRECT_TO('./reports_api.php?ftp_transfer=true');
						}
					}
				}					
				//close connection
				ftp_close($ftp_conn); 
			}
			
			//Redirect to URL
			function REDIRECT_TO($url, $statusCode=303)
			{
			   header('Location: ' . $url, true, $statusCode);
			   die();
			}

			//---------------------------------------
			//Get report range
			//---------------------------------------
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
			
			//---------------------------------------
			//Get report file details from file name
			//---------------------------------------
			function getDetailsFromFilename($filename) {
				# get the orgname from the filename, 3rd field
				$array = explode("_",$filename);
				$org = $array[2];
				$startdate = $array[0];
				$stopdate = $array[1];
				return array($org, $startdate, $stopdate );
			}
			
			//---------------------------------------
			//Get months ago
			//---------------------------------------
			function getMonthsAgo($pastEpoch, $nowEpoch) {
				if (!$nowEpoch) $nowEpoch = time();
				# inaccurate, need to improve on this.
				$monthsAgo = ($nowEpoch - $pastEpoch) / 60 / 60 / 24 / 30;
				return round($monthsAgo);
			}
			
			//---------------------------------------
			//Get client full name from a short name
			//---------------------------------------
			function getClientFullName($short_name)
			{
				$full_name = "";
				switch ($short_name) {
				case "BNZ":
					$full_name = "Bank of New Zealand (BNZ)";
					break;
				case "MNRG":
					$full_name = "Meridian Energy Limited (MNRG)";
					break;
				case "FONT":
					$full_name = "Fonterra Cooperative Group (FONT)";
					break;
				case "MSD":
					$full_name = "Ministry of Social Development (MSD)";
					break;
				case "NZP":
					$full_name = "NZ Post (NZP)";
					break;
				case "STHX":
					$full_name = "Southern Cross (STHX)";
					break;
				case "TCL":
					$full_name = "TelstraClear (TCL)";
					break;
				//for report of ALL (all of the other clients)
				case "ALL":
					$full_name = "ALL Clients";
					break;
				//default full name
				default:
					$full_name = "Client Full Name";
				}
				return $full_name;
			}
			
			//---------------------------------------
			//Build XML report for all of the others
			//---------------------------------------
			function buildXMLReportForAllOthers($from_report, $xml_output_file, $report_obj)
			{
				$reports_xml_dir = "./xml/";
				$xml_report = $reports_xml_dir.$xml_output_file;
				if (file_exists($xml_report)) 
				{
					//file already exists
				} 
				else 
				{
					if (($write_file_handler = fopen($xml_report,"a")) !== FALSE)	//append to file
					{
						fwrite($write_file_handler, "<Report>");
						fwrite($write_file_handler, "\n");
						fwrite($write_file_handler, "<Client>");
						fwrite($write_file_handler, getClientFullName($report_obj->getClient()));
						fwrite($write_file_handler, "</Client>");
						fwrite($write_file_handler, "\n");
						fwrite($write_file_handler, "<Range>");
						fwrite($write_file_handler, $report_obj->getDateRange());
						fwrite($write_file_handler, "</Range>");
						fwrite($write_file_handler, "\n");
						fwrite($write_file_handler, "<Period>");
						fwrite
						(
							$write_file_handler, 
							"Production report for period: ".
							$report_obj->getFromDate().
							" to ".
							$report_obj->getToDate()
						);
						fwrite($write_file_handler, "</Period>");
						fwrite($write_file_handler, "\n");
						//------------------------------------------
						//write and append to xml
						//------------------------------------------
						$read_file_handler = fopen($from_report,"r");
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
								$current_client_code = $field_array[0];
								if ($line_count === 1)
								{
									$previous_client_code = $field_array[0];
									writeXMLSingleClientRecord(TRUE, TRUE, $current_client_code, $previous_client_code, $write_file_handler, $field_array);
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
										writeXMLSingleClientRecord(FALSE, FALSE, $current_client_code, $previous_client_code, $write_file_handler, $field_array);
									}
									else
									{
										/*
										echo '----------------------'.'<br />';
										echo 'LINE COUNT => '.$line_count.'<br />';
										echo '$current_client_code'.' => '.$current_client_code.'<br />';
										echo '$previous_client_code'.' => '.$previous_client_code.'<br />';
										echo '----------------------'.'<br />';
										echo '<br />';
										*/
										writeXMLSingleClientRecord(TRUE, FALSE, $current_client_code, $previous_client_code, $write_file_handler, $field_array);
									}
								}
								$previous_client_code = $current_client_code;
							}
							fclose($read_file_handler);
						}
						//------------------------------------------
						fwrite($write_file_handler, "</SingleClient>");
						fwrite($write_file_handler, "\n");
						fwrite($write_file_handler, "</Report>");
						fwrite($write_file_handler, "\n");
						fclose($write_file_handler);
						//reset GLOBALS
						RESET_GLOBALS();
					}
					//write xml report
					//$field_array = (explode("|",$line));
				}
			}
			
			//---------------------------------------
			//Write XML record in report
			//---------------------------------------
			function writeXMLSingleClientRecord
				($isBeginningOfClientCode, 
				$isFirstLineOfFile,
				$current_client_code, 
				$previous_client_code, 
				$writer, 
				$record_array)
			{
				if ($isBeginningOfClientCode === TRUE)
				{
					if ($isFirstLineOfFile === TRUE)
					{
						//do not write end of Single Client 
					}
					else
					{
						//------------------------------------------
						//write TOTLAL from GLOBALS
						//------------------------------------------
						fwrite($writer, "<Totals>"."\n");
						fwrite($writer, "<SumPeriodCountTotal>");
						fwrite($writer, $GLOBALS['DocPeriodTotal']." docs");
						fwrite($writer, "</SumPeriodCountTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "<SumDocsTotal>");
						fwrite($writer, $GLOBALS['DocTotal']." docs");
						fwrite($writer, "</SumDocsTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "<SumPeriodSizeTotal>");
						fwrite($writer, buildByteField($GLOBALS['PeriodBytes']));
						fwrite($writer, "</SumPeriodSizeTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "<SumSizeTotal>");
						fwrite($writer, buildByteField($GLOBALS['BytesTotal']));
						fwrite($writer, "</SumSizeTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "</Totals>"."\n");
						//------------------------------------------
						//write end of Single Client
						//------------------------------------------
						fwrite($writer, "</SingleClient>");
						fwrite($writer, "\n");
						//reset GLOBALS
						RESET_GLOBALS();
					}
					//------------------------------------------
					//write beginning of Single Client
					//------------------------------------------
					fwrite($writer, "<SingleClient>");
					fwrite($writer, "\n");
					fwrite($writer, "<ClientCode>");
					fwrite($writer, $current_client_code);
					fwrite($writer, "</ClientCode>");
					fwrite($writer, "\n");
				}
				else
				{
				}
				//------------------------------------------
				//write single Client Record
				//------------------------------------------
				fwrite($writer, "<Record>"."\n");
					fwrite($writer, "<DocType>");
					fwrite($writer, $record_array[1]);
					fwrite($writer, "</DocType>");
					fwrite($writer, "\n");
					fwrite($writer, "<PeriodCount>");
					fwrite($writer, buildDocsField($record_array[2]));
					fwrite($writer, "</PeriodCount>");
					fwrite($writer, "\n");
					fwrite($writer, "<TotalDocs>");
					fwrite($writer, buildDocsField($record_array[3]));
					fwrite($writer, "</TotalDocs>");
					fwrite($writer, "\n");
					fwrite($writer, "<PeriodSize>");
					fwrite($writer, buildByteField($record_array[4]));
					fwrite($writer, "</PeriodSize>");
					fwrite($writer, "\n");
					fwrite($writer, "<TotalSize>");
					fwrite($writer, buildByteField($record_array[5]));
					fwrite($writer, "</TotalSize>");
					fwrite($writer, "\n");
				fwrite($writer, "</Record>"."\n");
				//------------------------------------------
				UPDATE_GLOBALS(1, $record_array);
			}
			
			//---------------------------------------
			//Write XML record in report
			//---------------------------------------
			function writeXMLRecord($template, $writer, $record_array)
			{
				if ($template === 0)
				{
					//------------------------------------------
					//write single Record
					//------------------------------------------
					fwrite($writer, "<Record>"."\n");
						fwrite($writer, "<DocType>");
						fwrite($writer, $record_array[1]);
						fwrite($writer, "</DocType>");
						fwrite($writer, "\n");
						fwrite($writer, "<PeriodCount>");
						fwrite($writer, buildDocsField($record_array[2]));
						fwrite($writer, "</PeriodCount>");
						fwrite($writer, "\n");
						fwrite($writer, "<TotalDocs>");
						fwrite($writer, buildDocsField($record_array[3]));
						fwrite($writer, "</TotalDocs>");
						fwrite($writer, "\n");
						fwrite($writer, "<PeriodSize>");
						fwrite($writer, buildByteField($record_array[4]));
						fwrite($writer, "</PeriodSize>");
						fwrite($writer, "\n");
						fwrite($writer, "<TotalSize>");
						fwrite($writer, buildByteField($record_array[5]));
						fwrite($writer, "</TotalSize>");
						fwrite($writer, "\n");
					fwrite($writer, "</Record>"."\n");
					//------------------------------------------
					UPDATE_GLOBALS(0, $record_array);
				}
				else if ($template === 1)
				{
					//------------------------------------------
					//write single Record
					//------------------------------------------
					fwrite($writer, "<Record>"."\n");
						fwrite($writer, "<Repository>");
						fwrite($writer, $record_array[1]);
						fwrite($writer, "</Repository>");
						fwrite($writer, "\n");
						fwrite($writer, "<PeriodCount>");
						fwrite($writer, buildDocsField($record_array[2]));
						fwrite($writer, "</PeriodCount>");
						fwrite($writer, "\n");
						fwrite($writer, "<TotalDocs>");
						fwrite($writer, buildDocsField($record_array[3]));
						fwrite($writer, "</TotalDocs>");
						fwrite($writer, "\n");
						fwrite($writer, "<PeriodSize>");
						fwrite($writer, buildByteField($record_array[4]));
						fwrite($writer, "</PeriodSize>");
						fwrite($writer, "\n");
						fwrite($writer, "<TotalSize>");
						fwrite($writer, buildByteField($record_array[5]));
						fwrite($writer, "</TotalSize>");
						fwrite($writer, "\n");
					fwrite($writer, "</Record>"."\n");
					//------------------------------------------
					UPDATE_GLOBALS(1, $record_array);
				}
				else if ($template === 2)
				{
					//------------------------------------------
					//write single Record
					//------------------------------------------
					fwrite($writer, "<Record>"."\n");
						fwrite($writer, "<Repository>");
						fwrite($writer, $record_array[1]);
						fwrite($writer, "</Repository>");
						fwrite($writer, "\n");
						fwrite($writer, "<PeriodCount>");
						fwrite($writer, buildDocsField($record_array[2]));
						fwrite($writer, "</PeriodCount>");
						fwrite($writer, "\n");
						fwrite($writer, "<PeriodSize>");
						fwrite($writer, buildByteField($record_array[4]));
						fwrite($writer, "</PeriodSize>");
						fwrite($writer, "\n");
					fwrite($writer, "</Record>"."\n");
					//------------------------------------------
					UPDATE_GLOBALS(2, $record_array);
				}
				else
				{
					//for TOTAL records
					if ($template === 99)
					{
						fwrite($writer, "<Totals>"."\n");
						fwrite($writer, "<SumPeriodCountTotal>");
						fwrite($writer, $GLOBALS['DocPeriodTotal']." docs");
						fwrite($writer, "</SumPeriodCountTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "<SumDocsTotal>");
						fwrite($writer, $GLOBALS['DocTotal']." docs");
						fwrite($writer, "</SumDocsTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "<SumPeriodSizeTotal>");
						fwrite($writer, buildByteField($GLOBALS['PeriodBytes']));
						fwrite($writer, "</SumPeriodSizeTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "<SumSizeTotal>");
						fwrite($writer, buildByteField($GLOBALS['BytesTotal']));
						fwrite($writer, "</SumSizeTotal>");
						fwrite($writer, "\n");
						fwrite($writer, "</Totals>"."\n");
					}
					else
					{}
				}
			}
			
			//---------------------------------------
			//Create XML report
			//---------------------------------------
			function createXML($from_report, $xml_output_file, $report_obj)
			{
				$reports_xml_dir = "./xml/";
				$xml_report = $reports_xml_dir.$xml_output_file;
				if (file_exists($xml_report)) 
				{
					//echo "The file $xml_report Exists"."<br />";
				} 
				else 
				{
					//if (($write_file_handler = fopen($xml_report,"w")) !== FALSE)
					if (($write_file_handler = fopen($xml_report,"a")) !== FALSE)	//append to file
					{
						fwrite($write_file_handler, "<Report>"."\n");
						fwrite($write_file_handler, "<Client>");
						fwrite($write_file_handler, getClientFullName($report_obj->getClient()));
						fwrite($write_file_handler, "</Client>");
						fwrite($write_file_handler, "\n");
						fwrite($write_file_handler, "<Range>");
						fwrite($write_file_handler, $report_obj->getDateRange());
						fwrite($write_file_handler, "</Range>");
						fwrite($write_file_handler, "\n");
						fwrite($write_file_handler, "<Period>");
						fwrite
						(
							$write_file_handler, 
							"Production report for period: ".
							$report_obj->getFromDate().
							" to ".
							$report_obj->getToDate()
						);
						fwrite($write_file_handler, "</Period>");
						fwrite($write_file_handler, "\n");
						//------------------------------------------
						//write and append to xml
						//------------------------------------------
						$read_file_handler = fopen($from_report,"r");
						if ($read_file_handler !== FALSE) {
							while (($line = fgets($read_file_handler)) !== false) {
								//process the line read
								//echo $line."<br />";
								//print_r (explode("|",$line));
								$field_array = (explode("|",$line));
								//------------------------------------------
								//different templates for different clients
								//------------------------------------------
								if ($report_obj->getClient() === 'BNZ')
									writeXMLRecord(0, $write_file_handler, $field_array);
								else if ($report_obj->getClient() === 'TCL')
									writeXMLRecord(0, $write_file_handler, $field_array);
								else if ($report_obj->getClient() === 'MNRG')
									writeXMLRecord(1, $write_file_handler, $field_array);
								else if ($report_obj->getClient() === 'MSD')
									writeXMLRecord(1, $write_file_handler, $field_array);
								else if ($report_obj->getClient() === 'STHX')
									writeXMLRecord(1, $write_file_handler, $field_array);
								else if ($report_obj->getClient() === 'NZP')
									writeXMLRecord(1, $write_file_handler, $field_array);
								else if ($report_obj->getClient() === 'FONT')
									writeXMLRecord(2, $write_file_handler, $field_array);
								else
								{}
								//------------------------------------------
							}
							fclose($read_file_handler);
						}
						//------------------------------------------
						//------------------------------------------
						//write TOTLAL from GLOBALS
						//------------------------------------------
						writeXMLRecord(99, $write_file_handler, null);
						//------------------------------------------
						fwrite($write_file_handler, "</Report>"."\n");
						fclose($write_file_handler);
						//reset GLOBALS
						RESET_GLOBALS();
					}
				}
			}
			
			//---------------------------------------
			//Reset GLOBALS variables
			//---------------------------------------
			function RESET_GLOBALS()
			{
				//------------------------------------------
				//reset GLOBALS
				//------------------------------------------
				foreach (array('DocPeriodTotal', 'DocTotal', 'PeriodBytes', 'BytesTotal') as $var)
				{
					$GLOBALS[$var] = 0;
				}
			}
			
			//---------------------------------------
			//Reset GLOBALS variables
			//---------------------------------------
			function RESET_GLOBALS_IN_REPORT_OBJ($_report_obj)
			{
				//------------------------------------------
				//show TOTALS
				//------------------------------------------
				echo "----------------------"."<br />";
				echo $_report_obj->getClient().'<br />';
				echo $_report_obj->getDateRange().'<br />';
				echo "Production report for period: ".$_report_obj->getFromDate()." to ".$_report_obj->getToDate();
				echo '<br />';
				echo "----------------------"."<br />";
				echo $GLOBALS['DocPeriodTotal'].'<br />';
				echo $GLOBALS['DocTotal'].'<br />';
				echo buildByteField($GLOBALS['PeriodBytes']).'<br />';
				echo buildByteField($GLOBALS['BytesTotal']).'<br />';
				echo "----------------------"."<br />";
				//------------------------------------------
				//reset GLOBALS
				//------------------------------------------
				foreach (array('DocPeriodTotal', 'DocTotal', 'PeriodBytes', 'BytesTotal') as $var)
				{
					$GLOBALS[$var] = 0;
				}
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
			
			//---------------------------------------
			//Async Task Callback function
			//---------------------------------------
			//echo "We are ready to do the tasks".'<br />';
			//AsyncTask('pass parameter in async task' , 'callback_function');
			
			//Task for Async call
			function AsyncTask($parameters, $callback = false)
			{
				echo "Task is in progress ...".'<br />';
				//echo "pass parameters => ".(string)$parameters.'<br />';
				//echo "pass parameters => ".strval($parameters).'<br />';
				$callback_parameter_msg = "this is callback message";
				//now call the callback
				if($callback !== false)
				{    
					call_user_func($callback, $callback_parameter_msg);
				}
			}
			 
			//Callback function
			function callback_function($callback_msg) 
			{
				echo "This is in Callback function".'<br />';
				echo "Message => ".$callback_msg.'<br />';
				echo '<br />';
			}
			//---------------------------------------
        ?>
		<div class="panel panel-default">
			<div class="jumbotron panel-heading text-center">
				<h2>VR2 Reports Dashboard</h2>
				<hr width="88%"/>
				<p>
				  <!-- <img class="center-block" style="padding-top:0px; padding-bottome:0px;" src="nzpost.png"> -->
				  <img class="text-center" style="padding-top:0px; padding-bottome:0px;" src="nzpost.png">
				</p>
			</div>
			<div class="panel-body">
				<div class="container">
				  <div class="row">
					<div class="col-md-12">
						<?=$html_table?>
					</div>
				  </div>
				</div>
			</div>
			<!-- Modal Start -->
			<div class="modal fade" id="FTPTansferDialog" role="dialog">
				<div class="modal-dialog">
				<!-- Modal Content Start -->
					<div class="modal-content">
						<div class="modal-header modal-header-primary">
							<button type="button" class="close" data-dismiss="modal">&times;</button>
							<h4 class="modal-title">Fetching and Transferring Files...</h4>
						</div>
						<div class="modal-body">
						<p>
							<img class="center-block" style="padding-top:10px; padding-bottome:5px; height:100px; width:100px;" src="loading_in_progress.svg">
						</p>
						</div>
						<div class="modal-footer modal-footer-primary">
							<button type="button" class="btn btn-primary" data-dismiss="modal" onclick="window.location.href='/ReportsAPI/reports_api.php';">Close</button>
						</div>
					</div>
				<!-- Modal Content End -->
				</div>
			</div>
			<!-- Modal End -->
		</div>
    </body>
</html>
