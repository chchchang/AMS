; $(document).ready(function(){
    iframe_auto_height(); //����ready�ɤ~�ॿ�T���oiframe���e������
  });
  //iframe auto height�D�{��
  function iframe_auto_height(){
    //if(!this.in_site()) return;
    var iframe;
    $(parent.document).find("iframe").map(function(){ //���ۤv��iframe
      if($(this).contents().get(0).location == window.location) iframe = this;
    });
    if(!iframe) return;//no parent
	if(parent.document.location.pathname=='/AMS/index.php') return;//index��main�ج[���i�ܰ�
    var content_height = $("body").height()+80;
    content_height = typeof content_height == 'number' ? content_height+"px" : content_height;
	iframe.style.height = content_height;
  }
  //�P�_�O�_�b������iframe����
  function in_site(){
    if(parent != window && this.is_crosssite() == false) return(true);
    return(false);
  }
  //�P�_�O�_��(�i��O�O�H�O�J�F�A������)
  function is_crosssite() {
    try {
      parent.location.host;
      return(false);
    }
    catch(e) {
      return(true);
    }
  };