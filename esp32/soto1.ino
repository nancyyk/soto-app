/*
 * ============================================================
 * SOTO — ESP32 #1 UTAMA (CONTROLLER)
 * ============================================================
 * Integrasi:
 *   - RFID RC522        : SS=15, RST=22
 *   - TFT ILI9341       : CS=5  (via TFT_eSPI)
 *   - DFPlayer Mini     : RX=16, TX=17 (HardwareSerial 2)
 *   - ESP-NOW Receiver  : Terima SensorData dari ESP32 #2
 *   - WiFi (STA)        : Untuk ESP-NOW (channel sama)
 *   - MQTT HiveMQ Cloud : Publish data via PubSubClient TLS
 *
 * Library yang dibutuhkan:
 *   - TFT_eSPI
 *   - MFRC522
 *   - DFRobotDFPlayerMini
 *   - PubSubClient
 *   - WiFiClientSecure
 *   - ArduinoJson
 *   - esp_now (built-in ESP-IDF)
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <esp_now.h>

#include <SPI.h>
#include <MFRC522.h>
#include <TFT_eSPI.h>
#include <DFRobotDFPlayerMini.h>
#include <ArduinoJson.h>

// ============================================================
// PIN — JANGAN DIUBAH
// ============================================================

#define TFT_CS    5
#define RFID_SS   15
#define RFID_RST  22
#define DFPLAYER_RX 16
#define DFPLAYER_TX 17

// ============================================================
// KONFIGURASI WIFI & MQTT — Sesuaikan dengan jaringan Anda
// ============================================================

const char* WIFI_SSID     = "Publik";
const char* WIFI_PASSWORD = "";

const char* MQTT_HOST   = "92dfec62ae7648038f50c5842ab6977b.s1.eu.hivemq.cloud";
const int   MQTT_PORT   = 8883;
const char* MQTT_USER   = "soto";
const char* MQTT_PASS   = "!Soto1234";
const char* MQTT_CLIENT = "esp32-soto-01";

// Topik MQTT — JANGAN diubah tanpa alasan
const char* TOPIC_SENSOR  = "soto/sensor/esp32-soto-01";  // publish data sensor
const char* TOPIC_COMMAND = "soto/device/esp32-soto-01/command"; // subscribe command
const char* TOPIC_UID     = "soto/device/esp32-soto-01/uid";     // publish UID pairing

// ============================================================
// Root CA HiveMQ (ISRG Root X1)
// Jika TLS gagal, ganti setCACert(ROOT_CA) dengan setInsecure()
// ============================================================
const char* ROOT_CA = R"EOF(
-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoBggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF+
0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX6U
A5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq+sW
T8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR3qyH
B5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j3x+UC
B5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b0SHzUv
KBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH0ahmbWn
OlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xDk3SzynTn
jh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaHWBfEbwrbw
qHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ13hef4Y53CI
rU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQEAwIBBjAPBgNV
HRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY9umbbjANBgkq
hkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu+ILlaS/V9lZL
ubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+GnY+EgPbk6ZGQ
3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X/qkSsisRcOj/KK
NFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCvjHIInNzktHCgKQ5
ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNbOhdjsnvzqvHu7Ur
TkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nxe5AW0wdeRlN8NwF
XH+//i80+KiGFQxbANQpQcqF+1GMh5hHCXlI2vqBaaxbEFIJVyIimfMXfFwNwIgF
OAdKqAjSkRGpGRhVFzxvMEEkMBhLBkFVB8cCb4rrTaWD4JXQP9T0fTnOUH0kXLN
u6kgQ/1GmSjCNABdBCBbIHY2J1aU7hFiSOXk8CbBjz1fZlBITJLVmWCb85PGX1M
Xyq7JaBmSM6TTFR/V6c3GKVvg5YcQ3dNHPaJN5C0yf0wMPEBEgaFJPsSmAHFVZlM
hRjVpEEMTGCx18sPgVHHrpLBIqhLIGNYT/g9fCUCr5KY0BFM/8TKDIs=
-----END CERTIFICATE-----
)EOF";

// ============================================================
// STRUKTUR DATA SENSOR — HARUS SAMA PERSIS DENGAN ESP32 #2
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

TFT_eSPI           tft = TFT_eSPI();
MFRC522            rfid(RFID_SS, RFID_RST);
HardwareSerial     dfPlayerSerial(2);
DFRobotDFPlayerMini dfPlayer;
WiFiClientSecure   wifiClient;
PubSubClient       mqttClient(wifiClient);

// ============================================================
// KONSTANTA LOGIKA
// ============================================================

const int   POIN_PER_BOTOL    = 1;       // 1 botol = 1 poin (mudah diubah)
const float TINGGI_REFERENSI  = 27.0;    // cm — tinggi maksimum tempat sampah
const int   KAPASITAS_WARNING = 80;      // % — peringatan hampir penuh
const unsigned long TIMEOUT_SENSOR_MS   = 5000;  // ms — ESP32 #2 dianggap offline
const unsigned long DEBOUNCE_RFID_MS    = 1500;  // ms — debounce tap RFID
const unsigned long INTERVAL_MQTT_MS    = 5000;  // ms — interval publish MQTT
const unsigned long TIMEOUT_TRANSAKSI_MS = 120000UL; // 2 menit — auto cancel transaksi

// ============================================================
// STATE MACHINE MESIN
// ============================================================
enum MesinState {
  STATE_STANDBY,      // Menunggu tap kartu
  STATE_WAIT_CHECK,   // Menunggu respon MQTT valid/tidaknya kartu
  STATE_TRANSAKSI,    // Kartu valid, sedang menerima botol
  STATE_SELESAI,      // Menampilkan hasil transaksi
};

MesinState stateMesin = STATE_STANDBY;

// ============================================================
// STATE MACHINE FSM BOTOL
// ============================================================
enum BottleStage {
  STAGE_0,   // idle, tidak ada botol
  STAGE_1,   // IR1 aktif
  STAGE_2,   // IR1 + IR2 aktif
  STAGE_3,   // hanya IR2 aktif (IR1 sudah lewat)
};

BottleStage bottleStage = STAGE_0;

// ============================================================
// VARIABEL STATE
// ============================================================

SensorData   data;
volatile bool dataBaru  = false;
bool          dataMasuk = false;
unsigned long waktuDataTerakhir = 0;

String uidAktif   = "";          // UID user yang sedang transaksi
int    jumlahBotol = 0;
int    totalPoin   = 0;

// Kapasitas tempat sampah
float  jarakRataRata = 0.0;
int    kapasitasPersen = 0;
bool   warningPlayed80  = false;  // flag agar audio warning tidak spam
bool   warningPlayed100 = false;

// RFID debounce
unsigned long lastRfidTime = 0;

// Pairing mode (dari MQTT command)
bool          scanningPairing = false;
unsigned long scanDeadline    = 0;

// Menunggu check RFID (timeout 5s)
unsigned long waitCheckMulai  = 0;

// MQTT publish interval
unsigned long lastMqttPublish = 0;

// TFT refresh — agar tidak redraw setiap loop
bool          needRedraw = true;

// Timer timeout transaksi
unsigned long transaksiMulai = 0;

// DFPlayer ready
bool dfPlayerReady = false;

// ============================================================
// FORWARD DECLARATIONS
// ============================================================
void tftStandby();
void tftTapKartu();
void tftKartuTidakTerdaftar();
void tftTransaksi();
void tftPenuh();
void tftHasilTransaksi();
void tftWaitCheck();
void prosesRFID(String uid);
void selesaikanTransaksi();
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

// ============================================================
// CALLBACK ESP-NOW — Jangan proses berat di sini
// ============================================================
void OnDataRecv(
  const esp_now_recv_info_t *info,
  const uint8_t *incomingData,
  int len
) {
  if (len != sizeof(SensorData)) {
    Serial.println("[ESP-NOW] Ukuran data tidak sesuai!");
    return;
  }
  memcpy(&data, incomingData, sizeof(SensorData));
  dataBaru = true;
  waktuDataTerakhir = millis();
}

// ============================================================
// CALLBACK MQTT
// ============================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String payloadStr = "";
  for (unsigned int i = 0; i < length; i++) {
    payloadStr += (char)payload[i];
  }

  Serial.println("[MQTT] Pesan masuk: " + String(topic));
  Serial.println("[MQTT] Payload: " + payloadStr);

  JsonDocument doc;
  if (deserializeJson(doc, payloadStr) != DeserializationError::Ok) {
    Serial.println("[MQTT] JSON tidak valid");
    return;
  }

  // --- Cek Command dari Laravel ---
  const char* action = doc["action"];
  if (action) {
    if (strcmp(action, "scan") == 0) {
      int expiresIn = doc["expires_in"] | 30;
      scanningPairing = true;
      scanDeadline    = millis() + ((unsigned long)expiresIn * 1000UL);
      Serial.println("[MQTT] Mode pairing RFID aktif, timeout: " + String(expiresIn) + "s");
    }
    return;
  }

  // --- Cek Hasil Verifikasi RFID (check_result) ---
  if (String(topic).endsWith("/check_result")) {
    if (stateMesin != STATE_WAIT_CHECK) {
      Serial.println("[MQTT] Hasil check diabaikan, mesin tidak sedang menunggu verifikasi");
      return;
    }

    String recvUid = doc["uid"] | "";
    bool   valid   = doc["valid"] | false;

    if (recvUid != uidAktif) {
      Serial.println("[MQTT] UID check_result tidak cocok dengan uidAktif");
      return;
    }

    if (valid) {
      // Kartu valid — mulai transaksi
      jumlahBotol   = 0;
      bottleStage   = STAGE_0;
      stateMesin    = STATE_TRANSAKSI;
      transaksiMulai = millis();
      needRedraw    = true;

      Serial.println("[RFID] Kartu valid, transaksi dimulai: " + uidAktif);
      playAudio(3);   // 0003.mp3: "Kartu berhasil dikenali"
      delay(2500);
      playAudio(6);   // 0006.mp3: "Silakan masukkan botol..."
      tftTransaksi();
    } else {
      // Kartu belum terdaftar
      Serial.println("[RFID] Kartu belum terdaftar: " + uidAktif);
      uidAktif = "";
      tftKartuTidakTerdaftar();
      playAudio(5);  // 0005.mp3: "Kartu belum terdaftar..."
      delay(4000);
      stateMesin = STATE_STANDBY;
      needRedraw = true;
      tftStandby();
    }
  }
}

// ============================================================
// AUDIO — Putar file dari folder MP3 di SD card
// ============================================================
void playAudio(int nomor) {
  if (!dfPlayerReady) {
    Serial.println("[DFPlayer] Tidak siap — audio dilewati");
    return;
  }
  Serial.println("[DFPlayer] Putar track: " + String(nomor));
  dfPlayer.playMp3Folder(nomor);
}

// ============================================================
// HITUNG KAPASITAS — Rata-rata sensor valid, abaikan -1
// ============================================================
void hitungKapasitas() {
  float total = 0;
  int   valid = 0;

  if (data.jarak1 >= 0) { total += data.jarak1; valid++; }
  if (data.jarak2 >= 0) { total += data.jarak2; valid++; }
  if (data.jarak3 >= 0) { total += data.jarak3; valid++; }
  if (data.jarak4 >= 0) { total += data.jarak4; valid++; }

  if (valid == 0) {
    jarakRataRata  = TINGGI_REFERENSI;
    kapasitasPersen = 0;
    return;
  }

  jarakRataRata  = total / valid;

  float persen = ((TINGGI_REFERENSI - jarakRataRata) / TINGGI_REFERENSI) * 100.0;
  if (persen < 0)   persen = 0;
  if (persen > 100) persen = 100;

  kapasitasPersen = (int)persen;
}

// ============================================================
// CEK WARNING KAPASITAS — Audio tidak spam
// ============================================================
void cekWarningKapasitas() {
  if (kapasitasPersen < 80)  { warningPlayed80  = false; }
  if (kapasitasPersen < 100) { warningPlayed100 = false; }

  if (kapasitasPersen >= 100 && !warningPlayed100) {
    warningPlayed100 = true;
    warningPlayed80  = true;
    Serial.println("[KAPASITAS] PENUH 100%!");
    playAudio(11); // 011.mp3
    needRedraw = true;
  } else if (kapasitasPersen >= 80 && !warningPlayed80) {
    warningPlayed80 = true;
    Serial.println("[KAPASITAS] Hampir penuh: " + String(kapasitasPersen) + "%");
    playAudio(10); // 010.mp3
    needRedraw = true;
  }
}

// ============================================================
// FSM BOTOL
// ============================================================
void fsmBotol() {
  if (stateMesin != STATE_TRANSAKSI) return;

  bool ir1 = (data.obstacle1 == LOW);
  bool ir2 = (data.obstacle2 == LOW);

  switch (bottleStage) {
    case STAGE_0:
      if (ir1 && !ir2) {
        bottleStage = STAGE_1;
        Serial.println("[FSM] STAGE_1: IR1 aktif");
      }
      break;

    case STAGE_1:
      if (ir1 && ir2) {
        bottleStage = STAGE_2;
        Serial.println("[FSM] STAGE_2: IR1+IR2 aktif");
      } else if (!ir1 && !ir2) {
        bottleStage = STAGE_0;
        Serial.println("[FSM] Reset ke STAGE_0 (botol ditarik)");
      }
      break;

    case STAGE_2:
      if (!ir1 && ir2) {
        bottleStage = STAGE_3;
        Serial.println("[FSM] STAGE_3: hanya IR2 aktif");
      } else if (!ir1 && !ir2) {
        bottleStage = STAGE_0;
        Serial.println("[FSM] Reset ke STAGE_0 (urutan tidak valid)");
      }
      break;

    case STAGE_3:
      if (!ir1 && !ir2) {
        bottleStage = STAGE_0;
        jumlahBotol++;

        Serial.println("=================================");
        Serial.println("[FSM] BOTOL BERHASIL MASUK!");
        Serial.println("[FSM] Jumlah botol: " + String(jumlahBotol));
        Serial.println("=================================");

        playAudio(7);
        delay(1500);
        playAudio(9);

        needRedraw = true;
      } else if (ir1 && !ir2) {
        bottleStage = STAGE_0;
        Serial.println("[FSM] Reset ke STAGE_0 (botol mundur dari STAGE_3)");
      }
      break;
  }
}

// ============================================================
// PROSES UID RFID
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
      Serial.println("[MQTT] UID pairing dikirim: " + uid);
      scanningPairing = false;
    } else {
      scanningPairing = false;
      Serial.println("[RFID] Pairing timeout, mode pairing dibatalkan");
    }
    return;
  }

  if (kapasitasPersen >= 100 && stateMesin == STATE_STANDBY) {
    Serial.println("[RFID] Tempat sampah penuh, transaksi ditolak");
    tftPenuh();
    playAudio(11);
    delay(3000);
    needRedraw = true;
    tftStandby();
    return;
  }

  if (stateMesin == STATE_STANDBY) {
    // -------------------------------------------------------
    // STATE STANDBY — Minta validasi RFID ke MQTT
    // -------------------------------------------------------
    Serial.println("[RFID] Meminta validasi RFID ke backend...");
    uidAktif = uid;
    stateMesin = STATE_WAIT_CHECK;
    waitCheckMulai = millis();
    tftWaitCheck();

    JsonDocument doc;
    doc["device_id"] = MQTT_CLIENT;
    doc["uid"]       = uid;
    char buf[128];
    serializeJson(doc, buf);
    
    String topicCheck = "soto/device/" + String(MQTT_CLIENT) + "/check";
    mqttClient.publish(topicCheck.c_str(), buf, false);
    return;
  }

  if (stateMesin == STATE_TRANSAKSI) {
    if (uid == uidAktif) {
      Serial.println("[RFID] Tap akhir — selesaikan transaksi");
      playAudio(12);
      delay(500);
      selesaikanTransaksi();
    } else {
      Serial.println("[RFID] Kartu berbeda diabaikan saat transaksi: " + uid);
    }
    return;
  }
}

// ============================================================
// SELESAIKAN TRANSAKSI
// ============================================================
void selesaikanTransaksi() {
  totalPoin  = jumlahBotol * POIN_PER_BOTOL;
  stateMesin = STATE_SELESAI;
  needRedraw = true;

  Serial.println("=================================");
  Serial.println("[TRANSAKSI] SELESAI");
  Serial.print  ("[TRANSAKSI] UID    : "); Serial.println(uidAktif);
  Serial.print  ("[TRANSAKSI] Botol  : "); Serial.println(jumlahBotol);
  Serial.print  ("[TRANSAKSI] Poin   : "); Serial.println(totalPoin);
  Serial.println("=================================");

  tftHasilTransaksi();
  playAudio(13);
  delay(2500);
  playAudio(14);

  // Kirim transaksi ke Laravel via MQTT
  kirimTransaksiKeAPI(uidAktif, jumlahBotol, totalPoin);

  // Publish telemetry
  publishMQTT();

  delay(5000);

  uidAktif    = "";
  jumlahBotol = 0;
  totalPoin   = 0;
  stateMesin  = STATE_STANDBY;
  needRedraw  = true;
  tftStandby();
}

// ============================================================
// KIRIM TRANSAKSI KE LARAVEL VIA MQTT
// ============================================================
void kirimTransaksiKeAPI(String uid, int botol, int poin) {
  if (!mqttClient.connected()) {
    Serial.println("[MQTT] Gagal kirim transaksi, MQTT tidak terhubung");
    return;
  }
  
  JsonDocument doc;
  doc["device_id"]    = MQTT_CLIENT;
  doc["uid"]          = uid;
  doc["jumlah_botol"] = botol;
  doc["total_poin"]   = poin;
  
  char buf[256];
  serializeJson(doc, buf);
  
  String topicTx = "soto/device/" + String(MQTT_CLIENT) + "/transaction";
  mqttClient.publish(topicTx.c_str(), buf, false);
  Serial.println("[MQTT] Transaksi berhasil dikirim ke " + topicTx);
}

// ============================================================
// PUBLISH DATA TELEMETRY KE MQTT
// ============================================================
void publishMQTT() {
  if (!mqttClient.connected()) return;

  JsonDocument doc;
  doc["device_id"]         = MQTT_CLIENT;
  doc["uid"]               = uidAktif;
  doc["jumlah_botol"]      = jumlahBotol;
  doc["total_poin"]        = totalPoin;
  doc["kapasitas"]         = kapasitasPersen;
  doc["jarak_rata_rata"]   = jarakRataRata;
  doc["jarak_us1"]         = data.jarak1;
  doc["jarak_us2"]         = data.jarak2;
  doc["jarak_us3"]         = data.jarak3;
  doc["jarak_us4"]         = data.jarak4;
  doc["obstacle1"]         = data.obstacle1;
  doc["obstacle2"]         = data.obstacle2;
  doc["status_transaksi"]  = (stateMesin == STATE_TRANSAKSI) ? "aktif" : (stateMesin == STATE_WAIT_CHECK ? "wait_check" : "standby");

  char buf[512];
  serializeJson(doc, buf);
  mqttClient.publish(TOPIC_SENSOR, buf, false);
  Serial.println("[MQTT] Telemetry dikirim ke: " + String(TOPIC_SENSOR));
}

// ============================================================
// TFT: MENUNGGU VERIFIKASI (Wait Check)
// ============================================================
void tftWaitCheck() {
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

// ============================================================
// TFT: STANDBY — Layar utama
// ============================================================
void tftStandby() {
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

  // Kapasitas
  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 65);
  tft.println("Kapasitas:");

  // Progress bar kapasitas (10 blok = 100%)
  tft.setCursor(15, 90);
  int blokIsi = kapasitasPersen / 10;
  String bar = "";
  for (int i = 0; i < 10; i++) {
    bar += (i < blokIsi) ? "#" : "-";
  }
  bar += " " + String(kapasitasPersen) + "%";

  if (kapasitasPersen >= 100) {
    tft.setTextColor(TFT_RED, TFT_BLACK);
  } else if (kapasitasPersen >= 80) {
    tft.setTextColor(TFT_ORANGE, TFT_BLACK);
  } else {
    tft.setTextColor(TFT_CYAN, TFT_BLACK);
  }
  tft.println(bar);

  // Status ESP-NOW
  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 120);
  if (dataMasuk && (millis() - waktuDataTerakhir < TIMEOUT_SENSOR_MS)) {
    tft.setTextColor(TFT_GREEN, TFT_BLACK);
    tft.println("SENSOR: OK");
  } else {
    tft.setTextColor(TFT_RED, TFT_BLACK);
    tft.println("SENSOR: OFFLINE");
  }

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 155);
  tft.println("----------------");

  // Instruksi
  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 180);
  tft.println("Tap kartu RFID");
  tft.setCursor(15, 210);
  tft.println("untuk memulai");

  digitalWrite(TFT_CS, HIGH);
}

// ============================================================
// TFT: TRANSAKSI AKTIF
// ============================================================
void tftTransaksi() {
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

  // Jumlah botol
  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 65);
  tft.print("Botol: ");
  tft.setTextColor(TFT_CYAN, TFT_BLACK);
  tft.println(jumlahBotol);

  // Kapasitas
  tft.setTextColor(TFT_YELLOW, TFT_BLACK);
  tft.setCursor(15, 95);
  tft.print("Kapasitas: ");
  if (kapasitasPersen >= 100) {
    tft.setTextColor(TFT_RED, TFT_BLACK);
  } else if (kapasitasPersen >= 80) {
    tft.setTextColor(TFT_ORANGE, TFT_BLACK);
  } else {
    tft.setTextColor(TFT_CYAN, TFT_BLACK);
  }
  tft.print(kapasitasPersen);
  tft.println("%");

  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(15, 125);
  tft.println("----------------");

  // Instruksi
  tft.setTextColor(TFT_WHITE, TFT_BLACK);
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
}

// ============================================================
// TFT: KARTU TIDAK TERDAFTAR
// ============================================================
void tftKartuTidakTerdaftar() {
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

// ============================================================
// TFT: TEMPAT SAMPAH PENUH
// ============================================================
void tftPenuh() {
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

// ============================================================
// TFT: HASIL TRANSAKSI
// ============================================================
void tftHasilTransaksi() {
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
// BACA RFID — Non-blocking, debounce, chip select aware
// ============================================================
void bacaRFID() {
  // Timeout pairing mode
  if (scanningPairing && millis() >= scanDeadline) {
    scanningPairing = false;
    Serial.println("[RFID] Mode pairing expired");
  }

  // Debounce
  if (millis() - lastRfidTime < DEBOUNCE_RFID_MS) return;

  // Switch ke RFID
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, LOW);

  if (!rfid.PICC_IsNewCardPresent()) {
    digitalWrite(RFID_SS, HIGH);
    return;
  }

  if (!rfid.PICC_ReadCardSerial()) {
    digitalWrite(RFID_SS, HIGH);
    return;
  }

  // Bentuk UID — format HEX uppercase tanpa separator
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

  // Routing berdasarkan state
  prosesRFID(uid);
}

// ============================================================
// SETUP WIFI (untuk ESP-NOW, WiFi harus STA)
// ============================================================
void connectWifi() {
  Serial.print("[WiFi] Connecting to: ");
  Serial.println(WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    attempts++;
    if (attempts > 40) {
      Serial.println("\n[WiFi] Gagal — restart");
      ESP.restart();
    }
  }
  Serial.println();
  Serial.print("[WiFi] Terhubung! IP: ");
  Serial.println(WiFi.localIP());
}

// ============================================================
// SETUP MQTT
// ============================================================
void setupMqtt() {
  wifiClient.setCACert(ROOT_CA);
  // Jika TLS gagal, ganti baris di atas dengan: wifiClient.setInsecure();

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
      Serial.println("[MQTT] Subscribe: " + String(TOPIC_COMMAND));
      
      String topicCheckRes = "soto/device/" + String(MQTT_CLIENT) + "/check_result";
      mqttClient.subscribe(topicCheckRes.c_str(), 1);
      Serial.println("[MQTT] Subscribe: " + topicCheckRes);
    } else {
      Serial.println(" Gagal rc=" + String(mqttClient.state()));
      retries++;
      if (retries >= 5) {
        Serial.println("[MQTT] Gagal berulang, lanjut tanpa MQTT");
        return;
      }
      delay(3000);
    }
  }
}

void reconnectMqtt() {
  if (mqttClient.connected()) return;
  Serial.println("[MQTT] Reconnect...");
  connectMqtt();
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  delay(2000);

  Serial.println();
  Serial.println("==============================");
  Serial.println(" ESP32 #1 - SOTO CONTROLLER  ");
  Serial.println("==============================");

  // --- Chip Select ---
  pinMode(TFT_CS, OUTPUT);
  pinMode(RFID_SS, OUTPUT);
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, HIGH);

  // --- WiFi (STA mode untuk ESP-NOW + MQTT) ---
  connectWifi();
  Serial.print("[WiFi] MAC: ");
  Serial.println(WiFi.macAddress());

  // --- SPI ---
  SPI.begin(18, 19, 23); // SCK, MISO, MOSI

  // --- TFT ---
  Serial.println("[TFT] Init...");
  tft.init();
  tft.setRotation(2);
  tft.fillScreen(TFT_BLACK);
  tft.setTextSize(2);
  tft.setTextColor(TFT_GREEN, TFT_BLACK);
  tft.setCursor(25, 40);  tft.println("SOTO");
  tft.setTextColor(TFT_WHITE, TFT_BLACK);
  tft.setCursor(25, 80);  tft.println("Starting...");

  // --- RFID ---
  Serial.println("[RFID] Init...");
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS, LOW);
  rfid.PCD_Init();
  delay(100);
  rfid.PCD_DumpVersionToSerial();
  digitalWrite(RFID_SS, HIGH);

  // --- DFPlayer ---
  Serial.println("[DFPlayer] Init...");
  dfPlayerSerial.begin(9600, SERIAL_8N1, DFPLAYER_RX, DFPLAYER_TX);
  delay(1000);

  if (dfPlayer.begin(dfPlayerSerial)) {
    Serial.println("[DFPlayer] OK!");
    dfPlayerReady = true;
    dfPlayer.volume(25);
    dfPlayer.EQ(DFPLAYER_EQ_NORMAL);
  } else {
    Serial.println("[DFPlayer] GAGAL!");
    dfPlayerReady = false;
  }

  // --- ESP-NOW ---
  Serial.println("[ESP-NOW] Init...");
  if (esp_now_init() != ESP_OK) {
    Serial.println("[ESP-NOW] GAGAL!");
  } else {
    esp_now_register_recv_cb(OnDataRecv);
    Serial.println("[ESP-NOW] OK — menunggu data dari ESP32 #2");
  }

  // --- MQTT ---
  Serial.println("[MQTT] Init...");
  setupMqtt();

  // --- Tampilan awal ---
  tft.fillScreen(TFT_BLACK);

  // --- Audio selamat datang ---
  delay(500);
  playAudio(1);  // 0001.mp3: "Selamat datang di SOTO"
  delay(2500);
  playAudio(2);  // 0002.mp3: "Silakan tempelkan kartu Anda"

  tftStandby();
  needRedraw = false;

  Serial.println();
  Serial.println("==============================");
  Serial.println("       SYSTEM READY");
  Serial.println("==============================");
}

// ============================================================
// LOOP
// ============================================================
void loop() {

  // --- MQTT keep-alive ---
  if (!mqttClient.connected()) {
    reconnectMqtt();
  }
  mqttClient.loop();

  // --- Terima data ESP-NOW baru ---
  if (dataBaru) {
    dataBaru  = false;
    dataMasuk = true;

    // Hitung kapasitas dari sensor baru
    hitungKapasitas();
    cekWarningKapasitas();

    // Log singkat ke serial
    Serial.print("[SENSOR] US: ");
    Serial.print(data.jarak1); Serial.print("/");
    Serial.print(data.jarak2); Serial.print("/");
    Serial.print(data.jarak3); Serial.print("/");
    Serial.print(data.jarak4);
    Serial.print("  OB: ");
    Serial.print(data.obstacle1 == LOW ? "ADA" : "OK");
    Serial.print("/");
    Serial.print(data.obstacle2 == LOW ? "ADA" : "OK");
    Serial.print("  Kapasitas: ");
    Serial.print(kapasitasPersen);
    Serial.println("%");

    // Update TFT hanya saat standby (bukan saat transaksi agar tidak flicker)
    if (stateMesin == STATE_STANDBY) {
      needRedraw = true;
    }
  }

  // --- Cek koneksi sensor ESP-NOW (offline detection) ---
  bool sensorOnline = dataMasuk && (millis() - waktuDataTerakhir < TIMEOUT_SENSOR_MS);
  static bool prevSensorOnline = false;
  if (sensorOnline != prevSensorOnline) {
    prevSensorOnline = sensorOnline;
    Serial.println(sensorOnline ? "[ESP-NOW] SENSOR: CONNECTED" : "[ESP-NOW] SENSOR: DISCONNECTED");
    if (stateMesin == STATE_STANDBY) needRedraw = true;
  }

  // --- FSM Botol (hanya saat transaksi aktif) ---
  if (stateMesin == STATE_TRANSAKSI && dataMasuk) {
    static int prevBotol = -1;
    fsmBotol();
    if (jumlahBotol != prevBotol) {
      prevBotol  = jumlahBotol;
      needRedraw = true;
    }
  }

  // --- Timeout Wait Check (5 detik) ---
  if (stateMesin == STATE_WAIT_CHECK && (millis() - waitCheckMulai > 5000)) {
    Serial.println("[MQTT] Timeout menunggu balasan check_result dari server");
    uidAktif = "";
    stateMesin = STATE_STANDBY;
    needRedraw = true;
    tftStandby();
    playAudio(16); // Terjadi kesalahan
  }

  // --- Auto-timeout transaksi (2 menit tanpa aktivitas) ---
  if (stateMesin == STATE_TRANSAKSI &&
      (millis() - transaksiMulai > TIMEOUT_TRANSAKSI_MS)) {
    Serial.println("[TRANSAKSI] Timeout — dibatalkan otomatis");
    playAudio(15); // 015.mp3: "Transaksi dibatalkan"
    delay(2000);
    uidAktif    = "";
    jumlahBotol = 0;
    stateMesin  = STATE_STANDBY;
    needRedraw  = true;
  }

  // --- Baca RFID ---
  bacaRFID();

  // --- Redraw TFT jika dibutuhkan ---
  if (needRedraw) {
    needRedraw = false;
    switch (stateMesin) {
      case STATE_STANDBY:    tftStandby();     break;
      case STATE_TRANSAKSI:  tftTransaksi();   break;
      case STATE_SELESAI:    tftHasilTransaksi(); break;
    }
  }

  // --- Publish MQTT berkala ---
  if (millis() - lastMqttPublish >= INTERVAL_MQTT_MS) {
    lastMqttPublish = millis();
    publishMQTT();
  }

  delay(10);
}