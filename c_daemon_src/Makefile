CC = gcc
CFLAGS = -std=gnu99 -Wall -pedantic
INCLUDE = -I /usr/include -I /usr/local/include
ESLPATH = /usr/local/src/freeswitch/libs/esl
MARIADBCLIENT = /usr/lib64/libmariadbclient.a

all: lmxcc ESLeventServ

lmxcc: lmxcc.c config.h $(ESLPATH)/.libs/libesl.a $(MARIADBCLIENT)
	$(CC) $(CFLAGS) $(INCLUDE) -I $(ESLPATH)/src/include -lm -pthread -lconfig -lhiredis -ldl -lssl -lcrypto -lz lmxcc.c $(ESLPATH)/.libs/libesl.a $(MARIADBCLIENT) -o lmxcc
	
ESLeventServ: ESLeventServ.c config.h $(ESLPATH)/.libs/libesl.a 
	$(CC) $(CFLAGS) $(INCLUDE) -I $(ESLPATH)/src/include -lm -pthread -lconfig -lhiredis -ldl -lssl -lcrypto -lz ESLeventServ.c $(ESLPATH)/.libs/libesl.a -o ESLeventServ
	
clean:
	rm -rf lmxcc  ESLeventServ
	rm -rf /usr/lmxcc /usr/config.conf /usr/ESLeventServ /usr/keeprun.sh
install:
	cp config.conf /usr
	cp lmxcc /usr
	cp ESLeventServ /usr	
	cp keeprun.sh /usr 	
	chown freeswitch:daemon /usr/lmxcc
	chown freeswitch:daemon /usr/ESLeventServ
	cp /dev/null /var/log/lmxcc
	chown freeswitch:daemon /var/log/lmxcc
	
