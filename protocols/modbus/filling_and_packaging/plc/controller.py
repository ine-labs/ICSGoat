import requests
import subprocess
import time


def start():
    print("Starting filling and packaging plc")
    try:
        subprocess.Popen(["python", "slave.py"])
        time.sleep(3)
    except Exception as e:
        print(f"Error starting slave: {e}")

def stop():
    print("Stopping filling and packaging plc")
    try:
        subprocess.run(["pkill", "-f", "slave.py"])
    except Exception as e:
        print(f"Error stopping slave: {e}")
    

url = "http://hmi.pharmatech.local:5000/data"

start()
# wait for 15 seconds
time.sleep(15)

stop()
while True:
    
    try:
        response = requests.get(url)
        data = response.json()

        temperature = 0
        humidity = 0

        for item in data:
            if item['variable_name'] == 'Temperature' and item['process_name'] == 'formulation':
                temperature = float(item['value'])
                break
        for item in data:
            if item['variable_name'] == 'Humidity' and item['process_name'] == 'formulation':
                humidity = float(item['value'])
                break
            
        valve4 = 0
        for item in data:
            if item['variable_name'] == 'binary_2':
                valve4 = item['value']
                if valve4 == "True":
                    valve4 = True
                else:
                    valve4 = False
                break

        print(valve4)
        if temperature > 10 and temperature < 50 and humidity > 40 and humidity < 70 and valve4 == True:
            start()
        else:
            stop()

        
        time.sleep(3)
    except Exception as e:
        print(f"An error occurred: {e}")
        time.sleep(8)
