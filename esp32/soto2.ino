/*
 * ============================================================
 * SOTO — ESP32 #2 SENSOR SENDER — VERSI UART + INA219
 * ============================================================
 * Kirim data sensor ke ESP32 #1 via UART2.
 *
 * KABEL KE ESP32 #1:
 *   ESP32 #2 TX (GPIO17) ──[100Ω opsional]──► ESP32 #1 RX2 (GPIO16)
 *   ESP32 #2 GND         ───────────────────── ESP32 #1 GND   ← WAJIB!
 *
 * Format data: D,jarak1,jarak2,jarak3,jarak4,ob1,ob2,tegangan\n
 * Baudrate: 115200
 *
 * PIN YANG DIPAKAI:
 *   Ultrasonik 1 : TRIG=13, ECHO=34
 *   Ultrasonik 2 : TRIG=14, ECHO=35
 *   Ultrasonik 3 : TRIG=26, ECHO=32
 *   Ultrasonik 4 : TRIG=27, ECHO=33
 *   Obstacle 1   : GPIO21
 *   Obstacle 2   : GPIO4
 *   UART TX      : GPIO17
 *   UART RX      : GPIO16
 *
 * INA219 (I2C):
 *   SDA          : GPIO18
 *   SCL          : GPIO19
 *   VCC          : 3.3V
 *   GND          : GND
 *
 * KALIBRASI (referensi 27 cm):
 *   Sensor 1: offset 1
 *   Sensor 2: offset 2
 *   Sensor 3: offset 2
 *   Sensor 4: offset 4
 * ============================================================
 */

#include <Arduino.h>
#include <Wire.h>
#include <Adafruit_INA219.h>

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
#define OBSTACLE2_PIN 4

// ==================================================
// UART KE ESP32 #1
// ==================================================
#define SENSOR_TX 17
#define SENSOR_RX 16

HardwareSerial sensorSerial(2);

// ==================================================
// INA219 (I2C)
// ==================================================
#define I2C_SDA 18
#define I2C_SCL 19

Adafruit_INA219 ina219;
bool ina219Ready = false;

// ==================================================
// KALIBRASI OFFSET PER-SENSOR (cm)
// ==================================================
const int OFFSET1 = 1;
const int OFFSET2 = 2;
const int OFFSET3 = 2;
const int OFFSET4 = 4;

const int JARAK_MIN = 2;
const int JARAK_MAX = 400;

// Interval baca INA219 (jangan tiap loop, terlalu cepat)
const unsigned long INA219_INTERVAL_MS = 1000;
unsigned long lastIna219Read = 0;
float teganganTerakhir = 0.0;
float arusTerakhir     = 0.0;

