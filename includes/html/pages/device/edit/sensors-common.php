<?php

/*
 * LibreNMS
 *
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk>
 * Copyright (c) 2017 Tony Murray <https://github.com/murrant>
 * Copyright (c) 2018 TheGreatDoc <https://github.com/TheGreatDoc>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

// FUA
?>
<div class="table-responsive">
    <table id="edit-sensors" class="table table-striped table-condensed table-bordered">
        <thead>
        <tr>
            <th data-column-id="sensor_class">Class</th>
            <th data-column-id="sensor_type">Type</th>
            <th data-column-id="sensor_descr">Description</th>
            <th data-column-id="sensor_current">Current</th>
            <th data-column-id="sensor_limit" data-formatter="sensor_limit">High</th>
            <th data-column-id="sensor_limit_warn" data-formatter="sensor_limit_warn">High warn</th>
            <th data-column-id="sensor_limit_low_warn" data-formatter="sensor_limit_low_warn">Low warn</th>
            <th data-column-id="sensor_limit_low" data-formatter="sensor_limit_low">Low</th>
            <th data-column-id="sensor_alert" data-sortable="false" data-searchable="false" data-formatter="sensor_alert">Alerts</th>
            <th data-column-id="sensor_reset" data-sortable="false" data-searchable="false" data-formatter="sensor_reset"></th>
        </tr>
        </thead>
    </table>
</div>
<form id="alert-reset">
<?php
$rollback = [];
foreach (dbFetchRows("SELECT `sensor_id`, `sensor_limit`, `sensor_limit_warn`, `sensor_limit_low_warn`, `sensor_limit_low`, `sensor_alert` FROM `$table` WHERE `device_id` = ? AND `sensor_deleted`='0' ORDER BY `sensor_id`", [$device['device_id']]) as $sensor) {
    $rollback[] = [
        'sensor_id' => $sensor['sensor_id'],
        'sensor_limit' => $sensor['sensor_limit'],
        'sensor_limit_warn' => $sensor['sensor_limit_warn'],
        'sensor_limit_low_warn' => $sensor['sensor_limit_low_warn'],
        'sensor_limit_low' => $sensor['sensor_limit_low'],
        'sensor_alert' => $sensor['sensor_alert'],
    ];
}

echo csrf_field();

$reset_payload_json = json_encode($rollback, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($reset_payload_json === false) {
    $reset_payload_json = '[]';
}
?>
<input type="hidden" name="type" value="<?php echo $ajax_prefix; ?>-alert-reset">
</form>
<script>
var resetPayload = <?php echo $reset_payload_json; ?>;

$(document).on('click', '#newThread', function (e) {
    e.preventDefault(); // preventing default click action

    var token = $('#alert-reset input[name="_token"]').val();
    var type = $('#alert-reset input[name="type"]').val();

    $.ajax({
        type: 'POST',
        url: 'ajax_form.php',
        data: {
            _token: token,
            type: type,
            reset_payload: JSON.stringify(resetPayload)
        },
        dataType: "json",
        success: function(data){
            if (data.status == 'ok') {
                toastr.success(data.message);
                setTimeout(function() {
                    location.reload(true);
                }, 1200);
            } else {
                toastr.error(data.message || 'Falha ao resetar valores');
            }

        },
        error:function(xhr){
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseText || ('Erro inesperado no reset (HTTP ' + xhr.status + ')'));
            toastr.error(msg);
        }
    });
});
</script>
<script>
var grid = $("#edit-sensors").bootgrid({
    ajax: true,
    rowCount: [50, 100, 250, -1],
    templates: {
        header: '<div id="{{ctx.id}}" class="{{css.header}}"><div class="row">\
                    <div class="col-sm-8 actionBar">\
                        <button id="newThread" class="btn btn-primary btn-sm" type="button">Reset values</button>\
                    </div>\
                    <div class="col-sm-4 actionBar"><p class="{{css.search}}"></p><p class="{{css.actions}}"></p></div>\
                </div></div>'
    },
    post: function () {
        return {
            id: "sensor-edit",
            device_id: <?php echo $device['device_id']; ?>,
            table: "<?php echo $table; ?>",
        };
    },
    url: "ajax_table.php",
    formatters: {
        "sensor_limit": function (column, row) {
            return "<div class='form-group has-feedback'><input type='text' class='form-control input-sm sensor' data-device_id='" + row.device_id + "' data-value_type='sensor_limit' data-sensor_id='" + row.sensor_id + "' data-default='" + row.sensor_limit + "' value='" + row.sensor_limit + "'></div>";
        },
        "sensor_limit_warn": function (column, row) {
            return "<div class='form-group has-feedback'><input type='text' class='form-control input-sm sensor' data-device_id='" + row.device_id + "' data-value_type='sensor_limit_warn' data-sensor_id='" + row.sensor_id + "' data-default='" + row.sensor_limit_warn + "' value='" + row.sensor_limit_warn + "'></div>";
        },
        "sensor_limit_low_warn": function (column, row) {
            return "<div class='form-group has-feedback'><input type='text' class='form-control input-sm sensor' data-device_id='" + row.device_id + "' data-value_type='sensor_limit_low_warn' data-sensor_id='" + row.sensor_id + "' data-default='" + row.sensor_limit_low_warn + "' value='" + row.sensor_limit_low_warn + "'></div>";
        },
        "sensor_limit_low": function (column, row) {
            return "<div class='form-group has-feedback'><input type='text' class='form-control input-sm sensor' data-device_id='" + row.device_id + "' data-value_type='sensor_limit_low' data-sensor_id='" + row.sensor_id + "' data-default='" + row.sensor_limit_low + "' value='" + row.sensor_limit_low + "'></div>";
        },
        "sensor_alert": function (column, row) {
            return "<input type='checkbox' name='alert-status' data-device_id='" + row.device_id + "' data-sensor_id='" + row.sensor_id + "' data-sensor_desc='" + row.sensor_descr_attr + "'" + (row.sensor_alert === '1' ? " checked" : "") + ">";
        },
        "sensor_reset": function (column, row) {
            var disabled = row.sensor_custom === 'No' ? ' disabled' : '';
            return "<a type='button' class='btn btn-danger btn-sm remove-custom" + disabled + "' name='remove-custom' data-sensor_id='" + row.sensor_id + "' data-sensor-alert='" + row.sensor_alert + "'>Reset</a>";
        }
    }
}).on("loaded.rs.jquery.bootgrid", function() {
    bindSensorActions();
});

function bindSensorActions() {
    $("[name='alert-status']").bootstrapSwitch('offColor', 'danger');
}

function isNumericOrEmpty(value) {
    if (value === null || value === undefined || value === '') {
        return true;
    }

    return /^-?\d+(\.\d+)?$/.test($.trim(String(value)));
}

$(document).on('focusin', '.sensor', function () {
    console.log("Saving value " + $(this).val());
    $(this).data('val', $(this).val());
});

$(document).on('blur keyup', '.sensor', function (e) {
    if (e.type === 'keyup' && e.keyCode !== 13) return;
    var prev = $(this).data('val');
    if (prev === undefined) {
        prev = $(this).attr('data-default');
    }
    var data = $(this).val();
    if(prev === data) return;
    if (! isNumericOrEmpty(data)) {
        toastr.error('Only numeric values are allowed');
        $(this).val(prev);
        return;
    }

    var sensor_type = $(this).attr('id');
    var device_id = $(this).data("device_id");
    var sensor_id = $(this).data("sensor_id");
    var value_type = $(this).data("value_type");
    var $this = $(this);
    $.ajax({
        type: 'POST',
            url: 'ajax_form.php',
            data: { type: "<?php echo $ajax_prefix; ?>-update", device_id: device_id, data: data, sensor_id: sensor_id , value_type: value_type},
            dataType: "json",
            success: function(data){
            if (data.status == 'ok') {
                $('.remove-custom[data-sensor_id='+sensor_id+']').removeClass('disabled');
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }

            },
            error:function(data){
                toastr.error(data.message);
            }
    });
});

$(document).on('switchChange.bootstrapSwitch', 'input[name="alert-status"]', function (event, state) {
    event.preventDefault();
    var $this = $(this);
    var device_id = $(this).data("device_id");
    var sensor_id = $(this).data("sensor_id");
    var sensor_desc = $(this).data("sensor_desc");
    $.ajax({
        type: 'POST',
            url: 'ajax_form.php',
            data: { type: "<?php echo $ajax_prefix; ?>-alert-update", device_id: device_id, sensor_id: sensor_id, sensor_desc: sensor_desc, state: state},
            dataType: "json",
            success: function(data){
                if (data.status != 'error') {
                    if (data.status == 'ok') {
                        toastr.success(data.message);
                    } else {
                        toastr.info(data.message);
                    }
                } else {
                    toastr.error(data.message);
                }
            },
                error:function(data){
                    toastr.error(data.message);
            }
    });
});
$(document).on('click', "[name='remove-custom']", function (event) {
    event.preventDefault();
    var $this = $(this);
    var sensor_id = $(this).data("sensor_id");
    var token = $('#alert-reset input[name="_token"]').val();
    var resetItem = null;
    for (var i = 0; i < resetPayload.length; i++) {
        if (String(resetPayload[i].sensor_id) === String(sensor_id)) {
            resetItem = resetPayload[i];
            break;
        }
    }

    if (! resetItem) {
        resetItem = {
            sensor_id: sensor_id,
            sensor_limit: $('.sensor[data-sensor_id="' + sensor_id + '"][data-value_type="sensor_limit"]').attr('data-default') || '',
            sensor_limit_warn: $('.sensor[data-sensor_id="' + sensor_id + '"][data-value_type="sensor_limit_warn"]').attr('data-default') || '',
            sensor_limit_low_warn: $('.sensor[data-sensor_id="' + sensor_id + '"][data-value_type="sensor_limit_low_warn"]').attr('data-default') || '',
            sensor_limit_low: $('.sensor[data-sensor_id="' + sensor_id + '"][data-value_type="sensor_limit_low"]').attr('data-default') || '',
            sensor_alert: String($this.data("sensor-alert") === undefined ? '1' : $this.data("sensor-alert"))
        };
    }

    $.ajax({
        type: 'POST',
            url: 'ajax_form.php',
            data: {
                _token: token,
                type: "<?php echo $ajax_prefix; ?>-alert-reset",
                reset_payload: JSON.stringify([resetItem])
            },
            dataType: "json",
            success: function(data){
                if (data.status === 'ok') {
                    toastr.success(data.message);
                    grid.bootgrid('reload');
                } else {
                    toastr.error(data.message || 'Falha no reset individual');
                }
            },
                error:function(xhr){
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseText || ('Erro inesperado no reset individual (HTTP ' + xhr.status + ')'));
                toastr.error(msg);
                }
    });
});
</script>
