<?php
if( !headers_sent() && extension_loaded("zlib") && strstr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip")) 
{
  ini_set('zlib.output_compression', 'On');
  ini_set('zlib.output_compression_level', '5');
  ini_set('date.timezone','Asia/Shanghai');
}
session_start();
header("Content-Type:text/html;charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
$error='';
#set username and password here
$users = array(
    'wei'=>'970521'  
);

if (isset($_POST['user'])){
	if($_POST['password']==$users[$_POST['user']]){
		$_SESSION['user'] = $_POST['user'];
		$user = $_POST['user'];
	} else {
		$error = "用户名或者密码错误！";
		login($error);
		die();
	}
} else if(isset($_SESSION['user'])){
	$user = $_SESSION['user'];
	if(!in_array($user,array_keys($users))){
		login("用户名 $user 不存在！");
		die();
	}
} else {
	login($error);
	die();
}

$db = new PDO("sqlite:./bells.db");
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$schedule_names=array('schedule_spring','schedule_summer','schedule_autumn','schedule_winnter','schedule_a','schedule_b');
foreach ($schedule_names as $val){
	$db->exec("CREATE TABLE IF NOT EXISTS $val (
	    description TEXT,
		time TEXT,
		mon INTEGER,
		tue INTEGER,
		wed INTEGER,
		thu INTEGER,
		fri INTEGER,
		sat INTEGER,
		sun INTEGER
	);");
}
$db->exec("CREATE TABLE IF NOT EXISTS bells (
    description TEXT,
	date TEXT,
	time TEXT,
	PRIMARY KEY('date','time')
);");

function login($error){
	echo "
	<html>
	<head>
		<title>登录电铃系统</title>
		<style>
		body{
			font-family:sans-serif;
			text-align:center;
		}
		</style>
	</head>
	<body>
	<form method=post>
		<br>
		$error<br>
		<table style='margin:0 auto;'>
			<tr><td><h3 style='text-align:center'>请输入管理员账号和密码</h3><td>
			<tr><td>用户名<td><input name=user>
			<tr><td>密码<td><input name=password type=password>
		</table>
		<input type=submit value=登录>
	</form>
	</body>
	</html>";
}


function loadcontent($content,$function){
	echo "
	<script type='text/javascript'>
		function AjaxFunction()
		{
			var httpxml;
			try
			{
				// Firefox, Opera 8.0+, Safari
				httpxml=new XMLHttpRequest();
			}
			catch (e)
			{
				// Internet Explorer
				try
				{
					httpxml=new ActiveXObject('Msxml2.XMLHTTP');
				}
				catch (e)
				{
					try
					{
						httpxml=new ActiveXObject('Microsoft.XMLHTTP');
					}
					catch (e)
					{
						alert('Your browser does not support AJAX!');
						return false;
					}
				}
			}

			function stateck() 
			{
				if(httpxml.readyState==4)
				{
					document.getElementById('msg').innerHTML='当前系统时间：<br>' + httpxml.responseText;
					document.getElementById('msg').style.background='#f1f1f1';
				}
			}
				
			var url='ajax-server-clock-demock.php';
			url=url+'?sid='+Math.random();
			httpxml.onreadystatechange=stateck;
			httpxml.open('GET',url,true);
			httpxml.send(null);
			tt=timer_function();
		}

		function timer_function(){
			var refresh=1000; // Refresh rate in milli seconds
			mytime=setTimeout('AjaxFunction();',refresh)
		}
		window.onload=function clock(){
			document.getElementById('msg').innerHTML='当前时间：';
			timer_function();
		}
	</script>
	<html>
	<head>
		<title>校园电铃系统</title>
		<script type='text/javascript'>
	    	".file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'zepto.min.js','r')."
		</script>
		<style>
		body{
			font-family:sans-serif;
			text-align:left;
			font-size:18px;
		}
		#left {
		    position:fixed;
		    top:0px;
		    left:0px;
		    bottom:0px;
		    width:170px;
		    overflow-y:auto;
		    padding:20px;
		    background-color:#eee;
		}
		table {
		    border-collapse:collapse;
		}
		td, th {
		    border:1px solid #ccc;
		}
		h2 {
		    padding:0px;
		    margin:0px;
		}
		#left a {
		    text-decoration:none;
		    color:#000;
		    display:block;
		    margin-bottom:5px;
		}
		#content {
		    position:fixed;
		    top:0px;
		    left:0px;
		    bottom:0px;
		    left:200px;
		    overflow-y:auto;
		    padding:20px;
		}
		</style>
	</head>
	<body>
	<div id=left>
		<h2>校园电铃系统</h2>
		<div id='msg'></div>
		<br>
		<a href='./?f=main'>首页</a>
		<a href='./?f=week'>打铃方案</a>
		<a href='./?f=troubleshoot'>疑难解答</a>
		<a href='./?f=ring'>立刻打铃</a>
		<a href='./?f=logout'>退出</a>
	</div>
	<div id=content>
	$content
	</div>
	<script>
	    document.getElementById('left').querySelector('[href=\"./?f=$function\"]').style.fontWeight = 'bold';
	</script>
	</body>
	</html>";
}

