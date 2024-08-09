import sqlite3
import time
from pymodbus.client import ModbusTcpClient
import socket

process = 'filling_and_packaging'

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

# Function to read holding registers
def read_holding_registers(client, address, count):
    result = client.read_holding_registers(address, count, unit=1)
    if not result.isError():
        return result.registers
    else:
        print(f"Error reading holding registers: {result}")
        return None

# Function to read coils
def read_coils(client, address, count):
    result = client.read_coils(address, count, unit=1)
    if not result.isError():
        return result.bits
    else:
        print(f"Error reading coils: {result}")
        return None

# Function to read input registers
def read_input_registers(client, address, count):
    result = client.read_input_registers(address, count, unit=1)
    if not result.isError():
        return result.registers
    else:
        print(f"Error reading input registers: {result}")
        return None

def main():
    # Configuration
    # Get the IP address from the hostname
    modbus_server_ip = socket.gethostbyname('server6.pharmatech.local')
    modbus_server_port = 502        # Port number of the Modbus server

    # Create a Modbus client
    client = ModbusTcpClient(modbus_server_ip, port=modbus_server_port)
    
    # Connect to the SQLite database
    conn = connect_db()

    while True:
        try:
            # Connect to the Modbus server
            if client.connect():
                print("Connected to Modbus server")
                
                try:
                    while True:
                        # Read and save holding registers (0-7) for filling levels
                        holding_registers = read_holding_registers(client, 0, 8)
                        if holding_registers is not None:
                            print(f"Holding Registers (0-7): {holding_registers}")
                            for i, value in enumerate(holding_registers):
                                insert_or_update_record(conn, f'holding_register_{i}', process, 'Modbus', str(value), identifier=f'{0 + i}')

                        # Read and save coils (10-17) for conveyor status
                        coils = read_coils(client, 10, 8)
                        if coils is not None:
                            print(f"Coils (10-17): {coils}")
                            for i, value in enumerate(coils):
                                insert_or_update_record(conn, f'coil_{i}', process, 'Modbus', str(value), identifier=f'{10 + i}')

                        # Read and save input registers (20-27) for flow levels
                        input_registers_flow = read_input_registers(client, 20, 8)
                        if input_registers_flow is not None:
                            print(f"Input Registers - Flow Levels (20-27): {input_registers_flow}")
                            for i, value in enumerate(input_registers_flow):
                                insert_or_update_record(conn, f'input_register_flow_{i}', process, 'Modbus', str(value), identifier=f'{20 + i}')

                        # Read and save input registers (30-37) for pressure values
                        input_registers_pressure = read_input_registers(client, 30, 8)
                        if input_registers_pressure is not None:
                            print(f"Input Registers - Pressure Sensors (30-37): {input_registers_pressure}")
                            for i, value in enumerate(input_registers_pressure):
                                insert_or_update_record(conn, f'input_register_pressure_{i}', process, 'Modbus', str(value), identifier=f'{30 + i}')
                        
                        # Read and save holding register (50) for bottle count
                        bottle_count_register = read_holding_registers(client, 50, 1)
                        if bottle_count_register is not None:
                            bottle_count = bottle_count_register[0]
                            print(f"Bottle Count (Holding Register 50): {bottle_count}")
                            insert_or_update_record(conn, 'bottle_count', process, 'Modbus', str(bottle_count), identifier='50')

                        # Wait for 1 second before polling again
                        time.sleep(1)

                except Exception as e:
                    print(f"An error occurred during polling: {e}")
                    time.sleep(5)
                finally:
                    # Close the connection to the Modbus server
                    client.close()
                    print("Disconnected from Modbus server")

            else:
                print("Failed to connect to Modbus server")
                time.sleep(5)

        except Exception as e:
            print(f"An error occurred: {e}")
            time.sleep(5)

    # Close the connection to the SQLite database
    conn.close()
    
if __name__ == "__main__":
    main()
