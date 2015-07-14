<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<script>
    function set_control(id, mode){
        set_button(id, mode);
        set_status(id, "Sending...");

        $.ajax({
            url: '<?php echo base_url();?>index.php/control/set_control',
            method: "POST",
            dataType: "json",
            data: {
                id: id,
                mode: mode,
                <?php echo $this->security->get_csrf_token_name().': \''.$this->security->get_csrf_hash().'\''; ?>
            }
        }).done(parse_control);
    }

    function set_button(id, mode) {
        var _button = $("#button_"+id);
        _button.removeClass("btn-danger btn-info btn-success");
        switch(Number(mode)) {
            case 1:
                _button.addClass( "btn-info" );
                _button.text("On");
                break;
            case 2:
                _button.addClass( "btn-danger" );
                _button.text("Off");
                break;
            case 3:
                _button.addClass( "btn-success" );
                _button.text("Program");
                break;
            case 4:
                _button.addClass( "btn-warning" );
                _button.text("Disabled");
                break;
        }
        _button.append(' <span class="caret"></span>');
    }

    function set_status(id, status) {
        var _status = $("#status_"+id);
        _status.text(status);
    }

    function set_name(id, name) {
        var _name = $("#name_"+id);
        _name.text(name);
    }

    function get_control(){
        $.ajax({
            url: '<?php echo base_url();?>index.php/control/get_control',
            method: "GET",
            dataType: "json"
        }).done(parse_control);
    }

    function parse_control(data, status) {
        if (status == 'success') {
            for(i=0; i<data.length; i++){
                set_name(data[i].id, data[i].name);
                set_button(data[i].id, data[i].mode_id);
                set_status(data[i].id, data[i].status);
            }
        }
    }

    $(function() {
        setInterval(get_control, 1000);
    });
</script>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Remote Control</h3>
            </div>
            <table class="table">
                <tr>
                    <th class="col-xs-3  col-sm-3 col-md-3">System</th>
                    <th class="col-xs-4  col-sm-3 col-md-2">Mode</th>
                    <th class="col-xs-5  col-sm-6 col-md-7">Status</th>
                </tr>
                <?php foreach($actuators as $actuator): ?>
                    <tr>
                        <td id="name_<?php echo $actuator['id']; ?>">
                            <?php echo $actuator['name'];?>
                        </td>
                        <td>
                            <?php
                                $mode = "Off";
                                $color = "btn-danger";
                                if ($actuator['mode_id'] == 1) {
                                    $mode = "On";
                                    $color = "btn-info";
                                } else if ($actuator['mode_id'] == 2) {
                                    $mode = "Off";
                                    $color = "btn-danger";
                                } else if ($actuator['mode_id'] == 3) {
                                    $mode = "Program";
                                    $color = "btn-success";
                                } else if ($actuator['mode_id'] == 4) {
                                    $mode = "Disabled";
                                    $color = "btn-warning";
                                }

                            ?>
                            <div class="btn-group btn-group-justified btn-group-xs">
                                <div class="btn-group btn-group-xs" role="group">
                                    <button type="button" id="button_<?php echo $actuator['id']; ?>" class="btn btn-default <?php echo $color." "; ?> dropdown-toggle" data-toggle="dropdown"><?php echo $mode; ?>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <?php $can_edit = $this->User->get_level("remote-control/edit"); ?>
                                        <?php if ($actuator['mode_id'] != 4): ?>
                                            <li <?php if (!$can_edit) {echo ' class="disabled"';}?>>
                                                <a href="#/" <?php if ($can_edit) {echo 'onclick="set_control('.$actuator['id'].',1);';} ?>" class="relay_anchor">On</a>
                                            </li>
                                            <li <?php if (!$can_edit) {echo ' class="disabled"';}?>>
                                                <a href="#/" <?php if ($can_edit) {echo 'onclick="set_control('.$actuator['id'].',2);';} ?>" class="relay_anchor">Off</a>
                                            </li>
                                            <li <?php if (!$can_edit) {echo ' class="disabled"';}?>>
                                                <a href="#/" <?php if ($can_edit) {echo 'onclick="set_control('.$actuator['id'].',3);';} ?>" class="relay_anchor">Program</a>
                                            </li>
                                            <li class="divider" class="relay_anchor"></li>
                                        <?php endif; ?>
                                        <li <?php if (!$this->User->get_level("config/view")) {echo ' class="disabled"';}?>>
                                            <a href="/index.php/dashboard/configure/<?php echo $actuator['id'];?>">Configure</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                        <td id="status_<?php echo $actuator['id']; ?>">
                            <?php echo $actuator['status'];?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>