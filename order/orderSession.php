<?php
include('../tool/auth/authAJAX.php');
//設定session
if(isset($_POST['saveOrder'])){
	if(!$_POST['saveOrder'])
	unset($_SESSION['AMS']['saveOrder']);
	else{
		if(!isset($_SESSION['AMS']['saveOrder']))
			$_SESSION['AMS']['saveOrder']=[];
		$saveOrder=htmlescape($_POST['saveOrder']);
		foreach($saveOrder as $key=>$value){
			$_SESSION['AMS']['saveOrder'][$key]=$value;
		}
	}
}

if(isset($_POST['saveAdOwner']))
	$_SESSION['AMS']['saveAdOwner']=htmlescape($_POST['saveAdOwner']);

	
if(isset($_POST['saveOrderList']))
	$_SESSION['AMS']['saveOrderList']=htmlescape($_POST['saveOrderList']);

	
if(isset($_POST['saveEditList'])){
	if(!$_POST['saveEditList'])
		unset($_SESSION['AMS']['saveEditList']);
	else{
		if(!isset($_SESSION['AMS']['saveEditList']))
			$_SESSION['AMS']['saveEditList']=[];
		$saveEditList=htmlescape($_POST['saveEditList']);
		
		//待刪除的託單
		if(isset($saveEditList['delete'])){
			if(!isset($_SESSION['AMS']['saveEditList']['delete']))
				$_SESSION['AMS']['saveEditList']['delete']=[];
			
			foreach($saveEditList['delete'] as $key=>$value){
				$_SESSION['AMS']['saveEditList']['delete'][$key]=$value;
			}
		}
		
		//待更新的託播單
		if(isset($saveEditList['edit'])){
			if(!isset($_SESSION['AMS']['saveEditList']['edit']))
				$_SESSION['AMS']['saveEditList']['edit']=[];
			
			foreach($saveEditList['edit'] as $key=>$value){
				$_SESSION['AMS']['saveEditList']['edit'][$key]=$value;
			}
		}
	}
}

if(isset($_POST['saveLastOrder'])){
	if(!$_POST['saveLastOrder'])
	unset($_SESSION['AMS']['saveLastOrder']);
	else
	$_SESSION['AMS']['saveLastOrder']=htmlescape($_POST['saveLastOrder']);
}

if(isset($_POST['saveLastMaterialGroup'])){
	if(!$_POST['saveLastMaterialGroup'])
	unset($_SESSION['AMS']['saveLastMaterialGroup']);
	else
	$_SESSION['AMS']['saveLastMaterialGroup']=htmlescape($_POST['saveLastMaterialGroup']);
}


//清除session
if(isset($_POST['clearOrder']))
	unset($_SESSION['AMS']['saveOrder']);

if(isset($_POST['clearAdOwner']))
	unset($_SESSION['AMS']['saveAdOwner']);
	
if(isset($_POST['clearOrderList']))
	unset($_SESSION['AMS']['saveOrderList']);
	
if(isset($_POST['claerEditList']))
	unset($_SESSION['AMS']['saveEditList']);
	
if(isset($_POST['claerLastOrder']))
	unset($_SESSION['AMS']['saveLastOrder']);
	
if(isset($_POST['clearOrderSession'])){
	unset($_SESSION['AMS']['saveOrder']);
	unset($_SESSION['AMS']['saveAdOwner']);
	unset($_SESSION['AMS']['saveOrderList']);
	unset($_SESSION['AMS']['saveEditList']);
	}

//取得session
if(isset($_POST['getOrder']))
	if(isset($_SESSION['AMS']['saveOrder']))
		//echo $_SESSION['AMS']['saveOrder'];
		echo json_encode(($_SESSION['AMS']['saveOrder']),JSON_UNESCAPED_UNICODE);
	else echo "";

if(isset($_POST['getAdOwner']))
	if(isset($_SESSION['AMS']['saveAdOwner']))
		//echo $_SESSION['AMS']['saveAdOwner'];
		echo json_encode(($_SESSION['AMS']['saveAdOwner']),JSON_UNESCAPED_UNICODE);
	else echo "";
	
if(isset($_POST['getOrderList']))
	if(isset($_SESSION['AMS']['saveOrderList']))
		//echo $_SESSION['AMS']['saveOrderList'];
		echo json_encode(($_SESSION['AMS']['saveOrderList']),JSON_UNESCAPED_UNICODE);
	else echo "";
	
if(isset($_POST['getEditList']))
	if(isset($_SESSION['AMS']['saveEditList']))
		//echo $_SESSION['AMS']['saveEditList'];
		echo json_encode(($_SESSION['AMS']['saveEditList']),JSON_UNESCAPED_UNICODE);
	else echo "";
	
if(isset($_POST['getLastOrder']))
	if(isset($_SESSION['AMS']['saveLastOrder']))
		echo json_encode(($_SESSION['AMS']['saveLastOrder']),JSON_UNESCAPED_UNICODE);
	else echo "[]";
	
if(isset($_POST['getLastMaterialGroup']))
	if(isset($_SESSION['AMS']['saveLastMaterialGroup']))
		echo json_encode(($_SESSION['AMS']['saveLastMaterialGroup']),JSON_UNESCAPED_UNICODE);
	else echo "[]";
	
function htmlescape($data){
	if(gettype($data)=="array")
		return array_map('htmlescape',$data);
	else{
		if($data == null)
			return null;
		else
			return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}
}
?>
