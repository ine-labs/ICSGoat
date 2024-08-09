import requests
import subprocess
import time


def start():
    print("Starting outstation")
    try:
        subprocess.Popen(["python", "/app/pydnp3/examples/outstation.py"])
    except Exception as e:
        print(f"Error starting outstation: {e}")

def stop():
    print("Stopping outstation")
    try:
        subprocess.run(["pkill", "-f", "/app/pydnp3/examples/outstation.py"])
    except Exception as e:
        print(f"Error stopping outstation: {e}")
    
start()

# wait for 15 seconds
time.sleep(15)

url = "http://hmi.pharmatech.local:5000/data"

while True:
    try:
        response = requests.get(url)
        data = response.json()
        bioreactor_level = 0
        waste_valve = 0
        for item in data:
            if item['variable_name'] == 'BioreactorLevel':
                bioreactor_level = float(item['value'])
                break
        for item in data:
            if item['variable_name'] == 'WasteRemovalValve':
                waste_valve = int(item['value'])
                break

        print(bioreactor_level)

        if bioreactor_level > 50 and waste_valve == 0:
            start()
        else:
            stop()

        time.sleep(3)
    except Exception as e:
        print(f"Error: {e}")
        time.sleep(8)