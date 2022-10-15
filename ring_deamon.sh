#!/bin/bash
loadDB(){
	ring_table=$(sqlite3 -batch $1 "SELECT time FROM bells WHERE date='"$(date '+%Y-%m-%d')"' ORDER BY time")
}

ring_now(){
	echo $(date '+%Y/%m/%d %H:%M:%S')" Ring now!"
	sudo echo 1 > /sys/class/gpio/gpio198/value
	sleep 10s
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
	PIDS=$(ps aux |grep php |grep -v grep | awk '{print $2}')
	if [ "$PIDS" != "" ]; then
		kill $PIDS
	fi
	mk_pipe $DIR/ring.pipe
	cd $DIR && nohup php -S 0.0.0.0:80 > /dev/null 2>&1 &
}


#加载DS3231时间模块
sudo echo ds1307 0x68 > /sys/class/i2c-adapter/i2c-0/new_device
#GPIO198控制继电器
sudo echo 198 > /sys/class/gpio/export
sudo echo out > /sys/class/gpio/gpio198/direction
sudo echo 0 > /sys/class/gpio/gpio198/value
sleep 2s
#读取时钟模块的时间到系统时间
sudo hwclock --rtc /dev/rtc1 --hctosys --localtime
hwclock -w -f /dev/rtc1
DIR=$(cd $(dirname $0) && pwd)
#启动php
start_php
sleep 2s
#读取目前系统时间
nowtime=$(date '+%H:%M')
#读取当天的打铃时间表
loadDB $DIR/bells.db
while true
do
	nowtime=$(date '+%H:%M')
	read -t 1 READ_PIPE<$DIR/ring.pipe
	if [[ "$nowtime" == "00:00" ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 新的一天开始，加载新的一天打铃表..."
		loadDB $DIR/bells.db
		sleep 1m
	fi
	if [[ " ${ring_table[*]} " =~ "$nowtime" ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 时间到，打铃！"
		ring_now
		sleep 1m
	fi
	if [[ $READ_PIPE == 'update_table' ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 收到打铃表可能更新，加载新的打铃表..."
		loadDB $DIR/bells.db
		echo 0 > $DIR/ring.pipe
		#echo ${ring_table[@]} | xargs -n 1 | sed "=" | sed "N;s/\n/. /"
	fi
	if [[ $READ_PIPE = 'ring_now' ]]; then
		echo $(date '+%Y/%m/%d %H:%M:%S')" 收到立即打铃指令，现在打铃..."
		ring_now
		echo 0 > $DIR/ring.pipe
	fi

done
