#ifndef _CONFIG_H
#define _CONFIG_H

#include <stdbool.h>
#include <hiredis/hiredis.h>

typedef struct {
    char host[30];
    int port;
    char password[20];
    int db;
} redis_inf;

typedef struct {
    char host[30];
    int port;
    char user[20];
    char password[20];
    char db[20];
} server_inf;

typedef struct {
    char host[30];
    int port;
    char password[20];
} esl_inf;

typedef struct {
    redis_inf redis;
    server_inf mysql_fs;
    server_inf mysql_cc;
    esl_inf esl;
    char log_file[100];
    bool log_info;
    char *err;
} conf_inf;

typedef struct {
    char callee[50][25];
    int taskid[50];
} phone_list;

void logs(const char *file, const char *messages);
bool load_conf_init(const char *file, conf_inf *conf);
redisContext *redis(char *host, unsigned short port, char *password, unsigned int db);
void originate(esl_handle_t *esl, char *domainid, int count,char *prefix, char *callerName, char *callerID, char *gw, phone_list *number);

#endif
