<?php
	date_default_timezone_set("Asia/Taipei");
	header("Content-Type:text/html; charset=utf-8");
	class Config
	{
		const PROJECT_ROOT='/AMS/';
		
		const DB_HOST='localhost';
		const DB_USER='AMS';
		const DB_PASSWORD='AMS';
		const DB_NAME='AMS';
		
		const SECRET_FOLDER='0b7d6e5a265d20715443e19a1f7609c6';
	}
?>