<?php
	include('../tool/auth/auth.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '隱藏廣告主'){
			$sql='
				UPDATE 廣告主 SET DISABLE_TIME=CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 廣告主識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["廣告主識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'隱藏廣告主(廣告主識別碼:'.$_POST["廣告主識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '顯示廣告主'){
			$sql='
				UPDATE 廣告主 SET DISABLE_TIME=NULL, LAST_UPDATE_PEOPLE=? WHERE 廣告主識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["廣告主識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'顯示廣告主(廣告主識別碼:'.$_POST["廣告主識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '刪除廣告主'){
			//統計廣告主下的託播單
			$sql='
				SELECT COUNT(*) AS count FROM 託播單,委刊單 WHERE 託播單.委刊單識別碼=委刊單.委刊單識別碼 AND 廣告主識別碼=?
			';
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->bind_param('i',$_POST["廣告主識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			if(!$res=$stmt->get_result()) {
				exit(json_encode(array("success"=>false,"message"=>'無法取得結果集，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$row = $res->fetch_assoc();
			if($row['count']>0)
				exit(json_encode(array("success"=>false,"message"=>'此廣告主已有託播單，無法刪除。'),JSON_UNESCAPED_UNICODE));
			
			$sql='
				UPDATE 廣告主 SET DELETED_TIME=CURRENT_TIMESTAMP, LAST_UPDATE_PEOPLE=? WHERE 廣告主識別碼=?
			';
			
			if(!$stmt=$my->prepare($sql)) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法準備statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->bind_param('ii',$_SESSION['AMS']['使用者識別碼'],$_POST["廣告主識別碼"])){
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法繫結資料，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			
			if(!$stmt->execute()) {
				$my->close();
				exit(json_encode(array("success"=>false,"message"=>'無法執行statement，請聯絡系統管理員！'),JSON_UNESCAPED_UNICODE));
			}
			$logger->info('使用者代碼:'.$_SESSION['AMS']['使用者識別碼'].'刪除廣告主(廣告主識別碼:'.$_POST["廣告主識別碼"].')');
			exit(json_encode(array("success"=>true,"message"=>'修改成功'),JSON_UNESCAPED_UNICODE));
		}
	}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-1.11.1.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script> 
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
</head>
<body>
<div class = "basicBlock">
<div>
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入廣告主名稱、承銷商名稱、頻道商名稱查詢"></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>

<div id = "datagrid"></div>

<script type="text/javascript">
	var showAminationTime = 500;

	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){       
			if (event.keyCode == 13) 
				ODG.search();
		});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});


	var ajaxtodbPath = "ajaxToDB_Adowner.php";
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var ODG;//預備用來放datagrid的物件
	var ownerQuery=[];
	ownerQuery["basic"] ="action=廣告主資料表&SHOWHIDE=true";
	ownerQuery["where"] = "&WHERE=1";
	ownerQuery["sort"] = "&ORDER=廣告主識別碼";
	ownerQuery["page"] ="&PAGE="+0;
	var ownerAttribute =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱",'狀態'];
	
	ajax_to_db(
		"action=getCount&TABLE=廣告主&WHERE=1",ajaxtodbPath,
		function(data){
			var result=$.parseJSON(data);
			if(result["dbError"]!=undefined){
				alert(result["dbError"]);
				return 0;
			}
			var totalPage = Math.ceil((result[0][0])/g_numPerPage);
				/**向司服器要求廣告主資料**/
				header =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱",'狀態',"修改",'隱藏','刪除'];
				ODG=new OwnerDataGrid(header,totalPage,ownerQuery,ownerAttribute);
				$("#searchButton").click(function(){
					ODG.search();
				});
		}
	);
	
	/**建立廣告主表單**/
	function OwnerDataGrid(header,totalPage,query,attribute){
		var mydg = new DataGrid('datagrid',header);
		var m_this= this;
		
		mydg.set_header(header);
		mydg.set_sortable(["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱",'狀態'],true);
		updateData();
		setPage(totalPage);
		//覆寫header被點擊時的動作
		mydg.headerOnClick = function(headerName,sort){
			var hindex=$.inArray( headerName, header);
			var orderAtt=attribute[hindex];
			switch(sort){
				case "increase":
					query["sort"] ="&ORDER="+orderAtt+"&SORT=ASC"
					break;
				case "decrease":
					query["sort"] ="&ORDER="+orderAtt+"&SORT=DESC"
					break;
				case "unsort":
					break;
			}
			updateData();
			hideInfoWindow();
		};
		
		
		//按鈕被點擊
		mydg.buttonCellOnClick= function(row,column,rowdata){
			//alert(row+" "+column+" "+"["+rowdata+"]")
			//修改
			if(rowdata[column][0] == '修改'){
				if($(".InfoWindow").length>0)
					$(".InfoWindow").remove();
				$('body').append('<iframe id="ownerInfo" name="ownerInfo" class = "InfoWindow">');
				$('#ownerInfo')
				.attr("src",'ownerInfoTable_edit.php?ownerid='+rowdata[0][0])
				.css("width","100%")
				.hide().fadeIn(showAminationTime);
				if(!mydg.is_collapsed())
					mydg.collapse_row(row);
				else
					hideInfoWindow();
			}
			else if(rowdata[column][0] == '隱藏'){
				$.post('?',{method:'隱藏廣告主','廣告主識別碼':rowdata[0][0]}
					,function(json){
						if(json.success)
							updateData();
					}
					,'json'
				);
			}
			else if(rowdata[column][0] == '顯示'){
				$.post('?',{method:'顯示廣告主','廣告主識別碼':rowdata[0][0]}
					,function(json){
						if(json.success)
							updateData();
					}
					,'json'
				);
			}
			else if(rowdata[column][0] == '刪除'){
				if(confirm("確定要刪除廣告主?"))
				$.post('?',{method:'刪除廣告主','廣告主識別碼':rowdata[0][0]}
					,function(json){
						if(json.success)
							updateData();
						else{
							alert(json.message);
						}
					}
					,'json'
				);
			}
			
		}
				
		/**設定頁數資訊**/
		function setPage(totalPage){
			if(totalPage>1){
				mydg.set_page_info(1,totalPage);
				//覆寫改變頁數時的動作
				mydg.pageChange = function(toPage){
					var startN = (toPage-1)*g_numPerPage;
					query["page"] ="&PAGE="+startN;
					updateData();
				};
			}	
		}
		
		/**更新資料**/
		function updateData(){
			ajax_to_db(
				query["basic"]+query["where"]+query["sort"]+query["page"]+"&PNUMBER="+g_numPerPage,ajaxtodbPath,
				function(data){
					var result=$.parseJSON(data);
					if(result["dbError"]!=undefined){
							alert(result["dbError"]);
							return 0;
						}
					var dataArr=[];
					for(var i in result){
						var pushArr=[];
						for(var j in attribute)
							pushArr.push([result[i][ownerAttribute[j]],"text"]);
							
						if(result[i]['狀態']=='顯示')
							pushArr.push(["修改","button"],["隱藏","button"],["刪除","button"]);
						else
							pushArr.push(["修改","button"],["顯示","button"],["刪除","button"]);
						dataArr.push(pushArr);
					}
					mydg.set_data(dataArr);
				}
			); 
		}

		
		
		/**搜尋動作**/
		this.search = function(){
			var filter = $("#shearchText").val()
			if(filter!="")
				query["where"]= "&WHERE=" + ownerAttribute.slice(0, ownerAttribute.length-1).join(escape(" LIKE '%") + filter + escape("%' or ")) + escape(" LIKE '%") +filter+ escape("%' ");	
			else
				query["where"]="&WHERE=1";
			//更新頁數資訊
			ajax_to_db(
				"action=getCount&TABLE=廣告主&WHERE="+query["where"],ajaxtodbPath,
				function(data){
					var result=$.parseJSON(data);
					if(result["dbError"]!=undefined){
							alert(result["dbError"]);
							return 0;
						}
					var totalPage = Math.ceil((result[0][0])/g_numPerPage);
					query["page"] ="&PAGE="+0;
					setPage(totalPage);
					updateData();
					hideInfoWindow();
				}
			);
		}
		
		this.uncollapse = function(){
			mydg.uncollapse();
		}
		
		this.updateData = function(){
			hideInfoWindow();
			updateData();
		}
	}

		/**隱藏修改視窗**/
	function hideInfoWindow(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
		}
		ODG.uncollapse();
	}
	
	function AdOwnerUpdated(){
		ODG.updateData();
	}

	
</script>
</body>
</html>