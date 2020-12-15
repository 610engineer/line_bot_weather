#!/usr/bin/php
<?php
require_once('phpQuery-onefile.php');
$channelToken = ''; //アクセストークン
$headers = [
	'Authorization: Bearer ' . $channelToken,
	'Content-Type: application/json; charset=utf-8',
];

//気象庁愛知県の天気予報サイト
$html = file_get_contents("https://www.jma.go.jp/jp/yoho/329.html");

//降水確率の文字列を取得
$rain_day[0] = phpQuery::newDocument($html)->find("#base")->find("#main")->find("div")->find("#forecasttablefont")->find("td.rain:eq(0)")->find("div")->find("table")->find("td:eq(3)")->text();
$rain_day[1] = phpQuery::newDocument($html)->find("#base")->find("#main")->find("div")->find("#forecasttablefont")->find("td.rain:eq(0)")->find("div")->find("table")->find("td:eq(5)")->text();
$rain_day[2] = phpQuery::newDocument($html)->find("#base")->find("#main")->find("div")->find("#forecasttablefont")->find("td.rain:eq(0)")->find("div")->find("table")->find("td:eq(7)")->text();

//6-12
//12-18
//18-24
//降水確率を数値に変換用変数
$rain_int[0] = 0;
$rain_int[1] = 0;
$rain_int[2] = 0;

//一日の最高降水確率用変数
$highest_value = 0;
//時間帯判別用変数
$timeFlame = 0;
$tx_time = "";


for($i = 0 ; $i < 3 ; $i++){
    //文字列から％を取り除く
    $word = rtrim($rain_day[0], "%");
    //文字列が--の場合0を代入
    if(strcmp("--" , $word ) == 1){
        $rain_int[$i] = 0;
    }else{
    //$wordを数値に型変換して代入
        $rain_int[$i] = intval($word);
    }
    //最高降水確率より高い場合は更新
    if($highest_value < $rain_int[$i]){
        $highest_value = $rain_int[$i];
        $timeFlame = $i;
    }
}

//時間帯の文字列を代入
switch($timeFlame){
    case 0;
        $tx_time = "6時-12時";
        break;
    
    case 1:
        $tx_time = "12時-18時";
        break;

    case 2:
        $tx_time = "18時-24時";
        break;

    default :
        break;
}

//最高降水確率が0～20％の場合
//最高降水確率が30%～60％の場合
//最高降水確率が70％～の場合
if($highest_value >= 0 && $highest_value < 30){
    $text = "今日は傘はいらないと思います！\n本日の最高降水確率は\n" . $tx_time . "　" . $highest_value . "%";
}else if($highest_value >= 30 && $highest_value < 70){
    $text = "今日は折り畳み傘があるといいかもしれません！\n本日の最高降水確率は\n" . $tx_time . "　" . $highest_value . "%";
}else{
    $text = "今日は傘を持っていきましょう！\n本日の最高降水確率は\n" . $tx_time . "　" . $highest_value . "%";
}

//$text = 'test2'; //メッセージテキスト
$post = [
	'messages' => [
		[
			'type' => 'text',
			'text' => $text,
		],
	],
];
$post = json_encode($post);

$ch = curl_init('https://api.line.me/v2/bot/message/broadcast'); //一斉送信
$options = [
	CURLOPT_CUSTOMREQUEST => 'POST',
	CURLOPT_HTTPHEADER => $headers,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_BINARYTRANSFER => true,
	CURLOPT_HEADER => true,
	CURLOPT_POSTFIELDS => $post,
];
curl_setopt_array($ch, $options);

$result = curl_exec($ch);
$errno = curl_errno($ch);
if ($errno) {
	return;
}

$info = curl_getinfo($ch);
$httpStatus = $info['http_code']; //200なら成功

$responseHeaderSize = $info['header_size'];
$body = substr($result, $responseHeaderSize); //エラーメッセージ等

echo $httpStatus . '_' . $body; //ログ出力
?>