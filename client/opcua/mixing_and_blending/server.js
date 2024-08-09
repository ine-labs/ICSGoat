const { OPCUAClient, AttributeIds } = require("node-opcua");
const sqlite3 = require("sqlite3").verbose();

async function main() {
    // Function to connect to the SQLite database
    function connectDb(dbName = '/app/monitor.db') {
        return new sqlite3.Database(dbName);
    }

    // Function to insert or update a record in the monitor table
    function insertOrUpdateRecord(db, variableName, processName, protocol, value, identifier = null) {
        db.get(`
            SELECT * FROM monitor WHERE variable_name = ? AND process_name = ? AND protocol = ?
        `, [variableName, processName, protocol], (err, row) => {
            if (err) {
                console.error('Error querying the database:', err);
                return;
            }
            if (row) {
                db.run(`
                    UPDATE monitor SET value = ? WHERE variable_name = ? AND process_name = ? AND protocol = ?
                `, [value, variableName, processName, protocol], (err) => {
                    if (err) {
                        console.error('Error updating the database:', err);
                    } else {
                        // console.log(`Updated ${variableName} in the database.`);
                    }
                });
            } else {
                db.run(`
                    INSERT INTO monitor (variable_name, identifier, process_name, protocol, value)
                    VALUES (?, ?, ?, ?, ?)
                `, [variableName, identifier, processName, protocol, value], (err) => {
                    if (err) {
                        console.error('Error inserting into the database:', err);
                    } else {
                        // console.log(`Inserted ${variableName} into the database.`);
                    }
                });
            }
        });
    }

    try {
        const client = OPCUAClient.create({
            endpointMustExist: false,
        });

        const endpointUrl = "opc.tcp://server2.pharmatech.local:26543/UA/mixing_and_blending";

        await client.connect(endpointUrl);
        console.log("Connected to OPC UA Server");

        const session = await client.createSession();
        console.log("Session created");

        const db = connectDb();

        async function readVariable(nodeId, variableName) {
            try {
                const dataValue = await session.read({
                    nodeId: nodeId,
                    attributeId: AttributeIds.Value,
                });
                const value = dataValue.value.value;
                // console.log(`Value of ${nodeId} (${variableName}):`, value);
                insertOrUpdateRecord(db, variableName, 'mixing_and_blending', 'OPC UA', value, nodeId);
            } catch (error) {
                // console.error(`Error reading ${nodeId} (${variableName}):`, error);
            }
        }

        const nodesToRead = [
            { nodeId: "ns=1;i=1001", variableName: "MixerSpeed" },
            { nodeId: "ns=1;i=1002", variableName: "MixerStatus" },
            { nodeId: "ns=1;i=1004", variableName: "Temperature" },
            { nodeId: "ns=1;i=1005", variableName: "TemperatureSetpoint" },
            { nodeId: "ns=1;i=1007", variableName: "Pressure" },
            { nodeId: "ns=1;i=1009", variableName: "FlowRate" },
            { nodeId: "ns=1;i=1010", variableName: "IngredientQuantity" },
        ];

        setInterval(async () => {
            // console.log("Reading values from OPC UA Server:");
            for (const { nodeId, variableName } of nodesToRead) {
                await readVariable(nodeId, variableName);
            }
        }, 1000);

        // Keep the session alive
        process.on("SIGINT", async () => {
            await session.close();
            await client.disconnect();
            db.close();
            // console.log("Client disconnected and database closed");
            process.exit(0);
        });

    } catch (error) {
        console.error("An error has occurred:", error);
    }
}

main();













// const { OPCUAClient, AttributeIds } = require("node-opcua");

// async function main() {
//     try {
//         const client = OPCUAClient.create({
//             endpointMustExist: false,
//         });

//         const endpointUrl = "opc.tcp://server2.pharmatech.local:26543/UA/mixing_and_blending";

//         await client.connect(endpointUrl);
//         console.log("Connected to OPC UA Server");

//         const session = await client.createSession();
//         console.log("Session created");

//         async function readVariable(nodeId) {
//             try {
//                 const dataValue = await session.read({
//                     nodeId: nodeId,
//                     attributeId: AttributeIds.Value,
//                 });
//                 // console.log(`Value of ${nodeId}:`, dataValue);
//                 console.log(`Value of ${nodeId}:`, dataValue.value.value);
//             } catch (error) {
//                 console.error(`Error reading ${nodeId}:`, error);
//             }
//         }

//         const nodesToRead = [
//             "ns=1;i=1001", // MixerSpeed
//             "ns=1;i=1002", // MixerStatus
//             "ns=1;i=1004", // Temperature
//             "ns=1;i=1005", // TemperatureSetpoint
//             "ns=1;i=1007", // Pressure
//             "ns=1;i=1009", // Flow rate
//             "ns=1;i=1010" // IngredientQuantity
//         ];

//         setInterval(async () => {
//             console.log("Reading values from OPC UA Server:");
//             for (const nodeId of nodesToRead) {
//                 await readVariable(nodeId);
//             }
//         }, 1000);

//         // Keep the session alive
//         process.on("SIGINT", async () => {
//             await session.close();
//             await client.disconnect();
//             console.log("Client disconnected");
//             process.exit(0);
//         });

//     } catch (error) {
//         console.error("An error has occurred:", error);
//     }
// }

// main();
