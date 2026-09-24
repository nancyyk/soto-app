/*
 * ============================================================
 * SOTO — ESP32 RFID MQTT Scanner
 * ============================================================
 * Menggantikan rfid_scanner.ino (HTTP-based) dengan MQTT TLS.
 * ESP32 subscribe command dari Laravel, lalu publish UID kartu
 * ke HiveMQ Cloud saat mode scanning aktif.
 *
 * Library yang HARUS diinstall via Arduino Library Manager:
 *   - PubSubClient  by Nick O'Leary   (versi >= 2.8)
 *   - MFRC522       by GithubCommunity
 *   - ArduinoJson   by Benoit Blanchon (versi >= 6)
 *
 * Board : ESP32 (semua varian)
 * IDE   : Arduino IDE 2.x
 *
 * Pin RC522:
 *   SS  = GPIO 21
 *   RST = GPIO 22
 *   SCK = GPIO 18  (SPI default ESP32)
 *   MISO= GPIO 19  (SPI default ESP32)
 *   MOSI= GPIO 23  (SPI default ESP32)
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>

// ============================================================
// KONFIGURASI — Sesuaikan sebelum upload
// ============================================================

// WiFi
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

// HiveMQ Cloud — SAMA dengan kredensial di .env backend
const char* MQTT_HOST     = "92dfec62ae7648038f50c5842ab6977b.s1.eu.hivemq.cloud";
const int   MQTT_PORT     = 8883;
const char* MQTT_USER     = "soto";
const char* MQTT_PASS     = "!Soto1234";
const char* MQTT_CLIENT   = "esp32-soto-01";    // client ID unik untuk ESP32

// Device ID — harus sama dengan device_id di Laravel
const char* DEVICE_ID = "esp32-soto-01";

// Topik MQTT
const char* TOPIC_COMMAND = "soto/device/esp32-soto-01/command"; // subscribe
const char* TOPIC_UID     = "soto/device/esp32-soto-01/uid";     // publish

// Pin RC522
#define SS_PIN  21
#define RST_PIN 22

// Durasi scanning setelah terima command (30 detik, sama dengan Laravel)
const unsigned long SCAN_TIMEOUT_MS = 30000UL;

// Debounce: jeda setelah berhasil kirim UID (2 detik)
const unsigned long DEBOUNCE_MS = 2000UL;

// ============================================================
// Root CA — ISRG Root X1 (Let's Encrypt / HiveMQ Cloud)
// Sertifikat ini dipakai untuk verifikasi TLS.
//
// Jika koneksi TLS gagal saat testing, ganti baris:
//   wifiClient.setCACert(ROOT_CA);
// dengan:
//   wifiClient.setInsecure();
// lalu setelah berhasil, kembalikan ke setCACert.
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
// GLOBAL OBJECTS & STATE
// ============================================================
WiFiClientSecure wifiClient;
PubSubClient     mqttClient(wifiClient);
MFRC522          mfrc522(SS_PIN, RST_PIN);

// --- Flag & timer mode scanning ---
bool          scanning      = false;  // true = sedang aktif tunggu tap kartu
unsigned long scanDeadline  = 0;      // millis() saat scanning timeout
unsigned long lastDebounce  = 0;      // millis() saat terakhir kirim UID

// ============================================================
// SETUP
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(500);

    Serial.println();
    Serial.println("╔══════════════════════════════════════════╗");
    Serial.println("║    SOTO — ESP32 RFID MQTT Scanner        ║");
    Serial.println("║    Versi MQTT TLS via HiveMQ Cloud       ║");
    Serial.println("╚══════════════════════════════════════════╝");
    Serial.println();

    // 1. Hubungkan WiFi
    connectWifi();

    // 2. Setup TLS dan hubungkan MQTT
    setupMqtt();

    // 3. Inisialisasi SPI dan RC522
    SPI.begin();
    mfrc522.PCD_Init();

    Serial.println("[RC522] RFID reader siap (SS=21, RST=22)");
    Serial.println("[INFO]  Menunggu command scan dari Laravel...");
    Serial.println();
}

// ============================================================
// LOOP
// ============================================================
void loop() {
    // --- Auto-reconnect WiFi ---
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[WiFi] Koneksi terputus, reconnecting...");
        connectWifi();
    }

    // --- Auto-reconnect MQTT ---
    if (!mqttClient.connected()) {
        Serial.println("[MQTT] Koneksi terputus, reconnecting...");
        reconnectMqtt();
    }

    // Proses pesan MQTT yang masuk (callback dipanggil di sini)
    mqttClient.loop();

    // --- Cek timeout mode scanning ---
    if (scanning && millis() >= scanDeadline) {
        scanning = false;
        Serial.println("[SCAN] ⏰ Timeout 30 detik — mode scanning dinonaktifkan");
        Serial.println("[INFO] Menunggu command scan baru dari Laravel...");
    }

    // --- Baca kartu RFID (hanya saat mode scanning aktif) ---
    if (scanning) {
        checkRfidCard();
    }
}

// ============================================================
// FUNGSI: Baca dan Kirim UID Kartu RFID
// ============================================================
void checkRfidCard() {
    // Tidak ada kartu baru di dekat reader
    if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
        delay(50);
        return;
    }

    // Debounce: abaikan jika baru saja kirim UID
    if (millis() - lastDebounce < DEBOUNCE_MS) {
        mfrc522.PICC_HaltA();
        mfrc522.PCD_StopCrypto1();
        return;
    }

    // Baca UID dan konversi ke HEX uppercase
    String uid = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
        if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
        uid += String(mfrc522.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();

    Serial.println();
    Serial.println("[RC522] Kartu terdeteksi!");
    Serial.println("[RC522] UID: " + uid);

    // Publish UID ke HiveMQ Cloud
    publishUid(uid);

    // Nonaktifkan mode scanning setelah berhasil kirim
    scanning     = false;
    lastDebounce = millis();

    Serial.println("[SCAN] Mode scanning dinonaktifkan setelah kirim UID");
    Serial.println("[INFO] Menunggu command scan baru dari Laravel...");
    Serial.println();

    // Stop enkripsi dan halt kartu agar bisa baca ulang kartu lain
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
}

// ============================================================
// FUNGSI: Publish UID ke HiveMQ Cloud
// ============================================================
void publishUid(String uid) {
    // Buat JSON payload: {"device_id":"esp32-soto-01","uid":"43C210E2"}
    JsonDocument doc;
    doc["device_id"] = DEVICE_ID;
    doc["uid"]       = uid;

    char jsonBuffer[128];
    serializeJson(doc, jsonBuffer);

    Serial.println("[MQTT] Publish UID ke topik: " + String(TOPIC_UID));
    Serial.println("[MQTT] Payload: " + String(jsonBuffer));

    bool ok = mqttClient.publish(TOPIC_UID, jsonBuffer, false); // retain = false

    if (ok) {
        Serial.println("[MQTT] ✅ UID berhasil dikirim ke HiveMQ Cloud");
    } else {
        Serial.println("[MQTT] ❌ Gagal publish UID! State: " + String(mqttClient.state()));
        // Jangan nonaktifkan scanning jika gagal — biarkan user coba lagi
        scanning = true;
    }
}

// ============================================================
// FUNGSI: Callback MQTT — Terima Command dari Laravel
// ============================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    // Salin payload ke String
    String payloadStr = "";
    for (unsigned int i = 0; i < length; i++) {
        payloadStr += (char)payload[i];
    }

    Serial.println();
    Serial.println("[MQTT] ← Pesan masuk dari Laravel");
    Serial.println("[MQTT] Topik  : " + String(topic));
    Serial.println("[MQTT] Payload: " + payloadStr);

    // Parse JSON
    JsonDocument doc;
    DeserializationError err = deserializeJson(doc, payloadStr);
    if (err) {
        Serial.println("[MQTT] ⚠ JSON tidak valid: " + String(err.c_str()));
        return;
    }

    // Ambil field action
    const char* action = doc["action"];
    if (action == nullptr) {
        Serial.println("[MQTT] ⚠ Field 'action' tidak ditemukan");
        return;
    }

    // Proses command "scan" → aktifkan mode scanning
    if (strcmp(action, "scan") == 0) {
        int requestId = doc["request_id"] | 0;
        int expiresIn = doc["expires_in"] | 30;

        scanning     = true;
        scanDeadline = millis() + ((unsigned long)expiresIn * 1000UL);
        lastDebounce = 0; // reset debounce

        Serial.println("[SCAN] ✅ Mode scanning AKTIF!");
        Serial.println("[SCAN] Request ID : " + String(requestId));
        Serial.println("[SCAN] Timeout    : " + String(expiresIn) + " detik");
        Serial.println("[SCAN] Silakan tap kartu RFID ke reader...");
        Serial.println();
    } else {
        Serial.println("[MQTT] ⚠ Action tidak dikenal: " + String(action));
    }
}

// ============================================================
// FUNGSI: Setup MQTT — TLS + Server + Callback
// ============================================================
void setupMqtt() {
    // Pasang Root CA untuk verifikasi TLS ke HiveMQ Cloud
    // Jika gagal, coba ganti ke: wifiClient.setInsecure();
    wifiClient.setCACert(ROOT_CA);

    mqttClient.setServer(MQTT_HOST, MQTT_PORT);
    mqttClient.setCallback(mqttCallback);
    mqttClient.setKeepAlive(60);
    mqttClient.setBufferSize(512); // buffer cukup untuk payload JSON command

    Serial.print("[MQTT] Menghubungkan ke HiveMQ Cloud: ");
    Serial.println(MQTT_HOST);

    connectMqtt();
}

// ============================================================
// FUNGSI: Connect MQTT (pertama kali atau setelah reconnect)
// ============================================================
void connectMqtt() {
    int retries = 0;

    while (!mqttClient.connected()) {
        Serial.print("[MQTT] Connecting... (attempt " + String(retries + 1) + ")");

        bool ok = mqttClient.connect(MQTT_CLIENT, MQTT_USER, MQTT_PASS);

        if (ok) {
            Serial.println(" ✅ Terhubung!");

            // Subscribe ke topik command dari Laravel
            bool subOk = mqttClient.subscribe(TOPIC_COMMAND, 1); // QoS 1
            if (subOk) {
                Serial.println("[MQTT] ✅ Subscribe: " + String(TOPIC_COMMAND));
            } else {
                Serial.println("[MQTT] ❌ Gagal subscribe ke: " + String(TOPIC_COMMAND));
            }

            // Debug info
            Serial.print("[WiFi] IP   : ");
            Serial.println(WiFi.localIP());
            Serial.print("[WiFi] RSSI : ");
            Serial.println(String(WiFi.RSSI()) + " dBm");
            Serial.println("[INFO] ESP32 siap menerima command scan.");
            Serial.println();

        } else {
            int rc = mqttClient.state();
            Serial.println(" ❌ Gagal (rc=" + String(rc) + ")");
            Serial.println("[MQTT] rc codes: -4=TIMEOUT -3=CONN_DENIED -2=CONN_LOST -1=DISCONNECT 0=OK 1=PROTO 2=ID_REJECTED 3=SERVER_UNAVAIL 4=BAD_CRED 5=NOT_AUTHORIZED");

            retries++;
            if (retries >= 10) {
                Serial.println("[MQTT] Terlalu banyak gagal — restart WiFi lalu coba lagi");
                WiFi.disconnect();
                delay(2000);
                connectWifi();
                retries = 0;
            } else {
                delay(3000);
            }
        }
    }
}

// ============================================================
// FUNGSI: Reconnect MQTT (dipanggil dari loop saat koneksi drop)
// ============================================================
void reconnectMqtt() {
    connectMqtt(); // pakai fungsi yang sama

    // Jika mode scanning sedang aktif saat koneksi drop, reset
    if (scanning) {
        Serial.println("[SCAN] Koneksi drop saat scanning — mode scanning direset");
        scanning = false;
    }
}

// ============================================================
// FUNGSI: Connect WiFi
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

        if (attempts > 40) { // 20 detik
            Serial.println();
            Serial.println("[WiFi] ❌ Gagal connect — restart ESP32");
            ESP.restart();
        }
    }

    Serial.println();
    Serial.println("[WiFi] ✅ Terhubung!");
    Serial.print("[WiFi] IP   : ");
    Serial.println(WiFi.localIP());
    Serial.print("[WiFi] RSSI : ");
    Serial.println(String(WiFi.RSSI()) + " dBm");
}