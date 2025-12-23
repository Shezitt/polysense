<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

require_once base_path('libreria/Libreria.php');

class VoiceCommandService
{
    private $libreria;
    private $commandMap = [];

    public function __construct()
    {
        $this->libreria = new \Libreria();
        $this->setupLibreria();
        $this->loadCommandsFromDatabase();
    }

    private function setupLibreria()
    {
        $gramatica = "
            SENTENCIA: COMANDO ARGUMENTOS
            SENTENCIA: COMANDO
            COMANDO: PALABRA
            ARGUMENTOS: PALABRA ARGUMENTOS
            ARGUMENTOS: PALABRA
        ";

        $lexico = "
            PALABRA: [a-zA-ZáéíóúÁÉÍÓÚñÑ0-9_]+
            SKIP: [ \t\n]+
        ";

        $this->libreria->setGramatica($gramatica);
        $this->libreria->setLexico($lexico);
        $this->libreria->build();
    }

    
    private function loadCommandsFromDatabase()
    {
        $commands = DB::table('voice_commands')
            ->where('enabled', true)
            ->get();

        foreach ($commands as $command) {
            $triggers = array_map('trim', explode(',', $command->trigger));
            
            foreach ($triggers as $trigger) {
                $triggerKey = $this->normalizeText($trigger);
                
                $this->libreria->addComando($triggerKey, function() use ($command) {
                    return $this->executeCommand($command);
                });

                $this->commandMap[$triggerKey] = $command;
            }
        }
    }

    public function processVoiceInput(string $voiceText)
    {
        try {
            $normalizedText = $this->normalizeText($voiceText);
            
            $this->libreria->ejecutar($normalizedText);
            
            if (isset($this->commandMap[$normalizedText])) {
                return [
                    'success' => true,
                    'command' => $this->commandMap[$normalizedText],
                    'message' => 'Comando ejecutado correctamente'
                ];
            }

            return [
                'success' => true,
                'message' => 'Comando procesado'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Comando no reconocido'
            ];
        }
    }

    private function executeCommand($command)
    {
        
        return $command;
    }

    private function normalizeText(string $text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $text
        );
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return str_replace(' ', '_', $text);
    }


    public function getRegisteredCommands()
    {
        return $this->commandMap;
    }
}
