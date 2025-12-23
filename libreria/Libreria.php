<?php
use Parle\{Parser, Lexer, Token};

class Libreria
{
    private Parser $parser;
    private Lexer $lexer;
    private $comandos = [];

    public function __construct() {
        $this->parser = new Parser();
        $this->lexer = new Lexer();
    }

    public function setGramatica($gramatica) {
        $lineas = explode("\n", $gramatica);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (!$linea) continue;
            [$izq, $der] = explode(":", $linea);
            $izq = trim($izq);
            $der = trim($der);
            $this->parser->push($izq, $der);
        }
    }

    public function setLexico($lexico) {
        $lineas = explode("\n", $lexico);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (!$linea) continue;
            [$nombre, $regex] = explode(":", $linea, 2);
            $nombre = trim($nombre);
            $regex = trim($regex);
            if ($nombre === "SKIP") {
                $this->lexer->push($regex, Token::SKIP);
            } else {
                $this->parser->token($nombre);
                $this->lexer->push($regex, $this->parser->tokenId($nombre));
            }
        }
    }

    public function build() {
        $this->parser->build();
        $this->lexer->build();
    }


    public function addComando(string $comando, callable $funcion) {
        $this->comandos[strtolower($comando)] = $funcion;
    }

    public function validar($input) {
        $lineas = explode(";", $input);
        foreach ($lineas as $linea) {
            $last = "";
            $linea = trim($linea);
            if (!$linea) continue;
            $this->parser->consume($linea, $this->lexer);
            do {
                switch ($this->parser->action) {
                    case Parser::ACTION_ERROR:
                        throw new Exception("Error de sintaxis en la sentencia: $linea");
                    case Parser::ACTION_REDUCE:
                        $last = "";
                        for ($i = 0; $i < $this->parser->sigilCount(); $i++) {
                            $last = $last . " " . $this->parser->sigil($i);
                        }
                        break;
                }
                $this->parser->advance();
            } while ($this->parser->action !== Parser::ACTION_ACCEPT);

            $last = trim($last);
            $salida = explode(" ", $last);

            $comando = array_shift($salida);
            $argumentos = $salida;
            $comandoLower = strtolower($comando);
            if (!isset($this->comandos[$comandoLower])) {
                throw new Exception("Comando no reconocido: $comando");
            }

        }
        return true;
    }

    public function ejecutar($input) {
        $lineas = explode(";", $input);
        foreach ($lineas as $linea) {
            $last = "";
            $linea = trim($linea);
            if (!$linea) continue;
            $this->parser->consume($linea, $this->lexer);
            do {
                switch ($this->parser->action) {
                    case Parser::ACTION_ERROR:
                        throw new Exception("Error de sintaxis en la sentencia: $linea");
                    case Parser::ACTION_REDUCE:
                        $last = "";
                        for ($i = 0; $i < $this->parser->sigilCount(); $i++) {
                            $last = $last . " " . $this->parser->sigil($i);
                        }
                        break;
                }
                $this->parser->advance();
            } while ($this->parser->action !== Parser::ACTION_ACCEPT);

            $last = trim($last);
            $salida = explode(" ", $last);

            $comando = array_shift($salida);
            $argumentos = $salida;

            $comandoLower = strtolower($comando);
            if (!isset($this->comandos[$comandoLower])) {
                throw new Exception("Comando no reconocido: $comando");
            }
            
            try {
                call_user_func_array($this->comandos[$comandoLower], $argumentos);
            } catch (Exception $e) {
                throw new Exception("Error al ejecutar el comando '$comando': " . $e->getMessage());
            } catch (ArgumentCountError $e) {
                throw new Exception("Número incorrecto de argumentos para el comando '$comando'");
            } catch (TypeError $e) {
                throw new Exception("Tipo de argumento incorrecto para el comando '$comando'");
            }
        }
    }

}