if(array_key_exists('f',$_GET)){
    $function = $_GET['f'];
} else {
    $function = 'main';
}

if($function=='main'){
	if(array_key_exists('date',$_GET)){
	    $date = $_GET['date'];
	} else {
	    $date = date('Y-m-d');
	}
	$dateq = $db->quote($date);
	$datef = date('Y年n月j日',strtotime($date));
	$content = "
	<h2>修改单日打铃计划 - $datef</h2>
	<form method=get action='./' id=changedateform>
	<input type=hidden name=f value=main>
	<input type=date id=date name=date onchange='changedate()' value=$dateq></form>
	<br>
	<table id=todayschedule>
    <thead>
        <tr>
            <th>名称
            <th>时刻
            <th>删除
    </thead>
    <tbody>";
    
    $data = $db->query("SELECT * FROM bells WHERE date=$dateq ORDER BY time ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $d){
    	$description = json_encode($d['description'],JSON_UNESCAPED_UNICODE);
    	$time = json_encode($d['time'],JSON_UNESCAPED_UNICODE);
		$content .= "
        <tr>
            <td><input name=description value=$description>
            <td><input type=time name=time value=$time>
            <td><button onclick='removerow(this)'>删除</button>";
    }
            
    $content .= "
    </tbody>
    </table>
    <button onclick='addrow()'>增加打铃</button> 
    <button onclick='save()'>保存</button> 
    <button onclick='clearall()'>全部删除</button> <br>
    <form method=post action='./?f=saveday' id=saveday><input type=hidden name=newdata id=savedaydata><input type=hidden name=date id=savedaydate></form>
    <script>
    	function removerow(item){
    		$(item).closest('tr').remove();
    	}
        function addrow(){
            $('#todayschedule tbody').append('<tr><td><input name=description><td><input type=time name=time><td><button onclick=\"removerow(this)\">删除</button>');
        }
        function clearall(){
        	$('#todayschedule tbody tr').remove();
        	save();
        }
        function save(){
        	tosave = [];
            $('#todayschedule tbody tr').each(function(){
            	description = $(this).find('[name=description]').val();
            	time = $(this).find('[name=time]').val();
            	tosave.push({description,time});
            });
            console.log(tosave);
            $('#savedaydata').val(JSON.stringify(tosave));
            $('#savedaydate').val($('#date').val());
            $('#saveday').submit();
        }
        function changedate(){
        	$('#changedateform').submit();
        }
    </script>
    ";
	
    loadcontent($content,$function);
} else if($function=='troubleshoot'){
	$content = "
		<script type='text/javascript'>
		$(document).ready(function(){
			$('#btnsubmit').on('click',function (){
				$.ajax({
					type:'post',
					dataType:'text',
					url:'settime.php',
					data:$('#query_form').serialize(),
					beforeSend:function(data){
						$('#tip').html('正在更改时间，请稍后......');
					},
					success:function (data) {
						$('#tip').html(data);
					},
					error:function(data) {
						$('#tip').html(data);
					} 
				});
			}); 
			$('#btnsync').on('click', function (){
				$.ajax({
					type:'GET',
					dataType:'text',
					url : '/settime.php?syncnow=1',
					success : function(data) {
						$('#tip').html(data);
					},
					beforeSend:function(data){
						$('#tip').html('正在更改时间，请稍后......');
					},
					error:function(data) {
						$('#tip').html(data);
					}
				});
			}); 
		}); 
		</script>
		<h2>时间设置</h2><br>
		<form id='query_form' action='/settime.php' method='post' enctype='multipart/form-data' >
			<table style='font-size:16px;text-align:center;border:1px;'>
	    		<tbody>
					<tr>
						<th>年</th>
						<th>月</th>
						<th>日</th>
						<th>时</th>
						<th>分</th>
						<th>秒</th>
					</tr>
					<tr>
						<td><input style='width:100px;font-size:16px;text-align:center;' type='text' name='year' value='".date('Y')."'/></td>
						<td><input style='width:50px;font-size:16px;text-align:center;' type='text' name='month' value='".date('m')."'/></td>
						<td><input style='width:50px;font-size:16px;text-align:center;' type='text' name='day' value='".date('d')."'/></td>
						<td><input style='width:50px;font-size:16px;text-align:center;' type='text' name='hour' value='".date('H')."'/></td>
						<td><input style='width:50px;font-size:16px;text-align:center;' type='text' name='min' value='".date('i')."'/></td>
						<td><input style='width:50px;font-size:16px;text-align:center;' type='text' name='sec' value='".date('s')."'/></td>
					</tr>
				</tbody>
			</table>
			<table style='font-size:24px;text-align:center;border:none;border-color:transparent;'>
				<tr align='center'>
					<td align='center'><input type='button' value='手动更改时间' id='btnsubmit' style='padding:10px;margin:10px;' /></td>
					<td align='center'><input type='button' value='同步互联网时间时间' id='btnsync' style='padding:10px;margin:10px;' /></td>
				</tr>
			</table>
		</form>
		<div id='tip'></div>
		<br>
		<h2>重启系统</h2>
		<h3>遇到无法解决的问题，可以尝试重启，大约需要30秒系统重新上线，打铃时间表会保留</h3>
		<form action='./?f=reboot' method='post' id='reboot_form' >
			<button onclick='reboot_now()' id='btnreboot' style='padding:10px;margin:10px;' >立即重启系统</button>
		</form>
		<br>
		<h2>重置系统</h2>
		<h3>遇到重启无法解决的问题，可以尝试重置，大约需要30秒系统重新上线，打铃时间表会全部清除！</h3>
		<form action='./?f=reset' method='post'id='reset_form'>
			<button onclick='reset_now()' id='btnreset' style='padding:10px;margin:10px;' >立即重置系统</button>
		</form>
		<h2>系统日志</h2>
		<div id='log'>".nl2br(file_get_contents('/var/log/ring_log.txt'))."</div>
		<script>
	        function reboot_now(){
	        	$('#btnreboot').submit();
	        }
	        function reset_now(){
	        	$('#btnreset').submit();
	        }
    	</script>
	";
	loadcontent($content,$function);
} else if($function=='reboot'){
	loadcontent("正在重启，请于30秒后刷新页面...",'troubleshoot');
	popen('nohup $(sleep 3;/sbin/reboot) &',"r");
} else if($function=='reset'){
	loadcontent("正在重置，请于30秒后刷新页面...",'troubleshoot');
	foreach ($schedule_names as $val){
		$db->exec("DROP TABLE IF EXISTS $val");
	}
	$db->exec("DROP TABLE IF EXISTS bells");
	popen('nohup $(sleep 3;/sbin/reboot) &',"r");
} else if($function=='week'){
	$schedule_name=isset($_GET['schedule_name'])?$_GET['schedule_name']:"schedule_a";
    $content = "<h2>打铃方案</h2><br>
    <form method=get action='./' id=changescheduleform>
	<input type=hidden name=f value=week>
	<select style='color:red' name='schedule_name' onchange='changeschedule()'>
      <option value='schedule_spring' ".($schedule_name == "schedule_spring" ? "selected=''" : "").">春季作息</option>
      <option value='schedule_summer' ".($schedule_name == "schedule_summer" ? "selected=''" : "").">夏季作息</option>
      <option value='schedule_autumn' ".($schedule_name == "schedule_autumn" ? "selected=''" : "").">秋季作息</option>
      <option value='schedule_winnter' ".($schedule_name == "schedule_winnter" ? "selected=''" : "").">冬季作息</option>
      <option value='schedule_a' ".($schedule_name == "schedule_a" ? "selected=''" : "").">备用方案A</option>
      <option value='schedule_b' ".($schedule_name == "schedule_b" ? "selected=''" : "").">备用方案B</option>
	</select></form>
    <table id=weeklyschedule>
    <thead>
        <tr>
            <th><p>名称</p></th>
            <th><p>时刻</p></th>
            <th><input type='checkbox' id='mon_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 一</p></th>
            <th><input type='checkbox' id='tue_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 二</p></th>
            <th><input type='checkbox' id='wed_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 三</p></th>
            <th><input type='checkbox' id='thu_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 四</p></th>
            <th><input type='checkbox' id='fri_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 五</p></th>
            <th><input type='checkbox' id='sat_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 六</p></th>
            <th><input type='checkbox' id='sun_allcheck' onchange='allcheck(this)'><p style='display:inline-block;'> 日</p></th>
            <th><p>删除</p></th>
    </thead>
    <tbody>";
    
    $data = $db->query("SELECT * FROM $schedule_name ORDER BY time ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $d){
    	$description = json_encode($d['description'],JSON_UNESCAPED_UNICODE);
    	$time = json_encode($d['time'],JSON_UNESCAPED_UNICODE);
    	if($d['mon']==1){$mon="checked";}else{$mon="";}
    	if($d['tue']==1){$tue="checked";}else{$tue="";}
    	if($d['wed']==1){$wed="checked";}else{$wed="";}
    	if($d['thu']==1){$thu="checked";}else{$thu="";}
    	if($d['fri']==1){$fri="checked";}else{$fri="";}
    	if($d['sat']==1){$sat="checked";}else{$sat="";}
    	if($d['sun']==1){$sun="checked";}else{$sun="";}
		$content .= "
        <tr>
            <td><input name=description value=$description>
            <td><input type=time name=time value=$time>
            <td><input type=checkbox name=mon $mon>
            <td><input type=checkbox name=tue $tue>
            <td><input type=checkbox name=wed $wed>
            <td><input type=checkbox name=thu $thu>
            <td><input type=checkbox name=fri $fri>
            <td><input type=checkbox name=sat $sat>
            <td><input type=checkbox name=sun $sun>
            <td><button onclick='removerow(this)'>删除</button>";
    }
            
    $content .= "
    </tbody>
    </table>
    <button onclick='addrow()'>添加打铃</button> 
    <button onclick='save()'>保存</button> <br>
    <br>
    <b>设置此打铃方案的起始日期（请先保存）:</b><br>
    <form method=post action='./?f=applytodates' id=applytodates>
    <input type=hidden name=schedule_name value=".$schedule_name.">
    <input type=date name=firstdate> to <input type=date name=lastdate><br>
    <button onclick='applytodates()'>应用方案</button> 
    </form>
    <form method=post action='./?f=saveweekly' id=saveweekly><input type=hidden name=newdata id=saveweeklynewdata><input type=hidden name=schedule_name value=".$schedule_name."></form>
    <input type=hidden name=start id=applytodatesstart><input type=hidden name=end id=applytodatesend>
    <script>
    	function removerow(item){
    		$(item).closest('tr').remove();
    	}
        function addrow(){
            $('#weeklyschedule tbody').append('<tr><td><input name=description><td><input type=time name=time><td><input type=checkbox name=mon><td><input type=checkbox name=tue><td><input type=checkbox name=wed><td><input type=checkbox name=thu><td><input type=checkbox name=fri><td><input type=checkbox name=sat><td><input type=checkbox name=sun><td><button onclick=\"removerow(this)\">删除</button>');
        }
        function allcheck(x){
        	var y = x.id.substring(0,3);
            var checkboxes = document.querySelectorAll('input[name='+y+']');
            var flag = x.checked;
            for (var i = 0; i < checkboxes.length; i++)
    			checkboxes[i].checked = flag;
        }
        function save(){
        	tosave = [];
            $('#weeklyschedule tbody tr').each(function(){
            	description = $(this).find('[name=description]').val();
            	time = $(this).find('[name=time]').val();
            	if ($(this).find('[name=mon]').is(':checked')) {mon=1;} else {mon=0;}
            	if ($(this).find('[name=tue]').is(':checked')) {tue=1;} else {tue=0;}
            	if ($(this).find('[name=wed]').is(':checked')) {wed=1;} else {wed=0;}
            	if ($(this).find('[name=thu]').is(':checked')) {thu=1;} else {thu=0;}
            	if ($(this).find('[name=fri]').is(':checked')) {fri=1;} else {fri=0;}
            	if ($(this).find('[name=sat]').is(':checked')) {sat=1;} else {sat=0;}
            	if ($(this).find('[name=sun]').is(':checked')) {sun=1;} else {sun=0;}
            	tosave.push({description,time,mon,tue,wed,thu,fri,sat,sun});
            });
            $('#saveweeklynewdata').val(JSON.stringify(tosave));
            $('#saveweekly').submit();
        }
        function applytodates(){
            $('#applytodates').submit();
        }
        function changeschedule(){
        	$('#changescheduleform').submit();
        }
    </script>
    ";
    loadcontent($content,$function);
} else if($function=='saveweekly'){
	$schedule_name = $_POST['schedule_name'];
    $db->exec("DELETE FROM $schedule_name WHERE 1=1");
    $newdata = json_decode($_POST['newdata'], true, 512, JSON_UNESCAPED_UNICODE);
    foreach($newdata as $d){
    	$description = $db->quote($d['description']);
    	$time = $db->quote($d['time']);
    	$mon = $db->quote($d['mon']);
    	$tue = $db->quote($d['tue']);
    	$wed = $db->quote($d['wed']);
    	$thu = $db->quote($d['thu']);
    	$fri = $db->quote($d['fri']);
    	$sat = $db->quote($d['sat']);
    	$sun = $db->quote($d['sun']);
    	$db->exec("INSERT INTO $schedule_name VALUES ($description,$time,$mon,$tue,$wed,$thu,$fri,$sat,$sun)");
    }
    header('location: ./?f=week&schedule_name='.$schedule_name);
} else if($function=='saveday'){
	$date = $_POST['date'];
	$dateq = $db->quote($_POST['date']);
    $db->exec("DELETE FROM bells WHERE date=$dateq");
    $newdata = json_decode($_POST['newdata'], true, 512, JSON_UNESCAPED_UNICODE);
    foreach($newdata as $d){
    	$description = $db->quote($d['description']);
    	$time = $db->quote($d['time']);
    	$db->exec("INSERT INTO bells VALUES ($description,$dateq,$time)");
    }
    file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR."ring.pipe", "update_table".PHP_EOL);
    header("location: ./?f=main&date=$date");
} else if($function=='applytodates'){
	loadcontent("正在添加打铃计划，请保持窗口不要关闭...",'week');
	$schedule_name = $_POST['schedule_name'];
	$firstdate = $_POST['firstdate'];
	$lastdate = $_POST['lastdate'];
	$firstdateq = $db->quote($_POST['firstdate']);
	$lastdateq = $db->quote($_POST['lastdate']);
	$diff_seconds=date_diff(date_create($firstdate),date_create($lastdate));
	$diff_days=$diff_seconds->format("%R%a");
	if ($diff_days>=0&&$diff_days<366){
		$db->exec("DELETE FROM bells WHERE date>=$firstdateq AND date<=$lastdateq");
		$bells = $db->query("SELECT * FROM $schedule_name ORDER BY time ASC")->fetchAll(PDO::FETCH_ASSOC);
		$date = strtotime($firstdate);
		$end = strtotime($lastdate);
		$i=0;

		while($date<=$end){
			$dateq = $db->quote(date('Y-m-d',$date));
			$dotw = strtolower(date('D',$date));
			foreach($bells as $b){
				if($b[$dotw]==1){
					$description = $db->quote($b['description']);
					$time = $db->quote($b['time']);
					$db->exec("INSERT INTO bells VALUES ($description,$dateq,$time)");
				}
			}
			$date = strtotime("+1 day",$date);	
			$i++;
		}
	    file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR."ring.pipe", "update_table".PHP_EOL);
	    loadcontent("<br>共成功添加".$i."天的打铃计划",'week');
    } else {
    	loadcontent("<br>开始日期不能晚于结束日期，且间隔要小于1年！",'week');
    }
} else if($function=='ring'){
	$content = "
	<h2>确认打铃</h2>
	<form method=post action='./?f=ring_now' id=ring_now>
		<button onclick='ring_now()' style='padding:10px;margin:10px;' >确认立即打铃</button>
	</form>
    <script>
	        function ring_now(){
	        	$('#ring_now').submit();
	        }
    </script>
	";
	loadcontent($content,$function);
} else if($function=='ring_now'){
    file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR."ring.pipe", "ring_now".PHP_EOL);
	loadcontent("正在打铃！",'ring');
} else if($function=='logout'){
    session_destroy();
    header('location: ./');
} else {
    loadcontent($function." is not coded",$function);
}
?>
