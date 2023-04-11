<?php
/****
 * 利用ecxcle匯入託播單資訊
 * 2022 09 22 若選擇單一平台版位，excle中會加入連結類型的tab供參考
 * 
 */
	include('../tool/auth/authAJAX.php');
	require_once '../tool/phpExtendFunction.php';
	
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
	<script src="../tool/vue/vue.min.js"></script>
	<link rel='stylesheet' type='text/css' href='../external-stylesheet.css'/>
	<script src="../tool/GeneralSanitizer.js"></script>
	<script src="../tool/xlsx.full.min.js"></script>
	<script src="../WebConfig.js"></script>
	<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="../tool/jquery.loadmask.js"></script>
	<style type="text/css">
	</style>
</head>
<body>
<fieldset>
<legend>****注意事項****</legend>
務必輸入「停用時間」欄位，系統會參考此欄位定期清理資料<br>
「啟用時間」、「停用時間」欄位資料須為excel的日期格式，類型(顯示格式)不拘。
不須輸入的欄位留白即可，系統會自動帶入null值。<br>
</fieldset>
<div id="vueApp">
	<fieldset>
	<legend>匯入託播單excel檔案</legend>
	<button id="downloadExample" v-on:click = "hendleDownloadExampleClick()">下載空白Excel範例檔案</button><br>
	表單內容範例:
	<br>
	<table id="exampleTable" class="styledTable" style="color:blue;">
		<thead>
			<tr><th v-for="col in globalThead" :key="col">{{col}}</th></tr>
		</thead>
		<tbody>
			<tr v-for="(row,index) in exampleRows" :key="index">
				<td v-for="col in globalThead">{{row[col]}}</td>
			</tr>
		</tbody>
	</table>
	<br>
	<input type="file" name="excleFileDom" id="excleFileDom" @change = "handleExcelFileUpload" />
	</fieldset>
	<fieldset v-if="importResult.length">
	<legend>匯入/檢查結果</legend>
	<div id="resultBlock">
		<table id="resultTable" class="styledTable">
			<thead>
				<tr><th v-for="col in importResult[0]" :key="col">{{col}}</th></tr>
			</thead>
			<tbody>
				<tr v-for="(row,index) in importResult.slice(1)" :key="index">
					<td v-for="(val) in row" >{{val}}</td>
				</tr>
			</tbody>
		</table>
	</div>
	<br>
	<button id="submitExcel" class="darkButton" v-on:click="handleSubmitClick()">開始匯入</button> 
	<button id="downloadResult" v-on:click="handleDownloadResultClick()">下載執行結果Excel檔案</button>
	</fieldset>
