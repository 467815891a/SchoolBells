#!/bin/bash
timedatectl set-timezone Asia/Shanghai
if [[ -z $1 ]]; then
	echo "正在尝试同步互联网时间..."
	ntpdate -u ntp1.aliyun.com
	if [[ "$?" -eq 0 ]]; then
		echo "获取到互联网时间为"$(date "+%Y-%m-%d %H:%M:%S")
		hw_time=$(date -d "$(hwclock --rtc /dev/rtc1 --localtime --get)" +%s)
		web_time=$(date +%s)
		diff=$(expr $hw_time - $web_time)
		if [[ "$diff" -lt -5 || "$diff" -gt 5 ]]; then
			echo "互联网与当前硬件时间相差"$diff"秒，大于5秒，正在更正硬件时间..."
			hwclock --rtc /dev/rtc1 --systohc --localtime
			if [[ "$?" -eq 0 ]]; then
				echo "修改时间模块成功，当前时间为"$(date "+%Y-%m-%d %H:%M:%S")
			else
				echo "修改硬件时间失败，可能是时钟模块损坏，请更换电池！"
				exit 16
			fi
		else
			hwclock --rtc /dev/rtc1 --hctosys --localtime
			echo "互联网与当前硬件时间相差"$diff"秒，小于5秒，以当前硬件时间为准，当前时间为"$(date "+%Y-%m-%d %H:%M:%S")
		fi
	else
		echo "网络问题，未获取到互联网时间，当前时钟模块时间为："$(hwclock -r -f /dev/rtc1)
		echo "正在把时钟模块时间应用到系统时间..."
		hwclock --rtc /dev/rtc1 --hctosys --localtime
		if [[ "$?" -eq 0 ]]; then
		    echo "修改系统时间成功，当前时间为"$(date "+%Y-%m-%d %H:%M:%S")
		else
		    echo "修改系统时间失败，可能是时钟模块损坏，请通过网络同步时间！"
		    exit 16
		fi
	fi
else
	echo "正在修改系统时间为"$1" "$2"..."
	#可接受的时间日期格式为'2014-12-25 12:34:56'
	date --set=$1" "$2
	if [[ "$?" -eq 0 ]]; then
		echo "修改系统时间成功，正在写入时间模块..."
	else
		echo "修改系统时间失败，可能是时间输入错误，请检查输入字符串："$1" "$2
		exit 65
	fi
	hwclock --rtc /dev/rtc1 --localtime --systohc
	if [[ "$?" -eq 0 ]]; then
		echo "修改时间模块成功，当前时间为"$(date "+%Y-%m-%d %H:%M:%S")
	else
		echo "修改硬件时间失败，可能是时钟模块损坏，请更换电池！"
		exit 16
	fi
fi
