#!/bin/bash

# Define the command to be executed
command="echo -ne \"05641ac40a0001008a1cd7c9050c0128010002008101640000001e320000000000ffff\" | xxd -p -r | nc -w1 172.31.200.22 20000 | xxd -p"

# Infinite loop
while true
do
  eval $command
  sleep 1
done
