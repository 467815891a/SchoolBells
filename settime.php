<?php
if (isset($_GET['syncnow'])){
    $temp = exec(dirname(__FILE__).DIRECTORY_SEPARATOR."synctime.sh ",$arr,$return_var);
} else {
    if (isset($_POST['year'])&&isset($_POST['month'])&&isset($_POST['day'])&&isset($_POST['hour'])&&isset($_POST['min'])&&isset($_POST['sec'])){
      $year=intval($_POST['year']);$month=intval($_POST['month']);$day=intval($_POST['day']);$hour=intval($_POST['hour']);$min=intval($_POST['min']);$sec=intval($_POST['sec']);
      if (is_int($year)&&is_int($month)&&is_int($day)&&is_int($hour)&&is_int($min)&&is_int($sec)){
        if (checkdate($month,$day,$year)&&$hour<24&&$hour>=0&&$min>=0&&$min<60&&$sec>=0&&$sec<60){
            $timestr=strval($year).'-'.strval($month).'-'.strval($day).' '.strval($hour).':'.strval($min).':'.strval($sec);
            $temp = exec(dirname(__FILE__).DIRECTORY_SEPARATOR."synctime.sh ".$timestr,$arr,$return_var);
        } else {
            echo "时间不符合常识！请重新检查！";
            die(65);
        }
    } else {
        echo "必须输入自然数！请重新检查！";
        die(65);
    }
} else {
    echo "时间输入不全！请重新检查！";
    die(65);
}
}

if ($return_var == 0){
    echo "指令执行成功！详细结果如下：";
} else {
    echo "指令执行失败！详细结果如下：";
}
foreach ($arr as $val){
    echo $val."<br>";  
}
exit(0);
?>
