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
	
	$searchBy=isset($_GET['searchBy'])?'%'.$_GET['searchBy'].'%':'%';
	$sortBy=isset($_GET['sortBy'])&&array_search($_GET['sortBy'],array('頁面識別碼','頁面名稱','頁面路徑'))!==false?$_GET['sortBy']:'頁面路徑';
	$sortType=isset($_GET['sortType'])&&$_GET['sortType']==='decrease'?'decrease':'increase';
	$sql='
		SELECT 頁面識別碼,頁面名稱,頁面說明,頁面路徑
		FROM 頁面
		WHERE 頁面名稱 LIKE ? OR 頁面說明 LIKE ? OR 頁面路徑 LIKE ?
		ORDER BY '.$sortBy.' '.($sortType==='increase'?'ASC':'DESC').',頁面路徑,頁面名稱
	';	//注意SQL中的identifier非使用者可以輸入的內容故不可使用bind param，故排序部分非得使用自組字串方式，但已使用白名單所以亦無SQL injection發生機會。
	if(!$stmt=$my->prepare($sql)) {
		$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法準備statement，請聯絡系統管理員！');
	}
	
	if(!$stmt->bind_param('sss',$searchBy,$searchBy,$searchBy)) {
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
	
	$DG1_data=array();
	while($row=$res->fetch_assoc()) {
		$DG1_data[]=array(array($row['頁面識別碼']),array($row['頁面名稱']),array($row['頁面說明']),array($row['頁面路徑']));
	}
	$DG1_header=array('頁面識別碼','頁面名稱','頁面說明','頁面路徑');
	$DG1_header=json_encode($DG1_header,JSON_UNESCAPED_UNICODE);
	$DG1_data=json_encode($DG1_data,JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
	<script src='../tool/jquery-3.4.1.min.js'></script>
	<script src="../tool/HtmlSanitizer.js"></script>
	<script src='../tool/datagrid/CDataGrid.js'></script>
	<script>
		$(document).ready(function() {
			//處理datagrid
			var DG1_header=<?=$DG1_header?>;
			var DG1_data=<?=$DG1_data?>;
			var DG1=new DataGrid('DG1',DG1_header,DG1_data);
			DG1.set_sortable(['頁面識別碼','頁面名稱','頁面路徑'],true);
			DG1.set_sort_state('<?=htmlentities($sortBy)?>','<?=$sortType?>');
			DG1.headerOnClick=function(headerName,sortState){
				if(sortState==='unSort')	//切換順序依序是遞增、遞減、不排序，故若要取消不排序，則要將不排序視為遞增，否則只會在遞減與不排序間切換。
					sortState='increase';
				var searchBy='';
				if(searchBy=/searchBy=[^&]+/.exec(location.search)) {
					searchBy=searchBy.toString().substr(9);
					
				}
				location.replace('?'+(searchBy?'searchBy='+searchBy+'&':'')+'sortBy='+headerName+'&sortType='+sortState);
			};
			
			//處理搜尋框
			var searchByDefaultValue='輸入頁面名稱、說明、路徑等進行搜尋';
			if($('input[name|="searchBy"]').val()=='')
				$('input[name|="searchBy"]').val(searchByDefaultValue);
			$("input[name|='searchBy']").focus(function(){
				if(this.value===searchByDefaultValue)
					this.value="";
			})
		});
	</script>
</head>
<body>
<form method="get">
	<input class="searchInput" type="text" name="searchBy" value="<?=isset($_GET['searchBy'])?htmlentities($_GET['searchBy']):''?>">
	<input class="searchSubmit" type="submit" value="查詢">
</form>
<div id="DG1"></div>
</body>
</html>