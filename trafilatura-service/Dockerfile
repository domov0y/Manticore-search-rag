FROM mirror.gcr.io/library/python:3.10-slim

WORKDIR /app

ENV PYTHONUNBUFFERED=1

# Сначала копируем только зависимости — так Docker лучше кэширует слои
COPY requirements.txt .

RUN pip install \
    --no-cache-dir \
    --retries 10 \
    --timeout 120 \
    -r requirements.txt


# Потом копируем сам код
COPY main-1.py main.py

EXPOSE 5000

CMD ["python", "-u", "main.py"]
