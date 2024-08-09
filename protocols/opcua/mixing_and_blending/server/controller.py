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
    print("Fetching data from HMI")
    try:
        response = requests.get(url)
        data = response.json()

        valve1 = 0
        for item in data:
            if item['variable_name'] == 'valve-pump':
                valve1 = int(item['value'])
                break

        print(valve1)

        if valve1 == 1:
            print("Valve is open, starting Node.js application")
            start_node()
        else:
            print("Valve is closed, stopping Node.js application")
            stop_node()

    except Exception as e:
        print(f"Error fetching data: {e}")

    time.sleep(3)
