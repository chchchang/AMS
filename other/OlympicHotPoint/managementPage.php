<?php 
	include('../../tool/auth/authAJAX.php');
	include('../../tool/phpExtendFunction.php');
    //define("GETHOTPOINTURLNAME","http://localhost/Olympic2021HotPointUrl/api/getHotPointURLName.php");
    //define("GETHOTPOINTURL","http://localhost/Olympic2021HotPointUrl/api/getHotPointURLDataByName.php");
	define("GETHOTPOINTURLNAME","http://172.17.254.141/hmiin/Olympic2021HotPointUrl/api/getHotPointURLName.php");
    define("GETHOTPOINTURL","http://172.17.254.141/hmiin/Olympic2021HotPointUrl/api/getHotPointURLDataByName.php");
	if(isset($_POST['method'])){
		if($_POST['method'] == '更改託播單'){
			$url = $_POST['url'];
			foreach($_POST['orders'] as $key=>$id){
				$sql='
					UPDATE 託播單素材
					SET 點擊後開啟類型 = "external" , 點擊後開啟位址 = ?
					WHERE 託播單識別碼=?
					';
				if(!$stmt=$my->prepare($sql)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('si',$url,$id)) {
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->execute()) {
					exit('無法執行statement，請聯絡系統管理員！');
				}
			}
			exit(json_encode(array('success'=> true),JSON_UNESCAPED_UNICODE));
		}
		else if($_POST['method'] == '取得所有URL名稱'){
			$apiResult=PHPExtendFunction::connec_to_Api(GETHOTPOINTURLNAME,"GET",null);
			if($apiResult["success"]){
				exit($apiResult["data"]);
			}
			else{
				exit(json_encode(array("success"=>false,"message"=>"API連接失敗")));
			}
		}
		else if($_POST['method'] == '依名稱取得URL內容'){
			$apiResult=PHPExtendFunction::connec_to_Api(GETHOTPOINTURL."?URLName=".urlencode($_POST["URLName"]),"GET",null);
			if($apiResult["success"]){
				exit($apiResult["data"]);
			}else{
				exit(json_encode(array("success"=>false,"message"=>"API連接失敗")));
			}
		}
		exit(json_encode(array("success"=>false,"message"=>"ajax參數錯誤")));
	}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<script type="text/javascript" src="../../tool/jquery-3.4.1.min.js"></script>
<link rel="stylesheet" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery-ui1.2/jquery-ui.css">
<script src="../../tool/jquery-ui1.2/jquery-ui.js"></script>
<script src="../tool/HtmlSanitizer.js"></script>
<script type="text/javascript" src="../../tool/datagrid/CDataGrid.js"></script>
<script type="text/javascript" src="../../tool/autoCompleteComboBox.js"></script>
<script type="text/javascript" src="../../tool/jquery-plugin/jquery.placeholder.min.js"></script>
<script src="../../tool/jquery.loadmask.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
<script src="../../tool/vue/vue.min.js"></script>
</head>
<body>
<div id = "vueapp">
<table>
   <tr><th>使用的熱點回看URL:</th><td>
    <select name="hotPointURLNameSelect" id="hotPointURLNameSelect" v-model="hotPointURLName">
        <option v-for="option in hotPointURLNameOption" :value="option.value" >{{option.text}}</option>
    </select>
	</td>
	</tr>
	<tr>
	<th>URL內容:</th>
	<td>
    <input id = "hotpotinURL" style="width:600px" :value="hotPointURL" readonly>
	</td>
	</tr>
