from pymodbus.client import ModbusTcpClient
modbus_server_ip = 'localhost'
modbus_server_port = 502
client = ModbusTcpClient(modbus_server_ip, port=modbus_server_port)
if client.connect():
    client.write_coil(2, True, unit=1)
