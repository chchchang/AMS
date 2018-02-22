<?php
	require '../tool/auth/auth.php';
	define('PAGE_SIZE',10);
	define("MATERIAL_FOLDER", Config::GET_MATERIAL_FOLDER());
	define("MATERIAL_FOLDER_URL", Config::GET_MATERIAL_FOLDER_URL(dirname(__FILE__).'\\'));
	
	if(isset($_POST['action'])){
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
			WHERE 素材類型識別碼=2 
				AND 素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? )'.($unCimmitOnly=='true'?'AND (圖片素材派送結果 IS NULL OR 圖片素材派送結果="[]")':'')
				.$fileUploadOrNot
				,'ssssssssssss'
				,$materialGroup
				,$startDate,$endDate,$startDate,$endDate,$startDate,$endDate,$startDate
				,$searchBy,$searchBy,$searchBy,$searchBy
				);			
			$totalRowCount=$result[0]['COUNT'];
			//取得資料
			$DG_header=array('素材識別碼','素材名稱','素材說明','素材原始檔名','是否曾經派送');
			$sql = 'SELECT 素材識別碼,素材名稱,素材說明,素材原始檔名,圖片素材派送結果 AS 是否曾經派送 FROM 素材 
			WHERE 素材類型識別碼=2 
				AND 素材群組識別碼 LIKE ? 
				AND(
						((素材有效開始時間 BETWEEN ? AND ?) OR (素材有效結束時間 BETWEEN ? AND ?) OR (? BETWEEN 素材有效開始時間 AND 素材有效結束時間))
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間 IS NULL)
						OR (素材有效開始時間 IS NULL AND 素材有效結束時間>?)
						OR (素材有效結束時間 IS NULL AND 素材有效開始時間<?)
					)
				AND ( 素材識別碼 LIKE ? OR 素材名稱 LIKE ? OR 素材說明 LIKE ? OR 素材原始檔名 LIKE ? )'.($unCimmitOnly=='true'?' AND (圖片素材派送結果 IS NULL OR 圖片素材派送結果="[]")':'')
				.$fileUploadOrNot.
				' ORDER BY '.((isset($_POST['order']))?$_POST['order']:'素材識別碼').' '.$_POST['asc'].
				($showAll?'':(' LIMIT ?, '.PAGE_SIZE));
			$defString = 'ssssssssssss'.($showAll?'':'i');
			$a_params =[&$sql,&$defString,&$materialGroup,&$startDate,&$endDate,&$startDate,&$endDate,&$startDate,&$endDate,&$startDate,&$searchBy,&$searchBy,&$searchBy,&$searchBy];
			if(!$showAll)
				$a_params[] =&$fromRowNo;
			$result=call_user_func_array(array($my, 'getResultArray'), $a_params);
			
			$DG_header=array_merge($DG_header,array('圖片預覽','VSM狀態','取得VSM狀態','派送到VSM'));
			$DG_body=array();
			if(isset($result)&&$result!=null)
			foreach($result as $row){
				$mnameA=explode('.',$row['素材原始檔名']);
				$DG_body[]=array(array($row['素材識別碼']),array($row['素材名稱']),array($row['素材說明']),array($row['素材原始檔名']),
				array('<img src="../tool/pic/'.($row['是否曾經派送']==NULL||$row['是否曾經派送']=='[]'?'Circle_Red.png':'Circle_Green.png').'">','html'),
				array('<img class="dgImg" src="'.MATERIAL_FOLDER_URL.$row['素材識別碼'].'.'.end($mnameA).'?'.time().'" alt="'.$row['素材識別碼'].':'.$row['素材原始檔名'].'" style="max-width:100%;max-height:100%;border:0;">','html'),
				array(''),array('取得VSM狀態','button'),array('派送到VSM','button'));
			}
			header('Content-Type: application/json');
			exit(json_encode(array('pageNo'=>$showAll?1:(($fromRowNo/PAGE_SIZE)+1),'maxPageNo'=>$showAll?1:ceil($totalRowCount/PAGE_SIZE),'allCount'=>$totalRowCount,
				'header'=>$DG_header,'sortable'=>array('素材識別碼','素材名稱','素材說明','素材原始檔名','是否曾經派送'),'body'=>$DG_body)));
		}
		else if(($_POST['action']==='isAllFile')&&isset($_POST['remote'])){
			$result=array();
			$hostRes = array();
			require '../tool/SFTP.php';
			foreach(Config::$FTP_SERVERS['VSM'] as $server){
				$遠端路徑=$server['圖片素材路徑'];
				$result[]=SFTP::isFile($server['host'],$server['username'],$server['password'],$遠端路徑.$_POST['remote'])?true:false;
				//$result[]=true;
				$hostRes[$server['host']]=$result[count($result)-1];
			}
			recordResult($_POST['素材識別碼'],$hostRes);
			header('Content-Type: application/json');
			exit(json_encode(array('remote'=>$_POST['remote'],'result'=>$result)));
		}
		else if(($_POST['action']==='putAll')&&isset($_POST['local'])&&isset($_POST['remote'])){
			$本地路徑=MATERIAL_FOLDER;
			if(is_file($本地路徑.$_POST['local'])===false){
				header('Content-Type: application/json');
				exit(json_encode(array('error'=>'找不到指定素材，可能是素材未到位或檔案遺失，請上傳後再派送。')));
			}
			$result=array();
			$hostRes = array();
			require '../tool/SFTP.php';
			foreach(Config::$FTP_SERVERS['VSM'] as $server){
				$遠端路徑=$server['圖片素材路徑'];
				$result[]=SFTP::putAndRename($server['host'],$server['username'],$server['password'],$本地路徑.$_POST['local'],$遠端路徑.$_POST['remote'],$_POST['remote'].'.temp')?true:false;
				//$result[]=true;
				$hostRes[$server['host']]=$result[count($result)-1];
			}
			recordResult($_POST['素材識別碼'],$hostRes);
			header('Content-Type: application/json');
			exit(json_encode(array('error'=>'','local'=>$_POST['local'],'remote'=>$_POST['remote'],'result'=>$result)));
		}
	}
	
	function recordResult($mid,$hostArray){
		//取得資料庫的送出結果
		$my = new MyDB();
		$sql='SELECT 圖片素材派送結果 FROM 素材 WHERE 素材識別碼=?';
		$result=$my->getResultArray($sql,'i',$mid);
		if($result!=null)
			$result = json_decode($result[0]['圖片素材派送結果']);
		if($result == null) $result = [];
		
		//更新送出結果
		foreach($hostArray as $host=>$sendRes){
			if($sendRes){
				//送出成功
				if(!in_array($host,$result))
					$result[]=$host;
			}else{
				//送出失敗
				if(in_array($host,$result)){
					unset($result[$host]);
					$result = array_values($result);
				}
			}
		}
		$sql='UPDATE 素材 SET 圖片素材派送結果=? WHERE 素材識別碼=?';
		if(!$my->execute($sql,'si',json_encode($result),$mid))
			exit(json_encode(array('error'=>'素材送出結果記錄失敗')));
	}
