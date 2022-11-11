# SchoolBells
I created this as we had an old bell system that worked great, but in order to change the times we had to remote into a server and because of where we had that running it was difficult to give multiple people access to it.

This uses the same hardware design as that system:<br>
* NanoPi M1 with Armbian
* DS3231 RTC Module (which register as /dev/rtc1)
* Relay Module
<img src="https://github.com/467815891a/SchoolBells/raw/main/hardware.jpg" height=200>

But has a web interface for scheduling the bells.<br>
<img src="https://github.com/467815891a/SchoolBells/raw/main/screenshot.png" height=200>
<img src="https://github.com/467815891a/SchoolBells/raw/main/screenshot2.png" height=200>
<img src="https://github.com/467815891a/SchoolBells/raw/main/screenshot3.png" height=400>

Released as is where is for you to take as needed.

One command to install Bells system:
```
sudo bash install.sh
```
