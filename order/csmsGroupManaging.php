<?php
	include('../tool/auth/auth.php');
	define('PAGE_SIZE',10);
	if(isset($_POST['method'])){
		//取得全部SEPG頻道
		if($_POST['method'] == 'getAllSepgCh'){
			$sql ='
			SELECT 版位.版位名稱 ,版位.版位識別碼
			FROM 版位,版位 版位類型
			WHERE 版位.上層版位識別碼 = 版位類型.版位識別碼 AND 版位類型.版位名稱 = "頻道short EPG banner"
			';
			$result=$my->getResultArray($sql);
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		//取得CSMS群組中的頻道
		else if($_POST['method'] == 'getCSMSCh'){
			$sql ='SELECT 版位.版位名稱 ,版位.版位識別碼
			FROM 版位, 託播單
			WHERE 版位.版位識別碼 = 託播單.版位識別碼 AND 託播單CSMS群組識別碼 = ? AND 託播單狀態識別碼 IN (0,1,2,3,4)
			';
			$result=$my->getResultArray($sql,'i',$_POST['CSMSID']);
			exit(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		//調整CSMS群組
		else if($_POST['method'] == 'editCsmsGroup'){
			//取得CSMS群組下全部版位
			$sql ='
			SELECT 版位識別碼
			FROM 託播單
			WHERE 託播單CSMS群組識別碼 = ? AND 託播單狀態識別碼 IN (0,1,2,3,4)
			';
			$result=$my->getResultArray($sql,'i',$_POST['託播單CSMS群組識別碼']);
			$csmsGroup;
			foreach($result as $row){
				$csmsGroup[]=$row['版位識別碼'];
			}

			//取得目前資料庫中群組的頻道與更便後群組的頻道之交集
			$intersect=array_intersect($csmsGroup,$_POST['newCsmsGroup']);
			//要增加的頻道
			$insertPosition = array_diff($_POST['newCsmsGroup'],$intersect);
			//要移除的頻道
			$deletePosition = array_diff($csmsGroup,$intersect);
			
			$my->begin_transaction();
			//新增頻道
			if(count($insertPosition)!=0){
				foreach($insertPosition as $key=>$pid){
					//取得版位區域
					$sql ='SELECT 版位名稱 FROM 版位 WHERE 版位識別碼 = ?';
					$res = $my->getResultArray($sql,'i',$pid);
					$area = explode('_',$res[0]['版位名稱']);
					$area = $area[count($area)-1]; 
					//取得同區域非凍結的託播單資訊
					$sql ='SELECT 託播單識別碼,委刊單識別碼,託播單.版位識別碼,託播單狀態識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價
						FROM 託播單,版位
						WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE ? AND 託播單狀態識別碼 != 5
						';
					$res = $my->getResultArray($sql,'is',$_POST['託播單CSMS群組識別碼'],'%_'.$area);
					//若沒有同區域的託播單，使用同群組中任一張非凍結的託播單
					if($res == null){
						$sql ='SELECT 託播單識別碼,委刊單識別碼,託播單.版位識別碼,託播單狀態識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價
							FROM 託播單,版位
							WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 託播單CSMS群組識別碼 = ? AND 託播單狀態識別碼 != 5
							';
						$res = $my->getResultArray($sql,'i',$_POST['託播單CSMS群組識別碼']);
					}
					//沒有非凍結的託播單，使用同區域的凍結託播單
					if($res == null){
						$sql ='SELECT 託播單識別碼,委刊單識別碼,託播單.版位識別碼,託播單狀態識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價
						FROM 託播單,版位
						WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 託播單CSMS群組識別碼 = ? AND 版位名稱 LIKE ? AND 託播單狀態識別碼 = 5
							';
						$res = $my->getResultArray($sql,'i',$_POST['託播單CSMS群組識別碼']);
					}
					//沒有同區域的凍結託播單，使用認一區域的凍結託播單
					if($res == null){
						$sql ='SELECT 託播單識別碼,委刊單識別碼,託播單.版位識別碼,託播單狀態識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段,預約到期時間,售價
							FROM 託播單,版位
							WHERE 託播單.版位識別碼 = 版位.版位識別碼 AND 託播單CSMS群組識別碼 = ? AND 託播單狀態識別碼 = 5
							';
						$res = $my->getResultArray($sql,'i',$_POST['託播單CSMS群組識別碼']);
					}
					$order = $res[0];
					//複製託播單資訊
					$order['託播單狀態識別碼']=isset($order['託播單狀態識別碼'])?$order['託播單狀態識別碼']:0;
					$sql = 'INSERT INTO 託播單 (託播單CSMS群組識別碼,託播單狀態識別碼,委刊單識別碼,版位識別碼,託播單名稱,託播單說明,廣告期間開始時間,廣告期間結束時間,廣告可被播出小時時段
					,預約到期時間,售價,CREATED_PEOPLE) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
					if(!$stmt=$my->prepare($sql)){
						$my->rollback();
						exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->bind_param('iiiissssssii',$_POST['託播單CSMS群組識別碼'],$order['託播單狀態識別碼'],$order['委刊單識別碼'],$pid,$order['託播單名稱'],$order['託播單說明']
					,$order['廣告期間開始時間'],$order['廣告期間結束時間'],$order['廣告可被播出小時時段'],$order['預約到期時間'],$order['售價'],$_SESSION['AMS']['使用者識別碼'])){
						$my->rollback();
						exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
					}
					if(!$stmt->execute()){
						$my->rollback();
						exit(json_encode(array('success'=>false , 'message'=>'資料庫錯誤'),JSON_UNESCAPED_UNICODE));
					}
					if($stmt->affected_rows==0){
						$my->rollback();
						exit(json_encode(array('success'=>false , 'message'=>'頻道新增失敗'),JSON_UNESCAPED_UNICODE));
					}
					$newOId=$stmt->insert_id;
					//取得託播單素材資訊
					$sql ='SELECT 素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址
						FROM 託播單素材
						WHERE 託播單識別碼 = ?
						';
					$mres = $my->getResultArray($sql,'i',$order['託播單識別碼']);
					//複製素材資訊
					if($mres != null){
						$sql ='INSERT INTO 託播單素材 (託播單識別碼,素材順序,素材識別碼,可否點擊,點擊後開啟類型,點擊後開啟位址,CREATED_PEOPLE) VALUES ';
						$difString = '';
						$para = [];
						$para[] = &$sql;
						$para[] = &$difString;
						$arrayTemp=[];
						foreach($mres as $index=>$row){
							$arrayTemp[] = '(?,?,?,?,?,?,?)';
							$difString .= 'iiiissi';
							$para[] = &$newOId;
							$para[] = &$mres[$index]['素材順序'];
							$para[] = &$mres[$index]['素材識別碼'];
							$para[] = &$mres[$index]['可否點擊'];
							$para[] = &$mres[$index]['點擊後開啟類型'];
							$para[] = &$mres[$index]['點擊後開啟位址'];
							$para[] = &$_SESSION['AMS']['使用者識別碼'];
						}
						$sql.=implode(',',$arrayTemp);
						if(!call_user_func_array(array($my,'execute',),$para)){
							$my->rollback();
							exit(json_encode(array('success'=>false , 'message'=>'新增頻道託播單素材失敗'),JSON_UNESCAPED_UNICODE));
						}
					}
					
					//取得託播單其他參數資訊
					$sql ='SELECT 託播單其他參數順序,託播單其他參數值
						FROM 託播單其他參數
						WHERE 託播單識別碼 = ?
						';
					$cres = $my->getResultArray($sql,'i',$order['託播單識別碼']);
					//複製其他參數資訊
					if($cres != null){
						$sql ='INSERT INTO 託播單其他參數 (託播單識別碼,託播單其他參數順序,託播單其他參數值,CREATED_PEOPLE) VALUES ';
						$difString = '';
						$para = [];
						$para[] = &$sql;
						$para[] = &$difString;
						$arrayTemp=[];
						foreach($cres as $index=>$row){
							$arrayTemp[] = '(?,?,?,?)';
							$difString .= 'iisi';
							$para[] = &$newOId;
							$para[] = &$cres[$index]['託播單其他參數順序'];
							$para[] = &$cres[$index]['託播單其他參數值'];
							$para[] = &$_SESSION['AMS']['使用者識別碼'];
						}
						$sql.=implode(',',$arrayTemp);
						if(!call_user_func_array(array($my,'execute',),$para)){
							$my->rollback();
							exit(json_encode(array('success'=>false , 'message'=>'新增頻道託播單其他參數失敗'),JSON_UNESCAPED_UNICODE));
						}
					}
				}
			}
			//移除頻道
			if(count($deletePosition)!=0){
				//取得被影響的託播單識別碼
				$sql ='SELECT 託播單識別碼 FROM 託播單 WHERE 託播單CSMS群組識別碼=? AND 託播單狀態識別碼 != 5';
				$difString = 'i';
				$para = [];
				$para[] = &$sql;
				$para[] = &$difString;
				$para[] = &$_POST['託播單CSMS群組識別碼'];
				$arrayTemp=[];
				foreach($deletePosition as $key=>$pid){
					$arrayTemp[] = ' 版位識別碼 = ? ';
					$para[] = &$deletePosition[$key];
					$difString.='i';
				}
				$sql.=' AND ('.implode(' OR ',$arrayTemp).')';
				$res = call_user_func_array(array($my,'getResultArray',),$para);
				if(!$res || $res == null){
					$my->rollback();
					exit(json_encode(array('success'=>false , 'message'=>'取得要凍結的託播單識別碼失敗!'),JSON_UNESCAPED_UNICODE));
				}
				$freezeOrderIds=[];
				foreach($res as $row){
					$freezeOrderIds[]=$row['託播單識別碼'];
				}
				//將託播單改為凍結狀態
				$sql ='UPDATE 託播單 SET 託播單狀態識別碼=?,LAST_UPDATE_PEOPLE=?,LAST_UPDATE_TIME=CURRENT_TIMESTAMP WHERE 託播單CSMS群組識別碼=?';
				$difString = 'iii';
				$freezeState = 5;
				$para = [];
				$para[] = &$sql;
				$para[] = &$difString;
				$para[] = &$freezeState;
				$para[] = &$_SESSION['AMS']['使用者識別碼'];
				$para[] = &$_POST['託播單CSMS群組識別碼'];
				$arrayTemp=[];
				foreach($deletePosition as $key=>$pid){
					$arrayTemp[] = ' 版位識別碼 = ? ';
					$para[] = &$deletePosition[$key];
					$difString.='i';
				}
				$sql.=' AND ('.implode(' OR ',$arrayTemp).')';
				if(!call_user_func_array(array($my,'execute',),$para)){
					$my->rollback();
					exit(json_encode(array('success'=>false , 'message'=>'移除頻道失敗!'),JSON_UNESCAPED_UNICODE));
				}
				//記錄異動
				$sql ='INSERT INTO `頻道short EPG banner託播單移出託播單CSMS群組記錄` (託播單CSMS群組識別碼,託播單識別碼,移出者) VALUES ';
				$difString = '';
				$para = [];
				$para[] = &$sql;
				$para[] = &$difString;
				$arrayTemp=[];
				foreach($freezeOrderIds as $key=>$oid){
					$arrayTemp[] = '(?,?,?)';
					$para[] = &$_POST['託播單CSMS群組識別碼'];
					$para[] = &$freezeOrderIds[$key];
					$para[] = &$_SESSION['AMS']['使用者識別碼'];
					$difString.='iii';
				}
				$sql.=implode(' , ',$arrayTemp);
				if(!call_user_func_array(array($my,'execute',),$para)){
					$my->rollback();
					exit(json_encode(array('success'=>false , 'message'=>'異動記錄失敗!'),JSON_UNESCAPED_UNICODE));
				}
			}
			
			$my->commit();
			exit(json_encode(array('success'=>true , 'message'=>'CSMS群組修改成功!'),JSON_UNESCAPED_UNICODE));
		}
		//取得刪除記錄
		else if($_POST['method'] == 'getEditLog_delete'){	
			$logs = [];
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			//取得總筆數
			$sql = 'SELECT  COUNT(1) COUNT
				FROM `頻道short EPG banner託播單移出託播單CSMS群組記錄` 記錄,託播單,版位
				WHERE 記錄.託播單識別碼 = 託播單.託播單識別碼 AND 託播單.版位識別碼 = 版位.版位識別碼
				AND	記錄.託播單CSMS群組識別碼 = ?
			';
			$res = $my->getResultArray($sql,'i',$_POST['託播單CSMS群組識別碼']);
			$totalRowCount=$res[0]['COUNT'];
			//取得記錄
			$sql = 'SELECT 記錄.託播單識別碼,版位名稱,移出時間
				FROM `頻道short EPG banner託播單移出託播單CSMS群組記錄` 記錄,託播單,版位
				WHERE 記錄.託播單識別碼 = 託播單.託播單識別碼 AND 託播單.版位識別碼 = 版位.版位識別碼
				AND	記錄.託播單CSMS群組識別碼 = ?
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			$res = $my->getResultArray($sql,'ii',$_POST['託播單CSMS群組識別碼'],$fromRowNo);
			if($res != null)
			foreach($res as $row){
				$logs[]=[[$row['託播單識別碼'],'text'],[$row['版位名稱'],'text'],[$row['移出時間'],'text']];
			}
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('託播單識別碼','版位名稱','移出時間')
							,'data'=>$logs,'sortable'=>array('託播單識別碼','版位名稱','移出時間')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		//取得加入記錄
		else if($_POST['method'] == 'getEditLog_insert'){	
			$logs = [];
			$fromRowNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?(intval($_POST['pageNo'])-1)*PAGE_SIZE:0;
			$totalRowCount=0;
			//取得總筆數
			$sql = 'SELECT  COUNT(1) COUNT
				FROM 託播單,版位
				WHERE 託播單.版位識別碼 = 版位.版位識別碼
				AND	託播單CSMS群組識別碼 = ?
			';
			$res = $my->getResultArray($sql,'i',$_POST['託播單CSMS群組識別碼']);
			$totalRowCount=$res[0]['COUNT'];
			//取得記錄
			$sql = 'SELECT 託播單識別碼,版位名稱,託播單.CREATED_TIME AS 加入時間
				FROM 託播單,版位
				WHERE 託播單.版位識別碼 = 版位.版位識別碼
				AND	託播單CSMS群組識別碼 = ?
				ORDER BY '.$_POST['order'].' '.$_POST['asc'].' '.
				'LIMIT ?,'.PAGE_SIZE.'
			';
			$res = $my->getResultArray($sql,'ii',$_POST['託播單CSMS群組識別碼'],$fromRowNo);
			if($res != null)
			foreach($res as $row){
				$logs[]=[[$row['託播單識別碼'],'text'],[$row['版位名稱'],'text'],[$row['加入時間'],'text']];
			}
			echo json_encode(array('pageNo'=>($fromRowNo/PAGE_SIZE)+1,'maxPageNo'=>ceil($totalRowCount/PAGE_SIZE),'header'=>array('託播單識別碼','版位名稱','加入時間')
							,'data'=>$logs,'sortable'=>array('託播單識別碼','版位名稱','加入時間')),JSON_UNESCAPED_UNICODE);
			exit;
		}
		exit();
	}
	
	$sql ='
		SELECT 版位名稱 ,版位識別碼
		FROM 版位
		WHERE 版位名稱 = "頻道short EPG banner"
		';
	$result=$my->getResultArray($sql);
	if($result===false)
		exit(json_encode(array('success'=>false,'message'=>'資料庫錯誤，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
	$ptN = $result[0]['版位名稱'];
	$ptId = $result[0]['版位識別碼'];
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script src="../tool/jquery-ui1.2/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.tokenize.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script type="text/javascript" src="newOrder_851.js?<?=time()?>"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-plugin/jquery.tokenize.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<style type="text/css">
.tokenize{ width: 100% }
</style>
</head>

<body>
<?php include('_searchOrderUI.php');?>
<div id = "datagrid"></div>

<div id="dialog_form">
<fieldset>
<legend>設定此CSMS群組中所包含的頻道</legend>
<select id="positions"  multiple="multiple"  class ="tokenize" width='100%'></select>
</fieldset>
<br>
<button id = 'csmsGroupSubmit' style='float:right'>確定</button>
</div>

<div id='dialog_form_logging'>
<fieldset>
<legend>加入記錄</legend>
<div id = 'logDg_insert' width='100%'></div>
</fieldset>
<fieldset>
<legend>移出記錄</legend>
<div id = 'logDg_delete'  width='100%'></div>
</fieldset>
</div>
<script>
	var selectCsmsId;
	var DG;//資料表
	//SEPG版位類型名稱與識別碼
	var ptN = '<?=$ptN?>';
	var ptId = <?=$ptId?>;
	//限制只可選擇SEPG類型版位
	$( "#_searchOUI_positiontype").attr('selectedId',ptId);
	$("#_searchOUI_positiontype").bind('_searchOUI_positiontype_iniDone',function(){
		$( "#_searchOUI_positiontype" ).combobox('disable').trigger('_searchOUI_positiontype_setPosition');
	});
	//調整CSMS群組的子視窗
	$( "#dialog_form" ).dialog({
		autoOpen: false,
		width: 500,
		height: 500,
		title:'設定CSMS群組中的版位',
		modal: true,
	});
	//異動記錄子視窗
	$( "#dialog_form_logging" ).dialog({
		autoOpen: false,
		title:'CSMS群組異動記錄',
		modal: true,
	});
	//增加可被加入的選項
	$.post('',{method:'getAllSepgCh'},
		function(data){
			for(var i in data){
				$('#positions').append('<option value='+data[i]['版位識別碼']+'>'+data[i]['版位識別碼']+':'+data[i]['版位名稱']+'</option>');
			}
			$('#positions').tokenize({
				placeholder:"輸入識別碼或關鍵字該版位類型下的版位"
				,displayDropdownOnFocus:true
				,newElements:false
				,onAddToken: 
					function(value, text, e){
						setSCNPosition([value],'#positions');
					},
				onRemoveToken: 
					function(value, text, e){
						removeSCNPosition([value],'#positions');
					}
			});
		}
		,'json'
	);
	//顯示搜尋的託播單列表
	function showOrderDG(){		
		$('#datagrid').html('');
		var bypost={
				searchBy:$('#_searchOUI_searchOrder').val()
				,廣告主識別碼:$('#_searchOUI_adOwner').val()
				,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$('#_searchOUI_position').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,pageNo:1
				,order:'託播單CSMS群組識別碼'
				,asc:'DESC'
			};

		//取得資料
		bypost['method']='OrderInfoBySearch';
		$.post('ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('CSMS群組管理','群組異動查詢');
				for(var row in json.data){
						json.data[row].push(['CSMS群組管理','button'],['群組異動查詢','button']);
				}
				
				DG=new DataGrid('datagrid',json.header,json.data);
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
				//按鈕點擊
				DG.buttonCellOnClick=function(y,x,row) {
					var colnum = $.inArray('託播單CSMS群組識別碼',json.header);
					if(row[x][0]=='CSMS群組管理') {
						var colnum2 = $.inArray('託播單狀態',json.header);
						if(row[colnum2][0]=='確定'||row[colnum2][0]=='預約') {
							showManagingDialog(row[colnum][0]);
						}
						else{
							alert('只有預約或確定狀態的託播單可調整群組');
						}
					}
					else if(row[x][0]=='群組異動查詢'){
						showLoggingDialog(row[colnum][0]);
					}
					
				}
				
				DG.update=function(){
					$.post('ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data){
									json.data[row].push(['CSMS群組管理','button'],['群組異動查詢','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	//顯示CSMS設定視窗
	function showManagingDialog(CSMSid){
		selectCsmsId = CSMSid;
		$.post('',{method:'getCSMSCh',CSMSID:CSMSid}
		,function(data){
			$('#positions').data('tokenize').clear()
			for(var i in data){
				$('#positions').data('tokenize').tokenAdd(data[i]['版位識別碼'],data[i]['版位識別碼']+':'+data[i]['版位名稱']);
			}
			$( "#dialog_form" ).dialog('open');
		}
		,'json'
		)
	}
	
	//CSMS設定視窗的完成按鈕
	$('#csmsGroupSubmit').click(function(){
		if($('#positions').val() == null){
			alert('CSMS群組中須至少有一個頻道');
			return 0;
		}
		$.post('',{'method':'editCsmsGroup','託播單CSMS群組識別碼':selectCsmsId,'newCsmsGroup':$('#positions').val()}
			,function(data){
				if(!data.success){
					alert(data.message);
					return 0 ;
				}
				alert(data.message);
				DG.update();
				$( "#dialog_form" ).dialog('close');
			}
			,'json'
		);
	});
	
	//顯示異動記錄視窗
	function showLoggingDialog(CSMSid){
		$('#logDg_insert,#logDg_delete').empty();
		showLoggingDG_insert(CSMSid);
		showLoggingDG_delete(CSMSid);
		$( "#dialog_form_logging" ).dialog({height:$(window).height()*0.8, width:$(window).width()*0.8}).dialog('open');
	}
	
	//顯示刪除異動記錄資料表
	function showLoggingDG_delete(CSMSid){
		var bypost={
				'method':'getEditLog_delete'
				,'託播單CSMS群組識別碼':CSMSid
				,pageNo:1
				,order:'移出時間'
				,asc:'ASC'
			};
		$.post('',bypost,
			function(json){				
				var ddg=new DataGrid('logDg_delete',json.header,json.data);
				ddg.set_page_info(json.pageNo,json.maxPageNo);
				ddg.set_sortable(json.sortable,true);
				//頁數改變動作
				ddg.pageChange=function(toPageNo) {
					bypost.pageNo=toPageNo;
					ddg.update();
				}
				//header點擊
				ddg.headerOnClick = function(headerName,sort){
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
					ddg.update();
				};
				ddg.update=function(){
					$.post('',bypost,function(json) {
							ddg.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
	
	//顯示加入異動記錄資料表
	function showLoggingDG_insert(CSMSid){
		var bypost={
				'method':'getEditLog_insert'
				,'託播單CSMS群組識別碼':CSMSid
				,pageNo:1
				,order:'加入時間'
				,asc:'ASC'
			};
		$.post('',bypost,
			function(json){				
				var ddg=new DataGrid('logDg_insert',json.header,json.data);
				ddg.set_page_info(json.pageNo,json.maxPageNo);
				ddg.set_sortable(json.sortable,true);
				//頁數改變動作
				ddg.pageChange=function(toPageNo) {
					bypost.pageNo=toPageNo;
					ddg.update();
				}
				//header點擊
				ddg.headerOnClick = function(headerName,sort){
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
					ddg.update();
				};
				ddg.update=function(){
					$.post('',bypost,function(json) {
							ddg.set_data(json.data);
						},'json');
				}
			}
			,'json'
		);
	}
</script> 
</body>
</html>