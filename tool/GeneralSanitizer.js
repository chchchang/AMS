/****
2022/04/26 chia_chi_chang
修復各種injection弱點用Sanitizer
解決方式將Sting轉為ASCII Byte Array再轉回String
****/


var GeneralSanitizer = new (function () {
	this.sanitize = function(input) {
		const encoder = new TextEncoder()
		encodedstr = encoder.encode(input);
		str = String.fromCharCode.apply(null, encodedstr)
		return str;
	}
});
