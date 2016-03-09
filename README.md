# Greenhouse Control System

The greenhouse control system is designed to allow for automation and remote monitoring of a greenhouse. The system consists of two major components; the greenhouse based controller and the cloud based user interface and data management system.

## Control system

## Cloud System

## Roadmap
The goal is to move away from using a raspberry pi / arduino hybrid system and towards a system based on the esp8266 system. There are several reasons why this is more attractive. The current system and has a lot of moving parts and while the stability of the system is now very good, it requires cron jobs and init scripts along with finding wifi drivers. The hardware is also dificult to manage, currently the raspberry pi controls actuators and reads sensors via an arduino which can also be a configuration nightmare. While this allows one system to perform a host of tasks, it is suspected that this does not outweigh the drawbacks. Instead by moving to the esp8266 we would hope to see a swarm of devices each performing a single task or reduced set of tasks. This also removes the single point of failure the system currently has. The esp8266 nodes should also be cheaper than the current system.

### Milestones
1. Provide a RESTful interface along side the SQL based on.
_The SQL interface can only be provided for trusted clients and is not and ideal solution. Instead a RESTful system will allow and user with an account to connect._
2. Develope firmware for the ESP8266 to comunicate with the RESTful interface
