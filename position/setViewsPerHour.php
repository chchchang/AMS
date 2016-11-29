<?php
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){
		if($_POST['method']==='取得所有版位資料'){
			$my=new MyDB(true);
			$sql='SELECT 版位識別碼,上層版位識別碼,版位名稱,版位說明 FROM 版位 WHERE DISABLE_TIME IS NULL AND DELETED_TIME IS NULL ORDER BY 版位識別碼';
			$result=$my->getResultArray($sql);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($result);
			exit;
		}
		else if($_POST['method']==='取得版位曝光數'&&isset($_POST['版位識別碼'])){
			$my=new MyDB(true);
			$sql='SELECT 星期幾,曝光數0,曝光數1,曝光數2,曝光數3,曝光數4,曝光數5,曝光數6,曝光數7,曝光數8,曝光數9,曝光數10,曝光數11,曝光數12,曝光數13,曝光數14,曝光數15,曝光數16,曝光數17,曝光數18,曝光數19,曝光數20,曝光數21,曝光數22,曝光數23 FROM 曝光數 WHERE 版位識別碼=? ORDER BY 星期幾';
			$result=$my->getResultArray($sql,'i',$_POST['版位識別碼']);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($result);
			exit;
		}
		else if($_POST['method']==='寫入資料庫'&&isset($_POST['版位識別碼'])){
			//先刪除該版位識別碼所有曝光數記錄
			$my=new MyDB(true);
			$sql='DELETE FROM 曝光數 WHERE 版位識別碼=?';
			$stmt=$my->prepare($sql);
			$stmt->bind_param('i',$_POST['版位識別碼']);
			$result=$stmt->execute();
			//再新增該版位識別碼所有曝光數記錄
			$sql='
				INSERT INTO 曝光數(CREATED_PEOPLE,版位識別碼,星期幾,曝光數0,曝光數1,曝光數2,曝光數3,曝光數4,曝光數5,曝光數6,曝光數7,曝光數8,曝光數9,曝光數10,曝光數11,曝光數12,曝光數13,曝光數14,曝光數15,曝光數16,曝光數17,曝光數18,曝光數19,曝光數20,曝光數21,曝光數22,曝光數23)
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
			';
			$stmt=$my->prepare($sql);
			$stmt->bind_param('iiiiiiiiiiiiiiiiiiiiiiiiiii',
				$_SESSION['AMS']['使用者識別碼'],$_POST['版位識別碼'],$星期幾,$曝光數0,$曝光數1,$曝光數2,$曝光數3,$曝光數4,$曝光數5,$曝光數6,$曝光數7,$曝光數8,$曝光數9,$曝光數10,$曝光數11,$曝光數12,$曝光數13,$曝光數14,$曝光數15,$曝光數16,$曝光數17,$曝光數18,$曝光數19,$曝光數20,$曝光數21,$曝光數22,$曝光數23
			);
			for($i=0;$i<7;$i++){
				$星期幾=$i;
				$tmp='';
				for($j=0;$j<24;$j++){
					$tmp='曝光數'.$j;
					$$tmp=isset($_POST[$i.$j])?$_POST[$i.$j]:0;
				}
				$result=$stmt->execute();
			}
			//再回傳該版位識別碼所有曝光數記錄以便更新網頁上資訊
			$sql='SELECT 星期幾,曝光數0,曝光數1,曝光數2,曝光數3,曝光數4,曝光數5,曝光數6,曝光數7,曝光數8,曝光數9,曝光數10,曝光數11,曝光數12,曝光數13,曝光數14,曝光數15,曝光數16,曝光數17,曝光數18,曝光數19,曝光數20,曝光數21,曝光數22,曝光數23 FROM 曝光數 WHERE 版位識別碼=? ORDER BY 星期幾';
			$result=$my->getResultArray($sql,'i',$_POST['版位識別碼']);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($result);
			exit;
		}
		else if($_POST['method']==='下載為檔案'&&isset($_POST['版位識別碼'])){
			//將指定版位曝光數資料轉為整數並寫到檔案中
			$fp=fopen('download/'.intval($_POST['版位識別碼']).'.txt','w');
			$rows=array();
			for($i=0;$i<7;$i++){
				$row=array();
				for($j=0;$j<24;$j++){
					$row[$j]=isset($_POST[$i.$j])?intval($_POST[$i.$j]):0;
				}
				$rows[$i]=$row;
				fputs($fp,$i.','.join(',',$row)."\n");
			}
			fclose($fp);
			//再回傳該版位識別碼所有曝光數記錄以便更新網頁上資訊
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($rows);
			exit;
		}

	}
