#!/bin/bash
/etc/init.d/ssh start
sleep 2

supervisord -n
