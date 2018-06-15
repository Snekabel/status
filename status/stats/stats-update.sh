#!/bin/sh
 
case "$1" in
  hour)
    ARG=-h
    NAME=hour
    ;;
  day)
    ARG=-d
    NAME=day
    ;;
  month)
    ARG=-m
    NAME=month
    ;;
  *)
    echo "Usage: $(basename $0) {hour|day|month}" >&2
    exit 3
    ;;
esac
 
vnstati $ARG -i eth0 -o $(dirname $0)/eth0-$NAME.png
