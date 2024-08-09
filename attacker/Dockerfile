FROM kalilinux/kali-rolling

RUN apt-get update -y

RUN apt-get install -y metasploit-framework nmap proxychains-ng supervisor

RUN apt-get install -y net-tools

# Install dependencies and Python 3.9
RUN apt-get update && \
    apt-get install -y \
    wget \
    build-essential \
    libssl-dev \
    zlib1g-dev \
    libbz2-dev \
    libreadline-dev \
    libsqlite3-dev \
    libffi-dev \
    libncursesw5-dev \
    xz-utils \
    tk-dev \
    libxml2-dev \
    libxmlsec1-dev \
    liblzma-dev && \
    wget https://www.python.org/ftp/python/3.9.13/Python-3.9.13.tgz && \
    tar -xf Python-3.9.13.tgz && \
    cd Python-3.9.13 && \
    ./configure --enable-optimizations && \
    make -j $(nproc) && \
    make altinstall && \
    cd .. && \
    rm -rf Python-3.9.13 Python-3.9.13.tgz && \
    ln -s /usr/local/bin/python3.9 /usr/bin/python3.9 && \
    python3.9 -m ensurepip && \
    python3.9 -m pip install --upgrade pip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Ensure supervisor is installed using pip as well
RUN python3.9 -m pip install supervisor

# Set Python 3.9 as default
RUN update-alternatives --install /usr/bin/python3 python3 /usr/bin/python3.9 1

COPY requirements.txt /tmp/requirements.txt

RUN pip install --no-cache-dir -r /tmp/requirements.txt

RUN apt-get update && apt-get install -y iproute2 nano

# Create a supervisor configuration file
RUN mkdir -p /etc/supervisor/conf.d
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create a simple script to keep the container running
RUN echo -e '#!/bin/bash\nwhile true; do sleep 1000; done' > /keep_alive.sh \
    && chmod +x /keep_alive.sh

# Set the command to run supervisord
CMD ["/usr/local/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]