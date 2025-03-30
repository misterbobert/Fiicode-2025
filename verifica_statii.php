require_once 'incarca_statii.php';

$apiKey = 'f5CfxGaxn8cC3jQYK8tgPnGvSaRnTdsZyGJV4HxE';
$agencyId = 1;

$stops = incarcaStatii($apiKey, $agencyId);

// Test vizual
echo "<pre>";
foreach (array_slice($stops, 0, 10) as $stop) {
    echo "ID: {$stop['stop_id']} | {$stop['stop_name']}\n";
}
echo "</pre>";
