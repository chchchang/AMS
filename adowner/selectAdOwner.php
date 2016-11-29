<?php
	include('../tool/auth/authAJAX.php');
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
<input id = "shearchText" type ="text" value = ""  class="searchInput"  placeholder="輸入廣告主名稱、承銷商名稱、頻道商名稱查詢"></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>

<div id = "datagrid"></div>

<script type="text/javascript">
	//按下enter查詢
	$(function() {
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
	ownerQuery["basic"] ="action=廣告主資料表";
	ownerQuery["where"] = "&WHERE=1";
	ownerQuery["sort"] = "&ORDER=廣告主識別碼";
	ownerQuery["page"] ="&PAGE="+0;
	var ownerAttribute =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱"];
	
	ajax_to_db(
		"action=getCount&TABLE=廣告主&WHERE=1",ajaxtodbPath,
		function(data){
			var result=$.parseJSON(data);
			if(result["dbError"]!=undefined){
				alert(result["dbError"]);
				return 0;
			}
			var totalPage = Math.ceil((result[0][0])/g_numPerPage);
				header =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱","選擇"];
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
		mydg.set_sortable(["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱"],true);
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
			parent.adOwnerSelected(rowdata[0][0],rowdata[1][0]);
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
						pushArr.push(["選擇","button"]);
						dataArr.push(pushArr);
					}
					mydg.set_data(dataArr);
				}
			); 
		}
		/**隱藏修改視窗**/
		function hideInfoWindow(){
			if($(".InfoWindow").length>0){
				$(".InfoWindow").remove();
			}
			if(mydg.is_collapsed())
				mydg.uncollapse();
		}
		
		
		/**搜尋動作**/
		this.search = function(){
			var filter = $("#shearchText").val()
			if(filter!="")
				query["where"]= "&WHERE=" + ownerAttribute.join(escape(" LIKE '%") + filter + escape("%' or ")) + escape(" LIKE '%") +filter+ escape("%' ");	
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
	}

	
	function closeOwnerInfoTable_edit(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
			ODG.uncollapse();
		}
	}

	
</script>
</body>
</html>