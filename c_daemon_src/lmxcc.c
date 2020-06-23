#include <unistd.h>
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/param.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <time.h>
#include <string.h>
#include <libconfig.h>
#include <hiredis/hiredis.h>
#include <mysql/mysql.h>
#include <esl.h>
#include "config.h"

MYSQL fs_conn; // mysql 连接
MYSQL cc_conn; // mysql 连接
MYSQL_RES *g_res; // mysql 记录集
MYSQL_ROW g_row; // 字符串数组，mysql 记录行

redisContext *redisDB = NULL;

esl_handle_t esl = {{0}};

conf_inf conf;
bool server_mode = false;
char *domainid = NULL;
char str[150] = "";
char redis_pid[50] = "";
char infos[500] = "";

void logs(const char *file, const char *messages) {
    if (file == NULL || messages == NULL) {
        return;
    }
    time_t rawtime;
    struct tm *t;
    time(&rawtime);
    t = localtime(&rawtime);
    FILE *fp = NULL;
    fp = fopen(file, "a");
    if (fp != NULL) {
        fprintf (fp, "[%4d-%02d-%02d %02d:%02d:%02d] %s\n", t->tm_year + 1900, t->tm_mon + 1, t->tm_mday, t->tm_hour, t->tm_min, t->tm_sec, messages);
        fclose(fp);
    }
    return;
}

