#include <dht.h>

#define SENSOR_A_PIN 2
#define SENSOR_B_PIN 3
#define SENSOR_C_PIN 4
#define SENSOR_D_PIN 5

dht DHT;
int interval = 5000;
unsigned long previous_millis = 0;

void report_dht(int pin)
{
  int chk = DHT.read22(pin);
  Serial.print(pin);
  Serial.print(',');
  switch (chk)
    {
    case DHTLIB_OK:
        Serial.print("OK");
        break;
    case DHTLIB_ERROR_CHECKSUM:
        Serial.print("Checksum error");
        break;
    case DHTLIB_ERROR_TIMEOUT:
        Serial.print("Time out error");
        break;
    case DHTLIB_ERROR_CONNECT:
        Serial.print("Connect error");
        break;
    case DHTLIB_ERROR_ACK_L:
        Serial.print("Ack Low error");
        break;
    case DHTLIB_ERROR_ACK_H:
        Serial.print("Ack High error");
        break;
    default:
        Serial.print("Unknown error");
        break;
    }
    Serial.print(',');
    Serial.print(DHT.humidity, 1);
    Serial.print(',');
    Serial.print(DHT.temperature, 1);
    Serial.print("\n");
}

void setup()
{
  Serial.begin(9600);
  pinMode(13, OUTPUT);
  pinMode(12, OUTPUT);
  pinMode(11, OUTPUT);
  pinMode(10, OUTPUT);
  pinMode(9, OUTPUT);
  pinMode(8, OUTPUT);
  pinMode(7, OUTPUT);
  pinMode(6, OUTPUT);
  
  digitalWrite(13, HIGH);
  digitalWrite(12, HIGH);
  digitalWrite(11, HIGH);
  digitalWrite(10, HIGH);
  digitalWrite(9, HIGH);
  digitalWrite(8, HIGH);
  digitalWrite(7, HIGH);
  digitalWrite(6, HIGH);
}

void loop()
{
  byte control;
  byte pin;

  if (Serial.available())
  {
   control = Serial.read();
   pin = control & 15;
   if (pin <= 13 && pin >= 6) {
     if (control & 16)
     {
      digitalWrite(pin, HIGH); 
     } else {
      digitalWrite(pin, LOW);
     }
    }
  }
  
  unsigned long current_millis = millis();
  if ((unsigned long)(current_millis - previous_millis) > interval)
  {
    previous_millis = current_millis;

    report_dht(SENSOR_A_PIN);
    report_dht(SENSOR_B_PIN);
    report_dht(SENSOR_C_PIN);
    report_dht(SENSOR_D_PIN);
  }
}
