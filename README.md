# ICSGoat : A Damn Vulnerable ICS Infrastructure

![1](https://github.com/user-attachments/assets/9b297982-1521-4a0f-9567-24328b440ca7)

ICSGoat is a vulnerable by design ICS infrastructure.ICSGoat mimics real-world infrastructure but with added vulnerabilities. It features simulations of multiple popularly used ICS protocols. ICSGoat is focused on a black box approach and will help understand the possible threats to critical ICS Infrastructure.

ICSGoat uses docker compose to deploy the infrastructure on your local system. This gives the user complete control and customizability over the code and infrastructure setup. 
Currently ICSGoat features the following protocols:
- MODBUS
- DNP3
- OPCUA
- MQTT


**Presented at**

- [BlackHat ASIA 2024](https://www.blackhat.com/us-24/arsenal/schedule/index.html#icsgoat-a-damn-vulnerable-ics-infrastructure-39448)

### Developed with :heart: by [INE](https://ine.com/) 

[<img src="https://user-images.githubusercontent.com/25884689/184508144-f0196d79-5843-4ea6-ad39-0c14cd0da54c.png" alt="drawing" width="200"/>](https://discord.gg/TG7bpETgbg)

## Built With

* Python 3
* Docker


# Getting Started

### Prerequisites

* A Linux / Windows Machine
* Docker Compose


### Installation

Installing ICSGoat would require you to follow these steps:


**Step 1.** Clone the repo
```sh
git clone https://github.com/ine-labs/ICSGoat
```

**Step 2.** Navigate to the ICSGoat Directory
```sh
cd ICSGoat
```

**Step 3.** Start up the application stack
```sh
sudo docker-compose up
```

**Step 4.** Access the entrypoint to the infra at "172.16.2.20" on your browser

Infrastructure Diagram:

<p align="center">
  <img src="https://github.com/user-attachments/assets/f29e641e-e1bf-4bd4-8e35-7ccd4272f2f6">
</p>

Escalation Path:

<p align="center">
  <img src="https://github.com/user-attachments/assets/667e5e57-8272-4c29-bd08-a1549d09cd35">
</p>



# Contributors

Sherin Stephen, Software Engineer, INE <sstephen@ine.com>

Nishant Sharma, Director, Lab Platform, INE <nsharma@ine.com>

Shantanu Kale, Infrastructure Team Lead, INE  <skale@ine.com>

Divya Nain, Software Engineer, INE <dnain@ine.com> 



# Documentation

For more details refer to the "ICSGoat.pdf" PDF file. This file contains the slide deck used for presentations.


## Contribution Guidelines

* Contributions in the form of code improvements, feature improvements, and any general suggestions are welcome. 
* Improvements to the functionalities of the current components are also welcome. 

# License

This program is free software: you can redistribute it and/or modify it under the terms of the MIT License.

You should have received a copy of the MIT License along with this program. If not, see https://opensource.org/licenses/MIT.

# Sister Projects

- [AWSGoat](https://github.com/ine-labs/AWSGoat)
- [AzureGoat](https://github.com/ine-labs/AzureGoat)
- [GCPGoat](https://github.com/ine-labs/GCPGoat)
- [GearGoat](https://github.com/ine-labs/GearGoat)
- [PA Toolkit (Pentester Academy Wireshark Toolkit)](https://github.com/pentesteracademy/patoolkit)
- [ReconPal: Leveraging NLP for Infosec](https://github.com/pentesteracademy/reconpal) 
- [VoIPShark: Open Source VoIP Analysis Platform](https://github.com/pentesteracademy/voipshark)
- [BLEMystique](https://github.com/pentesteracademy/blemystique)
