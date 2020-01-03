	;function DataGrid(appendElementId,header,data){
		
		//檢察div是否為空
		if( $("#"+appendElementId).children().length > 0)
			return ;
		
		/****
		****屬性
		***/
		var m_isCollapsed = false;
		var m_currentPage;
		var m_attrNum;
		var m_totalPage;
		var m_appendElement = $("#"+appendElementId);		//依附的div id
		var m_table = $(document.createElement('table'));	//建立table主體
		var m_thead = $(document.createElement('thead'));	//建立header
		var m_tbody= $(document.createElement('tbody'));	//建立body
		var m_pageInfo= $(document.createElement('div'));	//建立頁面資訊用
		m_pageInfo.addClass("pageInfo");		
		var m_this= this;
		
		m_table.css({"border-style":"solid",
					"border-color":"#E6E6FA"
					});
		
		//讓datagrid可以拖拉調整寬度
		$.getScript(location.origin+'/'+location.pathname.split('/')[1]+"/tool/jquery-plugin/colResizable.min.js", function(){
		  m_table.colResizable({liveDrag:true,fixed:false});
		});

		/****
		*****method
		***/
		
		
		/**grid是否被折疊**/
		this.is_collapsed=function(){
			return m_isCollapsed;
		}
		
		/**取得目前所在頁數**/
		this.get_current_page=function(){
			return m_currentPage;
		}
		
		/**取得總頁數**/
		this.get_total_page=function(){
			return m_totalPage;
		}
		
		/**取得依附的DIV ID**/
		this.get_append_elemrnt=function(){
			return appendElementId;
		}
		
		
		/**摺疊datagrip**/
		this.collapse_row=function(rowIndex){
			if(!m_isCollapsed){
				$("#"+appendElementId+">.pageInfo").fadeOut(200);
				$("#"+appendElementId+">.ChDataGrid>tbody > tr").eq(rowIndex).siblings().fadeOut(200);
				m_isCollapsed = true;
			}
		}
		
		
		/**展開datagrip**/
		this.uncollapse=function(){
				$("#"+appendElementId+">.pageInfo").fadeIn(200);
				$("#"+appendElementId+">.ChDataGrid>tbody > tr").fadeIn(200);
				m_isCollapsed = false;
		}
		
		
		/***header資料設定***/
		this.set_header = function(header){
			$(m_thead).empty();
			var thtr = $(document.createElement('tr'));
			thtr.appendTo(m_thead);
			thtr.css("background-color", "#444444")
			thtr.css('color', 'white')
			for(var i in header){
			   var th = $(document.createElement('th'));
			   th.text(header[i]+"　")
			   .appendTo(thtr);
			}
			m_attrNum = header.length;
		};
		
		/***data資料設定***/
		this.set_data = function(data){
			m_this.uncollapse();
			$(m_tbody).empty();
			var Count=false;
			

			
			try{
				for(var i in data){
					Count=!Count;
					var tr = $(document.createElement('tr'));
					//設定顏色
					if(Count)
						tr.css("background-color", "#F4F4F8");
					else
						tr.css("background-color", "#E6E6FA");
						
					tr.appendTo(m_tbody);
					//逐一檢視資料並建立cell
					for(var j in data[i]){
						var td = $(document.createElement('td'));
						if(data[i][j][1]=="button"){
							var btn = $(document.createElement('button'));
							btn.css('width', '100%');
							btn.text(data[i][j][0])
							.appendTo(td);
						}else if(data[i][j][1]=="html"){
							td.html(data[i][j][0]);
						}
						else
							td.text(data[i][j][0]);
						
						td.appendTo(tr);
					}
				}
			}catch(e){
				
			}finally{
				var tr = $(document.createElement('tr'));
				if(Count)
						tr.css("background-color", "#F4F4F8");
					else
						tr.css("background-color", "#E6E6FA");
				tr.appendTo(m_tbody);
				var th = $(document.createElement('th'));
				
				th.attr('colspan',m_attrNum).text("資料底端").appendTo(tr);
			}
			
			
			//jquery設訂click動作
			$("#"+appendElementId+">.ChDataGrid>tbody > tr> td> button")
			.click(function() {	
				var clickTd = $(this).parent();
				var clickTr = clickTd.parent();
				var clickTbody = clickTr.parent();
				//取得index
				var row = clickTbody.children().index(clickTr);
				var col = clickTr.children().index(clickTd);
				//呼叫對應動作的method
				m_this.buttonCellOnClick(row,col,data[row]);
			}).css("cursor","pointer");
		};
		
		
		/***頁數相關元件初始化***/
		this.set_page_info = function(current,total){
			//檢查是否需要重新設定(尚未建立頁數資訊元素 或總頁數改變)
			if( m_pageInfo.children().length > 0 &&m_totalPage == total){
				//不須重新設定，只改變頁數資訊
				m_currentPage = current;
				$("#"+appendElementId+">.pageInfo>select").val(current);
				return;
			}
			m_pageInfo.empty();
			if(total<2 && total != -1)
				return;
			
			m_currentPage = current;
			m_totalPage = total;
			if(total == -1)
				total=1;
			//建立頁數資訊
			var nextBtn = $(document.createElement('button'));
			nextBtn.text("下一頁")
			.addClass("nextBtn");
			
			var preBtn = $(document.createElement('button'));
			preBtn.text("上一頁")
			.addClass("preBtn");
			
			var select = $(document.createElement("select"));
			for(var i=0;i<total;i++){
				var opt = $(document.createElement("option"));
				opt.text(i+1)
				.val(i+1)
				.appendTo(select);
			}
			
			preBtn.appendTo(m_pageInfo);
			select.appendTo(m_pageInfo);
			nextBtn.appendTo(m_pageInfo);
			m_pageInfo.appendTo(m_appendElement);
			
			//jquery設定
			select.css({"margin-left":"5px","margin-right":"5px"});
			m_pageInfo.css("float","right")
			$("#"+appendElementId+">.pageInfo>select").val(m_currentPage)	//設定目前頁數
											.change(function() {			//selector目前頁數改變目前頁數改變
												m_currentPage=this.value;
												return m_this.pageChange(this.value);
											});
			//點擊下一頁
			$("#"+appendElementId+">.pageInfo>.nextBtn").click(function(){
														m_currentPage+=1;
														//總頁數為-1，動態增加頁數
														if(m_totalPage == -1){
															if( $("#"+appendElementId+">.pageInfo>select option[value="+m_currentPage+"]").length == 0){
																$("#"+appendElementId+">.pageInfo>select").append($("<option></option>").attr("value",m_currentPage).text(m_currentPage));
															}
															$("#"+appendElementId+">.pageInfo>select").val(m_currentPage);
															return m_this.pageChange(m_currentPage);
														}
														//總頁數不為-1，一般行為
														else if(m_currentPage < m_totalPage){
															$("#"+appendElementId+">.pageInfo>select").val(m_currentPage);
															return m_this.pageChange(m_currentPage);
														}
													});
			//點擊上一頁
			$("#"+appendElementId+">.pageInfo>.preBtn").click(function() {
														if(m_currentPage > 1){
															$("#"+appendElementId+">.pageInfo>select").val(--m_currentPage);
															return m_this.pageChange(m_currentPage);
													}});											
		}
		
		/**設定欄位是否可排序**/
		this.set_sortable = function(headerNames,enable){
			for(var i in headerNames){
				var header = $("#"+appendElementId+">.ChDataGrid >thead > tr >th:contains("+headerNames[i]+")");
				if(enable)
					header.addClass("sortable");
				else
					header.removeClass("sortable");
			}	
			
			//jquery設定header被點擊時的動作
			$("#"+appendElementId+">.ChDataGrid >thead > tr >th.sortable")
			.click(function() {
				if(!m_isCollapsed){
					var headerName = $(this).text().replace("▼","").replace("▲","").replace("　","");
					var status="";

					if($(this).hasClass("inSort")){
						m_this.set_sort_state(headerName,"decrease");
						status = "decrease";
					}
					else if($(this).hasClass("deSort")){
						m_this.set_sort_state(headerName,"unsort");
						status = "unSort";
					}
					else{
						m_this.set_sort_state(headerName,"increase");
						status = "increase";
					}	
					//呼叫對應動作的method
					m_this.headerOnClick(headerName,status);
				}

			}).css("cursor","pointer");
		}
		
		/**設定欄位排序狀態**/
		this.set_sort_state = function(headerName,state){
			//依照header排序狀態改變顯示標誌
			var header = $("#"+appendElementId+">.ChDataGrid >thead > tr >th:contains("+headerName+")");
			header.removeClass("inSort").removeClass("deSort");
			switch(state){
				case "increase":
					header.addClass("inSort").text(headerName+"▲");
					break;
				case "decrease":
					header.addClass("deSort").text(headerName+"▼"); 
					break;
				case "unsort":
					header.text(headerName+"　"); 
					break;
			}
			
			//取消其他header的排序標誌
			header.siblings().each(function() {
				$(this).text($(this).text().replace("▲","　").replace("▼","　"));
				$(this).removeClass("inSort").removeClass("deSort");	
			});
		}
		
		/****
		****依照column名稱取的cell文字
		***/
		this.getCellText = function(colName,rowNum){
			var col = m_thead.find('th:contains("'+colName+'")');
			colnum = m_thead.find('th').index(col);
			return m_tbody.find('tr:eq('+rowNum+') td:eq('+colnum+')').text();
		}
		
		/****
		***產生DataGrid
		*****/
		m_table.css('width', '100%');
		m_table.appendTo(m_appendElement);
		m_table.addClass("ChDataGrid")
		m_thead.appendTo(m_table);
		m_tbody.appendTo(m_table);
		this.set_header(header);
		this.set_data(data);
		
		
		
		/****
		*****客製化的method，由外部覆寫
		*****/
		
		
		/***header被點擊時執行動作(排序)
			headerName:	被點擊的header名稱
			sortState:	點擊後的排序狀態***/
		this.headerOnClick = function(headerName,sortState){
		}
		
		/***頁數改變時執行動作(更新頁面資料)
			toPage:	改變後的頁數***/
		this.pageChange = function(toPage){
		}
		
		/***buttonCell被點擊時的動作
			row column: index***/
		this.buttonCellOnClick = function(row,column,rowdata){
		}
		
	};