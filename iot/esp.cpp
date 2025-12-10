#include "esp_camera.h"
#include <WiFi.h>
#include <WiFiUdp.h>
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"
#include "esp_wifi.h"

// ===========================
// CONFIGURACIÓN WIFI
// ===========================
const char* ssid = "FLIA-VILLCA";
const char* password = "4722ab+32m";

// ===========================
// CONFIGURACIÓN UDP
// ===========================
const char* serverIP = "144.22.56.85";
const int serverPort = 5001;  // Puerto UDP (cambió a 5001)
const char* nodeId = "CAM_001";  // Cambiar para cada cámara: CAM_002, CAM_003, etc.

// ===========================
// OPTIMIZACIÓN DE VELOCIDAD
// ===========================
#define MAX_UDP_PACKET 1400       // Tamaño máximo de paquete UDP
#define JPEG_QUALITY 12           // 10-25 (menor = mejor calidad, más datos)
#define TARGET_FPS 30             // FPS objetivo (ajustar según WiFi)
#define FRAME_SIZE FRAMESIZE_HVGA // HVGA=480x320, CIF=400x296, QVGA=320x240

// ===========================
// PINES AI THINKER
// ===========================
#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22
#define FLASH_GPIO_NUM     4
#define RED_LED_GPIO_NUM   33

WiFiUDP udp;
unsigned long lastFrameTime = 0;
unsigned long frameCount = 0;
unsigned long frameInterval = 1000 / TARGET_FPS;
unsigned long lastStatsTime = 0;

// Estructura de header optimizada para UDP
struct __attribute__((packed)) PacketHeader {
  uint32_t frameId;
  uint16_t packetNum;
  uint16_t totalPackets;
  uint32_t frameSize;
  char nodeId[12];
};

void setup() {
  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0);
  
  // Configurar y apagar LEDs
  pinMode(FLASH_GPIO_NUM, OUTPUT);
  pinMode(RED_LED_GPIO_NUM, OUTPUT);
  digitalWrite(FLASH_GPIO_NUM, LOW);
  digitalWrite(RED_LED_GPIO_NUM, HIGH);

  Serial.begin(115200);
  Serial.println("\n╔════════════════════════════════════════════════╗");
  Serial.println("║   ESP32-CAM ULTRA-RÁPIDO (UDP OPTIMIZADO)      ║");
  Serial.println("╚════════════════════════════════════════════════╝\n");

  // ===========================
  // CONFIGURACIÓN DE CÁMARA OPTIMIZADA
  // ===========================
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;
  config.grab_mode = CAMERA_GRAB_LATEST; // Crítico: siempre frame más reciente
  config.fb_location = CAMERA_FB_IN_PSRAM;

  if(psramFound()){
    config.frame_size = FRAME_SIZE;
    config.jpeg_quality = JPEG_QUALITY;
    config.fb_count = 2; // Double buffering
    Serial.println("✓ PSRAM detectada");
    Serial.println("✓ Modo: Alta Velocidad");
  } else {
    config.frame_size = FRAMESIZE_CIF;
    config.jpeg_quality = JPEG_QUALITY + 5;
    config.fb_count = 1;
    Serial.println("⚠ Sin PSRAM");
    Serial.println("✓ Modo: Estándar");
  }

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("✗ ERROR: Cámara falló (0x%x)\n", err);
    Serial.println("Reiniciando en 5 segundos...");
    delay(5000);
    ESP.restart();
  }
  Serial.println("✓ Cámara inicializada");

  // ===========================
  // OPTIMIZACIÓN DEL SENSOR
  // ===========================
  sensor_t * s = esp_camera_sensor_get();
  s->set_framesize(s, FRAME_SIZE);
  s->set_quality(s, JPEG_QUALITY);
  
  // Orientación
  s->set_vflip(s, 0);
  s->set_hmirror(s, 0);
  
  // Optimizaciones de velocidad
  s->set_brightness(s, 0);
  s->set_contrast(s, 0);
  s->set_saturation(s, 0);
  s->set_sharpness(s, 0);
  s->set_denoise(s, 0);
  s->set_gainceiling(s, (gainceiling_t)6);
  s->set_colorbar(s, 0);
  s->set_whitebal(s, 1);
  s->set_gain_ctrl(s, 1);
  s->set_exposure_ctrl(s, 1);
  s->set_hmirror(s, 0);
  s->set_vflip(s, 0);
  s->set_awb_gain(s, 1);
  s->set_agc_gain(s, 0);
  s->set_aec_value(s, 300);
  s->set_special_effect(s, 0);
  s->set_wb_mode(s, 0);
  s->set_ae_level(s, 0);
  s->set_dcw(s, 1);
  s->set_bpc(s, 0);
  s->set_wpc(s, 1);
  s->set_raw_gma(s, 1);
  s->set_lenc(s, 1);
  
  Serial.println("✓ Sensor optimizado");

  digitalWrite(FLASH_GPIO_NUM, LOW);
  digitalWrite(RED_LED_GPIO_NUM, HIGH);

  // ===========================
  // CONEXIÓN WIFI OPTIMIZADA
  // ===========================
  Serial.print("\nConectando WiFi");
  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false); // Crítico: sin ahorro energía
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 40) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if(WiFi.status() != WL_CONNECTED) {
    Serial.println("\n✗ ERROR: WiFi no conectado");
    Serial.println("Reiniciando en 5 segundos...");
    delay(5000);
    ESP.restart();
  }

  // Desactivar ahorro de energía WiFi
  esp_wifi_set_ps(WIFI_PS_NONE);
  
  // Configurar UDP buffer grande
  udp.begin(0);

  Serial.println("\n✓ WiFi Conectado!");
  Serial.print("  IP Local: ");
  Serial.println(WiFi.localIP());
  Serial.print("  RSSI: ");
  Serial.print(WiFi.RSSI());
  Serial.println(" dBm");
  Serial.printf("  Servidor: %s:%d\n", serverIP, serverPort);
  Serial.printf("  Node ID: %s\n", nodeId);
  Serial.printf("  Target FPS: %d\n", TARGET_FPS);
  Serial.printf("  Calidad JPEG: %d\n", JPEG_QUALITY);
  Serial.printf("  Resolución: %dx%d\n", 
                s->status.framesize == FRAMESIZE_HVGA ? 480 : 400,
                s->status.framesize == FRAMESIZE_HVGA ? 320 : 296);
  
  Serial.println("\n╔════════════════════════════════════════════════╗");
  Serial.println("║          STREAMING INICIADO                   ║");
  Serial.println("╚════════════════════════════════════════════════╝\n");
  
  delay(1000);
}

