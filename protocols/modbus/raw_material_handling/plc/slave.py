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

# Configure the Modbus server context
store = ModbusSlaveContext(
    di=ModbusSequentialDataBlock(0, [0]*100),  # Discrete Inputs
    co=ModbusSequentialDataBlock(0, [0]*100),  # Coils
    hr=ModbusSequentialDataBlock(0, [0]*100),  # Holding Registers
    ir=ModbusSequentialDataBlock(0, [0]*100)   # Input Registers
)
context = ModbusServerContext(slaves=store, single=True)

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
    # Set initial values for coil and input registers
    context[0].setValues(1, 2, [0])  # Coil 2
    context[0].setValues(4, 0, [0])  # Input Register 0
    context[0].setValues(4, 1, [0])  # Input Register 1
    
    while True:
        # Get the existing values from the context
        input_register_0 = context[0].getValues(4, 0)  # Input Register 0
        input_register_1 = context[0].getValues(4, 1)  # Input Register 1
        coil_2 = context[0].getValues(1, 2)  # Coil 2
        
        # Simulate level sensor data (0-100)
        input_register_0[0] = random.randint(40, 100)
        context[0].setValues(4, 0, input_register_0)
        
        # Simulate valve/pump status (0 or 1)
        # coil_2[0] = random.choice([0, 1])
        # context[0].setValues(1, 2, coil_2)
        
        # If valve is zero, set flow meter to zero
        if coil_2[0] == 0:
            input_register_1[0] = 0
        else:
            # Simulate flow meter data (0-1000)
            input_register_1[0] = random.randint(0, 1000)
        
        context[0].setValues(4, 1, input_register_1)
        
        time.sleep(2)  # Update every 2 seconds

# Start a thread to update sensor data
threading.Thread(target=update_sensor_data, daemon=True).start()

# Start the Modbus TCP server
docker_ip = get_docker_container_ip()
StartTcpServer(context=context, identity=identity, address=(docker_ip, 502))
