<?php 


// * conn.php
error_reporting(0);
header("content-Type: text/html; charset=utf-8");//统一使用UTF-8编码

//参数设置3-8个，双引号内不能有空格回车;
$tiaojian1="姓名";			//查询条件1列标题，excel表头一行，注意无空格回车;

$UpDir="shujukufangzheli";			//数据文件存放目录(文件名字)不能修改，修改后对应文件夹中;
$title="数据库查询系统";			//设置查询标题,随便改改;
$copyr="数据库查询手机apk软件";				//设置底部版权信息,随便改改;
$copyu="https://tianping.ypvps.com/chaxun1/chaxun1.apk";			//设置底部版权信息,随便改改;

$ismas="1";				//设置是否使用验证码，1是0否;

// 管理员账户设置
$admin_user = 'admin';
$admin_pass = 'admin123';				// 请在首次使用后修改此默认密码



function webalert($Key){
 $html="<script>\r\n";
 $html.="alert('".$Key."');\r\n";
 $html.="history.go(-1);\r\n";
 $html.="</script>";
 exit($html);
}


function characet($data){
  if(!empty($data) ){    
    $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;   
    if( $fileType != 'UTF-8'){   
      $data = mb_convert_encoding($data ,'utf-8' , $fileType);   
    }   
  }   
  return $data;    
}

function charaget($data){
  if(!empty($data) ){    
    $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;   
    if( $fileType != 'UTF-8'){   
      $data = mb_convert_encoding($data ,'UTF-8' , $fileType);   
    }   
  }   
  return $data;    
}


//查找下拉选项时
function traverse($dir_name = '.') {
$dir = opendir($dir_name); 
$basename = basename($dir_name); 
$fileArr = array(); 
while ($file_name = readdir($dir)) 
{ 
if (($file_name ==".") || ($file_name == "..")) { 
 } else if(is_dir($file_name)) {
 } else {
$fileext=substr($file_name,-4);
$fileext2=substr($file_name,-5); // For .xlsx extension
$fileext=strtolower($fileext);
$fileext2=strtolower($fileext2);

// Check for both .xls and .xlsx extensions
if($fileext == ".xls" || $fileext2 == ".xlsx"){
    $filesw=substr($file_name,0,strrpos($file_name,'.')); // Get filename without extension
    $file = charaget($filesw);    //
    echo '<option value="'.trim($file).'">' . trim($file) . '</option>';
} 
 } 
} 
closedir($dir); 
}



?>