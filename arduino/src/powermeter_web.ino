
#include <SPI.h>
#include <Ethernet.h>
#include <EthernetClient.h>
#include <SD.h>
#include <DHT22.h>
#include <HttpClient.h>

// Enter a MAC address and IP address for your controller below.
// The IP  address will be dependent on your local network:
byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };
byte ip[] = { 192,168,25, 15 };
//byte gateway[] = { 192,168,25, 1 };
//byte subnet[] = { 255, 255, 255, 0 };

// Initialize the Ethernet server library
EthernetServer server(80);

String currentParams = "";

unsigned long currentMillis = 0; 
long previousMillis = 0;
int lightSensorArray[] = {0,0,0,0,0,0};

File myFile;

volatile unsigned long power_1_pulse = 0;
volatile unsigned long power_1_pulse_old = 0;
volatile unsigned long power_2_pulse = 0;
volatile unsigned long power_2_pulse_old = 0;           
volatile boolean CS_KWH = false;              // Critical Section for KWH meter (private)

int power_1 = 0;
int power_2 = 0;
int control_led = 13;

DHT22 myDHT22_1(5);
DHT22 myDHT22_2(6);
const int lightSensorPin = A0;

boolean lastConnected = false;  
EthernetClient client;
  
void setup(){
  Serial.begin(9600);
  if (!SD.begin(4)) {
    Serial.println("initialization failed!");
    return;
  }
  Serial.println("initialization done.");  
  // start the Ethernet connection and the server:
  Ethernet.begin(mac, ip);
  Serial.print("My IP address: ");
  Serial.println(Ethernet.localIP());
   
  //server.begin();

  // KWH interrupt attached to IRQ 0  = pin2
  attachInterrupt(1, Kirq_1, RISING);
  attachInterrupt(0, Kirq_2, RISING);
  pinMode(control_led, OUTPUT); 
}

void loop(){

  //EthernetClient c;
  //HttpClient http(c);

  char temperature_1[10]="0";
  char temperature_2[10]="0";
  char humidity_1[10]="0";
  char humidity_2[10]="0";
  int lightSensorValue = 0;
  
  DHT22_ERROR_t statusCode;

  String dataString = "";

  currentMillis = millis();

  // if there's incoming data from the net connection.
  // send it out the serial port.  This is for debugging
  // purposes only:
  if (client.available()) {
    char cl = client.read();
    //Serial.print(cl);
    //client.stop();

  }

  // if there's no net connection, but there was one last time
  // through the loop, then stop the client:
  if (!client.connected() && lastConnected) {
    //Serial.println();
    //Serial.println("disconnecting.");
    client.stop();
  }
    
  if(previousMillis == 0 || currentMillis - previousMillis > 60000) {
    if(previousMillis == 0 ){
      /* warmUp sensors */
      delay(2000);
    }
    previousMillis = currentMillis;
    
    statusCode = myDHT22_1.readData();
    if(statusCode == DHT_ERROR_NONE){
      dtostrf(myDHT22_1.getTemperatureC(),1,2,temperature_1);
      dtostrf(myDHT22_1.getHumidity(),1,2,humidity_1);
    }else {
      Serial.print("DHT1_ERROR: ");
      Serial.println(getDHT22StatusMsg(statusCode));
    }

    statusCode = myDHT22_2.readData();
    if(statusCode == DHT_ERROR_NONE){
      dtostrf(myDHT22_2.getTemperatureC(),1,2,temperature_2);
      dtostrf(myDHT22_2.getHumidity(),1,2,humidity_2);
    }else {
      Serial.print("DHT2_ERROR: ");
      Serial.println(getDHT22StatusMsg(statusCode));  
    }
    lightSensorValue = getLightData();

    power_1 = readKWH(1);
    power_2 = readKWH(2);

    currentParams = "p1=";
    currentParams += power_1;
    currentParams += "&p2=";
    currentParams += power_2;
    currentParams += "&t1=";
    currentParams += temperature_1;
    currentParams += "&h1=";
    currentParams += humidity_1;
    currentParams += "&t2=";
    currentParams += temperature_2;
    currentParams += "&h2=";
    currentParams += humidity_2;
    currentParams += "&light=";
    currentParams += lightSensorValue;
    
    if(!client.connected()) {
      httpRequest(currentParams);
    }
  }
  
  //webServer(currentParams);

  // store the state of the connection for next time through
  // the loop:
  lastConnected = client.connected();   

}

void httpRequest(String requestParams) {
  // if there's a successful connection:

  if (client.connect("emon.darcoto.net", 80)) {
    //Serial.println("connecting...");
    // send the HTTP PUT request:

    String getUrl = "GET /input.php?";
    getUrl += requestParams;
    getUrl += " HTTP/1.1";
    
    Serial.println(getUrl);
    char kPath[getUrl.length()+1];
    
    getUrl.toCharArray(kPath,getUrl.length()+1); 
    
    client.println(kPath);
    client.println("Host: emon.darcoto.net");
    client.println("User-Agent: arduino-emon");
    client.println("Connection: close");
    client.println();

  }else {
    // if you couldn't make a connection:
    Serial.println("connection failed");
    Serial.println("disconnecting.");
    client.stop();
  }
}

