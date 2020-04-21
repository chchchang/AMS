<?php
	include('tool/auth/auth.php');
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	include('tool/sameOriginXfsBlock.php');
	?>
	<link rel='stylesheet' type='text/css' href='<?=$SERVER_SITE.Config::PROJECT_ROOT?>external-stylesheet.css'/>
	<script language="javascript" src="tool/jquery-3.4.1.min.js"></script>
	<script src="tool/tree.js"></script>
	<link rel="stylesheet" type="text/css" href="<?=$SERVER_SITE.Config::PROJECT_ROOT?>tool/jquery.loadmask.css" />
	<script src="tool/jquery.loadmask.js"></script>
<style type="text/css">
.tree{
	position: absolute;
	top: 10px;
	bottom: 10px;
	right:5px;
	left:5px;
	padding-left:20px; 
	box-shadow: #666 0px 2px 3px;
	overflow-x:hidden;
	overflow-y:auto;
	-pie-background: linear-gradient(180deg, #fafafa, #fdfdfd);
	border-style: solid;
	border-width: 1px;
    border-color: #e0e0e0 ;
	/*IE scrollbar 更改*/
    scrollbar-face-color:#e8e8e8;
	background: rgb(246,248,249); /* Old browsers */
	background: -moz-linear-gradient(left,  rgba(246,248,249,1) 0%, rgba(229,235,238,1) 83%, rgba(229,235,238,1) 83%); /* FF3.6-15 */
	background: -webkit-linear-gradient(left,  rgba(246,248,249,1) 0%,rgba(229,235,238,1) 83%,rgba(229,235,238,1) 83%); /* Chrome10-25,Safari5.1-6 */
	background: linear-gradient(to right,  rgba(246,248,249,1) 0%,rgba(229,235,238,1) 83%,rgba(229,235,238,1) 83%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f6f8f9', endColorstr='#e5ebee',GradientType=1 ); /* IE6-9 */
	/*background: #fafafa;
    background: -webkit-linear-gradient(180deg, #fafafa, #fdfdfd); /* For Safari 5.1 to 6.0 */
	background: -moz-linear-gradient(180deg, #fafafa, #fdfdfd);*/

}
.maskDiv{
	position: absolute;
	top: 25px;
	bottom: 20px;
	right:5px;
	left:5px;
}
@media screen and (-webkit-min-device-pixel-ratio:0){
	.tree{
		padding-left:5px; 
	}
}
</style>
</head>
<body>
<div class="maskDiv" >
<div class="tree" >
<li>首頁
	<ul>
		<li id="main.php">首頁</li>
	</ul>
</li>
<li>曝光數預測
	<ul>
		<li id="predict/report2.php">查詢曝光數</li>
		<li id="predict/category.php">編輯廣告分類</li>
	</ul>
</li>
<li>廣告銷售前預約
	<ul>
		<li id="booking/bookingOrder.php">新增廣告銷售前預約託播單</li>
		<li id="booking/editBooking.php">修改廣告銷售前預約託播單</li>
		<li id="booking/searchBooking.php">查詢廣告銷售前預約託播單</li>
		<li id="booking/splitBooking.php">分割廣告銷售前預約託播單</li>
		<li id="booking/mergeBooking.php">合併廣告銷售前預約託播單</li>
		<li id="booking/commitBooking.php">確認廣告銷售前預約託播單</li>
		<li id="booking/bookingSchedule.php">銷售前預約託播單排程表</li>
	</ul>
</li>

<li>各投放系統客制化功能
	<ul>
	<!--<li>前置廣告投放系統
		<ul>
		
		</ul>
	</li>
	<li>CAMPS投放系統
	<ul>
		<li id="http://172.17.251.83/camps">CAMPS管理業面</li>
		<li id="material/deliverMaterialCF_CAMPS.php">CAMPS派送素材</li>
		<li id="position/positionCAMPS.php">同步CAMPS版位</li>
	</ul>
	</li>-->
	<li id="http://172.17.251.133/camps">CAMPS管理業面</li>
	<li id="setBarkerPosition.php">CAMPS版位資訊更新</li>
	<li id="other/vsmBGManagement.php">單一平台黃金版位管理</li>
	<li id="VSM/setVSMPosition.php">更新VSM版位</li>
	<li id="other/updateOrderPara.php">VOD插廣告/單一平台廣告參數更新</li>
	<li id="other/vastUrlManagement.php">聯播網廣告來源管理</li>
	<li id="other/shortEPGBannerLimitManagement.php">EPG Banner投放上限設定</li>
	<li id="VSM/epgBannerAuth/managementPage.php">單一平台EPG Banner撥放權限管理</li>
	</ul>
</li>

<li>廣告主管理
	<ul>
		<li id="adowner/newOwner.php">新增廣告主</li>
		<li id="adowner/editAdOwner.php">修改廣告主</li>
		<li id="adowner/searchAdOwner.php">查詢廣告主</li>
		<li id="adowner/agent/agnetManagement.php">代理商管理</li>
	</ul>
</li>
<li>版位管理
	<ul>
		<li id="position/newPositionType.php">新增版位類型資料</li>
		<li id="position/editPositionType.php">修改版位類型資料</li>
		<li id="position/searchPositionType.php">查詢版位類型資料</li>
		<li id="position/newPosition.php">新增版位資料</li>
		<li id="position/editPosition.php">修改版位資料</li>
		<li id="position/searchPosition.php">查詢版位資料</li>
		<li id="position/setViewsPerHour.php">設定版位每小時曝光數</li>
		<li id="position/positionTitle.php">前置廣告投放系統片單資訊</li>
		<li id="position/positionAdNum.php">前置廣告投放系統廣告片數</li>
		<li id="position/positionSchedule.php">版位類型排程查詢</li>
	</ul>
</li>
<li>訂單管理
	<ul>
		<li id="order/orderList_v2/orderListManagement.php">新版委刊單管理</li>
		<li id="order/orderManaging.php">訂單管理</li>
		<li id="order/newOrderListByPage.php">新增委刊單</li>
		<li id="order/editOrderListByPage.php">修改委刊單</li>
		<li id="order/searchOrderList.php">查詢委刊單</li>
		<li id="order/newOrderByPage.php">新增託播單</li>
		<li id="order/editOrderByPage.php">修改託播單</li>
		<li id="order/searchOrder.php">查詢託播單</li>
		<li id="order/editOrderBatch.php">批次修改託播單</li>
		<li id="order/checkOrderBatch.php">批次確定/取消確定託播單</li>
		<li id="order/orderManaging_adowner.php">廣告主個人訂單管理</li>
		<li id="order/csmsGroupManaging.php">SEPG託播單CSMS群組管理</li>
	</ul>
</li>
<li>素材管理
	<ul>
		<li id="material/newMaterialGroup.php">新增素材群組</li>
		<li id="material/editMaterialGroup.php">修改素材群組</li>
		<li id="material/searchMaterialGroup.php">查詢素材群組</li>
		<li id="material/newMaterial.php">新增素材</li>
		<li id="material/editMaterial.php">修改素材</li>
		<li id="material/editMaterialBatch.php">批次修改素材</li>
		<li id="material/searchMaterial.php">查詢素材</li>
		<li id="material/deliverMaterial.php">派送素材(圖片)</li>
		<li id="material/deliverMaterial_VSM.php">單一平台派送素材(圖片)</li>
		<li id="material/deliverMaterialCF.php">派送素材(影片)</li>
		<li id="material/deliverMaterialCF_CAMPS.php">CAMPS派送素材</li>
		<li id="material/deliverMaterialV2.php">派送素材2.0</li>
	</ul>
</li>
<li>投放管理
	<ul>
		<li id="casting/castingManagement.php">投放管理</li>
		<li id="casting/castingManagementByTime.php">日期查詢排程</li>
		<li id="casting/sendOrderBatch.php">批次送出/取消送出託播單</li>
	</ul>
</li>
<li>使用記錄查詢
	<ul>
		<li id="log/log.php">使用記錄查詢</li>
	</ul>
</li>
<li>系統管理
	<ul>
		<li id="sys/newUser.php">新增使用者</li>
		<li id="sys/editUser.php">修改使用者</li>
		<li id="sys/searchUser.php">查詢使用者</li>
		<li id="sys/newPage.php">新增頁面</li>
		<li id="sys/editPage.php">修改頁面</li>
		<li id="sys/searchPage.php">查詢頁面</li>
		<li id="sys/editPrivilege.php">修改權限</li>
	</ul>
</li>
</div>
</div>

<script>
$('body').bind('mask',function(){
	$('.maskDiv').mask('資料處理中...');
});

$('body').bind('unmask',function(){
	$('.maskDiv').unmask();
});

</script>

</body>
</html>
