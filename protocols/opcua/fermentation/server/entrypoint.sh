#!/bin/bash

# Forward SIGTERM to the process
_term() {
  echo "Caught SIGTERM signal!"
  kill -TERM "$child" 2>/dev/null
}

# Trap SIGTERM signal
trap _term SIGTERM

# Run the Python script in the background
# python pydnp3/outstation.py &
pip3 install requests
pip3 install paho-mqtt
python3 /app/server/controller.py &

# Save the PID of the background process
child=$!
wait "$child"
