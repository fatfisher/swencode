<?php
/**
 * 这里是自定义的编解码函数。
 * 2014-11-1
 *
 */
 
// 注意！！如果采用第2种编解码方式，必须要定义 $SWC_KEY 。
if(!$SWC_KEY) $SWC_KEY = "swstr";

function swencode($s,$method=1){
	if($method==1)return "swstr1"._swc_encode1($s); 	//附加一个字符，表示encode方式。
	if($method==2)return "swstr2"._swc_encode2($s,$SWC_KEY); 	//附加一个字符，表示encode方式。
	else return; 									//如果没有相应的编码方式，返回空。
}
function swdecode($s){
	if(!(strtolower(substr($s,0,5))=="swstr")) return;		//先判断前5个字符是不是"swstr"，如果不是，说明不是swstr编码，返回空。
	$method = substr($s,5,1);		//获取编码方式
	$str_encode = substr($s,6);		//获取编码后的字符串
	if($method==1)return _swc_decode1($str_encode);
	elseif($method==2)return _swc_decode2($str_encode,$SWC_KEY);
	else return; 					//如果没有相应的编码方式，返回空。
}

//================================= 第1种encode、decode方式 ==============================================
//直接将字符翻译成hex。适合ASCII字符，中文好像也可以。
//前面附加1字符表示编码方式。编码方式（0:unknow;1:UTF-8;2:GBK;3:ISO8859-1）
//结果："abc中文"-->"616263E4B8ADE69687"(1:UTF-8)-->"abc中文"  （php不分中文编码方式，只按照byte(char)转换成Hex串）
function _swc_char2hexstr($c){return dechex(ord($c));} 		//字符转换成16进制串
function _swc_hexstr2char($hex){return chr(hexdec($hex));} 	//16进制串转换成字符
function _swc_string2hexstr($s){for($i=0;$i<strlen($s);$i++){$r=$r._swc_char2hexstr(substr($s,$i,1));}return $r;} 			//将字符串转换成16进制串
function _swc_hexstr2string($s){for($i=1;$i*2<=strlen($s);$i++){$r=$r._swc_hexstr2char(substr($s,($i-1)*2,2));}return $r;} 	//将16进制串转换成字符串
//encode方式1。前面附加一个字符，表示中文的编码方式。缺省中文编码方式1:UTF-8。
//其实php里面不分这个，照byte（也就是char）编码。GBK也同样编码过去。
function _swc_encode1($s){return _swc_string2hexstr($s);} 
//decode方式1。要去掉第一个字符（编码方式）。
function _swc_decode1($s){return _swc_hexstr2string($s);} 


//================================= 第2种encode、decode方式 ==============================================
//假设双方有共同的key，通过相同的算法加解密。
function _swc_encode2($s,$key){return _swc_authcode($s,'ENCODE',$key);} 		//encode方式2
function _swc_decode2($s,$key){return _swc_authcode($s,'DECODE',$key);} 		//decode方式2


//================================= 第3种encode、decode方式 ==============================================
//还没想好。
//结果："abc中文"-->
function _swc_get_keylength($s){return substr($s,0,1);} 					//获得密钥字符串长度
function _swc_get_key($s){return substr($s,1,_swc_get_keylength($s));} 	//获得密钥
//function get_encode_str2($s){return substr($s,2+get_keylength($s));} //获得编码的字符串






//================================= 经常用到的算法 ==============================================
// 就是ucclient.php里面的uc_authcode()
// this function comes from the UC Client. 
// 这函数不错，编码出来的直接是可显示的字符串，不用再加工就可以使用了。
// 好像二进制码也可以被编码。
// 不过，好像编码后长度太长了。如果要编码的字符串太长不知道会怎样。
// V1.0 20141101
function _swc_authcode($string, $operation = 'DECODE', $key = 'swstr', $expiry = 0) {

	$ckey_length = 4;

	//$key = md5($key ? $key : SW_KEY);
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




//==== 使用说明 ===========================================
//include "swincode.php";
//$ss = swencode($s,1);		//将字符串$s以第一种编码方式编码。
//$sss = swdecode($ss);		//解码字符串$ss


//==== 测试 ===========================================
$s = "abc中文";
$s = "//将字符串s以第一种编码方式编码。//解码字符串ss";
$ss = swencode($s,2);
$sss = swdecode($ss);
echo $s."<br /><br />".$ss."<br /><br />".$sss;

?>
