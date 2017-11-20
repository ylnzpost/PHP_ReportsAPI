<!DOCTYPE HTML>
<html>
    <head>
		<title>Dashboard</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="./bootstrap_lib/bootstrap.min.css">
		<link rel="stylesheet" href="./bootstrap_lib/bootstrap-dialog.min.css">
		<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css"> -->
		<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> -->
		<!-- <link rel="stylesheet" href="./bootstrap_lib/font-awesome.min.css"> -->
		<script src="./bootstrap_lib/jquery.min.js"></script>
		<script src="./bootstrap_lib/bootstrap.min.js"></script>
		<script src="./bootstrap_lib/bootstrap-dialog.min.js"></script>
		<script src="./bootstrap_lib/Chart.js"></script>
		<script src="./bootstrap_lib/google_charts_loader.js"></script>
		<!-- <script src="./bootstrap_lib/angular-1.6.4.min.js"></script> -->
		<!-- <script src="./bootstrap_lib/angular-chart.js"></script> -->
        <script>
			//JQuery starts
            $(document).ready(function() {
                //jquery
                var href = $(location).attr('href');
                var url = window.location;
                //pure javascript
                var pathname = window.location.pathname;
                //alert(href + "\r\n" + url + "\r\n" + pathname);
                var client_code_string = href.split("?")[1];
				
				//----------------------------------------------------
				//API GET URLs - DEV
				//----------------------------------------------------
                //var API_GET_ALL_REPORTS_URL = "/ReportsAPI/api/api_xml_response.php" + "?" + client_code_string + "&" + "api_code=allReports";
				//var API_GET_MONTHLY_REPORTS_URL = "/ReportsAPI/api/api_xml_response.php" + "?" + client_code_string + "&" + "api_code=monthlyReports";
				//var API_GET_WEEKLY_REPORTS_URL = "/ReportsAPI/api/api_xml_response.php" + "?" + client_code_string + "&" + "api_code=weeklyReports";
				//----------------------------------------------------
				//----------------------------------------------------
				//API GET URLs - PROD
				//----------------------------------------------------
                var API_GET_ALL_REPORTS_URL = "/vr2/doctypestats/api/api_xml_response.php" + "?" + client_code_string + "&" + "api_code=allReports";
				var API_GET_MONTHLY_REPORTS_URL = "/vr2/doctypestats/api/api_xml_response.php" + "?" + client_code_string + "&" + "api_code=monthlyReports";
				var API_GET_WEEKLY_REPORTS_URL = "/vr2/doctypestats/api/api_xml_response.php" + "?" + client_code_string + "&" + "api_code=weeklyReports";
				//----------------------------------------------------
				
				//----------------------------------------------------
				//show loading
				//----------------------------------------------------
				var loadingWeeklyCanvas, loadingWeeklyContext, x, y; 
				//for Line Chart 01
				loadingWeeklyCanvas = document.getElementById('generalWeeklyLineChart');
				loadingWeeklyContext = loadingWeeklyCanvas.getContext('2d');
				x = loadingWeeklyCanvas.width / 2;
				y = loadingWeeklyCanvas.height / 2;
				loadingWeeklyContext.font = '20pt Calibri';
				loadingWeeklyContext.textAlign = 'center';
				loadingWeeklyContext.fillStyle = '#99CC00';
				loadingWeeklyContext.fillText("Retrieving Data ...", x, y);
				//for Line Chart 02
				loadingWeeklyCanvas = document.getElementById('generalWeeklyLineChart_nofill');
				loadingWeeklyContext = loadingWeeklyCanvas.getContext('2d');
				x = loadingWeeklyCanvas.width / 2;
				y = loadingWeeklyCanvas.height / 2;
				loadingWeeklyContext.font = '20pt Calibri';
				loadingWeeklyContext.textAlign = 'center';
				loadingWeeklyContext.fillStyle = '#99CC00';
				loadingWeeklyContext.fillText("Retrieving Data ...", x, y);
				//for Bar Chart
				loadingWeeklyCanvas = document.getElementById('generalWeeklyBarChart');
				loadingWeeklyContext = loadingWeeklyCanvas.getContext('2d');
				x = loadingWeeklyCanvas.width / 2;
				y = loadingWeeklyCanvas.height / 2;
				loadingWeeklyContext.font = '20pt Calibri';
				loadingWeeklyContext.textAlign = 'center';
				loadingWeeklyContext.fillStyle = '#99CC00';
				loadingWeeklyContext.fillText("Retrieving Data ...", x, y);
				//----------------------------------------------------
				//client name
				//----------------------------------------------------
				var clientCode = client_code_string.split("=")[1];
				var client = getClientName(clientCode);
				var href_link = "./view_dashboard.php" + "?" + "client_code=" + clientCode;
				var clientNameNavBarHTML = 
						'<span style="padding-top:0px; padding-bottome:0px;">' +
						'<a href="' + href_link + '">' + client + '</a>' +
						'</span>';
				$('#client_name').html(clientNameNavBarHTML);
				//----------------------------------------------------
				function getClientName(client_code)
				{
					if (client_code === 'BNZ')
						return 'BNZ (Bank of New Zealand)';
					else if (client_code === 'MNRG')
						return 'Meridian Energy';
					else if (client_code === 'STHX')
						return 'Southern Cross';
					else if (client_code === 'MSD')
						return 'MSD (Ministry of Social Development)';
					else if (client_code === 'TCL')
						return 'TelstraClear';
					else if (client_code === 'FONT')
						return 'Fonterra';
					else if (client_code === 'ALL')
						return 'All Other Clients';
					else
						return 'Error: invalid client code'
				}
				//----------------------------------------------------
				
				//----------------------------------------------------
				//streaming reports data in via HTTP GET API call
				//----------------------------------------------------
				function getFormattedDate(type, dateVal)
				{
					if (type === 1)
					{
						var arr = dateVal.split('-');
						var date = arr[2] + '/' + arr[1] + '/' + arr[0];
						return date;
					}
					else
					{
						return "invalid date type";
					}
				}

                $.get(API_GET_ALL_REPORTS_URL, function(callback_data, status)
                {
                    //alert("Callback Data: " + callback_data + "\r\n" + "Status: " + status);
                    var xmlData = $.parseXML(callback_data);
                    var xml = $(xmlData);
                    //var client_code = xml.find("client_code").text();
                    //var client_code = xml.find("client_code");
                    //var client_code = $(xmlData).find('client_code').first().text();
                    var client_code = xml.find('data').find('reports').find('client_code').text();
                    
                    var dataTableBody = "<tbody>";
                    var startTableTag = "<table id='all_reports_table' class='table table-striped'>";
                    var tableHeadTag = "<thead>" +
                    				   "<tr>" + 
                    				   "<th><h4>Report</h4></th>" +
                    				   "<th><h4 class=" + "\"" + "pull-left" + "\"" + ">" + "Range" + "</h4></th>" + 
                    				   "<th><h4 class=" + "\"" + "pull-right" + "\"" + ">" + "View" + "</h4></th>" +
                    				   "</tr>" + 
                    				   "</thead>";
                    var endTableTag = "</tbody>" + "</table>";
                        
                    $(xml).find('report').each(function() {
                    	var report = $(this);
                        var report_file = report.find('report_file').text();
                        var report_range = report.find('report_range').text();
                        var view_report_button = '<a href=' + '\"' + 'view_reports.php?report_name=' + report_file + '\"' + ' class="btn btn-info btn-sm pull-right" target="_blank" data-toggle="tooltip" title="open to view"><span class="glyphicon glyphicon-share-alt"></span></a>';
                        //var view_report_button = '<a href=' + '\"' + report_file + '\"' + ' class="btn btn-info btn-sm pull-right" target="_blank" data-toggle="tooltip" title="View Report"><i class="fa fa-file-text-o" style="font-size:18px;"></i></a>';
                        dataTableBody += '<tr><td><h5>' + report_file + '</h5></td>' + '<td><h5>' + report_range + '</h5></td>' + '<td><h5>' + view_report_button + '</h5></td></tr>';
                    });
                    var htmlTableStr = startTableTag + tableHeadTag + dataTableBody + endTableTag;
                    $('#all_reports').html(htmlTableStr);

                    //----------------------------------------------------
                    //streaming monthly reports data in via HTTP GET API call
                    //----------------------------------------------------
                    $.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
	                {
	                	//alert("Callback Data: " + callback_data + "\r\n" + "Status: " + status);
	                	var xmlData = $.parseXML(callback_data);
                    	var xml = $(xmlData);
                    	var labelsArr = [];
                    	var reportFilesArr = [];
						//var docTypesDataArr = [];
                    	var totalPeriodCountValsArr = [];
                    	var totalDocsCountValsArr = [];
                    	var totalPeriodSizeValArr = [];
                    	var totalSizeValArr = [];
                    	var totalPeriodCountAvg, totalDocsCountAvg, totalPeriodSizeAvg, totalSizeAvg;
						var indexCount = 0;
						jsonStart = { "ReportKey": "Range of Report", "ReportValue": "Choose Report" };
						reportFilesArr.push(jsonStart);
                    	$(xml).find('report').each(function() {
	                    	var report = $(this);
	                    	var reportSingleFileJSON = {};
	                    	//get labels in chart
	                        var report_from_date = report.find('from_date').text();
	                        var report_to_date = report.find('to_date').text();
	                        var labelVal = "[ " + getFormattedDate(1, report_from_date) + " - " + getFormattedDate(1, report_to_date) + " ]";
	                        labelsArr.push(labelVal);
	                        var reportFileName = report.find('report_file').text();
	                        reportSingleFileJSON.ReportKey = reportFileName;
	                        reportSingleFileJSON.ReportValue = labelVal;
	                        reportFilesArr.push(reportSingleFileJSON);
	                        //get items in chart
	                        var totalPeriodCount = report.find('total_01').text();
	                        totalPeriodCountValsArr.push(totalPeriodCount);
	                        var totalDocsCount = report.find('total_02').text();
	                        totalDocsCountValsArr.push(totalDocsCount);
	                        var totalPeriodSize = report.find('total_03').text();
	                        totalPeriodSizeValArr.push(totalPeriodSize);
	                        var totalSize = report.find('total_04').text();
	                        totalSizeValArr.push(totalSize);
	                    });
						
						//----------------------------------------------------
						//get average
						//----------------------------------------------------
						var sum = 0;
						var totalPeriodCountAvgArr = [];
						var totalDocsCountAvgArr = [];
						var totalPeriodSizeAvgArr = [];
						var totalSizeAvgArr = [];

						for(var i=0; i<totalPeriodCountValsArr.length; i++ ){
						    sum += parseInt(totalPeriodCountValsArr[i], 10);
						}
						var totalPeriodCountAvg = sum/totalPeriodCountValsArr.length;
						for (var x=0; x<labelsArr.length; x++)
						{
							totalPeriodCountAvgArr.push(totalPeriodCountAvg);
						}

						sum = 0;
						for(var i=0; i<totalDocsCountValsArr.length; i++ ){
						    sum += parseInt(totalDocsCountValsArr[i], 10);
						}
						var totalDocsCountAvg = sum/totalDocsCountValsArr.length;
						for (var x=0; x<labelsArr.length; x++)
						{
							totalDocsCountAvgArr.push(totalDocsCountAvg);
						}
						//----------------------------------------------------

	                	//----------------------------------------------------
						//Monthly Reports Charts
						//----------------------------------------------------
						//Chart Variables
						var ctx;
						var chart;
						//Monthly Reports Line Chart
						lineMixChartMonthlyData = {
							labels: labelsArr,
							datasets: [{
							  fill: false,
							  label: 'Period Count',
							  data: totalPeriodCountValsArr,
							  borderColor: "rgba(0,255,191,0.8)",
							  backgroundColor: "rgba(153,255,51,0.4)",
							  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
							  pointHoverBorderColor: "rgba(220,220,220,1.0)"
							}, {
							  fill: true,
							  label: 'Period Count Average',
							  data: totalPeriodCountAvgArr,
							  borderColor: "rgba(0,191,255,1.0)",
							  type: 'line'
							}]
						};
						
						ctx = document.getElementById('monthlyLineChart').getContext('2d');
						chart = new Chart(ctx, 
						{
						  type: 'line',
						  data: lineMixChartMonthlyData
						});
						
						//Total Reports Line Chart
						lineMixChartTotalData = {
							labels: labelsArr,
							datasets: [{
							  fill: false,
							  label: 'Total Docs',
							  data: totalDocsCountValsArr,
							  borderColor: "rgba(255,153,0,0.8)",
							  backgroundColor: "rgba(255,153,0,0.4)",
							  pointHoverBackgroundColor: "rgba(0,128,255,1)",
							  pointHoverBorderColor: "rgba(220,220,220,1)"
							},{
							  fill: true,
							  label: 'Total Docs Average',
							  data: totalDocsCountAvgArr,
							  borderColor: "rgba(191,0,255,1.0)",
							  type: 'line'
							}]
						};
						
						ctx = document.getElementById('totalLineChart').getContext('2d');
						chart = new Chart(ctx, 
						{
						  type: 'line',
						  data: lineMixChartTotalData
						});
						
						//Monthly Reports Bar Chart
						barMixedChartMonthlyData = {
							labels: labelsArr,
							datasets: [{
							  label: 'Period Count',
							  data: totalPeriodCountValsArr,
							  backgroundColor: "rgba(153,255,51,1.0)"
							}, {
							  fill: true,
							  label: 'Period Count Average',
							  data: totalPeriodCountAvgArr,
							  borderColor: "rgba(0,191,255,1.0)",
							  type: 'line'
							}]
						};
						
						ctx = document.getElementById('monthlyBarChart').getContext('2d');
						chart = new Chart(ctx, 
						{
						  type: 'bar',
						  data: barMixedChartMonthlyData
						});
						
						//Total Reports Bar Chart
						barMixedChartTotalData = {
							labels: labelsArr,
							datasets: [{
							  label: 'Total Docs',
							  data: totalDocsCountValsArr,
							  backgroundColor: "rgba(255,153,0,1.0)"
							},{
							  fill: true,
							  label: 'Total Docs Average',
							  data: totalDocsCountAvgArr,
							  borderColor: "rgba(191,0,255,1.0)",
							  type: 'line'
							}]
						};
						
						ctx = document.getElementById('totalBarChart').getContext('2d');
						chart = new Chart(ctx, 
						{
						  type: 'bar',
						  data: barMixedChartTotalData
						});
						
						//---------------------------------------------------------
						//adding selected options boxes
						//---------------------------------------------------------
						var populateSelect = function(strControlID, data, idKey, nameKey){
							var selectDocTypeControl = document.getElementById(strControlID);
							for(var i = 0; i < data.length; i++){
								var option = document.createElement('option');
								option.value = data[i][idKey];
								option.text = data[i][nameKey];
								selectDocTypeControl.appendChild(option);
							}
						};

						$('#selectSingleReport')
							.find('option')
							.remove()
							.end();
							
						$('#google_selectSingleReport')
							.find('option')
							.remove()
							.end();
							
						//populateSelect('selectSingleReport', reportFilesArr, 'ReportKey', 'ReportValue');
						populateSelect('google_selectSingleReport', reportFilesArr, 'ReportKey', 'ReportValue');
						
						//-------------------------------------------------
						//call function to get general  weekly report
						//-------------------------------------------------
						getGeneralWeeklyReport();
						//-------------------------------------------------
						/*
						$('#selectSingleReport').on('change', function(){
							var optionSelected = $("option:selected", this);
							var valueSelected = this.value;
						    alert(valueSelected);
						});

						$('#selectSingleReport').change(function (){
							var optionSelected = $(this).find("option:selected");
							var valueSelected  = optionSelected.val();
							var textSelected   = optionSelected.text();
							alert(valueSelected + "\r\n" + textSelected);
						});
						
						$('#selectSingleReport').find('option').click(function (){
							var optionSelected = $(this);
							var valueSelected  = optionSelected.val();
							var textSelected   = optionSelected.text();
							alert(valueSelected + "\r\n" + textSelected);
						});
						
						$('#selectSingleReport')
							.change(function () {
								var str = "";
								$("#selectSingleReport option:selected").each(function() {
									str += $(this).text() + " ";
								});
							alert(str);
						}).change();
						*/
						$('#singleReportChart').on('change', 'select', function() {
							updateCanvas('docTypePieChart');
							updateCanvas('docTypeDoughnutChart');
							resetCanvas('docTypePieChart', 'singleReportChartForm');
							resetCanvas('docTypeDoughnutChart', 'docTypeChartForm');

							var optionSelected = $(this).find("option:selected");
							var valueSelected = optionSelected.val();
							if (valueSelected === "Range of Report")
							{
								$('#selectDocType')
									.find('option')
									.remove()
									.end()
									.append('<option value="doc type">View Doc Type</option>');
							}
							var textSelected = optionSelected.text();
							//alert('KEY => ' + valueSelected + "\r\n" + 'TEXT => ' + $(this).find("option:selected").text());
							updateSelectDocTypes(valueSelected);
						});
						
						$('#docTypeChart').on('change', 'select', function() {
							updateCanvas('docTypePieChart');
							updateCanvas('docTypeDoughnutChart');
							resetCanvas('docTypePieChart', 'singleReportChartForm');
							resetCanvas('docTypeDoughnutChart', 'docTypeChartForm');

							var optionSelected = $(this).find("option:selected");
							var valueSelected = optionSelected.val();
							var textSelected  = optionSelected.text();
							//alert('KEY => ' + valueSelected + "\r\n" + 'TEXT => ' + $(this).find("option:selected").text());
							var checkExist = setInterval(function() {
								if ($('#docTypePieChart').length) {
									if ($('#docTypeDoughnutChart').length) {
										updateDocTypeSumChart(textSelected);
										clearInterval(checkExist);
								   }
							   }
							}, 100); 
						});
						
						//-----------------------------------------------
						//Google chart - 3D Pie Chart
						//-----------------------------------------------
						$('#google_singleReportChart').on('change', 'select', function() {
							var optionSelected = $(this).find("option:selected");
							var valueSelected = optionSelected.val();
							if (valueSelected === "Range of Report")
							{
								$('#google_selectDocType')
									.find('option')
									.remove()
									.end()
									.append('<option value="doc type">View Doc Type</option>');
							}
							var textSelected   = optionSelected.text();
							//alert('KEY => ' + valueSelected + "\r\n" + 'TEXT => ' + $(this).find("option:selected").text());
							$('#google_docTypePieChart_3d').empty();
							$('#google_docTypeDoughnutChart').empty();
							updateGoogleSelectDocTypes(valueSelected);
						});
						
						//-----------------------------------------------
						//Google chart - Donut Chart
						//-----------------------------------------------
						$('#google_docTypeChart').on('change', 'select', function() {
							var optionSelected = $(this).find("option:selected");
							var valueSelected = optionSelected.val();
							var textSelected  = optionSelected.text();
							//alert('KEY => ' + valueSelected + "\r\n" + 'TEXT => ' + $(this).find("option:selected").text());
							updateDocTypeSumGoogleChart(textSelected);
						});
						
						function updateDocTypeSumGoogleChart(selectedDocTypeText)
						{
							var reportRangeLabelsArr = [];
							var docTypeTotalSumArr = [];
							var docTypePeriodSumArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
							{
								var xmlData = $.parseXML(callback_data);
								var xml = $(xmlData);
								$(xml).find('report').each(function() {
									var report = $(this);
									var report_from_date = report.find('from_date').text();
									var report_to_date = report.find('to_date').text();
									var labelVal = getFormattedDate(1, report_from_date) + " - " + getFormattedDate(1, report_to_date);
									report.find('doc_group').find('doc').each(function() {
										var doc = $(this);
										var docTypeValue = doc.find('doc_type').text();
										if (docTypeValue === selectedDocTypeText)
										{
											var total_up_to_the_month = doc.find('doc_type_total_02').text();
											var period_sum_for_the_month = doc.find('doc_type_total_01').text();
											
											//var singleItemTotalSumArr = [];
											//singleItemTotalSumArr.push(labelVal);
											//singleItemTotalSumArr.push(total_up_to_the_month);
											var singleItemTotalSumArr = new Array(labelVal, total_up_to_the_month);
											docTypeTotalSumArr.push(singleItemTotalSumArr);
											google.charts.load("current", {packages:["corechart"]});
											google.charts.setOnLoadCallback(draw3DPieChart(docTypeTotalSumArr));

											//var singleItemPeriodSumArr = [];
											//singleItemPeriodSumArr.push(labelVal);
											//singleItemPeriodSumArr.push(period_sum_for_the_month);
											var singleItemPeriodSumArr = new Array(labelVal, period_sum_for_the_month);
											docTypePeriodSumArr.push(singleItemPeriodSumArr);
											google.charts.load("current", {packages:["corechart"]});
											google.charts.setOnLoadCallback(drawDonutChart(docTypePeriodSumArr));
								
										}
									});
								});
								
								//------------------------------------------
								//Google charts drawing
								//------------------------------------------
								//3D Pie Chart
								//------------------------------------------
								//google.charts.load("current", {packages:["corechart"]});
								//google.charts.setOnLoadCallback(draw3DPieChart(docTypeTotalSumArr));
								function draw3DPieChart(dataArr) {
									/*
									var matrix = [
										[dataArr[0][0], parseInt(dataArr[0][1])],
										[dataArr[1][0], parseInt(dataArr[1][1])],
										[dataArr[2][0], parseInt(dataArr[2][1])]
									]
									*/
									var dataMatrix = new Array();
									for (var x=0; x<dataArr.length; x++)
									{
										var singleItem = [dataArr[x][0], parseInt(dataArr[x][1])];
										dataMatrix.push(singleItem);
									}
									var data = google.visualization.arrayToDataTable(dataMatrix, true);
									var options = {
									  title: 'Total Report',
									  is3D: true,
									};

									var chart = new google.visualization.PieChart(document.getElementById('google_docTypePieChart_3d'));
									chart.draw(data, options);
								}
								//------------------------------------------

								//------------------------------------------
								//Donut Chart
								//------------------------------------------
								//google.charts.load("current", {packages:["corechart"]});
								//google.charts.setOnLoadCallback(drawDonutChart(docTypePeriodSumArr));
								function drawDonutChart(dataArr) {
									var dataMatrix = new Array();
									for (var x=0; x<dataArr.length; x++)
									{
										var singleItem = [dataArr[x][0], parseInt(dataArr[x][1])];
										dataMatrix.push(singleItem);
									}
									var data = google.visualization.arrayToDataTable(dataMatrix, true);
									var options = {
									  title: 'Period Report',
									  pieHole: 0.4,
									};

									var chart = new google.visualization.PieChart(document.getElementById('google_docTypeDoughnutChart'));
									chart.draw(data, options);
								}
								//------------------------------------------
							});
						}
							
						/*
						$('#singleSumReportChart').on('change', 'select', function() {
							var optionSelected = $(this).find("option:selected");
							var valueSelected = optionSelected.val();
							var textSelected = optionSelected.text();
							updateCanvas('weeklyDoughnutChart');
							alert('KEY => ' + valueSelected + "\r\n" + 'TEXT => ' + $(this).find("option:selected").text());
						});
						
						
						$('#singleLogSumReportChart').on('change', 'select', function() {
							var optionSelected = $(this).find("option:selected");
							var valueSelected = optionSelected.val();
							var textSelected = optionSelected.text();
							updateCanvas('weeklyBarChart');
							alert('KEY => ' + valueSelected + "\r\n" + 'TEXT => ' + $(this).find("option:selected").text());
						});
						*/
						function updateCanvas(canvasID)
						{
							var loadingCanvas = document.getElementById(canvasID);
							var loadingContext = loadingCanvas.getContext('2d');
							var x = loadingCanvas.width / 2;
							var y = loadingCanvas.height / 2;
							loadingContext.font = '20pt Calibri';
							loadingContext.textAlign = 'center';
							loadingContext.fillStyle = '#99CC00';
							loadingContext.clearRect(0, 0, loadingCanvas.width, loadingCanvas.height);
							loadingContext.fillText("Retrieving Data ...", x, y);
						}
						
						function resetCanvas(canvasID, appendAfterID)
						{
							//var canvas = document.getElementById(canvasID);
							//var ctx = canvas.getContext('2d');
							//ctx.clearRect(0, 0, canvas.width, canvas.height);

							$('#' + canvasID).remove();
							$('<canvas>').attr({
								id: canvasID
							}).css({
								width: 500 + 'px',
								height: 400 + 'px'
							}).insertAfter('#' + appendAfterID);
						}
						//----------------------------------------------------------
						//update DocType values into Select control
						//----------------------------------------------------------
						function updateSelectDocTypes(reportFile)
						{
							docTypesJSONArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
			                {
			                	var xmlData = $.parseXML(callback_data);
		                    	var xml = $(xmlData);
		                    	$(xml).find('report').each(function() {
			                    	var report = $(this);
			                    	if (report.find('report_file').text() === reportFile)
			                    	{
			                    		var countIndex = 0;
			                    		report.find('doc_group').find('doc').each(function() {
	                    					var doc = $(this);
	                    					var docTypeValue = doc.find('doc_type').text();
											var docTypeJSON = {};
											docTypeJSON.DocTypeIndex = countIndex;
											docTypeJSON.DocTypeName = docTypeValue;
											//alert(JSON.stringify(docTypeJSON));
											docTypesJSONArr.push(docTypeJSON);
											countIndex += 1;
			                    		});
										
			                    		$('#selectDocType')
											.find('option')
											.remove()
											.end();

										//populateSelect('selectDocType', docTypesJSONArr, 'DocTypeIndex', 'DocTypeName');
			                    	}
									else
									{
									}
			                    });
						    });
						}
						
						//----------------------------------------------------------
						//update DocType values into Select control
						//----------------------------------------------------------
						function updateGoogleSelectDocTypes(reportFile)
						{
							docTypesJSONArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
			                {
			                	var xmlData = $.parseXML(callback_data);
		                    	var xml = $(xmlData);
		                    	$(xml).find('report').each(function() {
			                    	var report = $(this);
			                    	if (report.find('report_file').text() === reportFile)
			                    	{
			                    		var countIndex = 0;
			                    		report.find('doc_group').find('doc').each(function() {
	                    					var doc = $(this);
	                    					var docTypeValue = doc.find('doc_type').text();
											var docTypeJSON = {};
											docTypeJSON.DocTypeIndex = countIndex;
											docTypeJSON.DocTypeName = docTypeValue;
											//alert(JSON.stringify(docTypeJSON));
											docTypesJSONArr.push(docTypeJSON);
											countIndex += 1;
										});
										
										$('#google_selectDocType')
											.find('option')
											.remove()
											.end();

										populateSelect('google_selectDocType', docTypesJSONArr, 'DocTypeIndex', 'DocTypeName');
			                    	}
									else
									{
									}
			                    });
						    });
						}

						//----------------------------------------------------------
						//create Pie Chart
						//----------------------------------------------------------
						function updateSingleReportChart(single_report)
						{
							docTypesArr = [];
							docTypeSumArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
			                {
			                	var xmlData = $.parseXML(callback_data);
		                    	var xml = $(xmlData);
		                    	$(xml).find('report').each(function() {
			                    	var report = $(this);
			                    	if (report.find('report_file').text() === single_report)
			                    	{
			                    		report.find('doc_group').find('doc').each(function() {
	                    					var doc = $(this);
	                    					var docTypeValue = doc.find('doc_type').text();
	                    					docTypesArr.push(docTypeValue);
	                    					var docTypeSumValue = doc.find('doc_type_total_01').text();
	                    					docTypeSumArr.push(docTypeSumValue);
			                    		});
			                    	}
			                    });
			                    //draw and update chart
			                    var singleReportPieChartData = {
									labels: docTypesArr,
									datasets: [{
										backgroundColor: getRandomColorsArr(100),
										data: docTypeSumArr
									  	//data: [12, 19, 3, 17, 6, 3, 7, 19, 29, 15, 11, 22]
									}]
								};

								ctx = document.getElementById('singleReportPieChart').getContext('2d');
								chart = new Chart(ctx, 
								{
								  type: 'pie',
								  data: singleReportPieChartData
								});
			                });
						}

						function getDocTypeTotals(selectedDocTypeText)
						{
							docTypeTotalsArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
			                {
			                	var xmlData = $.parseXML(callback_data);
		                    	var xml = $(xmlData);
		                    	$(xml).find('report').each(function() {
			                    	var report = $(this);
									report.find('doc_group').find('doc').each(function() {
										var doc = $(this);
										var docTypeValue = doc.find('doc_type').text();
										if (docTypeValue === selectedDocTypeText)
										{
											docTypeTotalsArr.push(docTypeValue);
										}
									});
			                    });
						    });
							return docTypeTotalsArr;
						}
						
						function updateDocTypeSumChart(selectedDocTypeText)
						{
							var ctx;
							var canvas;

							//if (chart != null)
							//	chart.destroy();
							
							var reportRangeLabelsArr = [];
							var docTypeTotalSumArr = [];
							var docTypePeriodSumArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
			                {
			                	var xmlData = $.parseXML(callback_data);
		                    	var xml = $(xmlData);
		                    	$(xml).find('report').each(function() {
			                    	var report = $(this);
									//get labels in chart
									var report_from_date = report.find('from_date').text();
									var report_to_date = report.find('to_date').text();
									var labelVal = "[ " + getFormattedDate(1, report_from_date) + " - " + getFormattedDate(1, report_to_date) + " ]";
									reportRangeLabelsArr.push(labelVal);
									report.find('doc_group').find('doc').each(function() {
										var doc = $(this);
										var docTypeValue = doc.find('doc_type').text();
										if (docTypeValue === selectedDocTypeText)
										{
											var total_up_to_the_month = doc.find('doc_type_total_02').text();
											docTypeTotalSumArr.push(total_up_to_the_month);
											var period_sum_for_the_month = doc.find('doc_type_total_01').text();
											docTypePeriodSumArr.push(period_sum_for_the_month);
										}
									});
			                    });
								
								//-------------------------------------------------------
								//draw and update pie chart for total up to the month
								//-------------------------------------------------------
								var singleDocTypePieChartData = {
									labels: reportRangeLabelsArr,
									datasets: [{
										backgroundColor: getRandomColorsArr(12),
										data: docTypeTotalSumArr
									}]
								};
		
								canvas = document.getElementById('docTypePieChart');
								ctx = canvas.getContext('2d');
								ctx.clearRect(0, 0, canvas.width, canvas.height);
								var pieChart = new Chart(ctx, 
								{
								  type: 'pie',
								  data: singleDocTypePieChartData
								});
								pieChart.update();
								pieChart.render();
								
								//-------------------------------------------------------
								//draw and update doughnut chart for period sum for the month
								//-------------------------------------------------------
			                    var singleDocTypeDoughnutChartData = {
									labels: reportRangeLabelsArr,
									datasets: [{
										backgroundColor: getRandomColorsArr(12),
										data: docTypePeriodSumArr
									}]
								};
								
								canvas = document.getElementById('docTypeDoughnutChart');
								ctx = canvas.getContext('2d');
								ctx.clearRect(0, 0, canvas.width, canvas.height);
								var doughnutChart = new Chart(ctx, 
								{
								  type: 'doughnut',
								  data: singleDocTypeDoughnutChartData
								});
								doughnutChart.update();
								doughnutChart.render();
						    });
						}
						
						function getDocTypesFrom(type, single_report)
						{
							docTypesArr = [];
							$.get(API_GET_MONTHLY_REPORTS_URL, function(callback_data, status)
			                {
			                	var xmlData = $.parseXML(callback_data);
		                    	var xml = $(xmlData);
		                    	$(xml).find('report').each(function() {
			                    	var report = $(this);
			                    	if (report.find('report_file').text() === single_report)
			                    	{
			                    		var countIndex = 0;
			                    		report.find('doc_group').find('doc').each(function() {
	                    					var doc = $(this);
	                    					var docTypeValue = doc.find('doc_type').text();
	                    					if (type === 0)
											{
												docTypesArr.push(docTypeValue);
											}
											else if (type === 1)
											{
												var docTypeJSON = {};
												docTypeJSON.DocTypeIndex = countIndex;
												docTypeJSON.DocTypeName = docTypeValue;
												docTypesArr.push(docTypeJSON);
											}
											else
											{}
											countIndex += 1;
			                    		});
			                    	}
			                    });
						    });
							return docTypesArr;
						}

						function getRandomColor() {
					        var letters = '0123456789ABCDEF';
					        var color = '#';
					        for (var i=0; i<6; i++) {
					            color += letters[Math.floor(Math.random() * 16)];
					        }
					        return color;
					    }

					    function getRandomColorsArr(numOfColors)
					    {
					    	colorsArr = [];
					    	for (var x=0; x<numOfColors; x++)
						    {
						    	colorsArr.push(getRandomColor());
							}
					    	return colorsArr;
					    }
						
						function getGeneralWeeklyReport()
						{
							$.get(API_GET_WEEKLY_REPORTS_URL, function(callback_data, status)
			                {
								//alert("Callback Data: " + callback_data + "\r\n" + "Status: " + status);
								var xmlData = $.parseXML(callback_data);
								var xml = $(xmlData);
								var logFilesArr = [];
								var updateDocumentPropertiesSumArr = [];
								var doSearchSumArr = [];
								var getResultCountSumArr = [];
								var getSearchProfilesSumArr = [];
								var getDocumentSumArr = [];
								var getSingleDocumentSumArr = [];
								
								var reportSumArrJSON = [];
								var logFilesArrJSON = [];
								var theFirstAuditLog = $(xml).find('audit_log').first();
								var theFirstAuditLogFullPath = theFirstAuditLog.find('log_file').text();
								var theFirstAuditLogName = theFirstAuditLogFullPath.split('/').pop();
								
								var reportSumArr = [];
								for (var i=1, len=theFirstAuditLog.children().length; i<len; i++)
								{
									//alert(theFirstAuditLog.children()[i].nodeName);
									var TYPE_OF_SUM_VAL = theFirstAuditLog.children()[i].nodeName;
									reportSumArr.push(TYPE_OF_SUM_VAL);
								}

								$(xml).find('audit_log').each(function() {
			                    	var audit_log = $(this);
									var log_full_path = audit_log.find('log_file').text();
									var log_file_name = log_full_path.split('/').pop();
									logFilesArr.push(log_file_name);
									updateDocumentPropertiesSumArr.push(audit_log.find('update_doc_properties_sum').text());
									doSearchSumArr.push(audit_log.find('do_search_sum').text());
									getResultCountSumArr.push(audit_log.find('result_count_sum').text());
									getSearchProfilesSumArr.push(audit_log.find('search_profiles_sum').text());
									getDocumentSumArr.push(audit_log.find('document_sum').text());
									getSingleDocumentSumArr.push(audit_log.find('single_document_sum').text());
			                    });

								var updateDocumentPropertiesAverageArr = [];
								var doSearchAverageArr = [];
								var getResultCountAverageArr = [];
								var getSearchProfilesAverageArr = [];
								var getDocumentAverageArr = [];
								var getSingleDocumentAverageArr = [];
	
								var sum = 0;
								for(var i=0; i<updateDocumentPropertiesSumArr.length; i++ ){
									sum += parseInt(updateDocumentPropertiesSumArr[i], 10);
								}
								var updateDocumentPropertiesAvg = sum/updateDocumentPropertiesSumArr.length;
								for (var x=0; x<updateDocumentPropertiesSumArr.length; x++)
								{
									updateDocumentPropertiesAverageArr.push(updateDocumentPropertiesAvg);
								}
						
								sum = 0;
								for(var i=0; i<doSearchSumArr.length; i++ ){
									sum += parseInt(doSearchSumArr[i], 10);
								}
								var doSearchAvg = sum/doSearchSumArr.length;
								for (var x=0; x<doSearchSumArr.length; x++)
								{
									doSearchAverageArr.push(doSearchAvg);
								}
								
								sum = 0;
								for(var i=0; i<getResultCountSumArr.length; i++ ){
									sum += parseInt(getResultCountSumArr[i], 10);
								}
								var getResultCountAvg = sum/getResultCountSumArr.length;
								for (var x=0; x<getResultCountSumArr.length; x++)
								{
									getResultCountAverageArr.push(doSearchAvg);
								}
								
								sum = 0;
								for(var i=0; i<getSearchProfilesSumArr.length; i++ ){
									sum += parseInt(getSearchProfilesSumArr[i], 10);
								}
								var getSearchProfilesAvg = sum/getSearchProfilesSumArr.length;
								for (var x=0; x<getSearchProfilesSumArr.length; x++)
								{
									getSearchProfilesAverageArr.push(getSearchProfilesAvg);
								}
								
								sum = 0;
								for(var i=0; i<getDocumentSumArr.length; i++ ){
									sum += parseInt(getDocumentSumArr[i], 10);
								}
								var getDocumentAvg = sum/getDocumentSumArr.length;
								for (var x=0; x<getDocumentSumArr.length; x++)
								{
									getDocumentAverageArr.push(getDocumentAvg);
								}
								
								sum = 0;
								for(var i=0; i<getSingleDocumentSumArr.length; i++ ){
									sum += parseInt(getSingleDocumentSumArr[i], 10);
								}
								var getSingleDocumentAvg = sum/getSingleDocumentSumArr.length;
								for (var x=0; x<getSingleDocumentSumArr.length; x++)
								{
									getSingleDocumentAverageArr.push(getSingleDocumentAvg);
								}
								//----------------------------------------------------
						
								//----------------------------------------------------
								//Chart Variables
								//----------------------------------------------------
								var weeklyCanvas;
								var weeklyChartCtx;
								var weeklyChart;
								//----------------------------------------------------
								//General Line Filled Charts
								//----------------------------------------------------
								var weeklyLineChartData = {
									labels: logFilesArr,
									datasets: [{
									  fill: true,
									  label: 'Update Docs',
									  lineTension: 0,
									  data: updateDocumentPropertiesSumArr,
									  borderColor: "rgba(153,255,51,1.0)",
									  backgroundColor: "rgba(153,255,51,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Search Docs',
									  lineTension: 0,
									  data: doSearchSumArr,
									  borderColor: "rgba(255,153,0,1.0)",
									  backgroundColor: "rgba(255,153,0,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Result Count',
									  lineTension: 0,
									  data: getResultCountSumArr,
									  borderColor: "rgba(90,46,146,1.0)",
									  backgroundColor: "rgba(142,75,219,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Search Profiles',
									  lineTension: 0,
									  data: getSearchProfilesSumArr,
									  borderColor: "rgba(62,170,185,1.0)",
									  backgroundColor: "rgba(122,193,170,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Get Document',
									  lineTension: 0,
									  data: getDocumentSumArr,
									  borderColor: "rgba(72,107,219,1.0)",
									  backgroundColor: "rgba(59,146,255,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Get Single Document',
									  lineTension: 0,
									  data: getSingleDocumentSumArr,
									  borderColor: "rgba(150,178,50,1.0)",
									  backgroundColor: "rgba(209,255,33,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}]
								};
								
								//weeklyChartCtx = document.getElementById('generalWeeklyLineChart').getContext('2d');
								weeklyCanvas = document.getElementById('generalWeeklyLineChart');
								weeklyChartCtx = weeklyCanvas.getContext('2d');
								weeklyChart = new Chart(weeklyChartCtx, 
								{
								  type: 'line',
								  data: weeklyLineChartData
								});
								
								//----------------------------------------------------
								//General Line No Filled Charts
								//----------------------------------------------------
								var weeklyNoFillLineChartData = {
									labels: logFilesArr,
									datasets: [{
									  fill: false,
									  label: 'Update Docs',
									  //lineTension: 0,
									  data: updateDocumentPropertiesSumArr,
									  borderColor: "rgba(153,255,51,1.0)",
									  //backgroundColor: "rgba(153,255,51,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: false,
									  label: 'Search Docs',
									  //lineTension: 0,
									  data: doSearchSumArr,
									  borderColor: "rgba(255,153,0,1.0)",
									  //backgroundColor: "rgba(255,153,0,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: false,
									  label: 'Result Count',
									  //lineTension: 0,
									  data: getResultCountSumArr,
									  borderColor: "rgba(90,46,146,1.0)",
									  //backgroundColor: "rgba(142,75,219,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: false,
									  label: 'Search Profiles',
									  //lineTension: 0,
									  data: getSearchProfilesSumArr,
									  borderColor: "rgba(62,170,185,1.0)",
									  //backgroundColor: "rgba(122,193,170,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: false,
									  label: 'Get Document',
									  //lineTension: 0,
									  data: getDocumentSumArr,
									  borderColor: "rgba(72,107,219,1.0)",
									  //backgroundColor: "rgba(59,146,255,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: false,
									  label: 'Get Single Document',
									  //lineTension: 0,
									  data: getSingleDocumentSumArr,
									  borderColor: "rgba(150,178,50,1.0)",
									  //backgroundColor: "rgba(209,255,33,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Update Docs Average',
									  data: updateDocumentPropertiesAverageArr,
									  borderColor: "rgba(211,215,255,1.0)",
									  backgroundColor: "rgba(211,255,255,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Search Docs Average',
									  data: doSearchAverageArr,
									  borderColor: "rgba(133,38,226,1.0)",
									  backgroundColor: "rgba(98,118,178,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Result Count Average',
									  data: getResultCountAverageArr,
									  borderColor: "rgba(211,150,42,1.0)",
									  backgroundColor: "rgba(211,198,42,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Search Profiles Average',
									  data: getSearchProfilesAverageArr,
									  borderColor: "rgba(211,49,224,1.0)",
									  backgroundColor: "rgba(211,150,224,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Get Document Average',
									  data: getDocumentAverageArr,
									  borderColor: "rgba(36,68,96,1.0)",
									  backgroundColor: "rgba(116,57,31,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Get Single Document Average',
									  data: getSingleDocumentAverageArr,
									  borderColor: "rgba(57,29,255,1.0)",
									  backgroundColor: "rgba(94,172,234,0.4)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}]
								};
								
								//weeklyChartCtx = document.getElementById('generalWeeklyLineChart').getContext('2d');
								weeklyCanvas = document.getElementById('generalWeeklyLineChart_nofill');
								weeklyChartCtx = weeklyCanvas.getContext('2d');
								weeklyChart = new Chart(weeklyChartCtx, 
								{
								  type: 'line',
								  data: weeklyNoFillLineChartData
								});
								
								//----------------------------------------------------
								//General Bar Charts
								//----------------------------------------------------
								var weeklyBarChartData = {
									labels: logFilesArr,
									datasets: [{
									  fill: true,
									  label: 'Update Docs',
									  lineTension: 0,
									  data: updateDocumentPropertiesSumArr,
									  borderColor: "rgba(153,255,51,1.0)",
									  backgroundColor: "rgba(153,255,51,0.8)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Search Docs',
									  lineTension: 0,
									  data: doSearchSumArr,
									  borderColor: "rgba(255,153,0,1.0)",
									  backgroundColor: "rgba(255,153,0,0.8)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Result Count',
									  lineTension: 0,
									  data: getResultCountSumArr,
									  borderColor: "rgba(90,46,146,1.0)",
									  backgroundColor: "rgba(142,75,219,0.8)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Search Profiles',
									  lineTension: 0,
									  data: getSearchProfilesSumArr,
									  borderColor: "rgba(62,170,185,1.0)",
									  backgroundColor: "rgba(122,193,170,0.8)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Get Document',
									  lineTension: 0,
									  data: getDocumentSumArr,
									  borderColor: "rgba(72,107,219,1.0)",
									  backgroundColor: "rgba(59,146,255,0.8)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}, {
									  fill: true,
									  label: 'Get Single Document',
									  lineTension: 0,
									  data: getSingleDocumentSumArr,
									  borderColor: "rgba(150,178,50,1.0)",
									  backgroundColor: "rgba(209,255,33,0.8)",
									  pointHoverBackgroundColor: "rgba(255,128,0,1.0)",
									  pointHoverBorderColor: "rgba(220,220,220,1.0)"
									}]
								};
								
								weeklyCanvas = document.getElementById('generalWeeklyBarChart');
								weeklyChartCtx = weeklyCanvas.getContext('2d');
								weeklyChart = new Chart(weeklyChartCtx, 
								{
								  type: 'bar',
								  data: weeklyBarChartData
								});
							});
						}
	                });
                });
				
				$('#pageHeader').on('click', 'a', function(event) 
				{
					if (this.hash !== "") {
						event.preventDefault();
						var hash = this.hash;
						$('html, body').animate ({
							scrollTop:$(hash).offset().top
						}, 800, function() {
							window.location.hash = hash;
						});
					}
				});
				
				//search reports
				$("#searchReportsTextBox").keyup(function() {
					var searchVal = $("#searchReportsTextBox").val();
					var searchTable = $("#all_reports_table");
					filterSearch(searchVal, searchTable);
				});
				
				//search function in table
				function filterSearch(searchValue, searchTable) {
					var inputValue, filterValue, table, tbody, trArr, td_01, td_02, td_03, td_04, i;
					var td_05;
					inputValue = searchValue;
					filterValue = inputValue.toUpperCase();
					table = searchTable;
					tbody = table.children('tbody');
					trArr = tbody.children('tr');
					for (i = 0; i < trArr.length; i++) {
						td_01 = trArr[i].getElementsByTagName("td")[0];
						td_02 = trArr[i].getElementsByTagName("td")[1];
						td_03 = trArr[i].getElementsByTagName("td")[2];
						if ((td_01) || (td_02) || (td_03)) {
							if (td_01.innerHTML.toUpperCase().indexOf(filterValue) > -1) {
								trArr[i].style.display = "";
							} else if (td_02.innerHTML.toUpperCase().indexOf(filterValue) > -1) {
								trArr[i].style.display = "";
							} else if (td_03.innerHTML.toUpperCase().indexOf(filterValue) > -1) {
								trArr[i].style.display = "";
							} else {
								trArr[i].style.display = "none";
							}
						}
					}
				}
				$('[data-toggle="tooltip"]').tooltip();
            });
        </script>
    </head>
	<body>
        <div class="navbar navbar-default navbar-fixed-top">
			<section id="pageTopSection">
				<div class="container" id="pageHeader">
					<div class="panel-default text-center" style="padding-top:10px; padding-bottome:10px;">
						<h2>VR2 Reports Dashboard</h2>
						<hr width="88%"/>
						<p>
							<img class="center-block" style="padding-top:0px; padding-bottome:0px;" src="nzpost.png">
						</p>
					</div>
					<h4 class="navbar-left center-block">
						<div id="client_name">
							<span style="padding-top:0px; padding-bottome:0px;">
							<a href="#pageTopSection">CLIENT CODE</a>
							</span>
						</div>
					</h4>

					<button type="button" class="btn btn-default navbar-btn navbar-right center-block" data-toggle="tooltip" data-placement="top" title="Setting">
						<span class="glyphicon glyphicon-cog"></span> Setting
					</button>
					<!--
					<button type="button" class="btn btn-default navbar-btn navbar-right center-block" data-toggle="tooltip" data-placement="top" title="Search">
						<span class="glyphicon glyphicon-search"></span> Finder
					</button>
					-->
					<a href="#reportsSection" role="button" class="btn btn-default navbar-btn navbar-right center-block" data-toggle="tooltip" data-placement="top" title="Search Reports">
						<span class="glyphicon glyphicon-search"></span> Finder</a>
					<a href="#weeklyChartsSection" role="button" class="btn btn-default navbar-btn navbar-right center-block" data-toggle="tooltip" data-placement="top" title="Weekly Charts">
						<span class="glyphicon glyphicon-stats"></span> Weekly</a>
					<a href="#monthlyChartsSection" role="button" class="btn btn-default navbar-btn navbar-right center-block" data-toggle="tooltip" data-placement="top" title="Mobthly Charts">
						<span class="glyphicon glyphicon-stats"></span> Monthly</a>
					<a href="#reportsSection" role="button" class="btn btn-default navbar-btn navbar-right center-block" data-toggle="tooltip" data-placement="top" title="Weekly Charts">
						<span class="glyphicon glyphicon-stats"></span> Reports</a>
				</div>
			</section>
		</div>
        <div class="container" style="padding-top: 240px;">
			<!-- WEEKLY REPORTS CHARTS START -->
			<section id="weeklyChartsSection">
				<div class="panel panel-info">
					<div class="panel-heading">
					  <h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#weekly_chart_collapse">
						WEEKLY CHARTS REPORTS <span class="glyphicon glyphicon-menu-down"></span>
						</a>
					  </h4>
					</div>
					<div id="weekly_chart_collapse" class="panel-collapse collapse in">
					  <div class="panel-body">
						<div class="row">
							<div class="col-sm-2 col-md-2">
							</div>
							<div class="col-sm-8 col-md-8">
								<h3>Line Chart 01 - General Weekly Report</h3>
								<div class="center-block text-center">
									<canvas id="generalWeeklyLineChart" width="500px" height="300px"></canvas>
								</div>
							</div>
							<div class="col-sm-2 col-md-2">
							</div>
						</div>
						<hr />
						<div class="row">
							<div class="col-sm-2 col-md-2">
							</div>
							<div class="col-sm-8 col-md-8">
								<h3>Line Chart 02 - General Weekly Report</h3>
								<div class="center-block text-center">
									<canvas id="generalWeeklyLineChart_nofill" width="500px" height="300px"></canvas>
								</div>
							</div>
							<div class="col-sm-2 col-md-2">
							</div>
						</div>
						<hr />
						<div class="row">
							<div class="col-sm-2 col-md-2">
							</div>
							<div class="col-sm-8 col-md-8">
								<h3>Bar Chart - General Weekly Report</h3>
								<div class="center-block text-center">
									<canvas id="generalWeeklyBarChart" id="weeklyBarChart" width="500px" height="300px"></canvas>
								</div>
							</div>
							<div class="col-sm-2 col-md-2">
							</div>
						</div>
						<hr />
					  </div>
					</div>
				</div>
			</section>
			<hr />
			<!-- WEEKLY REPORTS CHARTS END -->
			<!-- MONTHLY REPORTS CHARTS START -->
			<section id="monthlyChartsSection">
				<div class="panel panel-info">
					<div class="panel-heading">
					  <h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#monthly_chart_collapse">
						MONTHLY CHARTS REPORTS <span class="glyphicon glyphicon-menu-down"></span>
						</a>
					  </h4>
					</div>
					<div id="monthly_chart_collapse" class="panel-collapse collapse in">
					  <div class="panel-body">
						<div class="row">
							<div class="col-sm-6 col-md-6">
								<h3>Line Chart - Monthly Report</h3>
								<canvas id="monthlyLineChart" width="500" height="300"></canvas>
							</div>
							<div class="col-sm-6 col-md-6">
								<h3>Line Chart - Total Report</h3>
								<canvas id="totalLineChart" width="500" height="300"></canvas>
							</div>
						</div>
						<hr />
						<div class="row">
							<div class="col-sm-6 col-md-6">
								<h3>Bar Chart - Monthly Report</h3>
								<canvas id="monthlyBarChart" width="500" height="300"></canvas>
							</div>
							<div class="col-sm-6 col-md-6">
								<h3>Bar Chart - Total Report</h3>
								<canvas id="totalBarChart" width="500" height="300"></canvas>
							</div>
						</div>
						<hr />
						<div class="row">
							<div class="col-sm-6 col-md-6">
								<form id="google_singleReportChartForm">
								<div class="form-group" id="google_singleReportChart">
								  <label for="selectSingleReport">Select Period of Report:</label>
								  <select class="form-control" id="google_selectSingleReport">
									<option>Select Period of Report</option>
								  </select>
								</div>
								</form>
								<div id="google_docTypePieChart_3d" style="width: 600px; height: 500px;"></div>
								<hr />
								<h5>Report of total for a period of time up to the selected MONTH</h5>
								<hr />
							</div>
							<div class="col-sm-6 col-md-6">
								<form id="google_docTypeChartForm">
								<div class="form-group" id="google_docTypeChart">
								  <label for="selectDocType">Choose Doc Type:</label>
								  <select class="form-control" id="google_selectDocType">
									<option>View Doc Type</option>
								  </select>
								</div>
								</form>
								<div id="google_docTypeDoughnutChart" style="width: 600px; height: 500px;"></div>
								<hr />
								<h5>Report of single doument type for a period of time up to the selected MONTH</h5>
								<hr />
							</div>
						</div>
						<hr />
					  </div>
					</div>
				</div>
			</section>
			<hr />
			<!-- MONTHLY REPORTS CHARTS END -->
			<section id="reportsSection">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#all_reports_collapse">
						REPORTS PANEL <span class="glyphicon glyphicon-menu-down"></span>
						</a>
						</h4>
					</div>
					<div id="all_reports_collapse" class="panel-collapse collapse in">
						<div class="panel-body">
							<!-- search reports box start -->
							<div id="searchReportsArea">
								<ul class="list-group">
									<li class="list-group-item list-group-item-default">
										<form class="form-inline" role="form">
											<div class="form-group has-success has-feedback">
												<label class="control-label" for="searchReportsTextBox"></label>
												<input id="searchReportsTextBox" type="text" placeholder="Search" class="form-control">
												<span class="glyphicon glyphicon-search form-control-feedback"><span>
											</div>
										</form>
									</li>
								</ul>
							</div>
							<!-- search reports box end -->		
							<div id="all_reports">					
								<p>
									<img class="center-block" style="padding-top:10px; padding-bottome:5px; height:40px; width:30px;" src="data_loading.gif">
								</p>
							</div>
						</div>
					</div>
				</div>
			</section>
			<hr />
		</div>
	</body>
</html>