import subprocess
import time
import requests

def start_node():
    try:
        subprocess.Popen(["npm", "start"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        print("Node.js application started")
    except Exception as e:
        print(f"Error starting Node.js application: {e}")

def stop_node():
    try:
        subprocess.Popen(["npm", "stop"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        print("Node.js application stopped")
    except Exception as e:
        print(f"Error stopping Node.js application: {e}")

url = "http://hmi.pharmatech.local:5000/data"

start_node()

# wait for 10 seconds
time.sleep(10)

while True:
    try:
        response = requests.get(url)
        data = response.json()

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

        if valve4 != False:
            print("Valve is open, starting Node.js application")
            start_node()
        else:
            print("Valve is closed, stopping Node.js application")
            stop_node()

    except Exception as e:
        print(f"Error fetching data: {e}")

    time.sleep(3)
