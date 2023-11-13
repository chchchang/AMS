<?php
/****
 * 利用ecxcle匯入託播單資訊
 * 2022 09 22 若選擇單一平台版位，excle中會加入連結類型的tab供參考
 * 
 */
	include('../tool/auth/authAJAX.php');
	require_once '../tool/phpExtendFunction.php';
	if(isset($_POST["action"])){
		//檢查委刊單是某存在
		if($_POST["action"] === "checkOrderListExist"){
			$sql = 'select * from 委刊單 WHERE 委刊單識別碼 = ?';						
			$result = $my->getResultArray($sql,'i',$_POST["委刊單識別碼"]);
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="../tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
	<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
	<link rel='stylesheet' type='text/css' href='../external-stylesheet.css'/>
	<script src="../tool/GeneralSanitizer.js"></script>
	<script src="../tool/xlsx.full.min.js"></script>
	<script src="../tool/vue/vue.min.js"></script>
	<script src="../WebConfig.js"></script>
	<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="../tool/jquery.loadmask.js"></script>
	<style type="text/css">
	</style>
</head>
<body>
<fieldset>
<legend>****注意事項****</legend>
一份excel檔案僅供一種版位類型的託播單匯入，請選擇版位類型後下載範例檔案填寫，並勿更動第二頁「版位識別碼對照表」內容。<br>
「開始日期」、「開始日期」、「預約到期」欄位資料須為excel的日期格式，類型(顯示格式)不拘。<br>
不須輸入的欄位留白即可，系統會自動帶入null值。<br>
</fieldset>
<fieldset>
<legend>匯入託播單excel檔案</legend>
版位類型:<select id="_searchOUI_positiontype"></select> <button id="downloadExample">下載空白Excel範例檔案</button><br>
表單內容範例:
<br>
<div id="exampleTableVue">
	<table  class="styledTable" style="color:blue;">
		<thead>
			<tr>
				<th v-for="headName in thead">{{headName}}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td v-for="td in tbody">{{td}}</td>
			</tr>
		</tbody>
	</table>
</div>

<br>
<input type="file" name="excleFileDom" id="excleFileDom" />
</fieldset>
<fieldset>
<legend>匯入/檢查結果</legend>
<div id="resultBlock"></div>
<div id="resultTableVue">
	<table class="styledTable">
		<thead>
			<tr  v-for="(row, index) in rows" :key="index">
				<th  v-if="index === 0" v-for="(td, tdId) in row" :key="tdId">{{td}}</th>
			</tr>
		</thead>
		<tbody>
			<tr  v-for="(row, index) in rows" :key="index" v-if="index > 0">
				<td v-for="(td, tdId) in row" :key="tdId">{{td}}</td>
			</tr>
		</tbody>
	</table>
</div>

<br><button id="submitExcel" class="darkButton">開始匯入</button> <button id="downloadResult">下載執行結果Excel檔案</button>
</fieldset>
<script type="text/javascript">
	let global_thead=[];//表單title
	let	global_positionList=[];//版位
	let global_linkType=[];//點擊開砌起類型

	$("#downloadResult,#submitExcel").hide();
	//版位類型自動完成選項
	$.post('orderManaging.php',{method:'getPositionTypeSelection'}
		,function(positionTypeOption){
			$(document.createElement("option")).text('').val('').appendTo($("#_searchOUI_positiontype"));
			for(var i in positionTypeOption){
				var opt = $(document.createElement("option"));
				opt.text(positionTypeOption[i][0]+":"+positionTypeOption[i][1])//紀錄版位類型名稱
				.val(positionTypeOption[i][0])//紀錄版位類型識別碼
				.appendTo($("#_searchOUI_positiontype"));
			}
			
			$( "#_searchOUI_positiontype" ).combobox({
					select: function( event, ui ) {
						_searchOUI_setPosition(this.value);
						getLinkTypeByPositionType(getPtNameOnUI());
					}
			});
			
			if($("#_searchOUI_positiontype").attr('selectedId')!='undefined'){
				var sid = $("#_searchOUI_positiontype").attr('selectedId');
				$("#_searchOUI_positiontype option[value="+sid+"]").prop('selected',true);
				$( "#_searchOUI_positiontype" ).combobox('setText',$("#_searchOUI_positiontype option[value="+sid+"]").text())
				.val(sid);
			}
			_searchOUI_setPosition($("#_searchOUI_positiontype").attr('selectedId'));
			
		}
		,'json'
	);

	//回傳UI上不含版位編號的版位類型名稱
	function getPtNameOnUI(){
		let ptname = $("#_searchOUI_positiontype option:selected").text().split(":");//移除網頁option中冒號「:」前的版位識別碼
		ptname.shift();
		return ptname.join(":");
	}

	//依照選擇的版位類型取得板位資料
	function _searchOUI_setPosition(pId){
		$.post( "ajaxToDB_Order.php", { action: "getPositionByPositionType",版位類型識別碼:pId }, 
			( data )=> {
				//第一行加入版位類型資料
				global_positionList=[[parseInt(pId),getPtNameOnUI()]];
				if(data){
					data.forEach((pdata)=>{global_positionList.push([pdata["版位識別碼"],pdata["版位名稱"]]);});
					//利用第一筆版位取得託播單表單資料
					if(data.length&&data.length!=0)
						getOrderForm(data[0]["版位識別碼"]);			
				}
				global_positionList.unshift(["版位識別碼","版位名稱"]);
			}
			,"json"
		);
	}

	//依照版位類型取得點擊開啟類型資料:
	//目前支援單一平台版位類型
	function getLinkTypeByPositionType(ptName){
		ptName = String(ptName);
		let sheet = [];
		if(ptName.startsWith("單一平台") || ptName==="barker頻道"){
			sheet = WebConfig.VSM_LINK.general.map((ele)=>([ele.value,ele.text]));
			if(WebConfig.VSM_LINK[ptName]){
				sheet= sheet.concat(WebConfig.VSM_LINK[ptName].map((ele)=>([ele.value,ele.text])));
			}
		}
		sheet.unshift(["連結類型代碼(請填入此值)","連結類型說明"]);
		global_linkType = sheet;
	}
	//取得範例與表單格是資料
	function getOrderForm(pId){
		let callback = (data)=>{
				var today = new Date();
				var dd = String(today.getDate()).padStart(2, '0');
				var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
				var yyyy = today.getFullYear();
				today = yyyy+"-"+mm + '-' + dd;
				positionPrar = data;
				global_thead = ["版位識別碼(多筆用分號「;」隔開)","委刊單識別碼","託播單名稱","託播單說明","託播單開始時間(時分秒不指定將帶入00:00:00)","託播單結束時間(時分秒不指定將帶入23:59:59)","時段(多筆用分號「;」隔開,留白預設為0~23時段)"
							,"預約到期日(不指定將帶入託播單開始時間)","售價"];
				let tbodyForVue = [
					pId,
					"1234",
					"新增託播單名稱",
					"",
					today+" 00:00:00",
					today+" 23:59:59",
					"0;1;2;3;4;5;6;7;8;9;10;11;12;13;14;15;16;17;18;19;20;21;22;23",
					today+" 00:00:00",
					""];
				for(let i in data["其他參數設定"]){
					global_thead.push(data["其他參數設定"][i]["版位其他參數顯示名稱"]);
					tbodyForVue.push(data["其他參數設定"][i]["版位其他參數預設值"]);
				}
				for(let i in data["版位素材設定"]){
					global_thead.push(data["版位素材設定"][i]["顯示名稱"]+i+":素材識別碼");
					global_thead.push(data["版位素材設定"][i]["顯示名稱"]+i+":可否點擊(1/0)");
					global_thead.push(data["版位素材設定"][i]["顯示名稱"]+i+":點擊開始類型");
					global_thead.push(data["版位素材設定"][i]["顯示名稱"]+i+":點擊開始位址");
					tbodyForVue.push("123456");
					tbodyForVue.push("0");
					tbodyForVue.push("");
					tbodyForVue.push("");
				}
				
				exampleTableVue.thead = global_thead;
				exampleTableVue.tbody = tbodyForVue;
			};
		getPositionPara(pId,callback);
	}

	//利用ajax取得版位資料，ajax完成後執行clallbac function
	let getPositionPara = function(pId,callback){
		$.post("ajaxToDB_Order.php",{action:'取得版位素材與參數','版位識別碼':pId},
		(data)=>{
			if(data["success"]){
				callback(data);
			}
		}
		,"json"
		);
	}

	function downLoadExcel(orderData,filname){
		/* generate worksheet and workbook */
		const workbook = XLSX.utils.book_new();
		//託播單設定
		const worksheet = XLSX.utils.aoa_to_sheet(orderData,{cellDates:true});
		XLSX.utils.book_append_sheet(workbook, worksheet, "託播單匯入");

		//版位對照表
		const worksheet2 = XLSX.utils.aoa_to_sheet(global_positionList);
		XLSX.utils.book_append_sheet(workbook, worksheet2, "版位識別碼對照表");

		//點擊開啟類型對照表
		const worksheet3 = XLSX.utils.aoa_to_sheet(global_linkType);
		XLSX.utils.book_append_sheet(workbook, worksheet3, "點擊開啟類型對照表");

		/* calculate column width */
		//const max_width = global_thead.reduce((w, r) => Math.max(w, r.length*2), 10);
		//worksheet["!cols"] = [ { wch: max_width } ];

		/* create an XLSX file and try to save to .xlsx */
		XLSX.writeFile(workbook, filname);
	}
	//下載範例檔案
	$("#downloadExample").click(function(){		
		downLoadExcel([global_thead],$("#_searchOUI_positiontype option:selected").text()+"託播單.xlsx");
	});
	//下載匯入結果檔案
	$("#downloadResult").click(function(){		
		downLoadExcel(global_orderInfoSheetAoa,$(this).attr("fileName"));
	});
	
	let global_adOrders ={};//存放輸入的託播單使用
	let global_orderInfoSheetAoa ={};//存放輸入的託播單array使用
	let global_validOrderCheck = true;//匯入的資訊是否合格
	//處裡excle檔案
	function handleFile(e) {
		var file = e.target.files[0];
		$("#downloadResult").attr("fileName","result:"+file.name).show();
		$("#submitExcel").show().attr('disabled', false);

		var reader = new FileReader();
		reader.onload = function(e) {
			var data = e.target.result;
			/* reader.readAsArrayBuffer(file) -> data will be an ArrayBuffer */
			
			var workbook = XLSX.read(e.target.result);
			//取得板位對照表資料
			let positionInfoSheet = workbook.Sheets["版位識別碼對照表"];
			global_positionList = XLSX.utils.sheet_to_json(positionInfoSheet, { header: 1});
			let positionSet = new Set();
			for(let i in global_positionList){
				if(i==0)//skip title
				continue;
				positionSet.add(global_positionList[i][0]);
			}
			//取得連結類型資料
			let linkTypeInfoSheet = workbook.Sheets["點擊開啟類型對照表"];
			global_linkType = XLSX.utils.sheet_to_json(linkTypeInfoSheet, { header: 1});
			let linkSet = new Set();
			for(let i in global_linkType){
				if(i==0)//skip title
				continue;
				linkSet.add(global_linkType[i][0]);
			}
			//處裡託播單資訊
			let orderInfoSheet = workbook.Sheets["託播單匯入"];
			global_orderInfoSheetAoa = XLSX.utils.sheet_to_json(orderInfoSheet, { header: 1});
			//換算excel日期使用
			function ExcelDateToJSDate(serial) {
				return new Date((serial - (25567 + 1))*86400*1000-32*3600000 );
			}
			function padTo2Digits(num) {
				return num.toString().padStart(2, '0');
			}
			function formatDate(date) {
				return (
					[
					date.getFullYear(),
					padTo2Digits(date.getMonth() + 1),
					padTo2Digits(date.getDate()),
					].join('-') +
					' ' +
					[
					padTo2Digits(date.getHours()),
					padTo2Digits(date.getMinutes()),
					padTo2Digits(date.getSeconds()),
					].join(':')
				);
			}
			//1版位識別碼，1:委刊單識別碼，3:託播單名稱，4:託播單說明，5:託播單開始時間、6:託播單結束時間、7:託播單時段、8:預約到期日、9:售價
			//9之後:託播單參數、素材:素材識別碼，素材可否點擊，點擊開啟類型，點擊開始位址
			//先利用第一筆資料取得版位類型
			let [pId] = positionSet;
			let orderListMap={};
			let callback = (data)=>{
				let paraOrders = Object.keys(data["其他參數設定"]);
				let	materialOrders = Object.keys(data["版位素材設定"]);
				global_adOrders = {};
				global_orderInfoSheetAoa[0].push("執行結果");
				for(let i in global_orderInfoSheetAoa){
					if(i == 0)//第一行是title 跳過
						continue;
					let row = global_orderInfoSheetAoa[i];
					let orderListId = row[1];
					if(row.length==0)
						continue;
					//日期換算
					row[4]=ExcelDateToJSDate(row[4])//廣告開始
					row[5]=ExcelDateToJSDate(row[5])//廣告結束
					row[7]=(row[7]==undefined)?row[4]:ExcelDateToJSDate(row[7])//預約日期
					let adOrder = {
						"版位類型識別碼":pId,
						"版位識別碼":String(row[0]).replaceAll(";",","),//將區分多筆的;改為系統使用的,
						"託播單名稱":row[2],
						"託播單說明":row[3],
						"廣告期間開始時間":formatDate(row[4]),
						"廣告期間結束時間":formatDate(row[5]).replace("00:00:00","23:59:59"),//若結束時間為00:00:00 置換為當天結束
						"廣告可被播出小時時段":getHoursStringFromExcelData(row[6]),
						"預約到期時間":formatDate(row[7]),
						"售價":(row[8]==undefined)?null:row[8],
						'其他參數':{},
						'素材':{},
						'rowId':i
					};
					//index 10開始為其他參數
					let index =9;
					for(let paraOrder of paraOrders){
						adOrder['其他參數'][paraOrder]=row[index++];
						//若沒有填入，使用預設值
						if(adOrder['其他參數'][paraOrder] == undefined ){
							adOrder['其他參數'][paraOrder] = data['其他參數設定'][paraOrder]["版位其他參數預設值"]
						}
					}
					//其他參數後接著是素材:素材識別碼，素材可否點擊，點擊開啟類型，點擊開始位址
					for(let materialOrder of materialOrders){
						let materialObj = {"素材識別碼":row[index++],"可否點擊":row[index++],"點擊後開啟類型":row[index++],"點擊後開啟位址":row[index++]};
						Object.keys(materialObj).forEach((key)=>{
							if(materialObj[key]==undefined)materialObj[key]=null;
						});
						if(materialObj["可否點擊"] == null)
							materialObj["可否點擊"]=0;
						if(materialObj["素材識別碼"]!=null||materialObj["點擊後開啟位址"]!=null)
						adOrder['素材'][materialOrder]=materialObj;
					}
					//檢查輸入參數
					//檢查版位用參數
					let checkPositions =(positions)=>{
						positions = positions.split(",");
						for(let p of positions){
							
							if(!positionSet.has(parseInt(p)))
							return false;
						}
						return true;
					}
					//檢查委刊單是否存在用
					let checkOrderListExist= function(id){
						let result = false;
						$.ajax({
							type: "POST",
							url: "?",
							data: {action:"checkOrderListExist",委刊單識別碼:id},
							success: (data)=>{
									if(!data.length||data.length==0)
									result = false;
									else
									result= true;
									},
							dataType: "json",
							async :false
						});
						return result;
					}
					//檢查必填資訊
					if(adOrder["版位類型識別碼"]==undefined || adOrder["版位識別碼"]==undefined|| adOrder["託播單名稱"]==undefined || adOrder["廣告期間開始時間"]==undefined|| adOrder["廣告期間結束時間"]==undefined
					|| adOrder["廣告可被播出小時時段"]==undefined){
						row[index]="必要資訊未輸入";
						global_validOrderCheck=false;
					}
					//檢查走期
					else if(adOrder["廣告期間開始時間"]>adOrder["廣告期間結束時間"] || adOrder["預約到期時間"]>adOrder["廣告期間開始時間"]){
						row[index]="走期設定錯誤";
						global_validOrderCheck=false;
					}
					//檢查版位識別碼
					else if(!checkPositions(adOrder["版位識別碼"])){
						row[index]="版位識別碼錯誤";
						global_validOrderCheck=false;
					}
					//若有連結類型對照表，檢查連結類型
					else if(linkSet.size!=0){
						for(let mkey of Object.keys(adOrder["素材"])){
							if(adOrder['素材'][mkey]["點擊後開啟類型"]!=undefined && !linkSet.has(adOrder['素材'][mkey]["點擊後開啟類型"])){
								row[index]="點擊後開啟類型不存在";
								global_validOrderCheck=false;
							}
						}
					}
					else{
						let check = orderListMap[orderListId]||checkOrderListExist(orderListId);
						orderListMap[orderListId]= check;
						if(!check){
							row[index]="委刊單不存在";
							global_validOrderCheck=false;
						}
					}
					
					//加入清單
					if(global_adOrders[orderListId]===undefined){
						global_adOrders[orderListId]=[];
					}
					global_adOrders[orderListId].push(adOrder);
				}
				resultTableVue.originalData = [...global_orderInfoSheetAoa];

			};
			getPositionPara(pId,callback);
		};
		reader.readAsArrayBuffer(file);
	}
	excleFileDom.addEventListener("change", handleFile, false);

	let getHoursStringFromExcelData =(excelHoursData)=>{
		if(!excelHoursData && excelHoursData !== ""){
			return "0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23";
		}
		//將"0;1~5;7"分割為[0,1~5,7]
		let hourSections= String(excelHoursData).split(";");
		let hoursSet = new Set();
		for(section of hourSections){
			//處理1~5這類的連續時段表示方式
			let preiod = section.split("~");
			hoursSet.add(+preiod[0]);
			if(preiod.length > 1 ){
				for(let i = +preiod[0] + 1; i <= +preiod[1]; i++){
					hoursSet.add(i);
					hoursSet.add(+i);
				}
			}
		}
		return Array.from(hoursSet).join(",");
	}
	//匯入excel資料
	$("#submitExcel").click(()=>{
		if(!global_validOrderCheck){
			alert("匯入檔案設定有誤，請修正後再匯入。");
		}
		else{
			$("#submitExcel").attr('disabled', true).mask();
			//儲存
			Promise.all(
				Object.keys(global_adOrders).map(
					(orderListId)=>(new Promise((promise_reslove,promise_reject)=>{
						$.post("ajaxToDB_Order.php",
							{"action":"儲存更變",
							"orders":JSON.stringify(global_adOrders[orderListId]),
							"orderListId":orderListId,
							"edits":{"delete":[],"edit":[]}},
							function(data){
								$result = "";
								if(data["dbError"]!=undefined){
									$result=data["dbError"];
								}
								else{
									$result=data["message"];
								}
								for(let order of global_adOrders[orderListId]){
									let rowId = order["rowId"];
									let resultIndex =global_orderInfoSheetAoa[0].length-1;//利用tiltile 的row的長度判斷執行結果的index
									global_orderInfoSheetAoa[rowId][resultIndex] = $result;
								}
								promise_reslove();
							}
							,'json'
						);
					}))
				)
			).then(()=>{
				//所有託播單新增完成
				alert("匯入完成，請查看匯入結果欄位");
				resultTableVue.originalData = global_orderInfoSheetAoa;
				$("#submitExcel").attr('disabled', false).unmask();
			});
		}
	});

	let exampleTableVue = new Vue({
		el: '#exampleTableVue',
    	data:{
			thead : [],
			tbody : []
	  }
    });

	let resultTableVue = new Vue({
		el: '#resultTableVue',
    	data:{
			originalData :[],
			rows : [],
			positionNameCache : new Map(),
			positionNameWatingForAjaxData : {},
			orderlistNameCache : new Map(),
			orderlistNameWatingForAjaxData : {},

		},
		methods:{
			getTdVaule(rowId,colId) {
				let value = this.originalData[rowId][colId];
				if (value instanceof Date) {
					const year = value.getFullYear();
					const month = String(value.getMonth() + 1).padStart(2, '0');
					const day = String(value.getDate()).padStart(2, '0');
					const hours = String(value.getHours()).padStart(2, '0');
					const minutes = String(value.getMinutes()).padStart(2, '0');
					const seconds = String(value.getSeconds()).padStart(2, '0');
					return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
				}
				else if( colId === 0 ){
					return this.getPositionName(value,rowId,colId);
				}
				else if( colId === 1 ){
					return this.getOrderlistName(value,rowId,colId);
				}
				else {
					return value;
				}
			},
			getPositionName(positionId,rowId,colId){
				if(this.positionNameCache.has(positionId)){
					return this.positionNameCache.get(positionId);
				}

				if(!this.positionNameWatingForAjaxData[positionId]){
					this.positionNameWatingForAjaxData[positionId] = [];
					this.getPositionNameFromAjax(positionId);
				}
				this.positionNameWatingForAjaxData[positionId].push([rowId,colId]);
				
				return "Loading..."
			},
			getPositionNameFromAjax(positionId){
				$.post('../position/positionTypeForm.php',{method:'版位資料',id:positionId},
					(data)=>{
						let positionInfo = data[0];
						this.positionNameCache.set(positionId,positionInfo["版位識別碼"]+":"+positionInfo["版位名稱"]);
						this.rerenderPositionNameUsingDataFromAjax(positionInfo["版位識別碼"]);
					}		
					,'json'
				);
			},
			rerenderPositionNameUsingDataFromAjax(positionId){
				for(let [row,col] of this.positionNameWatingForAjaxData[positionId]){
					this.rows[row][col] = this.positionNameCache.get(positionId);
				}
				this.$forceUpdate();
			},
			getOrderlistName(orderlistId,rowId,colId){
				if(this.orderlistNameCache.has(orderlistId)){
					return this.orderlistNameCache.get(orderlistId);
				}

				if(!this.orderlistNameWatingForAjaxData[orderlistId]){
					this.orderlistNameWatingForAjaxData[orderlistId] = [];
					this.getOrderlistNameFromAjax(orderlistId);
				}
				this.orderlistNameWatingForAjaxData[orderlistId].push([rowId,colId]);
				
				return "Loading..."
			},
			getOrderlistNameFromAjax(orderlistId){
				$.post('ajaxToDB_Order.php',{action:'顯示委刊單資料',委刊單識別碼:orderlistId},
					(data)=>{
						this.orderlistNameCache.set(orderlistId,data["委刊單識別碼"]+":"+data["委刊單名稱"]);
						this.rerenderOrderlistNameUsingDataFromAjax(data["委刊單識別碼"]);
					}		
					,'json'
				);
			},
			rerenderOrderlistNameUsingDataFromAjax(orderlistId){
				for(let [row,col] of this.orderlistNameWatingForAjaxData[orderlistId]){
					this.rows[row][col] = this.orderlistNameCache.get(orderlistId);
				}
				this.$forceUpdate();
			},
		},
		watch:{
			originalData: function(){
				console.log("original data changed");
				let rowNum = this.originalData.length;
				let colNUm = this.originalData[0].length;
				this.rows = Array.from({length:rowNum},()=>(new Array(colNUm)));

				for(let i = 0; i<rowNum; i++){
					for(let j = 0; j<colNUm; j++){
						if(i == 0)//header
							this.rows[i][j] = this.originalData[i][j];
						else{
							this.rows[i][j] = this.getTdVaule(i,j);
						}
					}
				}
				this.$forceUpdate();

			}
		}
    });
</script>
</body>
</html>

