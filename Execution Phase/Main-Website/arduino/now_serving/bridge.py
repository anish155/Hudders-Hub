import requests
import serial
import time
import sys

# Config
APEX_URL = "http://localhost:8080/ords/huddershub/api/now-serving"
SERIAL_PORT = "COM6"        # Windows: COM3, COM4 etc.
BAUD_RATE = 9600
POLL_INTERVAL = 2           # seconds (slightly faster polling)

last_id = -1

print("--- HuddersHub Now Serving Bridge ---")
print(f"Connecting to Arduino on {SERIAL_PORT}...")

try:
    ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
    time.sleep(2)  # Wait for Arduino to boot
    print("Connected to Arduino.")
except Exception as e:
    print(f"CRITICAL ERROR: Could not open serial port {SERIAL_PORT}. Is the Arduino plugged in?")
    print(f"Details: {e}")
    sys.exit(1)

def fetch_now_serving():
    try:
        res = requests.get(APEX_URL, timeout=5)
        res.raise_for_status()
        data = res.json()
        print(f"DEBUG: Received from APEX: {data}") # Log raw data
        
        # Handle both list and single object responses from ORDS
        current_id = None
        if "items" in data:
            items = data["items"]
            if items:
                current_id = items[0].get("order_id") or items[0].get("queue_position")
        else:
            current_id = data.get("order_id") or data.get("queue_position")
            
        return int(current_id) if current_id is not None else -1
    except Exception as e:
        print(f"DEBUG: API Error: {e}") # Log error details
        return -1

print(f"Polling APEX at {APEX_URL}...")
print("Waiting for 'Called' orders...")

try:
    while True:
        current = fetch_now_serving()
        
        if current != -1 and current != last_id:
            print(f"NEW ORDER CALLED: {current}")
            # Send to Arduino (e.g., "1234\n")
            ser.write(f"{current}\n".encode())
            last_id = current
        
        time.sleep(POLL_INTERVAL)
except KeyboardInterrupt:
    print("\nBridge stopped by user.")
finally:
    if 'ser' in locals() and ser.is_open:
        ser.close()