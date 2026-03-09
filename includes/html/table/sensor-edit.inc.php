<?php

use Illuminate\Support\Facades\DB;

if (! isset($vars['device_id']) || ! is_numeric($vars['device_id'])) {
    echo json_encode(['current' => $current, 'rowCount' => $rowCount, 'rows' => [], 'total' => 0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return;
}

$device_id = (int) $vars['device_id'];
$table = $vars['table'] ?? 'sensors';
$allowed_tables = ['sensors', 'wireless_sensors'];

if (! in_array($table, $allowed_tables, true)) {
    $table = 'sensors';
}

$response = [];

$query = DB::table($table)
    ->where('device_id', $device_id)
    ->where('sensor_deleted', 0);

if (isset($searchPhrase) && ! empty($searchPhrase)) {
    $query->where(function ($q) use ($searchPhrase): void {
        $search = "%$searchPhrase%";
        $q->where('sensor_class', 'like', $search)
            ->orWhere('sensor_type', 'like', $search)
            ->orWhere('sensor_descr', 'like', $search)
            ->orWhere('sensor_current', 'like', $search);
    });
}

$total = (clone $query)->count('sensor_id');
if (empty($total)) {
    $total = 0;
}

if (isset($_REQUEST['sort']) && is_array($_REQUEST['sort']) && ! empty($_REQUEST['sort'])) {
    $allowed_sort = [
        'sensor_class',
        'sensor_type',
        'sensor_descr',
        'sensor_current',
        'sensor_limit',
        'sensor_limit_warn',
        'sensor_limit_low_warn',
        'sensor_limit_low',
        'sensor_alert',
    ];
    foreach ($_REQUEST['sort'] as $column => $direction) {
        if (! in_array($column, $allowed_sort, true)) {
            continue;
        }

        $query->orderBy($column, strtolower((string) $direction) === 'desc' ? 'desc' : 'asc');
    }
} else {
    $query->orderBy('sensor_class')
        ->orderBy('sensor_type')
        ->orderBy('sensor_descr');
}

if (isset($current)) {
    $limit_low = ($current * $rowCount) - $rowCount;
    $limit_high = $rowCount;
}

if ($rowCount != -1) {
    $query->skip($limit_low)->take($limit_high);
}

foreach ($query->get() as $sensor) {
    $response[] = [
        'sensor_id' => $sensor->sensor_id,
        'device_id' => $sensor->device_id,
        'sensor_class' => clean_bootgrid($sensor->sensor_class),
        'sensor_type' => clean_bootgrid($sensor->sensor_type),
        'sensor_descr' => clean_bootgrid($sensor->sensor_descr),
        'sensor_descr_attr' => htmlspecialchars((string) $sensor->sensor_descr, ENT_QUOTES),
        'sensor_current' => clean_bootgrid($sensor->sensor_current) . ($sensor->sensor_class == 'temperature' ? '°C' : ''),
        'sensor_limit' => clean_bootgrid($sensor->sensor_limit),
        'sensor_limit_warn' => clean_bootgrid($sensor->sensor_limit_warn),
        'sensor_limit_low_warn' => clean_bootgrid($sensor->sensor_limit_low_warn),
        'sensor_limit_low' => clean_bootgrid($sensor->sensor_limit_low),
        'sensor_alert' => (string) $sensor->sensor_alert,
        'sensor_custom' => clean_bootgrid($sensor->sensor_custom),
    ];
}

$output = ['current' => $current, 'rowCount' => $rowCount, 'rows' => $response, 'total' => $total];
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
