; 
$('head').append('<meta http-equiv="x-frame-options" content="sameorigin">')
.append('<style id="antiClickjack">body{display:none !important;}</style>');
if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById("antiClickjack");
		antiClickjack.parentNode.removeChild(antiClickjack);
} else {
		throw new Error("拒絕存取!");
		$('html').empty();
		//top.location = self.location;
};
