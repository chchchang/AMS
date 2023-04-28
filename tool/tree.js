; $('document').ready(function(){
		$("li").css("margin","2px")
		$("li:has(ul)").css("list-style-image","url(tool/pic/list_plus.gif)")
		.children().hide();
		
		$("li:has(ul)").click(function(e){
		if(this==e.target){
			if($(this).children().is(":hidden")){
				//如果子項是隱藏的則顯示
				$(this)
				.css("list-style-image","url(tool/pic/list_minus.gif)")
				.children().slideDown(200);
			}else{
				//如果子項是顯示的則隱藏
				$(this)
				.css("list-style-image","url(tool/pic/list_plus.gif)")
				.children().slideUp(200);
			}
		}
		return false; //避免不必要的事件混繞
		}).css("cursor","pointer").click(); //加載時觸發點擊事件

		//對於沒有子項的選單，統一設置
		$("li:not(:has(ul))").css({
			"cursor":"default",
			"list-style-image":"url(tool/pic/list.png)",
			"border-left": "solid",
			"border-left-color": "#AAAAAA",
		});
		//li被點擊後依照id跳轉主畫面中央頁面
		$("li:not(:has(ul))").click(function(e){
			parent.main.location=this.id;
			
			$("li:not(:has(ul))").css({
				"list-style-image":"url(tool/pic/list.png)",
				"border-left-color": "#AAAAAA",
			});
			
			$(this).css({
				"list-style-image":"url(tool/pic/list_selected.png)",
				"border-left-color": "#FF8800",
			})
		}).css("cursor","pointer");
		
		$("li:not(:has(ul))").hover(function(e){
			$(this).css("color","#FF8800");
		},function(e){
			$(this).css("color","black");
		})
	});