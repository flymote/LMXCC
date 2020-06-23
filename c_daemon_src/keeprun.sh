#!/bin/bash
svrnm="ESLeventServ"
cmd="/usr/ESLeventServ -s"
if ps -ef | grep $svrnm | egrep -v grep > /dev/null
then
echo "$svrnm is started!"
else
echo "$svrnm is nostart"
`$cmd`
echo "$cmd been started !!"
fi
