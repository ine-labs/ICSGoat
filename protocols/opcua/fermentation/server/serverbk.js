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
      resourcePath: "/UA/FermentationServer",
      buildInfo: {
          productName: "FermentationServer",
          buildNumber: "1",
          buildDate: new Date()
      }
  });

  await server.initialize();

  const addressSpace = server.engine.addressSpace;
  const namespace = addressSpace.getOwnNamespace();

  // Add a new object for the Bioreactors
  const bioreactors = namespace.addObject({
      organizedBy: addressSpace.rootFolder.objects,
      browseName: "Bioreactors"
  });

  // Add variables for Bioreactor
  const bioreactorTemperature = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "BioreactorTemperature",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 37.0 }) // Initial value
  });

  const bioreactorPH = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "BioreactorPH",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 7.0 }) // Initial value
  });

  const bioreactorDissolvedOxygen = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "BioreactorDissolvedOxygen",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 20.0 }) // Initial value
  });

  const bioreactorLevel = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "BioreactorLevel",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 50.0 }) // Initial value
  });

  const bioreactorAgitationSpeed = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "BioreactorAgitationSpeed",
      dataType: "Double",
      value: new Variant({ dataType: DataType.Double, value: 100.0 }) // Initial value (RPM)
  });

  const bioreactorNutrientSupplyValve = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "NutrientSupplyValve",
      dataType: "Boolean",
      value: new Variant({ dataType: DataType.Boolean, value: false }) // Initial value (Closed)
  });

  const bioreactorWasteRemovalValve = namespace.addVariable({
      componentOf: bioreactors,
      browseName: "WasteRemovalValve",
      dataType: "Boolean",
      value: new Variant({ dataType: DataType.Boolean, value: false }) // Initial value (Closed)
  });

  // Simulate data changes
  setInterval(() => {
      // Simulate bioreactor temperature readings
    const tempValue = Math.round((37 + 2 * Math.sin(Date.now() / 10000)) * 10) / 10;
    bioreactorTemperature.setValueFromSource(new Variant({ dataType: DataType.Double, value: tempValue }));

      // Simulate pH readings
    const phValue = Math.round((7 + 0.5 * Math.sin(Date.now() / 15000)) * 10) / 10;
    bioreactorPH.setValueFromSource(new Variant({ dataType: DataType.Double, value: phValue }));

      // Simulate dissolved oxygen readings
    const oxygenValue = Math.round((20 + 5 * Math.sin(Date.now() / 20000)) * 10) / 10;
    bioreactorDissolvedOxygen.setValueFromSource(new Variant({ dataType: DataType.Double, value: oxygenValue }));

      // Simulate bioreactor level readings
    const levelValue = Math.round((50 + 10 * Math.sin(Date.now() / 30000)) * 10) / 10;
    bioreactorLevel.setValueFromSource(new Variant({ dataType: DataType.Double, value: levelValue }));

      // Simulate bioreactor agitation speed
    const agitationSpeedValue = Math.round((100 + 20 * Math.sin(Date.now() / 25000)) * 10) / 10;
    bioreactorAgitationSpeed.setValueFromSource(new Variant({ dataType: DataType.Double, value: agitationSpeedValue }));

      // Simulate valve states
      const nutrientValveState = Math.random() > 0.5;
      const wasteValveState = Math.random() > 0.5;
      bioreactorNutrientSupplyValve.setValueFromSource(new Variant({ dataType: DataType.Boolean, value: nutrientValveState }));
      bioreactorWasteRemovalValve.setValueFromSource(new Variant({ dataType: DataType.Boolean, value: wasteValveState }));

  }, 1000);

  await server.start();
  console.log("Server is now listening on port 4334");
  console.log(`Server endpoint url: ${server.endpoints[0].endpointDescriptions()[0].endpointUrl}`);
}

startServer().catch((err) => {
  console.log("Server failed to start... ", err);
});
