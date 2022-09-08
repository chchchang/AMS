<?php 
	include('../../tool/auth/authAJAX.php');
	include('../../tool/phpExtendFunction.php');
	if(isset($_POST['ajaxAction'])){
		if($_POST['ajaxAction'] == "getPlayListRecord"){
			$sql = "SELECT * FROM `barker_playlist_import_result` WHERE (created_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH) || import_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH))";
			if(isset($_POST["failOnly"])&& $_POST["failOnly"]=="true"){
				$sql .= " AND (import_result != 1 || import_result IS NULL)";
			}
			$sql .=" order by import_time desc";
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$data = array();
			$chNameMap = getBarkerNameMap();
			while($row = $res->fetch_assoc()){
				//channel date hours result message import_time
				preg_match("/(.+)_(.+)\.json/",$row["file_name"],$fileNameMatches);
				$channel = $row["channel_id"].(isset($chNameMap[$row["channel_id"]])?$chNameMap[$row["channel_id"]]["版位名稱"]:"");
				$date = $fileNameMatches[1];
				$hours = $fileNameMatches[2];
				$result = $row["import_result"]==1?"成功":"失敗";
				if($row["import_time"] == null)
					$result = "處理中";
				$message = $row["message"]==null?"":$row["message"];
				$import_time = $row["import_time"]==null?"":$row["import_time"];
				
				$data[]=array(
					"channel"=>$channel,
					"date"=>$date,
					"hours"=>$hours,
					"result"=>$result,
					"message"=>$message,
					"import_time"=>$import_time,
				);
			}
			exit(json_encode(array('success'=> true,"data"=>$data),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['ajaxAction'] == "getMaterialRecord"){
			$sql = "SELECT * FROM `barker_material_import_result` WHERE (created_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH) || import_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH))";
			if(isset($_POST["failOnly"])&& $_POST["failOnly"]=="true"){
				$sql .= " AND (import_result != 1 || import_result IS NULL)";
			}
			$sql .=" order by import_time desc";
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$data = array();
			while($row = $res->fetch_assoc()){
				$result = $row["import_result"]==1?"成功":"失敗";
				if($row["import_time"] == null)
					$result = "處理中";
				$message = $row["message"]==null?"":$row["message"];
				$import_time = $row["import_time"]==null?"":$row["import_time"];
				
				$data[]=array(
					"material_id"=>$row["material_id"],
					"file_name"=>$row["file_name"],
					"result"=>$result,
					"message"=>$message,
					"import_time"=>$import_time,
				);
			}
			exit(json_encode(array('success'=> true,"data"=>$data),JSON_UNESCAPED_UNICODE));
		}

		else if($_POST['ajaxAction'] == "getPlayFailRecord"){
			$sql = "SELECT * FROM `barker_play_fail_log` WHERE (created_time >= DATE_SUB(NOW(),INTERVAL 1 MONTH) )";
			$sql .=" order by created_time desc";
			if(!$stmt=$my->prepare($sql)) {
				exit('無法準備statement，請聯絡系統管理員！');
			}
			if(!$stmt->execute()) {
				exit('無法執行statement，請聯絡系統管理員！');
			}
			if(!$res=$stmt->get_result()) {
				exit('無法取得結果集，請聯絡系統管理員！');
			}
			$data = array();
			$chNameMap = getBarkerNameMap();
			while($row = $res->fetch_assoc()){
				
				$channel = $row["channel_id"].(isset($chNameMap[$row["channel_id"]])?$chNameMap[$row["channel_id"]]["版位名稱"]:"");
				$message = $row["message"]==null?"":$row["message"];
				
				$data[]=array(
					"channel"=>$channel,
					"file_name"=>$row["file_name"],
					"play_time"=>$row["play_time"],
					"transaction_id"=>$row["transaction_id"],
					"message"=>$message,
					
				);
			}
			exit(json_encode(array('success'=> true,"data"=>$data),JSON_UNESCAPED_UNICODE));
		}
	}
	function getBarkerNameMap(){
		global $my;
		$sql = "
		SELECT 版位.版位識別碼,版位名稱,版位其他參數預設值 as channel_id
			FROM 版位 JOIN 版位其他參數 on 版位.版位識別碼 = 版位其他參數.版位識別碼 AND 版位其他參數名稱 = 'channel_id'
			WHERE 上層版位識別碼 in
			(SELECT 版位識別碼 AS 上層板位識別碼
			FROM 版位
			WHERE 版位名稱='barker頻道')
		"
		;

		if(!$stmt=$my->prepare($sql)) {
			exit('無法準備statement，請聯絡系統管理員！');
		}
		if(!$stmt->execute()) {
			exit('無法執行statement，請聯絡系統管理員！');
		}
		if(!$res=$stmt->get_result()) {
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		$data = array();
		while($row = $res->fetch_assoc()){
			$data[$row["channel_id"]] = $row;
		}
		return $data;
	}

?>
<!doctype html>

<html>
<head>
<meta charset="utf-8">
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<script src="../../tool/vue/vue.min.js"></script>
<style type="text/css">
	.pagingDiv{
		list-style: none;
		margin: 0;
		padding: 0;   
		overflow: hidden; /* 超過範圍隱藏 */
		white-space: nowrap; /* 不斷行 */
	}
</style>
</head>
<body>
<div id = "playListVueTable" class="pagingDiv">
	<h3>排播匯入紀錄</h3><br>
	<input type="checkbox" v-model="failonly" @change="getTalbeData"><a>只顯示失敗紀錄</a>
	<table class="styledTable2">	
		<thead>
		<tr><th>頻道</th><th>排播日期</th><th>排播時段</th><th>匯入結果</th><th>訊息</th><th>匯入時間</th></tr>
		</thead>
		<tbody>
			<tr	v-for="row in presentdata" >
				<td>{{row.channel}}</td>
				<td>{{row.date}}</td>
				<td>{{row.hours}}</td>
				<td>{{row.result}}</td>
				<td>{{row.message}}</td>
				<td>{{row.import_time}}</td>
			</tr>
		</tbody>
	</table>
	<div class = "pagingDiv" >
		<button @click="getpagedata(page-1)"> << </button>
			<select  v-model="page">
			<option v-for="option in pagearray" :value="option" >{{option}}</option>
			</select>
		<button @click="getpagedata(page+1)"> >> </button>	
	</div>
</div>

<div id = "materialVueTable" class="pagingDiv">
	<h3>素材匯入紀錄</h3><br>
	<input type="checkbox" v-model="failonly" @change="getTalbeData"><a>只顯示失敗紀錄</a>
	<table class="styledTable2">	
		<thead>
		<tr><th>素材識別碼</th><th>barker上傳檔案名稱</th><th>匯入結果</th><th>訊息</th><th>匯入時間</th></tr>
		</thead>
		<tbody>
			<tr	v-for="row in presentdata" >
				<td>{{row.material_id}}</td>
				<td>{{row.file_name}}</td>
				<td>{{row.result}}</td>
				<td>{{row.message}}</td>
				<td>{{row.import_time}}</td>
			</tr>
		</tbody>
	</table>
	<div class = "pagingDiv" >
		<button @click="getpagedata(page-1)"> << </button>
			<select  v-model="page">
			<option v-for="option in pagearray" :value="option" >{{option}}</option>
			</select>
		<button @click="getpagedata(page+1)"> >> </button>	
	</div>
</div>

<div id = "playFailRecordVueTable" class="pagingDiv">
	<h3>播放失敗紀錄</h3><br>
	<table class="styledTable2">	
		<thead>
		<tr><th>頻道</th><th>檔案名稱</th><th>託播單號</th><th>播放時間</th><th>訊息</th></tr>
		</thead>
		<tbody>
			<tr	v-for="row in presentdata" >
				<td>{{row.channel}}</td>
				<td>{{row.file_name}}</td>
				<td>{{row.transaction_id}}</td>
				<td>{{row.play_time}}</td>
				<td>{{row.message}}</td>
			</tr>
		</tbody>
	</table>
	<div class = "pagingDiv" >
		<button @click="getpagedata(page-1)"> << </button>
			<select  v-model="page">
			<option v-for="option in pagearray" :value="option" >{{option}}</option>
			</select>
		<button @click="getpagedata(page+1)"> >> </button>	
	</div>
</div>

</body>
<script>
	//排播表資料表
	var playListVueTable =new Vue({
        el: '#playListVueTable',
        data:{
			totalpage :0,
			page:0,
			numperpage :20,
			pagearray:[],
            playListRecord:[],//	channel date hours result message import_time
			presentdata:[],
			failonly: false
        }
        ,mounted(){
            this.getTalbeData();
			
        },
        methods:{
			getTalbeData(){
				this.playListRecord = [];
				$.post('?',{ajaxAction:'getPlayListRecord',failOnly:this.failonly}
				,(ajaxdata=>{
					ajaxdata["data"].forEach(
						element => {
							this.playListRecord.push({
								"channel":element["channel"]
								,"date":element["date"]
								,"hours":element["hours"]
								,"result":element["result"]
								,"message":element["message"]
								,"import_time":element["import_time"]
							});
						}
					);
					this.getpage();
					this.addpage();
					this.getpagedata(1);
				})
				,'json'
				);
			},
			getpage() {
				this.totalpage = this.playListRecord.length/this.numperpage +1;
			},
			addpage() {
				for(var i =0;i<this.totalpage;i++){
					this.pagearray[i]=i+1;
				}
				return this.pagearray;
			},
			getpagedata(index){
				if(index>0 && index<=this.totalpage){
					this.page = index;
					this.presentdata = this.playListRecord.slice((this.page-1)*this.numperpage,(this.page)*this.numperpage);
				}
			}
        },
		watch:{
            page: function(val,oldval){
                this.getpagedata(val);
            }
        }

    });

	//素材匯入資料表
	var materialVueTable =new Vue({
        el: '#materialVueTable',
        data:{
			totalpage :0,
			page:0,
			numperpage :20,
			pagearray:[],
            record:[],//	channel date hours result message importTime
			presentdata:[],
			failonly: false
        }
        ,mounted(){
            this.getTalbeData();
			
        },
        methods:{
			getTalbeData(){
				this.record = [];
				$.post('?',{ajaxAction:'getMaterialRecord',failOnly:this.failonly}
				,(ajaxdata=>{
					ajaxdata["data"].forEach(
						element => {
							this.record.push({

								"material_id":element["material_id"]
								,"file_name":element["file_name"]
								,"result":element["result"]
								,"message":element["message"]
								,"import_time":element["import_time"]
							});
						}
					);
					this.getpage();
					this.addpage();
					this.getpagedata(1);
				})
				,'json'
				);
			},
			getpage() {
				this.totalpage = this.record.length/this.numperpage +1;
			},
			addpage() {
				for(var i =0;i<this.totalpage;i++){
					this.pagearray[i]=i+1;
				}
				return this.pagearray;
			},
			getpagedata(index){
				if(index>0 && index<=this.totalpage){
					this.page = index;
					this.presentdata = this.record.slice((this.page-1)*this.numperpage,(this.page)*this.numperpage);
				}
			}
        },
		watch:{
            page: function(val,oldval){
                this.getpagedata(val);
            }
        }

    });

	//素材匯入資料表
	var playFailRecordVueTable =new Vue({
        el: '#playFailRecordVueTable',
        data:{
			totalpage :0,
			page:0,
			numperpage :20,
			pagearray:[],
            record:[],//	channel date hours result message importTime
			presentdata:[],
        }
        ,mounted(){
            this.getTalbeData();
			
        },
        methods:{
			getTalbeData(){
				this.record = [];
				$.post('?',{ajaxAction:'getPlayFailRecord'}
				,(ajaxdata=>{
					ajaxdata["data"].forEach(
						element => {
							this.record.push({

								"channel":element["channel"]
								,"file_name":element["file_name"]
								,"transaction_id":element["transaction_id"]
								,"play_time":element["play_time"]
								,"message":element["message"]
							});
						}
					);
					this.getpage();
					this.addpage();
					this.getpagedata(1);
				})
				,'json'
				);
			},
			getpage() {
				this.totalpage = this.record.length/this.numperpage +1;
			},
			addpage() {
				for(var i =0;i<this.totalpage;i++){
					this.pagearray[i]=i+1;
				}
				return this.pagearray;
			},
			getpagedata(index){
				if(index>0 && index<=this.totalpage){
					this.page = index;
					this.presentdata = this.record.slice((this.page-1)*this.numperpage,(this.page)*this.numperpage);
				}
			}
        },
		watch:{
            page: function(val,oldval){
                this.getpagedata(val);
            }
        }

    });
</script>
</html>