;function BlockDataGrid(appendElementId,data){
	//檢察div是否為空
	if( $("#"+appendElementId).children().length > 0)
		return ;
	
	/****
	****屬性
	***/
	var m_appendElement = $("#"+appendElementId);		//依附的div id
	var m_table = $(document.createElement('table'));	//建立table主體
	var m_tbody= $(document.createElement('tbody'));	//建立body
	var m_this= this;
	
	
	/***data資料設定***/
	this.set_data = function(data){
		$(m_tbody).empty();
		var Count=false;

		for(var i in data){
			Count=!Count;
			var tr = $(document.createElement('tr'));
			tr.appendTo(m_tbody);
			var td = $(document.createElement('td'));
			var div = $(document.createElement('div'));
			if(Count)
				div.css("background-color","#8A2BE2");
			else
				div.css("background-color","#4169E1");
			div.text(data[i].head)
			.css({"width":"150px","text-align":"center","font-weight":"bold"})
			.appendTo(td);
			td.appendTo(tr);
			//逐一檢視資料並建立cell
			for(var j in data[i].cells){
				var dtd = $(document.createElement('td'));
				var ddiv = $(document.createElement('div'));
				if(Count)
					ddiv.css("background-color","#9370DB")
				else
					ddiv.css("background-color","#6495ED")
				ddiv.text(data[i].cells[j])
				.css({"width":"150px","text-align":"center"})
				.addClass("Clickalbe")
				.appendTo(dtd);
				dtd.appendTo(tr);
			}
		}
		
		$("#"+appendElementId+">.BlockDataGrid>tbody > tr> td> div.Clickalbe").css("border","2px solid #DDDDDD");
		//jquery設訂click動作
		$("#"+appendElementId+">.BlockDataGrid>tbody > tr> td> div.Clickalbe")
		.click(function() {	
			$("#"+appendElementId+">.BlockDataGrid>tbody > tr> td> div.Clickalbe").css({"border":"2px solid #DDDDDD","color":"#FFFFFF"});
			$(this).css({"border":"2px solid #666666","color":"#000000"});
			var clickTd = $(this).parent();
			var clickTr = clickTd.parent();
			var clickTbody = clickTr.parent();
			//取得index
			var row = clickTbody.children().index(clickTr);
			var col = clickTr.children().index(clickTd);
			//呼叫對應動作的method
			m_this.cellOnClick(row,col,$(this).text());
		}).css("cursor","pointer");
	};
	
	/****
	***產生DataGrid
	*****/
	m_table.appendTo(m_appendElement);
	m_table.addClass("BlockDataGrid")
	m_tbody.appendTo(m_table);
	this.set_data(data);
	$("#m_table").css({"border-collpase":"collpase"});
	/**使用者複寫***/
	this.cellOnClick = function(row,col,text){
	}
};