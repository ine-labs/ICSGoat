import sys
import sqlite3
import paho.mqtt.client as mqtt
import asyncio
from asyncua import Client

DATABASE = '/app/monitor.db'

def store_alert(topic, message):
    with sqlite3.connect(DATABASE) as conn:
        cursor = conn.cursor()
        cursor.execute('''
            INSERT INTO alerts (topic, message) VALUES (?, ?)
        ''', (topic, message))
        conn.commit()

def message_handling(client, userdata, msg):
    message = msg.payload.decode()
    print(f"{msg.topic}: {message}")
    store_alert(msg.topic, message)
    if message == "Bioreactor conditions not met, Resetting to ideal values.":
        asyncio.run(write())
    
async def write():
    client = Client(url="opc.tcp://server3.pharmatech.local:26543/UA/FermentationServer")
    try:
        await client.connect()
        
        # Get the root node
        root = client.get_root_node()
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorTemperature"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 32.0
        await node_to_write.set_value(value_to_write)
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorPH"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 7.2
        await node_to_write.set_value(value_to_write)
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorDissolvedOxygen"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 14.0
        await node_to_write.set_value(value_to_write)
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorLevel"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 42.0
        await node_to_write.set_value(value_to_write)
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:WasteRemovalValve"])
        print("Writing to node:", node_to_write)
        
        value_to_write = False
        await node_to_write.set_value(value_to_write)

        print("Value written successfully.")
        await client.disconnect()
    
    except Exception as e:
        print(f"Caught an Exception: {e}")
        
        

client = mqtt.Client()
client.on_message = message_handling

if client.connect("broker.pharmatech.local", 1883, 60) != 0:
    print("Couldn't connect to the mqtt broker")
    sys.exit(1)

client.subscribe("alerts")

try:
    print("Press CTRL+C to exit...")
    client.loop_forever()
except KeyboardInterrupt:
    print("Exiting...")
except Exception as e:
    print(f"Caught an Exception: {e}")
# finally:
#     print("Disconnecting from the MQTT broker")
#     client.disconnect()















# import sys

# import paho.mqtt.client as mqtt


# # aready there table:
# # CREATE TABLE IF NOT EXISTS alerts (
# #                 id INTEGER PRIMARY KEY AUTOINCREMENT,
# #                 topic TEXT NOT NULL,
# #                 message TEXT NOT NULL,
# #                 timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
# #             )

# def message_handling(client, userdata, msg):
#     print(f"{msg.topic}: {msg.payload.decode()}")


# client = mqtt.Client()
# client.on_message = message_handling

# if client.connect("broker.pharmatech.local", 1883, 60) != 0:
#     print("Couldn't connect to the mqtt broker")
#     sys.exit(1)

# client.subscribe("test_topic")

# try:
#     print("Press CTRL+C to exit...")
#     client.loop_forever()
# except Exception:
#     print("Caught an Exception, something went wrong...")
# finally:
#     print("Disconnecting from the MQTT broker")
#     client.disconnect()