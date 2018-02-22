<?php
	include('../tool/auth/authAJAX.php');
	if(isset($_POST['method'])){
		if($_POST['method'] == '停用使用者'){
				$sql='UPDATE 使用者 SET 啟用=0 WHERE 使用者識別碼=?';
				if(!$stmt=$my->prepare($sql)) {
					$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('i',$_POST['使用者識別碼'])) {
					$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法繫結資料，請聯絡系統管理員！');
				}
				if(!$stmt->execute()) {
					$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法執行statement，請聯絡系統管理員！');
				}
				$logger->info('"使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')"停用"使用者識別碼('.intval($_POST['使用者識別碼']).')"');
				exit('停用成功');
		}
		if($_POST['method'] == '啟用使用者'){
				$sql='UPDATE 使用者 SET 啟用=1 WHERE 使用者識別碼=?';
				if(!$stmt=$my->prepare($sql)) {
					$logger->error('無法準備statement，錯誤代碼('.$my->errno.')、錯誤訊息('.$my->error.')。');
					exit('無法準備statement，請聯絡系統管理員！');
				}
				if(!$stmt->bind_param('i',$_POST['使用者識別碼'])) {
					$logger->error('無法繫結資料，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法繫結資料，請聯絡系統管理員！');
				}
				if(!$stmt->execute()) {
					$logger->error('無法執行statement，錯誤代碼('.$stmt->errno.')、錯誤訊息('.$stmt->error.')。');
					exit('無法執行statement，請聯絡系統管理員！');
				}
				$logger->info('"使用者識別碼('.$_SESSION['AMS']['使用者識別碼'].')"啟用"使用者識別碼('.intval($_POST['使用者識別碼']).')"');
				exit('啟用成功');
		}
	}
?>