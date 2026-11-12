/*
 * ============================================================
 * SOTO — ESP32 #1 UTAMA (CONTROLLER) — v1.5
 * ============================================================
 * Changelog v1.5:
 *   - FIX: Layar "Kartu belum terdaftar" tampil 4 detik (tidak kedip)
 *   - FIX: Layar "Pairing timeout" tampil 3 detik (tidak kedip)
 *   - NEW: Timer layarInfoDeadline untuk semua layar info sementara
 *   - FIX: Tampilan hasil transaksi bertahan 8 detik sebelum standby
 *   - FIX: Audio "Silakan masukkan botol" (track 6) kembali via queue
 *   - NEW: Audio queue non-blocking (scheduleAudio)
 *   - NEW: Timer hasilTransaksiDeadline untuk STATE_SELESAI
 *   - FIX: Buffer MQTT 1024 + socket timeout 15s
 *   - NEW: Field tegangan (INA219) dari ESP32 #2
 *
 * Integrasi:
 *   - RFID RC522        : SS=15, RST=22
 *   - TFT ILI9341       : CS=5  (via TFT_eSPI) — PARTIAL REFRESH
 *   - DFPlayer Mini     : RX=25, TX=26 (UART1)
 *   - Sensor UART       : RX=16, TX=17 (UART2, dari ESP32 #2)
 *   - Buzzer            : GPIO 27
 *   - WiFi (STA)        : Untuk MQTT
 *   - MQTT HiveMQ Cloud : Publish via PubSubClient TLS
 *
 * KABEL KE ESP32 #2:
 *   ESP32 #2 TX (GPIO17) ──[100Ω]──► ESP32 #1 RX2 (GPIO16)
 *   ESP32 #2 GND ──────────────────── ESP32 #1 GND   ← WAJIB!
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

#define BUZZER_PIN     27
#define BUZZER_FREQ    1850
#define BUZZER_DURASI  150

// ============================================================
// WIFI & MQTT
// ============================================================
const char* WIFI_SSID     = "TRIFENA 1313";
const char* WIFI_PASSWORD = "13133131";

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
  int jarak1, jarak2, jarak3, jarak4;
  int obstacle1, obstacle2;
  float tegangan;
} SensorData;

// ============================================================
// AUDIO QUEUE (NON-BLOCKING)
// ============================================================
#define MAX_PENDING_AUDIO 6
struct PendingAudio {
  int           track;
  unsigned long at;   // 0 = slot kosong
};
PendingAudio audioQueue[MAX_PENDING_AUDIO];

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
const unsigned long TIMEOUT_SENSOR_MS     = 5000;
const unsigned long DEBOUNCE_RFID_MS      = 1500;
const unsigned long INTERVAL_MQTT_MS      = 5000;
const unsigned long TIMEOUT_TRANSAKSI_MS  = 300000UL;
const unsigned long TIMEOUT_CHECK_RESULT  = 5000;
const unsigned long TAMPIL_HASIL_MS       = 8000;   // hasil transaksi 8 detik
const unsigned long TAMPIL_KARTU_INVALID_MS = 4000; // kartu belum terdaftar 4 detik
const unsigned long TAMPIL_PAIRING_TIMEOUT_MS = 3000; // pairing timeout 3 detik

#define SCR_W 240
#define SCR_H 320

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

int  lastKapasitasPersen = -1;
bool lastSensorOnline    = false;
int  lastJumlahBotol     = -1;
int  lastTotalPoin       = -1;

int  lastPairingDetik    = -1;
bool pairingScreenActive = false;

unsigned long lastSensorWarn = 0;
bool          lastSensorState = false;
unsigned long lastSensorDataCount = 0;

// Counter MQTT untuk log
unsigned long mqttPublishOk   = 0;
unsigned long mqttPublishFail = 0;

// ▼▼▼ Timer tampilan hasil transaksi ▼▼▼
unsigned long hasilTransaksiDeadline = 0;

// ▼▼▼ Timer layar info sementara (kartu tidak terdaftar, pairing timeout) ▼▼▼
unsigned long layarInfoDeadline = 0;

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
void tftPairingInit();
void tftPairingUpdate();
void tftPairingEnd(bool sukses);

void prosesRFID(String uid);
void selesaikanTransaksi();
void autoCommitTransaksi();
void kirimTransaksiKeAPI(String uid, int botol, int poin);
void hitungKapasitas();
void cekWarningKapasitas();
void fsmBotol();
void playAudio(int nomor);
void scheduleAudio(int track, unsigned long delayMs);
void processPendingAudio();
void buzzerTit();
void buzzerInit();
bool publishMQTT();
bool publishCustom(const char* topic, const char* payload);
void setupMqtt();
void connectMqtt();
void reconnectMqtt();
void connectWifi();
void bacaUartSensor();
void parseSensorLine(String line);

// ============================================================
// HELPER: TFT CENTERING
// ============================================================
void printCentered(const String &text, int y, uint16_t color, uint8_t size = 2) {
  tft.setTextSize(size);
  tft.setTextColor(color, TFT_BLACK);
  int w = tft.textWidth(text);
  int x = (SCR_W - w) / 2;
  if (x < 0) x = 0;
  tft.setCursor(x, y);
  tft.print(text);
}

void printCenteredOn(const String &text, int y, uint16_t color, uint16_t bg, uint8_t size = 2) {
  tft.setTextSize(size);
  tft.setTextColor(color, bg);
  int w = tft.textWidth(text);
  int x = (SCR_W - w) / 2;
  if (x < 0) x = 0;
  tft.setCursor(x, y);
  tft.print(text);
}

void printLabelValue(const String &label, const String &value, int y,
                     uint16_t labelColor, uint16_t valueColor, uint8_t size = 2) {
  tft.setTextSize(size);
  tft.setTextColor(labelColor, TFT_BLACK);
  tft.setCursor(10, y);
  tft.print(label);
  tft.setTextColor(valueColor, TFT_BLACK);
  int w = tft.textWidth(value);
  int x = SCR_W - 10 - w;
  if (x < 0) x = 0;
  tft.setCursor(x, y);
  tft.print(value);
}

void drawDivider(int y, uint16_t color = TFT_WHITE) {
  tft.drawFastHLine(15, y, SCR_W - 30, color);
}

// ============================================================
// BACA UART
// ============================================================
void bacaUartSensor() {
  while (sensorSerial.available()) {
    char c = sensorSerial.read();
    if (c == '\n') {
      if (uartBuffer.length() > 0) {
        Serial.println("[UART2] Raw: " + uartBuffer);
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
  if (!line.startsWith("D,")) {
    Serial.println("[UART2] ⚠ Format tidak dikenal: " + line);
    return;
  }

  int values[6];
  int idx = 2;
  int valueCount = 0;

  while (idx < line.length() && valueCount < 6) {
    int comma = line.indexOf(',', idx);
    if (comma == -1) comma = line.length();
    values[valueCount++] = line.substring(idx, comma).toInt();
    idx = comma + 1;
  }

  // Field ke-7 adalah tegangan (float) — opsional
  float tegangan = 0.0;
  if (valueCount >= 6 && idx < line.length()) {
    tegangan = line.substring(idx).toFloat();
  }

  if (valueCount < 6) {
    Serial.println("[UART2] ⚠ Field kurang (" + String(valueCount) + "/6): " + line);
    return;
  }

  data.jarak1    = values[0];
  data.jarak2    = values[1];
  data.jarak3    = values[2];
  data.jarak4    = values[3];
  data.obstacle1 = values[4];
  data.obstacle2 = values[5];
  data.tegangan  = tegangan;

  dataMasuk         = true;
  waktuDataTerakhir = millis();
  lastSensorDataCount++;

  hitungKapasitas();
  cekWarningKapasitas();

  Serial.printf("[SENSOR] #%lu J1=%d J2=%d J3=%d J4=%d | IR1=%d IR2=%d | Rata=%.1fcm Kap=%d%% | V=%.2f\n",
                lastSensorDataCount,
                data.jarak1, data.jarak2, data.jarak3, data.jarak4,
                data.obstacle1, data.obstacle2,
                jarakRataRata, kapasitasPersen, data.tegangan);

  if (stateMesin == STATE_STANDBY && !pairingScreenActive && layarInfoDeadline == 0) {
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

  Serial.println("[MQTT] RX [" + String(topic) + "] → " + payloadStr);

  JsonDocument doc;
  if (deserializeJson(doc, payloadStr) != DeserializationError::Ok) {
    Serial.println("[MQTT] ⚠ Payload bukan JSON valid");
    return;
  }

  const char* action = doc["action"];
  if (action && strcmp(action, "scan") == 0) {
    if (stateMesin != STATE_STANDBY) {
      Serial.println("[PAIRING] Diabaikan — mesin tidak standby");
      return;
    }
    int expiresIn = doc["expires_in"] | 30;
    scanningPairing = true;
    scanDeadline    = millis() + ((unsigned long)expiresIn * 1000UL);
    Serial.println("[MQTT] Pairing RFID aktif " + String(expiresIn) + "s");
    tftPairingInit();
    pairingScreenActive = true;
    lastPairingDetik    = -1;
    playAudio(17);
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
      scheduleAudio(6, 2500);   // "Silakan masukkan botol"

      tftTransaksiInit();
      lastJumlahBotol = -1;
    } else {
      Serial.println("[RFID] Tidak terdaftar: " + uidAktif);
      uidAktif = "";
      stateMesin = STATE_STANDBY;
      tftKartuTidakTerdaftarInit();
      playAudio(5);
      // ✅ Timer 4 detik — layar "belum terdaftar" tampil 4 detik
      layarInfoDeadline = millis() + TAMPIL_KARTU_INVALID_MS;
    }
  }
}

// ============================================================
// AUDIO
// ============================================================
void playAudio(int nomor) {
  if (!dfPlayerReady) {
    Serial.println("[DFPlayer] ⚠ Belum ready, skip track " + String(nomor));
    return;
  }
  Serial.println("[DFPlayer] ▶ Track " + String(nomor));
  dfPlayer.playMp3Folder(nomor);
}

void scheduleAudio(int track, unsigned long delayMs) {
  for (int i = 0; i < MAX_PENDING_AUDIO; i++) {
    if (audioQueue[i].at == 0) {
      audioQueue[i].track = track;
      audioQueue[i].at    = millis() + delayMs;
      Serial.printf("[AUDIO] ⏰ Track %d dijadwalkan +%lums\n", track, delayMs);
      return;
    }
  }
  Serial.printf("[AUDIO] ⚠ Queue penuh, drop track %d\n", track);
}

void processPendingAudio() {
  unsigned long now = millis();
  for (int i = 0; i < MAX_PENDING_AUDIO; i++) {
    if (audioQueue[i].at != 0 && now >= audioQueue[i].at) {
      int track = audioQueue[i].track;
      audioQueue[i].at = 0;
      playAudio(track);
    }
  }
}

// ============================================================
// BUZZER
// ============================================================
void buzzerInit() {
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);
}

void buzzerTit() {
  static unsigned long lastTit = 0;
  if (millis() - lastTit < 300) return;
  lastTit = millis();
  tone(BUZZER_PIN, BUZZER_FREQ, BUZZER_DURASI);
  Serial.println("[BUZZER] Tit");
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
      if (ir1 && ir2) { bottleStage = STAGE_2; buzzerTit(); }
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

  // Saat STATE_SELESAI — abaikan scan sampai timer habis
  if (stateMesin == STATE_SELESAI) {
    Serial.println("[RFID] Diabaikan — masih menampilkan hasil transaksi");
    return;
  }

  // Saat layar info aktif (kartu tidak terdaftar, pairing timeout) — abaikan
  if (layarInfoDeadline > 0) {
    Serial.println("[RFID] Diabaikan — masih menampilkan info");
    return;
  }

  if (scanningPairing) {
    if (millis() < scanDeadline) {
      JsonDocument doc;
      doc["device_id"] = MQTT_CLIENT;
      doc["uid"]       = uid;
      char buf[128];
      serializeJson(doc, buf);
      publishCustom(TOPIC_UID, buf);
      Serial.println("[PAIRING] UID dikirim: " + uid);
      scanningPairing = false;

      if (pairingScreenActive) {
        tftPairingEnd(true);
        pairingScreenActive = false;
        playAudio(18);
        delay(3000);
        tftStandbyInit();
        lastKapasitasPersen = -1;
        lastSensorOnline    = false;
      }
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
    publishCustom(topicCheck.c_str(), buf);
    return;
  }

  if (stateMesin == STATE_TRANSAKSI && uid == uidAktif) {
    selesaikanTransaksi();
  }
}

// ============================================================
// SELESAIKAN TRANSAKSI — tampil 8 detik
// ============================================================
void selesaikanTransaksi() {
  totalPoin  = jumlahBotol * POIN_PER_BOTOL;
  stateMesin = STATE_SELESAI;

  Serial.println("[TRANSAKSI] Selesai — " + String(jumlahBotol) + " botol, " + String(totalPoin) + " poin");

  tftHasilTransaksiInit();

  playAudio(13);
  scheduleAudio(14, 2500);

  kirimTransaksiKeAPI(uidAktif, jumlahBotol, totalPoin);
  publishMQTT();

  hasilTransaksiDeadline = millis() + TAMPIL_HASIL_MS;
}

// ============================================================
// AUTO-COMMIT — tampil 8 detik
// ============================================================
void autoCommitTransaksi() {
  Serial.println("[TRANSAKSI] Timeout — auto-commit");

  stateMesin = STATE_SELESAI;

  if (jumlahBotol > 0) {
    totalPoin = jumlahBotol * POIN_PER_BOTOL;

    tftHasilTransaksiInit();
    playAudio(13);
    scheduleAudio(14, 2500);

    kirimTransaksiKeAPI(uidAktif, jumlahBotol, totalPoin);
    publishMQTT();

    hasilTransaksiDeadline = millis() + TAMPIL_HASIL_MS;
  } else {
    playAudio(15);
    tftStandbyInit();
    lastKapasitasPersen = -1;
    lastSensorOnline = false;
    hasilTransaksiDeadline = millis() + 3000;
  }
}

// ============================================================
// KIRIM KE LARAVEL
// ============================================================
void kirimTransaksiKeAPI(String uid, int botol, int poin) {
  JsonDocument doc;
  doc["device_id"]    = MQTT_CLIENT;
  doc["uid"]          = uid;
  doc["jumlah_botol"] = botol;
  doc["total_poin"]   = poin;

  char buf[256];
  serializeJson(doc, buf);
  String topicTx = "soto/device/" + String(MQTT_CLIENT) + "/transaction";
  publishCustom(topicTx.c_str(), buf);
}

// ============================================================
// PUBLISH CUSTOM
// ============================================================
bool publishCustom(const char* topic, const char* payload) {
  if (!mqttClient.connected()) {
    Serial.printf("[MQTT] ❌ Publish gagal (tidak connected) → %s\n", topic);
    mqttPublishFail++;
    return false;
  }
  bool ok = mqttClient.publish(topic, payload, false);
  if (ok) {
    mqttPublishOk++;
    Serial.printf("[MQTT] ✅ TX [%s] %s\n", topic, payload);
  } else {
    mqttPublishFail++;
    Serial.printf("[MQTT] ❌ TX GAGAL [%s] state=%d\n", topic, mqttClient.state());
  }
  return ok;
}

// ============================================================
// PUBLISH MQTT SENSOR
// ============================================================
bool publishMQTT() {
  if (!mqttClient.connected()) {
    Serial.println("[MQTT] ❌ publishMQTT() dilewati — client tidak connected");
    mqttPublishFail++;
    return false;
  }

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

  if (data.tegangan > 0.1) {
    doc["tegangan_baterai"] = data.tegangan;
  }

  doc["status_transaksi"] = (stateMesin == STATE_TRANSAKSI) ? "aktif" :
                            (stateMesin == STATE_WAIT_CHECK ? "wait_check" :
                            (stateMesin == STATE_SELESAI ? "selesai" : "standby"));

  char buf[512];
  size_t len = serializeJson(doc, buf);

  bool ok = mqttClient.publish(TOPIC_SENSOR, buf, false);
  if (ok) {
    mqttPublishOk++;
    Serial.printf("[MQTT] ✅ SENSOR TX (%u B) kap=%d%% → %s\n",
                  (unsigned)len, kapasitasPersen, TOPIC_SENSOR);
  } else {
    mqttPublishFail++;
    Serial.printf("[MQTT] ❌ SENSOR TX GAGAL state=%d len=%u\n",
                  mqttClient.state(), (unsigned)len);
  }
  return ok;
}

// ============================================================
// TFT
// ============================================================
void tftBootScreen() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("SOTO", 110, TFT_GREEN, 4);
  printCentered("Starting...", 160, TFT_WHITE, 2);
  digitalWrite(TFT_CS, HIGH);
}

void tftStandbyInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("SOTO", 15, TFT_GREEN, 3);
  printCentered("Smart Bottle Bank", 50, TFT_DARKGREY, 1);
  drawDivider(70);
  printCentered("KAPASITAS", 85, TFT_YELLOW, 2);
  printCentered("---------- 0%", 115, TFT_CYAN, 2);
  printCentered("SENSOR: ----", 150, TFT_WHITE, 2);
  drawDivider(180);
  printCentered("Tap kartu RFID", 200, TFT_WHITE, 2);
  printCentered("untuk memulai", 230, TFT_WHITE, 2);
  printCentered("v1.5", 295, TFT_DARKGREY, 1);
  digitalWrite(TFT_CS, HIGH);
  lastKapasitasPersen = -1;
  lastSensorOnline = false;
  tftStandbyUpdate();
}

void tftStandbyUpdate() {
  bool sensorOnline = dataMasuk && (millis() - waktuDataTerakhir < TIMEOUT_SENSOR_MS);
  bool kapasitasBerubah = (kapasitasPersen != lastKapasitasPersen);
  bool sensorBerubah    = (sensorOnline != lastSensorOnline);
  if (!kapasitasBerubah && !sensorBerubah) return;

  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);

  if (kapasitasBerubah) {
    lastKapasitasPersen = kapasitasPersen;
    tft.fillRect(0, 115, SCR_W, 24, TFT_BLACK);
    int blokIsi = kapasitasPersen / 10;
    String bar = "";
    for (int i = 0; i < 10; i++) bar += (i < blokIsi) ? "#" : "-";
    bar += " " + String(kapasitasPersen) + "%";
    uint16_t color = (kapasitasPersen >= 100) ? TFT_RED :
                     (kapasitasPersen >= 80)  ? TFT_ORANGE : TFT_CYAN;
    printCentered(bar, 115, color, 2);
  }
  if (sensorBerubah) {
    lastSensorOnline = sensorOnline;
    tft.fillRect(0, 150, SCR_W, 24, TFT_BLACK);
    printCentered(sensorOnline ? "SENSOR: OK" : "SENSOR: OFFLINE", 150,
                  sensorOnline ? TFT_GREEN : TFT_RED, 2);
  }
  digitalWrite(TFT_CS, HIGH);
}

void tftTransaksiInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("SOTO", 15, TFT_GREEN, 3);
  printCentered("Transaksi Aktif", 50, TFT_DARKGREY, 1);
  drawDivider(70);
  printCentered("BOTOL MASUK", 85, TFT_YELLOW, 2);
  printCentered("0", 115, TFT_CYAN, 4);
  printCentered("KAPASITAS", 165, TFT_YELLOW, 2);
  printCentered("0%", 195, TFT_CYAN, 3);
  drawDivider(235);
  printCentered("Masukkan botol", 250, TFT_WHITE, 2);
  printCentered("Tap kartu utk selesai", 280, TFT_ORANGE, 1);
  digitalWrite(TFT_CS, HIGH);
  lastJumlahBotol = -1;
  lastKapasitasPersen = -1;
  tftTransaksiUpdate();
}

void tftTransaksiUpdate() {
  bool botolBerubah     = (jumlahBotol != lastJumlahBotol);
  bool kapasitasBerubah = (kapasitasPersen != lastKapasitasPersen);
  if (!botolBerubah && !kapasitasBerubah) return;

  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);

  if (botolBerubah) {
    lastJumlahBotol = jumlahBotol;
    tft.fillRect(0, 115, SCR_W, 40, TFT_BLACK);
    printCentered(String(jumlahBotol), 115, TFT_CYAN, 4);
  }
  if (kapasitasBerubah) {
    lastKapasitasPersen = kapasitasPersen;
    tft.fillRect(0, 195, SCR_W, 32, TFT_BLACK);
    uint16_t color = (kapasitasPersen >= 100) ? TFT_RED :
                     (kapasitasPersen >= 80)  ? TFT_ORANGE : TFT_CYAN;
    printCentered(String(kapasitasPersen) + "%", 195, color, 3);
  }
  digitalWrite(TFT_CS, HIGH);
}

void tftWaitCheckInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("Memeriksa", 120, TFT_CYAN, 3);
  printCentered("Kartu...", 155, TFT_CYAN, 3);
  printCentered("Mohon Tunggu", 220, TFT_WHITE, 2);
  digitalWrite(TFT_CS, HIGH);
}

void tftKartuTidakTerdaftarInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("Kartu belum", 50, TFT_RED, 3);
  printCentered("terdaftar!", 90, TFT_RED, 3);
  drawDivider(140);
  printCentered("Download app:", 160, TFT_WHITE, 2);
  printCentered("SOTO App", 190, TFT_GREEN, 3);
  drawDivider(240);
  printCentered("Buat akun &", 260, TFT_WHITE, 2);
  printCentered("hub. kartu kamu", 290, TFT_WHITE, 2);
  digitalWrite(TFT_CS, HIGH);
}

void tftPenuhInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_RED);
  printCenteredOn("Tempat sampah", 90, TFT_WHITE, TFT_RED, 3);
  printCenteredOn("PENUH!", 130, TFT_WHITE, TFT_RED, 4);
  drawDivider(180, TFT_WHITE);
  printCenteredOn("Gunakan mesin", 210, TFT_WHITE, TFT_RED, 2);
  printCenteredOn("lainnya.", 240, TFT_WHITE, TFT_RED, 2);
  digitalWrite(TFT_CS, HIGH);
}

void tftHasilTransaksiInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("Transaksi", 25, TFT_GREEN, 3);
  printCentered("Berhasil!", 60, TFT_GREEN, 3);
  drawDivider(105);
  printLabelValue("Botol", String(jumlahBotol), 130, TFT_YELLOW, TFT_CYAN, 2);
  printLabelValue("Poin", String(totalPoin), 170, TFT_YELLOW, TFT_CYAN, 2);
  drawDivider(210);
  printCentered("Terima kasih!", 235, TFT_WHITE, 2);
  digitalWrite(TFT_CS, HIGH);
}

void tftPairingInit() {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  printCentered("HUBUNGKAN", 30, TFT_CYAN, 3);
  printCentered("KARTU", 65, TFT_CYAN, 3);
  drawDivider(105);
  printCentered("Silakan tap", 130, TFT_WHITE, 2);
  printCentered("kartu Anda", 160, TFT_WHITE, 2);
  printCentered("di area sensor", 190, TFT_DARKGREY, 1);
  drawDivider(225);
  printCentered("Waktu: --s", 245, TFT_YELLOW, 3);
  digitalWrite(TFT_CS, HIGH);
  lastPairingDetik = -1;
  tftPairingUpdate();
}

void tftPairingUpdate() {
  if (!pairingScreenActive) return;
  long sisaMs = (long)scanDeadline - (long)millis();
  int  sisaDetik = (sisaMs > 0) ? (int)((sisaMs + 999) / 1000) : 0;
  if (sisaDetik == lastPairingDetik) return;
  lastPairingDetik = sisaDetik;
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillRect(0, 245, SCR_W, 30, TFT_BLACK);
  String txt = "Waktu: " + String(sisaDetik) + "s";
  uint16_t color = (sisaDetik <= 5) ? TFT_RED : TFT_YELLOW;
  printCentered(txt, 245, color, 3);
  digitalWrite(TFT_CS, HIGH);
}

void tftPairingEnd(bool sukses) {
  digitalWrite(RFID_SS, HIGH); digitalWrite(TFT_CS, LOW);
  tft.fillScreen(TFT_BLACK);
  if (sukses) {
    printCentered("Kartu", 110, TFT_GREEN, 3);
    printCentered("terhubung!", 145, TFT_GREEN, 3);
    printCentered("Silakan cek", 210, TFT_WHITE, 2);
    printCentered("di aplikasi", 240, TFT_WHITE, 2);
  } else {
    printCentered("Waktu", 110, TFT_RED, 3);
    printCentered("habis!", 145, TFT_RED, 3);
    printCentered("Kembali ke", 210, TFT_WHITE, 2);
    printCentered("standby...", 240, TFT_WHITE, 2);
  }
  digitalWrite(TFT_CS, HIGH);
}

// ============================================================
// BACA RFID
// ============================================================
void bacaRFID() {
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
  Serial.println("\n[WiFi] ✅ IP: " + WiFi.localIP().toString());
}

void setupMqtt() {
  wifiClient.setInsecure();
  mqttClient.setServer(MQTT_HOST, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
  mqttClient.setKeepAlive(60);
  mqttClient.setBufferSize(1024);
  mqttClient.setSocketTimeout(15);
  connectMqtt();
}

void connectMqtt() {
  int retries = 0;
  while (!mqttClient.connected()) {
    Serial.print("[MQTT] Connecting...");
    if (mqttClient.connect(MQTT_CLIENT, MQTT_USER, MQTT_PASS)) {
      Serial.println(" ✅ OK!");
      mqttClient.subscribe(TOPIC_COMMAND, 1);
      String topicCheckRes = "soto/device/" + String(MQTT_CLIENT) + "/check_result";
      mqttClient.subscribe(topicCheckRes.c_str(), 1);
      Serial.println("[MQTT] Subscribed ke command + check_result");
      lastMqttPublish = millis() - INTERVAL_MQTT_MS;
    } else {
      Serial.println(" ❌ Gagal rc=" + String(mqttClient.state()));
      if (++retries >= 5) return;
      delay(3000);
    }
  }
}

void reconnectMqtt() {
  if (!mqttClient.connected()) {
    Serial.println("[MQTT] ⚠ Koneksi hilang, reconnect...");
    connectMqtt();
  }
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  delay(2000);

  Serial.println("\n==============================");
  Serial.println(" ESP32 #1 - SOTO CONTROLLER v1.5");
  Serial.println("==============================");

  pinMode(TFT_CS, OUTPUT);
  pinMode(RFID_SS, OUTPUT);
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, HIGH);

  // Init audio queue
  for (int i = 0; i < MAX_PENDING_AUDIO; i++) {
    audioQueue[i].track = 0;
    audioQueue[i].at    = 0;
  }

  connectWifi();

  SPI.begin(18, 19, 23);

  Serial.println("[TFT] Init...");
  tft.init();
  tft.setRotation(2);
  tft.fillScreen(TFT_BLACK);
  tftBootScreen();

  Serial.println("[RFID] Init...");
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, LOW);
  rfid.PCD_Init();
  delay(100);
  rfid.PCD_DumpVersionToSerial();
  digitalWrite(RFID_SS, HIGH);

  Serial.println("[DFPlayer] Init (UART1)...");
  dfPlayerSerial.begin(9600, SERIAL_8N1, DFPLAYER_RX, DFPLAYER_TX);
  delay(1000);
  if (dfPlayer.begin(dfPlayerSerial)) {
    dfPlayerReady = true;
    dfPlayer.volume(15);
    dfPlayer.EQ(DFPLAYER_EQ_NORMAL);
    Serial.println("[DFPlayer] ✅ OK");
  } else {
    Serial.println("[DFPlayer] ❌ GAGAL");
  }

  Serial.println("[BUZZER] Init...");
  buzzerInit();

  Serial.println("[SENSOR UART] Init (UART2)...");
  Serial.println("  RX=16 TX=17 Baud=115200");
  sensorSerial.begin(SENSOR_BAUD, SERIAL_8N1, SENSOR_RX, SENSOR_TX);

  Serial.println("[MQTT] Setup...");
  setupMqtt();

  delay(500);
  playAudio(1);
  delay(2500);
  playAudio(2);

  tftStandbyInit();

  Serial.println("==============================");
  Serial.println("       SYSTEM READY");
  Serial.println("   Menunggu data dari ESP32 #2");
  Serial.println("==============================");
}

// ============================================================
// LOOP
// ============================================================
void loop() {
  if (!mqttClient.connected()) reconnectMqtt();
  mqttClient.loop();

  // Proses audio queue
  processPendingAudio();

  bacaUartSensor();

  // Handle pairing
  if (pairingScreenActive) {
    if (scanningPairing && millis() < scanDeadline) {
      tftPairingUpdate();
    } else {
      if (scanningPairing) {
        Serial.println("[PAIRING] Waktu habis");
        scanningPairing = false;
        tftPairingEnd(false);
        playAudio(19);
        // ✅ Layar "waktu habis" tampil 3 detik
        layarInfoDeadline = millis() + TAMPIL_PAIRING_TIMEOUT_MS;
      } else {
        tftStandbyInit();
        lastKapasitasPersen = -1;
        lastSensorOnline    = false;
      }
      pairingScreenActive = false;
    }
  }

  // ✅ Handle tampilan hasil transaksi — reset setelah 8 detik
  if (stateMesin == STATE_SELESAI && hasilTransaksiDeadline > 0
      && millis() >= hasilTransaksiDeadline) {
    Serial.println("[TRANSAKSI] Tampilan hasil selesai — kembali ke standby");

    hasilTransaksiDeadline = 0;
    uidAktif    = "";
    jumlahBotol = 0;
    totalPoin   = 0;
    stateMesin  = STATE_STANDBY;
    tftStandbyInit();
    lastKapasitasPersen = -1;
    lastSensorOnline = false;
    lastJumlahBotol = -1;
    lastMqttPublish = millis();
  }

  // ✅ Handle layar info sementara (kartu tidak terdaftar, pairing timeout)
  if (layarInfoDeadline > 0 && millis() >= layarInfoDeadline) {
    Serial.println("[TFT] Layar info selesai — kembali ke standby");
    layarInfoDeadline = 0;
    if (stateMesin == STATE_STANDBY) {
      tftStandbyInit();
      lastKapasitasPersen = -1;
      lastSensorOnline = false;
    }
  }

  // Status sensor
  bool sensorOnline = dataMasuk && (millis() - waktuDataTerakhir < TIMEOUT_SENSOR_MS);
  if (sensorOnline != lastSensorState) {
    lastSensorState = sensorOnline;
    Serial.println(sensorOnline ? "[SENSOR] ✅ ONLINE" : "[SENSOR] ❌ OFFLINE");
  }
  if (!sensorOnline && millis() - lastSensorWarn > 5000) {
    lastSensorWarn = millis();
    Serial.printf("[SENSOR] ⚠ Tidak ada data >5s. Total: %lu paket\n",
                  lastSensorDataCount);
  }

  if (stateMesin == STATE_STANDBY && !pairingScreenActive && layarInfoDeadline == 0) {
    tftStandbyUpdate();
  }
  if (stateMesin == STATE_TRANSAKSI && dataMasuk) fsmBotol();

  if (stateMesin == STATE_WAIT_CHECK &&
      (millis() - waitCheckMulai > TIMEOUT_CHECK_RESULT)) {
    Serial.println("[MQTT] Timeout check_result");
    uidAktif = "";
    stateMesin = STATE_STANDBY;
    tftStandbyInit();
    lastKapasitasPersen = -1;
    lastSensorOnline = false;
    playAudio(16);
  }

  if (stateMesin == STATE_TRANSAKSI &&
      (millis() - aktivitasTerakhir > TIMEOUT_TRANSAKSI_MS)) {
    autoCommitTransaksi();
  }

  bacaRFID();

  // Publish MQTT periodik
  if (millis() - lastMqttPublish >= INTERVAL_MQTT_MS) {
    lastMqttPublish = millis();
    publishMQTT();
  }

  // Log counter publish tiap 30 detik
  static unsigned long lastMqttStat = 0;
  if (millis() - lastMqttStat > 30000) {
    lastMqttStat = millis();
    Serial.printf("[MQTT] 📊 Statistik: OK=%lu, FAIL=%lu, connected=%d\n",
                  mqttPublishOk, mqttPublishFail, mqttClient.connected());
  }

  delay(10);
}