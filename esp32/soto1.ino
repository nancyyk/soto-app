/*
 * ============================================================
 * SOTO — ESP32 #1 UTAMA (CONTROLLER) — VERSI UART + PARTIAL TFT
 * ============================================================
 * Integrasi:
 *   - RFID RC522        : SS=15, RST=22
 *   - TFT ILI9341       : CS=5  (via TFT_eSPI) — PARTIAL REFRESH
 *   - DFPlayer Mini     : RX=25, TX=26 (UART1)
 *   - Sensor UART       : RX=16, TX=17 (UART2, dari ESP32 #2)
 *   - WiFi (STA)        : Untuk MQTT
 *   - MQTT HiveMQ Cloud : Publish via PubSubClient TLS
 *
 * KABEL KE ESP32 #2:
 *   ESP32 #2 TX (GPIO17) ──[100Ω]──► ESP32 #1 RX2 (GPIO16)
 *   ESP32 #2 GND ──────────────────── ESP32 #1 GND   ← WAJIB!
 *
 * CATATAN TLS:
 *   Pakai wifiClient.setInsecure() — untuk development.
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <TFT_eSPI.h>
#include <DFRobotDFPlayerMini.h>
#include <ArduinoJson.h>

// ============================================================
// PIN
// ============================================================
#define TFT_CS         5
#define RFID_SS        15
#define RFID_RST       22

#define DFPLAYER_RX    25
#define DFPLAYER_TX    26

#define SENSOR_RX      16
#define SENSOR_TX      17
#define SENSOR_BAUD    115200

// ============================================================
// WIFI & MQTT
// ============================================================
const char* WIFI_SSID     = "Staf / Teknisi";
const char* WIFI_PASSWORD = "Kabiarachis30Hypogaea";

const char* MQTT_HOST   = "92dfec62ae7648038f50c5842ab6977b.s1.eu.hivemq.cloud";
const int   MQTT_PORT   = 8883;
const char* MQTT_USER   = "soto";
const char* MQTT_PASS   = "!Soto1234";
const char* MQTT_CLIENT = "esp32-soto-01";

const char* TOPIC_SENSOR  = "soto/sensor/esp32-soto-01";
const char* TOPIC_COMMAND = "soto/device/esp32-soto-01/command";
const char* TOPIC_UID     = "soto/device/esp32-soto-01/uid";

// ============================================================
// STRUKTUR DATA SENSOR
// ============================================================
typedef struct {
  int jarak1;
  int jarak2;
  int jarak3;
  int jarak4;
  int obstacle1;
  int obstacle2;
} SensorData;

// ============================================================
// OBJEK GLOBAL
// ============================================================
TFT_eSPI            tft = TFT_eSPI();
MFRC522             rfid(RFID_SS, RFID_RST);
HardwareSerial      dfPlayerSerial(1);
HardwareSerial      sensorSerial(2);
DFRobotDFPlayerMini dfPlayer;
WiFiClientSecure    wifiClient;
PubSubClient        mqttClient(wifiClient);

// ============================================================
// KONSTANTA
// ============================================================
const int   POIN_PER_BOTOL    = 1;
const float TINGGI_REFERENSI  = 27.0;
const int   KAPASITAS_WARNING = 80;
const unsigned long TIMEOUT_SENSOR_MS     = 5000;
const unsigned long DEBOUNCE_RFID_MS      = 1500;
const unsigned long INTERVAL_MQTT_MS      = 5000;
const unsigned long TIMEOUT_TRANSAKSI_MS  = 300000UL;

// ============================================================
// STATE
// ============================================================
enum MesinState { STATE_STANDBY, STATE_WAIT_CHECK, STATE_TRANSAKSI, STATE_SELESAI };
MesinState stateMesin = STATE_STANDBY;

enum BottleStage { STAGE_0, STAGE_1, STAGE_2, STAGE_3 };
BottleStage bottleStage = STAGE_0;

SensorData    data;
bool          dataMasuk = false;
unsigned long waktuDataTerakhir = 0;

String uidAktif    = "";
int    jumlahBotol = 0;
int    totalPoin   = 0;

float  jarakRataRata   = 0.0;
int    kapasitasPersen = 0;
bool   warningPlayed80  = false;
bool   warningPlayed100 = false;

unsigned long lastRfidTime = 0;
bool          scanningPairing = false;
unsigned long scanDeadline    = 0;
unsigned long waitCheckMulai  = 0;
unsigned long lastMqttPublish = 0;

unsigned long transaksiMulai    = 0;
unsigned long aktivitasTerakhir = 0;

bool dfPlayerReady = false;
String uartBuffer = "";

// State untuk partial refresh — simpan nilai lama
int  lastKapasitasPersen = -1;
bool lastSensorOnline    = false;
int  lastJumlahBotol     = -1;
int  lastTotalPoin       = -1;

// Flag redraw
bool needFullRedraw  = true;   // gambar ulang seluruh kerangka
bool needPartialRefresh = false; // hanya angka yang berubah

// ============================================================
// FORWARD DECLARATIONS
// ============================================================
void tftStandbyInit();
void tftStandbyUpdate();
void tftTransaksiInit();
void tftTransaksiUpdate();
void tftHasilTransaksiInit();
void tftWaitCheckInit();
void tftKartuTidakTerdaftarInit();
void tftPenuhInit();
void tftBootScreen();

void prosesRFID(String uid);
void selesaikanTransaksi();
void autoCommitTransaksi();
void kirimTransaksiKeAPI(String uid, int botol, int poin);
void hitungKapasitas();
void cekWarningKapasitas();
void fsmBotol();
void playAudio(int nomor);
void publishMQTT();
void setupMqtt();
void connectMqtt();
void reconnectMqtt();
void connectWifi();
void bacaUartSensor();
void parseSensorLine(String line);

// ============================================================
// BACA UART
// ============================================================
void bacaUartSensor() {
  while (sensorSerial.available()) {
    char c = sensorSerial.read();
    if (c == '\n') {
      if (uartBuffer.length() > 0) {
        parseSensorLine(uartBuffer);
        uartBuffer = "";
      }
    } else if (c != '\r') {
      uartBuffer += c;
      if (uartBuffer.length() > 100) uartBuffer = "";
    }
  }
}

void parseSensorLine(String line) {
  if (!line.startsWith("D,")) return;

  int idx = 2;
  int values[6];
  int count = 0;

  while (idx < line.length() && count < 6) {
    int comma = line.indexOf(',', idx);
    if (comma == -1) comma = line.length();
    values[count++] = line.substring(idx, comma).toInt();
    idx = comma + 1;
  }
  if (count != 6) return;

  data.jarak1    = values[0];
  data.jarak2    = values[1];
  data.jarak3    = values[2];
  data.jarak4    = values[3];
  data.obstacle1 = values[4];
  data.obstacle2 = values[5];

  dataMasuk         = true;
  waktuDataTerakhir = millis();

  // Update kapasitas & warning
  hitungKapasitas();
  cekWarningKapasitas();

  // Update TFT sesuai state (partial, bukan full)
  if (stateMesin == STATE_STANDBY) {
    tftStandbyUpdate();
  } else if (stateMesin == STATE_TRANSAKSI) {
    tftTransaksiUpdate();
  }
}

// ============================================================
// MQTT CALLBACK
// ============================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String payloadStr = "";
  for (unsigned int i = 0; i < length; i++) payloadStr += (char)payload[i];

  Serial.println("[MQTT] " + String(topic) + " → " + payloadStr);

  JsonDocument doc;
  if (deserializeJson(doc, payloadStr) != DeserializationError::Ok) return;

  const char* action = doc["action"];
  if (action) {
    if (strcmp(action, "scan") == 0) {
      int expiresIn = doc["expires_in"] | 30;
      scanningPairing = true;
      scanDeadline    = millis() + ((unsigned long)expiresIn * 1000UL);
      Serial.println("[MQTT] Pairing RFID aktif " + String(expiresIn) + "s");
    }
    return;
  }

  if (String(topic).endsWith("/check_result")) {
    if (stateMesin != STATE_WAIT_CHECK) return;

    String recvUid = doc["uid"] | "";
    bool   valid   = doc["valid"] | false;

    if (recvUid != uidAktif) return;

    if (valid) {
      jumlahBotol       = 0;
      bottleStage       = STAGE_0;
      stateMesin        = STATE_TRANSAKSI;
      transaksiMulai    = millis();
      aktivitasTerakhir = millis();

      Serial.println("[RFID] Valid: " + uidAktif);
      playAudio(3);
      delay(2500);
      playAudio(6);

      // Full redraw ke layar transaksi
      tftTransaksiInit();
      needPartialRefresh = false;
      lastJumlahBotol = -1;   // paksa update pertama
    } else {
      Serial.println("[RFID] Tidak terdaftar: " + uidAktif);
      uidAktif = "";
      tftKartuTidakTerdaftarInit();
      playAudio(5);
      delay(4000);
      stateMesin = STATE_STANDBY;
      tftStandbyInit();
      lastKapasitasPersen = -1;
      lastSensorOnline = false;
    }
  }
}

// ============================================================
// AUDIO
// ============================================================
void playAudio(int nomor) {
  if (!dfPlayerReady) return;
  Serial.println("[DFPlayer] Track " + String(nomor));
  dfPlayer.playMp3Folder(nomor);
}

// ============================================================
// HITUNG KAPASITAS
// ============================================================
void hitungKapasitas() {
  float total = 0;
  int valid = 0;

  if (data.jarak1 >= 0 && data.jarak1 <= 400) { total += data.jarak1; valid++; }
  if (data.jarak2 >= 0 && data.jarak2 <= 400) { total += data.jarak2; valid++; }
  if (data.jarak3 >= 0 && data.jarak3 <= 400) { total += data.jarak3; valid++; }
  if (data.jarak4 >= 0 && data.jarak4 <= 400) { total += data.jarak4; valid++; }

  if (valid == 0) {
    jarakRataRata = TINGGI_REFERENSI;
    kapasitasPersen = 0;
    return;
  }

  jarakRataRata = total / valid;
  float persen = ((TINGGI_REFERENSI - jarakRataRata) / TINGGI_REFERENSI) * 100.0;
  if (persen < 0) persen = 0;
  if (persen > 100) persen = 100;
  kapasitasPersen = (int)persen;
}

void cekWarningKapasitas() {
  if (kapasitasPersen < 80)  warningPlayed80  = false;
  if (kapasitasPersen < 100) warningPlayed100 = false;

  if (kapasitasPersen >= 100 && !warningPlayed100) {
    warningPlayed100 = true;
    warningPlayed80  = true;
    playAudio(11);
  } else if (kapasitasPersen >= 80 && !warningPlayed80) {
    warningPlayed80 = true;
    playAudio(10);
  }
}

// ============================================================
// FSM BOTOL
// ============================================================
void fsmBotol() {
  if (stateMesin != STATE_TRANSAKSI) return;

  bool ir1 = (data.obstacle1 == LOW);
  bool ir2 = (data.obstacle2 == LOW);

  static bool prevIr1 = false, prevIr2 = false;
  if (ir1 != prevIr1 || ir2 != prevIr2) {
    aktivitasTerakhir = millis();
    prevIr1 = ir1;
    prevIr2 = ir2;
  }

  switch (bottleStage) {
    case STAGE_0:
      if (ir1 && !ir2) bottleStage = STAGE_1;
      break;

    case STAGE_1:
      if (ir1 && ir2) bottleStage = STAGE_2;
      else if (!ir1 && !ir2) bottleStage = STAGE_0;
      break;

    case STAGE_2:
      if (!ir1 && ir2) bottleStage = STAGE_3;
      else if (!ir1 && !ir2) bottleStage = STAGE_0;
      break;

    case STAGE_3:
      if (!ir1 && !ir2) {
        bottleStage = STAGE_0;
        jumlahBotol++;

        transaksiMulai    = millis();
        aktivitasTerakhir = millis();

        Serial.println("[FSM] Botol masuk! Total: " + String(jumlahBotol));
        playAudio(7);
        delay(1500);
        playAudio(9);

        // Update TFT
        tftTransaksiUpdate();
      } else if (ir1 && !ir2) {
        bottleStage = STAGE_0;
      }
      break;
  }
}

// ============================================================
// PROSES RFID
// ============================================================
void prosesRFID(String uid) {
  Serial.println("[RFID] UID: " + uid);

  if (scanningPairing) {
    if (millis() < scanDeadline) {
      JsonDocument doc;
      doc["device_id"] = MQTT_CLIENT;
      doc["uid"]       = uid;
      char buf[128];
      serializeJson(doc, buf);
      mqttClient.publish(TOPIC_UID, buf, false);
      scanningPairing = false;
    } else {
      scanningPairing = false;
    }
    return;
  }

  if (kapasitasPersen >= 100 && stateMesin == STATE_STANDBY) {
    tftPenuhInit();
    playAudio(11);
    delay(3000);
    tftStandbyInit();
    lastKapasitasPersen = -1;
    lastSensorOnline = false;
    return;
  }

  if (stateMesin == STATE_STANDBY) {
    uidAktif       = uid;
    stateMesin     = STATE_WAIT_CHECK;
    waitCheckMulai = millis();
    tftWaitCheckInit();

    JsonDocument doc;
    doc["device_id"] = MQTT_CLIENT;
    doc["uid"]       = uid;
    char buf[128];
    serializeJson(doc, buf);

    String topicCheck = "soto/device/" + String(MQTT_CLIENT) + "/check";
    mqttClient.publish(topicCheck.c_str(), buf, false);
    return;
  }

  if (stateMesin == STATE_TRANSAKSI && uid == uidAktif) {
    playAudio(12);
    delay(500);
    selesaikanTransaksi();
  }
}

// ============================================================
// SELESAIKAN TRANSAKSI
// ============================================================
void selesaikanTransaksi() {
  totalPoin  = jumlahBotol * POIN_PER_BOTOL;
  stateMesin = STATE_SELESAI;

  Serial.println("[TRANSAKSI] Selesai — " + String(jumlahBotol) + " botol");

  tftHasilTransaksiInit();
  playAudio(13);
  delay(2500);
  playAudio(14);

  kirimTransaksiKeAPI(uidAktif, jumlahBotol, totalPoin);
  publishMQTT();

  delay(5000);

  uidAktif    = "";
  jumlahBotol = 0;
  totalPoin   = 0;
  stateMesin  = STATE_STANDBY;
  tftStandbyInit();
  lastKapasitasPersen = -1;
  lastSensorOnline = false;
  lastJumlahBotol = -1;
}

// ============================================================
// AUTO-COMMIT (timeout)
// ============================================================
void autoCommitTransaksi() {
  Serial.println("[TRANSAKSI] Timeout — auto-commit");

  if (jumlahBotol > 0) {
    totalPoin = jumlahBotol * POIN_PER_BOTOL;
    stateMesin = STATE_SELESAI;

    tftHasilTransaksiInit();
    playAudio(13);
    delay(3000);

    kirimTransaksiKeAPI(uidAktif, jumlahBotol, totalPoin);
    publishMQTT();
    delay(2000);
  } else {
    playAudio(15);
    delay(2000);
  }

  uidAktif    = "";
  jumlahBotol = 0;
  totalPoin   = 0;
  stateMesin  = STATE_STANDBY;
  tftStandbyInit();
  lastKapasitasPersen = -1;
  lastSensorOnline = false;
}

// ============================================================
// KIRIM KE LARAVEL
// ============================================================
void kirimTransaksiKeAPI(String uid, int botol, int poin) {
  if (!mqttClient.connected()) return;

  JsonDocument doc;
  doc["device_id"]    = MQTT_CLIENT;
  doc["uid"]          = uid;
  doc["jumlah_botol"] = botol;
  doc["total_poin"]   = poin;

  char buf[256];
  serializeJson(doc, buf);
  String topicTx = "soto/device/" + String(MQTT_CLIENT) + "/transaction";
  mqttClient.publish(topicTx.c_str(), buf, false);
}

// ============================================================
// PUBLISH MQTT
// ============================================================
void publishMQTT() {
  if (!mqttClient.connected()) return;

  JsonDocument doc;
  doc["device_id"]        = MQTT_CLIENT;
  doc["uid"]              = uidAktif;
  doc["jumlah_botol"]     = jumlahBotol;
  doc["total_poin"]       = totalPoin;
  doc["kapasitas"]        = kapasitasPersen;
  doc["jarak_rata_rata"]  = jarakRataRata;
  doc["jarak_us1"]        = data.jarak1;
  doc["jarak_us2"]        = data.jarak2;
  doc["jarak_us3"]        = data.jarak3;
  doc["jarak_us4"]        = data.jarak4;
  doc["obstacle1"]        = data.obstacle1;
  doc["obstacle2"]        = data.obstacle2;
  doc["status_transaksi"] = (stateMesin == STATE_TRANSAKSI) ? "aktif" :
                            (stateMesin == STATE_WAIT_CHECK ? "wait_check" : "standby");

  char buf[512];
  serializeJson(doc, buf);
  mqttClient.publish(TOPIC_SENSOR, buf, false);
}

// ============================================================
// ============================================================
// TFT — FUNGSI TAMPILAN DENGAN PARTIAL REFRESH
// ============================================================
// ============================================================
// Konsep:
//   Init()   → gambar kerangka statis, dipanggil SEKALI saat masuk state
//   Update() → hanya overwrite area angka yang berubah, dipanggil RUTIN
// ============================================================

// ---------- BOOT SCREEN ----------
void tftBootScreen() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);
  tft.setTextColor(TFT_GREEN, TFT_BLACK);
  tft.setCursor(60, 100);
  tft.println("SOTO");
  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(35, 150);
  tft.println("Starting...");

  digitalWrite(TFT_CS, HIGH);
}

// ---------- STANDBY: INIT (sekali) ----------
void tftStandbyInit() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);

  // Judul
  tft.setTextColor(TFT_GREEN, TFT_BLACK);
  tft.setCursor(60, 10);
  tft.println("SOTO");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 40);
  tft.println("----------------");

  // Label Kapasitas (statis)
  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 65);
  tft.println("Kapasitas:");

  // Bar kosong & persen (akan diisi oleh Update)
  tft.setCursor(15, 90);
  tft.setTextColor(TFT_CYAN, TFT_BLACK);
  tft.println("---------- 0%");

  // Label sensor (statis)
  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 120);
  tft.println("SENSOR: ----");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 155);
  tft.println("----------------");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 180);
  tft.println("Tap kartu RFID");
  tft.setCursor(15, 210);
  tft.println("untuk memulai");

  digitalWrite(TFT_CS, HIGH);

  // Paksa update pertama
  lastKapasitasPersen = -1;
  lastSensorOnline = false;
  tftStandbyUpdate();
}

// ---------- STANDBY: UPDATE (partial) ----------
void tftStandbyUpdate() {
  bool sensorOnline = dataMasuk && (millis() - waktuDataTerakhir < TIMEOUT_SENSOR_MS);

  // Cek apakah perlu update (hanya kalau nilai berubah)
  bool kapasitasBerubah = (kapasitasPersen != lastKapasitasPersen);
  bool sensorBerubah    = (sensorOnline != lastSensorOnline);

  if (!kapasitasBerubah && !sensorBerubah) return;

  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  // --- Update Kapasitas (baris y=90, tinggi ~20px) ---
  if (kapasitasBerubah) {
    lastKapasitasPersen = kapasitasPersen;

    // Hapus area bar lama saja (x=15, y=90, lebar=250, tinggi=22)
    tft.fillRect(15, 90, 250, 22, TFT_BLACK);
    tft.setTextSize(2);
    tft.setCursor(15, 90);

    int blokIsi = kapasitasPersen / 10;
    String bar = "";
    for (int i = 0; i < 10; i++) bar += (i < blokIsi) ? "#" : "-";
    bar += " " + String(kapasitasPersen) + "%";

    if (kapasitasPersen >= 100)      tft.setTextColor(TFT_RED, TFT_BLACK);
    else if (kapasitasPersen >= 80)  tft.setTextColor(TFT_ORANGE, TFT_BLACK);
    else                             tft.setTextColor(TFT_CYAN, TFT_BLACK);

    tft.println(bar);
  }

  // --- Update Status Sensor (baris y=120) ---
  if (sensorBerubah) {
    lastSensorOnline = sensorOnline;

    tft.fillRect(15, 120, 200, 22, TFT_BLACK);
    tft.setTextSize(2);
    tft.setCursor(15, 120);

    if (sensorOnline) {
      tft.setTextColor(TFT_GREEN, TFT_BLACK);
      tft.println("SENSOR: OK");
    } else {
      tft.setTextColor(TFT_RED, TFT_BLACK);
      tft.println("SENSOR: OFFLINE");
    }
  }

  digitalWrite(TFT_CS, HIGH);
}

// ---------- TRANSAKSI: INIT ----------
void tftTransaksiInit() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);

  tft.setTextColor(TFT_GREEN, TFT_BLACK);
  tft.setCursor(60, 10);
  tft.println("SOTO");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 40);
  tft.println("----------------");

  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 65);
  tft.println("Botol:");

  tft.setCursor(15, 95);
  tft.println("Kapasitas:");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 125);
  tft.println("----------------");

  tft.setCursor(15, 150);
  tft.println("Masukkan botol");
  tft.setCursor(15, 178);
  tft.println("melalui lubang");

  tft.setCursor(15, 215);
  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.println("Tap kartu lagi");
  tft.setCursor(15, 243);
  tft.println("untuk selesai");

  digitalWrite(TFT_CS, HIGH);

  // Paksa update pertama
  lastJumlahBotol = -1;
  lastKapasitasPersen = -1;
  tftTransaksiUpdate();
}

// ---------- TRANSAKSI: UPDATE (partial) ----------
void tftTransaksiUpdate() {
  bool botolBerubah     = (jumlahBotol != lastJumlahBotol);
  bool kapasitasBerubah = (kapasitasPersen != lastKapasitasPersen);

  if (!botolBerubah && !kapasitasBerubah) return;

  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);
  tft.setTextSize(2);

  // --- Update Jumlah Botol (baris y=65) ---
  if (botolBerubah) {
    lastJumlahBotol = jumlahBotol;

    // Hapus area nilai botol (mulai setelah label "Botol:")
    tft.fillRect(120, 65, 180, 22, TFT_BLACK);
    tft.setCursor(120, 65);
    tft.setTextColor(TFT_CYAN, TFT_BLACK);
    tft.println(jumlahBotol);
  }

  // --- Update Kapasitas (baris y=95) ---
  if (kapasitasBerubah) {
    lastKapasitasPersen = kapasitasPersen;

    tft.fillRect(140, 95, 160, 22, TFT_BLACK);
    tft.setCursor(140, 95);

    if (kapasitasPersen >= 100)      tft.setTextColor(TFT_RED, TFT_BLACK);
    else if (kapasitasPersen >= 80)  tft.setTextColor(TFT_ORANGE, TFT_BLACK);
    else                             tft.setTextColor(TFT_CYAN, TFT_BLACK);

    tft.print(kapasitasPersen);
    tft.println("%");
  }

  digitalWrite(TFT_CS, HIGH);
}

// ---------- WAIT CHECK ----------
void tftWaitCheckInit() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);

  tft.setTextColor(TFT_CYAN, TFT_BLACK);
  tft.setCursor(15, 60);
  tft.println("Memeriksa Kartu...");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 120);
  tft.println("Mohon Tunggu");

  digitalWrite(TFT_CS, HIGH);
}

// ---------- KARTU TIDAK TERDAFTAR ----------
void tftKartuTidakTerdaftarInit() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);

  tft.setTextColor(TFT_RED, TFT_BLACK);
  tft.setCursor(15, 20);
  tft.println("Kartu belum");
  tft.setCursor(15, 50);
  tft.println("terdaftar!");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 100);
  tft.println("Download app:");
  tft.setTextColor(TFT_GREEN, TFT_BLACK);
  tft.setCursor(15, 128);
  tft.println("SOTO App");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 165);
  tft.println("Buat akun &");
  tft.setCursor(15, 193);
  tft.println("hub. kartu kamu");

  digitalWrite(TFT_CS, HIGH);
}

// ---------- PENUH ----------
void tftPenuhInit() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_RED);
  tft.setTextSize(2);

  tft.setTextColor(TFT_WHITE, TFT_RED);
  tft.setCursor(15, 60);
  tft.println("Tempat sampah");
  tft.setCursor(15, 90);
  tft.println("PENUH!");

  tft.setCursor(15, 150);
  tft.println("Gunakan mesin");
  tft.setCursor(15, 180);
  tft.println("lainnya.");

  digitalWrite(TFT_CS, HIGH);
}

// ---------- HASIL TRANSAKSI ----------
void tftHasilTransaksiInit() {
  digitalWrite(RFID_SS, HIGH);
  digitalWrite(TFT_CS, LOW);

  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);

  tft.setTextColor(TFT_GREEN, TFT_BLACK);
  tft.setCursor(15, 20);
  tft.println("Transaksi");
  tft.setCursor(15, 50);
  tft.println("Berhasil!");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 100);
  tft.println("----------------");

  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 125);
  tft.print("Botol : ");
  tft.setTextColor(TFT_CYAN, TFT_BLACK);
  tft.println(jumlahBotol);

  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 155);
  tft.print("Poin  : ");
  tft.setTextColor(TFT_CYAN, TFT_BLACK);
  tft.println(totalPoin);

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 195);
  tft.println("Terima kasih!");

  digitalWrite(TFT_CS, HIGH);
}

// ============================================================
// BACA RFID
// ============================================================
void bacaRFID() {
  if (scanningPairing && millis() >= scanDeadline) {
    scanningPairing = false;
  }

  if (millis() - lastRfidTime < DEBOUNCE_RFID_MS) return;

  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, LOW);

  if (!rfid.PICC_IsNewCardPresent()) { digitalWrite(RFID_SS, HIGH); return; }
  if (!rfid.PICC_ReadCardSerial())   { digitalWrite(RFID_SS, HIGH); return; }

  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
  digitalWrite(RFID_SS, HIGH);

  lastRfidTime = millis();
  prosesRFID(uid);
}

// ============================================================
// WIFI & MQTT
// ============================================================
void connectWifi() {
  Serial.print("[WiFi] Connecting to: " + String(WIFI_SSID));
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    if (++attempts > 40) {
      Serial.println("\n[WiFi] Gagal — restart");
      ESP.restart();
    }
  }
  Serial.println("\n[WiFi] IP: " + WiFi.localIP().toString());
}

void setupMqtt() {
  wifiClient.setInsecure();
  mqttClient.setServer(MQTT_HOST, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
  mqttClient.setKeepAlive(60);
  mqttClient.setBufferSize(512);
  connectMqtt();
}

void connectMqtt() {
  int retries = 0;
  while (!mqttClient.connected()) {
    Serial.print("[MQTT] Connecting...");
    if (mqttClient.connect(MQTT_CLIENT, MQTT_USER, MQTT_PASS)) {
      Serial.println(" OK!");
      mqttClient.subscribe(TOPIC_COMMAND, 1);
      String topicCheckRes = "soto/device/" + String(MQTT_CLIENT) + "/check_result";
      mqttClient.subscribe(topicCheckRes.c_str(), 1);
    } else {
      Serial.println(" Gagal rc=" + String(mqttClient.state()));
      if (++retries >= 5) return;
      delay(3000);
    }
  }
}

void reconnectMqtt() {
  if (!mqttClient.connected()) connectMqtt();
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  delay(2000);

  Serial.println("\n==============================");
  Serial.println(" ESP32 #1 - SOTO CONTROLLER");
  Serial.println("==============================");

  pinMode(TFT_CS, OUTPUT);
  pinMode(RFID_SS, OUTPUT);
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, HIGH);

  connectWifi();

  SPI.begin(18, 19, 23);

  // --- TFT ---
  Serial.println("[TFT] Init...");
  tft.init();
  tft.setRotation(2);
  tft.fillScreen(TFT_BLACK);
  tftBootScreen();

  // --- RFID ---
  Serial.println("[RFID] Init...");
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, LOW);
  rfid.PCD_Init();
  delay(100);
  rfid.PCD_DumpVersionToSerial();
  digitalWrite(RFID_SS, HIGH);

  // --- DFPlayer (UART1) ---
  Serial.println("[DFPlayer] Init (UART1)...");
  dfPlayerSerial.begin(9600, SERIAL_8N1, DFPLAYER_RX, DFPLAYER_TX);
  delay(1000);
  if (dfPlayer.begin(dfPlayerSerial)) {
    dfPlayerReady = true;
    dfPlayer.volume(25);
    dfPlayer.EQ(DFPLAYER_EQ_NORMAL);
    Serial.println("[DFPlayer] OK");
  } else {
    Serial.println("[DFPlayer] GAGAL");
  }

  // --- Sensor UART (UART2) ---
  Serial.println("[SENSOR UART] Init (UART2)...");
  sensorSerial.begin(SENSOR_BAUD, SERIAL_8N1, SENSOR_RX, SENSOR_TX);

  // --- MQTT ---
  setupMqtt();

  // --- Audio selamat datang ---
  delay(500);
  playAudio(1);
  delay(2500);
  playAudio(2);

  // --- Tampilan standby ---
  tftStandbyInit();

  Serial.println("==============================");
  Serial.println("       SYSTEM READY");
  Serial.println("==============================");
}

// ============================================================
// LOOP
// ============================================================
void loop() {
  // MQTT keep-alive
  if (!mqttClient.connected()) reconnectMqtt();
  mqttClient.loop();

  // Baca UART sensor (akan auto-update TFT jika perlu)
  bacaUartSensor();

  // Deteksi perubahan status sensor (online/offline)
  static bool prevSensorOnline = false;
  bool sensorOnline = dataMasuk && (millis() - waktuDataTerakhir < TIMEOUT_SENSOR_MS);
  if (sensorOnline != prevSensorOnline) {
    prevSensorOnline = sensorOnline;
    if (stateMesin == STATE_STANDBY) tftStandbyUpdate();
  }

  // FSM Botol
  if (stateMesin == STATE_TRANSAKSI && dataMasuk) {
    fsmBotol();
  }

  // Timeout wait check
  if (stateMesin == STATE_WAIT_CHECK && (millis() - waitCheckMulai > 5000)) {
    Serial.println("[MQTT] Timeout check_result");
    uidAktif = "";
    stateMesin = STATE_STANDBY;
    tftStandbyInit();
    lastKapasitasPersen = -1;
    lastSensorOnline = false;
    playAudio(16);
  }

  // Auto-timeout transaksi
  if (stateMesin == STATE_TRANSAKSI &&
      (millis() - aktivitasTerakhir > TIMEOUT_TRANSAKSI_MS)) {
    autoCommitTransaksi();
  }

  // Baca RFID
  bacaRFID();

  // Publish MQTT berkala
  if (millis() - lastMqttPublish >= INTERVAL_MQTT_MS) {
    lastMqttPublish = millis();
    publishMQTT();
  }

  delay(10);
}