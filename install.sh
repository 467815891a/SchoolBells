#!/bin/bash
DIR=$(cd $(dirname $0) && pwd)
sudo timedatectl set-timezone Asia/Shanghai
sudo apt update && sudo apt install -y ntpdate php*-sqlite3 php*-cli sqlite3
sudo apt-get remove fake-hwclock
sudo dpkg --purge fake-hwclock
sudo echo -e "rtc-ds1307">>/etc/modules
sudo echo ds1307 0x68 > /sys/class/i2c-adapter/i2c-0/new_device
sudo hwclock --rtc /dev/rtc1 --hctosys --localtime
sudo chmod a+x synctime.sh
sudo chmod a+x ring_deamon.sh
sudo echo "[Unit]
Description=ring

[Service]
User=root
Type=simple
PIDFile=/var/run/ring.pid
ExecStart=/usr/bin/bash $DIR/ring_deamon.sh
ExecReload=/bin/kill -s HUP \$MAINPID ; /usr/bin/bash $DIR/ring_deamon.sh
ExecStop=/bin/kill -s QUIT \$MAINPID
StandardOutput=append:/var/log/ring_log.txt
StandardError=append:/var/log/ring_log.txt
Restart=always

[Install]
WantedBy=multi-user.target
" | tee /etc/systemd/system/ring.service >/dev/null
sudo systemctl daemon-reload && sudo systemctl start ring && sudo systemctl enable ring.service
sudo tee /etc/logrotate.d/ring >/dev/null <<'EOF'
/var/log/ring_log.txt{
    monthly
    rotate 3
    copytruncate
    noolddir
    dateext
    compress
    delaycompress
    missingok
    notifempty
    create 644 root root
}
EOF
