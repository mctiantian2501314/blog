termux在qemu里安装alpine，再安装docker，原来无法安装是dns的问题，开机关机慢，是启动虚拟机命令的问题。



设置清华源，安装快些
```
sed -i 's@^\(deb.*stable main\)$@#\1\ndeb https://mirrors.tuna.tsinghua.edu.cn/termux/apt/termux-main stable main@' $PREFIX/etc/apt/sources.list
apt update && apt upgrade
```
termux安装必要软件
```
pkg install qemu-common qemu-system-x86-64-headless qemu-utils wget -y
```
更新软件，访问运行出错，设置软件源时已经更新了，所以不需要更新了
```
pkg update
```
下载alpine系统，我这下载的
alpine-virt-3.19.0_rc1-x86_64.iso
```
wget https://mirrors.tuna.tsinghua.edu.cn/alpine/v3.19/releases/x86_64/alpine-virt-3.19.0_rc1-x86_64.iso
```

创建虚拟硬盘
```
qemu-img create -f qcow2 alpine.img 32G
```
硬盘启动，如果硬盘没有引导，就会光盘启动
```
 qemu-system-x86_64 -smp 4,cores=2,threads=1,sockets=2 --accel tcg,thread=multi -m 1024 -hda alpine.img -cdrom alpine-virt-3.19.0_rc1-x86_64.iso -boot c -nographic
```

设置网卡
```
vi /etc/network/interfaces
```
```
auto lo
iface lo inet loopback

auto eth0
iface eth0 inet static
        address 10.0.2.15
        netmask 255.255.255.0
        gateway 10.0.2.2
```

设置dns
```
vi /etc/resolv.conf
```

```
nameserver 114.114.114.114
```

安装setup-alpine
需要注意设置
DNS nameserver(s)? [10.0.2.3]
dns设置114.114.114.114

第一次可能不能设置，安装到提示
HTTP/FTP proxy URL?
，crtl+c结束一次安装。
第二次安装时就可以设置dns

=

安装系统
sda选择系统安装位置
sys安装系统
y确定安装
poweroff安装完关机

安装设置软件源
```
vi /etc/apk/repositories
```
进入编辑模式，把第三行`#`删掉，然后esc退出，输入 :wq 进行保存退出。
更新软件源
```
apk update
```

接下来就可以安装docker了
```
apk add docker
```
启动docker服务
```
service docker start
```
可以加入开机启动
```
rc-update add docker boot
```

测试docker
先进入超级权限
```
su
```

```
docker run hello-world
```