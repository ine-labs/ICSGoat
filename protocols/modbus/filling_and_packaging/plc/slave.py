from pymodbus.server import StartTcpServer
from pymodbus.device import ModbusDeviceIdentification
from pymodbus.datastore import ModbusSequentialDataBlock
from pymodbus.datastore import ModbusSlaveContext, ModbusServerContext
from pymodbus.transaction import ModbusRtuFramer, ModbusBinaryFramer
from pymodbus.datastore.store import ModbusSparseDataBlock
import random
import time
import threading
import socket
import requests


# def get_initial_bottle_count():
#     url = "http://hmi.pharmatech.local:5000/data"
#     response = requests.get(url)
#     data = response.json()

#     bottle_count = 0

#     for item in data:
#         if item['variable_name'] == 'bottle_count':
#             bottle_count = int(item['value'])
#             break

#     return bottle_count


# Configure the Modbus server context
store = ModbusSlaveContext(
    di=ModbusSequentialDataBlock(0, [0]*100),  # Discrete Inputs
    co=ModbusSequentialDataBlock(0, [0]*100),  # Coils
    hr=ModbusSequentialDataBlock(0, [0]*100),  # Holding Registers
    ir=ModbusSequentialDataBlock(0, [0]*100)   # Input Registers
)
context = ModbusServerContext(slaves=store, single=True)

# update bottle count in Holding Register 50



# Configure the identity of the Modbus server
identity = ModbusDeviceIdentification()
identity.VendorName = 'PharmaTech'
identity.ProductCode = 'PHARMA'
identity.VendorUrl = 'http://www.pharmatech.com/'
identity.ProductName = 'PharmaTech PLC'
identity.ModelName = 'PharmaTech PLC'
identity.MajorMinorRevision = '1.0'

def get_docker_container_ip():
    try:
        # Get the hostname of the Docker container
        hostname = socket.gethostname()
        
        # Get the IP address corresponding to the hostname
        ip_address = socket.gethostbyname(hostname)
        
        return ip_address
    except Exception as e:
        print("Error occurred while getting Docker container IP:", e)
        return None
    
# Function to simulate sensor data changes
def update_sensor_data():
    bottle_count = 0
    
    while True:
        all_bottles_filled = True
        
        # Filling Machine Fill Levels (Holding Register 0-7)
        for i in range(8):
            fill_level = context[0].getValues(3, i, count=1)[0]
            if fill_level < 100:
                fill_level += 10  # Simulate filling process
                context[0].setValues(3, i, [fill_level])
                all_bottles_filled = False
        
        # Update Conveyor Status (Coil 10)
        if all_bottles_filled:
            context[0].setValues(1, 10, [1])  # Conveyor ON
            time.sleep(2)  # Simulate time taken for conveyor to move bottles away
            for i in range(8):
                context[0].setValues(3, i, [0])  # Reset fill levels after moving bottles
            context[0].setValues(1, 10, [0])  # Conveyor OFF
            bottle_count += 8  # Increase bottle count by 8
            
            if bottle_count >= 1000:
                # Reset everything when bottle count reaches 1000
                bottle_count = 0
                for i in range(49):
                    context[0].setValues(3, i, [0])  # Reset all Holding Registers
                    context[0].setValues(4, i, [0])  # Reset all Input Registers
                for i in range(100):
                    context[0].setValues(1, i, [0])  # Reset all Coils
                    context[0].setValues(2, i, [0])  # Reset all Discrete Inputs
        else:
            context[0].setValues(1, 10, [0])  # Conveyor OFF
        
        # Simulate Flow Levels (Input Registers 20-27)
        for i in range(20, 28):
            flow_level = random.randint(5, 15) if not all_bottles_filled else 0
            context[0].setValues(4, i, [flow_level])

        # Simulate Filling Pressure Values (Input Registers 30-37)
        for i in range(30, 38):
            pressure_value = random.randint(500, 1000) if not all_bottles_filled else 0
            context[0].setValues(4, i, [pressure_value])
        
        # Update Bottle Count (Holding Register 50)
        context[0].setValues(3, 50, [bottle_count])

        time.sleep(1)  # Update every 1 second
        
threading.Thread(target=update_sensor_data, daemon=True).start()

# Start the Modbus TCP server
docker_ip = get_docker_container_ip()
StartTcpServer(context=context, identity=identity, address=(docker_ip, 502))

# time.sleep(15)
# bottle_count = get_initial_bottle_count()
# context[0].setValues(3, 50, [bottle_count])