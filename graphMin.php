<?php
mb_language('ja');
mb_internal_encoding('SJIS');

$max     = 40; //上限
$step    = 2;  //目盛の刻み
 
//値
//$lines = array(
//    array(
//        'name'   => 'A課',
//        'values' => array(20, 50, 40, 80, 100, 90, 70),
//        'color'  => array(100, 180, 255)
//    ),
//    array(
//        'name'   => 'B課',
//        'values' => array(10, 30, 60, 70, 90, 90, 80),
//        'color'  => array(255, 150, 200)
//    ),
//    array(
//        'name'   => 'C課',
//        'values' => array(60, 70, 70, 50, 60, 40, 30),
//        'color'  => array(255, 255, 150)
//    )
//);

//値

$dbDate = htmlspecialchars($_GET["dbDate"]);

//$tempValue = array(20, 50, 40, 30, 20, 30, 30);
$tempValue = array();
$labels = array();

$conn = mysqli_connect("mysql1.php.xdomain.ne.jp", "sugielectric_db", "s3g2m5t5", "sugielectric_db");

if (mysqli_connect_errno()) {
    echo "Unable to connect to DB: " . mysqli_error($conn);
    exit;
}

//if (!mysqli_select_db($conn, "sugielectric_db")) {
//    echo "Unable to select mydbname: " . mysqli_error($conn);
//    exit;
//}

if ($dbDate != 0) {
 	$dbDateAfterWork = new DateTime($dbDate);
 	$dbDateAfterWork->modify('+1 days');
 	$dbDateAfter = $dbDateAfterWork->format('Y-m-d');
 
	//$sql = "SELECT *
    //    FROM tempMstDB where recordDate between '" . $dbDate . "' and '" . $dbDateAfter . "' order by recordDate DESC ";
	$sql = "SELECT *
        FROM tempMstDB where recordDate between '" . $dbDate . "' and '" . $dbDateAfter . "' order by recordDate DESC ";
	//$sql = "SELECT *
    //    FROM tempMstDB where recordDate >= '" . $dbDate . "' order by recordDate DESC LIMIT 48";

}else{
	$sql = "SELECT *
        FROM tempMst order by recordDate DESC LIMIT 30";
}


$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Could not successfully run query ($sql) from DB: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo "No rows found, nothing to print so am exiting";
    exit;
}

$cnt = 0;

while ($row = mysqli_fetch_assoc($result)) {
    
   $tempValue[$cnt] = $row["temp"];
   $tempDate = $row["recordDate"];
   $tempDate = substr($tempDate, 11, 8);
   //echo $tempDate;
   if ((($cnt % 2) == 0) || ($dbDate != 0)){
   		$labels[$cnt] = $tempDate;
   }else{
   		$labels[$cnt] = "";
   }
   
   $cnt = $cnt + 1;

}

//$cnt = $cnt - 1;
//while ($cnt > 30) {
//    
//   unset($tempDate[$cnt]);
//   unset($tempDate[$labels]);
//   $cnt = $cnt - 1;
//}

$tempValue = array_reverse($tempValue);
$labels = array_reverse($labels);
//$tempDate = array_values($tempDate);
//$labels = array_values($labels);
//array_reverse($tempDate);
//array_reverse($labels);

unset($value); // 最後の要素への参照を解除します
mysqli_free_result($result);
mysqli_close($conn);

$lines = array(
    array(
        'name'   => '卓也室内温度',
        'values' => $tempValue,
        'color'  => array(100, 180, 255)
    )
);

//ラベル
//$labels = array('2000', '2001', '2002', '2003', '2004', '2005', '2006');
$label_rotate = true;
 
$title = '温度グラフ  ' . date("G時i分s秒");
$show_legend = true;        //凡例の表示
 
$width   = 400;
$height  = 300;
$margin_top      = 50;
$margin_right    = 100;
$margin_bottom   = 70;
$margin_left     = 50;
 
//フォント
$font = 'migmix-1p-regular.ttf';
$font_size = 10;
 
$image = imagecreatetruecolor($width + $margin_left + $margin_right, $height + $margin_top + $margin_bottom);
imageantialias($image, true);
 
$org_x = $margin_left;
$org_y = $height + $margin_top;
 
//色
$bg_color   = imagecolorallocate($image, 10, 10, 10);       //背景
$text_color = imagecolorallocate($image, 255, 255, 255);    //テキスト
$grid_color = imagecolorallocate($image, 50, 50, 50);       //グリッド
$grid_spacing = $height / $max * $step;
 