?>
<!DOCTYPE html>
<html>
<head>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<script src="../tool/jquery-1.11.1.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui/jquery-ui.css">
<script src="../tool/jquery-ui/jquery-ui.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css" />
<script>
	$(function(){
		$('#table1').hide();
		$('#link').hide();
		$('#div3').hide();
		$.post('?',{method:'取得所有版位資料'},function(data){
			所有版位資料=data;	//global
			$('#複製到右邊').click(複製到右邊);
			$('#複製到左邊').click(複製到左邊);
			$('#寫入資料庫').click(寫入資料庫);
			$('#下載為檔案').click(下載為檔案);
			$('#從檔案上傳').click(從檔案上傳);
			建立並顯示版位類型DataGrid();
		},'json');
	});
	
	function 建立並顯示版位類型DataGrid(){
	//產生DataGrid所需要的header
		var colNames=['版位類型識別碼','版位類型名稱','版位類型說明','設定此類型所有版位曝光數預設值','顯示此類型版位'];
	//產生DataGrid所需要的body
		var rows=[];
		var row;
		for(var i=0;i<所有版位資料.length;i++){
			if(所有版位資料[i].上層版位識別碼===null){
				row=[
					[所有版位資料[i]['版位識別碼']],
					[所有版位資料[i]['版位名稱']],
					[所有版位資料[i]['版位說明']],
					['設定此類型所有版位曝光數預設值','button'],
					['顯示此類型版位','button']
				];
				rows.push(row);
			}
		}
	//顯示DataGrid
		dataGrid1=new DataGrid('divDataGrid1',colNames,rows);	//global
	//處理DataGrid按鍵點擊event
		dataGrid1.buttonCellOnClick=function(y,x,row){
			if(row[x][0]=='設定此類型所有版位曝光數預設值'){
				if(dataGrid1.is_collapsed()){
					$('#divDataGrid2').hide();
					$('#table1').hide();
					dataGrid1.uncollapse();
				}
				else{
					dataGrid1.collapse_row(y);
					$('#divDataGrid2').hide();
					顯示設定曝光數區塊(row[0][0]);
					$('#table1').show();
				}
			}
			else{
				if(dataGrid1.is_collapsed()){
					$('#divDataGrid2').hide();
					$('#table1').hide();
					dataGrid1.uncollapse();
				}
				else{
					dataGrid1.collapse_row(y);
					建立並顯示版位DataGrid(row[0][0]);
					$('#divDataGrid2').show();
				}
			}
		}
	}
	
	function 建立並顯示版位DataGrid(版位類型識別碼){
		var rows=[];
		var row;
		for(var i=0;i<所有版位資料.length;i++){
			if(所有版位資料[i].上層版位識別碼===版位類型識別碼){
				row=[
					[所有版位資料[i]['版位識別碼']],
					[所有版位資料[i]['版位名稱']],
					[所有版位資料[i]['版位說明']],
					['設定此版位曝光數','button']
				];
				rows.push(row);
			}
		}
		if(typeof dataGrid2==='undefined'){
			var colNames=['版位識別碼','版位名稱','版位說明','設定此版位曝光數'];
			dataGrid2=new DataGrid('divDataGrid2',colNames,rows);	//global
			dataGrid2.buttonCellOnClick=function(y,x,row){
				if(dataGrid2.is_collapsed()){
					$('#table1').hide();
					dataGrid2.uncollapse();
				}
				else{
					dataGrid2.collapse_row(y);
					顯示設定曝光數區塊(row[0][0]);
					$('#table1').show();
				}
			}
		}
		else
			dataGrid2.set_data(rows);
	}
	
	function 顯示設定曝光數區塊(版位識別碼){
		$('#link').hide();
		$.post('?',{method:'取得版位曝光數',版位識別碼:版位識別碼},function(data){
			var rows=[];
			if(data!==null){
				for(var i=0;i<data.length;i++){
					rows[data[i].星期幾]=data[i];
				}
			}
			var table='<form><table border="1"><tr><td>時段</td><td>日</td><td>一</td><td>二</td><td>三</td><td>四</td><td>五</td><td>六</td></tr>';
			for(var i=0;i<24;i++){
				table+='<tr><td>時段'+i+'</td>';
				for(var j=0;j<7;j++){
					table+='<td><input type="text" name="'+j+i+'" maxlength="4" size="4" value="'+(typeof rows[j]==='undefined'?'':rows[j]['曝光數'+i])+'"></td>';
				}
				table+='</tr>';
			}
			table+='</table><input type="hidden" name="method" value="寫入資料庫"><input type="hidden" name="版位識別碼" value="'+版位識別碼+'"></form>';
			$('#div1').html(table);
			$('#div2').html(table);
			$('#div2 form input[type=text]').val('');
			$('#div2 form input[type=hidden][value=寫入資料庫]').val('下載為檔案');
			$('#table1').show();
		},'json');
	}
	
	function 複製到右邊(){
		if(confirm('確定複製到右邊？')){
			for(var i=0;i<7;i++){
				for(var j=0;j<24;j++){
					$('#div2 table input[name='+i+j+']').val($('#div1 table input[name='+i+j+']').val());
				}
			}
		}
	}
	
	function 複製到左邊(){
		if(confirm('確定複製到左邊？')){
			for(var i=0;i<7;i++){
				for(var j=0;j<24;j++){
					$('#div1 table input[name='+i+j+']').val($('#div2 table input[name='+i+j+']').val());
				}
			}
		}
	}
	
	function 寫入資料庫(){
		if(confirm('確定寫入資料庫？')){
			$.post('?',$('#div1 form').serialize(),function(data){
				for(var i=0;i<7;i++){
					for(var j=0;j<24;j++){
						$('#div1 form input[name='+i+j+']').val(data[i]['曝光數'+j]);
					}
				}
				alert('寫入完成。')
			},'json');
		}
	}
	
	function 下載為檔案(){
		if(confirm('確定下載為檔案？')){
			$.post('?',$('#div2 form').serialize(),function(data){
				for(var i=0;i<7;i++){
					for(var j=0;j<24;j++){
						$('#div2 form input[name='+i+j+']').val(data[i][j]);
					}
				}
				$('#link').attr({target:'_blank',href:'download/'+$('#div2 input[name=版位識別碼]').val()+'.txt'});
				$('#link').show();
			},'json');
		}
	}
	
	function 從檔案上傳(){
		$('#div3').dialog({title:'請將檔案內容複製後貼到下方輸入框',width:950,resizable:false,modal:true,buttons:[{text:'確定',click:上傳檔案},{text:'取消',click:function(){$(this).dialog('close')}}]});
	}
	
	function 上傳檔案(){
		var rows=$('#file_contents').val().split("\n");
		if(rows.length<7){
			alert('資料行數必須要有7行！');
		}
		else{
			var cols;
			for(var i=0;i<7;i++){
				cols=rows[i].split(',');
				if(cols.length!=25||cols[0]!=i){
					alert('第'+(i+1)+'行格式不正確！');
					return;
				}
				rows[i]=cols;
			}
			if(confirm('確定從檔案上傳？')){
				for(var i=0;i<7;i++){
					for(var j=0;j<24;j++){
						$('#div2 form input[name='+i+j+']').val(rows[i][j+1]);
						$(this).dialog('close');
					}
				}
			}
		}
	}
