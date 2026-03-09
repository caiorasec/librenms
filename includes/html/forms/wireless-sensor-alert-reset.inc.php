<?php

/*
 * LibreNMS
 *
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk>
 * Copyright (c) 2017 Tony Murray <https://github.com/murrant>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

header('Content-type: application/json');

// FUA

if (! Auth::user()->hasGlobalAdmin()) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Need to be admin',
    ]));
}

$status = 'error';
$message = 'Error resetting values';

$payload = json_decode($_POST['reset_payload'] ?? '', true);

if (is_array($payload) && ! empty($payload)) {
    foreach ($payload as $item) {
        $sensor_id = $item['sensor_id'] ?? null;
        if (! is_numeric($sensor_id)) {
            $status = 'error';
            $message = 'Invalid sensor id in payload';
            continue;
        }

        if (dbUpdate(
            [
                'sensor_limit' => set_null($item['sensor_limit'] ?? null),
                'sensor_limit_warn' => set_null($item['sensor_limit_warn'] ?? null),
                'sensor_limit_low_warn' => set_null($item['sensor_limit_low_warn'] ?? null),
                'sensor_limit_low' => set_null($item['sensor_limit_low'] ?? null),
                'sensor_alert' => (int) (($item['sensor_alert'] ?? 1) ? 1 : 0),
            ],
            'wireless_sensors',
            '`sensor_id` = ?',
            [$sensor_id]
        ) >= 0) {
            $status = 'ok';
            $message = 'Sensor values reset';
        } else {
            $message = 'Could not reset sensor values';
        }
    }
} else {
    $sensor_limit = $_POST['sensor_limit'] ?? [];
    $sensor_limit_warn = $_POST['sensor_limit_warn'] ?? [];
    $sensor_limit_low = $_POST['sensor_limit_low'] ?? [];
    $sensor_limit_low_warn = $_POST['sensor_limit_low_warn'] ?? [];
    $sensor_alert = $_POST['sensor_alert'] ?? [];
    $sensor_id = $_POST['sensor_id'] ?? [];

    if (is_array($sensor_id)) {
        $sensor_count = count($sensor_id);
        for ($x = 0; $x < $sensor_count; $x++) {
            if (dbUpdate(
                [
                    'sensor_limit' => set_null($sensor_limit[$x] ?? null),
                    'sensor_limit_warn' => set_null($sensor_limit_warn[$x] ?? null),
                    'sensor_limit_low_warn' => set_null($sensor_limit_low_warn[$x] ?? null),
                    'sensor_limit_low' => set_null($sensor_limit_low[$x] ?? null),
                    'sensor_alert' => (int) (($sensor_alert[$x] ?? 1) ? 1 : 0),
                ],
                'wireless_sensors',
                '`sensor_id` = ?',
                [$sensor_id[$x]]
            ) >= 0) {
                $status = 'ok';
                $message = 'Sensor values reset';
            } else {
                $message = 'Could not reset sensor values';
            }
        }
    } else {
        $message = 'Invalid sensor id';
    }
}

echo json_encode([
    'status' => $status,
    'message' => $message,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
