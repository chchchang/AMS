<?php
echo '
<meta http-equiv="x-frame-options" content="sameorigin">
<style id="antiClickjack">body{display:none !important;}</style>
<script>
if (self.location.hostname === top.location.hostname) {
		var antiClickjack = document.getElementById("antiClickjack");
		antiClickjack.parentNode.removeChild(antiClickjack);
} else {
		$("html").empty();
		throw new Error("拒絕存取!");
		//top.location = self.location;
};
</script>
';

?>