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
  
}
