<?php
	//define('__ROOT__', dirname(dirname(__FILE__))); 
	//require_once(__ROOT__.'/include_php'.'/Objects.php');
	require_once('../include_php/Objects.php');
	//header('Content-Type: text/xml');	//for dev testing
	//header('Content-Type: text/html');	//for frv testing
	header('Content-Type: text/pain');	//for prod
		
	$api_code = $_GET['api_code'];
	$client_code = $_GET['client_code'];
	$reportsDir = $GLOBALS['reportsDirFromAPI'];
	$auditLogsDir = $GLOBALS['VRAuditDirFromAPI'];
	$GLOBALS['PLAIN_TEXT_NEW_LINE'] = chr(13).chr(10);
	
	if (strtoupper($api_code) === strtoupper("weeklyReports"))
	{
		$clientAuditLogsDir = $auditLogsDir.$client_code;
		if (!$dir_handler = opendir($clientAuditLogsDir)) {
			echo "Cannot open dir $reportsDir";
			exit;
		}
		
		/*-------------------------------------------------------------------
		/* These are the VR services we need to check for daily log
		/*-------------------------------------------------------------------
		/*-------------------------------------------------------------------
		/vretrieve/webservices/metadataservice/updateDocumentProperties
		/vretrieve/webservices/searchservice/doSearch
		/vretrieve/webservices/searchservice/getResultCount
		/vretrieve/webservices/searchservice/getSearchProfiles
		/vretrieve/webservices/viewdocumentservice/getDocument
		/vretrieve/webservices/viewdocumentservice/getSingleDocument 
		---------------------------------------------------------------------*/
		//---------------------------------------------------------------------
		$ACTION_UpdateDocumentProperties = "updateDocumentProperties";
		$ACTION_DoSearch = "doSearch";
		$ACTION_GetResultCount = "getResultCount";
		$ACTION_GetSearchProfiles = "getSearchProfiles";
		$ACTION_GetDocument = "getDocument";
		$ACTION_GetSingleDocument = "getSingleDocument";
		//---------------------------------------------------------------------
		$SUM_UpdateDocumentProperties = 0;
		$SUM_DoSearch = 0;
		$SUM_GetResultCount = 0;
		$SUM_GetSearchProfiles = 0;
		$SUM_GetDocument = 0;
		$SUM_GetSingleDocument = 0;
		//---------------------------------------------------------------------
		$COUNT_INVALID_ACTION = 0;
		//---------------------------------------------------------------------
		
		while (($log_file = readdir($dir_handler)) !== false ) {
			$audit_log_file_path = $clientAuditLogsDir.'/'.$log_file;
			$auditLogObj = null;
			//if (!is_file($audit_log_file_path) || !preg_match( "/\.log$/i", $audit_log_file_path)) continue;
			if (is_file($audit_log_file_path) && preg_match( "/\.log$/i", $audit_log_file_path))
			{
				//echo '[ '.'Audit LOG File'.' -> '.$audit_log_file_path.' ]';
				//echo $GLOBALS['PLAIN_TEXT_NEW_LINE'];
				//echo $GLOBALS['PLAIN_TEXT_NEW_LINE'];
				$lineCount = 0;
				$read_file_handler = fopen($audit_log_file_path, "r");
				if ($read_file_handler !== FALSE) {
					while (($line = fgets($read_file_handler)) !== false) {
						$lineCount += 1;
						//$field_array = (explode("\t", $line));
						$field_array = (preg_split("/\t/", $line));
						//print_r($field_array);
						//echo $GLOBALS['PLAIN_TEXT_NEW_LINE'];
						if (count($field_array) === 10)
						{
							try
							{
								$dateTime = $field_array[0];
								$auditType = $field_array[1];
								$client = $field_array[2];
								$email = $field_array[4];
								$actionQtyVal = $field_array[5];
								$actionHttpMethod = $field_array[6];
								$actionIP = $field_array[7];
								$actionFromVR = $field_array[8];
								$actionFullCriteriaStr = $field_array[9];
							}
							catch (Exception $ex)
							{
								echo $lineCount.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
								echo 'Error Message -> '.$ex->getMessage();
							}
						}
						//---------------------------------------
						//check which action is triggered
						//---------------------------------------
						$action_fields_arr = (explode("/", $actionFromVR));
						//print_r($action_fields_arr);
						//echo count($action_fields_arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
						if (count($action_fields_arr) === 5)
						{
							//print_r($action_fields_arr);
							//echo count($action_fields_arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
							try
							{
								$action = $action_fields_arr[4];
								//echo $action;
								//echo $GLOBALS['PLAIN_TEXT_NEW_LINE'];
								//echo $GLOBALS['PLAIN_TEXT_NEW_LINE'];
								switch ($action) {
									case $ACTION_UpdateDocumentProperties:
										$SUM_UpdateDocumentProperties += 1;
										break;
									case $ACTION_DoSearch:
										$SUM_DoSearch += 1;
										break;
									case $ACTION_GetResultCount:
										$SUM_GetResultCount += 1;
										break;
									case $ACTION_GetSearchProfiles:
										$SUM_GetSearchProfiles += 1;
										break;
									case $ACTION_GetDocument:
										$SUM_GetDocument += 1;
										break;
									case $ACTION_GetSingleDocument:
										$SUM_GetSingleDocument += 1;
										break;
									default:
										$COUNT_INVALID_ACTION += 1;
										//echo '[ '.'VR Action'.' at line '.$lineCount.' is Invalid'.' ]'.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
								}
							}
							catch (Exception $ex)
							{
								echo $lineCount.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
								echo 'Error Message -> '.$ex->getMessage();
							}
						}
						//---------------------------------------
						/*
						$auditLogObj = new DailyLog
							($dateTime,
							$auditType,
							$client,
							$email,
							$actionQtyVal,
							$actionHttpMethod,
							$actionIP,
							$actionFromVR,
							$actionFullCriteriaStr);
						*/
					}
					fclose($read_file_handler);
				}
				//add current daily log summary value into array
				$key_val_arr = array($audit_log_file_path=>$SUM_UpdateDocumentProperties);
				array_push($updateDocumentProperties_Sum_Arr, $key_val_arr);
				$key_val_arr = array($audit_log_file_path=>$SUM_DoSearch);
				array_push($doSearch_Sum_Arr, $key_val_arr);
				$key_val_arr = array($audit_log_file_path=>$SUM_GetResultCount);
				array_push($getResultCount_Sum_Arr, $key_val_arr);
				$key_val_arr = array($audit_log_file_path=>$SUM_GetSearchProfiles);
				array_push($getSearchProfiles_Sum_Arr, $key_val_arr);
				$key_val_arr = array($audit_log_file_path=>$SUM_GetDocument);
				array_push($getDocument_Sum_Arr, $key_val_arr);
				$key_val_arr = array($audit_log_file_path=>$SUM_GetSingleDocument);
				array_push($getSingleDocument_Sum_Arr, $key_val_arr);
				//add current audit log file into array
				array_push($auditLogsArr, $audit_log_file_path);
			}
		}
		//sort array in DESC order (shift the latest LOG information to the front)
		rsort($auditLogsArr);
		krsort($updateDocumentProperties_Sum_Arr);
		krsort($doSearch_Sum_Arr);
		krsort($getResultCount_Sum_Arr);
		krsort($getSearchProfiles_Sum_Arr);
		krsort($getDocument_Sum_Arr);
		krsort($getSingleDocument_Sum_Arr);
		//limit number of items in array (weekly - 7 days)
		$auditLogsArr = array_slice($auditLogsArr, 0, 7);
		$auditLogsArrReverse = $auditLogsArr;
		krsort($auditLogsArrReverse);
		//$updateDocumentProperties_Sum_Arr = array_slice($updateDocumentProperties_Sum_Arr, 0, 7);
		//print_r($updateDocumentProperties_Sum_Arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
		//$doSearch_Sum_Arr = array_slice($doSearch_Sum_Arr, 0, 7);
		//print_r($doSearch_Sum_Arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
		//$getResultCount_Sum_Arr = array_slice($getResultCount_Sum_Arr, 0, 7);
		//print_r($getResultCount_Sum_Arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
		//$getSearchProfiles_Sum_Arr = array_slice($getSearchProfiles_Sum_Arr, 0, 7);
		//print_r($getSearchProfiles_Sum_Arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
		//$getDocument_Sum_Arr = array_slice($getDocument_Sum_Arr, 0, 7);
		//print_r($getDocument_Sum_Arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
		//$getSingleDocument_Sum_Arr = array_slice($getSingleDocument_Sum_Arr, 0, 7);
		//print_r($getSingleDocument_Sum_Arr).$GLOBALS['PLAIN_TEXT_NEW_LINE'];
		/*
		//display array data
		print_r($auditLogsArr);
		print_r($auditLogsArrReverse);
		print_r($updateDocumentProperties_Sum_Arr);
		print_r($doSearch_Sum_Arr);
		print_r($getResultCount_Sum_Arr);
		print_r($getSearchProfiles_Sum_Arr);
		print_r($getDocument_Sum_Arr);
		print_r($getSingleDocument_Sum_Arr);
		*/
	}
	else
	{
		if (!$dir_handler = opendir($reportsDir)) {
			echo "Cannot open dir $reportsDir";
			exit;
		}
	
		while (($file = readdir($dir_handler)) !== false ) {
			$report_file_path = $reportsDir.$file;
			if (!is_file($report_file_path) || !preg_match( "/\.report/", $file)) continue;
			list($org, $startdate, $stopdate)= getDetailsFromFilename($file);
			$range = getRangeString($startdate, $stopdate);
			$reportObj = null;
			$skiplist = array("ALL-RSP", "ALL02", "ACP");
			if (in_array($org, $skiplist)) continue;
			if (strtoupper($org) === strtoupper($client_code))
			{
				$reportObj = new Report($org, $range, $file);
				$reportObj->setFromDate($startdate);
				$reportObj->setToDate($stopdate);
				//add report object into array	
				array_push($reportsArr, $reportObj);
			}
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
	
	//usort($reportsArr, "sortReports");
	
	//-------------------------------------------------------
	//get xml api for all reports
	//-------------------------------------------------------
	function getAllReportsXML($for_client_code, $obj_arr)
	{
		$xmlReportFiles = getAllReportFiles($obj_arr);
		
		$xmlStr =
			"<?xml version='1.0' encoding='UTF-8'?>".
			"<data>".
			"<reports>".
			"<client_code>".
			$for_client_code.
			"</client_code>".
			$xmlReportFiles.
			"</reports>".
			"</data>";
		return $xmlStr;
	}
	
	//-------------------------------------------------------
	//get xml api for monthly reports
	//-------------------------------------------------------
	function getMonthlyReportsXML($for_client_code, $obj_arr)
	{
		$xmlReportFiles = getMonthlyReportFiles($obj_arr);
		
		$xmlStr =
			"<?xml version='1.0' encoding='UTF-8'?>".
			"<data>".
			"<reports>".
			"<client_code>".
			$for_client_code.
			"</client_code>".
			$xmlReportFiles.
			"</reports>".
			"</data>";
		return $xmlStr;
	}
	
	//-------------------------------------------------------
	//get documents for doc types
	//-------------------------------------------------------
	function getDocsXML($docObjArr)
	{
		$reportDocStartTag = "<doc>";
		$reportDocCloseTag = "</doc>";
		$reportDocTypeStartTag = "<doc_type>";
		$reportDocTypeCloseTag = "</doc_type>";
		$docTypeTotal01_StartTag = "<doc_type_total_01>";
		$docTypeTotal01_CloseTag = "</doc_type_total_01>";
		$docTypeTotal02_StartTag = "<doc_type_total_02>";
		$docTypeTotal02_CloseTag = "</doc_type_total_02>";
		$docTypeTotal03_StartTag = "<doc_type_total_03>";
		$docTypeTotal03_CloseTag = "</doc_type_total_03>";
		$docTypeTotal04_StartTag = "<doc_type_total_04>";
		$docTypeTotal04_CloseTag = "</doc_type_total_04>";
		
		$docXML = "";
		$xmlStr = "";
		//print_r($docObjArr);
		
		foreach ($docObjArr as $_docObj) {
			//echo '['.$docArrVal->getDocType().']';
			$docXML =
				$reportDocStartTag.
				$reportDocTypeStartTag.
				$_docObj->getDocType().
				$reportDocTypeCloseTag.
				$docTypeTotal01_StartTag.
				$_docObj->getTotalCountVal01().
				$docTypeTotal01_CloseTag.
				$docTypeTotal02_StartTag.
				$_docObj->getTotalCountVal02().
				$docTypeTotal02_CloseTag.
				$docTypeTotal03_StartTag.
				$_docObj->getTotalCountVal03().
				$docTypeTotal03_CloseTag.
				$docTypeTotal04_StartTag.
				$_docObj->getTotalCountVal04().
				$docTypeTotal04_CloseTag.
				$reportDocCloseTag;
			$xmlStr .= $docXML;
		}
		return $xmlStr;
	}
	
	//-------------------------------------------------------
	//get all reports files for getAllReportsXML()
	//-------------------------------------------------------
	function getAllReportFiles($objArr)
	{
		$reportStartTag = "<report>";
		$fileStartTag = "<report_file>";
		$rangeStartTag = "<report_range>";
		$fileCloseTag = "</report_file>";
		$rangeCloseTag = "</report_range>";
		$reportCloseTag = "</report>";
		$xmlString = "";
		foreach ($objArr as $reportObj) {
			$xmlString .= $reportStartTag;
			$xmlString .= $fileStartTag;
			$xmlString .= $reportObj->getReportFile();
			$xmlString .= $fileCloseTag;
			$xmlString .= $rangeStartTag;
			$xmlString .= $reportObj->getDateRange();
			$xmlString .= $rangeCloseTag;
			$xmlString .= $reportCloseTag;
		}
		return $xmlString;
	}

	//-------------------------------------------------------
	//get all monthly reports files for getMonthlyReportsXML()
	//-------------------------------------------------------
	function getMonthlyReportFiles($objArr)
	{
		$reportStartTag = "<report>";
		$fileStartTag = "<report_file>";
		$rangeStartTag = "<report_range>";
		$fileCloseTag = "</report_file>";
		$rangeCloseTag = "</report_range>";
		$reportCloseTag = "</report>";
		$reportFromDateStartTag = "<from_date>";
		$reportFromDateCloseTag = "</from_date>";
		$reportToDateStartTag = "<to_date>";
		$reportToDateCloseTag = "</to_date>";
		$reportDocGroupStartTag = "<doc_group>";
		$reportDocGroupCloseTag = "</doc_group>";
		$total01_StartTag = "<total_01>";
		$total01_CloseTag = "</total_01>";
		$total02_StartTag = "<total_02>";
		$total02_CloseTag = "</total_02>";
		$total03_StartTag = "<total_03>";
		$total03_CloseTag = "</total_03>";
		$total04_StartTag = "<total_04>";
		$total04_CloseTag = "</total_04>";

		$monthlyReportObjArr = array();
		//get only monthly reports
		foreach ($objArr as $reportObj) {
			if ($reportObj->getDateRange() === '1 month')
			{
				array_push($monthlyReportObjArr, $reportObj);
			}
		}
		//limit the monthly reports array with only 12 months
		array_splice($monthlyReportObjArr, 12);
		//write xml as API response
		$xmlString = "";
		foreach ($monthlyReportObjArr as $reportObj) {
			//read each file
			$dir = $GLOBALS['reportsDirFromAPI'];
			$doc_type = "";
			$doc_count_val_01 = 0;
			$doc_count_val_02 = 0;
			$doc_count_val_03 = 0;
			$doc_count_val_04 = 0;
			//$singleDocValArr = array();
			$docsArr = array();
			$total_val_01 = 0;
			$total_val_02 = 0;
			$total_val_03 = 0;
			$total_val_04 = 0;
			$file_full_path = $dir.$reportObj->getReportFile();
			if (file_exists($file_full_path)) {
				//$xmlString .= "<full_path>".$file_full_path."</full_path>";
				$read_file_handler = fopen($file_full_path,"r");	
				if ($read_file_handler !== FALSE) {
					while (($line = fgets($read_file_handler)) !== false) {
						//process the line read
						//print_r(explode("|",$line));
						$field_array = (explode("|", $line));
						//print_r($field_array);
						$doc_type = $field_array[1];
						$doc_count_val_01 = $field_array[2];
						$doc_count_val_02 = $field_array[3];
						$doc_count_val_03 = $field_array[4];
						$doc_count_val_04 = $field_array[5];
						$docObj = new Document($doc_type, $doc_count_val_01, $doc_count_val_02, $doc_count_val_03, $doc_count_val_04);
						array_push($docsArr, $docObj);
						$total_val_01 += (int)($field_array[2]);
						$total_val_02 += (int)($field_array[3]);
						$total_val_03 += (int)($field_array[4]);
						$total_val_04 += (int)($field_array[5]);
					}
					fclose($read_file_handler);
				}
			}
			//generic values
			$xmlString .= $reportStartTag;
			$xmlString .= $fileStartTag;
			$xmlString .= $reportObj->getReportFile();
			$xmlString .= $fileCloseTag;
			$xmlString .= $rangeStartTag;
			$xmlString .= $reportObj->getDateRange();
			$xmlString .= $rangeCloseTag;
			$xmlString .= $reportFromDateStartTag;
			$xmlString .= $reportObj->getFromDate();
			$xmlString .= $reportFromDateCloseTag;
			$xmlString .= $reportToDateStartTag;
			$xmlString .= $reportObj->getToDate();
			$xmlString .= $reportToDateCloseTag;
			//doc types
			$xmlString .= $reportDocGroupStartTag;
			$xmlString .= getDocsXML($docsArr);
			$xmlString .= $reportDocGroupCloseTag;
			//total values
			$xmlString .= $total01_StartTag;
			$xmlString .= $total_val_01;
			$xmlString .= $total01_CloseTag;
			$xmlString .= $total02_StartTag;
			$xmlString .= $total_val_02;
			$xmlString .= $total02_CloseTag;
			$xmlString .= $total03_StartTag;
			$xmlString .= $total_val_03;
			$xmlString .= $total03_CloseTag;
			$xmlString .= $total04_StartTag;
			$xmlString .= $total_val_04;
			$xmlString .= $total04_CloseTag;
			$xmlString .= $reportCloseTag;
		}
		return $xmlString;
	}
	
	function getWeeklyAuditLogReportXML(
		$for_client_code, 
		$audit_logs_arr,
		$sum_arr_01,
		$sum_arr_02,
		$sum_arr_03,
		$sum_arr_04,
		$sum_arr_05,
		$sum_arr_06)
	{
		//echo count($audit_logs_arr).chr(13).chr(10);
		$xmlStr =
			"<?xml version='1.0' encoding='UTF-8'?>".
			"<data>".
			"<logs>".
			"<client_code>".
			$for_client_code.
			"</client_code>";
		
		foreach ($audit_logs_arr as $val) {
			$xmlStr .= "<audit_log>";
			$xmlStr .= "<log_file>".$val."</log_file>";
			foreach ($sum_arr_01 as $item_arr_val)
			{
				/*
				foreach ($item_arr_val as $item_val)
				{
					$key = key($item_arr_val);
					echo $key.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
				}
				*/
				foreach($item_arr_val as $key => $value)
				{
					$key_val = $key;
					if ($key_val === $val)
					{
						//echo $key_val.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
						//echo $value.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
						$xmlStr .= "<update_doc_properties_sum>".$value."</update_doc_properties_sum>";
						break;
					}
				}
			}
			
			foreach ($sum_arr_02 as $item_arr_val)
			{
				foreach($item_arr_val as $key => $value)
				{
					$key_val = $key;
					if ($key_val === $val)
					{
						$xmlStr .= "<do_search_sum>".$value."</do_search_sum>";
						break;
					}
				}
			}
			
			foreach ($sum_arr_03 as $item_arr_val)
			{
				foreach($item_arr_val as $key => $value)
				{
					$key_val = $key;
					if ($key_val === $val)
					{
						$xmlStr .= "<result_count_sum>".$value."</result_count_sum>";
						break;
					}
				}
			}
			
			foreach ($sum_arr_04 as $item_arr_val)
			{
				foreach($item_arr_val as $key => $value)
				{
					$key_val = $key;
					if ($key_val === $val)
					{
						$xmlStr .= "<search_profiles_sum>".$value."</search_profiles_sum>";
						break;
					}
				}
			}
			
			foreach ($sum_arr_05 as $item_arr_val)
			{
				foreach($item_arr_val as $key => $value)
				{
					$key_val = $key;
					if ($key_val === $val)
					{
						$xmlStr .= "<document_sum>".$value."</document_sum>";
						break;
					}
				}
			}
			
			foreach ($sum_arr_06 as $item_arr_val)
			{
				foreach($item_arr_val as $key => $value)
				{
					$key_val = $key;
					if ($key_val === $val)
					{
						$xmlStr .= "<single_document_sum>".$value."</single_document_sum>";
						break;
					}
				}
			}
			
			$xmlStr .= "</audit_log>";
		}
			
		$xmlStr .= 
			"</logs>".
			"</data>";
		return $xmlStr;
	}
/*
$xmlStr = <<<XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>PHP: Behind the Parser</title>
  <characters>
   <character>
    <name>Ms. Coder</name>
    <actor>Onlivia Actora</actor>
   </character>
   <character>
    <name>Mr. Coder</name>
    <actor>El Act&#211;r</actor>
   </character>
  </characters>
  <plot>
   So, this language. It's like, a programming language. Or is it a
   scripting language? All is revealed in this thrilling horror spoof
   of a documentary.
  </plot>
  <great-lines>
   <line>PHP solves all my web problems</line>
  </great-lines>
  <rating type="thumbs">7</rating>
  <rating type="stars">5</rating>
 </movie>
</movies>
XML;
echo $xmlStr;
*/
	/*
	$xml = new SimpleXMLElement($xmlStr);
	echo $xml->movie[0]->title;
	echo '<br />';
	
	$xml = simplexml_load_string($xmlStr);
	echo $xml;
	echo '<br />';
	print_r($xml);
	echo '<br />';
	*/
	
	//main to get different API responses
	switch ($api_code) {
		case "allReports":
			echo getAllReportsXML($client_code, $reportsArr);
			break;
		case "monthlyReports":
			echo getMonthlyReportsXML($client_code, $reportsArr);
			break;
		case "weeklyReports":
			echo getWeeklyAuditLogReportXML
				($client_code, 
				$auditLogsArrReverse,
				$updateDocumentProperties_Sum_Arr,
				$doSearch_Sum_Arr,
				$getResultCount_Sum_Arr,
				$getSearchProfiles_Sum_Arr,
				$getDocument_Sum_Arr,
				$getSingleDocument_Sum_Arr);
			break;
		case "yearlyReports":
			echo '[ '.'api_code'.'='.$api_code.' ]'.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
			break;
		default:
			echo '[ '.'api_code '.$api_code.' is Invalid'.' ]'.$GLOBALS['PLAIN_TEXT_NEW_LINE'];
	}
?>