<?php
	
	include('../tool/auth/authAJAX.php');
	include('../Config_VSM_Meta.php');
	require_once '../tool/phpExtendFunction.php';
	if(isset($_POST['postAction'])){
		//取得VASTURL來源資料
		if($_POST['postAction'] == "getVastUrlTable"){
			$sql = "select * from 聯播網廣告來源";
			$res=$my->getResultArray($sql);
			$temp = array();//整理並儲存查詢結果用
			foreach($res as $data){
				//存入資料
				if($data["是否為預設來源"] == 1){
					$defalut = "是";
				}
				else{
					$defalut = "否";
				}
				$temp[]=["聯播網廣告來源識別碼"=>$data["聯播網廣告來源識別碼"],"聯播網廣告URL"=>$data["聯播網廣告URL"],"是否為預設來源"=>$defalut,"聯播網廣告來源名稱"=>$data["聯播網廣告來源名稱"]];
			}
			exit(json_encode(array('success'=>true,"data"=>$temp),JSON_UNESCAPED_UNICODE));
		}
		//新增url
		else if($_POST['postAction'] == "insertUrl"){
			if(!checkRepeat($_POST['data']['newname'])){
				exit(json_encode(array('success'=>false,"message"=>"更新失敗:名稱重複"),JSON_UNESCAPED_UNICODE));
			}
			if(insertUrl($_POST['data']['newurl'],$_POST['data']['newname'])){
				exit(json_encode(array('success'=>true,"message"=>"更新成功"),JSON_UNESCAPED_UNICODE));
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"更新失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		//更新URL
		else if($_POST['postAction'] == "setUrl"){
			/*if(!checkRepeat($_POST['data']['newname'])){
				exit(json_encode(array('success'=>false,"message"=>"更新失敗:名稱重複"),JSON_UNESCAPED_UNICODE));
			}*/
			if(updateUrl($_POST['data']['newurl'],$_POST['data']['newname'],$_POST['data']['urlid'])){
				exit(json_encode(array('success'=>true,"message"=>"更新成功"),JSON_UNESCAPED_UNICODE));
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"更新失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		//設URL為預設
		else if($_POST['postAction'] == "setDefaultUrl"){
			if(setDefaultUrl($_POST['data']['urlid'])){
				exit(json_encode(array('success'=>true,"message"=>"預設來源設定成功"),JSON_UNESCAPED_UNICODE));
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"預設來源設定失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		//取消URL為預設
		else if($_POST['postAction'] == "cancleDefaultUrl"){
			if(cancleDefaultUrl($_POST['data']['urlid'])){
				exit(json_encode(array('success'=>true,"message"=>"取消預設來源設定成功"),JSON_UNESCAPED_UNICODE));
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"取消預設來源設定失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		//刪除URL
		else if($_POST['postAction'] == "deleteUrl"){
			if(deleteUrl($_POST['data']['urlid'])){
				exit(json_encode(array('success'=>true,"message"=>"刪除成功"),JSON_UNESCAPED_UNICODE));
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"刪除失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
		//同步到VSM
		else if($_POST['postAction'] == "syncToVsm"){
			$url = Config_VSM_Meta::GET_SET_VAST_OPTION_API();
			$sql = "select 聯播網廣告URL,聯播網廣告來源識別碼,是否為預設來源 from 聯播網廣告來源 WHERE 1";
			$urls=$my->getResultArray($sql);
			$source = array();
			foreach($urls as $row){
				$source[] = array("vast_url_id"=>$row["聯播網廣告來源識別碼"],"url"=>$row["聯播網廣告URL"],"default_flag"=>$row["是否為預設來源"]);
			}
			$bypost = array("action"=>"setVastUrl","data"=>$source);
			$postvars = http_build_query($bypost);
			$res = PHPExtendFunction::connec_to_Api($url,'POST',$postvars);
			if($res["success"]){
				exit($res["data"]);
			}
			else{
				exit(json_encode(array('success'=>false,"message"=>"單一平台API連接失敗"),JSON_UNESCAPED_UNICODE));
			}
		}
	}
	//檢查URL是否重複使用
	function checkRepeat($newname){
		global $my;
		$sql = "select COUNT(*) as C from 聯播網廣告來源 where 聯播網廣告來源名稱 = ?";
		$res=$my->getResultArray($sql,"s",$newname);
		if($res[0]["C"]!=0){
			return false;
		}
		else{
			return true;
		}
	}
	//新增URL
	function insertUrl($newurl,$newname){
		global $my;
		$my->begin_transaction();
		$sql = "insert into 聯播網廣告來源 (聯播網廣告URL,聯播網廣告來源名稱,CREATED_PEOPLE) value (?,?,?)";
		if(!$my->execute($sql,"ssi",$newurl,$newname,$_SESSION['AMS']['使用者識別碼'])){
			$my->rollback();
			return false;
		}
		$my->commit();
		return true;
	}
	//更新URL
	function updateUrl($newurl,$newname,$urlid){
		global $my;
		$my->begin_transaction();
		$sql = "update 聯播網廣告來源 set 聯播網廣告URL =?,聯播網廣告來源名稱=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP where 聯播網廣告來源識別碼 = ?";
		if(!$my->execute($sql,"ssii",$newurl,$newname,$_SESSION['AMS']['使用者識別碼'],$urlid)){
			$my->rollback();
			return false;
		}
		$my->commit();
		return true;
	}
	//設定URL為預設
	function setDefaultUrl($urlid){
		global $my;
		$my->begin_transaction();
		//先將所有的廣告來源設為非預設
		/*$sql = "update 聯播網廣告來源 set 是否為預設來源 = 0,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP";
		if(!$my->execute($sql,"i",$_SESSION['AMS']['使用者識別碼'])){
			$my->rollback();
			return false;
		}*/
		//將指定廣告來源設為預設
		$sql = "update 聯播網廣告來源 set 是否為預設來源 = 1,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP where 聯播網廣告來源識別碼 = ?";
		if(!$my->execute($sql,"ii",$_SESSION['AMS']['使用者識別碼'],$urlid)){
			$my->rollback();
			return false;
		}
		$my->commit();
		return true;
	}
	//取消URL為預設
	function cancleDefaultUrl($urlid){
		global $my;
		$my->begin_transaction();
		//將指定廣告來源設為預設
		$sql = "update 聯播網廣告來源 set 是否為預設來源 = 0,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP where 聯播網廣告來源識別碼 = ?";
		if(!$my->execute($sql,"ii",$_SESSION['AMS']['使用者識別碼'],$urlid)){
			$my->rollback();
			return false;
		}
		$my->commit();
		return true;
	}
	//刪除UR
	function deleteUrl($urlid){
		global $my;
		$my->begin_transaction();
		//刪除廣告來源
		$sql = "delete from 聯播網廣告來源 where 聯播網廣告來源識別碼 = ?";
		if(!$my->execute($sql,"i",$urlid)){
			$my->rollback();
			return false;
		}
		$my->commit();
		return true;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
	<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
	<script src="../tool/jquery-ui1.2/jquery-ui.min.js"></script>
	<script src="../tool/HtmlSanitizer.js"></script>
	<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.form.js"></script> 
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<style type="text/css">
		#epglimittable,#epglimittable * * *{
			border: 1px solid #5599FF
		}
		th{
			background-color: #4169E1;
			color: white;
		}
		table {
			border-collapse:separate;
			width:100%
		}
		td{
			min-width: 60px;
		}
		#syncToVsmBtn{
			float: right;
		}
	</style>
</head>
<body>
<button id="syncToVsmBtn">同步至單一平台</button>
<fieldset>
<legend>新增VAST廣告來源</legend>
<table id = "addurltable">
<thead><tr><th>URL</th><th>聯播網廣告來源名稱</th><th></th></tr></thead>
<tbody><tr><td><input id = "newurltext" type="text"></input></td><td><input id = "newurlname" type="text"></input></td><td><button id = "newurlbtn">新增</button></td></tr></tbody>
</table>
</fieldset>

<fieldset>
<legend>投放上限資料表</legend>
<table id = "epglimittable" width="100%">
<thead><tr><th>廣告來源識別碼</th><th>URL</th><th>聯播網廣告來源名稱</th><th>是否為預設廣告來源</th><th>設為預設來源</th><th>修改URL</th><th>刪除</th></tr></thead>
<tbody id = "soursetablebody"><tbody>
</table>
</fieldset>

<script type="text/javascript">
refreshtable()
function refreshtable(){
	$.post("?",{"postAction":"getVastUrlTable"},
		function(result){
			if(result["success"]){
				data = result["data"];
				//清空table資料
				$("#soursetablebody").empty();
				//更新table資料
				for(var i =0;i<data.length;i++){
					tr = $(document.createElement('tr'));
					tr.append("<td>"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"</td>"
					+"<td><input id='url_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' value='"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告URL"])+"' disabled></input></td>"
					+"<td><input id='name_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' value='"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源名稱"])+"' disabled></input></td>"
					+"<td>"+HtmlSanitizer.SanitizeHtml(data[i]["是否為預設來源"])+"</input></td>"
					+"<td><button id='setdefault_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' class='urlDefaultBtn' urlid = '"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' >設為預設來源</button>"
					+"<button id='cancledefault_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' class='urlCancleDefaultBtn' urlid = '"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' >取消預設來源</button></td>"
					+"<td><button id='edit_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' class='urlEditBtn' urlid = '"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' >修改</button>"
					+"<button id='submit_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' class='urlSumbitBtn' urlid = '"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' >提交</button></td>"
					+"<td><button id='delete_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' class='urlDeletBtn' urlid = '"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])+"' >刪除</button></td>"
					);
					if(i%2 == 1){
						tr.css({"background-color": "#F0F8FF"});
					}
					$("#soursetablebody").append(tr);
					if(data[i]["是否為預設來源"]=="是"){
						$("#setdefault_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])).hide();
						$("#cancledefault_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])).show();
					}
					else{
						$("#setdefault_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])).show();
						$("#cancledefault_"+HtmlSanitizer.SanitizeHtml(data[i]["聯播網廣告來源識別碼"])).hide();
					}
				}
				//設定設為預設按鈕動作
				$(".urlDefaultBtn").click(function(){
					//if(confirm("只允許一個預設廣告來源，舊的預設廣告來源將會被取消。確定將此來源設為新的預設廣告來源?")){
						var urlid = $(this).attr("urlid");
						//ajax設定預設廣告
						$.post("?",{"postAction":"setDefaultUrl","data":{urlid:urlid}},
							function(feedback){
								if(feedback["success"]){
									refreshtable();
								}
								alert(feedback["message"]);
							}
							,"json"
						)
					//}
				});
				//取消設為預設按鈕動作
				$(".urlCancleDefaultBtn").click(function(){
					//if(confirm("只允許一個預設廣告來源，舊的預設廣告來源將會被取消。確定將此來源設為新的預設廣告來源?")){
						var urlid = $(this).attr("urlid");
						//ajax設定預設廣告
						$.post("?",{"postAction":"cancleDefaultUrl","data":{urlid:urlid}},
							function(feedback){
								if(feedback["success"]){
									refreshtable();
								}
								alert(feedback["message"]);
							}
							,"json"
						)
					//}
				});
				
				//設定修改按鈕動作
				$(".urlEditBtn").click(function(){
					var urlid = $(this).attr("urlid");
					//UI更變
					$(this).hide();
					$("#url_"+urlid).prop("disabled",false);
					$("#name_"+urlid).prop("disabled",false);
					$("#submit_"+urlid).show();
				});
				//設定提交按鈕動作
				$(".urlSumbitBtn").click(function(){
					var urlid = $(this).attr("urlid");
					var newurl = $("#url_"+urlid).val();
					var newname = $("#name_"+urlid).val();
					
					//ajax更新投放上限
					$.post("?",{"postAction":"setUrl","data":{newurl:newurl,newname:newname,urlid:urlid}},
						function(feedback){
							if(feedback["success"]){
								//UI更變
								$("#submit_"+urlid).hide();
								$("#url_"+urlid).prop("disabled",true);
								$("#name_"+urlid).prop("disabled",true);
								$("#edit_"+urlid).show();
								refreshtable();
							}
							alert(feedback["message"]);
						}
						,"json"
					)
					
				}).hide();
				//設定刪除按鈕動作
				$(".urlDeletBtn").click(function(){
					if(confirm("刪除後將無法復原，確定要刪除此廣告來源URL?")){
						var urlid = $(this).attr("urlid");
						$.post("?",{"postAction":"deleteUrl","data":{urlid:urlid}},
							function(feedback){
								if(feedback["success"]){
									refreshtable();
								}
								alert(feedback["message"]);
							}
							,"json"
						)
					}
					
				});
			}
			else{
				alert(result["message"]);
			}
		},
		"json"
	);
}

//新增VAST廣告來源
$("#newurlbtn").click(function(){	
	var newurltext = $("#newurltext").val();
	var newname = $("#newurlname").val();
	if(newurltext == "" || newurltext==null){
		alert("url為空");
	}else if(newname == "" || newname==null){
		alert("名稱為空");
	}
	
	else{
		$.post("?",{"postAction":"insertUrl","data":{newurl:newurltext,newname:newname}},
			function(feedback){
				if(feedback["success"]){
					refreshtable();
				}
				alert(feedback["message"]);
			}
			,"json"
		)
	}
});

//同步到單一平台
$("#syncToVsmBtn").click(
function(){
	$.post("?",{"postAction":"syncToVsm"},
	function(feedback){
		alert(feedback["message"]);
	},
	"json"
	)
});
</script>
</body>
</html>
