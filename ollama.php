<?php

function corregirConOllama($pregunta, $respuestaAlumno, $respuestaEsperada, $puntajeMaximo){

    $prompt = "
Eres un docente de Introducción a la Programación en C++.

Corrige la respuesta del estudiante.

Pregunta:
$pregunta

Respuesta esperada:
$respuestaEsperada

Respuesta del estudiante:
$respuestaAlumno

Puntaje máximo:
$puntajeMaximo

Devuelve SOLO un JSON válido con este formato:
{
  \"correcta\": true,
  \"puntaje\": 0,
  \"observacion\": \"comentario corto\"
}

Reglas:
- Si el código cumple la consigna, correcta debe ser true.
- Si tiene errores pequeños pero se entiende, puedes dar puntaje parcial.
- Si no responde la consigna, puntaje 0.
- El puntaje no puede ser mayor a $puntajeMaximo.
";

    $data = [
        "model" => "qwen2.5-coder",
        "prompt" => $prompt,
        "stream" => false,
        "format" => "json"
    ];

    $ch = curl_init("http://localhost:11434/api/generate");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if ($response === false) {
        return [
            "correcta" => false,
            "puntaje" => 0,
            "observacion" => "No se pudo conectar con Ollama."
        ];
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (!isset($json["response"])) {
        return [
            "correcta" => false,
            "puntaje" => 0,
            "observacion" => "Ollama no devolvió una respuesta válida."
        ];
    }

    $resultado = json_decode($json["response"], true);

    if (!$resultado) {
        return [
            "correcta" => false,
            "puntaje" => 0,
            "observacion" => "No se pudo interpretar la corrección de Ollama."
        ];
    }

    return [
        "correcta" => $resultado["correcta"] ?? false,
        "puntaje" => $resultado["puntaje"] ?? 0,
        "observacion" => $resultado["observacion"] ?? "Sin observación."
    ];
}
?>