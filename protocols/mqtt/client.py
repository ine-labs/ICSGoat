# mqtt client 

import paho.mqtt.client as mqtt

# Define the MQTT settings
broker = "127.0.0.1"
port = 1883
topic = "test/topic"

# Define the callback functions
def on_connect(client, userdata, flags, rc):
    print(f"Connected with result code {rc}")
    client.subscribe(topic)

def on_message(client, userdata, msg):
    print(f"Message received: {msg.topic} {msg.payload}")

# Create an MQTT client instance
client = mqtt.Client()

# Assign the callback functions
client.on_connect = on_connect
client.on_message = on_message

# Connect to the broker
client.connect(broker, port, 60)

# Publish a message
client.loop_start()
client.publish(topic, "Hello MQTT")
client.loop_stop()

# Start the MQTT client loop
client.loop_forever()
