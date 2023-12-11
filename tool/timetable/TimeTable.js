	;var colorsCodeforTimeTable = ['#FFA488','#FFDD55','#CCFF33','#33FF33','#33CCFF','#5555FF','#9955FF','#E93EFF'];//不同色碼
	;function CreateTimetable(outputId,data)
	{
		var m_appendElement = $("#"+outputId);
		m_appendElement.empty();
		var m_this = this;
		var rwoCount=true;
		var output='<table style="width:100%;border-style:solid;border-color:#DDDDDD">'
		+'<thead><tr><th>no</th>'
		+'<th>'+(typeof(data['託播單代碼標題文字'])!='undefined'?HtmlSanitizer.SanitizeHtml(data['託播單代碼標題文字']):'託播單識別碼')
		+'</th><th>00</th><th>01</th><th>02</th><th>03</th><th>04</th><th>05</th><th>06</th><th>07</th><th>08</th><th>09</th><th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th><th>16</th><th>17</th><th>18</th><th>19</th><th>20</th><th>21</th><th>22</th><th>23</th></tr></thead>'
		var sum=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
		output+='<tbody>';
		var bgcolor ='rgb(244, 244, 248)';
		var orders = data['託播單'];
		for(var i=0;i<orders.length;i++) {
			if(i%2==0)
				bgcolor ='rgb(244, 244, 248)';
			else
				bgcolor ='rgb(230, 230, 250)';
			if(typeof(orders[i].upTitle)!='undefined'){
				output+='<tr style="background-color:'+bgcolor+'"><th rowspan=2>'+(i+1)+'</th><th rowspan=2>'+(typeof(orders[i].託播單代碼替換文字)=='undefined'?HtmlSanitizer.SanitizeHtml(orders[i].託播單代碼):HtmlSanitizer.SanitizeHtml(orders[i].託播單代碼替換文字))+
				'</th><td colspan=24>'+HtmlSanitizer.SanitizeHtml(orders[i].upTitle)+'</td></tr><tr style="background-color:'+bgcolor+'" value='+HtmlSanitizer.SanitizeHtml(orders[i].託播單代碼)+'>';
			}
			else{
				output+='<tr style="background-color:'+bgcolor+'" value='+HtmlSanitizer.SanitizeHtml(orders[i].託播單代碼)+'><th>'+(i+1)+'</th><th>'
				+(typeof(orders[i].託播單代碼替換文字)=='undefined'?HtmlSanitizer.SanitizeHtml(orders[i].託播單代碼):HtmlSanitizer.SanitizeHtml(orders[i].託播單代碼替換文字))+'</th>';
			}
			var dataLength=0;
			var highlight = false;
			if(typeof orders[i].highlight!='undefined')
				highlight = true;
				
			for(var j=0;j<23;j++) {
				if($.inArray( j, orders[i].hours)!==-1){
					dataLength++;
					sum[j]++;
				}
				else{
					if(dataLength!=0){
						if(highlight)
							output+='<td class = "highlightdata" colspan="'+dataLength+'">&nbsp;</td>';
						else
							output+='<td class = "data" colspan="'+dataLength+'">&nbsp;</td>';
						dataLength =0;
					}
					output+='<td></td>';
				}

			}
			if($.inArray( j, orders[i].hours)!==-1) {
				if(highlight)
					output+='<td class = "highlightdata" colspan="'+dataLength+'">&nbsp;</td>';
				else
					output+='<td class = "data" colspan="'+(++dataLength)+'">&nbsp;</td>';
				sum[23]++;
			}
			else{
				if(dataLength!=0){
					if(highlight)
						output+='<td class = "highlightdata" colspan="'+dataLength+'">&nbsp;</td>';
					else
						output+='<td class = "data" colspan="'+dataLength+'">&nbsp;</td>';
					dataLength =0;
				}
				output+='<td></td>';
			}
			output+='</tr>';
			
		}
		output+='<tr class = "sumTr"><th></th><th>合計</th><th class = "sum">'+HtmlSanitizer.SanitizeHtml(sum.join('</th><th class = "sum">'))+'</th></tr></tbody></table>';
		document.getElementById(outputId).innerHTML=output;
				
		if(typeof(data['素材'])!='undefined')
		extendDataGrid(outputId,data);
		
		//jquery設訂
		/*$("#"+outputId+">table >tbody > tr:odd" ).css( "background-color", "#E6E6FA" );
		$("#"+outputId+">table >tbody > tr:even" ).css( "background-color", "#F4F4F8" );*/
		$("#"+outputId+">table >tbody > .sumTr" ).css( "background-color", "#DDDDDD" );
		$("#"+outputId+">table >thead >tr >th" ).css( "background-color", "#DDDDDD" );
		//一般DATA
		$("#"+outputId+">table >tbody > tr> td.data,#"+outputId+">table >tbody > tr> td.highlightdata")
		.css("cursor","pointer")
		.each(function(){
					var clickTr = $(this).parent();
					var clickTbody = clickTr.parent();
					var row = clickTbody.children().index(clickTr);
					var id = clickTr.attr('value');
					var color = colorsCodeforTimeTable[id%(colorsCodeforTimeTable.length)];
					//var col = clickTr.children('td').index($(this));
					$(this).css('background-color',color)
					.hover(function(e){					
						clickTr.children('td.data').css('background-color','#FF8800');	
						clickTr.children('td.highlightdata').css('background-color','#32CD32');	
						var mX = e.pageX;
						var mY = e.pageY;					
						m_this.mouseOnDataCell(mX,mY,row,id);		
						},
						function(){
							clickTr.children('td.data').css({'background-color':color});
							clickTr.children('td.highlightdata').css('background-color','#3CB371');
							m_this.mouseOutDataCell()
						}
					)
					.click(function(e){	
							var mX = e.pageX;
							var mY = e.pageY;						
							m_this.clickOnDataCell(mX,mY,row,id);		
						}
					);
		});
				
		$("#"+outputId+">table >tbody > tr> th.sum")
		.click(function(){	
					var clickTr = $(this).parent();
					var col = clickTr.children('th').index($(this));	
					m_this.sumCellClick(col);		
				}
		).css("cursor","pointer");
		
		//HighLight Data
		$("#"+outputId+">table >tbody > tr> td.highlightdata")
		.css('background-color','#3CB371');
		
		//CSMS託播單詳細資料
		$("#"+outputId+' .showSendResult_CSMS').click(function(){
			var dialog = $('<div id="_setWieghDia"><iframe width="100%" height="100%" src="../casting/showCSMSResult.php?id='+$(this).attr('id')+'"></iframe></div>')
				.appendTo('body')
				.dialog({
					width: '100%',
					height: 450,
					modal: true,
					title: '託播單送出結果',
					close:function(event, ui){
					dialog.dialog("close");
					dialog.remove()}
				});
		});
		
		/**
		Customize method
		**/		
		/*滑鼠移到資料cell上*/
		this.mouseOnDataCell = function(mx,my,row,name){
		}
		
		/*滑鼠移出資料cell*/
		this.mouseOutDataCell = function(mx,my,row,name){
		}
		
		/*滑鼠移到資料cell上*/
		this.clickOnDataCell = function(mx,my,row,name){
		}
		
		/*統計被點擊cell*/
		this.sumCellClick = function(time){
		}
	}
	
	
	
	/*****
	連續型timeTable
	****/	
	;function CreateTimetable_sequence(outputId,data)
	{
		var m_this = this;
		var rwoCount=true;
		var m_appendElement = $("#"+outputId);
		m_appendElement.empty();
		var m_table = $(document.createElement('table')).css('width', '100%').appendTo(m_appendElement);
		var m_thead = $(document.createElement('thead')).appendTo(m_table);
		var m_tbody = $(document.createElement('tbody')).appendTo(m_table);
		
		//header
		var tr = $(document.createElement('tr'));
		tr.appendTo(m_thead);
		$(document.createElement('th')).text('no').appendTo(tr);
		$(document.createElement('th')).text((typeof(data['託播單代碼標題文字'])!='undefined')?data['託播單代碼標題文字']:'託播單識別碼').appendTo(tr);
		for(var i = 0;i<24;i++){
			 $(document.createElement('th')).text(pad(i,2)).appendTo(tr);
		}
		
		//body
		var bgcolor='#E6E6FA';
		var orders = data['託播單'];
		for(var i=0;i<orders.length;i++) {
			if(i%2==0)
				bgcolor ='#E6E6FA';
			else
				bgcolor ='#F4F4F8';
			var tr = $(document.createElement('tr')).css({'background-color':bgcolor}).appendTo(m_tbody);
			var th1 = $(document.createElement('th')).text(i+1).appendTo(tr);
			var th2 = $(document.createElement('th')).text((typeof(orders[i].託播單代碼替換文字)=='undefined'?orders[i].託播單代碼:orders[i].託播單代碼替換文字)).appendTo(tr);
			var datatd = $(document.createElement('td')).css({'height':"100%",'margin': 0}).attr('colspan',24).appendTo(tr);
			if(typeof(orders[i].upTitle)!='undefined'){
				th1.attr('rowspan',2);
				th2.attr('rowspan',2);
				datatd.html(orders[i].upTitle);
				tr = $(document.createElement('tr')).css({'background-color':bgcolor}).appendTo(m_tbody);
				datatd = $(document.createElement('td')).css({'height':"100%",'margin': 0}).attr('colspan',24).appendTo(tr);
			}
			tr.attr('value',orders[i].託播單代碼);
			var datap = $(document.createElement('p')).css({'height':"100%",'margin': 0})
			.text(".").appendTo(datatd);
			if(typeof orders[i].highlight!='undefined')
				datap.addClass('highlightdata');
			else
				datap.addClass('data');
			//計算寬度與位置
			var st = getMins(orders[i].startTime);
			var et = getMins(orders[i].endTime);
			var width = (et-st)/1440*100;
			var sp = st/1440*100;
			datap.css({'width':width+"%",'position': 'relative','left':sp+"%"});
				
		}
		
				
		if(typeof(data['素材'])!='undefined')
		extendDataGrid(outputId,data);
		
		//計算分鐘數
		function getMins(timeString){
			var temp = timeString.split(":");
			return parseInt(temp[0])*60+parseInt(temp[1]);
		}
		
		//jquery設訂
		$("#"+outputId+">table >tbody > tr").css("height","21px");
		$("#"+outputId+">table >tbody > .sumTr" ).css( "background-color", "#DDDDDD" );
		$("#"+outputId+">table >thead >tr >th" ).css( "background-color", "#DDDDDD" );
		//一般DATA
		$("#"+outputId+">table >tbody > tr> td> p.data,#"+outputId+">table >tbody > tr> td> p.highlightdata")
		.css("cursor","pointer")
		.each(
			function(){
				var clickTd = $(this).parent();
				var clickTr = clickTd.parent();
				var clickTbody = clickTr.parent();
				var row = clickTbody.children().index(clickTr);
				var id = clickTr.attr('value');
				
				//var col = clickTr.children('td').index($(this));
				var color = colorsCodeforTimeTable[id%(colorsCodeforTimeTable.length)];
				$(this).css({'background-color':color,'color':color})
				.hover(function(e){
							clickTd.children('td> p.data').css({'background-color':'#FF8800'});	
							clickTd.children('td> p.highlightdata').css({'background-color':'#32CD32','color':'#32CD32'});
							var mX = e.pageX;
							var mY = e.pageY;
							//m_this.mouseOnDataCell(mX,mY,row,orders[row].託播單代碼);		
						},
						function(){
							clickTd.children('td> p.data').css({'background-color':color,'color':color});
							clickTd.children('td> p.highlightdata').css({'background-color':'#3CB371'});
							//m_this.mouseOutDataCell()
						}
				)
				.click(function(e){	
							var mX = e.pageX;
							var mY = e.pageY;					
							m_this.clickOnDataCell(mX,mY,row,id);		
						}
				)
			}
		);
		
		$("#"+outputId+">table >tbody > tr> th.sum")
		.click(function(){	
					var clickTr = $(this).parent();
					var col = clickTr.children('th').index($(this));	
					m_this.sumCellClick(col);		
				}
		).css("cursor","pointer");
		
		//HighLight Data
		$("#"+outputId+">table >tbody > tr> td> p.highlightdata")
		.css({'background-color':'#3CB371','color':'#3CB371'});
				
		/**
		Customize method
		**/		
		/*滑鼠移到資料cell上*/
		this.mouseOnDataCell = function(mx,my,row,name){
		}
		
		/*滑鼠移出資料cell*/
		this.mouseOutDataCell = function(mx,my,row,name){
		}
		
		/*滑鼠移到資料cell上*/
		this.clickOnDataCell = function(mx,my,row,name){
		}
		
		/*統計被點擊cell*/
		this.sumCellClick = function(time){
		}

	};
	
	//leading zero
	function pad(num, size) {
		var s = num+"";
		while (s.length < size) s = "0" + s;
		return s;
	}
	
	function extendDataGrid(outputId,data){
		var $tbody = $("#"+outputId+'>table >tbody');
		var material = data['素材'];
		$tr = $('<tr class ="timetable-extend-dg-th"/>').append($('<th>素材識別碼</th>'));
		for(var i =0;i<24;i++)
			$tr.append($('<th>'+pad(i,2)+'</th>'));
		$tbody.append($tr);
		
		var sum =[];
		//逐一增加row
		for(var i in material){
			$tr = $('<tr class ="timetable-extend-dg"/>').append($('<th>'+HtmlSanitizer.SanitizeHtml(material[i]['素材識別碼'])+'</th>'));
			for(var j in material[i]['個數']){
				$tr.append($('<td>'+HtmlSanitizer.SanitizeHtml(material[i]['個數'][j])+'</td>'));
				if(typeof(sum[j])=='undefined')
					sum[j]=parseInt(material[i]['個數'][j],10);
				else
					sum[j]+=parseInt(material[i]['個數'][j],10);
			}
			$tbody.append($tr);
		}
		$tr = $('<tr class ="timetable-extend-dg-th"/>').append($('<th>統計</th>'));
		for(var i in sum){
			$tr.append($('<th>'+sum[i]+'</th>'));
		}
		$tbody.append($tr);
		
		$tbody.find('.timetable-extend-dg-th').css("background-color", "#DDDDDD");
		$tbody.find('.timetable-extend-dg:even').css("background-color", "#F4F4F8");
		$tbody.find('.timetable-extend-dg:odd').css("background-color", "#E6E6FA");
	}