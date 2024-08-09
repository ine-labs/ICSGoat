import subprocess
import time
import requests
import sys
import paho.mqtt.client as paho

client = paho.Client()

def start_node():
    try:
        subprocess.Popen(["npm", "start"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        print("Node.js application started")
    except Exception as e:
        print(f"Error starting Node.js application: {e}")

def stop_node():
    try:
        subprocess.Popen(["npm", "stop"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        time.sleep(2)
        print("Node.js application stopped")
    except Exception as e:
        print(f"Error stopping Node.js application: {e}")

url = "http://hmi.pharmatech.local:5000/data"

start_node()

# wait for 10 seconds
time.sleep(10)

while True:
    try:
        time.sleep(3)
        response = requests.get(url)
        data = response.json()

        valve1 = 0
        pressure = 0
        for item in data:
            if item['variable_name'] == 'valve-pump':
                valve1 = int(item['value'])
            elif item['variable_name'] == 'Pressure' and item['process_name'] == 'mixing_and_blending':
                pressure = float(item['value']) 

        print(valve1)
        print(pressure)
        print(f"Valve 1: {valve1}, Pressure: {pressure} so {valve1 == 0 and pressure <= 50.0} and {valve1 == 1 and pressure > 50.0  }")

        if valve1 == 0 and pressure <= 50.0:
            print("Valve is closed, stopping Node.js application")
            stop_node()
        
        if valve1 == 1 and pressure > 50.0:
            print("Valve is open, starting Node.js application")
            start_node()
            
        # // ideal ph - 7 to 8
        # // ideal o2 > 10
        # // ideal temp 30 - 45
        #  if any of the above conditions are not met, start the node.js application
        
        if client.connect("broker.pharmatech.local", 1883, 60) != 0:
            print("Couldn't connect to the mqtt broker")
            sys.exit(1)
        
            
        ph_value = 0 
        o2_value = 0
        temp_value = 0
        n_valve = 0
        level = 0
        
        for item in data:
            if item['variable_name'] == 'BioreactorPH':
                ph_value = float(item['value'])
            elif item['variable_name'] == 'BioreactorDissolvedOxygen':
                o2_value = float(item['value'])
            elif item['variable_name'] == 'BioreactorTemperature':
                temp_value = float(item['value'])
            elif item['variable_name'] == 'NutrientSupplyValve':
                n_valve = int(item['value'])
            elif item['variable_name'] == 'BioreactorLevel':
                level = float(item['value'])
        
        if n_valve == 0:
            print("Nutrient supply valve is closed")
            continue
            
        
        if ph_value < 7 or ph_value > 8 or o2_value < 10 or temp_value < 30 or temp_value > 45 or level == 0 or level > 50:
            print("Bioreactor conditions not met, sending mqtt message")
            mqtt_topic = "alerts"
            mqtt_message = f"Bioreactor conditions not met, Resetting to ideal values."
            client.publish(mqtt_topic, mqtt_message, 0)
            client.disconnect()
            # send mqtt message

    except Exception as e:
        print(f"Error fetching data: {e}")

    time.sleep(3)
