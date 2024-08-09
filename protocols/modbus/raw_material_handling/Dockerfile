FROM python:3.9-slim

WORKDIR /app

COPY ./plc /app

RUN pip install --no-cache-dir -r requirements.txt

COPY entrypoint.sh /app/entrypoint.sh

RUN chmod +x /app/entrypoint.sh

EXPOSE 502

CMD ["/app/entrypoint.sh"]