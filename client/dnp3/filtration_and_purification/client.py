import sqlite3

import time
from pydnp3 import opendnp3, openpal, asiopal, asiodnp3
from visitors import *

global result

result=[]

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

def get_dnp_result():
    FILTERS = opendnp3.levels.NORMAL | opendnp3.levels.ALL_COMMS
    HOST = "server4.pharmatech.local"
    LOCAL = "0.0.0.0"
    PORT = 20000
    class SOEHandler(opendnp3.ISOEHandler):
        """
            Override ISOEHandler in this manner to implement application-specific sequence-of-events behavior.

            This is an interface for SequenceOfEvents (SOE) callbacks from the Master stack to the application layer.
        """

        def __init__(self):
            super(SOEHandler, self).__init__()

        def Process(self, info, values):
            """
                Process measurement data.

            :param info: HeaderInfo
            :param values: A collection of values received from the Outstation (various data types are possible).
            """
            global result
            visitor_class_types = {
                opendnp3.ICollectionIndexedBinary: VisitorIndexedBinary,
                opendnp3.ICollectionIndexedDoubleBitBinary: VisitorIndexedDoubleBitBinary,
                opendnp3.ICollectionIndexedCounter: VisitorIndexedCounter,
                opendnp3.ICollectionIndexedFrozenCounter: VisitorIndexedFrozenCounter,
                opendnp3.ICollectionIndexedAnalog: VisitorIndexedAnalog,
                opendnp3.ICollectionIndexedBinaryOutputStatus: VisitorIndexedBinaryOutputStatus,
                opendnp3.ICollectionIndexedAnalogOutputStatus: VisitorIndexedAnalogOutputStatus,
                opendnp3.ICollectionIndexedTimeAndInterval: VisitorIndexedTimeAndInterval
            }
            visitor_class = visitor_class_types[type(values)]
            visitor = visitor_class()
            values.Foreach(visitor)

            conn = connect_db()
            process_name = 'filtration_and_purification'  # Adjust this based on your needs
            protocol = 'DNP3'

            # Insert specific binary values
            if isinstance(values, opendnp3.ICollectionIndexedBinaryOutputStatus):
                filtered_binary = [(index, value) for index, value in visitor.index_and_value if index in [1, 2]]
                for index, value in filtered_binary:
                    variable_name = f'binary_{index}'
                    insert_or_update_record(conn, variable_name, process_name, protocol, str(value), identifier=str(index))
                print(filtered_binary)

            # Insert specific analog values
            if isinstance(values, opendnp3.ICollectionIndexedAnalog):
                filtered_analog = [(index, value) for index, value in visitor.index_and_value if index in [1, 2, 3, 4]]
                for index, value in filtered_analog:
                    variable_name = f'analog_{index}'
                    insert_or_update_record(conn, variable_name, process_name, protocol, str(value), identifier=str(index))
                print(filtered_analog)

            conn.close()

        def Start(self):
            pass

        def End(self):
            pass
    global result
    Cnlog=asiodnp3.ConsoleLogger().Create()
    #Cnlog=MyLogger()
        
    manager = asiodnp3.DNP3Manager(1, Cnlog)
        
    retry = asiopal.ChannelRetry().Default()
        
    Pchnl=asiodnp3.PrintingChannelListener().Create()
        
    channel = manager.AddTCPClient("tcpclient", FILTERS, retry, HOST, LOCAL, PORT, Pchnl)

    stack_config = asiodnp3.MasterStackConfig()
    stack_config.master.responseTimeout = openpal.TimeDuration().Seconds(2)
    stack_config.link.RemoteAddr = 10
        
        #Psoe=asiodnp3.PrintingSOEHandler().Create()
    Psoe=SOEHandler()
    Dmp=asiodnp3.DefaultMasterApplication().Create()
    global master
    master = channel.AddMaster("master", Psoe, Dmp, stack_config)
        
    channel.SetLogFilters(openpal.LogFilters(opendnp3.levels.NOTHING))
    master.SetLogFilters(openpal.LogFilters(opendnp3.levels.NOTHING))
        
    master.Enable()
        
    time.sleep(2)
    #await asyncio.sleep(2)
    master.SetLogFilters(openpal.LogFilters(opendnp3.levels.NORMAL))
    while True:
        s = master.ScanRange(opendnp3.GroupVariationID(10, 0), 1, 2)
        s = master.ScanRange(opendnp3.GroupVariationID(30, 0), 1, 4)
        
        time.sleep(1)

    del master
    del channel
    manager.Shutdown()

get_dnp_result()

