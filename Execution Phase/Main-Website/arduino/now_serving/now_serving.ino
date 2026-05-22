#include <TM1637Display.h>

#define CLK     2
#define DIO     3
#define BUZZER  8

TM1637Display display(CLK, DIO);

void buzz(int freq, int duration) {
  tone(BUZZER, freq, duration);
  delay(duration + 50);
}

void setup() {
  Serial.begin(9600);
  pinMode(BUZZER, OUTPUT);
  display.setBrightness(0x0f);
  display.showNumberDec(0, true);

  // Startup beeps
  buzz(1000, 200);
  delay(100);
  buzz(1500, 200);
  
  Serial.println("Arduino Ready - Waiting for Order IDs...");
}

void loop() {
  if (Serial.available() > 0) {
    String incoming = Serial.readStringUntil('\n');
    incoming.trim();
    
    if (incoming.length() > 0) {
      int num = incoming.toInt();
      
      // Update display with the Order ID
      display.showNumberDec(num, true);

      // Triple buzz for attention
      buzz(2000, 150);
      delay(100);
      buzz(2000, 150);
      delay(100);
      buzz(2000, 150);
    }
  }
}