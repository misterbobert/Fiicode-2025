<?php
function incarcaStatii($apiKey, $agencyId, $fisierLocal = 'statii_iasi.json') {
    // ✅ Dacă fișierul există și e valid, îl folosim direct
    if (file_exists($fisierLocal)) {
        $continut = file_get_contents($fisierLocal);
        $json = json_decode($continut, true);
        if (is_array($json)) {
            return $json;
        }
    }

    // 🔁 Dacă nu există sau e invalid, apelăm API-ul Tranzy
    $url = "https://api.tranzy.ai/v1/opendata/stops?agency_id=$agencyId";
    $headers = [
        "Accept: application/json",
        "X-API-KEY: $apiKey",
        "X-Agency-Id: $agencyId"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($resp, true);
    if (!$data || !isset($data['data'])) {
        echo "<pre>❌ Eroare la încărcarea din API. Răspuns brut:\n";
        var_dump($resp);
        echo "</pre>";
        return [];
    }

    // ✅ Salvăm local
    file_put_contents($fisierLocal, json_encode($data['data'], JSON_PRETTY_PRINT));
    return $data['data'];
}