imagefill($image, 0, 0, $bg_color);
 
for($i=0;$i<=floor($max / $step);$i++){
    if($i !== 0) imageline($image, $org_x, $org_y - $grid_spacing * $i, $org_x + $width, $org_y - $grid_spacing * $i, $grid_color);
 
    $text = $i * $step;
    $box = imagettfbbox($font_size, 0, $font, $text);
    $text_width = $box[2] - $box[6];
    $text_height = $box[3] - $box[7];
     
    $text_x = $org_x - $font_size;
    $text_y = $org_y - $grid_spacing * $i;
    imagettftext($image, $font_size, 0, (-1 * $text_width) + $text_x, ($text_height / 2) + $text_y, $text_color, $font, $text);
}
 
$count = count($lines[0]['values']);
$graph_spacing = floor( $width / $count);
 
$legend_x = $org_x + $width + 20;
$legend_y = $margin_top + 10;
 
//各グラフの描画
foreach($lines as $line){
    $values = $line['values'];
    $graph_color  = imagecolorallocate($image, $line['color'][0], $line['color'][1], $line['color'][2]);
     
    for($i=0;$i<$count;$i++){
        $graph_x = $org_x + $graph_spacing * $i + round($graph_spacing / 2);
        $graph_y = $org_y - $height * $values[$i] / $max;
 
        if(isset($prev)){
            imageline($image, $prev[0], $prev[1], $graph_x, $graph_y, $graph_color);
            imageline($image, $prev[0] + round($graph_spacing / 2), $org_y, $prev[0] + round($graph_spacing / 2), $org_y + 5, $text_color);
        }
        imagefilledrectangle($image, $graph_x - 2, $graph_y - 2, $graph_x + 2, $graph_y + 2, $graph_color);
         
 
        $prev = array($graph_x,$graph_y);
    }
     
    //凡例の描画
    if($show_legend){
        $text = $line['name'];
        $box = imagettfbbox($font_size, 0, $font, $text);
        $text_width = $box[2] - $box[6];
        $text_height = $box[3] - $box[7];
        imagettftext($image, $font_size, 0, $legend_x, $legend_y, $graph_color, $font, '' . $text);
        $legend_y = $legend_y + ($text_height * 2);
    }
    unset($prev);
}
 
for($i=0;$i<$count;$i++){
        $graph_x = $org_x + $graph_spacing * $i + round($graph_spacing / 2);
         
        $text = $labels[$i];
        $box = imagettfbbox($font_size, 0, $font, $text);
        $text_width = $box[2] - $box[6];
        $text_height = $box[3] - $box[7];
         
        if($label_rotate){
            $text_x = round($text_height / 2) + $graph_x;
            $text_y = $text_width + $org_y + $font_size;
            imagettftext($image, $font_size, 90, $text_x, $text_y, $text_color, $font, $text);
        } else {
            $text_x = round((-1 * $text_width / 2)) + $graph_x;
            $text_y = ($text_height / 2) + $org_y + $font_size * 2;
            imagettftext($image, $font_size, 0, $text_x, $text_y, $text_color, $font, $text);
        }
}
 
imageline($image, $org_x, $org_y, $org_x, $margin_top, $text_color);
imageline($image, $org_x, $org_y, $org_x + $width, $org_y, $text_color);
 
$box = imagettfbbox($font_size, 0, $font, $title);
$text_width  = $box[2] - $box[6];
$text_height = $box[3] - $box[7];
$text_x = $org_x + $width / 2 - ($text_width / 2);
$text_y = $org_y - $height - $font_size * 2;
imagettftext($image, $font_size, 0, $text_x, $text_y, $text_color, $font, $title);
 
//header('Content-type: image/png');

///////////////////
// ヘッダに「data:image/png;base64,」が付いているので、それは外す
//$image= preg_replace("/data:[^,]+,/i","",$image);
 
// 残りのデータはbase64エンコードされているので、デコードする
//$image= base64_decode($image);
  
// 文字列状態から画像リソース化
//$image = imagecreatefromstring($image);
//////////////////
//header('Content-Type: image/png');

//imagepng($image);
//imagedestroy($image);

//
//if($_GET['img']){
//print file_get_contents($_GET['img']);
//exit();
//}

// Enable output buffering
ob_start();
imagepng($image);
// Capture the output
$imagedata = ob_get_contents();
// Clear the output buffer
ob_end_clean();

echo base64_encode($imagedata);
?>
