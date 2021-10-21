# LMXCC
基于freeswitch的永久免费开源的呼叫中心系统，无特别的个性化模块或调整，使用FS的域管理，多企业云平台架构，实现自动外呼任务！

1、平台架构：Freeswitch 作为业务支撑，redis 作为数据交换支撑，mariadb 作为数据库支撑

2、Freeswitch 核心需启用mariadb数据库，而后通过redis，与C服务进程（实现批量外呼和呼叫状态监听）进行数据交互

3、整体系统包含几个部分：FS的管理端（即本人的开源[FSLMX](https://github.com/flymote/FSlmx)，FS服务后台管理）、企业用户管理端（给呼叫企业使用的）、登录管理（把平台的登录和授权独立了）

4、平台基本思路：FS采用域管理后实现多企业云平台，使用C编写的服务进行批量外呼支撑和呼叫状态监听（来电提醒和基础的用户端CRM功能）

5、实现的功能：基于FS呼叫中心模块的坐席管理和坐席外呼、自动外呼、来电提醒及简单CRM、给DID使用的IVR配置、域管理、组管理、用户管理、FS服务器配置文件管理、路由配置、呼叫设置、CDR、webrtc的SIP电话（sipml5）

使用配置：

1、系统的基本配置：数据库和redis的相关配置在三个文件中，Shoudian_db.php (FS控制台的数据库和redis连接设置) \ DM_db.php（域用户管理端的数据库和redis连接设置） \ func.inc.php（登录账号的数据库连接设置），为啥有3个？因为我本意是希望这3个系统可以相互独立开，而本身他们在代码上也是完全独立的

2、C程序的基本配置是在 c_daemon_src 的config.conf 中，C程序的编译安装在c_daemon_src目录的readme中有详细说明！

3、登录系统的用户账号自己在数据库login_users表里面设置即可（数据表中，app设置 FSlmx 为FS控制台账号，app设置 lmxcc 为域用户管理端账号，isadm表示管理权限，7以上为平台管理人员，5和6为域的管理人员，其他为一般用户），管理程序我没有上传！
登录账号举例：
* FS管理账号：app:FSlmx user:admin pwd:123456 isadm:9 enabled:1  
* 域管理账号：app:lmxcc user:manage pwd:123456 isadm:6 area:test enabled:1 (这是创建test域的管理账号manage)
* 域坐席账号：app:lmxcc umin:10000 umax:20000 pwd:1234 isadm:1 area:test enabled:1 (这是创建test域的坐席账号，坐席号从10000到20000，可以使用坐席号登录)

域需要用FS管理账号去平台创建，创建域后再创建相关的账号、坐席和组等（这是登录使用FS的账号哦~），而后再在login_users表自己添加相关域账号让域的相关人员登录WEB即可！

4、记住，登录系统的用户和FS的用户不是一个概念！！登录系统的用户（上面第3点说的）是指登录这个web平台的人员，而FS控制台的用户管理其实是指坐席人员；

5、安装后在代码中数据库和redis基本设置完毕后，在main.php中进入FS管理台，进行对系统平台的使用设置，先进行 参数设置 ，而后进行 服务器设置 ！

6、配置安装Freeswitch，请参考我在FSlmx项目的说明，并附上安装小记： [FS1.10版本](https://blog.csdn.net/onebird_lmx/article/details/107353334)  [FS1.8及1.6版本](https://blog.csdn.net/onebird_lmx/article/details/107353692)  [配置使用FS小记](https://blog.csdn.net/onebird_lmx/article/details/107354258)

7、tables.sql为数据库结构

8、本web系统中是按域启动域的批量外呼服务（lmxcc）：如果域里面的批量呼叫任务结束就会自动停止服务程序并退出！所以如果有新任务，需要启动任务后，在域配置页面中启动外呼（启动外呼会强行停止已在运行的域外呼而后启动一个新的域外呼服务！所以如你确信当前有任务在执行的话，仅需启动任务，任务便会自动进入外呼队列！否则就需要点击 开始外呼 启动外呼服务）
*记住，启动任务，仅仅表示本任务的已经随时可用，而不是说这个任务已经开始

9、顾客信息管理实现了外呼用户的信息记录和查询，需启动呼叫状态监控服务（ESLeventServ）方可实现坐席呼叫状态的即时同步

注意：经测试，在php8下面，当前代码库中包含的PhpSpreadsheet项目代码存在问题，如果使用php8及以上版本，请更新PhpSpreadsheet的项目代码到其较新版本！！因为这是外部引入的，我这里就不再保持更新！望周知！
