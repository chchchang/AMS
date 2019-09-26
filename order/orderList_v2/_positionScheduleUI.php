版位類型:<select id="psch_positiontype"></select> 版位名稱:<select id="psch_position" ></select>
日期:<input type="text" id="startDatePicker" style="width:100px" readonly></input>~<input type="text" id="endDatePicker" style="width:100px" readonly></input>
</br>
<input type="radio" name="displayType" value="withOrder" checked>顯示有託播單的版位
<input type="radio" name="displayType" value="withoutOder">顯示沒有託播單的版位
<input type="radio" name="displayType" value="all">顯示全部版位
<button onclick = 'getPositionSchedule()'>查詢</button>
</br>
<hr>
<select id ='coloringMethod'>
<option value = '依版位識別碼上色'>依版位識別碼上色</option>
<option value = '依委刊單識別碼上色'>依委刊單識別碼上色</option>
<option value = '依素材識別碼上色'>依素材識別碼上色</option>
<option value = '依託播單識別碼上色'>依託播單識別碼上色</option>
</select>
<a id ='showArea'>顯示區域:<input type = 'checkbox' name='showAreaCheckBox' value='北'></input>北<input type = 'checkbox' name='showAreaCheckBox' value='中'></input>中
<input type = 'checkbox' name='showAreaCheckBox' value='南'></input>南<input type = 'checkbox' name='showAreaCheckBox' value='IAP'></input>IAP</a>
<br>
<a id ='showDefault'>顯示預設廣告設定:<input type = 'radio' name='showDefaultCheckBox' value=0></input>非預設廣告
<input type = 'radio' name='showDefaultCheckBox' value=1></input>預設廣告<input type = 'radio' name='showDefaultCheckBox' value='all'></input>全部</a>
<a id ='showOutterOrder'>顯示外廣告設定:<input type = 'radio' name='showOutterOrderCheckBox' value=0></input>非預設廣告
<input type = 'radio' name='showOutterOrderCheckBox' value=1></input>預設廣告<input type = 'radio' name='showOutterOrderCheckBox' value='all'></input>全部</a>
<div id = 'schDiv'>
</div>
<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>
