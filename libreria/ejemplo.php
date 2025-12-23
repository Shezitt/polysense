<?php

require_once 'Libreria.php';

$libreria = new libreria();

// Definir léxico

$lexico = <<<EOT
PALABRA: [a-zA-Z]+
NUMERO: [0-9]+
SKIP: \s+
EOT;

// Definir gramática

$gramatica = <<<EOT
START: SENTENCIA
SENTENCIA: COMANDO ARGUMENTOS
SENTENCIA: COMANDO
COMANDO: PALABRA
ARGUMENTOS: ARGUMENTOS VALOR
ARGUMENTOS: VALOR
VALOR: PALABRA
VALOR: NUMERO
EOT;

$libreria->setLexico($lexico);
$libreria->setGramatica($gramatica);


// Registrar comandos y sus funciones asociadas

$libreria->addComando('mostrar', function($objeto) {
    echo "Mostrando: $objeto\n";
});

$libreria->addComando('abrir', function($objeto) {
    echo "Abriendo: $objeto\n";
});

$libreria->addComando('saludar', function($nombre, $apellido) {
    echo "¡Hola $nombre $apellido!\n";
});

$libreria->addComando('sumar', function(...$numeros) {
    $res = 0;
    foreach ($numeros as $num) {
        $res += (int)$num;
    }
    echo "La suma es: $res\n";
});

$libreria->build();



// PRUEBA DE EJECUCIÓN

// Es necesario utilizar ';' para separar múltiples sentencias
// La libreria verificara que cada sentencia cumpla con la gramática definida

$input = "sumar 5 5 3; saludar Juan Perez; abrir puerta; mostrar libro";

try {
    $libreria->validar($input);
    $libreria->ejecutar($input);
    echo "Comandos ejecutados correctamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}