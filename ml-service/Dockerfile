FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Copy dataset and train model at build time
COPY ../glpi-config/dataset_tichete.csv ./dataset_tichete.csv

RUN python train.py

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