//------------------------------------------------------------------
// The interrupt routine
void Kirq_1()
{
  power_1_pulse++;                    // just increment raw pulse counter.
  if (power_1_pulse > 1000000000)     // reset pulse counter after 10e9 pulse = 500.000 KW 
  {
    if (false == CS_KWH)    // in critical section?  // assumption IRQ-call is handled atomic on arduino.
    {
      power_1_pulse -= power_1_pulse_old;
      power_1_pulse_old = 0;
    }
  }
}

//------------------------------------------------------------------
// The interrupt routine
void Kirq_2()
{
  power_2_pulse++;                    // just increment raw pulse counter.
  if (power_2_pulse > 1000000000)     // reset pulse counter after 10e9 pulse = 500.000 KW 
  {
    if (false == CS_KWH)    // in critical section?  // assumption IRQ-call is handled atomic on arduino.
    {
      power_2_pulse -= power_2_pulse_old;
      power_2_pulse_old = 0;
    }
  }
}

//------------------------------------------------------------------
// returns KWH's since last call
int readKWH(int power_mode)
{
  CS_KWH = true;           // Start Critical Section - prevent interrupt Kirq() from changing rpk & rpk_old ;
  long t;
  long k;
  if(power_mode == 1){
    t = power_1_pulse;            // store the raw pulse counter in a temp var.
    k = t - power_1_pulse_old;    // subtract last measure to get delta
    power_1_pulse_old = t;             // remember old value
  }else{
    t = power_2_pulse;            // store the raw pulse counter in a temp var.
    k = t - power_2_pulse_old;    // subtract last measure to get delta
    power_2_pulse_old = t;             // remember old value
  }
  CS_KWH = false;          // End Critical Section
  return k;         // return delta, one pulse = 0.5 watt.
}

//------------------------------------------------------------------
String getDHT22StatusMsg(DHT22_ERROR_t statusCode)
{ 
  String dataString;

    switch(statusCode){
      case DHT_ERROR_NONE:
        dataString = "OK";
        break;
      case DHT_ERROR_CHECKSUM:
        dataString = "ERR-check-sum-error";
        break;
      case DHT_BUS_HUNG:
        dataString = "ERR-BUS_Hung";
        break;
      case DHT_ERROR_NOT_PRESENT:
        dataString = "ERR-Not_Present";
        break;
      case DHT_ERROR_ACK_TOO_LONG:
        dataString = "ERR-ACK_time_out";
        break;
      case DHT_ERROR_SYNC_TIMEOUT:
        dataString = "ERR-Sync_Timeout";
        break;
      case DHT_ERROR_DATA_TIMEOUT:
        dataString = "ERR-Data_Timeout";
        break;
      case DHT_ERROR_TOOQUICK:
        dataString = "ERR-Polled_to_quick";
        break;              
    }
    return dataString;
}

//-------------------------------------------------------------------------
int getLightData()
{
  int count;      
  int lightSensorValue = 0;
  
  for (count=0;count<5;count++) {
    lightSensorArray[count] = lightSensorArray[count+1];
    if(lightSensorArray[count] == 0){
      lightSensorArray[count] = analogRead(lightSensorPin);
    }
  }
    
  lightSensorArray[5] = analogRead(lightSensorPin);
 
  for (count=0;count<6;count++) {
    lightSensorValue += lightSensorArray[count];
  }
  return lightSensorValue / 6;
}


//-----------------------------------------------------------

void webServer(String dataString)
{
  //http://arduino.cc/forum/index.php/topic,51138.0.html
  char clientline[128];
  char *filename;
  int index = 0;
  char rootFileName[] = "index.htm";

  // listen for incoming clients
  EthernetClient client = server.available();
  if (client) {
    // an http request ends with a blank line
    boolean currentLineIsBlank = true;
    index = 0;
    while (client.connected()) {
      if (client.available()) {
        char c = client.read();
        
        
        if (c == '\n' && currentLineIsBlank) {
          // send a standard http response header
          client.println("HTTP/1.1 200 OK");
          client.println("Content-Type: application/json");
          client.println("X-Server: Arduino 0.22");
          client.println();
          client.println(dataString);
          break;
        }
        if (c == '\n') {
          // you're starting a new line
          currentLineIsBlank = true;
        } 
        else if (c != '\r') {
          // you've gotten a character on the current line
          currentLineIsBlank = false;
          clientline[index] = c;
          index++;  
        }
      }
    }
    // give the web browser time to receive the data
    delay(1);
    // close the connection:
    client.stop();
  }
}

//-------------------------------------------------------------------------
void saveArchive(String dataString)
{
  File dataFile = SD.open("temp.txt", FILE_WRITE);
  if (dataFile) {
    dataFile.println(dataString);
    dataFile.close();
    Serial.println(dataString);
    
  }else{
    Serial.println("error opening temperature.txt");
  }         
    
}

//-------------------------------------------------------------------------
void getArchive()
{
  myFile = SD.open("temp.txt");
  if (myFile) {
    while (myFile.available()) {
      //client.write(myFile.read());
    }
    myFile.close();
  } else {
    Serial.println("error opening temperature.txt");
  }
}
