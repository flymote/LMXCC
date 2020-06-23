<?php
namespace Yurun\Util;
require_once 'Util/Chinese.php';
require_once 'Util/Chinese/Pinyin.php';
require_once 'Util/Chinese/JSONIndex.php';
require_once 'Util/Chinese/PinyinSplit.php';
require_once 'Util/Chinese/SQLiteData.php';
require_once 'Util/Chinese/Traits/JSONInit.php';
require_once 'Util/Chinese/Driver/Pinyin/BaseInterface.php';
require_once 'Util/Chinese/Driver/Pinyin/Base.php';
require_once 'Util/Chinese/Driver/Pinyin/JSON.php';
require_once 'Util/Chinese/Driver/Pinyin/SQLite.php';

use \Yurun\Util\Chinese\Pinyin;

// 设为性能模式
// Chinese::setMode('Memory');
// 性能模式占用内存大，如果提示内存不足，请扩大内存限制
// ini_set('memory_limit','256M');

// 设为通用模式，支持 PDO_SQLITE 的情况下为默认
// Chinese::setMode('SQLite');

// 设为兼容模式，不支持 PDO_SQLITE 的情况下为默认
// Chinese::setMode('JSON');

// 汉字转拼音
if (!empty($_POST['string']))
	$string = $_POST['string'];
else 
	die("");
// echo $string, PHP_EOL;
// echo '所有结果:', PHP_EOL;
// var_dump(Chinese::toPinyin($string));
// echo '全拼:', PHP_EOL;
// var_dump(Chinese::toPinyin($string, Pinyin::CONVERT_MODE_PINYIN));
// echo '首字母:', PHP_EOL;
// var_dump(Chinese::toPinyin($string, Pinyin::CONVERT_MODE_PINYIN_FIRST));
// echo '读音:', PHP_EOL;
// var_dump(Chinese::toPinyin($string, Pinyin::CONVERT_MODE_PINYIN_SOUND));
// echo '读音数字:', PHP_EOL;
// var_dump(Chinese::toPinyin($string, Pinyin::CONVERT_MODE_PINYIN_SOUND_NUMBER));

// echo '自选返回格式 + 以文本格式返回 + 自定义分隔符:', PHP_EOL;
$str = Chinese::toPinyin($string, Pinyin::CONVERT_MODE_PINYIN | Pinyin::CONVERT_MODE_PINYIN, '');
$text=preg_replace("/[~`!@#$%^&*\(\)\+=\[\]\{\}\\;:<>\/\?\s]+/",'_',strtolower($str['pinyin'][0])); //去掉英文符号及空格，允许 . - _
$text=urlencode($text);
//下面去掉中文符号，也重复了 / 等特殊的url符号
$text=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99|%EF%BD%9E|%EF%BC%8E|%EF%BC%88)+/",'.',$text);
$text=urldecode($text); 
//替换常见的特殊中文符号
$text = str_replace(array("　","＂","＇","｜","＼","／","【","】","｀","《","》","＞","＜","（","）","、","１","２","３","４","５","６","７","８","９","０"), array("","_","_","_","_","_","_","_",".","_","_","_","_",".",".",".","1","2","3","4","5","6","7","8","9","0"), $text);
die($text);

// // 拼音分词
// $string2 = 'xianggang';
// echo '"', $string2, '"的分词结果：', PHP_EOL;
// var_dump(Chinese::splitPinyin($string2));
// // 简繁互转
// require_once 'Util/Chinese/SimplifiedAndTraditional.php';
// require_once 'Util/Chinese/Driver/SimplifiedTraditional/BaseInterface.php';
// require_once 'Util/Chinese/Driver/SimplifiedTraditional/Base.php';
// require_once 'Util/Chinese/Driver/SimplifiedTraditional/SQLite.php';
// require_once 'Util/Chinese/Driver/SimplifiedTraditional/JSON.php';
// $string3 = '中华人民共和国！恭喜發財！';
// echo '"', $string3, '"的简体转换：', PHP_EOL;
// var_dump(Chinese::toSimplified($string3));
// echo '"', $string3, '"的繁体转换：', PHP_EOL;
// var_dump(Chinese::toTraditional($string3));
// //
// echo '当前模式:', Chinese::getMode(), PHP_EOL;
// echo '开始内存:', $mem1, '; 结束内存:', memory_get_usage(), '; 峰值内存:', memory_get_peak_usage(), PHP_EOL;