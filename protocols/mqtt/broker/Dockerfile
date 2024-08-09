FROM eclipse-mosquitto:1.4.12

# Copy custom configuration files into the container
COPY mosquitto.conf /mosquitto/config/mosquitto.conf

# Expose the MQTT port
EXPOSE 1883

# Run the Mosquitto broker
CMD ["mosquitto", "-c", "/mosquitto/config/mosquitto.conf"]