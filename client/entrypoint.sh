#!/bin/bash

# Forward SIGTERM to the process
_term() {
  echo "Caught SIGTERM signal!"
  kill -TERM "$child" 2>/dev/null
}

# Trap SIGTERM signal
trap _term SIGTERM

# start dnp3 master
python pydnp3/examples/client.py &

# start modbus master
python3 filling_and_packaging/master.py &
python3 raw_material_handling/master.py &

# start opcua client

cd formulation && npm start &
cd fermentation && npm start &
cd mixing_and_blending && npm start &

# run the flask server

cd monitor && python3 app.py &

cd mqtt && python3 sub.py &
# Save the PID of the background process
child=$!
wait "$child"
