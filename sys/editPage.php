<?php
	include('../tool/auth/auth.php');
	
	$msg='';
	
	$my=new mysqli(Config::DB_HOST,Config::DB_USER,Config::DB_PASSWORD,Config::DB_NAME);
	if($my->connect_errno) {
		$logger->error('無法連線到資料庫，錯誤代碼('.$my->connect_errno.')、錯誤訊息('.$my->connect_error.')。');
		exit('無法連線到資料庫，請聯絡系統管理員！');
	}
	
	if(!$my->set_charset('utf8')) {
		$logger->error('無法設定資料庫連線字元集為utf8，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
		exit('無法設定資料庫連線字元集為utf8，請聯絡系統管理員！');
	}
	
	//先處理修改
	if(isset($_POST['頁面識別碼'])&&isset($_POST['頁面名稱'])&&isset($_POST['頁面說明'])&&isset($_POST['頁面路徑'])) {
		$sql='UPDATE 頁面 SET 頁面名稱=?,頁面說明=?,頁面路徑=?,LAST_UPDATE_TIME=NOW(),LAST_UPDATE_PEOPLE=? WHERE 頁面識別碼=?';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('sssii',$_POST['頁面名稱'],$_POST['頁面說明'],$_POST['頁面路徑'],$_SESSION['AMS']['使用者識別碼'],$_POST['頁面識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			if($stmt->errno==1062)
				exit('重複的名稱: '.$stmt->error);
			$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法執行statement，請聯絡系統管理員！');
		}
		else {
			$logger->info('"使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')"修改"頁面識別碼('.intval($_POST['頁面識別碼']).')"資料成功');
			$msg='<script>alert("修改成功！")</script>';
		}
	}
	
	//再處理查詢
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
		$DG1_data[]=array(array($row['頁面識別碼']),array($row['頁面名稱']),array($row['頁面說明']),array($row['頁面路徑']),array('修改','button'));
	}
	$DG1_header=array('頁面識別碼','頁面名稱','頁面說明','頁面路徑','修改');
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
			DG1.buttonCellOnClick=function(y,x,row) {
				if(!this.is_collapsed()) {
					this.collapse_row(y);
					$('input[name=頁面識別碼]').val(row[0][0]);
					$('input[name=頁面名稱]').val(row[1][0]);
					$('input[name=頁面說明]').val(row[2][0]);
					$('input[name=頁面路徑]').val(row[3][0]);
					$('form[method=post]').show(500);
				}
				else {
					this.uncollapse(y);
					$('form[method=post]').hide(500);
				}
			}
			
			//處理搜尋框
			var searchByDefaultValue='輸入頁面名稱、說明、路徑等進行搜尋';
			if($('input[name|="searchBy"]').val()=='')
				$('input[name|="searchBy"]').val(searchByDefaultValue);
			$("input[name|='searchBy']").focus(function(){
				if(this.value===searchByDefaultValue)
					this.value="";
			})
			
			//處理表單
			$('form[method=post]').hide();
		});
		
		function validateForm() {
			var nonNullEmpty= false;
			$(".nonNull").each(function(){ 
				if($.trim($(this).val())==""){
					nonNullEmpty = true;
				}
			});
			
			if(nonNullEmpty){
				alert("請填寫必要資訊");
				$(".nonNull").css("border", "2px solid red");
				return false;
			}
		}
	</script>
</head>
<body>
<?=$msg?>
<form method="get">
	<input type="text" name="searchBy" class="searchInput" value="<?=isset($_GET['searchBy'])?htmlentities($_GET['searchBy']):''?>">
	<input type="submit" class="searchSubmit" value="查詢">
</form>
<div id="DG1"></div>
<form align="center" valign="center" style="text-align:center" name="myForm" onsubmit="return validateForm()" method="post">
	<fieldset align="center" valign="center" style="text-align:center">
		<legend>請輸入欲修改頁面的資料</legend>
		<input type="hidden" name="頁面識別碼">
		<p>頁面名稱* <input type="text" name="頁面名稱" class ="nonNull"></p>
		<p>頁面說明 <input type="text" name="頁面說明"></p>
		<p>頁面路徑* <input type="text" name="頁面路徑" class ="nonNull"></p>
		<input type="reset" class="button"> <input type="submit" class="button" value="新增">
	</fieldset>
</form>
</body>
</html>