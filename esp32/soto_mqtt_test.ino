/*
 * ============================================================
 * SOTO — Progress 1: ESP32 MQTT Test
 * ============================================================
 * Tujuan : Publish data sensor dummy ke HiveMQ Cloud
 *          melalui MQTT TLS (port 8883)
 *
 * Library yang dibutuhkan (Install via Library Manager):
 *   - PubSubClient by Nick O'Leary
 *   - ArduinoJson by Benoit Blanchon
 *
 * Board    : ESP32 (semua varian)
 * IDE      : Arduino IDE 2.x
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>

// ============================================================
// KONFIGURASI — Sesuaikan dengan kredensial Anda
// ============================================================

// WiFi
const char* WIFI_SSID     = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// HiveMQ Cloud
const char* MQTT_HOST     = "your-cluster.s1.eu.hivemq.cloud";
const int   MQTT_PORT     = 8883;
const char* MQTT_USERNAME = "your-hivemq-username";
const char* MQTT_PASSWORD = "your-hivemq-password";
const char* MQTT_CLIENT   = "esp32-soto-001";

// Topic
const char* TOPIC_PUBLISH = "soto/test";

// Device ID
const char* DEVICE_ID = "SOTO-001";

// Interval publish (ms)
const unsigned long PUBLISH_INTERVAL = 5000;

// ============================================================
// SERTIFIKAT ROOT CA — ISRG Root X1 (Let's Encrypt / HiveMQ)
// Dibutuhkan untuk verifikasi TLS ke HiveMQ Cloud
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
// GLOBAL OBJECTS
// ============================================================
WiFiClientSecure wifiClientSecure;
PubSubClient     mqttClient(wifiClientSecure);

unsigned long lastPublishTime = 0;

// ============================================================
// SETUP
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(500);

    Serial.println();
    Serial.println("========================================");
    Serial.println("   SOTO ESP32 MQTT Test — Progress 1   ");
    Serial.println("========================================");

    connectWifi();
    setupMqtt();
}

// ============================================================
// LOOP
// ============================================================
void loop() {
    // Pastikan koneksi MQTT tetap aktif
    if (!mqttClient.connected()) {
        Serial.println("[MQTT] Koneksi terputus, mencoba reconnect...");
        reconnectMqtt();
    }
    mqttClient.loop();

    // Publish data setiap PUBLISH_INTERVAL ms
    unsigned long now = millis();
    if (now - lastPublishTime >= PUBLISH_INTERVAL) {
        lastPublishTime = now;
        publishSensorData();
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

    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    Serial.print("[WiFi] Connected! IP: ");
    Serial.println(WiFi.localIP());
}

// ============================================================
// FUNGSI: Setup MQTT
// ============================================================
void setupMqtt() {
    // Pasang Root CA agar TLS bisa diverifikasi
    wifiClientSecure.setCACert(ROOT_CA);

    mqttClient.setServer(MQTT_HOST, MQTT_PORT);
    mqttClient.setKeepAlive(60);
    mqttClient.setBufferSize(1024); // Buffer cukup untuk payload JSON

    Serial.print("[MQTT] Connecting to HiveMQ Cloud: ");
    Serial.println(MQTT_HOST);

    connectMqtt();
}

// ============================================================
// FUNGSI: Connect MQTT (pertama kali)
// ============================================================
void connectMqtt() {
    while (!mqttClient.connected()) {
        Serial.print("[MQTT] Attempting connection...");

        bool ok = mqttClient.connect(MQTT_CLIENT, MQTT_USERNAME, MQTT_PASSWORD);

        if (ok) {
            Serial.println(" Connected!");
            Serial.println("[MQTT] Status: READY — akan publish setiap 5 detik");
            Serial.println("========================================");
        } else {
            Serial.print(" Failed, rc=");
            Serial.print(mqttClient.state());
            Serial.println(" — retry in 3s");
            delay(3000);
        }
    }
}

// ============================================================
// FUNGSI: Reconnect MQTT
// ============================================================
void reconnectMqtt() {
    int retries = 0;
    while (!mqttClient.connected() && retries < 5) {
        Serial.print("[MQTT] Reconnecting...");
        if (mqttClient.connect(MQTT_CLIENT, MQTT_USERNAME, MQTT_PASSWORD)) {
            Serial.println(" Reconnected!");
        } else {
            Serial.print(" Failed rc=");
            Serial.print(mqttClient.state());
            Serial.println(" — retry in 3s");
            delay(3000);
            retries++;
        }
    }

    if (!mqttClient.connected()) {
        Serial.println("[MQTT] Reconnect gagal. Restart WiFi...");
        WiFi.disconnect();
        delay(1000);
        connectWifi();
        connectMqtt();
    }
}

// ============================================================
// FUNGSI: Publish data sensor dummy ke HiveMQ
// ============================================================
void publishSensorData() {
    // --- Buat JSON payload ---
    JsonDocument doc;

    doc["device_id"] = DEVICE_ID;

    // Ultrasonic (data dummy — nanti ganti dengan analogRead/pulseIn)
    JsonObject ultrasonic   = doc["ultrasonic"].to<JsonObject>();
    ultrasonic["sensor_1"]  = 12.5;
    ultrasonic["sensor_2"]  = 25.2;
    ultrasonic["sensor_3"]  = 8.4;
    ultrasonic["sensor_4"]  = 40.1;

    // Obstacle (data dummy — nanti ganti dengan digitalRead)
    JsonObject obstacle   = doc["obstacle"].to<JsonObject>();
    obstacle["sensor_1"]  = true;
    obstacle["sensor_2"]  = false;

    // RFID (data dummy — nanti ganti dengan MFRC522 read)
    JsonObject rfid = doc["rfid"].to<JsonObject>();
    rfid["uid"]     = "A1B2C3D4";

    // DFPlayer (data dummy — nanti sesuaikan dengan status aktual)
    JsonObject dfplayer = doc["dfplayer"].to<JsonObject>();
    dfplayer["status"]  = "playing";
    dfplayer["track"]   = 1;

    // --- Serialize ke string ---
    char jsonBuffer[512];
    serializeJson(doc, jsonBuffer);

    // --- Publish ---
    bool published = mqttClient.publish(TOPIC_PUBLISH, jsonBuffer);

    Serial.println("--- PUBLISH ---");
    Serial.print("Topic  : ");
    Serial.println(TOPIC_PUBLISH);
    Serial.print("Payload: ");
    Serial.println(jsonBuffer);
    Serial.print("Status : ");
    Serial.println(published ? "OK" : "FAILED");
    Serial.println("---------------");
}
