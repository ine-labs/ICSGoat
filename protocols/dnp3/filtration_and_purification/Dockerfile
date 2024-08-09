FROM python:3.9-slim

WORKDIR /app

RUN apt-get update && apt-get install -y git cmake g++ curl

RUN git clone --recursive http://github.com/Kisensum/pydnp3

RUN cd pydnp3 && python setup.py install

RUN rm /app/pydnp3/examples/outstation.py

COPY ./plc /app/pydnp3/examples

COPY entrypoint.sh /app/entrypoint.sh

RUN chmod +x /app/entrypoint.sh

EXPOSE 20000

RUN pip install requests

CMD ["/app/entrypoint.sh"]