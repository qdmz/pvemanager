<?php 
// 设置输出编码为UTF-8
define('DEFAULT_ENCODING', 'UTF-8');
header('Content-Type: text/html; charset=' . DEFAULT_ENCODING);

include "inc/conn.php";

// 使用新的Excel读取类
include "inc/excel_reader.php";
?>
 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
 <html xmlns="http://www.w3.org/1999/xhtml">
 <head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0"/>
 <meta name="apple-mobile-web-app-capable" content="yes" />
 <title><?php echo $title;?></title>
 <meta property="qc:admins" content="2c9a085366c30b3b0b61af9f57cf8915" />
 <meta name="baidu-site-verification" content="533066cc044c77518994dc7d3a0d7279" />
 <meta name="author" content="yujianyue, admin@ewuyi.net">
 <meta name="copyright" content="www.12391.net">
 <script type="text/javascript" src="inc/js/ajax_wap.js"></script>
 <link href="inc/css/wap.css" rel="stylesheet" type="text/css" />
 <body onLoad="inst();">
 <div class="sub_bod"></div>
 <div class="sub_top">
 	<div class="title"><?php echo $title;?></div>
 	<!---<div class="back" id="pageback"><a href="?b=back" class="d">    </a></div> ---> 
 	<!---<div class="menu" id="topmenus"><a href="http://test.96448.cn" class="d"> 菜单 </a></div>--->
 </div><div class="main">
 <?php 


