
	<div id="psch_tabs">
	<ul>
		<li id ='psch_tabs_li-1' ><a href="#psch_tabs-1">版位與日期</a></li>
		<li id ='psch_tabs_li-2' ><a href="#psch_tabs-2">版位顯示條件</a></li>
		<li id ='psch_tabs_li-3' ><a href="#psch_tabs-3">託播單排序條件</a></li>
		<li id ='psch_tabs_li-4' ><a href="#psch_tabs-4">排程上色方式</a></li>
	</ul>
		<div id ='psch_tabs-1'>
			版位類型:<select id="psch_positiontype"></select> 版位名稱:<select id="psch_position" ></select>
			日期:<input type="text" id="startDatePicker" style="width:100px" readonly></input>
			~
			<input type="text" id="endDatePicker" style="width:100px" readonly></input>
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

	<a id ='showArea'>顯示區域:<input type = 'checkbox' name='showAreaCheckBox' value='北'></input>北<input type = 'checkbox' name='showAreaCheckBox' value='中'></input>中
	<input type = 'checkbox' name='showAreaCheckBox' value='南'></input>南<input type = 'checkbox' name='showAreaCheckBox' value='IAP'></input>IAP</a>
	<br>
	<!--<a id ='showDefault'>顯示預設廣告設定:<input type = 'radio' name='showDefaultCheckBox' value=0></input>非預設廣告
	<input type = 'radio' name='showDefaultCheckBox' value=1></input>預設廣告<input type = 'radio' name='showDefaultCheckBox' value='all'></input>全部</a>-->
	<div id = 'schDiv'>
	</div>

	<div id="dialog_form"><iframe id="dialog_iframe" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe></div>

	<div id = 'vueApp' >
	<button @click= "getPositionSche()">查詢</button>	<button id="btnExport" onclick="exportExcel();"> 匯出excel </button>
	<hr>
	<div style="display:flex;overflow:scroll;" v-if="dateRow.length!=0">
	<table id="pscheduleVue" class="styledTable2" style="flex:1;" >
		<thead  style="position:sticky;top: 0;">
			<tr> 
				<th>年月</th> 
				<th rowspan="2" colspan="4"></th> 
				<th v-for="yearMouth in Object.keys(yearMonthRow)"  :colspan="yearMonthRow[yearMouth].colspan" :key="yearMouth">
					{{yearMouth}}
				</th>
			</tr>
			<tr> 
				<th>日</th>
				<th v-for="date in dateRow" :day="date" :key="date">
					{{date.substring(8)}}
				</th>
			</tr>
			<tr>
				<th>星期</th>
				<th>no</th>
				<th>託播單識別碼</th>
				<th>託播單名稱</th>
				<th>狀態</th>
				<th v-for="day in dayRow">
					{{day}}
				</th>
			</tr>
		</thead>
		<tbody>
			<tr v-for="(order,orderId) in orderRows" :key="orderId">
				<td v-for="(td,tdId) in order" v-bind="getCustomHtmlAttribute(td.attr)" :key="tdId">{{td.text}}</td>
			</tr>
		</tbody>
	</table>
	</div>
</div>

<script>
$( "#psch_tabs" ).tabs();
</script>