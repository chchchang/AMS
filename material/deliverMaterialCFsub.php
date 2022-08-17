<?php
	//require '../tool/auth/auth.php';
	require '../tool/auth/authAJAX.php';
	define('PAGE_SIZE',10);
	define('MATERIALPATH',Config::GET_MATERIAL_FOLDER());
	
	$ajaxGetTableDataUrl = "";
	$ajaxUploadCFUrl = "";
	$ajaxGetAndPutStatusUrl = "";
	if(isset($_POST["ajaxTarget"])){
		switch($_POST["ajaxTarget"]){
			case "PMS":
				$ajaxGetTableDataUrl = "deliverMaterialSubFunction/ajaxGetTableData_PMS.php";
				$ajaxUploadCFUrl = "deliverMaterialSubFunction/ajaxUploadCF_PMS.php";
				$ajaxGetAndPutStatusUrl ="deliverMaterialSubFunction/ajaxGetAndPutStatus_PMS.php";
				break;
			case "CAMPS":
				$ajaxGetTableDataUrl = "deliverMaterialSubFunction/ajaxGetTableData_CAMPS.php";
				$ajaxUploadCFUrl = "deliverMaterialSubFunction/ajaxUploadCF_CAMPS.php";
				$ajaxGetAndPutStatusUrl ="deliverMaterialSubFunction/ajaxGetAndPutStatus_CAMPS.php";
				break;
		}
	}
	
	if(isset($_POST['action'])){
		if($_POST['action']==='getMateral'){
			$sqlparas = array();
			$sqlparas["fromRowNo"]=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$sqlparas["totalRowCount"]=0;
			$sqlparas["searchBy"]='%'.((isset($_POST['searchBy']))?$_POST['searchBy']:'').'%';
			if(isset($_POST['開始時間']))
				$sqlparas["startDate"]=($_POST['開始時間']=='')?'0000-00-00':$_POST['開始時間'].' 00:00:00';
			else
				$sqlparas["startDate"]='0000-00-00';
			if(isset($_POST['結束時間']))
				$sqlparas["endDate"]=($_POST['結束時間']=='')?'9999-12-31':$_POST['結束時間'].' 23:59:59';
			else
				$sqlparas["endDate"]='9999-12-31';
			if(isset($_POST['素材群組識別碼']))
				$sqlparas["materialGroup"]=($_POST['素材群組識別碼']=='0'||$_POST['素材群組識別碼']==null)?'%':$_POST['素材群組識別碼'];
			else
				$sqlparas["materialGroup"]='%';
			if(isset($_POST['僅顯示未派送']))
				$sqlparas["unCimmitOnly"]=$_POST['僅顯示未派送'];
			else
				$sqlparas["unCimmitOnly"]='false';
			if(isset($_POST['僅顯示未取得編號']))
				$sqlparas["unNumberOnly"]=$_POST['僅顯示未取得編號'];
			else
				$sqlparas["unNumberOnly"]='false';
			
			//若有設定CAMPS派送時間，將以下falg設為true並在sql中加入相關參數
			$sqlparas["CAMPSTimeFlag"] = false;
			if(isset($_POST['CAMPS開始時間'])&&$_POST['CAMPS開始時間']!=''){
				$sqlparas["startDateCAMPS"]=$_POST['CAMPS開始時間'].' 00:00:00';
				$sqlparas["CAMPSTimeFlag"] = true;
			}else
				$sqlparas["startDateCAMPS"]='0000-00-00';
			if(isset($_POST['CAMPS結束時間'])&&$_POST['CAMPS結束時間']!=''){
				$sqlparas["endDateCAMPS"]=$_POST['CAMPS結束時間'].' 23:59:59';
				$sqlparas["CAMPSTimeFlag"] = true;
			}else
				$sqlparas["endDateCAMPS"]='9999-12-31';
			
			switch($_POST['素材是否已到']){
				case '僅顯示素材未到項目':
					$sqlparas["fileUploadOrNot"]=' AND (素材原始檔名 IS NULL OR 素材原始檔名="") ';
				break;
				case '僅顯示素材已到項目':
					$sqlparas["fileUploadOrNot"]=' AND (素材原始檔名 IS NOT NULL AND 素材原始檔名!="") ';
				break;
				default:
					$sqlparas["fileUploadOrNot"]='';
				break;
			}
			$showAll = (isset($_POST['顯示全部']) && $_POST['顯示全部'])?true:false;
			//取得總筆數
			/*$result=$my->getResultArray('SELECT COUNT(*) COUNT FROM 素材 
			WHERE 素材類型識別碼=3 
				AND 素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? )
				'.($sqlparas["unCimmitOnly"]=='true'?'AND ( 影片派送時間 IS NULL )':'').'
				'.($sqlparas["unNumberOnly"]=='true'?'AND ((影片媒體編號 IS NULL || 影片媒體編號 = "") OR (影片媒體編號北 IS NULL || 影片媒體編號北 = "") OR (影片媒體編號南 IS NULL || 影片媒體編號南 = ""))':'')
				.$sqlparas["fileUploadOrNot"]
				,'ssssssssssss'
				,$sqlparas["materialGroup"]
				,$sqlparas["startDate"],$sqlparas["endDate"],$sqlparas["startDate"],$sqlparas["endDate"],$sqlparas["startDate"],$sqlparas["endDate"],$sqlparas["startDate"]
				,$sqlparas["searchBy"],$sqlparas["searchBy"],$sqlparas["searchBy"],$sqlparas["searchBy"]
				);*/
				$result=$my->getResultArray('SELECT COUNT(*) COUNT FROM 素材 
				WHERE 素材類型識別碼=3 
				AND 素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? OR CAMPS影片媒體編號 LIKE ?)
				AND ((CAMPS影片派送時間 BETWEEN ? AND ?)'.($sqlparas["CAMPSTimeFlag"]?'':'OR CAMPS影片派送時間 IS NULL').')
				'.($sqlparas["unCimmitOnly"]=='true'?'AND ( CAMPS影片派送時間 IS NULL )':'').'
				'.($sqlparas["unCimmitOnly"]=='true'?'AND ((CAMPS影片媒體編號 IS NULL || CAMPS影片媒體編號 = ""))':'')
				.$sqlparas["fileUploadOrNot"]
				,'sssssssssssssss'
				,$sqlparas["materialGroup"]
				,$sqlparas["startDate"],$sqlparas["endDate"],$sqlparas["startDate"],$sqlparas["endDate"],$sqlparas["startDate"],$sqlparas["endDate"],$sqlparas["startDate"]
				,$sqlparas["searchBy"],$sqlparas["searchBy"],$sqlparas["searchBy"],$sqlparas["searchBy"],$sqlparas["searchBy"],$sqlparas["startDateCAMPS"],$sqlparas["endDateCAMPS"]
				);
				
				$sqlparas["totalRowCount"]=$result[0]["COUNT"];
			//取得資料
			getTableData($sqlparas);
		}
		else if(($_POST['action']==='getReorders')&&isset($_POST['素材識別碼'])){
			$my=new MyDB(true);
			$sql='
				SELECT 託播單.託播單識別碼,託播單名稱
				FROM 託播單 INNER JOIN 託播單素材 ON 託播單.託播單識別碼=託播單素材.託播單識別碼
				WHERE 託播單狀態識別碼 IN(2,4) AND 素材識別碼=?
				ORDER BY 託播單.託播單識別碼
			';
			$result=$my->getResultArray($sql,'i',$_POST['素材識別碼']);
			$sql='
				UPDATE 託播單 JOIN 託播單素材 ON 託播單.託播單識別碼=託播單素材.託播單識別碼
				SET 託播單.託播單需重新派送 = 1
				WHERE 託播單狀態識別碼 IN(2,4) AND 素材識別碼=?
			';
			$my->execute($sql,'i',$_POST['素材識別碼']);
			header('Content-Type: application/json; charset=utf-8');
			exit(json_encode($result));
		}
		else if(($_POST['action']==='getAndPutStatus')&&isset($_POST['素材識別碼'])&&isset($_POST['副檔名'])){
			getAndPutStatus();
		}
		else if(($_POST['action']==='uploadCF')&&isset($_POST['素材識別碼'])&&isset($_POST['副檔名'])){
			uploadCF();
		}
	}
	
	function getTableData($sqlparas){
		global $ajaxGetTableDataUrl;
		require_once($ajaxGetTableDataUrl);
	}
	
	function uploadCF(){
		global $ajaxUploadCFUrl;
		require_once($ajaxUploadCFUrl);
	}
	
	function getAndPutStatus(){
		global $ajaxGetAndPutStatusUrl;
		require_once($ajaxGetAndPutStatusUrl);
	}
