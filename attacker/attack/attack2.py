# 1. enumerate nodes
# import asyncio
# from asyncua import Client

# async def enum():
#     client = Client(url="opc.tcp://localhost:26540")
#     try:
#         await client.connect()
#         namespace_array = await client.get_namespace_array()
#         print(namespace_array)
#         for selected_namespace in range(len(namespace_array)):
#             print(f"Nodes and variables under namespace {selected_namespace}:")
#             objects_node = client.get_objects_node()
#             childrens = await objects_node.get_children()
#             for node in childrens:
#                 if node.nodeid.NamespaceIndex == selected_namespace:
#                     node_name = await node.read_browse_name()
#                     print(f"Name: {node_name.Name} NodeId: {node.nodeid}")
#                     variables = await node.get_variables()
#                     for variable in variables:
#                         name = await variable.read_browse_name()
#                         print(f"    Variable: {name.Name}, NodeId: {variable.nodeid}")
#                         try:
#                             value = await variable.read_value()
#                             print(f"        Value: {value}")
#                         except Exception as e:
#                             print(f"        Error reading value: {e}")
#     finally:
#         await client.disconnect()

# asyncio.run(enum())

# 2. alter pressure


import asyncio
from asyncua import Client

async def write():
    client = Client(url="opc.tcp://localhost:26540")
    try:
        await client.connect()
        
        # Get the root node
        root = client.get_root_node()
        
        node_to_write = await root.get_child(["0:Objects", "1:PressureSensors", "1:Pressure"])
        print("Writing to node:", node_to_write)
        
        value_to_write = 51.0
        await node_to_write.set_value(value_to_write)

        print("Value written successfully.")
        
    finally:
        await client.disconnect()

asyncio.run(write())


