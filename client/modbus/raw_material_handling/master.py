import sqlite3
import time
from pymodbus.client import ModbusTcpClient
import time

process = 'raw_material_handling'

# Function to connect to the SQLite database
def connect_db(db_name='/app/monitor.db'):
    conn = sqlite3.connect(db_name)
    return conn

def insert_or_update_record(conn, variable_name, process_name, protocol, value, identifier=None):
    cursor = conn.cursor()

    # Check if the record already exists
    cursor.execute('''
        SELECT * FROM monitor WHERE variable_name=? AND process_name=? AND protocol=?
    ''', (variable_name, process_name, protocol))
    existing_record = cursor.fetchone()

    if existing_record:
        # If the record exists, update it
        cursor.execute('''
            UPDATE monitor SET value=? WHERE variable_name=? AND process_name=? AND protocol=?
        ''', (value, variable_name, process_name, protocol))
    else:
        # If the record does not exist, insert a new one
        cursor.execute('''
            INSERT INTO monitor (variable_name, identifier, process_name, protocol, value)
            VALUES (?, ?, ?, ?, ?)
        ''', (variable_name, identifier, process_name, protocol, value))
    conn.commit()

# Function to read input registers
def read_input_registers(client, address, count):
    result = client.read_input_registers(address, count, unit=1)
    if not result.isError():
        return result.registers
    else:
        print(f"Error reading input registers: {result}")
        return None

# Function to read coils
def read_coils(client, address, count):
    result = client.read_coils(address, count, unit=1)
    if not result.isError():
        return result.bits
    else:
        print(f"Error reading coils: {result}")
        return None

def main():
    # Configuration
    modbus_server_ip = 'server1.pharmatech.local'  # Replace with the actual IP address of your server
    modbus_server_port = 502        # Port number of the Modbus server

    # Create a Modbus client
    client = ModbusTcpClient(modbus_server_ip, port=modbus_server_port)
    
    # Connect to the Modbus server
    if client.connect():
        print("Connected to Modbus server")
        # Read and insert values into the SQLite database
        conn = connect_db()
        while True:
            # Read input registers (0 and 1)
            input_registers = read_input_registers(client, 0, 2)
            if input_registers is not None:
                print(f"Input Registers (0-1): Level Sensor = {input_registers[0]}, Flow Meter = {input_registers[1]}")
                insert_or_update_record(conn, 'level-sensor', process, 'Modbus', input_registers[0])
                insert_or_update_record(conn, 'flow-meter', process, 'Modbus', input_registers[1])
            
            # Read coil (2)
            coils = read_coils(client, 2, 1)
            if coils is not None:
                print(f"Coil (2): Valve/Pump Status = {coils[0]}")
                insert_or_update_record(conn, 'valve-pump', process, 'Modbus', coils[0])
            
            time.sleep(2)  # Read every 2 seconds

        # Close the database connection
        conn.close()
        client.close()
        print("Disconnected from Modbus server")
    else:
        print("Failed to connect to Modbus server")

if __name__ == "__main__":
    main()
