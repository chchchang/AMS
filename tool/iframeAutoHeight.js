; $(document).ready(function(){
    iframe_auto_height(); //當文件ready時才能正確取得iframe內容的高度
  });
  //iframe auto height主程式
  function iframe_auto_height(){
    //if(!this.in_site()) return;
    var iframe;
    $(parent.document).find("iframe").map(function(){ //找到自己的iframe
      if($(this).contents().get(0).location == window.location) iframe = this;
    });
    if(!iframe) return;//no parent
	if(parent.document.location.pathname=='/AMS/index.php') return;//index的main框架不可變動
    var content_height = $("body").height()+80;
    content_height = typeof content_height == 'number' ? content_height+"px" : content_height;
	iframe.style.height = content_height;
  }
  //判斷是否在網頁的iframe之中
  function in_site(){
    if(parent != window && this.is_crosssite() == false) return(true);
    return(false);
  }
  //判斷是否跨站(可能是別人嵌入了你的網頁)
  function is_crosssite() {
    try {
      parent.location.host;
      return(false);
    }
    catch(e) {
      return(true);
    }
  };