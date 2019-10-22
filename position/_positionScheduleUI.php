
<div id="psch_tabs">
  <ul>
    <li id ='psch_tabs_li-1' ><a href="#psch_tabs-1">版位與日期</a></li>
    <li id ='psch_tabs_li-2' ><a href="#psch_tabs-2">版位顯示條件</a></li>
	<li id ='psch_tabs_li-3' ><a href="#psch_tabs-3">託播單排序條件</a></li>
	<li id ='psch_tabs_li-4' ><a href="#psch_tabs-4">排程上色方式</a></li>
  </ul>
	<div id ='psch_tabs-1'>
		版位類型:<select id="psch_positiontype"></select> 版位名稱:<select id="psch_position" ></select>
		日期:<input type="text" id="startDatePicker" style="width:100px" readonly></input>~<input type="text" id="endDatePicker" style="width:100px" readonly></input>
	</div>
	<div id="psch_tabs-2">
		<input type="radio" name="displayType" value="withOrder" checked>顯示有託播單的版位
		<input type="radio" name="displayType" value="withoutOder">顯示沒有託播單的版位
		<input type="radio" name="displayType" value="all">顯示全部版位
	</div>
	<div id="psch_tabs-3">
		託播單排序條件:
		<select id = "psch_sortByProperty">
		</select>
		排程顯示方式:
		<select id = "psch_sortFormat">
		<option value = "samePosition">
		版位相同優先
		</option>
		<option value = "sameProperty">
		參數相同優先
		</option>
		</select>
	</div>
	<div id="psch_tabs-4">
	<select id ='coloringMethod'>
	<option value = '依版位識別碼上色'>依版位識別碼上色</option>
	<option value = '依委刊單識別碼上色'>依委刊單識別碼上色</option>
	<option value = '依素材識別碼上色'>依素材識別碼上色</option>
	<option value = '依託播單識別碼上色'>依託播單識別碼上色</option>
	</select>
	</div>
</div>

<button onclick = 'getPositionSchedule()'>查詢</button>
<button id="btnExport" onclick="exportExcel();"> 匯出excel </button>
<hr>

<a id ='showArea'>顯示區域:<input type = 'checkbox' name='showAreaCheckBox' value='北'></input>北<input type = 'checkbox' name='showAreaCheckBox' value='中'></input>中
<input type = 'checkbox' name='showAreaCheckBox' value='南'></input>南<input type = 'checkbox' name='showAreaCheckBox' value='IAP'></input>IAP</a>
<br>
<!--<a id ='showDefault'>顯示預設廣告設定:<input type = 'radio' name='showDefaultCheckBox' value=0></input>非預設廣告
<input type = 'radio' name='showDefaultCheckBox' value=1></input>預設廣告<input type = 'radio' name='showDefaultCheckBox' value='all'></input>全部</a>-->
<div id = 'schDiv'>
</div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>


<script>
$( "#psch_tabs" ).tabs();
</script>