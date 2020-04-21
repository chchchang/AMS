<?php
	require '../tool/auth/auth.php';
	define('PAGE_SIZE',10);
	define('MATERIALPATH',Config::GET_MATERIAL_FOLDER());
	
	if(isset($_POST['action'])){
		//取得資料表資料
		if($_POST['action']==='getMateral'){
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			$searchBy='%'.((isset($_POST['searchBy']))?$_POST['searchBy']:'').'%';
			if(isset($_POST['開始時間']))
				$startDate=($_POST['開始時間']=='')?'0000-00-00':$_POST['開始時間'].' 00:00:00';
			else
				$startDate='0000-00-00';
			if(isset($_POST['結束時間']))
				$endDate=($_POST['結束時間']=='')?'9999-12-31':$_POST['結束時間'].' 23:59:59';
			else
				$endDate='9999-12-31';
			if(isset($_POST['素材群組識別碼']))
				$materialGroup=($_POST['素材群組識別碼']=='0'||$_POST['素材群組識別碼']==null)?'%':$_POST['素材群組識別碼'];
			else
				$materialGroup='%';
			if(isset($_POST['僅顯示未派送']))
				$unCimmitOnly=$_POST['僅顯示未派送'];
			else
				$unCimmitOnly='false';
			if(isset($_POST['僅顯示未取得編號']))
				$unNumberOnly=$_POST['僅顯示未取得編號'];
			else
				$unNumberOnly='false';
			//若有設定CAMPS派送時間，將以下falg設為true並在sql中加入相關參數
			$CAMPSTimeFlag = false;
			if(isset($_POST['CAMPS開始時間'])&&$_POST['CAMPS開始時間']!=''){
				$startDateCAMPS=$_POST['CAMPS開始時間'].' 00:00:00';
				$CAMPSTimeFlag = true;
			}else
				$startDateCAMPS='0000-00-00';
			if(isset($_POST['CAMPS結束時間'])&&$_POST['CAMPS結束時間']!=''){
				$endDateCAMPS=$_POST['CAMPS結束時間'].' 23:59:59';
				$CAMPSTimeFlag = true;
			}else
				$endDateCAMPS='9999-12-31';
			
			switch($_POST['素材是否已到']){
				case '僅顯示素材未到項目':
					$fileUploadOrNot=' AND (素材原始檔名 IS NULL OR 素材原始檔名="") ';
				break;
				case '僅顯示素材已到項目':
					$fileUploadOrNot=' AND (素材原始檔名 IS NOT NULL AND 素材原始檔名!="") ';
				break;
				default:
					$fileUploadOrNot='';
				break;
			}
			$showAll = (isset($_POST['顯示全部']) && $_POST['顯示全部'])?true:false;
			//取得總筆數
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
				AND ((CAMPS影片派送時間 BETWEEN ? AND ?)'.($CAMPSTimeFlag?'':'OR CAMPS影片派送時間 IS NULL').')
				'.($unCimmitOnly=='true'?'AND ( CAMPS影片派送時間 IS NULL )':'').'
				'.($unNumberOnly=='true'?'AND ((CAMPS影片媒體編號 IS NULL || CAMPS影片媒體編號 = ""))':'')
				.$fileUploadOrNot
				,'sssssssssssssss'
				,$materialGroup
				,$startDate,$endDate,$startDate,$endDate,$startDate,$endDate,$startDate
				,$searchBy,$searchBy,$searchBy,$searchBy,$searchBy,$startDateCAMPS,$endDateCAMPS
				);			
			$totalRowCount=$result[0]['COUNT'];
			//取得資料
			$DG_header=array('素材識別碼','素材名稱','素材說明','素材原始檔名','影片素材秒數','CAMPS影片派送時間','CAMPS影片媒體編號');
			//取得資料
			$sql = 'SELECT '.join($DG_header,',').' FROM 素材 
			WHERE 素材類型識別碼=3	
				AND 素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? OR CAMPS影片媒體編號 LIKE ?)
				AND ((CAMPS影片派送時間 BETWEEN ? AND ?)'.($CAMPSTimeFlag?'':'OR CAMPS影片派送時間 IS NULL').')
				'.($unCimmitOnly=='true'?'AND ( CAMPS影片派送時間 IS NULL )':'').'
				'.($unNumberOnly=='true'?'AND ((CAMPS影片媒體編號 IS NULL || CAMPS影片媒體編號 = ""))':'')
				.$fileUploadOrNot.'
				ORDER BY '.((isset($_POST['order']))?$_POST['order']:'素材識別碼').' '.$_POST['asc'].
				($showAll?'':(' LIMIT ?, '.PAGE_SIZE));
			$defString = 'sssssssssssssss'.($showAll?'':'i');
			$a_params =[&$sql,&$defString,&$materialGroup,&$startDate,&$endDate,&$startDate,&$endDate,&$startDate,&$endDate,&$startDate,&$searchBy,&$searchBy,&$searchBy,&$searchBy,&$searchBy,&$startDateCAMPS,&$endDateCAMPS];
			if(!$showAll)
				$a_params[] =&$fromRowNo;
			$result=call_user_func_array(array($my, 'getResultArray'), $a_params);
			$DG_header=array_merge($DG_header,array('取得結果','派送影片','刪除遠端影片','執行結果'));
			$DG_body=array();
			foreach($result as $row){
				$DG_body[]=array(array($row['素材識別碼']),array($row['素材名稱']),array($row['素材說明']),array($row['素材原始檔名']),array($row['影片素材秒數']),array($row['CAMPS影片派送時間'])
				,array($row['CAMPS影片媒體編號']),array('取得結果','button'),array('派送影片','button'),array('刪除遠端影片','button'),array(""));
			}
			header('Content-Type: application/json');
			exit(json_encode(array('pageNo'=>$showAll?1:(($fromRowNo/PAGE_SIZE)+1),'maxPageNo'=>$showAll?1:ceil($totalRowCount/PAGE_SIZE),'allCount'=>$totalRowCount,
				'header'=>$DG_header,'sortable'=>array('素材識別碼','素材名稱','素材說明','素材原始檔名'),'body'=>$DG_body)));
		}
		//更新狀態
		else if(($_POST['action']==='getAndPutStatus')&&isset($_POST['素材識別碼'])&&isset($_POST['素材原始檔名'])){
			$materialUrl=Config::$CAMPS_API['material'];
			$local=MATERIALPATH.$_POST['素材識別碼'].'.'.$_POST['副檔名'];
			if(($md5_result=md5_file($local))===false){
				$json=array('success'=>false,'error'=>'計算檔案md5值失敗！');
				header('Content-Type: application/json');
				exit(json_encode($json));
			}
			$remoteFileName='_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
			//$remoteFileName='_____AMS_'.$_POST['素材識別碼'].'_'.$_POST['素材原始檔名'];
			$url = $materialUrl.'?file_name='.$remoteFileName;
			//$url = 'localhost/AMS/test.php';
			//取得CAMPS影片媒體編號
			$ch=curl_init($url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$getResult = json_decode(curl_exec($ch),true);
			$mcheck=false;
			if(count($getResult)>0){
				foreach($getResult as $id=>$mvalue){
					if($mvalue['status']=='ready' && $mvalue['md5']==$md5_result){
						$mcheck=true;
						//再更新到資料庫
						$mediaId = $mvalue['material_id'];
						$runtime = $mvalue['run_time'];
						$my=new MyDB(true);
						$sql='UPDATE 素材 SET CAMPS影片媒體編號=?,影片素材秒數=? WHERE 素材識別碼=?';
						if(
							($stmt=$my->prepare($sql))
							&&($stmt->bind_param('sii',$mediaId,$runtime,$_POST['素材識別碼']))
							&&($stmt->execute())
						){
							//更新成功才回傳狀態
							$json=json_encode(array('success'=>true,'mediaId'=>$mediaId));
						}
						else{
							//更新失敗只回傳失敗
							$json=json_encode(array('success'=>false,'error'=>'CAMPS影片媒體代碼更新失敗'));
						}
						break;
					}
				}
			}
			if(!$mcheck){
				//未取得CAMPS媒體識別碼，檢查是否仍在上傳目錄處理中
				require '../tool/FTP.php';
				$server=Config::$FTP_SERVERS['CAMPS_MATERIAL'][0];
				//$remote=$server['上傳目錄'].'_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
				$remote=$server['上傳目錄'].$remoteFileName;
				$result=FTP::isFile($server['host'],$server['username'],$server['password'],$remote);
				if($result){
					//檔案在上傳目錄中，回傳等待處理
					$json=json_encode(array('success'=>true,'mediaId'=>'請等待CAMPS處理'));
				}
				else
				{
					//沒有在上傳目錄中，回傳失敗
					$json=json_encode(array('success'=>true,'mediaId'=>''));
				}
			}
			header('Content-Type: application/json; charset=utf-8');
			exit($json);
		}
		//派送素材
		else if(($_POST['action']==='uploadCF')&&isset($_POST['素材識別碼'])&&isset($_POST['副檔名'])&&isset($_POST['素材原始檔名'])){
			$server=Config::$FTP_SERVERS['CAMPS_MATERIAL'][0];
			
			$local=MATERIALPATH.$_POST['素材識別碼'].'.'.$_POST['副檔名'];
			if(($md5_result=md5_file($local))===false){
				$json=array('success'=>false,'error'=>'計算檔案md5值失敗！');
				header('Content-Type: application/json');
				exit(json_encode($json));
			}
			$remote=$server['上傳目錄'].'_____AMS_'.$_POST['素材識別碼'].'_'.$md5_result.'.'.$_POST['副檔名'];
			//$remote=$server['上傳目錄'].'_____AMS_'.$_POST['素材識別碼'].'_'.$_POST['素材原始檔名'];
			
			require '../tool/FTP.php';
			$result=FTP::putAndRename($server['host'],$server['username'],$server['password'],$local,$remote,$remote.'.temp');
			if(!$result)
				$json=array('success'=>false,'error'=>'上傳檔案失敗！');
			else{
				$my=new MyDB(true);
				$sql='UPDATE 素材 SET CAMPS影片派送時間=?,LAST_UPDATE_TIME=?,LAST_UPDATE_PEOPLE=? WHERE 素材識別碼=?';
				$CAMPS影片派送時間=date('Y-m-d H:i:s');
				if(
					($stmt=$my->prepare($sql))
					&&($stmt->bind_param('ssii',$CAMPS影片派送時間,$CAMPS影片派送時間,$_SESSION['AMS']['使用者識別碼'],$_POST['素材識別碼']))
					&&($stmt->execute())
				){
					$json=array('success'=>true,'CAMPS影片派送時間'=>$CAMPS影片派送時間);
				}
				else{
					$json=array('success'=>false,'error'=>'更新派送狀態失敗！');
				}
			}
			header('Content-Type: application/json');
			$json['$remote']=$remote;
			exit(json_encode($json));
		}
		//刪除遠端素材deleteRemote
		else if(($_POST['action']==='deleteRemote')&&isset($_POST['素材識別碼'])){
			$statusCode = deleteRemote($_POST['素材識別碼']);
			$feedback = array('success'=>false,'message'=>'連接CAMPS刪除素材API失敗');
			if($statusCode == 200){
				//再利用API查詢一次，第二次查詢orbit找不到資料才算刪除成功
				$doublecheck = deleteRemote($_POST['素材識別碼']);
				if($doublecheck == 405){
					//更新DB，在素材說明欄位加註"Orbit影片以刪除"
					$my=new MyDB(true);
					$sql='UPDATE 素材 SET 素材說明=CONCAT(素材說明,"Orbit影片以刪除"),LAST_UPDATE_TIME=?,LAST_UPDATE_PEOPLE=? WHERE 素材識別碼=?';
					$time=date('Y-m-d H:i:s');
					if(
						($stmt=$my->prepare($sql))
						&&($stmt->bind_param('sii',$time,$_SESSION['AMS']['使用者識別碼'],$_POST['素材識別碼']))
						&&($stmt->execute())
					){
						$feedback = array('success'=>true,'message'=>getDeleteRemoteResultCodeMapping(200));
					}
					else{
						$json=array('success'=>false,'message'=>'更新派送狀態失敗！');
					}
				}
				else
					$feedback = array('success'=>false,'message'=>getDeleteRemoteResultCodeMapping($doublecheck));
			}
			else
				$feedback = array('success'=>false,'message'=>getDeleteRemoteResultCodeMapping($statusCode));
			
			exit(json_encode($feedback));
		}
	}
	
	function deleteRemote($mid){
		$api=Config::$CAMPS_API['delete_remote_material'];
		$url = $api.$mid;
		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$getResult = json_decode(curl_exec($ch),true);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		return $statusCode;
	}
	function getDeleteRemoteResultCodeMapping($code){
		switch($code){
			case 500:
				$feedback = 'API參數錯誤';
				break;
			case 404:
				$feedback = 'CAMPS無對應的檔案紀錄';
				break;
			case 405:
				$feedback = 'Orbit中已無此檔案';
				break;
			case 200:
				$feedback = '檔案成功從Orbit刪除';
				break;
			case 406:
				$feedback = 'API端流程中發生未知錯誤';
				break;
			default :
				$feedback = "";
		}
		return $feedback;
	}
