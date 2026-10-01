/*
 * ============================================================
 * SOTO — ESP32 #2 SENSOR SENDER — VERSI UART
 * ============================================================
 * Kirim data sensor ke ESP32 #1 via UART2.
 *
 * KABEL KE ESP32 #1:
 *   ESP32 #2 TX (GPIO17) ──[100Ω opsional]──► ESP32 #1 RX2 (GPIO16)
 *   ESP32 #2 GND         ───────────────────── ESP32 #1 GND   ← WAJIB!
 *
 * Format data: D,jarak1,jarak2,jarak3,jarak4,ob1,ob2\n
 * Baudrate: 115200
 *
 * PIN YANG DIPAKAI:
 *   Ultrasonik 1 : TRIG=13, ECHO=34
 *   Ultrasonik 2 : TRIG=14, ECHO=35
 *   Ultrasonik 3 : TRIG=26, ECHO=32
 *   Ultrasonik 4 : TRIG=27, ECHO=33
 *   Obstacle 1   : GPIO21
 *   Obstacle 2   : GPIO4   ← (dari GPIO12 karena strapping)
 *   UART TX      : GPIO17
 *   UART RX      : GPIO16
 * ============================================================
 */

#include <Arduino.h>

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
// OBSTACLE — PIN 12 SUDAH DIPINDAH KE 4 (strapping!)
// ==================================================
#define OBSTACLE1_PIN 21
#define OBSTACLE2_PIN 4

// ==================================================
// UART KE ESP32 #1
// ==================================================
#define SENSOR_TX 17
#define SENSOR_RX 16

HardwareSerial sensorSerial(2);   // UART2

// ==================================================
// BACA ULTRASONIK
// ==================================================
long bacaUltrasonik(int trigPin, int echoPin) {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duration = pulseIn(echoPin, HIGH, 30000);
  if (duration == 0) return -1;

  return duration * 0.0343 / 2;
}

// ==================================================
// SETUP
// ==================================================
void setup() {
  Serial.begin(115200);   // Serial Monitor USB
  delay(2000);

  Serial.println();
  Serial.println("==============================");
  Serial.println(" ESP32 #2 - SENSOR SENDER");
  Serial.println("==============================");

  // --- Pin Ultrasonik ---
  pinMode(TRIG1_PIN, OUTPUT); pinMode(ECHO1_PIN, INPUT);
  pinMode(TRIG2_PIN, OUTPUT); pinMode(ECHO2_PIN, INPUT);
  pinMode(TRIG3_PIN, OUTPUT); pinMode(ECHO3_PIN, INPUT);
  pinMode(TRIG4_PIN, OUTPUT); pinMode(ECHO4_PIN, INPUT);

  digitalWrite(TRIG1_PIN, LOW);
  digitalWrite(TRIG2_PIN, LOW);
  digitalWrite(TRIG3_PIN, LOW);
  digitalWrite(TRIG4_PIN, LOW);

  // --- Pin Obstacle ---
  pinMode(OBSTACLE1_PIN, INPUT);
  pinMode(OBSTACLE2_PIN, INPUT);

  // --- UART2 ke ESP32 #1 ---
  sensorSerial.begin(115200, SERIAL_8N1, SENSOR_RX, SENSOR_TX);

  Serial.println("UART2 aktif — TX=GPIO17, RX=GPIO16, Baud=115200");
  Serial.println("Format: D,j1,j2,j3,j4,ob1,ob2\\n");
  Serial.println("Mulai mengirim data...");
  Serial.println();
}

// ==================================================
// LOOP
// ==================================================
void loop() {
  // --- Baca 4 ultrasonik ---
  int jarak1 = bacaUltrasonik(TRIG1_PIN, ECHO1_PIN);
  delay(15);
  int jarak2 = bacaUltrasonik(TRIG2_PIN, ECHO2_PIN);
  delay(15);
  int jarak3 = bacaUltrasonik(TRIG3_PIN, ECHO3_PIN);
  delay(15);
  int jarak4 = bacaUltrasonik(TRIG4_PIN, ECHO4_PIN);
  delay(15);

  // --- Baca 2 obstacle ---
  int ob1 = digitalRead(OBSTACLE1_PIN);
  int ob2 = digitalRead(OBSTACLE2_PIN);

  // --- Kirim ke ESP32 #1 via UART2 ---
  // Format: D,jarak1,jarak2,jarak3,jarak4,ob1,ob2\n
  sensorSerial.print("D,");
  sensorSerial.print(jarak1); sensorSerial.print(",");
  sensorSerial.print(jarak2); sensorSerial.print(",");
  sensorSerial.print(jarak3); sensorSerial.print(",");
  sensorSerial.print(jarak4); sensorSerial.print(",");
  sensorSerial.print(ob1);    sensorSerial.print(",");
  sensorSerial.print(ob2);
  sensorSerial.print("\n");

  // --- Log ke Serial Monitor (untuk debug) ---
  Serial.print("US: ");
  Serial.print(jarak1); Serial.print(" / ");
  Serial.print(jarak2); Serial.print(" / ");
  Serial.print(jarak3); Serial.print(" / ");
  Serial.print(jarak4);
  Serial.print("  OB: ");
  Serial.print(ob1 == LOW ? "ADA" : "KOSONG"); Serial.print(" / ");
  Serial.println(ob2 == LOW ? "ADA" : "KOSONG");

  // Total delay per siklus: 15×4 + 30 = 90ms → ~11 paket/detik
  delay(30);
}