// ==================================================
// BACA ULTRASONIK (RAW)
// ==================================================
long bacaUltrasonikRaw(int trigPin, int echoPin) {
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
// MEDIAN FILTER 3x
// ==================================================
long bacaUltrasonikMedian(int trigPin, int echoPin) {
  long a = bacaUltrasonikRaw(trigPin, echoPin);
  delay(10);
  long b = bacaUltrasonikRaw(trigPin, echoPin);
  delay(10);
  long c = bacaUltrasonikRaw(trigPin, echoPin);

  if (a < 0 && b < 0) return c;
  if (a < 0 && c < 0) return b;
  if (b < 0 && c < 0) return a;

  if ((a <= b && b <= c) || (c <= b && b <= a)) return b;
  if ((b <= a && a <= c) || (c <= a && a <= b)) return a;
  return c;
}

// ==================================================
// BACA + KALIBRASI
// ==================================================
int bacaUltrasonikTerkalibrasi(int trigPin, int echoPin, int offset) {
  long raw = bacaUltrasonikMedian(trigPin, echoPin);
  if (raw < 0) return -1;

  int hasil = (int)raw + offset;
  if (hasil < JARAK_MIN) hasil = JARAK_MIN;
  if (hasil > JARAK_MAX) hasil = JARAK_MAX;
  return hasil;
}

// ==================================================
// BACA INA219 (rate-limited)
// ==================================================
void updateIna219() {
  if (!ina219Ready) return;
  if (millis() - lastIna219Read < INA219_INTERVAL_MS) return;

  lastIna219Read = millis();

  float busVoltage = ina219.getBusVoltage_V();    // Tegangan (V)
  float shuntVoltage = ina219.getShuntVoltage_mV();
  float current_mA = ina219.getCurrent_mA();      // Arus (mA)

  // Tegangan bus + shunt = tegangan aktual di beban
  teganganTerakhir = busVoltage + (shuntVoltage / 1000.0);
  arusTerakhir     = current_mA;

  Serial.printf("[INA219] Tegangan=%.2fV Arus=%.1fmA Shunt=%.2fmV\n",
                teganganTerakhir, arusTerakhir, shuntVoltage);
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
  Serial.println(" (Kalibrasi + Median + INA219)");
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

  // --- I2C + INA219 ---
  Serial.printf("I2C init — SDA=GPIO%d, SCL=GPIO%d\n", I2C_SDA, I2C_SCL);
  Wire.begin(I2C_SDA, I2C_SCL);

  if (ina219.begin(&Wire)) {
    ina219Ready = true;
    // Kalibrasi 32V / 2A (default) — sesuaikan jika pakai shunt lain
    // Untuk baterai Li-ion (max ~4.2V), mode ini aman
    ina219.setCalibration_32V_2A();
    Serial.println("[INA219] ✅ Terdeteksi");

    // Tes baca awal
    delay(100);
    float v = ina219.getBusVoltage_V();
    Serial.printf("[INA219] Tegangan awal: %.2f V\n", v);
  } else {
    ina219Ready = false;
    Serial.println("[INA219] ❌ Tidak terdeteksi — cek wiring SDA/SCL");
    Serial.println("         Pastikan pull-up 4.7kΩ di SDA & SCL ke 3.3V");
  }

  Serial.println("Format: D,j1,j2,j3,j4,ob1,ob2,tegangan\\n");
  Serial.print("Offset kalibrasi: ");
  Serial.print(OFFSET1); Serial.print(" / ");
  Serial.print(OFFSET2); Serial.print(" / ");
  Serial.print(OFFSET3); Serial.print(" / ");
  Serial.println(OFFSET4);
  Serial.println("Mulai mengirim data...");
  Serial.println();
}

// ==================================================
// LOOP
// ==================================================
void loop() {
  // --- Update INA219 (rate-limited tiap 1 detik) ---
  updateIna219();

  // --- Baca 4 ultrasonik ---
  int jarak1 = bacaUltrasonikTerkalibrasi(TRIG1_PIN, ECHO1_PIN, OFFSET1);
  delay(20);
  int jarak2 = bacaUltrasonikTerkalibrasi(TRIG2_PIN, ECHO2_PIN, OFFSET2);
  delay(20);
  int jarak3 = bacaUltrasonikTerkalibrasi(TRIG3_PIN, ECHO3_PIN, OFFSET3);
  delay(20);
  int jarak4 = bacaUltrasonikTerkalibrasi(TRIG4_PIN, ECHO4_PIN, OFFSET4);
  delay(20);

  // --- Baca 2 obstacle ---
  int ob1 = digitalRead(OBSTACLE1_PIN);
  int ob2 = digitalRead(OBSTACLE2_PIN);

  // --- Kirim ke ESP32 #1 via UART2 ---
  // Format BARU: D,jarak1,jarak2,jarak3,jarak4,ob1,ob2,tegangan\n
  sensorSerial.print("D,");
  sensorSerial.print(jarak1); sensorSerial.print(",");
  sensorSerial.print(jarak2); sensorSerial.print(",");
  sensorSerial.print(jarak3); sensorSerial.print(",");
  sensorSerial.print(jarak4); sensorSerial.print(",");
  sensorSerial.print(ob1);    sensorSerial.print(",");
  sensorSerial.print(ob2);    sensorSerial.print(",");
  if (ina219Ready) {
    sensorSerial.print(teganganTerakhir, 2);   // 2 desimal, misal "3.85"
  } else {
    sensorSerial.print("0");                    // fallback jika INA219 mati
  }
  sensorSerial.print("\n");

  // --- Log ke Serial Monitor ---
  Serial.print("US: ");
  Serial.print(jarak1); Serial.print(" / ");
  Serial.print(jarak2); Serial.print(" / ");
  Serial.print(jarak3); Serial.print(" / ");
  Serial.print(jarak4);
  Serial.print("  OB: ");
  Serial.print(ob1 == LOW ? "ADA" : "KOSONG"); Serial.print(" / ");
  Serial.print(ob2 == LOW ? "ADA" : "KOSONG");
  Serial.print("  V: ");
  if (ina219Ready) {
    Serial.print(teganganTerakhir, 2);
    Serial.println("V");
  } else {
    Serial.println("---");
  }

  delay(30);
}