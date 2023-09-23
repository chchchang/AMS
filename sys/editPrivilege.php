<?php
	include('../tool/auth/auth.php');
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	//取得使用者列表
	if(isset($_POST['method'])&&$_POST['method']==='getUsers') {
		$searchBy=isset($_POST['searchBy'])?'%'.$_POST['searchBy'].'%':'%';
		$sortableFields=array('使用者識別碼','使用者帳號','使用者姓名','使用者電話');
		$sortBy=isset($_POST['sortBy'])&&(($tmpIndex=array_search($_POST['sortBy'],$sortableFields))!==false)?$sortableFields[$tmpIndex]:'使用者識別碼';
		$sortType=isset($_POST['sortType'])&&$_POST['sortType']==='decrease'?'DESC':'ASC';
		$pageNo=isset($_POST['pageNo'])&&intval($_POST['pageNo'])>0?intval($_POST['pageNo']):1;
		$pageSize=10;
		$rowNoFrom=($pageNo-1)*$pageSize;
		$maxPageNo=0;	//T.B.D.
		$DG1_data=array();
		$DG1_header=array('使用者識別碼','使用者帳號','使用者姓名','使用者電話','修改權限');
		$DG1_sortableFields=$sortableFields;
		
		//先取得總頁數
		$sql='
			SELECT COUNT(1)
			FROM 使用者
			WHERE 使用者識別碼 LIKE ? OR 使用者帳號 LIKE ? OR 使用者姓名 LIKE ? OR 使用者電話 LIKE ?
		';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('ssss',$searchBy,$searchBy,$searchBy,$searchBy)) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		if($row=$res->fetch_assoc()) {
			$maxPageNo=ceil($row['COUNT(1)']/$pageSize);
		}
		
		//再取得使用者資料
		$sql='
			SELECT 使用者識別碼,使用者帳號,使用者姓名,使用者電話
			FROM 使用者
			WHERE 使用者識別碼 LIKE ? OR 使用者帳號 LIKE ? OR 使用者姓名 LIKE ? OR 使用者電話 LIKE ?
			ORDER BY '.$sortBy.' '.$sortType.',使用者識別碼
			LIMIT ?,?
		';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('ssssii',$searchBy,$searchBy,$searchBy,$searchBy,$rowNoFrom,$pageSize)) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		while($row=$res->fetch_assoc()) {
			$DG1_data[]=array(array($row['使用者識別碼']),array($row['使用者帳號']),array($row['使用者姓名']),array($row['使用者電話']),array('修改權限','button'));
		}
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array(
			'header'=>$DG1_header,
			'data'=>$DG1_data,
			'sortableFields'=>$DG1_sortableFields,
			'sortBy'=>$sortBy,
			'sortType'=>$sortType==='ASC'?'increase':'decrease',
			'pageNo'=>$pageNo,
			'maxPageNo'=>$maxPageNo
		),JSON_UNESCAPED_UNICODE);
		exit;
	}
	//取得使用者權限列表
	else if(isset($_POST['method'])&&$_POST['method']==='getUserPrivileges'&&isset($_POST['userId'])) {
		$DG2_data=array();
		$DG2_header=array('頁面識別碼','頁面名稱','頁面說明','已啟用','開啟權限','關閉權限');
		
		$sql= '
			SELECT 頁面.頁面識別碼,頁面.頁面名稱,頁面.頁面說明,NOT ISNULL(TEMP.頁面識別碼) 已啟用
			FROM 頁面 LEFT JOIN (SELECT * FROM 權限 WHERE 使用者識別碼=?) TEMP ON 頁面.頁面識別碼=TEMP.頁面識別碼
			ORDER BY 頁面.頁面路徑
		';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('i',$_POST['userId'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		
		if(!$res=$stmt->get_result()) {
			$logger->error('無法取得結果集，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法取得結果集，請聯絡系統管理員！');
		}
		
		while($row=$res->fetch_assoc()) {
			$DG2_data[]=array(array($row['頁面識別碼']),array($row['頁面名稱']),array($row['頁面說明']),array($row['已啟用']===1?'啟用':'未啟用'),array('開啟權限','button'),array('關閉權限','button'));
		}
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array(
			'header'=>$DG2_header,
			'data'=>$DG2_data
		),JSON_UNESCAPED_UNICODE);
		exit;
	}
	//更新使用者權限
	else if(isset($_POST['method'])&&$_POST['method']==='updateUserPrivilege'&&isset($_POST['cmd'])&&isset($_POST['使用者識別碼'])&&isset($_POST['頁面識別碼'])) {
		$_POST['使用者識別碼']=intval($_POST['使用者識別碼']);
		
		if($_POST['cmd']==='delete')
			$sql='DELETE FROM 權限 WHERE 使用者識別碼=? AND 頁面識別碼=?';
		else
			$sql='INSERT INTO 權限(使用者識別碼,頁面識別碼,CREATED_PEOPLE) VALUES(?,?,?)';

		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if($_POST['cmd']==='delete') {
			if(!$stmt->bind_param('ii',$_POST['使用者識別碼'],$_POST['頁面識別碼'])) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
		}
		else {
			if(!$stmt->bind_param('iii',$_POST['使用者識別碼'],$_POST['頁面識別碼'],$_SESSION['AMS']['使用者識別碼'])) {
				$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法繫結資料，請聯絡系統管理員！');
			}
		}
		
		if(!$success=$stmt->execute()) {
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		else {
			if($_POST['cmd']==='delete')
				$logger->info('使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')關閉使用者識別碼('.$_POST['使用者識別碼'].')的頁面識別碼('.$_POST['頁面識別碼'].')權限成功！');
			else
				$logger->info('使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')開啟使用者識別碼('.$_POST['使用者識別碼'].')的頁面識別碼('.$_POST['頁面識別碼'].')權限成功！');
		}
		
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array(
			'success'=>$success
		),JSON_UNESCAPED_UNICODE);
		exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script src='../tool/jquery-3.4.1.min.js'></script>
	<script src="../tool/HtmlSanitizer.js"></script>
	<script src='../tool/datagrid/CDataGrid.js'></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
	<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
	<script>
		$(document).ready(function() {
			// 幫有 placeholder 屬性的輸入框加上提示效果
			$('input[placeholder]').placeholder();
		
			var DG1=new DataGrid('DG1',[""],[""]);	//先占空間與宣告為全域變數
			DG1.sortBy=null;	//T.B.D.
			DG1.sortType=null;	//T.B.D.
			DG1.使用者識別碼=null;	//T.B.D.
			var DG2=new DataGrid('DG2',[""],[""]);	//先占空間與宣告為全域變數
			
			//處理datagrid1
			$.post('?',{method:'getUsers'},function(json) {
				DG1.set_header(json.header);
				DG1.set_data(json.data);
				DG1.set_sortable(json.sortableFields,true);
				DG1.set_sort_state(json.sortBy,json.sortType);
				DG1.set_page_info(json.pageNo,json.maxPageNo);
				DG1.headerOnClick=function(headerName,sortState) {
					var searchBy=$('input[name=searchBy]').val();
					var sortBy=headerName;
					var sortType=(sortState==='decrease')?'decrease':'increase';
					DG1.sortBy=sortBy;	//至少換頁時會使用到
					DG1.sortType=sortType;	//至少換頁時會使用到
					
					$.post('?',{method:'getUsers',searchBy:searchBy,sortBy:sortBy,sortType:sortType},function(json) {
						DG1.set_header(json.header);
						DG1.set_data(json.data);
						DG1.set_sortable(json.sortableFields,true);
						DG1.set_sort_state(json.sortBy,json.sortType);
						DG1.set_page_info(json.pageNo,json.maxPageNo);
					},'json');
				}
				DG1.pageChange=function(pageNo) {
					var searchBy=$('input[name=searchBy]').val();
					
					$.post('?',{method:'getUsers',searchBy:searchBy,sortBy:DG1.sortBy,sortType:DG1.sortType,pageNo:pageNo},function(json) {
						DG1.set_header(json.header);
						DG1.set_data(json.data);
						DG1.set_sortable(json.sortableFields,true);
						DG1.set_sort_state(json.sortBy,json.sortType);
						DG1.set_page_info(json.pageNo,json.maxPageNo);
					},'json');
				}
				DG1.buttonCellOnClick=function(y,x,row) {
					if(this.is_collapsed()) {
						$('#DG2').hide();
						this.uncollapse();
					}
					else {
						this.collapse_row(y);
						DG1.使用者識別碼=row[0][0];	//至少開啟或關閉權限時會使用到
						$.post('?',{method:'getUserPrivileges',userId:DG1.使用者識別碼},function(json) {
							DG2.set_header(json.header);
							DG2.set_data(json.data);
							$('#DG2').show(300);
						},'json');
					}
				}
			},'json');
			
			//處理datagrid2
			$('#DG2').hide();
			DG2.buttonCellOnClick=function(y,x,row) {
				if(x===4) {	//開啟權限
					if(row[3][0]==='啟用')
						alert('已啟用，不需開啟權限！');
					else {
						$.post('?',{method:'updateUserPrivilege',cmd:'insert',頁面識別碼:row[0][0],使用者識別碼:DG1.使用者識別碼},function(json) {
							if(json.success) {
								alert('開啟權限成功！');
								$.post('?',{method:'getUserPrivileges',userId:DG1.使用者識別碼},function(json) {
									DG2.set_data(json.data);
								},'json');
							}
							else
								alert('開啟權限失敗！');
						},'json');
					}
				}
				else if(x===5) {	//關閉權限
					if(row[3][0]==='未啟用')
						alert('未啟用，不需關閉權限！');
					else {
						$.post('?',{method:'updateUserPrivilege',cmd:'delete',頁面識別碼:row[0][0],使用者識別碼:DG1.使用者識別碼},function(json) {
							if(json.success) {
								alert('關閉權限成功！');
								$.post('?',{method:'getUserPrivileges',userId:DG1.使用者識別碼},function(json) {
									DG2.set_data(json.data);
								},'json');
							}
							else
								alert('關閉權限失敗！');
						},'json');
					}
				}
			}
			
			//處理搜尋框
			$('form').submit(function(event){
				event.preventDefault();
				$('#DG2').hide();
				$.post('?',{method:'getUsers',searchBy:$('input[name="searchBy"]').val()},function(json) {
					DG1.set_header(json.header);
					DG1.set_data(json.data);
					DG1.set_sortable(json.sortableFields,true);
					DG1.set_sort_state(json.sortBy,json.sortType);
					DG1.set_page_info(json.pageNo,json.maxPageNo);
				},'json');
			});
		});
	</script>
</head>
<body>
<form>
	<input type="text" name="searchBy" class="searchInput" value="" placeholder="輸入使用者識別碼、使用者帳號、姓名、電話等進行搜尋">
	<input type="submit" class="searchSubmit" value="查詢">
</form>
<div id="DG1"></div>
<div id="DG2"></div>
</body>
</html>