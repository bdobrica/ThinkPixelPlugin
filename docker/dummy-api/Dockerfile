# Use an official Python runtime as a parent image
FROM python:3.9-slim

# Set the working directory
WORKDIR /app

# Copy the current directory contents into the container
COPY . /app

# Install any needed packages specified in requirements.txt
RUN pip install --no-cache-dir fastapi uvicorn rich

# Make port 8080 available to the world outside this container
EXPOSE 8080

# Define environment variable
ENV PYTHONUNBUFFERED=1

# Run app.py when the container launches
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8080", "--reload"]
