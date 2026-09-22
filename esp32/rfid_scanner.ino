#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

// --- KONFIGURASI WIFI & SERVER ---
const char* ssid = "NAMA_WIFI_ANDA";
const char* password = "PASSWORD_WIFI_ANDA";

// Ubah ke IP komputer/server Laravel Anda (Gunakan IPv4 yang aktif)
const char* serverUrl = "http://127.0.0.1:8000/api/v1/rfid/uid"; 
const char* deviceId = "esp32-soto-01";
const char* apiKey = "secret_key_123";

// --- KONFIGURASI PIN RC522 ---
#define RST_PIN 22
#define SS_PIN 21
MFRC522 mfrc522(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  
  // Connect WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected to WiFi!");

  // Init SPI & RFID
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("Mesin RVM SOTO Ready. Silakan tap kartu...");
}

void loop() {
  // Cek apakah ada kartu baru yang didekatkan
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  // Baca UID dan konversi ke Hex String Uppercase
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) {
      uid += "0";
    }
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  
  Serial.println("Kartu terdeteksi! UID: " + uid);

  // Kirim ke Laravel
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    
    // Set Header
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Key", apiKey);
    
    // Set Body
    String jsonPayload = "{\"device_id\":\"" + String(deviceId) + "\",\"uid\":\"" + uid + "\"}";
    
    // Send POST
    int httpResponseCode = http.POST(jsonPayload);
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Response [" + String(httpResponseCode) + "]: " + response);
    } else {
      Serial.println("Error on sending POST: " + String(httpResponseCode));
    }
    
    http.end();
  } else {
    Serial.println("Error: WiFi Disconnected");
  }

  // Halt PICC dan berhenti mengenkripsi PCD agar bisa membaca ulang
  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();

  // Delay agar tidak terjadi spam request (Debounce)
  delay(2000); 
}