#include <WiFi.h>
#include <esp_now.h>

// ==================================================
// ULTRASONIK
// ==================================================

#define TRIG1_PIN 13
#define ECHO1_PIN 34

#define TRIG2_PIN 14
#define ECHO2_PIN 35

#define TRIG3_PIN 26
#define ECHO3_PIN 32

#define TRIG4_PIN 27
#define ECHO4_PIN 33

// ==================================================
// OBSTACLE
// ==================================================

#define OBSTACLE1_PIN 21
#define OBSTACLE2_PIN 12

// ==================================================
// MAC ESP32 #1
// ==================================================

uint8_t receiverMAC[] = {
  0x68, 0x09, 0x47, 0x48, 0x6C, 0x40
};

// ==================================================
// DATA YANG DIKIRIM
// ==================================================

typedef struct {

  int jarak1;
  int jarak2;
  int jarak3;
  int jarak4;

  int obstacle1;
  int obstacle2;

} SensorData;

SensorData data;

// ==================================================
// BACA ULTRASONIK
// ==================================================

long bacaUltrasonik(int trigPin, int echoPin) {

  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);

  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duration = pulseIn(
    echoPin,
    HIGH,
    30000
  );

  if (duration == 0) {
    return -1;
  }

  long distance = duration * 0.0343 / 2;

  return distance;
}

// ==================================================
// CALLBACK PENGIRIMAN
// ==================================================

void OnDataSent(
  const wifi_tx_info_t *info,
  esp_now_send_status_t status
) {

  if (status == ESP_NOW_SEND_SUCCESS) {
    Serial.println("ESP-NOW: TERKIRIM");
  } else {
    Serial.println("ESP-NOW: GAGAL");
  }
}

// ==================================================
// SETUP
// ==================================================

void setup() {

  Serial.begin(115200);

  delay(2000);

  Serial.println();
  Serial.println("==============================");
  Serial.println(" ESP32 #2 - SENSOR SENDER");
  Serial.println("==============================");

  // ------------------------------------------------
  // ULTRASONIK
  // ------------------------------------------------

  pinMode(TRIG1_PIN, OUTPUT);
  pinMode(ECHO1_PIN, INPUT);

  pinMode(TRIG2_PIN, OUTPUT);
  pinMode(ECHO2_PIN, INPUT);

  pinMode(TRIG3_PIN, OUTPUT);
  pinMode(ECHO3_PIN, INPUT);

  pinMode(TRIG4_PIN, OUTPUT);
  pinMode(ECHO4_PIN, INPUT);

  digitalWrite(TRIG1_PIN, LOW);
  digitalWrite(TRIG2_PIN, LOW);
  digitalWrite(TRIG3_PIN, LOW);
  digitalWrite(TRIG4_PIN, LOW);

  // ------------------------------------------------
  // OBSTACLE
  // ------------------------------------------------

  pinMode(OBSTACLE1_PIN, INPUT);
  pinMode(OBSTACLE2_PIN, INPUT);

  // ------------------------------------------------
  // WIFI
  // ------------------------------------------------

  WiFi.mode(WIFI_STA);

  Serial.print("MAC ESP32 #2: ");
  Serial.println(WiFi.macAddress());

  // ------------------------------------------------
  // ESP-NOW
  // ------------------------------------------------

  if (esp_now_init() != ESP_OK) {

    Serial.println("ESP-NOW GAGAL!");

    return;
  }

  esp_now_register_send_cb(OnDataSent);

  // ------------------------------------------------
  // TAMBAHKAN ESP32 #1
  // ------------------------------------------------

  esp_now_peer_info_t peerInfo = {};

  memcpy(
    peerInfo.peer_addr,
    receiverMAC,
    6
  );

  peerInfo.channel = 0;
  peerInfo.encrypt = false;

  if (esp_now_add_peer(&peerInfo) != ESP_OK) {

    Serial.println("GAGAL MENAMBAHKAN PEER!");

    return;
  }

  Serial.println("ESP-NOW BERHASIL");
  Serial.println("4 Ultrasonik aktif");
  Serial.println("2 Obstacle aktif");
  Serial.println("Mulai mengirim data...");
}

// ==================================================
// LOOP
// ==================================================

void loop() {

  // ------------------------------------------------
  // SENSOR 1
  // ------------------------------------------------

  data.jarak1 =
    bacaUltrasonik(
      TRIG1_PIN,
      ECHO1_PIN
    );

  delay(60);

  // ------------------------------------------------
  // SENSOR 2
  // ------------------------------------------------

  data.jarak2 =
    bacaUltrasonik(
      TRIG2_PIN,
      ECHO2_PIN
    );

  delay(60);

  // ------------------------------------------------
  // SENSOR 3
  // ------------------------------------------------

  data.jarak3 =
    bacaUltrasonik(
      TRIG3_PIN,
      ECHO3_PIN
    );

  delay(60);

  // ------------------------------------------------
  // SENSOR 4
  // ------------------------------------------------

  data.jarak4 =
    bacaUltrasonik(
      TRIG4_PIN,
      ECHO4_PIN
    );

  delay(20);

  // ------------------------------------------------
  // OBSTACLE
  // ------------------------------------------------

  data.obstacle1 =
    digitalRead(OBSTACLE1_PIN);

  data.obstacle2 =
    digitalRead(OBSTACLE2_PIN);

  // ------------------------------------------------
  // SERIAL MONITOR
  // ------------------------------------------------

  Serial.println();
  Serial.println("========== SENSOR ==========");

  Serial.print("US1 : ");
  Serial.print(data.jarak1);
  Serial.println(" cm");

  Serial.print("US2 : ");
  Serial.print(data.jarak2);
  Serial.println(" cm");

  Serial.print("US3 : ");
  Serial.print(data.jarak3);
  Serial.println(" cm");

  Serial.print("US4 : ");
  Serial.print(data.jarak4);
  Serial.println(" cm");

  Serial.print("OB1 : ");

  if (data.obstacle1 == LOW) {
    Serial.println("ADA");
  } else {
    Serial.println("KOSONG");
  }

  Serial.print("OB2 : ");

  if (data.obstacle2 == LOW) {
    Serial.println("ADA");
  } else {
    Serial.println("KOSONG");
  }

  // ------------------------------------------------
  // KIRIM KE ESP32 #1
  // ------------------------------------------------

  esp_now_send(
    receiverMAC,
    (uint8_t *)&data,
    sizeof(data)
  );

  delay(100);
}