?>
<!doctype html>
<style>
.pretty-select {
  background-color: #007fff;
  color: #FFFFFF;
  padding: 2px;
  width: 250px;
  height:35px;
  border: none;
  font-size: 15px;
  -webkit-appearance: button;
  appearance: button;
  outline: none;
}

.pretty-select option {
  background-color: #F0F0F0;
  color: black;
  padding: 30px;
}
</style>
<head>
<meta charset="UTF-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css" />
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="../tool/jquery-3.4.1.min.js"></script>
<script src="../tool/jquery.loadmask.js"></script>
<script src="../tool/datagrid/CDataGrid.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<body>

<select id="deliverAction" class ="pretty-select">
<option value="PMS">自動派片系統</option>
<option value="CAMPS">CAMPS系統</option>
<option value="POINT">端點Barker系統</option>
</select>

<?php include('_searchMaterialUI.php'); ?>
<fieldset class="fieldset-auto-width">
<legend>媒體編號條件</legend>
<label for="僅顯示尚未派送項目">僅顯示尚未派送項目</label><input type="checkbox" id="僅顯示尚未派送項目" class = "checkboxradio">
<label for="僅顯示未取得媒體編號項目">僅顯示未取得媒體編號項目</label><input type="checkbox" id="僅顯示未取得媒體編號項目" class = "checkboxradio">
</fieldset>
<fieldset class="fieldset-auto-width">
<legend>素材已到條件</legend>
<label for="僅顯示素材已到與未到項目">僅顯示素材已到與未到項目</label><input type="radio" name="素材是否已到" value="顯示素材已到與未到項目" checked id="僅顯示素材已到與未到項目" class = "checkboxradio">
<label for="僅顯示素材未到項目">僅顯示素材未到項目</label><input type="radio" name="素材是否已到" value="僅顯示素材未到項目" id="僅顯示素材未到項目" class = "checkboxradio">
<label for="僅顯示素材已到項目">僅顯示素材已到項目</label><input type="radio" name="素材是否已到" value="僅顯示素材已到項目" id="僅顯示素材已到項目" class = "checkboxradio">
</fieldset>
<button id='pmscheckbtn'>Pms派送狀況查詢</button>
<div id = 'showAllDiv'>
<input type="checkbox" id="showAll">顯示全部<a id = "allCount"></a>筆資料(若資料量過大不建議使用)
</div>
<div id="DG"></div>
<script>
$("#_searchMUI_materialTypeSelectoin,#_searchMUI_missingFileOnly,#_searchMUI_missingFiletext").hide();
</script>