</script>
</head>
<body>
<div id="divDataGrid1"></div>
<div id="divDataGrid2"></div>
<table id="table1" align="center">
<tr><td>資料庫暫存區</td><td></td><td>檔案暫存區</td></tr>
<tr>
<td>
	<div id="div1"></div>
</td>
<td>
	<button id="複製到右邊">複製到右邊<br>-></button><br>
	<button id="複製到左邊">複製到左邊<br><-</button>
</td>
<td>
	<div id="div2"></div>
</td>
</tr>
<tr>
	<td><button id="寫入資料庫">寫入資料庫</button></td>
	<td></td>
	<td><button id="下載為檔案">下載為檔案</button><a id="link">點此下載</a><button id="從檔案上傳">從檔案上傳</button></td>
</tr>
</table>
<div id="div3">
<textarea id="file_contents" rows="7" cols="125" placeholder=
"<範例>
0,149,82,45,28,20,29,63,147,294,440,545,458,367,380,388,379,369,341,296,277,276,262,231,151
1,103,50,26,19,15,32,91,142,209,263,303,256,204,265,262,260,270,290,263,287,300,284,243,144
2,83,46,28,17,14,33,93,137,193,247,292,251,204,256,270,265,278,285,253,287,297,270,246,146
3,80,42,25,18,13,29,92,134,193,237,267,211,177,230,237,239,245,265,233,270,287,259,229,137
4,79,43,26,16,13,32,94,131,186,230,255,210,170,226,235,239,237,271,255,289,312,284,247,151
5,113,57,33,21,16,42,127,177,259,334,368,313,261,328,346,349,351,403,339,378,390,384,338,229
6,142,78,42,27,20,34,79,185,353,503,581,522,410,462,471,456,437,404,335,323,318,311,269,203">
</textarea>
</div>
</body>
</html>