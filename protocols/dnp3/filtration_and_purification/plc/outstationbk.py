
import time
import threading
import random
import requests
from pydnp3 import openpal, asiopal, asiodnp3, opendnp3

def configure_outstation_database(db_config):
    db_config.analog[1].clazz = opendnp3.PointClass.Class2
    db_config.analog[1].svariation = opendnp3.StaticAnalogVariation.Group30Var1
    db_config.analog[1].evariation = opendnp3.EventAnalogVariation.Group32Var7
    db_config.analog[2].clazz = opendnp3.PointClass.Class2
    db_config.analog[2].svariation = opendnp3.StaticAnalogVariation.Group30Var1
    db_config.analog[2].evariation = opendnp3.EventAnalogVariation.Group32Var7
    db_config.binary[1].clazz = opendnp3.PointClass.Class2
    db_config.binary[1].svariation = opendnp3.StaticBinaryVariation.Group1Var2
    db_config.binary[1].evariation = opendnp3.EventBinaryVariation.Group2Var2
    db_config.binary[2].clazz = opendnp3.PointClass.Class2
    db_config.binary[2].svariation = opendnp3.StaticBinaryVariation.Group1Var2
    db_config.binary[2].evariation = opendnp3.EventBinaryVariation.Group2Var2

# Create a DNP3Manager
manager = asiodnp3.DNP3Manager(1, asiodnp3.ConsoleLogger().Create())

# Set retry parameters for the channel
retry = asiopal.ChannelRetry().Default()

# Create a channel (TCP server)
channel = manager.AddTCPServer(
    "server",
    opendnp3.levels.NOTHING,  # Log level
    retry,
    "0.0.0.0",  # Address
    20000,  # Port
    asiodnp3.PrintingChannelListener().Create()
)

# Create an Outstation stack configuration
outstation_config = asiodnp3.OutstationStackConfig(
    opendnp3.DatabaseSizes.AllTypes(10)
)

configure_outstation_database(outstation_config.dbConfig)

# Setting the default settings
outstation_config.link.LocalAddr = 10
outstation_config.link.RemoteAddr = 1
outstation_config.link.KeepAliveTimeout = openpal.TimeDuration().Max()

# Create the outstation application and command handler
class OutstationApplication(opendnp3.IOutstationApplication):
    def __init__(self):
        super().__init__()

class CommandHandler(opendnp3.ICommandHandler):
    def __init__(self):
        super().__init__()
    def Select(self, command, index):
        print(f'Select - Command: {command}, Index: {index}')
        return opendnp3.CommandStatus.SUCCESS
    def Operate(self, command, index, op_type):
        print(f'Operate - Command: {command}, Index: {index}, Operation Type: {op_type}')
        return opendnp3.CommandStatus.SUCCESS

application = OutstationApplication()
command_handler = CommandHandler()

# Add the outstation to the channel
outstation = channel.AddOutstation(
    "outstation",
    command_handler,
    application,
    outstation_config
)


# Enable the outstation
outstation.Enable()


def simulate_value_changes(outstation):

    builder = asiodnp3.UpdateBuilder()
    # Simulate valve states (True for open, False for closed)
    valve_state_1 = opendnp3.BinaryOutputStatus(True)
    valve_state_2 = opendnp3.BinaryOutputStatus(False)  # Valve 2 initially closed
    builder.Update(valve_state_1, 1)
    builder.Update(valve_state_2, 2)
        
    while True:
        # Simulate pressure sensor values (e.g., in psi)
        pressure_value_1 = opendnp3.Analog(random.uniform(10.0, 30.0))  # Pressure Sensor 1
        pressure_value_2 = opendnp3.Analog(random.uniform(10.0, 30.0))  # Pressure Sensor 2
        builder.Update(pressure_value_1, 1)
        builder.Update(pressure_value_2, 2)
        
        
        
        # Simulate flow meter values (e.g., in liters per minute)
        flow_value_1 = opendnp3.Analog(random.uniform(50.0, 150.0))  # Flow Meter 1
        flow_value_2 = opendnp3.Analog(random.uniform(50.0, 150.0))  # Flow Meter 2
        builder.Update(flow_value_1, 3)
        builder.Update(flow_value_2, 4)
        
        
        
        outstation.Apply(builder.Build())
        
        time.sleep(5)  # Change values every 5 seconds

# Start the simulation in a separate thread
time.sleep(15)  # Wait for the outstation to be enabled
simulation_thread = threading.Thread(target=simulate_value_changes, args=(outstation,))
simulation_thread.daemon = True
simulation_thread.start()


# Run the program
# print("DNP3 Outstation running. Press enter to exit.")
# input()

# Run the program and wait indefinitely, handle graceful shutdown
try:
    print("DNP3 Outstation running. Press Ctrl+C to exit.")
    while True:
        time.sleep(1)
except KeyboardInterrupt:
    print("\nExiting the program.")
    manager.Shutdown()
