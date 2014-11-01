<?php
/**
 * 这个文件里面定义了各类编解码函数。
 *
 * 20130112
 * 把ucclient.php里面的uc_authcode()搬过来，便于程序调用。必须一致啊，否则不知道会发生些什么。
 * 添加自己琢磨的编解码函数sw_urlcode()。适用于url的加解密。还只弄出最简单的变换。
 *
 */


/**
 * define the function uc_authcode()
 *
 * this function comes from the UC Client. 
 * if it is defined in ucclient.php, pass it.
 * must same as above function, otherwise it will cause a lot of err.
 *
 * @since 2.0.0
 */
if ( !defined('SW_KEY') )
	define( 'SW_KEY', AUTH_KEY ? AUTH_KEY : '12345678' );
	
if(!function_exists('sw_authcode')) {
function sw_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	$ckey_length = 4;

	$key = md5($key ? $key : SW_KEY);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}
}

/**
 * define the function sw_urlcode()
 *
 * 自己琢磨出来的编解码. 
 * 功能：将字符串编码成可以在url中传输。带相应解码功能
 *
 * 20130111
 */

function sw_urlcode($s, $operation = 'DECODE', $method=1, $key = '', $expiry = 0){
	if($operation == 'DECODE')return sw_decode($s);
	if($operation == 'ENCODE')return sw_encode($s, $method);
}
function sw_urlencode($s, $method=1, $key = '', $expiry = 0){
	return sw_encode($s, $method);
}
function sw_urldecode($s, $method=1, $key = '', $expiry = 0){
	return sw_decode($s);
}
//思路：如果一个字符串被认定是encode，则开始解码。
//首先从字符串中获得解码需要的key。通过两步实现。截取字符串的第2个字符，它的大小表示key有几个字符。
//从第3个字符开始，就是key，长度由第2个字符确定。再往后就是encode的字符串。
//第1个字符表示什么意思呢？表示encode和decode的方法。
//暂且将这种方法定为方式1。

function sw_encode($s,$method=1){
	if($method==2)return "2".encode2($s); //附加一个字符，表示encode方式。
	else return "1".encode1($s); //缺省使用第一种encode方式。
}
function sw_decode($s){
	$method=get_method_id($s);$str_encode=get_encode_str($s);
	if($method==1)return decode1($str_encode);
	elseif($method==2)return decode2($str_encode);
	else return decode1($str_encode); //缺省使用decode方式1。
}

//===================================================================================================
//获取字符串的encode、decode方式（即取字符串的第一个字符）
function get_method_id($s){return substr($s,0,1);}
//获得编码的字符串
function get_encode_str($s){return substr($s,1);}


//================================= 第1种encode、decode方式 ==============================================
//直接将字符翻译成hex。适合ASCII字符，中文好像也可以。
//前面附加1字符表示编码方式。编码方式（0:unknow;1:UTF-8;2:GBK;3:ISO8859-1）
//结果："abc中文"-->"616263E4B8ADE69687"(1:UTF-8)-->"abc中文"  （php不分中文编码方式，只按照byte(char)转换成Hex串）
function char2hexstr($c){return dechex(ord($c));} //字符转换成16进制串
function hexstr2char($hex){return chr(hexdec($hex));} //16进制串转换成字符
function string2hexstr($s){for($i=0;$i<strlen($s);$i++){$r=$r.char2hexstr(substr($s,$i,1));}return $r;} //将字符串转换成16进制串
function hexstr2string($s){for($i=1;$i*2<=strlen($s);$i++){$r=$r.hexstr2char(substr($s,($i-1)*2,2));}return $r;} //将16进制串转换成字符串
//encode方式1。前面附加一个字符，表示中文的编码方式。缺省中文编码方式1:UTF-8。
//其实php里面不分这个，照byte（也就是char）编码。GBK也同样编码过去。
function encode1($s){return "1".string2hexstr($s);} 
//decode方式1。要去掉第一个字符（编码方式）。
function decode1($s){return hexstr2string(substr($s,1));} 


//================================= 第2种encode、decode方式 ==============================================
//在第一种方式的基础上。每个字符hex用分隔符"-"分开，便于解码识别。不适用中文。
//结果："abc中文"-->"-61-62-63-d6-d0-ce-c4"-->"abc中文"
function get_encode_str2($s){return substr($s,1);} //获得编码的字符串
//function char2hexstr($c){return dechex(ord($c));} //字符转换成16进制串
//function hexstr2char($hex){return chr(hexdec($hex));} //16进制串转换成字符
function string2hexstr2($s){for($i=0;$i<strlen($s);$i++){$r=$r."-".char2hexstr(substr($s,$i,1));}return $r;} //将字符串转换成16进制串
function hexstr2string2($s){$s=str_replace("-","",$s);for($i=1;$i*2<=strlen($s);$i++){$r=$r.hexstr2char(substr($s,($i-1)*2,2));}return $r;} //将16进制串转换成字符串
function encode2($s){return "2".string2hexstr2($s);} //encode方式2
function decode2($s){return hexstr2string2($s);} //decode方式2


//================================= 第3种encode、decode方式 ==============================================
//还没想好。
//结果："abc中文"-->
function get_keylength($s){return substr($s,1,1);} //获得密钥字符串长度
function get_key($s){return substr($s,2,get_keylength($s));} //获得密钥
//function get_encode_str2($s){return substr($s,2+get_keylength($s));} //获得编码的字符串






//==== 测试 ===========================================
//$s="abc中文";
//$ss=encode($s,1);
//$sss=decode($ss);
//echo $ss."||".$sss;
//$s="ab:ab=0,cd=1,cee;";
//$ss=get_cmdname($s);
//$sss=get_parvalue($s,"cee");
//if (has_parname($s,"cee"))$sss="ok";
//echo $s."||".$ss."||".$sss;

?>