</table>
</div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
<?php include("../../order/_searchOrderUI.php")?>
<div style='float:right'>
<button id = 'selectall' class='darkButton'>全選</button> <button id = 'unselectall' class='darkButton'>取消全選</button> <button id = 'selectCurrent' class='darkButton'>全選本頁</button> <button id = 'unselectCurrent' class='darkButton'>取消本頁</button>
</div>
<p style='clear:both'><br></p>
<div id = "datagrid" style='clear:both'></div>
<div class ='basicBlock Center'>
<button id = 'checkall'>確定託播單</button>
</div>
</body>
<script>
	var DG = null;
	$(function() {
		$('#selectall,#uncheckall,#checkall,#unselectall,#selectCurrent,#unselectCurrent').hide();
		$( "#dialog_form" ).dialog( {autoOpen: false, modal: true} );
	});
	
	
	//顯示搜尋的委刊單列表
	var OrderSelectedOrNot={};//記錄託播單是否被選擇 結構:{orderId:true/false}
	function showOrderDG(){
		$('#selectall,#uncheckall,#checkall,#unselectall,#selectCurrent,#unselectCurrent').show();
		$('#datagrid').html('');
		OrderSelectedOrNot={};
		var bypost={
				searchBy:$('#_searchOUI_searchOrder').val()
				,廣告主識別碼:$('#_searchOUI_adOwner').val()
				,委刊單識別碼:$( "#_searchOUI_orderList" ).val()
				,開始時間:$('#_searchOUI_startDate').val()
				,結束時間:$('#_searchOUI_endDate').val()
				,狀態:$('#_searchOUI_orderStateSelectoin').val()
				,版位類型識別碼:$('#_searchOUI_positiontype').val()
				,版位識別碼:$('#_searchOUI_position').val()
				,素材識別碼:$('#_searchOUI_material').val()
				,素材群組識別碼:$('#_searchOUI_materialGroup').val()
				,全託播單識別碼狀態:[0,1]
				,pageNo:1
				,order:'託播單識別碼'
				,asc:'DESC'
			};
		//取的全部的託播單識別碼並建立是否選擇的map
		bypost['method']='全託播單識別碼';
		$.post('../../order/ajaxFunction_OrderInfo.php',bypost,function(json){
			for(var row in json){
				OrderSelectedOrNot[json[row]] = false;
			}
		}
		,'json'
		);
		//取得資料
		bypost['method']='OrderInfoBySearch';
		delete bypost['全託播單識別碼狀態'];
		$.post('../../order/ajaxFunction_OrderInfo.php',bypost,function(json){
				json.header.push('選擇');
				var stateCol = $.inArray('託播單狀態',json.header);
				for(var row in json.data){
					if(json.data[row][stateCol][0]=='預約'||json.data[row][stateCol][0]=='確定'){
						if(OrderSelectedOrNot[json.data[row][0][0]])
							json.data[row].push(['<input type="checkbox" checked value='+json.data[row][0][0]+'></input>','html']);
						else
							json.data[row].push(['<input type="checkbox" value='+json.data[row][0][0]+'></input>','html']);
					}else
						json.data[row].push(['','text']);
				}
							
				DG=new DataGrid('datagrid',json.header,json.data);
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
				DG.buttonCellOnClick=function(y,x,row) {
					if(row[x][0]=='選擇') {
						$("#dialog_iframe").attr("src",encodeURI("../../order/newOrder.php?saveBtnText=修改託播單&update="+row[0][0]))
						.css({"width":"100%","height":"100%"}); 
						dialog=$( "#dialog_form" ).dialog({height: $(window).height()*0.8, width:$(window).width()*0.8, title:"編輯託播單"});
						dialog.dialog( "open" );
					}
				}				
				
				DG.update=function(){
					$.post('../../order/ajaxFunction_OrderInfo.php',bypost,function(json) {
							var stateCol = $.inArray('託播單狀態',json.header);
							for(var row in json.data){
								if(json.data[row][stateCol][0]=='預約'||json.data[row][stateCol][0]=='確定'){
									if(OrderSelectedOrNot[json.data[row][0][0]])
										json.data[row].push(['<input type="checkbox" checked value='+json.data[row][0][0]+'></input>','html']);
									else
										json.data[row].push(['<input type="checkbox" value='+json.data[row][0][0]+'></input>','html']);
								}else
									json.data[row].push(['','text']);
							}
							DG.set_data(json.data);
							$('#datagrid').find('input[type="checkbox"]').each(function(){
								$(this).change(function(){
									OrderSelectedOrNot[$(this).val()]=$(this).prop("checked");
								});
							});
						},'json');
				}
				
				$('#datagrid').find('input[type="checkbox"]').each(function(){
					$(this).change(function(){
						if($(this).prop("checked"))
							OrderSelectedOrNot[$(this).val()]=true;
						else
							OrderSelectedOrNot[$(this).val()]=false;
					});
				});
			}
			,'json'
		);
	}
	
	//全選與全不選
	//全選
	$('#selectall').click(function(){
		for(var id in OrderSelectedOrNot)
			OrderSelectedOrNot[id] = true;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
		});
	});
	//全不選
	$('#unselectall').click(function(){
		for(var id in OrderSelectedOrNot)
			OrderSelectedOrNot[id] = false;
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
		});
	});
	//全選本頁
	$('#selectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", true);
			OrderSelectedOrNot[$(this).val()] = true;
		});
	});
	//全不選本頁
	$('#unselectCurrent').click(function(){
		$('#datagrid').find('input[type="checkbox"]').each(function() {
			$(this).prop("checked", false);
			OrderSelectedOrNot[$(this).val()] = false;
		});
	});
	
	//更改託播單
	$('#checkall').click(function(){
		$('body').mask('託播單更改中...');
		orders =[];
		for(var id in OrderSelectedOrNot)
			if(OrderSelectedOrNot[id])
				orders.push(id);
		if(orders.length==0){
			alert('未選擇任何託播單');
			$('body').unmask();
			return 0 ;
		}
		$.post('?',{method:'更改託播單',orders:orders,url:$("#hotpotinURL").val()},function(json){
			if(json.success){
				alert('勾選的託播單已確定');
				showOrderDG();
			}
			$('body').unmask();
		}
		,'json'
		);
	});
	
	new Vue({
        el: '#vueapp',
        data:{
            hotPointURLName: "",
            hotPointURL:"",
            hotPointURLNameOption:[]
        }
        ,mounted(){
            $.post('?',{method:'取得所有URL名稱'}
            ,(ajaxdata=>{
                ajaxdata["data"].forEach(
                    element => {
                        this.hotPointURLNameOption.push({"value":element,"text":element});
                    }
                );
            })
            ,'json'
            );
        },
        watch:{
            hotPointURLName: function(val,oldval){
                if(val!=""){
                    $.post('?',{method:'依名稱取得URL內容',URLName:this.hotPointURLName}
                    ,(ajaxdata=>{
                         this.hotPointURL = ajaxdata["data"];
                    })
                    ,'json'
                    );  
                }
            }
        }

    });
</script>
</html>