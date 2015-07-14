<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<style>
    canvas{
        cursor: pointer;
    }
    canvas:active{
        cursor: pointer;
    }
</style>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Schedule Management
                </h3>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-4">
                    <table class="table">
                        <tr>
                            <td id="slot">&nbsp;</td>
                            <td id="state"></td>
                        </tr>
                        <tr>
                            <td>Paint</td>
                            <td>
                                    <div class="btn-group btn-group-xs" role="group">
                                        <button class="btn btn-default btn-success" type="button" id="paint_button" onclick="toggle_paint()" style="width: 5em !important;">On</button>
                                    </div>
                            </td>
                        </tr>
                        <tr>
                            <td id="active-time"></td><td id="delay-time"></td>
                        </tr>
                        <tr>
                            <td><input id="active-slider" type="range" min="1" max="89"></td>
                            <td><input id="delay-slider" type="range" min="1" max="89"></td>
                        </tr>
                        <tr>
                            <td id="message" class="text-center" colspan="2"></td>
                        </tr>
                    </table>
                </div>
                <div class="col-xs-12 col-sm-8 text-center">
                <canvas id="schedule_canvas" width="350" height="480">

                </canvas>
                <script>
                    var days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                    var mouse_down = false;
                    var canvas;
                    var context;
                    var schedule = [];
                    var schedule_loaded = false;
                    var paint_state = 0

                    var slider_active_time = 0;
                    var slider_delay_time = 0;

                    function update_message() {
                        var message = "";
                        if (schedule_loaded == false) {
                            message = "<span class=\"text-warning\">Loading Schedule</span>";
                        } else if ((Number(slider_active_time) + Number(slider_delay_time)) > (30 * 60)) {
                            message = "<span class=\"text-danger\">Warning!<br>You have selected a switching pattern with a cycle longer the 30 minutes. This will result in the cycle not being fully completed.</span>"
                        } else {
                            message = "<span class=\"text-success\">Schedule Loaded</span>"
                        }
                        $("#message").html(message);
                    }

                    function update_sliders() {
                        slider_active_time = $("#active-slider").val();
                        slider_active_time = slider_active_time > 59 ? (slider_active_time-59)*60 : slider_active_time;
                        var active_string = slider_active_time < 60 ? slider_active_time + " Sec" : (slider_active_time / 60) + " Min"
                        $("#active-time").html(active_string);
                        slider_delay_time = $("#delay-slider").val();
                        slider_delay_time = slider_delay_time > 59 ? (slider_delay_time-59)*60 : slider_delay_time;
                        var delay_string = slider_delay_time < 60 ? slider_delay_time + " Sec" : (slider_delay_time / 60) + " Min"
                        $("#delay-time").html(delay_string);
                        update_message();
                    }

                    function toggle_paint() {
                        paint_state = (paint_state+1)%3;

                        console.log(paint_state);
                        $("#paint_button").removeClass("btn-success btn-danger btn-warning");
                        if (paint_state == 0) {
                            $("#paint_button").addClass("btn-success").text("On");
                        } else if (paint_state == 1) {
                            $("#paint_button").addClass("btn-warning").text("Switching");
                        } else {
                            $("#paint_button").addClass("btn-danger").text("Off");
                        }
                    }

                    function update_schedule(_day, _half_hour, _active_time, _delay_time) {
                        // If the schedule has changed
                        if (schedule[_day*48+_half_hour].active_time != _active_time ||
                            schedule[_day*48+_half_hour].delay_time != _delay_time) {
                            // Update Schedule
                            schedule[_day*48+_half_hour].active_time = _active_time;
                            schedule[_day*48+_half_hour].delay_time = _delay_time;
                            draw_schedule();

                            //Update the server's schedule
                            $.ajax({
                                url: '<?php echo base_url();?>index.php/dashboard/set_schedule',
                                method: "POST",
                                data: {
                                    actuator_id: <?php echo $actuator_id; ?>,
                                    day: _day,
                                    half_hour: _half_hour,
                                    active_time: _active_time,
                                    delay_time: _delay_time,
                                    <?php echo $this->security->get_csrf_token_name().': \''.$this->security->get_csrf_hash().'\''; ?>
                                }
                            });
                        }
                      }

                    function load_schedule() {
                        // Only update the schedule if the use is not entering new scheduling information.
                        // This stop the schedule for temporarily showing previous state.
                        if (mouse_down == false) {
                            $.ajax({
                                url: '<?php echo base_url();?>index.php/dashboard/get_schedule/<?php echo $actuator_id; ?>',
                                method: "get",
                                dataType: "json"
                            }).done(parse_schedule);
                        }
                    }

                    function parse_schedule(data, status) {
                        console.log(data, status);
                        if (status == 'success') {
                            schedule = data;
                            schedule_loaded = true;
                            draw_schedule();
                            update_message();
                        }
                    }

                    function draw_schedule() {
                        context.clearRect(0, 0, canvas.width, canvas.height);
                        if (schedule_loaded) {
                            for (var day = 0; day < 7; day++) {
                                for (var half_hour = 0; half_hour < 48; half_hour++) {
                                    context.lineWidth = 1;
                                    // No active time
                                    if (schedule[day*48+half_hour].active_time == 0){
                                        context.fillStyle = '#d9534f';
                                        context.strokeStyle = '#d43f3a';
                                        context.fillRect(day * 50, half_hour * 10, 49, 9);
                                        context.strokeRect(day * 50, half_hour * 10, 49, 9);
                                    }
                                    // No delay time
                                    else if (schedule[day*48+half_hour].delay_time == 0) {
                                        context.fillStyle = '#5cb85c';
                                        context.strokeStyle = '#4cae4c';
                                        context.fillRect(day * 50, half_hour * 10, 49, 9);
                                        context.strokeRect(day * 50, half_hour * 10, 49, 9);
                                    } else {
                                        var ratio = Number(schedule[day*48+half_hour].active_time) / (
                                                Number(schedule[day*48+half_hour].active_time) +
                                                Number(schedule[day*48+half_hour].delay_time)
                                            );
                                        //ratio = Math.max(Math.min(ratio, 0.95), 0.05);
                                        ratio = ratio *0.9 + 0.05;
                                        context.fillStyle = '#5cb85c';
                                        context.fillRect(day * 50, half_hour * 10, 49*(ratio), 9);
                                        context.fillStyle = '#d9534f';
                                        context.fillRect(day * 50+49*(ratio), half_hour * 10, 49*(1-ratio), 9);
                                        context.strokeStyle = '#d43f3a';
                                        context.strokeRect(day * 50, half_hour * 10, 49, 9);
                                    }
                                }
                            }
                        }
                    }

                    $(function(){
                        canvas = document.getElementById('schedule_canvas');
                        context = canvas.getContext('2d');




                        function getMousePos(canvas, evt) {
                            var rect = canvas.getBoundingClientRect();
                            return {
                                x: evt.clientX - rect.left,
                                y: evt.clientY - rect.top
                            };
                        }

                        document.addEventListener('mousedown', function(evt) {
                            mouse_down = true;
                        });

                        document.addEventListener('mouseup', function(evt) {
                            mouse_down = false;
                            load_schedule();
                        });

                        canvas.addEventListener('mousemove', function(evt) {
                            if (schedule_loaded) {
                                var mousePos = getMousePos(canvas, evt);
                                var day = Math.floor(mousePos.x / 50);
                                var day_name = days[day];
                                var half_hour = Math.floor(mousePos.y / 10);
                                var half_hour_name = Math.floor(half_hour / 2) + ":" + (half_hour % 2 ? "30" : "00");
                                if (mouse_down) {
                                    switch (paint_state) {
                                        case 0:
                                            update_schedule(day, half_hour, 1, 0);
                                            break;
                                        case 1:
                                            update_schedule(day, half_hour, slider_active_time, slider_delay_time);
                                            break;
                                        case 2:
                                            update_schedule(day, half_hour, 0, 1);
                                            break
                                    }
                                }
                                $("#slot").html(day_name + " " + half_hour_name);
                                if (schedule[day * 48 + half_hour].active_time == 0) {
                                    $("#state").html("<strong><span style=\"color: #d9534f\">Off</span></strong>");
                                } else if (schedule[day * 48 + half_hour].delay_time == 0){
                                    $("#state").html("<strong><span style=\"color: #5cb85c\">On</span></strong>");
                                } else {

                                    var active = schedule[day * 48 + half_hour].active_time;
                                    var delay = schedule[day * 48 + half_hour].delay_time;
                                    active = active < 60 ? active + " Sec" : (active / 60) + " Min"
                                    delay = delay < 60 ? delay + " Sec" : (delay / 60) + " Min"
                                    $("#state").html("<strong><span style=\"color: #5cb85c\">" + active + "</span> / <span style=\"color: #d9534f\">" + delay + "</span></strong>")
                                }


                            }
                        }, false);



                        $("#active-slider").on("change mousemove", update_sliders);
                        $("#delay-slider").on("change mousemove", update_sliders);
                        update_message();
                        update_sliders();
                        load_schedule();
                        setInterval(load_schedule, 5000);

                    })
                </script>
                </div>
            </div>
        </div>
    </div>
</div>