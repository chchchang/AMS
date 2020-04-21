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
	if(isset($_POST['使用者識別碼'])&&isset($_POST['使用者帳號'])&&isset($_POST['使用者姓名'])&&isset($_POST['使用者電話'])) {
		$sql='UPDATE 使用者 SET 使用者帳號=?,使用者姓名=?,使用者電話=?,LAST_UPDATE_TIME=NOW(),LAST_UPDATE_PEOPLE=? WHERE 使用者識別碼=?';
		if(!$stmt=$my->prepare($sql)) {
			$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
			exit('無法準備statement，請聯絡系統管理員！');
		}
		
		if(!$stmt->bind_param('sssii',$_POST['使用者帳號'],$_POST['使用者姓名'],$_POST['使用者電話'],$_SESSION['AMS']['使用者識別碼'],$_POST['使用者識別碼'])) {
			$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
			exit('無法繫結資料，請聯絡系統管理員！');
		}
		
		if(!$stmt->execute()) {
			if($stmt->errno==1062){
				$msg='<script>alert("修改失敗-重複的使用者帳號！")</script>';
			}
			else{
				$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
				exit('無法執行statement，請聯絡系統管理員！');
			}
		}
		else {
			$logger->info('"使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')"修改"使用者識別碼('.intval($_POST['使用者識別碼']).')"資料成功');
			$msg='<script>alert("修改成功！")</script>';
		}
	}
	
	//再處理查詢
	$searchBy=isset($_GET['searchBy'])?'%'.$_GET['searchBy'].'%':'%';
	$sortBy=isset($_GET['sortBy'])&&array_search($_GET['sortBy'],array('使用者識別碼','使用者帳號','使用者姓名','使用者電話'))!==false?$_GET['sortBy']:'使用者識別碼';
	$sortType=isset($_GET['sortType'])&&$_GET['sortType']==='decrease'?'decrease':'increase';
	$sql='
		SELECT 使用者識別碼,使用者帳號,使用者姓名,使用者電話,啟用
		FROM 使用者
		WHERE 使用者識別碼 LIKE ? OR 使用者帳號 LIKE ? OR 使用者姓名 LIKE ? OR 使用者電話 LIKE ?
		ORDER BY '.$sortBy.' '.($sortType==='increase'?'ASC':'DESC').',使用者識別碼
	';	//注意SQL中的identifier非使用者可以輸入的內容故不可使用bind param，故排序部分非得使用自組字串方式，但已使用白名單所以亦無SQL injection發生機會。
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
	
	$DG1_data=array();
	while($row=$res->fetch_assoc()) {
		$DG1_data[]=array(array($row['使用者識別碼']),array($row['使用者帳號']),array($row['使用者姓名']),array($row['使用者電話']),array($row['啟用']==1?'啟用':'停用'),array('修改','button'),array($row['啟用']==1?'停用':'啟用','button'));
	}
	$DG1_header=array('使用者識別碼','使用者帳號','使用者姓名','使用者電話','狀態','修改','切換所訂狀態');
	$DG1_header=json_encode($DG1_header,JSON_UNESCAPED_UNICODE);
	$DG1_data=json_encode($DG1_data,JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	include('../tool/sameOriginXfsBlock.php');
	?>
	<script src='../tool/jquery-3.4.1.min.js'></script>
	<script src='../tool/datagrid/CDataGrid.js'></script>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<script>
		$(document).ready(function() {
			//處理datagrid
			var DG1_header=<?=$DG1_header?>;
			var DG1_data=<?=$DG1_data?>;
			var DG1=new DataGrid('DG1',DG1_header,DG1_data);
			DG1.set_sortable(['使用者識別碼','使用者帳號','使用者姓名','使用者電話'],true);
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
					if(row[x][0]=='修改'){
						this.collapse_row(y);
						$('input[name=使用者識別碼]').val(row[0][0]);
						$('input[name=使用者帳號]').val(row[1][0]);
						$('input[name=使用者姓名]').val(row[2][0]);
						$('input[name=使用者電話]').val(row[3][0]);
						$('form[method=post]').show(500);
					}
					else if(row[x][0]=='停用'){
						$.post('ajaxToDb_user.php',{'method':'停用使用者','使用者識別碼':row[0][0]},
							function(data){
								alert(data);
								 location.reload();
							}
						);
					}
					else if(row[x][0]=='啟用'){
						$.post('ajaxToDb_user.php',{'method':'啟用使用者','使用者識別碼':row[0][0]},
							function(data){
								alert(data);
								 location.reload();
							}
						);
					}
				}
				else {
					this.uncollapse(y);
					$('form[method=post]').hide(500);
				}
			}
			
			//處理搜尋框
			var searchByDefaultValue='輸入使用者識別碼、姓名、電話等進行搜尋';
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
<form name="myForm" onsubmit="return validateForm()" method="post">
	<fieldset align="center" valign="center" style="text-align:center">
		<legend>請輸入欲修改使用者的資料</legend>
		<input type="hidden" name="使用者識別碼">
		<p>使用者帳號* <input type="text" name="使用者帳號" class ="nonNull"></p>
		<p>使用者姓名* <input type="text" name="使用者姓名" class ="nonNull"></p>
		<p>使用者電話 <input type="text" name="使用者電話" ></p>
		<input class="button" type="reset">
		<input class="button" type="submit" value="送出">
	</fieldset>
</form>
</body>
</html>