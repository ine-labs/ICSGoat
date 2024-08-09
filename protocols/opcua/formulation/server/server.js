const {
    OPCUAServer,
    Variant,
    DataType,
    StatusCodes
} = require("node-opcua");
const os = require("os");
const hostname = os.hostname();

process.title = 'opcua-server';

async function startServer() {
    const server = new OPCUAServer({
        port: 26543, // the port of the listening socket of the server
        resourcePath: "/UA/FormulationServer",
        buildInfo: {
            productName: "FormulationServer",
            buildNumber: "1",
            buildDate: new Date()
        }
    });

    await server.initialize();

    const addressSpace = server.engine.addressSpace;
    const namespace = addressSpace.getOwnNamespace();

    // Add a new object for the Mixers
    const mixers = namespace.addObject({
        organizedBy: addressSpace.rootFolder.objects,
        browseName: "Mixers"
    });

    // Add variables for Mixer
    const mixerSpeed = namespace.addVariable({
        componentOf: mixers,
        browseName: "MixerSpeed",
        dataType: "Double",
        value: new Variant({ dataType: DataType.Double, value: 0 })
    });

    const mixerStatus = namespace.addVariable({
        componentOf: mixers,
        browseName: "MixerStatus",
        dataType: "String",
        value: new Variant({ dataType: DataType.String, value: "Stopped" })
    });

    // Add a new object for the Temperature and Humidity Sensors
    const environmentalSensors = namespace.addObject({
        organizedBy: addressSpace.rootFolder.objects,
        browseName: "EnvironmentalSensors"
    });

    // Add variables for Temperature Sensors
    const temperature = namespace.addVariable({
        componentOf: environmentalSensors,
        browseName: "Temperature",
        dataType: "Double",
        value: new Variant({ dataType: DataType.Double, value: 5.0 }) // Initial value
    });

    // Add variables for Humidity Sensors
    const humidity = namespace.addVariable({
        componentOf: environmentalSensors,
        browseName: "Humidity",
        dataType: "Double",
        value: new Variant({ dataType: DataType.Double, value: 20.0 }) // Initial value
    });

    // Add a new object for the Level Sensors
    const levelSensors = namespace.addObject({
        organizedBy: addressSpace.rootFolder.objects,
        browseName: "LevelSensors"
    });

    // Add variables for Level Sensors
    const productLevel = namespace.addVariable({
        componentOf: levelSensors,
        browseName: "ProductLevel",
        dataType: "Double",
        value: new Variant({ dataType: DataType.Double, value: 0.0 }) // Initial value
    });

    

    await server.start();
    console.log("Server is now listening on port 4335");
    console.log(`Server endpoint url: ${server.endpoints[0].endpointDescriptions()[0].endpointUrl}`);

    await new Promise(resolve => setTimeout(resolve, 15000));

    // Simulate data changes
    setInterval(() => {
        // Update mixer speed and status
        mixerSpeed.setValueFromSource(new Variant({ dataType: DataType.Double, value: Math.round(Math.random() * 100 * 10) / 10 }));
        mixerStatus.setValueFromSource(new Variant({ dataType: DataType.String, value: Math.random() > 0.5 ? "Running" : "Stopped" }));

        // // Simulate temperature readings
        // const tempValue = Math.round((20 + 5 * Math.sin(Date.now() / 10000)) * 10) / 10;
        // temperature.setValueFromSource(new Variant({ dataType: DataType.Double, value: tempValue }));

        // // Simulate humidity readings
        // const humidityValue = Math.round((50 + 10 * Math.sin(Date.now() / 15000)) * 10) / 10;
        // humidity.setValueFromSource(new Variant({ dataType: DataType.Double, value: humidityValue }));

        // Simulate product level readings
        const levelValue = Math.round(100 * Math.random() * 10) / 10;
        productLevel.setValueFromSource(new Variant({ dataType: DataType.Double, value: levelValue }));
    }, 1000);
}

startServer().catch((err) => {
    console.log("Server failed to start... ", err);
});
