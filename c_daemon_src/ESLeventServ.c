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
#include <esl.h>
#include "config.h"

redisContext *redisDB = NULL;

esl_handle_t esl = {{0}};
char redis_pid[50] = "";
conf_inf conf;
bool server_mode = false;
char *txt = NULL;
char str[250] = "";

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
        fprintf (fp, "[%4d-%02d-%02d %02d:%02d:%02d] **EventServ** %s\n", t->tm_year + 1900, t->tm_mon + 1, t->tm_mday, t->tm_hour, t->tm_min, t->tm_sec, messages);
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
   
    config_destroy(&cfg);
    return true;
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
  }else
  	if (conf.log_info){
		sprintf(str,"ESL连接FS服务器 %s 端口 %d 成功！",host,port);
  	puts(str);
  	logs(conf.log_file, str);
  }
}

char *trim(char *str)
{
   char *p = str;
   char *p1;
   if(p) {
    p1 = p + strlen(str) - 1;
    while(*p && isspace(*p)) p++;
    while(p1 > p && isspace(*p1)) *p1-- = '\0';
   }
   return p;
}

char* substr(char* ch,int pos,int length)  
{  
    //定义字符指针 指向传递进来的ch地址
    char* pch=ch;  
    //通过calloc来分配一个length长度的字符数组，返回的是字符指针。
    char* subch=(char*)calloc(sizeof(char),length+1);  
    int i;  
 //只有在C99下for循环中才可以声明变量，这里写在外面，提高兼容性。  
    pch=pch+pos;  
//是pch指针指向pos位置。  
    for(i=0;i<length;i++)  
    {  
        subch[i]=*(pch++);  
//循环遍历赋值数组。  
    }  
    subch[length]='\0';//加上字符串结束符。  
    return subch;       //返回分配的字符数组地址。  
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
  char *optstring = "s";
  opt = getopt(argc, argv, optstring);
  while (opt != -1) {
    switch (opt) {
   	//server mode 
    case 's':
      signal(SIGCHLD, SIG_IGN);
      daemon(0, 0);
      server_mode = true;
      if (conf.log_info)
      	logs(conf.log_file, "后台服务模式启动");
    break;
    default:
      printf("  可选参数： -s 后台服务模式 \n\n");
    break;
    }
    opt = getopt(argc, argv, optstring);
  } 
	if (!server_mode){
  	puts("  可选参数： -s 后台服务模式   当前为普通程序运行！");
  }
  /* redis database connection */
 	// REDIS_REPLY_STRING 1    //字符串
	// REDIS_REPLY_ARRAY 2     //数组，例如mget返回值
	// REDIS_REPLY_INTEGER 3   //数字类型
	// REDIS_REPLY_NIL 4       //空
	// REDIS_REPLY_STATUS 5    //状态，例如set成功返回的‘OK’
	// REDIS_REPLY_ERROR 6     //执行失败
  redisDB = redis(conf.redis.host, conf.redis.port, conf.redis.password, conf.redis.db);
  redisReply *reply = NULL;
  
  esl_status_t status = ESL_FAIL;
  esl_event_t **save_event = NULL;

  init_esl(&esl, conf.esl.host, conf.esl.port, conf.esl.password);
//监听所有的CHANNEL事件：CHANNEL_ANSWER CHANNEL_APPLICATION CHANNEL_BRIDGE CHANNEL_CALLSTATE CHANNEL_CREATE CHANNEL_DATA CHANNEL_DESTROY CHANNEL_EXECUTE CHANNEL_EXECUTE_COMPLETE CHANNEL_GLOBAL CHANNEL_HANGUP CHANNEL_HANGUP_COMPLETE CHANNEL_HOLD CHANNEL_ORIGINATE CHANNEL_OUTGOING CHANNEL_PARK CHANNEL_PROGRESS CHANNEL_PROGRESS_MEDIA CHANNEL_STATE CHANNEL_UNBRIDGE CHANNEL_UNHOLD CHANNEL_UNPARK CHANNEL_UUID
//全部监听是用：ALL
  esl_events(&esl,ESL_EVENT_TYPE_PLAIN,"CHANNEL_CALLSTATE");
  char *pNext;
  char pName[150] = "";
  char *eventTime, *calleeId, *callerOId, *callerId, *toId, *state, *uid, *pid;
  int redis_get = 0;
  while (true) {
   status = esl_recv_event(&esl,0,save_event);
   if (status == ESL_FAIL)
   	exit(1);
   if (esl.last_event){
			pNext = strtok(esl.last_event->body,"\n");
			eventTime = NULL;
			state = NULL;
			uid = NULL;
			pid = NULL;
			callerId = NULL;
			callerOId = NULL;
			calleeId = NULL;
			toId = NULL;
			while(pNext != NULL) {
				txt = strchr(pNext,':');
				opt = strlen(txt);
				txt = trim(txt + 1);
				memcpy(pName, pNext, strlen(pNext) - opt);
				if (!strcasecmp(pName,"event-date-timestamp") ) {
					eventTime = substr(txt,0,10);
				}else
				if (!strcasecmp(pName,"channel-call-state") ) {
					state = txt;
				}else 
				if (!strcasecmp(pName,"unique-id") ) {
					uid = txt;
				}else
				if (!strcasecmp(pName,"channel-presence-id") ) {
					esl_url_decode(txt);
					pid = txt;
				}else
				if (!strcasecmp(pName,"caller-caller-id-number") ) {
					callerId = txt;
				}else
				if (!strcasecmp(pName,"caller-orig-caller-id-number") ) {
					callerOId = txt;
				}else
				if (!strcasecmp(pName,"caller-callee-id-number") ) {
					calleeId = txt;
				}else
				if (!strcasecmp(pName,"caller-destination-number") ) {//这里是事件处理的最后一条，下面的全部忽略
					toId = txt;
					memset(pName,0,150);
					break;
				}
				memset(pName,0,150);
				pNext = strtok(NULL,"\n");
			} 
			if (pid){
//				sprintf(str,"uid = %s ,eventTime = %s, state = %s, pid = %s, callerId = %s, callerOId = %s, calleeId = %s , toId = %s",uid,eventTime,state,pid,callerId,callerOId,calleeId,toId);
//				puts(str);
				reply = redisCommand(redisDB, "EXISTS c_%s ",uid);
		 		if (reply != NULL && reply->type == REDIS_REPLY_INTEGER) {
					redis_get = (int)reply->integer;
					freeReplyObject(reply);
					if (!redis_get){
						sprintf(str,"LPUSH %s %s",pid,uid);
//						puts(str);
						redisCommand(redisDB, str);
					}
					sprintf(str,"HSET c_%s eventTime %s state %s pid %s callerId %s callerOId %s calleeId %s toId %s",uid,eventTime,state,pid,callerId,callerOId,calleeId,toId);
//					puts(str);
					redisCommand(redisDB, str);
		 		}else
		 		logs(conf.log_file,"查询UID redis无回应 本事件操作终止!");
//				redisCommand(redisDB, "hmset %s eventTime %s, state = %s, pid = %s, callerId = %s, callerOId = %s, calleeId = %s , toId = %s",uid,eventTime,state,pid,callerId,callerOId,calleeId,toId);
			}
 	 }
  }	
  esl_disconnect(&esl);
 	redisFree(redisDB);  
  return 1;
}