?>
<!doctype html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css" />
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<script src="../tool/jquery-1.11.1.js"></script>
<script src="../tool/jquery.loadmask.js"></script>
<script src="../tool/datagrid/CDataGrid.js"></script>
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
</head>
<body>
<?php include('_searchMaterialUI.php'); ?>
<input type="checkbox" id="unCimmitOnly">僅顯示尚未派送項目 &nbsp;
<input type="radio" name="素材是否已到" value="顯示素材已到與未到項目" checked>僅顯示素材已到與未到項目
<input type="radio" name="素材是否已到" value="僅顯示素材未到項目">僅顯示素材未到項目
<input type="radio" name="素材是否已到" value="僅顯示素材已到項目">僅顯示素材已到項目
<div id = 'showAllDiv'>
<input type="checkbox" id="showAll">顯示全部<a id = "allCount"></a>筆資料(若資料量過大不建議使用)
</div>
<div id="DG"></div>
<script>
$("#_searchMUI_materialTypeSelectoin,#_searchMUI_missingFileOnly,#_searchMUI_missingFiletext").hide();
//增加顯示未派送素材選項
</script>
<script>
$(document).ready(function(){
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
});
	//顯示的資料類行切換
	$('#unCimmitOnly').click(function(){
		getmDataGrid();
	})
	$('input[name=素材是否已到]').change(function(){
		getmDataGrid();
    });
	
	var buttonOnClick=function(event){
		function getColByName(colName){
			return $('#DG td:nth-child('+($('#DG th:contains("'+colName+'")')[0].cellIndex+1)+')')[event.target.parentElement.parentElement.rowIndex-1];
		}
		function getColValueByName(colName){
			return getColByName(colName).textContent;
		}
		
		var local=getColValueByName('素材識別碼')+getColValueByName('素材原始檔名').substr(getColValueByName('素材原始檔名').lastIndexOf('.'))
		$(event.target).mask('處理中...');
		
		var remote='_____AMS_'+local;
		
		var mid = getColValueByName('素材識別碼');
		
		var 狀態node=getColByName('VSM狀態');
		$(狀態node).mask('...');
		
		if(event.target.textContent.substr(0,2)==='取得'){
			//判斷資料夾下是否存在此圖檔
			$.post(null,{action:'isAllFile',remote:remote,'素材識別碼':mid},function(json){
				var buff='';
				for(var i in json.result)
					buff+='<img src="../tool/pic/'+((json.result[i])?'Circle_Green.png':'Circle_Red.png')+'">';
				$(狀態node).unmask();
				$(event.target).unmask();
				狀態node.innerHTML=buff;
			},'json');
		}
		else{
			//上傳圖檔到資料夾下
			$.post(null,{action:'putAll',local:local,remote:remote,'素材識別碼':mid},function(json){
				if(json.error!==''){
					$(狀態node).unmask();
					$(event.target).unmask();
					狀態node.innerHTML=json.error;
				}else{
					var buff='';
					for(var i in json.result)
						buff+='<img src="../tool/pic/'+((json.result[i])?'Circle_Green.png':'Circle_Red.png')+'">';
					$(狀態node).unmask();
					$(event.target).unmask();
					狀態node.innerHTML=buff;
				}
			},'json');
		}
	}
	
	$('#showAll').click(function(){
		getmDataGrid();
	});
	
	//載入DG資料
	function getmDataGrid(){
		$("#DG").empty();
		$('body').mask('取得資料中...');
		var bypost={
			action:'getMateral'
			,searchBy:$('#_searchMUI_shearchText').val()
			,'素材類型':2
			,pageNo:1,order:'素材識別碼'
			,asc:'DESC'
			,"素材群組識別碼":$("#_searchMUI_materialGroup").val()
			,"開始時間":$("#_searchMUI_startDate").val()
			,"結束時間":$("#_searchMUI_endDate").val()
			,"僅顯示未派送":$("#unCimmitOnly").prop('checked')
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
			$('#DG').prepend('<button id="getAll">取得結果</button><button id="putAll">派送圖片</button>')
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
				setHoverImag()
				},'json');
			};
			setHoverImag()
			/*$$$$$全部、取消全部選擇以及批次取得結果、派送影片功能$$$$$*/
			$('body').unmask();
		},'json');
		
		function setHoverImag(){
			$('.dgImg').hover(
				function(e) {
					var mX = e.pageX;
					var mY = e.pageY;
					$('body').append('<img id="'+$(this).attr('alt').split(':')[0]+'" src="'+$(this).attr('src')+'"></img>');
					$('#'+$(this).attr('alt').split(':')[0]).css({'position':'absolute', 'top':mY,'left':mX});
				}, function() {
					$('#'+$(this).attr('alt').split(':')[0]).remove();
				}
			);
		}
		
		function setDgBtn(){
			$('#DG button').not('#getAll,#putAll').click(buttonOnClick);
			
			/*^^^^^全部、取消全部選擇以及批次取得結果、派送影片功能^^^^^*/
			$('#DG tr').first().find('th').first().html('<input type="checkbox" id="selectAllOrNot">');
			
			$('#DG').css('width','100%');
			
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
</body>