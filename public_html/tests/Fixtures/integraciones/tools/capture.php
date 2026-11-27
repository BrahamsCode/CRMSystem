<?php
// Graba respuestas del legacy (php -S en el contenedor s5-legacy-fixtures) para las pruebas de contrato.
// Uso: php capture.php <base_url> <out_dir> [<archivo de casos>]
$base = $argv[1] ?? 'http://127.0.0.1:8000';
$out = $argv[2] ?? '/work/fixtures';
@mkdir($out, 0777, true);

function call(string $base, array $case, ?string $jwt): array
{
    $headers = [];
    if (($case['auth'] ?? false) && $jwt) {
        $headers[] = 'Authorization: Bearer '.$jwt;
    }
    foreach ($case['headers'] ?? [] as $h) {
        $headers[] = $h;
    }
    $body = '';
    if (array_key_exists('json', $case)) {
        $body = json_encode($case['json'], JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
    } elseif (array_key_exists('form', $case)) {
        $body = http_build_query($case['form']);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    $ch = curl_init($base.$case['path']);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $case['method'],
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $rawHeaders = substr($raw, 0, $headerSize);
    $respBody = substr($raw, $headerSize);
    $get = function (string $name) use ($rawHeaders) {
        return preg_match('/^'.preg_quote($name, '/').':\s*(.*)$/mi', $rawHeaders, $m) ? trim($m[1]) : null;
    };

    return [
        'status' => $status,
        'content_type' => $get('Content-Type'),
        'location' => $get('Location'),
        'body' => $respBody,
    ];
}

// Tercer argumento opcional: otro archivo de casos (p. ej. tools/cases-tool.json)
$cases = json_decode(file_get_contents($argv[3] ?? __DIR__.'/cases.json'), true);
$manifest = [];
$jwt = null;
foreach ($cases as $case) {
    $res = call($base, $case, $jwt);
    if ($case['name'] === 'api_auth_ok') {
        $jwt = json_decode($res['body'], true)['jwt'] ?? null;
    }
    file_put_contents("$out/{$case['name']}.body", $res['body']);
    $entry = $case;
    $entry['response'] = [
        'status' => $res['status'],
        'content_type' => $res['content_type'],
        'location' => $res['location'],
        'body_file' => "{$case['name']}.body",
    ];
    $manifest[] = $entry;
    printf("%-45s %d %s %d bytes\n", $case['name'], $res['status'], $res['content_type'] ?? '-', strlen($res['body']));
}
file_put_contents("$out/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
