**安装前提说明=================================================================

1、libconfig :
yum install libconfig libconfig-devel 

2、hiredis:
wget https://github.com/redis/hiredis/archive/master.zip
unzip master.zip
cd hiredis-master
make
make install

3、需要对FS的esl模块进行一下编译：FS的源文件目录为/usr/local/src/freeswitch/libs/esl
cd /usr/local/src/freeswitch/libs/esl
make && make install
这时 /usr/local/src/freeswitch/libs/esl 下面会生成 .libs 目录是Makefile 需要的

4、需要添加hiredis.so的路径到系统环境中（否则运行时会提示 error while loading shared libraries: libhiredis.so ）
# echo "/usr/local/lib" >> /etc/ld.so.conf
# ldconfig

5、需检查需要的mariadb C client ：
# ll /usr/lib64/libmariadbclient.a
yum 安装10.*版本的mariadb后会自动创建，mysql的头文件如果没有，
# yum install MariaDB-devel

**安装说明===================================================================

make && make install

运行时，需要将config.conf、lmxcc文件放到 /usr 目录下面，对相应文件修改属组（make install 会进行这些操作！）：
cp config.conf /usr
cp lmxcc /usr
cp ESLeventServ /usr
cp keeprun.sh /usr
chown freeswitch:daemon /usr/lmxcc
cp /dev/null /var/log/lmxcc
chown freeswitch:daemon /var/log/lmxcc

**配置说明===================================================================

数据库连接和redis的连接信息在 config.conf中设置，程序读取 /usr/config.conf

**运行参数===================================================================

必须选择参数： -s 后台服务模式   -d domainid 特定域外呼（必须指定域id，如 -d domainid）
举例：
/usr/lmxcc -sd test
/usr/ESLeventServ -s
/usr/keeprun.sh

keeprun.sh是检测ESLeventServ是否运行的脚本，如果没有发现运行就会自动启动他，需要把这个放到定时任务中（下面是每5分钟检测一下）：
crontab -e
*/5 * * * * /usr/keeprun.sh

程序运行是一个域一个进程跑的，一个域的每秒新通话在15-200之间（30-400会话，在允许的并发线数、FS_SPS和maxsessions，取其最小值）！如果没有结束已有任务，新添加任务会按照设定的优先级插入原队列并依次运行