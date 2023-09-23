<?php
	include('../tool/auth/auth.php');
	
	if(isset($_POST['action'])){
		if($_POST['action']==='getCategory'){
			$my=new MyDB();
			$sql='SELECT 頻道號碼,廣告分類,InsertedTime,UpdatedTime FROM 預測_廣告分類 ORDER BY 頻道號碼 ASC';
			$result=$my->getResultArray($sql);
			
			$json=array();
			foreach($result as $row){
				$json[]=array(array($row['頻道號碼']),array($row['廣告分類']),array($row['InsertedTime']),array($row['UpdatedTime']),array('修改','button'),array('刪除','button'));
			}
			$json=array('header'=>array('頻道號碼','廣告分類','新增的時間','修改的時間','修改','刪除'),'body'=>$json);
			
			header('Content-Type: application/json');
			exit(json_encode($json));
		}
		else if($_POST['action']==='insert'&&isset($_POST['頻道號碼'])&&isset($_POST['廣告分類'])){
			$my=new MyDB(true);
			$sql='INSERT INTO 預測_廣告分類(頻道號碼,廣告分類) VALUES(?,?)';
			$stmt=$my->prepare($sql);
			$stmt->bind_param('is',$_POST['頻道號碼'],$_POST['廣告分類']);
			$stmt->execute();
		}
		else if($_POST['action']==='update'&&isset($_POST['頻道號碼'])&&isset($_POST['廣告分類'])){
			$my=new MyDB(true);
			$sql='UPDATE 預測_廣告分類 SET 廣告分類=?,UpdatedTime=? WHERE 頻道號碼=?';
			$stmt=$my->prepare($sql);
			$tmp=date('YmdHis');
			$stmt->bind_param('ssi',$_POST['廣告分類'],$tmp,$_POST['頻道號碼']);
			$stmt->execute();
		}
		else if($_POST['action']==='delete'&&isset($_POST['頻道號碼'])){
			$my=new MyDB();
			$sql='DELETE FROM 預測_廣告分類 WHERE 頻道號碼=?';
			$stmt=$my->prepare($sql);
			$stmt->bind_param('i',$_POST['頻道號碼']);
			$stmt->execute();
		}
	}
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css" />
<script src="../tool/jquery-3.4.1.min.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<script src="../tool/datagrid/CDataGrid.js"></script>
</head>
<script>
	$(document).ready(function(){
		$('#fieldset2').hide();
		$('#form2 input[type=button]').click(function(){
			$('#fieldset2').hide();
			DG1.uncollapse();
		});
		$.post(
			null,
			{action:'getCategory'},
			function(json){
				DG1=new DataGrid('DG1',json.header,json.body);	//global
				DG1.buttonCellOnClick=function(rc,cc,row){
					if(cc===4){
						if(this.is_collapsed())
							this.uncollapse();
						else{
							this.collapse_row(rc);
							$('#form2 input[name=頻道號碼]').val(row[0][0]);
							$('#form2 input[name=廣告分類]').val(row[1][0]);
							$('#fieldset2').show();
							$('#form2 input[name=廣告分類]').focus();
						}
					}
					else if(cc===5){
						if(confirm('確定刪除?')){
							$.post(
								null,
								{action:'delete',頻道號碼:row[0][0]},
								function(json){
									location.reload();
								},
								'html'
							);
						}
					}
				}
			},
			'json'
		);
	});
</script>
<body>
<fieldset id="fieldset1">
<legend>請輸入新增廣告分類的資料</legend>
	<form id="form1" method="post">
		<input type="hidden" name="action" value="insert">
		<table>
		<tr>
			<td>頻道號碼</td>
			<td>廣告分類</td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td><input type="text" name="頻道號碼" value=""></td>
			<td><input type="text" name="廣告分類" value=""></td>
			<td><input type="reset" value="清空"></td>
			<td><input type="submit" value="送出"></td>
		</tr>
		</table>
	</form>
</fieldset>
<div id="DG1"></div>
<fieldset id="fieldset2">
<legend>請輸入修改廣告分類的資料</legend>
	<form id="form2" method="post">
		<input type="hidden" name="action" value="update">
		<table>
		<tr>
			<td>頻道號碼(不可更改)</td>
			<td>廣告分類(請輸入新值)</td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td><input type="text" name="頻道號碼" value="" readonly></td>
			<td><input type="text" name="廣告分類" value=""></td>
			<td><input type="button" value="取消"></td>
			<td><input type="submit" value="送出"></td>
		</tr>
		</table>
	</form>
</fieldset>
</body>