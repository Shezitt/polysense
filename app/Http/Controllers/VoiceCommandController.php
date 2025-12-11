<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoiceCommandController extends Controller
{
    /**
     * Obtener todos los comandos de voz
     */
    public function index()
    {
        $commands = DB::table('voice_commands')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($commands);
    }

    /**
     * Obtener comandos activos para un módulo específico
     */
    public function getActiveCommands($module = 'all')
    {
        $commands = DB::table('voice_commands')
            ->where('enabled', true)
            ->where(function($query) use ($module) {
                $query->where('modules', 'like', "%{$module}%")
                      ->orWhere('modules', 'all');
            })
            ->get();

        return response()->json($commands);
    }

    /**
     * Crear un nuevo comando de voz
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|string|max:500',
            'action' => 'required|in:navigate,export,toggle,custom',
            'target' => 'nullable|string|max:255',
            'function_name' => 'nullable|string|max:255',
            'modules' => 'nullable|string|max:255',
            'enabled' => 'boolean'
        ]);

        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        $id = DB::table('voice_commands')->insertGetId($validated);

        return response()->json([
            'id' => $id,
            'message' => 'Comando creado exitosamente'
        ], 201);
    }

    /**
     * Obtener un comando específico
     */
    public function show($id)
    {
        $command = DB::table('voice_commands')->find($id);

        if (!$command) {
            return response()->json(['error' => 'Comando no encontrado'], 404);
        }

        return response()->json($command);
    }

    /**
     * Actualizar un comando existente
     */
    public function update(Request $request, $id)
    {
        $command = DB::table('voice_commands')->find($id);

        if (!$command) {
            return response()->json(['error' => 'Comando no encontrado'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|string|max:500',
            'action' => 'required|in:navigate,export,toggle,custom',
            'target' => 'nullable|string|max:255',
            'function_name' => 'nullable|string|max:255',
            'modules' => 'nullable|string|max:255',
            'enabled' => 'boolean'
        ]);

        $validated['updated_at'] = now();

        DB::table('voice_commands')
            ->where('id', $id)
            ->update($validated);

        return response()->json([
            'message' => 'Comando actualizado exitosamente'
        ]);
    }

    /**
     * Eliminar un comando
     */
    public function destroy($id)
    {
        $deleted = DB::table('voice_commands')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Comando no encontrado'], 404);
        }

        return response()->json([
            'message' => 'Comando eliminado exitosamente'
        ]);
    }

    /**
     * Activar/Desactivar un comando
     */
    public function toggle(Request $request, $id)
    {
        $command = DB::table('voice_commands')->find($id);

        if (!$command) {
            return response()->json(['error' => 'Comando no encontrado'], 404);
        }

        // Si se proporciona el estado enabled en el request, usarlo
        // Si no, alternar el estado actual
        $newStatus = $request->has('enabled') 
            ? (bool) $request->input('enabled') 
            : !$command->enabled;

        DB::table('voice_commands')
            ->where('id', $id)
            ->update([
                'enabled' => $newStatus,
                'updated_at' => now()
            ]);

        return response()->json([
            'enabled' => $newStatus,
            'message' => $newStatus ? 'Comando activado' : 'Comando desactivado'
        ]);
    }

    /**
     * Crear comandos de ejemplo (seed)
     */
    public function createDefaults()
    {
        $defaults = [
            [
                'name' => 'Ir al Módulo 1',
                'trigger' => 'módulo uno,ir al monitor,monitoreo',
                'action' => 'navigate',
                'target' => '/modulo1',
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Ir al Módulo 2',
                'trigger' => 'módulo dos,ir al historial,estadísticas',
                'action' => 'navigate',
                'target' => '/modulo2',
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Ir al Módulo 4',
                'trigger' => 'módulo cuatro,configurar voz,comandos de voz',
                'action' => 'navigate',
                'target' => '/modulo4',
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Exportar a Excel',
                'trigger' => 'exportar,descargar excel,guardar datos',
                'action' => 'export',
                'modules' => 'modulo2',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Ir al Inicio',
                'trigger' => 'inicio,página principal,home',
                'action' => 'navigate',
                'target' => '/',
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($defaults as $command) {
            DB::table('voice_commands')->insert($command);
        }

        return response()->json([
            'message' => 'Comandos predeterminados creados',
            'count' => count($defaults)
        ]);
    }
}
