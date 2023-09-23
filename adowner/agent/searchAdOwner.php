<?php
	include('../tool/auth/auth.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"/>
<?php
	include('../tool/sameOriginXfsBlock.php');
?>
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css' />
<script type="text/javascript" src="../tool/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../tool/ajax/ajaxToDB.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<script type="text/javascript" src="../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../tool/jquery-plugin/jquery.placeholder.min.js"></script>
</head>
<body>

<div class = "basicBlock">
<div id="searchForm">
<input id = "shearchText" type ="text" value = ""  class="searchInput" placeholder="輸入廣告主名稱、承銷商名稱、頻道商名稱查詢"></input><input type ="button" id = "searchButton" class="searchSubmit" value="查詢">
</div>
</div>

<div id = "datagrid"></div>
<div id = "datagrid2"></div>
<div id = "datagrid3"></div>
<script type="text/javascript">
	var showAminationTime = 500;
	//是否指定顯示的廣告主
	var selectedId = "<?php if(isset($_GET['ownerid'])) echo htmlspecialchars($_GET['ownerid'], ENT_QUOTES, 'UTF-8'); ?>";
	if(selectedId!="")
		$("#searchForm").hide();
	
	
	$(function() {
		//按下enter查詢
		$("#shearchText").keypress(function(event){
			if (event.keyCode == 13){
				ODG.search();
				$("#datagrid2").empty();
				$("#datagrid3").empty();
			}
		});
		// 幫有 placeholder 屬性的輸入框加上提示效果
		$('input[placeholder]').placeholder();
	});
	
	var ajaxtodbPath = "ajaxToDB_Adowner.php";
	var g_numPerPage=10;
	/**向司服器要求廣告主資料數目**/
	var ODG;//存放廣告組資料表用
	var TDG;//存放託播單資料表用
	var ownerQuery=[];
	ownerQuery["basic"] ="action=廣告主資料表";
	ownerQuery["sort"] = "&ORDER=廣告主識別碼";
	ownerQuery["page"] ="&PAGE="+0;
	if(selectedId=="")
		ownerQuery["where"] = "&WHERE=1";
	else
		ownerQuery["where"] = "&WHERE=廣告主識別碼="+selectedId;
	var ownerAttribute =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱"];
	
	//確認資料頁數
	ajax_to_db(
		"action=getCount&TABLE=廣告主"+ownerQuery["where"],ajaxtodbPath,
		function(data){
			var result=$.parseJSON(data);
			if(result["dbError"]!=undefined){
				alert(result["dbError"]);
				return 0;
			}
			var totalPage = Math.ceil((result[0][0])/g_numPerPage);

			header =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱","詳細資料","購買紀錄"];
			ODG=new OwnerDataGrid(header,totalPage,ownerQuery,ownerAttribute);
			
			$("#searchButton").click(function(){
				ODG.search();
				$("#datagrid2").empty();
				$("#datagrid3").empty();
				closeOwnerInfoTable();
			});
		}
	);
	
	/**關閉廣告主詳細資訊視窗**/
	function closeOwnerInfoTable(){
		if($(".InfoWindow").length>0){
			$(".InfoWindow").remove();
			ODG.uncollapse();
		}
	}
	
	
	/**建立廣告主表單**/
	function OwnerDataGrid(header,totalPage,query,attribute){
		//var header =["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱","詳細資料","購買紀錄"];	
		var mydg = new DataGrid('datagrid',header);
		mydg.set_sortable(["廣告主識別碼","廣告主名稱","頻道商名稱","承銷商名稱"],true);
		setPage(totalPage);
		updateData();
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
		};
		
		/**設定頁數資訊**/
		function setPage(totalPage){
			mydg.set_page_info(1,totalPage);
			//覆寫改變頁數時的動作
			mydg.pageChange = function(toPage){
				var startN = (toPage-1)*g_numPerPage;
				query["page"] ="&PAGE="+startN;
				updateData();
			};
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
						pushArr.push(["詳細資料","button"]);
						pushArr.push(["購買紀錄","button"]);
						dataArr.push(pushArr);
					}
					mydg.set_data(dataArr);
				}
			); 
		}
		
		//按鈕被點擊
		mydg.buttonCellOnClick= function(row,column,rowdata){
			if($(".InfoWindow").length>0)
				$(".InfoWindow").remove();
				$("#datagrid2").empty();
				$("#datagrid3").empty();
				
			//詳細資料
			if(!mydg.is_collapsed()){
				if(column == 4){
					$('body').append('<iframe id="ownerInfo" name="ownerInfo" class = "InfoWindow" scrolling="no">');
					$('#ownerInfo')
					.attr("src",'ownerInfoTable.php?ownerid='+rowdata[0][0])
					.css("width","100%").hide().fadeIn(showAminationTime);
					//.height(650);
				}
				//購買紀錄
				else if(column == 5){
					var infoQuery=[];
					infoQuery["basic"] ="action=委刊單資料表&廣告主識別碼="+rowdata[0][0];
					infoQuery["sort"] = "&ORDER=委刊單識別碼&SORT=ASC";
					infoQuery["page"] = "&PAGE="+0;
					var infoAttribute =["委刊單識別碼","委刊單編號","委刊單名稱","CREATED_TIME","LAST_UPDATE_TIME","託播單狀態"];
					//確認資料數目
					ajax_to_db(
						"action=getCount&TABLE=委刊單&WHERE=廣告主識別碼="+rowdata[0][0],ajaxtodbPath,
						function(data){
							var result=$.parseJSON(data);
							if(result["dbError"]!=undefined){
								alert(result["dbError"]);
								return 0;
							}
							var totalPage = Math.ceil(result[0][0]/g_numPerPage);
							
							
							ajax_to_db(
								infoQuery["basic"]+infoQuery["sort"]+infoQuery["page"]+"&PNUMBER="+g_numPerPage,ajaxtodbPath,
								function(data){
									var result=$.parseJSON(data);
								if(result["dbError"]!=undefined){
									alert(result["dbError"]);
									return 0;
								}
									var dataArr=[];
									for(var i in result){
										var pushArr=[];
										for(var j in infoAttribute)
											pushArr.push([result[i][infoAttribute[j]],"text"]);
										pushArr.push(["託播單資料","button"]);
										dataArr.push(pushArr);
									}
									creatOrderListDataGrid(dataArr,totalPage,infoQuery,infoAttribute);
								}
							);
							
							
						}
					);
				}
				if(selectedId=="")
					mydg.collapse_row(row);
			}
			else{
				mydg.uncollapse();
			}
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
				}
			);
		}
		
		this.uncollapse = function(){
			mydg.uncollapse();
		}
	}
	
	
	
	
	/**建立委刊表單**/
	function creatOrderListDataGrid(dataArr,totalPage,query,attribute){
		//query用共同參數
		var header =["委刊單識別碼","委刊單編號","委刊單名稱","建立時間","修改時間","託播單狀態","託播單資料"];
		var mydg = new DataGrid('datagrid2',header,dataArr);
		
		mydg.set_sortable(["委刊單識別碼","委刊單編號","委刊單名稱","建立時間","修改時間"],true);
		
		//覆寫header被點擊時的動作
		mydg.headerOnClick = function(headerName,sort){
			var hindex=$.inArray( headerName, header);
			var orderAtt=attribute[hindex];
			switch(sort){
				case "increase":
					query["sort"] = "&ORDER="+orderAtt+"&SORT=ASC"
					break;
				case "decrease":
					query["sort"] = "&ORDER="+orderAtt+"&SORT=DESC"
					break;
				case "unsort":
					break;
			}
			updateData();
		};
		
		
		//設定頁數資訊
		if(totalPage>1){
			mydg.set_page_info(1,totalPage);
			//覆寫改變頁數時的動作
			mydg.pageChange = function(toPage){
				var startN = (toPage-1)*g_numPerPage;
				query["page"] = "&PAGE="+startN;
				updateData();
			};
		}
		
		/**更新資料**/
		function updateData(){
			ajax_to_db(
				query["basic"]+query["sort"]+query["page"]+"&PNUMBER="+g_numPerPage,ajaxtodbPath,
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
							pushArr.push([result[i][attribute[j]],"text"]);
						pushArr.push(["詳細資料","button"]);
						dataArr.push(pushArr);
					}
					mydg.set_data(dataArr);
				}
			);
		}
		
		//按鈕被點擊
		mydg.buttonCellOnClick= function(row,column,rowdata){
			if(!mydg.is_collapsed()){
				mydg.collapse_row(row);
				showOrderDG(mydg.getCellText('委刊單識別碼',row));
			}
			else{
				mydg.uncollapse();
				$("#datagrid3").empty();
			}
		}
		
		$("#datagrid2").hide().slideDown(showAminationTime);
	}
	
	
	/**建立託播表單**/

	//顯示搜尋的託播單列表
	function showOrderDG(orderListId){
		$('#datagrid3').html('');
		var bypost={
				委刊單識別碼:orderListId
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
		//取得資料
		bypost['method']='OrderInfoBySearch';
		$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('詳細資料');
				for(var row in json.data){
						json.data[row].push(['詳細資料','button']);
				}
				
				var DG=new DataGrid('datagrid3',json.header,json.data);
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
				DG.buttonCellOnClick=function(row,column,rowdata) {
					var Col1 = $.inArray('託播單識別碼',json.header);
					if(!DG.is_collapsed()){
						DG.collapse_row(row);
						if($(".InfoWindow").length>0)
							$(".InfoWindow").remove();
						$('body').append('<iframe id="adInfo" name="adInfo" class = "InfoWindow" scrolling="no">');
						$('#adInfo')
						.attr("src",'../order/orderInfo.php?name='+rowdata[Col1][0])
						.css("width","100%").hide().fadeIn(showAminationTime);
					}
					else{
						$(".InfoWindow").remove();
						DG.uncollapse();
					}

				}
				
				DG.update=function(){
					$.post('../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							for(var row in json.data){
									json.data[row].push(['詳細資料','button']);
							}
							DG.set_data(json.data);
						},'json');
				}
				
				$("#datagrid3").hide().slideDown(showAminationTime);
			}
			,'json'
		);
	}
	
</script>
</body>
</html>