$stime=microtime(true); 
$codes = trim($_POST['code']);
$shujus = trim($_POST['time']);
$shuru1 = trim($_POST['name']);
if(!$shujus){
?>
<form name="queryForm" method="post" class="" action="?t=<?php echo time();?>" onsubmit="return startRequest(0);">
<div class="select" id="10">
<select name="time" id="time" onBlur="startRequest(1)" />
<?php traverse($UpDir."/");?></select>
</div>
<div class="so_box" id="11">
<input name="name" type="text" class="txts" id="name" value="" placeholder="      请输入<?php echo $tiaojian1;?>" onfocus="st('name',1)" onBlur="startRequest(2)" />
</div>
<?php 
if($ismas=="1"){
?>
<div class="so_box" id="33">
<input name="code" type="text" class="txts" id="code" placeholder="        验证码 " onfocus="this.value=''" onBlur="startRequest(3)" />
<div class="more" id="clearkey">
<img src="inc/code.php?t=<?php echo date("Y-m-d-H-i-s",time());?>" id="Codes" onClick="this.src='inc/code.php?t='+new Date();"/>
</div></div>
<?php }?><div class="so_boxes">
<input type="submit" name="button" class="buts" id="sub" value="      查询" />
</div>
<div class="so_boxex" id="tishi">提示:  <?php echo $tiaojian1;?><?php 
if($ismas=="1"){
?> + 验证码 <?php }?> 请确保输入正确信息
<!---       说明文字          开始--->


<!---       说明文字          结束--->
</div>
<div id="tishi1" style="display:none;">请输入 <?php echo $tiaojian1;?></div>
<div id="tishi4" style="display:none;">请输入4位验证码</div>
</form>
<?php 


}else{
if($ismas=="1"){
session_start();
if($codes!=$_SESSION['PHP_M2T']){
 webalert("验证码输入错误!");
}
}
if(!$shuru1){
 webalert("请输入<?php echo $tiaojian1;?>!");
}

// 首先尝试XLSX文件，然后尝试XLS文件
$files_xlsx = $UpDir."/".$shujus.".xlsx";
$files_xls = $UpDir."/".$shujus.".xls";

// 先用charaget处理文件名
$files_xlsx = charaget($files_xlsx);
$files_xls = charaget($files_xls);

// 检查XLSX文件
if(file_exists($files_xlsx)){
    $files = $files_xlsx;
} else if(file_exists($files_xls)){
    $files = $files_xls;
} else {
    // 如果直接文件不存在，尝试使用characet处理
    $files_xlsx_alt = characet($files_xlsx);
    $files_xls_alt = characet($files_xls);
    
    if(file_exists($files_xlsx_alt)){
        $files = $files_xlsx_alt;
    } else if(file_exists($files_xls_alt)){
        $files = $files_xls_alt;
    } else {
        webalert('找不到数据文件!');
    }
}

if(file_exists($files)){
 echo '<p align="center"> ';
 echo $shujus; 
 echo '</p>';
}
echo '<!--startprint-->';
$data = new ExcelReader(); 
$data->setOutputEncoding('UTF-8'); 

// 检查文件是否存在并尝试读取
if (!file_exists($files)) {
    webalert('找不到数据文件: ' . $files);
}

try {
    $data->read($files);
    
    // 检查Excel数据是否有效
    if (!isset($data->sheets[0]['numRows']) || !isset($data->sheets[0]['numCols'])) {
        webalert('Excel文件格式错误或文件损坏!');
    }
} catch (Exception $e) {
    webalert('读取Excel文件时出错: ' . $e->getMessage());
}

for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) { 
 if($i=="1"){
 $iaa=0;
 $iab=0;
 //echo '<tr class="tt">';
for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) { 
$taba = ''.$data->sheets[0]['cells'][$i][$j].'';
$taba = mb_convert_encoding($taba, 'UTF-8', 'auto'); 
 //echo '<td class="r">'.$taba.'</td>';
      $io++; 
    if($taba==$tiaojian1){
      $iaa=$io; 
    }
} 
 //echo '</tr>';
    if(strlen($iaa)<1){   //if($iaa){
 webalert('Excel表第1行中没有找到字段 '.$tiaojian1.' 列!');
    }else{
echo "<!--'.$tiaojian1.'='.$iaa.'-->\r\n";
    }
echo "\r\n";
 }else{
 $Excelx=$data->sheets[0]['cells'][$i][$iaa];
 $Excelx=mb_convert_encoding($Excelx, 'UTF-8', 'auto'); 
if("_".$shuru1=="_".$Excelx){
echo "<!-- $shuru1 == $Excelx -->\r\n";
 $iae++; 
echo '<table cellspacing="0">';
echo "<caption align='center'> 查询结果 $iae</caption>";
for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) { 
 $tabe = ''.$data->sheets[0]['cells']['1'][$j].'';
 $tabe = mb_convert_encoding($tabe, 'UTF-8', 'auto'); 
$tabu = ''.$data->sheets[0]['cells'][$i][$j].'';
 $tabu = mb_convert_encoding($tabu, 'UTF-8', 'auto'); 
 echo '<tr>';
 echo '<td class="r">'.$tabe.'</td>';
 echo '<td class="span">'.$tabu.'</td>';
 echo "</tr>\r\n";
} 
echo '</table>'; 
} 
}
}

if($iae<1){
echo '<table cellspacing="0">';
echo "<caption align='center'> 查询结果 </caption>";
    echo '<tr>';
        echo "<td colspan='2'>没有找到查询 $tiaojian1=$shuru1 的相关信息哦</td>";
    echo "</tr>\r\n";
echo '</table>'; 
}

echo '<!--endprint-->';
// fclose($filer);
?>
<div class="so_boxesd">
<input type="button" name="print" value="预览打印" onclick="preview()" class="buts">
<input type="button" value="返回" class="buts" onclick="location.href='?t=back';" id="reset"></div>
<?php 


}
$etime=microtime(true);// 获取程序执行结束的时间 
$total=$etime-$stime;   // 计算差值
echo "<!--页面执行时间：{$total} ]  --->";
?>
</div>
<div class="foot">
  <div class="title"> 
    <span>&copy;<?php echo date('Y');?>&nbsp; <a href="<?php echo $copyu;?>" target="_blank"><?php echo $copyr;?></a></span>
  </div> 
</div>
</body>
</html>