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
      resourcePath: "/UA/mixing_and_blending", // the path of the server on the endpoint
      buildInfo: {
          productName: "PharmaTechServer189",
          buildNumber: "7658",
          buildDate: new Date(2024, 5, 2)
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

  // Add a new object for the Temperature Sensors
  const temperatureSensors = namespace.addObject({
      organizedBy: addressSpace.rootFolder.objects,
      browseName: "TemperatureSensors"
  });

  // Add variables for Temperature Sensors
  const temperature = namespace.addVariable({
      componentOf: temperatureSensors,
      browseName: "Temperature",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 20.0 }) // Initial value
  });

  const temperatureSetpoint = namespace.addVariable({
      componentOf: temperatureSensors,
      browseName: "TemperatureSetpoint",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 25.0 }) // Initial setpoint
  });

  // Add a new object for the Pressure Sensors
  const pressureSensors = namespace.addObject({
      organizedBy: addressSpace.rootFolder.objects,
      browseName: "PressureSensors"
  });

  // Add variables for Pressure Sensors
  const pressure = namespace.addVariable({
      componentOf: pressureSensors,
      browseName: "Pressure",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 40.0 }) // Initial value
  });

  // Add a new object for the Flow Meters
  const flowMeters = namespace.addObject({
      organizedBy: addressSpace.rootFolder.objects,
      browseName: "FlowMeters"
  });

  // Add variables for Flow Meters
  const flowRate = namespace.addVariable({
      componentOf: flowMeters,
      browseName: "FlowRate",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 0.0 }) // Initial value
  });

  const ingredientQuantity = namespace.addVariable({
      componentOf: flowMeters,
      browseName: "IngredientQuantity",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 0.0 }) // Initial value
  });

  await server.start();

  // sleep for 20 seconds
  await new Promise((resolve) => setTimeout(resolve, 20000));

  // Simulate data changes
  setInterval(() => {
      // Update mixer speed and status
    mixerSpeed.setValueFromSource(new Variant({ dataType: DataType.Double, value: Math.round(Math.random() * 100 * 10) / 10 }));
      mixerStatus.setValueFromSource(new Variant({ dataType: DataType.String, value: Math.random() > 0.5 ? "Running" : "Stopped" }));

      // Simulate temperature readings
    const tempValue = Math.round((20 + 10 * Math.sin(Date.now() / 10000)) * 10) / 10;
      temperature.setValueFromSource(new Variant({ dataType: DataType.Double, value: tempValue }));

      // Simulate pressure readings
    // const pressureValue = Math.round((1 + Math.random()) * 10) / 10;
    //   pressure.setValueFromSource(new Variant({ dataType: DataType.Double, value: pressureValue }));

      // Simulate flow rate and ingredient quantities
    const flowValue = Math.round(Math.random() * 10 * 10) / 10;
      flowRate.setValueFromSource(new Variant({ dataType: DataType.Double, value: flowValue }));
      ingredientQuantity.setValueFromSource(new Variant({ dataType: DataType.Double, value: ingredientQuantity.readValue().value.value + flowValue }));
  }, 1000);

  
  console.log("Server is now listening on port 4334");
  console.log(`Server endpoint url: ${server.endpoints[0].endpointDescriptions()[0].endpointUrl}`);
}

startServer().catch((err) => {
  console.log("Server failed to start... ", err);
});