bool load_conf_init(const char *file, conf_inf *conf) {
    config_t cfg;
    config_init(&cfg);
    
    if (!config_read_file(&cfg, file)) {
        conf->err = "conf file error: 配置文件错误，无法读取 \n";
        config_destroy(&cfg);
        return false;
    }

    /* read log file configure */
    const char *log_file;
    if (!config_lookup_string(&cfg, "log_file", &log_file)) {
        strcpy(conf->log_file, "/var/log/lmxcc");
    }
    strncpy(conf->log_file, log_file, 100);
    
    /* read log info configure */
    int log_info;
    if (!config_lookup_bool(&cfg, "log_info", &log_info)) {
        conf->log_info =  0;
    }
    conf->log_info = log_info;
    
    /* read redis server configure */
    const char *redis_host;
    int redis_port;
    const char *redis_password;
    int redis_db;

    if (!config_lookup_string(&cfg, "redis_host", &redis_host)) {
        conf->err = "redis error: redis_host 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->redis.host, redis_host, 30);
    
    if (!config_lookup_int(&cfg, "redis_port", &redis_port)) {
        conf->redis.port = 6379;
    }else
    conf->redis.port = redis_port;
    
    if (!config_lookup_string(&cfg, "redis_password", &redis_password)) {
        conf->err = "redis error: redis_password 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->redis.password, redis_password, 20);
    
    if (!config_lookup_int(&cfg, "redis_db", &redis_db)) {
        conf->err = "redis error: redis_db 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    conf->redis.db = redis_db;

    /* read freeswitch event socket configuration */
    const char *esl_host;
    int esl_port;
    const char *esl_password;
    
    if (!config_lookup_string(&cfg, "esl_host", &esl_host)) {
        conf->err = "esl error: esl_host 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->esl.host, esl_host, 30);

    if (!config_lookup_int(&cfg, "esl_port", &esl_port)) {
        conf->esl.port = 8021;
    }
    conf->esl.port = esl_port;
    
    if (!config_lookup_string(&cfg, "esl_password", &esl_password)) {
        conf->err = "esl error: esl_password 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->esl.password, esl_password, 20);
    
    /* read mysql_fs server configure */
    const char *mysql_fs_host;
    int mysql_fs_port;
    const char *mysql_fs_password;
    const char *mysql_fs_user;
    const char *mysql_fs_db;

    if (!config_lookup_string(&cfg, "mysql_fs_host", &mysql_fs_host)) {
        conf->err = "mysql_fs error: mysql_fs_host 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_fs.host, mysql_fs_host, 30);
    
    if (!config_lookup_int(&cfg, "mysql_fs_port", &mysql_fs_port)) {
        conf->mysql_fs.port = 3306;
    }
    conf->mysql_fs.port = mysql_fs_port;
    
    if (!config_lookup_string(&cfg, "mysql_fs_user", &mysql_fs_user)) {
        conf->err = "mysql_fs error: mysql_fs_user 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_fs.user, mysql_fs_user, 20);
    
    if (!config_lookup_string(&cfg, "mysql_fs_password", &mysql_fs_password)) {
        conf->err = "mysql_fs error: mysql_fs_password 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_fs.password, mysql_fs_password, 20);
    
    if (!config_lookup_string(&cfg, "mysql_fs_db", &mysql_fs_db)) {
        conf->err = "mysql_fs error: mysql_fs_db 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_fs.db, mysql_fs_db, 20);
    
    /* read mysql_cc server configure */
    const char *mysql_cc_host;
    int mysql_cc_port;
    const char *mysql_cc_password;
    const char *mysql_cc_user;
    const char *mysql_cc_db;

    if (!config_lookup_string(&cfg, "mysql_cc_host", &mysql_cc_host)) {
        conf->err = "mysql_cc error: mysql_cc_host 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_cc.host, mysql_cc_host, 30);
    
    if (!config_lookup_int(&cfg, "mysql_cc_port", &mysql_cc_port)) {
        conf->mysql_cc.port = 3306;
    }
    conf->mysql_cc.port = mysql_cc_port;
    
    if (!config_lookup_string(&cfg, "mysql_cc_user", &mysql_cc_user)) {
        conf->err = "mysql_cc error: mysql_cc_user 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_cc.user, mysql_cc_user, 20);
    
    if (!config_lookup_string(&cfg, "mysql_cc_password", &mysql_cc_password)) {
        conf->err = "mysql_cc error: mysql_cc_password 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_cc.password, mysql_cc_password, 20);
    
    if (!config_lookup_string(&cfg, "mysql_cc_db", &mysql_cc_db)) {
        conf->err = "mysql_cc error: mysql_cc_db 读取错误 \n";
        config_destroy(&cfg);
        return false;
    }
    strncpy(conf->mysql_cc.db, mysql_cc_db, 20);
     
    config_destroy(&cfg);
    return true;
}

void print_mysql_error(MYSQL *g_conn, const char *msg) { // 打印错误，如果log_info则记录日志，否则仅当有msg的才记录日志
  if (msg)
  	sprintf(str,"Mysql错误 %s : ",msg);
  strcat(str,mysql_error(g_conn));
  if (!server_mode)
  	puts(str);
  if (conf.log_info)
  	logs(conf.log_file, str);
  else
  	if (msg)
  		logs(conf.log_file, str);
}

int executesql(MYSQL *g_conn, const char * sql) {
  /*query the database according the sql*/
  if (mysql_real_query(g_conn, sql, (unsigned int)strlen(sql))){
  	print_mysql_error(g_conn,"exec");
    return -1; // 表示失败
  }
  return 0; // 成功执行
}

// create a mysql database connection
int init_mysql(MYSQL *g_conn, char *host, unsigned short port, char *user, char *password, char *db,char *charset) { // 初始化连接
    // init the database connection
    mysql_init(g_conn);
    /* connect the database */
    if(!mysql_real_connect(g_conn, host, user, password, db, port, NULL, 0)){ // 如果失败
    	print_mysql_error(g_conn,"init");
    	exit(1);
    }
    /*设置字符编码,可能会乱码*/
    if (charset != NULL){
    	char t[50] = "set names ";
    	strcat(t,charset);
      executesql(g_conn,t);
    }
    return 1; // 返回成功
}

// create a redis database connection
redisContext *redis(char *host, unsigned short port, char *password, unsigned int db) {
    if (host == NULL) return NULL; 
    // init redis database
    redisContext *c = NULL;
    redisReply *reply = NULL;
    struct timeval timeout = {1, 500000};
    
    // connection to redis database
    c = redisConnectWithTimeout(host, port, timeout);
    if (c == NULL || c->err) {
      if (c) {
        sprintf(str,"Redis错误 init : 无法连接 %s\n", c->errstr);
        redisFree(c);
      } else 
        sprintf(str,"Redis错误 init : 无法初始化 %s\n",host);
	    if (!server_mode)
	  		puts(str);
	  	logs(conf.log_file, str);
	    exit(1);
    }

    // auth password
    if (password) {
        reply = redisCommand(c, "AUTH %s", password);
        if (reply != NULL) freeReplyObject(reply);
    }
    
    // select database
    reply = redisCommand(c, "SELECT %d", db);
    if (reply != NULL) {
        if ((reply->type == REDIS_REPLY_STATUS) && (strcmp(reply->str, "OK") == 0)) {
            freeReplyObject(reply);
            return c;
        }
        freeReplyObject(reply);
    }
    redisFree(c);
    return NULL;
}

void init_esl(esl_handle_t *handle, const char *host, esl_port_t port, const char *password){
	if(esl_connect(handle, host, port, NULL, password) != ESL_SUCCESS){
		sprintf(str,"ESL错误 init : 连接FS服务器 %s 端口 %d 没有成功",host,port);
  	puts(str);
  	logs(conf.log_file, str);
  	exit(1);
  }
}

void originate(esl_handle_t *esl, char *domainid, int count, char *prefix, char *callerName, char *callerID, char *gw, phone_list *p) {
    if (!esl || !p)
        return;
    int i;
    char cmd[512]="";
    char n[150]="";
		if (strlen(callerName))
			sprintf(n,",origination_caller_id_name=%s",callerName);
		if (strlen(callerID)){
			strcat(n,",origination_caller_id_number=");
			strcat(n,callerID);
		}
    for (i = 0; i < count; i++) {
        sprintf(cmd, "bgapi originate {ignore_early_media=true,taskid=%d,originate_timeout=30%s}sofia/gateway/%s/%s%s service XML %s LMXcc \n\n",p->taskid[i],n, gw, prefix,p->callee[i],domainid);
				if (conf.log_info){
		   		logs(conf.log_file, cmd);
		    }
        esl_send(esl, cmd);
        usleep(20000);
    }
    return;
}

int main(int argc, char *argv[]) {
	if (access("/usr/config.conf",4) == -1){
		puts("/usr/config.conf 无法访问！");
		return -1;
	}
		
  if (!load_conf_init("/usr/config.conf", &conf)) {
    logs(conf.log_file, conf.err);
    return -1;
  }
  
  int opt = 0;
  char *optstring = "d:s";
  opt = getopt(argc, argv, optstring);
  while (opt != -1) {
    switch (opt) {
   	//server mode 
    case 's':
      signal(SIGCHLD, SIG_IGN);
      daemon(0, 0);
      server_mode = true;
      logs(conf.log_file, "后台服务模式启动");
    break;
    //for the selected domain
    case 'd':
      domainid = optarg;
    break;
    default:
      printf("  参数： -s 后台服务模式   -d domainid 指定domainid，对特定域进行自动外呼\n\n");
    break;
    }
    opt = getopt(argc, argv, optstring);
  } 
  if (domainid == NULL){
  	printf("  可选参数： -s 后台服务模式   必选参数：-d domainid 特定域外呼（必须指定域id，如 -d domainid）  \n");
  	return 0;
  }else if (!server_mode){
  	printf("  前端运行模式，已选择域id为 %s ，进行特定域外呼（若使用参数 -s 则为服务模式）\n",domainid);
  }

  int lines = 0;
  int fs_sps = 0;
  int fs_maxsessions = 0;
  int sessions = 0;
  int domain_sessions = 0;
	char gateway[32] = "";
	char prefix[30] = "";
	char callerName[30] = "";
	char callerID[30] = "";
	char task_disabled[80] = "";

  sprintf(redis_pid,"lmxcc_%s",domainid);
  sprintf(str,"get %s",redis_pid);
  while (true) {
     /* redis database connection */
    	// REDIS_REPLY_STRING 1    //字符串
			// REDIS_REPLY_ARRAY 2     //数组，例如mget返回值
			// REDIS_REPLY_INTEGER 3   //数字类型
			// REDIS_REPLY_NIL 4       //空
			// REDIS_REPLY_STATUS 5    //状态，例如set成功返回的‘OK’
			// REDIS_REPLY_ERROR 6     //执行失败
     redisDB = redis(conf.redis.host, conf.redis.port, conf.redis.password, conf.redis.db);
     redisReply *reply = NULL;
     
	   reply = redisCommand(redisDB, str); //查看当前域的运行标识，检查是否存在
		 if (reply != NULL && reply->type == REDIS_REPLY_STRING) { //存在的就干掉它(不管是否在运行，kill)
				sprintf(str,"kill %d",atoi(reply->str));
				system(str);
		 }
	   sprintf(str,"set %s %d",redis_pid,getpid());
		 redisCommand(redisDB, str);
		if (reply != NULL) {
			freeReplyObject(reply);
	 	} 
		 
	   reply = redisCommand(redisDB, "get FS_sps");
		 if (reply != NULL && reply->type == REDIS_REPLY_STRING) {
			fs_sps = atoi(reply->str);
			freeReplyObject(reply);
		 }else
		 	logs(conf.log_file,"FS_sps 信息无法从redis中取得 !");

		 reply = redisCommand(redisDB, "get FS_maxsessions");
		 if (reply != NULL && reply->type == REDIS_REPLY_STRING) {
			fs_maxsessions = atoi(reply->str);
			freeReplyObject(reply);
		 }else
		 	logs(conf.log_file,"FS_maxsessions 信息无法从redis中取得 !");

		 reply = redisCommand(redisDB, "get task_disabled");
		 if (reply != NULL && reply->type == REDIS_REPLY_STRING) {
			strcpy(task_disabled,reply->str);
			freeReplyObject(reply);
		 }
		 if (conf.log_info){
			sprintf(str,"当前 FS_sps = %d , FS_maxsessions = %d ",fs_sps,fs_maxsessions);
   		logs(conf.log_file, str);
    }
//--------------------------- 基于单个域的处理 --------------------------------------------------------------------------------------------------------     		
     	reply = redisCommand(redisDB, "hmget domain_%s enabled lines GW prefix callerName callerID",domainid);
     	if (reply != NULL) {
        if ( reply->element[0]->type == REDIS_REPLY_NIL || (reply->element[0]->type == REDIS_REPLY_STRING && strcmp(reply->element[0]->str,"0")==0)){
          if (conf.log_info){
						sprintf(str," 域 %s 不存在或已停用",domainid);
						logs(conf.log_file, str);
					}
					if (!server_mode)
						puts(str);
  				return 0;
        }
        if (reply->element[1]->type == REDIS_REPLY_STRING)
        	lines = atoi(reply->element[1]->str);
        if (reply->element[2]->type == REDIS_REPLY_STRING)
        	strcpy(gateway,reply->element[2]->str);
        if (reply->element[3]->type == REDIS_REPLY_STRING)
        	strcpy(prefix,reply->element[3]->str);
        if (reply->element[4]->type == REDIS_REPLY_STRING)
        	strcpy(callerName, reply->element[4]->str);
        if (reply->element[5]->type == REDIS_REPLY_STRING)
       		strcpy(callerID,  reply->element[5]->str);
        freeReplyObject(reply);
    	}else{
    		if (conf.log_info){
				  sprintf(str," 无法从redis中获取域 %s 的信息！",domainid);
					logs(conf.log_file, str);
				}
				if (!server_mode)
					puts(str);
    		return 0;
    	}
    	if (conf.log_info){
      	sprintf(str,"域 %s 的相关信息： lines %d ,gateway %s ,prefix %s ,callerName %s ,callerID %s ",domainid,lines,gateway,prefix,callerName,callerID);
      	logs(conf.log_file, str);
    	}
   
    int iNum_rows;
  	phone_list p;
  	
    /* mysql database connection */
    init_mysql(&fs_conn, conf.mysql_fs.host, conf.mysql_fs.port, conf.mysql_fs.user, conf.mysql_fs.password, conf.mysql_fs.db,NULL);
    executesql(&fs_conn, "SELECT COUNT(*) FROM channels");
    g_res = mysql_store_result(&fs_conn);
    if (g_res){
     g_row = mysql_fetch_row(g_res);
     sessions = atoi(g_row[0]);
    }
    mysql_free_result(g_res);
    sprintf(str,"SELECT COUNT(*) FROM channels where context = '%s'",domainid);
    executesql(&fs_conn, str);
    g_res = mysql_store_result(&fs_conn);
    if (g_res){
     g_row = mysql_fetch_row(g_res);
     domain_sessions = atoi(g_row[0]);
    }
    mysql_free_result(g_res);
    if (conf.log_info){
	    sprintf(str,"当前 sessions = %d , domain_sessions = %d (域ID： %s )",sessions,domain_sessions,domainid);
		  logs(conf.log_file, str);
	  }
    mysql_close(&fs_conn); // 关闭链接
   	redisFree(redisDB);   
    int phones;
    phones = (lines*2) - domain_sessions; //当前本域可新建的会话数
    if (phones > 0){
	    int limitlines;
	    limitlines = fs_maxsessions - sessions; //当前平台允许的新会话数
			fs_sps = (fs_sps*0.7)<30 ? 30 : floor(fs_sps*0.7); //基于多域模式，一个域每秒新会话最多允许SPS的70%，最低为30（FS默认的sps数，1000会话）
	    phones = phones > limitlines ? limitlines : phones;
	    phones = phones > fs_sps ? fs_sps : phones;
	    phones = floor(phones/2); //转换会话数为电话数，因为一个电话是2个会话，所以除2
	    if (phones > 200)
	    	phones = 200; 
	    if (strlen(task_disabled)==0){
	    	sprintf(str,"SELECT `phone`,`taskid` FROM fs_phones WHERE `enabled`=1 AND `iscalled`=0 AND `domain_id`='%s' ORDER BY `level`,`taskid`,`id` LIMIT %d",domainid,phones);
	    	if (conf.log_info){
	    		strcpy(infos,str);
	    		strcat(infos," 域中无禁用任务");
	    		logs(conf.log_file, infos);
	    	}
	    }else{
	    	sprintf(str,"SELECT `phone`,`taskid` FROM fs_phones WHERE `enabled`=1 AND `iscalled`=0 AND `domain_id`='%s' AND `taskid` NOT IN (%s) ORDER BY `level`,`taskid`,`id` LIMIT %d",domainid,task_disabled,phones);
	    	if (conf.log_info){
	    		strcpy(infos,str);
	    		strcat(infos," 本域有禁用任务！");
	    		logs(conf.log_file, infos);
	    	}
	    }
	    /* mysql database connection */
	    init_mysql(&cc_conn, conf.mysql_cc.host, conf.mysql_cc.port, conf.mysql_cc.user, conf.mysql_cc.password, conf.mysql_cc.db,"utf8");
	    executesql(&cc_conn, str);
	    g_res = mysql_store_result(&cc_conn);
	    if (g_res){
	      iNum_rows = mysql_num_rows(g_res); // 得到记录的行数
	      int i=0;
	      while ((g_row=mysql_fetch_row(g_res))){
	      		strcpy(p.callee[i], g_row[0]);
	      		p.taskid[i] = atoi(g_row[1]);
	      	i++; 
	      }
	    }
	    mysql_free_result(g_res); // 释放结果集
      if (iNum_rows){
		    init_esl(&esl, conf.esl.host, conf.esl.port, conf.esl.password);
		    originate(&esl,domainid, iNum_rows, prefix, callerName, callerID, gateway, &p);
		    esl_disconnect(&esl);
		    mysql_close(&cc_conn); // 关闭链接
			  sleep(1);
		  }else{
		  	executesql(&cc_conn, "DELETE FROM fs_phones WHERE `iscalled` = 1 AND `datetime` < DATE_SUB(NOW(), INTERVAL 2 DAY)");
	    	if (conf.log_info){
	    		logs(conf.log_file, " fs_phones已执行清理，删除2天前的已呼数据");
	    	}
		  	mysql_close(&cc_conn); // 关闭链接
	  		return 1;
	  	}
  	}else
  		sleep(2);
  }
  return 1;
}