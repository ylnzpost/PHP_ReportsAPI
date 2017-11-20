<?php
	//GLOBALS variables
	$GLOBALS['reportsDir'] = "./reports/";
	$GLOBALS['xmlDir'] = "./xml/";
	$GLOBALS['reportsDirFromAPI'] = "../reports/";
	$GLOBALS['VRAuditDirFromAPI'] = "../VRAudit/";
	//main array objects
	$reportsArr = array();
	$auditLogsArr = array();
	$dailyLogObjectsArr = array();
	$file_array = array();
	//daily log summary array objects
	$updateDocumentProperties_Sum_Arr = array();
	$doSearch_Sum_Arr = array();
	$getResultCount_Sum_Arr = array();
	$getSearchProfiles_Sum_Arr = array();
	$getDocument_Sum_Arr = array();
	$getSingleDocument_Sum_Arr = array();
	//special report object for ALL
	$reportObjForALL = null;
	//define variables for TOTAL
	$DocPeriodTotal = 0;
	$DocTotal = 0;
	$PeriodBytes = 0;
	$BytesTotal = 0;
	/*
	$reportsDir = $GLOBALS['reportsDir'];
	if ( !$dir_handler = opendir($reportsDir) ){
		echo "Cannot open dir $reportsDir";
		exit;
	}
	*/
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
	//Get months ago
	//---------------------------------------
	function getMonthsAgo($pastEpoch, $nowEpoch) {
		if (!$nowEpoch) $nowEpoch = time();
		# inaccurate, need to improve on this.
		$monthsAgo = ($nowEpoch - $pastEpoch) / 60 / 60 / 24 / 30;
		return round($monthsAgo);
	}
			
	//---------------------------------
	//Report Object
	//---------------------------------
	class Report
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
	//Document Object
	//---------------------------------
	class Document
	{
		protected $doc_type;
		protected $total_count_val_01;
		protected $total_count_val_02;
		protected $total_count_val_03;
		protected $total_count_val_04;
		
		public function __construct($docType, $totalCountVal01, $totalCountVal02, $totalCountVal03, $totalCountVal04)
		{
			$this->doc_type = $docType;
			$this->total_count_val_01 = $totalCountVal01;
			$this->total_count_val_02 = $totalCountVal02;
			$this->total_count_val_03 = $totalCountVal03;
			$this->total_count_val_04 = $totalCountVal04;
		}
		
		public function getDocType()
		{
			return $this->doc_type;
		}
		
		public function getTotalCountVal01()
		{
			return $this->total_count_val_01;
		}
		
		public function getTotalCountVal02()
		{
			return $this->total_count_val_02;
		}
		
		public function getTotalCountVal03()
		{
			return $this->total_count_val_03;
		}
		
		public function getTotalCountVal04()
		{
			return $this->total_count_val_04;
		}
	}
	
	//---------------------------------
	//DailyLog Object
	//---------------------------------
	class DailyLog
	{
		protected $log_date_time;
		protected $log_audit_type;
		protected $log_client;
		protected $log_emial;
		protected $action_qty_value;
		protected $action_http_method;
		protected $action_ip;
		protected $action_from_vr;
		protected $action_full_criteria_string;
		
		public function __construct
			($logDateTime, 
			$logAuditType, 
			$logClient, 
			$logEmial, 
			$actionQtyValue,
			$actionHttpMethod,
			$actionIP,
			$actionFromVR,
			$actionFullCriteriaString)
		{
			$this->log_date_time = $logDateTime;
			$this->log_audit_type = $logAuditType;
			$this->log_client = $logClient;
			$this->log_emial = $logEmial;
			$this->action_qty_value = $actionQtyValue;
			$this->action_http_method = $actionHttpMethod;
			$this->action_ip = $actionIP;
			$this->action_from_vr = $actionFromVR;
			$this->action_full_criteria_string = $actionFullCriteriaString;
		}
		
		public function getLogDateTime()
		{
			return $this->log_date_time;
		}
		
		public function getLogAuditType()
		{
			return $this->log_audit_type;
		}
		
		public function getLogClient()
		{
			return $this->log_client;
		}
		
		public function getLogEmail()
		{
			return $this->log_emial;
		}
		
		public function getActionQtyValue()
		{
			return $this->action_qty_value;
		}
		
		public function getActionHttpMethod()
		{
			return $this->action_http_method;
		}
		
		public function getActionIP()
		{
			return $this->action_ip;
		}
		
		public function getActionFromVR()
		{
			return $this->action_from_vr;
		}
		
		public function getActionFullCriteriaString()
		{
			return $this->action_full_criteria_string;
		}
	}
?>