# sensor_simulator.py

import paho.mqtt.client as mqtt
import random
import time

# MQTT settings
broker = "127.0.0.1"
port = 1883
topic = "room/temperature"

# Callback when the client connects to the broker
def on_connect(client, userdata, flags, rc):
    print(f"Connected with result code {rc}")

# Create an MQTT client instance
client = mqtt.Client()

# Assign the on_connect callback
client.on_connect = on_connect

# Connect to the broker
client.connect(broker, port, 60)

# Start the MQTT client loop in a separate thread
client.loop_start()

try:
    while True:
        # Simulate a temperature value
        temperature = round(random.uniform(15.0, 30.0), 2)
        print(f"Publishing temperature: {temperature}°C")
        
        # Publish the temperature to the MQTT topic
        client.publish(topic, temperature)
        
        # Wait for a few seconds before publishing the next value
        time.sleep(5)

except KeyboardInterrupt:
    print("Simulation stopped")

# Stop the MQTT client loop
client.loop_stop()
client.disconnect()