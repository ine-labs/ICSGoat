import asyncio
from asyncua import Client

async def write():
    client = Client(url="opc.tcp://server3.pharmatech.local:26543/UA/FermentationServer")
    try:
        await client.connect()
        
        # Get the root node
        root = client.get_root_node()
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorTemperature"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 32.0
        await node_to_write.set_value(value_to_write)
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorPH"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 7.2
        await node_to_write.set_value(value_to_write)
        
        node_to_write = await root.get_child(["0:Objects", "1:Bioreactors", "1:BioreactorDissolvedOxygen"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 14.0
        await node_to_write.set_value(value_to_write)

        print("Value written successfully.")
        
    finally:
        await client.disconnect()

asyncio.run(write())
# asyncua==0.8.4