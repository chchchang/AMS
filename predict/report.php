<?php
	include('../tool/auth/auth.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		exit('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
	}
	if(!$my->set_charset('utf8')) {
		exit('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
	}
	if(isset($_POST["取得曝光數資料"])){
		$ch_where="";//紀錄選擇頻道query的where部分
		$date_where="";//紀錄選擇日期query的where部分
		$param_type='';
		$param_ch=array();
		
		if(isset($_POST["實際日期期間"])){
			$param_type.='ss';
			$date_where.=" 日期 BETWEEN ? AND ? ";
		}
		else if(isset($_POST["實際日期"])){
			$n = count($_POST["實際日期"]);
			$param_type.=str_repeat("s", $n);
			if($n>0)
				$date_where.=" 日期 IN (?".str_repeat(",?", $n-1).") ";
		}
		
		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			$param_type.=str_repeat("i", $n);
			if($n>0)
				$ch_where.="AND 頻道號碼 IN(?".str_repeat(",?", $n-1).") ";
		}
		
		$a_params = array();
		$a_params[] = &$param_type;
		
		if(isset($_POST["實際日期期間"])){
			$a_params[] = &$_POST["實際日期期間"][0];
			$a_params[] = &$_POST["實際日期期間"][1];
		}
		else if(isset($_POST["實際日期"])){
			$n = count($_POST["實際日期"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["實際日期"][$i];
			}
		}
		
		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["頻道列表"][$i];
			}
		}
		
		$result=array();
		$sql='SELECT 頻道號碼,頻道名稱,平台識別碼,日期,曝光數0,曝光數1,曝光數2,曝光數3,曝光數4,曝光數5,曝光數6,曝光數7,曝光數8,曝光數9,曝光數10,曝光數11,曝光數12,曝光數13,曝光數14,曝光數15,曝光數16,曝光數17,曝光數18,曝光數19,曝光數20,曝光數21,曝光數22,曝光數23 FROM 使用報表_實際 WHERE'.$date_where.$ch_where.'ORDER BY 頻道號碼,頻道名稱,平台識別碼,日期';
		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		if(!call_user_func_array(array($stmt, 'bind_param'), $a_params)){
			exit('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		$stmt->bind_result($channelNum,$channelName,$platformId,$dataDate,$impression0,$impression1,$impression2,$impression3,$impression4,$impression5,$impression6,$impression7,$impression8,$impression9,$impression10,$impression11,$impression12,$impression13,$impression14,$impression15,$impression16,$impression17,$impression18,$impression19,$impression20,$impression21,$impression22,$impression23);
		while($stmt->fetch()) {
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數0']=$impression0;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數1']=$impression1;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數2']=$impression2;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數3']=$impression3;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數4']=$impression4;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數5']=$impression5;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數6']=$impression6;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數7']=$impression7;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數8']=$impression8;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數9']=$impression9;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數10']=$impression10;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數11']=$impression11;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數12']=$impression12;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數13']=$impression13;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數14']=$impression14;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數15']=$impression15;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數16']=$impression16;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數17']=$impression17;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數18']=$impression18;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數19']=$impression19;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數20']=$impression20;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數21']=$impression21;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數22']=$impression22;
			$result[$channelNum][$channelName][$platformId][$dataDate]['曝光數23']=$impression23;
		}
		
		
				
		$ch_where="";//紀錄選擇頻道query的where部分
		$date_where="";//紀錄選擇日期query的where部分
		$param_type='';
		$param_ch=array();
		
		if(isset($_POST["預測日期期間"])){
			$param_type.='ss';
			$date_where.=" 日期 BETWEEN ? AND ? ";
		}
		else if(isset($_POST["預測日期"])){
			$n = count($_POST["預測日期"]);
			$param_type.=str_repeat("s", $n);
			if($n>0)
				$date_where.=" 日期 IN (?".str_repeat(",?", $n-1).") ";
		}
		
		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			$param_type.=str_repeat("i", $n);
			if($n>0)
				$ch_where.="AND 頻道號碼 IN(?".str_repeat(",?", $n-1).") ";
		}
		
		$a_params = array();
		$a_params[] = &$param_type;
		
		if(isset($_POST["預測日期期間"])){
			$a_params[] = &$_POST["預測日期期間"][0];
			$a_params[] = &$_POST["預測日期期間"][1];
		}
		else if(isset($_POST["預測日期"])){
			$n = count($_POST["預測日期"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["預測日期"][$i];
			}
		}

		if(isset($_POST["頻道列表"])){
			$n = count($_POST["頻道列表"]);
			for($i = 0; $i < $n; $i++) {
				$a_params[] = &$_POST["頻道列表"][$i];
			}
		}
		
		$sql='SELECT 頻道號碼,頻道名稱,平台識別碼,日期,預測數0,預測數1,預測數2,預測數3,預測數4,預測數5,預測數6,預測數7,預測數8,預測數9,預測數10,預測數11,預測數12,預測數13,預測數14,預測數15,預測數16,預測數17,預測數18,預測數19,預測數20,預測數21,預測數22,預測數23 FROM 使用報表_預測 WHERE'.$date_where.$ch_where.'ORDER BY 頻道號碼,頻道名稱,平台識別碼,日期';
		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		}
		if(!call_user_func_array(array($stmt, 'bind_param'), $a_params)){
			exit('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
		}	
		$stmt->bind_result($channelNum,$channelName,$platformId,$dataDate,$rediction0,$rediction1,$rediction2,$rediction3,$rediction4,$rediction5,$rediction6,$rediction7,$rediction8,$rediction9,$rediction10,$rediction11,$rediction12,$rediction13,$rediction14,$rediction15,$rediction16,$rediction17,$rediction18,$rediction19,$rediction20,$rediction21,$rediction22,$rediction23);
		while($stmt->fetch()) {
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數0']=$rediction0;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數1']=$rediction1;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數2']=$rediction2;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數3']=$rediction3;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數4']=$rediction4;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數5']=$rediction5;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數6']=$rediction6;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數7']=$rediction7;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數8']=$rediction8;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數9']=$rediction9;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數10']=$rediction10;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數11']=$rediction11;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數12']=$rediction12;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數13']=$rediction13;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數14']=$rediction14;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數15']=$rediction15;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數16']=$rediction16;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數17']=$rediction17;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數18']=$rediction18;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數19']=$rediction19;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數20']=$rediction20;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數21']=$rediction21;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數22']=$rediction22;
			$result[$channelNum][$channelName][$platformId][$dataDate]['預測數23']=$rediction23;
		}
		/*echo '<table border="1"><tr><td>頻道號碼</td><td>頻道名稱</td><td>平台識別碼</td><td>日期</td><td>實際/預測</td><td>時段0</td><td>時段1</td><td>時段2</td><td>時段3</td><td>時段4</td><td>時段5</td><td>時段6</td><td>時段7</td><td>時段8</td><td>時段9</td><td>時段10</td><td>時段11</td><td>時段12</td><td>時段13</td><td>時段14</td><td>時段15</td><td>時段16</td><td>時段17</td><td>時段18</td><td>時段19</td><td>時段20</td><td>時段21</td><td>時段22</td><td>時段23</td></tr>';
		foreach($result as $channelNum=>$v) {
		foreach($v as $channelName=>$v2) {
		foreach($v2 as $platformId=>$v3) {
		foreach($v3 as $dataDate=>$v4) {
			if(isset($v4['曝光數0'])) {
				echo '<tr><td>'.$channelNum.'</td><td>'.$channelName.'</td><td>'.$platformId.'</td><td>'.$dataDate.'</td><td>實際</td>';
				for($i=0;$i<24;$i++)
					echo '<td>'.$v4['曝光數'.$i].'</td>';
				echo '</tr>';
			}
			else {
				echo '<tr><td>'.$channelNum.'</td><td>'.$channelName.'</td><td>'.$platformId.'</td><td>'.$dataDate.'</td><td>預測</td>';
				for($i=0;$i<24;$i++)
					echo '<td>'.$v4['預測數'.$i].'</td>';
				echo '</tr>';
			}
		}
		}
		}
		}
		echo '</table>';*/
		exit(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	$sql='SELECT 頻道號碼,頻道名稱 FROM 使用報表_實際 GROUP BY 頻道號碼,頻道名稱';
	if(!$stmt=$my->prepare($sql)) {
		exit('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
	}
	if(!$stmt->execute()) {
		exit('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
	}
	if(!$res=$stmt->get_result()){
		$logger->error('無法取得結果集，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		return(array("dbError"=>'無法取得結果集，請聯絡系統管理員！'));
	}
	$channel=array();
	while($row=$res->fetch_array()){
		array_push($channel,$row["頻道號碼"].":".$row["頻道名稱"]);
	}
	$channel=json_encode($channel,JSON_UNESCAPED_UNICODE);
	//echo $channelName;
	$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<script src="tool/jquery-ui-1.11.2.custom/jquery-ui.js"></script>
	<script type="text/javascript" src="tool/jquery.autocomplete.multiselect.js"></script>
	<script type="text/javascript" src="tool/jquery-ui.multidatespicker.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>/predict/tool/jquery-ui-1.11.2.custom/jquery-ui.css">
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>/predict/css/normalize.min.css">
	<link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css"/>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>/predict/css/main.css">

	<style type="text/css">
		body{
			padding-top:15px; 
			padding-left:10px; 
			padding-right:10px; 
			font-size:10px;
		}
		.ui-datepicker .ui-datepicker-calendar .ui-state-highlight a {
			background: #743620 none;
			color: white;
		}
		.floatLeft{
			float:left;
			margin-lift:10px;
			margin-right:10px;
		}
		fieldset {
			float:left;
			margin-lift:10px;
			margin-right:10px;
			padding-top:10px; 
			padding-left:10px; 
			padding-right:10px; 
			padding-bottom:10px; 
			border: 1px solid #708090;
		}
		fieldset legend {
			color:#000000;
			margin: 5px 5px;
			text-align: left;
		}
		
		.cssLayer{
			height: 325px;
			margin-top: 3px;
			border: 1px solid #B0E0E6;
			padding: 0px 20px;
			text-align: left; 
			width: 95%px;
			min-width: 1050px;
			-webkit-border-radius: 8px;
			-moz-border-radius: 8px;
			border-radius: 8px;
			-webkit-box-shadow: #666 0px 2px 3px;
			-moz-box-shadow: #666 0px 2px 3px;
			box-shadow: #666 0px 2px 3px;
			background: #fcfcfc;
			background: -webkit-gradient(linear, 0 0, 0 bottom, from(#fcfcfc), to(#B0E0E6));
			background: -moz-linear-gradient(#fcfcfc, #B0E0E6);
			-pie-background: linear-gradient(#fcfcfc, #B0E0E6);
			behavior: url(tool/PIE.htc);
		}
	</style>
</head>
<body>
	<div id ="optionLayer" class= "cssLayer">
		<p id = "optionTitle" style="font-weight:bold" width="100%">資料設定選項</p>
		<div id ="optionPannel">
		<div class = "floatLeft">頻道名稱:<br>(輸入頻道號碼或關鍵字)<input id="tags"  width="100%"></input></div>
		<fieldset>
		<legend>曝光資料顯示設定(擇一)</legend>
		<div class = "floatLeft">曝光資料日期(複選):<input type="hidden" id = "realAltField"></input><div id = "realDate" type="text" value = ""></div></div>
		<div class = "floatLeft">曝光資料日期(起迄):<br>開始日期<br><input type = "text" id = "realStart"></input> <br><br>結束日期<br><input type = "text" id = "realEnd"></input></div>
		</fieldset>
		<fieldset>
		<legend>預測資料顯示設定(擇一)</legend>
		<div class = "floatLeft">預測資料日期(複選):<input type="hidden" id = "predictAltField"></input><div id = "predictDate" type="text" value = ""></div></div>
			<div class = "floatLeft">預測資料日期(起迄):<br>開始日期<br><input type = "text" id = "predictStart"></input> <br><br>結束日期<br><input type = "text" id = "predictEnd"></input></div>
		</fieldset>
			<button id = "fetch">查詢</button>
		</div>
	</div>
	<div id = "曝光資料表"></div>	
	
	<script type="text/javascript">
	$(function() {
		//控制選向板面的縮放設定
		var optionLayerSlided=false;
		function optionLaerSlideUP(){
			$("#optionLayer").animate( { height:"40px" }, { queue:false, duration:500 });
			$("#optionPannel").slideUp(500);
			optionLayerSlided=true;
		}
		function optionLaerSlideDown(){
			$("#optionLayer").animate( { height:"325px" }, { queue:false, duration:500 });
			$("#optionPannel").slideDown(500);
			optionLayerSlided=false;
		}
		$("#optionTitle").click(function(){
			if(optionLayerSlided)
				optionLaerSlideDown();
			else
				optionLaerSlideUP();
		}).css("cursor","pointer");
		
		$( "#realDate" ).multiDatesPicker({dateFormat: 'yymmdd',altField: '#realAltField',
			onSelect: function(dateText) {
				$("#realStart,#realEnd").val("");
			}
		});
		$( "#predictDate" ).multiDatesPicker({dateFormat: 'yymmdd',altField: '#predictAltField',
			onSelect: function(dateText) {
				$("#predictStart,#predictEnd").val("");
			}
		});
		
		$("#realStart,#realEnd").datepicker({dateFormat: 'yymmdd',
			onSelect: function(dateText) {
				$("#realAltField").val("");
				$( "#realDate" ).multiDatesPicker('resetDates')
			}
		});
		
		$("#predictStart,#predictEnd").datepicker({dateFormat: 'yymmdd',
			onSelect: function(dateText) {
				$("#predictAltField").val("");
				$( "#predictDate" ).multiDatesPicker('resetDates')
			}
		});
		
		var availableTags = <?=$channel?>;
		$( "#tags" ).autocomplete({
			source: availableTags,
			multiselect: true
		});
		
		$("#fetch").click(function(){
			optionLaerSlideUP();
			var selectedCh = []; 
			$(".ui-autocomplete-multiselect-item").each(function(){
				selectedCh.push($(this).text().split(":")[0]);
			});
			var byPost={"取得曝光數資料":true,
				"頻道列表":selectedCh,
				"實際日期":$("#realAltField").val().split(", "),
				"預測日期":$("#predictAltField").val().split(", "),
				};
				if($("#realStart").val()!=""&&$("#realEnd").val()!="")
					byPost["實際日期期間"]=[$("#realStart").val(),$("#realEnd").val()];
				if($("#predictStart").val()!=""&&$("#predictEnd").val()!="")
					byPost["預測日期期間"]=[$("#predictStart").val(),$("#predictEnd").val()];
			$.post('?'
				,byPost
				,function(data){
					//console.log(data);
					creatTable(data);
				}
				,'json'
			);
			//alert($("#realAltField").val());
		});
		
		$( "#realDate" ).change(function(){alert("")});
		//創造表單
		function creatTable(data){
			$("#曝光資料表").empty();
			var m_table = $(document.createElement('table'));	//建立table主體
			var m_thead = $(document.createElement('thead'));	//建立header
			var m_tbody= $(document.createElement('tbody'));	//建立body
			m_table.appendTo($("#曝光資料表"));
			m_thead.appendTo(m_table);
			m_tbody.appendTo(m_table);
			//header
			var tr = $(document.createElement('tr'))
			$(document.createElement('td')).text("頻道號碼").appendTo(tr);
			$(document.createElement('td')).text("頻道名稱").appendTo(tr);
			$(document.createElement('td')).text("平台識別碼").appendTo(tr);
			$(document.createElement('td')).text("日期").appendTo(tr);
			$(document.createElement('td')).text("實際/預測").appendTo(tr);
			for(var i =0;i<24;i++)
				$(document.createElement('td')).text(i+":00 ~ "+(i+1)+":00").appendTo(tr);
			$(document.createElement('td')).text("全時段總和").appendTo(tr);
			tr.appendTo(m_thead);
			//body
			var typeSum=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var lastType= true;
			var lastChNum='';
			$.each(data,function(頻道號碼,value1){
				if(lastChNum!='')
					if(lastChNum!=頻道號碼){
						var tr = $(document.createElement('tr'))
						for(var i =0;i<4;i++){
							$(document.createElement('td')).text("--").appendTo(tr);
						}
						$(document.createElement('td')).text("該時段總和").appendTo(tr);
						var sum=0;
						for(var i =0;i<24;i++){
							$(document.createElement('td')).text(formatNumber(String(typeSum[i]))).appendTo(tr);
							sum+=typeSum[i];
						}
						$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
						typeSum=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
						if(lastType)
							tr.css({"font-weight": "bold"});
						else
							tr.css({"color":"#0000CD", "font-weight": "bold"});
						tr.appendTo(m_tbody);
					}
				lastChNum = 頻道號碼;
				
				$.each(value1,function(頻道名稱,value2){
					$.each(value2,function(平台識別碼,value3){
						$.each(value3,function(日期,value4){
							if(typeof(value4['曝光數0'])!='undefined'){
								//上一列的型態為預測，輸出統計列
								if(!lastType && typeSum.toString()!='0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0'){
									var tr = $(document.createElement('tr'))
									for(var i =0;i<4;i++){
										$(document.createElement('td')).text("--").appendTo(tr);
									}
									$(document.createElement('td')).text("該時段總和").appendTo(tr);
									var sum=0;
									for(var i =0;i<24;i++){
										$(document.createElement('td')).text(formatNumber(String(typeSum[i]))).appendTo(tr);
										sum+=typeSum[i];
									}
									$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
									typeSum=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
									tr.css({"color":"#0000CD", "font-weight": "bold"});
									tr.appendTo(m_tbody);
								}
								//本列的資料				
								var tr = $(document.createElement('tr'))
								$(document.createElement('td')).text(頻道號碼).appendTo(tr);
								$(document.createElement('td')).text(頻道名稱).appendTo(tr);
								$(document.createElement('td')).text(平台識別碼).appendTo(tr);
								$(document.createElement('td')).text(日期).appendTo(tr);
								$(document.createElement('td')).text("實際").appendTo(tr);
								var sum=0;
								for(var i =0;i<24;i++){
									$(document.createElement('td')).text(formatNumber(String(value4["曝光數"+i]))).appendTo(tr);
									sum+=value4["曝光數"+i];
									typeSum[i]+=value4["曝光數"+i];
								}
								$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
								tr.appendTo(m_tbody);
								lastType = true;
							}
							else{
								//上一列的型態為實際曝光量，輸出統計列
								if(lastType && typeSum.toString()!='0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0'){
									var tr = $(document.createElement('tr'))
									for(var i =0;i<4;i++){
										$(document.createElement('td')).text("--").appendTo(tr);
									}
									$(document.createElement('td')).text("該時段總和").appendTo(tr);
									var sum=0;
									for(var i =0;i<24;i++){
										$(document.createElement('td')).text(formatNumber(String(typeSum[i]))).appendTo(tr);
										sum+=typeSum[i];
									}
									$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
									typeSum=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
									tr.css({"font-weight": "bold"});
									tr.appendTo(m_tbody);
								}
								//本列的資料
								var tr = $(document.createElement('tr'))
								$(document.createElement('td')).text(頻道號碼).appendTo(tr);
								$(document.createElement('td')).text(頻道名稱).appendTo(tr);
								$(document.createElement('td')).text(平台識別碼).appendTo(tr);
								$(document.createElement('td')).text(日期).appendTo(tr);
								$(document.createElement('td')).text("預測").appendTo(tr);
								var sum=0;
								for(var i =0;i<24;i++){
									$(document.createElement('td')).text(formatNumber(String(value4["預測數"+i]))).appendTo(tr);
									sum+=value4["預測數"+i];
									typeSum[i]+=value4["預測數"+i];
								}
								$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
								tr.css("color","#0000CD");
								tr.appendTo(m_tbody);
								lastType = false;
							}							
						})
					})
				})
			});
			//最後一個的統計結果
			var tr = $(document.createElement('tr'))
			for(var i =0;i<4;i++){
				$(document.createElement('td')).text("--").appendTo(tr);
			}
			$(document.createElement('td')).text("該時段總和").appendTo(tr);
			var sum=0;
			for(var i =0;i<24;i++){
				$(document.createElement('td')).text(formatNumber(String(typeSum[i]))).appendTo(tr);
				sum+=typeSum[i];
			}
			$(document.createElement('td')).text(formatNumber(String(sum))).appendTo(tr);
			typeSum=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			if(lastType)
				tr.css({"font-weight": "bold"});
			else
				tr.css({"color":"#0000CD", "font-weight": "bold"});
			tr.appendTo(m_tbody);
			
			
			m_table.css({
				"border-style":"solid",
				"border-color":"#FAFAD2",
				"width":"100%"
			});
			m_thead.css({"background-color": "#444444",'color': 'white'});
			$( "#曝光資料表>table>tbody>tr:even" ).css({"background-color": "#FFFACD"});
		}
		
		//數字format
		function formatNumber(str){
			if(str.length <= 3){
				return str;
			} else {
				return formatNumber(str.substr(0,str.length-3))+','+str.substr(str.length-3);
			}
		}
	});//end of $(function(){})
	
	</script>
</body>
</html>