</div>
<script type="text/javascript">
	const extappInterlinkApi = "http://localhost/testing/testing.php";///*****test */
	new Vue({
		el:"#vueApp",
		data:{
			globalThead : ["appid","啟用時間","停止時間","針對用戶群組(多筆用,隔開)","排除用戶群組(多筆用,隔開)","app開啟參數","連結類型","連結內容","返回外部app(0或1)"],//表單title
			importResult:[],
			parameterCheckPass:false,
			ajaxPostInfo:[],
			exampleRows:[
				{
					appid:"test",
					啟用時間:"2023-04-10",
					停止時間:"2023-04-31",
					針對用戶群組:"17",
					排除用戶群組:"",
					app開啟參數:"",
					連結類型:"external",
					連結內容:"http://xxxxxx",
					返回外部app:0,
				}
			]
		},
		methods:{
			hendleDownloadExampleClick(){
				downLoadExcel([this.globalThead],"app連結批次匯入.xlsx");
			},
			//檔案上傳
			handleExcelFileUpload(e){
				var file = e.target.files[0];

				var reader = new FileReader();
				reader.onload = (e)=>{
					var data = e.target.result;
					/* reader.readAsArrayBuffer(file) -> data will be an ArrayBuffer */
					var workbook = XLSX.read(e.target.result);
					//處裡託播單資訊
					let orderInfoSheet = workbook.Sheets["app連結跳轉設定"];
					let appIdInfoSheetAoa = XLSX.utils.sheet_to_json(orderInfoSheet, { header: 1});
					//換算excel日期使用
					function ExcelDateToJSDate(serial) {
						return new Date((serial - (25567 + 1))*86400*1000-32*3600000 );
					}
					//"appid","啟用時間","停止時間","針對用戶群組","排除用戶群組","app開啟參數","連結類型","連結內容","返回外部app"
					let importDataArray = [];
					let parametersCheck = true
					appIdInfoSheetAoa[0].push("執行結果");
					for(let i in appIdInfoSheetAoa){
						if(i == 0)//第一行是title 跳過
							continue;
						let row = appIdInfoSheetAoa[i];
						let orderListId = row[1];
						if(row.length==0)
							continue;
						//日期換算
						row[1]=ExcelDateToJSDate(row[1])//啟用時間
						row[2]=ExcelDateToJSDate(row[2])//停止時間
	
						let importData = {
							"appid":row[0],
							"啟用時間":(row[1]==undefined)?null:formatDate(row[1]),//將區分多筆的;改為系統使用的,
							"停止時間":formatDate(row[2]),
							"針對用戶群組":row[3],
							"排除用戶群組":row[4],
							"app開啟參數":row[5],//若結束時間為00:00:00 置換為當天結束
							"連結類型":row[6],
							"連結內容":row[7],
							"返回外部app":(row[8]==undefined)?null:row[8],
							'rowId':i
						};
						row[9]="";		
						//檢查必填資訊
						if(importData["停止時間"]==undefined ||importData["appid"]==undefined||importData["連結類型"]==undefined||importData["連結內容"]==undefined){
							row[9]="必要資訊未輸入";
							parametersCheck = false;
						}
						//檢查走期
						else if(importData["啟用時間"]!=null&& importData["啟用時間"]>importData["停止時間"] ){
							row[9]="走期設定錯誤";
							parametersCheck = false;
						}
						
						importDataArray.push(importData);
					}
					this.parameterCheckPass=parametersCheck;
					this.importResult = appIdInfoSheetAoa;
					this.ajaxPostInfo = importDataArray;
				};
				reader.readAsArrayBuffer(file);
			},
			handleSubmitClick(e){
				if(!this.parameterCheckPass){
					alert("匯入檔案設定有誤，請修正後再匯入。");
				}
				else{
					$("#submitExcel").attr('disabled', true).mask();
					//儲存
					Promise.all(
						this.ajaxPostInfo.map(
							(postInfo,i)=>(new Promise((promise_reslove,promise_reject)=>{
								$.post(extappInterlinkApi,
									{
										"action":"batchInsert",
										"data":postInfo,
									},
									(data)=>{
										this.importResult[i+1][this.importResult[i].length-1]=data["message"];
										promise_reslove();
									}
									,'json'
								);
							}))
						)
					).then(()=>{
						//所有託播單新增完成
						alert("匯入完成，請查看匯入結果欄位");
						this.importResult = [...this.importResult];
						$("#submitExcel").unmask();
					});
				}
			},
			//下載匯入結果檔案
			handleDownloadResultClick(){		
				downLoadExcel(this.importResult,"aapid連結匯入結果"+formatDate(new Date())+".xlsx");
			}
		}
	});


	function downLoadExcel(orderData,filname){
		/* generate worksheet and workbook */
		const workbook = XLSX.utils.book_new();

		const worksheet = XLSX.utils.aoa_to_sheet(orderData,{cellDates:true});
		XLSX.utils.book_append_sheet(workbook, worksheet, "app連結跳轉設定");
		/* calculate column width */
		//const max_width = global_thead.reduce((w, r) => Math.max(w, r.length*2), 10);
		//worksheet["!cols"] = [ { wch: max_width } ];
		/* create an XLSX file and try to save to .xlsx */
		XLSX.writeFile(workbook, filname);
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
	
</script>
</body>
</html>