<script>
$(document).ready(function(){
	//jquery ui checkbox and radio
	$(".checkboxradio").checkboxradio();
	//覆寫mask、unmask
	var oriMask=$.fn.mask;
	var oriUnmask=$.fn.unmask;
	$.fn.mask=function(label,delay){
		$(this).prop('disabled',true);
		oriMask.apply(this,arguments);
	};
	$.fn.unmask=function(){
		$(this).prop('disabled',false);
		oriUnmask.apply(this);
	};
	
	$('#僅顯示尚未派送項目,#僅顯示未取得媒體編號項目').click(function(){
		getmDataGrid();
	});
	$('input[name=素材是否已到]').change(function(){
		getmDataGrid();
    });
});
		
		
	$('#showAll').click(function(){
		getmDataGrid();
	});
	
	$('#deliverAction').change(function(){
		//選項為CAMPS才顯示CAMPS派送時間的搜尋條件
		if($("#deliverAction").val()=="CAMPS"){
			$( "#_searchMUI_tabs_nav-CAMPS_date" ).show();
		}
		else{
			$( "#_searchMUI_tabs_nav-CAMPS_date" ).hide();
		}
		getmDataGrid();
	});
	
	
	//載入DG資料
	function getmDataGrid(){
		$('#DG').empty();
		$('body').mask('取得資料中...');
		var bypost={
			action:'getMateral'
			,searchBy:$('#_searchMUI_shearchText').val()
			,'素材類型':3
			,pageNo:1,order:'素材識別碼'
			,asc:'DESC'
			,"素材群組識別碼":$("#_searchMUI_materialGroup").val()
			,"開始時間":$("#_searchMUI_startDate").val()
			,"結束時間":$("#_searchMUI_endDate").val()
			,"僅顯示未派送":$('#僅顯示尚未派送項目').prop('checked')
			,"僅顯示未取得編號":$('#僅顯示未取得媒體編號項目').prop('checked')
			,"素材是否已到":$("input[name=素材是否已到]:checked").val()
			,"CAMPS開始時間":$("#_searchMUI_startDate_CAMPS").val()
			,"CAMPS結束時間":$("#_searchMUI_endDate_CAMPS").val()
			,ajaxTarget:$("#deliverAction").val()
		};
		if($('#showAll').prop('checked'))
			bypost['顯示全部']=true;
		$.post(null,bypost
		,function(json){
			//若大於10筆資料，顯示「顯示全部資料」相關選項
			if(json.allCount>10){
				$('#showAllDiv').show();
				$('#allCount').text(json.allCount);
			}
			else{
				//小於10筆資料，隱藏「顯示全部資料」相關選項，並將「顯示全部資料」勾選取消
				$('#showAllDiv').hide();
				$('#showAll').prop('checked',false)
			}
			json.header.splice(0,0,"");
			for(tr in json.body){
				json.body[tr].splice(0,0,['<input type="checkbox">','html'])
			}
			var DG = new DataGrid('DG',json.header,json.body);
			$('#DG').prepend('<button id="getAll">取得結果</button><button id="putAll">派送影片</button>')
						$('#getAll').click(function(event){
				$(this).mask('...');
				var selected=$('#DG tr').has('input[type=checkbox]:checked').children('td').children('button:contains("取得")');
				selected.click()
				var interval=setInterval(function(){
					if(!selected.isMasked()){
						clearInterval(interval);
						$(event.target).unmask();
					}
				},1000);
			});
			
			$('#putAll').click(function(event){
				$(this).mask('...');
				var selected=$('#DG tr').has('input[type=checkbox]:checked').children('td').children('button:contains("派送")');
				selected.click()
				var interval=setInterval(function(){
					if(!selected.isMasked()){
						clearInterval(interval);
						$(event.target).unmask();
					}
				},1000);
			});
			setDgBtn();
			DG.set_page_info(json.pageNo,json.maxPageNo);
			DG.set_sortable(json.sortable,true);
			//頁數改變動作
			DG.pageChange=function(toPageNo) {
				bypost.pageNo=toPageNo;
				DG.update();
			}
			//header點擊
			DG.headerOnClick = function(headerName,sort){
				bypost.order=headerName;
				switch(sort){
				case "increase":
					bypost.asc='ASC';
					break;
				case "decrease":
					bypost.asc='DESC';
					break;
				case "unsort":
					break;
				}
				DG.update();
			};
			DG.update = function(){
				$.post(null,bypost
				,function(json){
				for(tr in json.body){
					json.body[tr].splice(0,0,['<input type="checkbox">','html'])
				}
				DG.set_data(json.body);
				setDgBtn();
				},'json');
			};
			/*$$$$$全部、取消全部選擇以及批次取得結果、派送影片功能$$$$$*/
			$('body').unmask();
		},'json');
		
		function setDgBtn(){
			var deliverAction = $("#deliverAction").val();
			var clickFunctionUrl = "deliverMaterialSubFunction/";
			if(deliverAction == "PMS"){
				clickFunctionUrl += "javascritpButtonClick_PMS.js";
			}else if (deliverAction == "CAMPS"){
				clickFunctionUrl += "javascritpButtonClick_CAMPS.js";
			}
			else if (deliverAction == "POINT"){
				clickFunctionUrl += "javascritpButtonClick_POINT.js";
			}
			
			$.getScript(clickFunctionUrl).done(function( script, textStatus ) {
				//buttonOnClick is defined in the js file that getScript referenced.
				$('#DG tbody button').not('#getAll,#putAll').click(buttonOnClick);
			});
			/*^^^^^全部、取消全部選擇以及批次取得結果、派送影片功能^^^^^*/
			$('#DG tr').first().find('th').first().html('<input type="checkbox" id="selectAllOrNot">');
			
			$('#selectAllOrNot').click(function(){
				if($(this).prop('checked')){
					$('#DG input[type=checkbox]').prop('checked',true);
				}
				else{
					$('#DG input[type=checkbox]').prop('checked',!true);
				}
			});
		}
	}
	
	getmDataGrid();
	
	$('#pmscheckbtn').click(function(){
		location.assign('../checkPms.php');
	});
</script>
</head>

</body>