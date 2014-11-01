<?php
/**
 * 这里是自定义的编解码函数。
 * 2014-11-1
 *
 */

function swencode($s,$method=1){
	if($method==1)return "swstr1"._sw_encode1($s); 	//附加一个字符，表示encode方式。
	if($method==2)return "swstr2"._sw_encode2($s); 	//附加一个字符，表示encode方式。
	else return; 									//如果没有相应的编码方式，返回空。
}
function swdecode($s){
	if(!(strtolower(substr($s,0,5))=="swstr")) return;		//先判断前5个字符是不是"swstr"，如果不是，说明不是swstr编码，返回空。
	$method = substr($s,5,1);		//获取编码方式
	$str_encode = substr($s,6);		//获取编码后的字符串
	if($method==1)return _sw_decode1($str_encode);
	elseif($method==2)return _sw_decode2($str_encode);
	else return; 					//如果没有相应的编码方式，返回空。
}

//================================= 第1种encode、decode方式 ==============================================
//直接将字符翻译成hex。适合ASCII字符，中文好像也可以。
//前面附加1字符表示编码方式。编码方式（0:unknow;1:UTF-8;2:GBK;3:ISO8859-1）
//结果："abc中文"-->"616263E4B8ADE69687"(1:UTF-8)-->"abc中文"  （php不分中文编码方式，只按照byte(char)转换成Hex串）
function _sw_char2hexstr($c){return dechex(ord($c));} 		//字符转换成16进制串
function _sw_hexstr2char($hex){return chr(hexdec($hex));} 	//16进制串转换成字符
function _sw_string2hexstr($s){for($i=0;$i<strlen($s);$i++){$r=$r._sw_char2hexstr(substr($s,$i,1));}return $r;} 			//将字符串转换成16进制串
function _sw_hexstr2string($s){for($i=1;$i*2<=strlen($s);$i++){$r=$r._sw_hexstr2char(substr($s,($i-1)*2,2));}return $r;} 	//将16进制串转换成字符串
//encode方式1。前面附加一个字符，表示中文的编码方式。缺省中文编码方式1:UTF-8。
//其实php里面不分这个，照byte（也就是char）编码。GBK也同样编码过去。
function _sw_encode1($s){return _sw_string2hexstr($s);} 
//decode方式1。要去掉第一个字符（编码方式）。
function _sw_decode1($s){return _sw_hexstr2string($s);} 


//================================= 第2种encode、decode方式 ==============================================
//在第一种方式的基础上。每个字符hex用分隔符"-"分开，便于解码识别。不适用中文。
//结果："abc中文"-->"-61-62-63-d6-d0-ce-c4"-->"abc中文"
function _sw_get_encode_str2($s){return substr($s,1);} 	//获得编码的字符串
//function char2hexstr($c){return dechex(ord($c));} 	//字符转换成16进制串
//function hexstr2char($hex){return chr(hexdec($hex));} //16进制串转换成字符
function _sw_string2hexstr2($s){for($i=0;$i<strlen($s);$i++){$r=$r."-"._sw_char2hexstr(substr($s,$i,1));}return $r;} //将字符串转换成16进制串
function _sw_hexstr2string2($s){$s=str_replace("-","",$s);for($i=1;$i*2<=strlen($s);$i++){$r=$r._sw_hexstr2char(substr($s,($i-1)*2,2));}return $r;} //将16进制串转换成字符串
function _sw_encode2($s){return _sw_string2hexstr2($s);} 		//encode方式2
function _sw_decode2($s){return _sw_hexstr2string2($s);} 		//decode方式2


//================================= 第3种encode、decode方式 ==============================================
//还没想好。
//结果："abc中文"-->
function _sw_get_keylength($s){return substr($s,0,1);} 					//获得密钥字符串长度
function _sw_get_key($s){return substr($s,1,_sw_get_keylength($s));} 	//获得密钥
//function get_encode_str2($s){return substr($s,2+get_keylength($s));} //获得编码的字符串





//==== 使用说明 ===========================================
//include "swincode.php";
//$ss = swencode($s,1);		//将字符串$s以第一种编码方式编码。
//$sss = swdecode($ss);		//解码字符串$ss


//==== 测试 ===========================================
//$s = "abc中文";
//$ss = swencode($s,2);
//$sss = swdecode($ss);
//echo $s."<br /><br />".$ss."<br /><br />".$sss;

?>
