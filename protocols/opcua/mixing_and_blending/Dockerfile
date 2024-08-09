# official Ubuntu as a parent image
FROM ubuntu:20.04

# non-interactive mode for apt-get
ENV DEBIAN_FRONTEND=noninteractive

# Update the package repository and install prerequisites
RUN apt-get update && \
    apt-get install -y software-properties-common curl gnupg && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Python 3.9
RUN add-apt-repository ppa:deadsnakes/ppa && \
    apt-get update && \
    apt-get install -y python3.9 python3.9-venv python3.9-dev python3-pip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Set Python 3.9 as the default python and pip
RUN update-alternatives --install /usr/bin/python python /usr/bin/python3.9 1 && \
    update-alternatives --install /usr/bin/pip pip /usr/bin/pip3 1

# Verify installations
RUN python --version && node --version && npm --version

# Set the working directory
WORKDIR /app

COPY server/* ./

# Install dependencies
RUN npm install

# Copy the rest of project files into this image
COPY . .

RUN chmod +x /app/server/entrypoint.sh

# Expose application port
EXPOSE 26543

# CMD npm start
CMD ["/app/server/entrypoint.sh"]