void sendFrameUDP(camera_fb_t *fb) {
  frameCount++;
  
  // Calcular paquetes necesarios
  const size_t headerSize = sizeof(PacketHeader);
  const size_t maxDataPerPacket = MAX_UDP_PACKET - headerSize;
  uint16_t totalPackets = (fb->len + maxDataPerPacket - 1) / maxDataPerPacket;
  
  // Enviar todos los paquetes
  for(uint16_t i = 0; i < totalPackets; i++) {
    PacketHeader header;
    header.frameId = frameCount;
    header.packetNum = i;
    header.totalPackets = totalPackets;
    header.frameSize = fb->len;
    strncpy(header.nodeId, nodeId, 11);
    header.nodeId[11] = '\0';
    
    size_t dataOffset = i * maxDataPerPacket;
    size_t dataSize = min((size_t)maxDataPerPacket, (size_t)(fb->len - dataOffset));
    
    // Enviar paquete UDP
    udp.beginPacket(serverIP, serverPort);
    udp.write((uint8_t*)&header, headerSize);
    udp.write(fb->buf + dataOffset, dataSize);
    udp.endPacket();
    
    // Micro-delay solo si hay muchos paquetes
    if(totalPackets > 15 && i < totalPackets - 1) {
      delayMicroseconds(50);
    }
  }
}

void loop() {
  // Mantener LEDs apagados
  digitalWrite(FLASH_GPIO_NUM, LOW);
  digitalWrite(RED_LED_GPIO_NUM, HIGH);

  unsigned long currentTime = millis();
  
  // Control preciso de FPS
  if (currentTime - lastFrameTime < frameInterval) {
    delayMicroseconds(200);
    return;
  }
  
  unsigned long frameStartTime = currentTime;
  lastFrameTime = currentTime;

  // Capturar frame
  camera_fb_t * fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("✗ Fallo captura");
    delay(10);
    return;
  }

  // Verificar WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠ WiFi desconectado, reconectando...");
    esp_camera_fb_return(fb);
    WiFi.reconnect();
    delay(1000);
    return;
  }

  // Enviar frame por UDP
  unsigned long sendStart = micros();
  sendFrameUDP(fb);
  unsigned long sendTime = micros() - sendStart;
  
  unsigned long totalTime = millis() - frameStartTime;
  float actualFPS = 1000.0 / totalTime;
  
  // Estadísticas cada 100 frames
  if(frameCount % 100 == 0 || currentTime - lastStatsTime > 5000) {
    lastStatsTime = currentTime;
    Serial.printf("┌─────────────────────────────────────────┐\n");
    Serial.printf("│ Frame: #%-6lu  Size: %-6u bytes  │\n", frameCount, fb->len);
    Serial.printf("│ FPS Real: %-5.1f   Target: %-2d FPS     │\n", actualFPS, TARGET_FPS);
    Serial.printf("│ Tiempo envío: %-5.1f ms                │\n", sendTime / 1000.0);
    Serial.printf("│ Tiempo total: %-5.1f ms                │\n", (float)totalTime);
    Serial.printf("│ RSSI: %-4d dBm                        │\n", WiFi.RSSI());
    Serial.printf("└─────────────────────────────────────────┘\n\n");
  }
  
  esp_camera_fb_return(fb);
  
  // Ajuste dinámico de FPS si es muy lento
  if(totalTime > frameInterval) {
    delayMicroseconds(100);
  }
}