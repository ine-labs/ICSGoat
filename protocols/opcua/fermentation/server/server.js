const { OPCUAServer, Variant, DataType, StatusCodes } = require("node-opcua");
const os = require("os");
const https = require("http");
const hostname = os.hostname();

process.title = "opcua-server";

function fetchData(url) {
  return new Promise((resolve, reject) => {
    https
      .get(url, (res) => {
        let data = "";

        // A chunk of data has been received.
        res.on("data", (chunk) => {
          data += chunk;
        });

        // The whole response has been received.
        res.on("end", () => {
          if (res.statusCode === 200) {
            try {
              const jsonData = JSON.parse(data);
              resolve(jsonData);
            } catch (error) {
              reject(`Error parsing JSON: ${error}`);
            }
          } else {
            reject(`HTTP error! status: ${res.statusCode}`);
          }
        });
      })
      .on("error", (err) => {
        reject(`Error fetching data: ${err.message}`);
      });
  });
}

async function startServer() {
  const server = new OPCUAServer({
    port: 26543, // the port of the listening socket of the server
    resourcePath: "/UA/FermentationServer",
    buildInfo: {
      productName: "FermentationServer",
      buildNumber: "1",
      buildDate: new Date(),
    },
  });

  await server.initialize();

  const addressSpace = server.engine.addressSpace;
  const namespace = addressSpace.getOwnNamespace();

  // Add a new object for the Bioreactors
  const bioreactors = namespace.addObject({
    organizedBy: addressSpace.rootFolder.objects,
    browseName: "Bioreactors",
  });

  // Add variables for Bioreactor
  const bioreactorTemperature = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "BioreactorTemperature",
    dataType: "Double",
    value: new Variant({ dataType: DataType.Double, value: 0.0 }), // Initial value
    accessLevel: "CurrentRead | CurrentWrite",
    userAccessLevel: "CurrentRead | CurrentWrite",
  });

  const bioreactorPH = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "BioreactorPH",
    dataType: "Double",
    value: new Variant({ dataType: DataType.Double, value: 0.0 }), // Initial value
    accessLevel: "CurrentRead | CurrentWrite",
    userAccessLevel: "CurrentRead | CurrentWrite",
  });

  const bioreactorDissolvedOxygen = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "BioreactorDissolvedOxygen",
    dataType: "Double",
    value: new Variant({ dataType: DataType.Double, value: 0.0 }), // Initial value
    accessLevel: "CurrentRead | CurrentWrite",
    userAccessLevel: "CurrentRead | CurrentWrite",
  });

  const bioreactorLevel = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "BioreactorLevel",
    dataType: "Double",
    value: new Variant({ dataType: DataType.Double, value: 0.0 }), // Initial value
    accessLevel: "CurrentRead | CurrentWrite",
    userAccessLevel: "CurrentRead | CurrentWrite",
  });

  const bioreactorAgitationSpeed = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "BioreactorAgitationSpeed",
    dataType: "Double",
    value: new Variant({ dataType: DataType.Double, value: 0.0 }), // Initial value (RPM)
  });

  const bioreactorNutrientSupplyValve = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "NutrientSupplyValve",
    dataType: "Boolean",
    value: new Variant({ dataType: DataType.Boolean, value: false }), // Initial value (Closed)
  });

  const bioreactorWasteRemovalValve = namespace.addVariable({
    componentOf: bioreactors,
    browseName: "WasteRemovalValve",
    dataType: "Boolean",
    value: new Variant({ dataType: DataType.Boolean, value: false }), // Initial value (Closed)
    accessLevel: "CurrentRead | CurrentWrite",
    userAccessLevel: "CurrentRead | CurrentWrite",
  });

  // Simulate data changes
  setInterval(() => {
    // Simulate bioreactor temperature readings
    const url = "http://hmi.pharmatech.local:5000/data";

    fetchData(url)
      .then((data) => {
        let pressureValue = 0;
        let speed = 0;
        data.forEach((element) => {
          if (element.variable_name === "Pressure" && element.process_name === "mixing_and_blending") {
            pressureValue = parseFloat(element.value);
          }
          if (element.variable_name === "MixerSpeed" && element.process_name === "mixing_and_blending") {
            speed = parseFloat(element.value);
          }
        });

        // bioreactorNutrientSupplyValve.setValueFromSource(
        //     new Variant({ dataType: DataType.Boolean, value: true })
        //   );

        if (pressureValue > 50) {
            bioreactorNutrientSupplyValve.setValueFromSource(
                new Variant({ dataType: DataType.Boolean, value: true })
              );
        }
      })
      .catch((error) => {
        console.error(error);
      });

    // Get existing values
    const currentTemperature = bioreactorTemperature.readValue().value.value;
    const currentPH = bioreactorPH.readValue().value.value;
    const currentDissolvedOxygen = bioreactorDissolvedOxygen.readValue().value.value;
    const currentLevel = bioreactorLevel.readValue().value.value;
    const currentAgitationSpeed = bioreactorAgitationSpeed.readValue().value.value;
    const currentNutrientSupplyValve = bioreactorNutrientSupplyValve.readValue().value.value;
    const currentWasteRemovalValve = bioreactorWasteRemovalValve.readValue().value.value;

    if (currentNutrientSupplyValve) {

        // ideal ph - 7 to 8
        // ideal o2 > 10
        // ideal temp 30 - 45

        if (currentTemperature > 30 && currentTemperature < 45 && currentPH > 7 && currentPH < 8 && currentDissolvedOxygen > 10 && currentLevel == 42.0) {
          bioreactorWasteRemovalValve.setValueFromSource(
            new Variant({ dataType: DataType.Boolean, value: false })
          );
          // bioreactorLevel.setValueFromSource(
          //   new Variant({ dataType: DataType.Double, value: 42.0 })
          // );
      } else{
          bioreactorWasteRemovalValve.setValueFromSource(
              new Variant({ dataType: DataType.Boolean, value: true })
            );
            return;
      }

        if (currentWasteRemovalValve) {
            bioreactorLevel.setValueFromSource(
                new Variant({ dataType: DataType.Double, value: 0.0 })
              );
              return;
        }

        // const tempValue =
        //     Math.round((37 + 2 * Math.sin(Date.now() / 10000)) * 10) / 10;
        //   bioreactorTemperature.setValueFromSource(
        //     new Variant({ dataType: DataType.Double, value: tempValue })
        //   );
      
        //   // Simulate pH readings
        //   const phValue =
        //     Math.round((7 + 0.5 * Math.sin(Date.now() / 15000)) * 10) / 10;
        //   bioreactorPH.setValueFromSource(
        //     new Variant({ dataType: DataType.Double, value: phValue })
        //   );
      
        //   // Simulate dissolved oxygen readings
        //   const oxygenValue =
        //     Math.round((20 + 5 * Math.sin(Date.now() / 20000)) * 10) / 10;
        //   bioreactorDissolvedOxygen.setValueFromSource(
        //     new Variant({ dataType: DataType.Double, value: oxygenValue })
        //   );
      
        //   // Simulate bioreactor level readings
          // const levelValue =
          //   Math.round((50 + 10 * Math.sin(Date.now() / 30000)) * 10) / 10;
          // bioreactorLevel.setValueFromSource(
          //   new Variant({ dataType: DataType.Double, value: levelValue })
          // );
      
          // Simulate bioreactor agitation speed
          const agitationSpeedValue =
            Math.round((100 + 20 * Math.sin(Date.now() / 25000)) * 10) / 10;
          bioreactorAgitationSpeed.setValueFromSource(
            new Variant({ dataType: DataType.Double, value: agitationSpeedValue })
          );

        

       
  
      // Simulate valve states
      const nutrientValveState = Math.random() > 0.5;
      const wasteValveState = Math.random() > 0.5;
    //   bioreactorNutrientSupplyValve.setValueFromSource(
    //     new Variant({ dataType: DataType.Boolean, value: nutrientValveState })
    //   );
    //   bioreactorWasteRemovalValve.setValueFromSource(
    //     new Variant({ dataType: DataType.Boolean, value: false })
    //   );
    }

    
  }, 1000);

  await server.start();
  console.log("Server is now listening on port 4334");
  console.log(
    `Server endpoint url: ${
      server.endpoints[0].endpointDescriptions()[0].endpointUrl
    }`
  );
}

startServer().catch((err) => {
  console.log("Server failed to start... ", err);
});
