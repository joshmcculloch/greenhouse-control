<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<link rel="stylesheet" href="/js/CodeMirror/lib/codemirror.css">
<script src="/js/CodeMirror/lib/codemirror.js"></script>
<script src="/js/CodeMirror/addon/edit/matchbrackets.js"></script>
<script src="/js/CodeMirror/mode/python/python.js"></script>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Rule Management</h3>
            </div>
            <div class="panel-body">
                <textarea id="code" name="code">
self.state = self.schedule.lookup(sensors.current_time)

if (sensors.temp-1 < 23.5 and "9.30am" < sensors.current_time < "4.30pm"):
    self.state = State.on

if (sensors.temp-1 > 14.5 and sensors.door == State.open):
    alert("Low temperature, door is open")
</textarea>
                <script>
                    var editor;
                    $(function() {
                        editor = CodeMirror.fromTextArea(document.getElementById("code"), {
                            mode: {name: "python",
                                version: 3,
                                singleLineStringErrors: false},
                            lineNumbers: true,
                            indentUnit: 4,
                            matchBrackets: true
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>