?>
<!doctype html>
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
<?php include('_searchMaterialUI.php'); ?>
<input type="checkbox" id="僅顯示尚未派送項目">僅顯示未派送項目 <input type="checkbox" id="僅顯示未取得媒體編號項目">僅顯示未取得媒體編號項目 &nbsp;
<input type="radio" name="素材是否已到" value="顯示素材已到與未到項目" checked>僅顯示素材已到與未到項目
<input type="radio" name="素材是否已到" value="僅顯示素材未到項目">僅顯示素材未到項目
<input type="radio" name="素材是否已到" value="僅顯示素材已到項目">僅顯示素材已到項目
<div id = 'showAllDiv'>
<input type="checkbox" id="showAll">顯示全部<a id = "allCount"></a>筆資料(若資料量過大不建議使用)
</div>
<div id="DG"></div>
<script>
$("#_searchMUI_materialTypeSelectoin,#_searchMUI_missingFileOnly,#_searchMUI_missingFiletext").hide();
</script>

<script>
$(document).ready(function(){
	$( "#_searchMUI_tabs_nav-CAMPS_date" ).show();
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
		
	var buttonOnClick=function(event){
		function getColByName(colName){
			return $('#DG td:nth-child('+($('#DG th:contains("'+colName+'")')[0].cellIndex+1)+')')[event.target.parentElement.parentElement.rowIndex-1];
		}
		function getColValueByName(colName){
			return getColByName(colName).textContent;
		}
		
		var buttonName=event.target.textContent;	//先記下來避加上mask之後取得的值被加上後序字串
		
		var value素材識別碼=getColValueByName('素材識別碼');
		var value素材原始檔名=getColValueByName('素材原始檔名');
		var value影片媒體編號=getColValueByName('CAMPS影片媒體編號');
		var node影片派送時間=getColByName('CAMPS影片派送時間');
		var node影片媒體編號=getColByName('CAMPS影片媒體編號');
		var node執行結果=getColByName('執行結果');
		
		$(event.target).mask('處理中...');
		$(node影片派送時間).mask('處理中...');
		$(node影片媒體編號).mask('處理中...');
		
		if(buttonName==='刪除遠端影片'){
			$.post(null,{action:'deleteRemote',素材識別碼:value素材識別碼},function(json){
					node執行結果.innerHTML=json.message;
					$(event.target).unmask();
					$(node影片派送時間).unmask();
					$(node影片媒體編號).unmask();
				},'json');
		}
		else{
			//無論是取得結果或是派送影片皆須先取得是否有已送出的託播單使用該素材以便提醒
			$.post('deliverMaterialCF.php',{action:'getReorders',素材識別碼:value素材識別碼},function(getReordersJson){
				var showReordersAlert=function(json){
					if(json){
						msg="注意：下列託播單已送出但使用到的是舊的影片素材，請等待派片成功之後，再將這些託播單先取消送出並且再次送出後才會生效。\n\n託播單識別碼,託播單名稱\n";
						for(var i in json)
							msg+=json[i].託播單識別碼+','+json[i].託播單名稱+"\n";
						alert(msg);
					}
				};
				//無論是取得結果或是派送影片皆須先取得狀態(取得狀態蘊含更新狀態)
				副檔名=value素材原始檔名.substr(value素材原始檔名.lastIndexOf('.')+1);
				$.post(null,{action:'getAndPutStatus',素材識別碼:value素材識別碼,副檔名:副檔名,素材原始檔名:value素材原始檔名},function(json){
					if(!json.success)
						node執行結果.innerHTML=json.error;
					else{
						if(json.mediaId===''){
							node影片媒體編號.innerHTML=json.mediaId;
							if(buttonName==='取得結果') node執行結果.innerHTML='查無資料，請重新派送影片。';
							if(buttonName==='派送影片'){
								$.post(null,{action:'uploadCF',素材識別碼:value素材識別碼,副檔名:副檔名,素材原始檔名:value素材原始檔名},function(json){
									if(!json.success)
										node執行結果.innerHTML=json.error;
									else{
										node影片派送時間.innerHTML=json.CAMPS影片派送時間;
										node執行結果.innerHTML='上傳影片成功，請等待CAMPS處理影片。';
										//上傳成功後，若mediaId原先不為空表示重覆派送，則進行提醒重送已送出託播單。
										if(value影片媒體編號!=='') showReordersAlert(getReordersJson);
									}
								},'json');
							}
						}
						else{
							node影片媒體編號.innerHTML=json.mediaId;
							if(buttonName==='派送影片') node執行結果.innerHTML='已派送，請檢視各欄位結果，不可重覆派送！';
							//取得結果成功後，若mediaId原先不為空且新的mediaId不同於原先的值，則表示重覆派送需進行提醒重送已送出託播單。
							if(buttonName==='取得結果'&&value影片媒體編號!==''&&value影片媒體編號.search(json.mediaId)==-1) showReordersAlert(getReordersJson);
						}
					}
					$(event.target).unmask();
					$(node影片派送時間).unmask();
					$(node影片媒體編號).unmask();
				},'json');
			},'json');
		}
	}
	
	$('#showAll').click(function(){
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
			,"CAMPS開始時間":$("#_searchMUI_startDate_CAMPS").val()
			,"CAMPS結束時間":$("#_searchMUI_endDate_CAMPS").val()
			,"僅顯示未派送":$('#僅顯示尚未派送項目').prop('checked')
			,"僅顯示未取得編號":$('#僅顯示未取得媒體編號項目').prop('checked')
			,"素材是否已到":$("input[name=素材是否已到]:checked").val()
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
			$('#DG').prepend('<button id="getAll">取得結果</button><button id="putAll">派送影片</button><button id="remoteDeletAll">刪除遠端影片</button>')
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
			
			$('#remoteDeletAll').click(function(event){
				$(this).mask('...');
				var selected=$('#DG tr').has('input[type=checkbox]:checked').children('td').children('button:contains("刪除遠端影片")');
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
			$('#DG button').not('#getAll,#putAll').click(buttonOnClick);
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
</script>
</head>

</body>