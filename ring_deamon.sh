#!/bin/bash
loadDB(){
	ring_table=$(sqlite3 -batch $1 "SELECT time FROM bells WHERE date='"$(date '+%Y-%m-%d')"' ORDER BY time")
}

ring_now(){
	sudo echo 1 > /sys/class/gpio/gpio198/value
	sleep 20s
	sudo echo 0 > /sys/class/gpio/gpio198/value
}

mk_pipe(){
	if [[ -e $1 ]]; then
		rm $1
	fi
	mkfifo $1
	#非阻塞方式进行管道通信
	exec 4<>$1
}

start_php(){
	PIDS=$(ps aux |grep 'php -S'  |grep -v grep | awk '{print $2}')
	if [ "$PIDS" != "" ]; then
		kill $PIDS
	fi
	mk_pipe $DIR/ring.pipe
	cd $DIR && nohup php -S 0.0.0.0:80 > /dev/null 2>&1 &
}

check_php(){
	PIDS=$(ps aux |grep 'php -S'  |grep -v grep | awk '{print $2}')
	if [ "$PIDS" == "" ]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 发现php进程意外退出，尝试重启..."
		cd $DIR && nohup php -S 0.0.0.0:80 > /dev/null 2>&1 &
	fi
}

#加载DS3231时间模块
sudo echo ds1307 0x68 > /sys/class/i2c-adapter/i2c-0/new_device
#GPIO198控制继电器
sudo echo 198 > /sys/class/gpio/export
sudo echo out > /sys/class/gpio/gpio198/direction
sudo echo 0 > /sys/class/gpio/gpio198/value
sleep 2s
DIR=$(cd $(dirname $0) && pwd)
echo $(date '+%Y/%m/%d %H:%M:%S')" 打铃系统启动，工作前尝试同步互联网时间..."
#尝试同步互联网时间
bash $DIR/synctime.sh
#启动php
start_php
sleep 2s
#读取目前系统时间
nowtime=$(date '+%H:%M')
#读取当天的打铃时间表
loadDB $DIR/bells.db
#读取当天的系统日期
nowdate=$(date '+%Y-%m-%d')
while true
do
	nowtime=$(date '+%H:%M')
	read -t 1 READ_PIPE<$DIR/ring.pipe
	if [[ "$nowdate" != $(date '+%Y-%m-%d') ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 新的一天开始，加载新的一天打铃表..."
		loadDB $DIR/bells.db
		nowdate=$(date '+%Y-%m-%d')
	fi
	if [[ " ${ring_table[*]} " =~ "$nowtime" ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 时间到，自动打铃！"
		ring_now
		sleep 1m
	fi
	if [[ $READ_PIPE == 'update_table' ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 收到打铃表可能更新，加载新的打铃表..."
		loadDB $DIR/bells.db
		echo 0 > $DIR/ring.pipe
	fi
	if [[ $READ_PIPE = 'ring_now' ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 收到手动打铃指令，现在打铃..."
		ring_now
		echo 0 > $DIR/ring.pipe
	fi
	check_php
	sleep 1s
done
