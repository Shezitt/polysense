#!/usr/bin/env python3
"""
Servidor WebSocket para reconocimiento de voz local
Integración entre Vosk (Python) y Laravel (JavaScript)
"""

import json
import queue
import threading
import sounddevice as sd
import vosk
from flask import Flask, render_template_string, request
from flask_socketio import SocketIO, emit
from pathlib import Path

app = Flask(__name__)
app.config['SECRET_KEY'] = 'polysense-voice-2025'
socketio = SocketIO(app, cors_allowed_origins="*")

class VoiceRecognitionServer:
    def __init__(self, model_path="model"):
        self.sample_rate = 16000
        self.q = queue.Queue()
        self.is_listening = False
        self.clients = set()
        self.audio_thread = None
        
        model_dir = Path(__file__).parent / model_path
        print(f"Cargando modelo desde {model_dir}...")
        self.model = vosk.Model(str(model_dir))
        print("Modelo cargado")
        
    def audio_callback(self, indata, frames, time, status):
        if status:
            print(f"Warning: {status}")
        self.q.put(bytes(indata))
    
    def process_audio(self):
        recognizer = vosk.KaldiRecognizer(self.model, self.sample_rate)
        recognizer.SetWords(True)
        
        try:
            with sd.RawInputStream(
                samplerate=self.sample_rate,
                blocksize=4000,  # Reducido para evitar buffer overflow
                dtype='int16',
                channels=1,
                callback=self.audio_callback
            ):
                
                while self.is_listening:
                    try:
                        data = self.q.get(timeout=0.5)
                        
                        if recognizer.AcceptWaveform(data):
                            result = json.loads(recognizer.Result())
                            text = result.get('text', '').strip()
                            
                            if text:
                                print(f"Reconocido: {text}")
                                socketio.emit('voice_command', {'text': text, 'confidence': 1.0})
                                
                    except queue.Empty:
                        continue
                    except Exception as e:
                        print(f"Error: {e}")
                        continue
                        
        except Exception as e:
            print(f"Error en stream de audio: {e}")
        finally:
            while not self.q.empty():
                try:
                    self.q.get_nowait()
                except:
                    pass
            print("Audio detenido")

voice_server = VoiceRecognitionServer()

@socketio.on('connect')
def handle_connect():
    voice_server.clients.add(request.sid)
    print(f"Cliente conectado (Total: {len(voice_server.clients)})")

@socketio.on('disconnect')
def handle_disconnect():
    voice_server.clients.discard(request.sid)
    print(f"Cliente desconectado (Total: {len(voice_server.clients)})")

if __name__ == '__main__':
    print("=" * 60)
    print("SERVIDOR DE RECONOCIMIENTO DE VOZ")
    print("=" * 60)
    print("WebSocket: http://localhost:5001")
    print("=" * 60)
    
    voice_server.is_listening = True
    voice_server.audio_thread = threading.Thread(target=voice_server.process_audio, daemon=True)
    voice_server.audio_thread.start()
    
    socketio.run(app, host='0.0.0.0', port=5001, debug=False, allow_unsafe_werkzeug=True)
