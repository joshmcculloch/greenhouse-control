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
            <div class="panel-body" style="padding:0">
                <div id="canvas_div" class="col-xs-12 col-m-8" onresize="resize_canvas()" style="padding:0;">
                    <canvas id="click_area" width="700" height="400"></canvas>
                </div>
                <div class="col-xs-12 col-m-4">
                    <div class="input-group">
                        <div class="input-group-btn">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Create Node <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                    <li><a href="#">Less Than</a></li>
                                    <li><a href="#">Greater Than</a></li>
                                    <li><a href="#">And</a></li>
                                    <li><a href="#">Or</a></li>
                                    <li><a href="#">Not</a></li>
                                </ul>
                            </div>
                            <button id="update_button" type="button" class="btn btn-info">Update Value</button>
                        </div>
                        <input id="node_value" type="text" class="form-control">
                        <div class="input-group-btn">
                            <button id="delete_button" type="button" class="btn btn-danger">Delete Node</button>
                        </div>
                    </div>
                </div>

                <script>
                    var db_nodes = <?php echo json_encode($nodes); ?>;
                    var db_nodetypes = <?php echo json_encode($nodetypes); ?>;
                    var db_links = <?php echo json_encode($links); ?>;

                    var type_colours = {"bool": "rgb(255,0,0)", "float": "rgb(0,255,0)"};

                    function Node(posx, posy, name, value, type) {
                        this.posx = posx;
                        this.posy = posy;
                        this.width = 100;
                        this.height = 40;
                        this.type = type;
                        this.text = name;
                        this.value = value;
                        this.active = false;
                        this.inputs = 0
                    }

                    Node.prototype.draw = function (context) {
                        context.beginPath();
                        context.lineWidth="2";
                        context.rect(this.posx, this.posy, this.width, this.height);
                        if (this.active) {
                            context.fillStyle="rgb(100,149,237)";
                            context.fill();
                        } else {
                            context.fillStyle="rgb(150,150,150)";
                            context.fill();
                        }
                        context.stroke();

                        //Inputs
                        if (this.type.intype != "none") {
                            for (var i = 0; i < this.type.input_count; i++) {
                                context.beginPath();
                                context.arc(this.posx, this.posy + (this.height / this.type.input_count) * (i + 0.5), 5, 0, 2 * Math.PI, false);
                                context.fillStyle = type_colours[this.type.intype];
                                context.fill();
                                context.lineWidth = 2;
                                context.strokeStyle = 'black';
                                context.stroke();
                            }
                        }

                        //Output
                        if (this.type.outtype != "none") {
                            context.beginPath();
                            context.arc(this.posx + this.width, this.posy + this.height / 2, 5, 0, 2 * Math.PI, false);
                            context.fillStyle = type_colours[this.type.outtype];
                            context.fill();
                            context.lineWidth = 2;
                            context.strokeStyle = 'black';
                            context.stroke();
                        }

                        context.fillStyle="black";
                        context.font = "10px serif";
                        context.fillText(this.text, this.posx + 5, this.posy + 15);
                        context.fillText(this.value, this.posx + 5, this.posy + 30);

                    };

                    Node.prototype.in_location = function () {
                        return {x: this.posx, y: this.posy + this.height/2};
                    };

                    Node.prototype.out_location = function () {
                        return {x: this.posx + this.width, y: this.posy + this.height/2};
                    };

                    Node.prototype.clicked = function (x,y) {
                        console.log(x,y);
                        if ( this.posx <= x && x < (this.posx+this.width) &&
                            this.posy <= y && y < (this.posy+this.height)) {
                            return true;
                        }
                        return false;
                    };

                    function Link(node_out, node_in) {
                        this.node_out = node_out;
                        this.node_in = node_in;
                        this.node_in.inputs += 1;
                    }

                    Link.prototype.unlink = function () {
                        this.node_in.inputs -= 1;
                    }

                    Link.prototype.draw = function (context) {
                        var out_location = this.node_out.out_location();
                        var in_location = this.node_in.in_location();
                        context.beginPath();
                        context.moveTo(out_location.x, out_location.y);
                        context.bezierCurveTo(out_location.x + 50, out_location.y,
                            in_location.x-50, in_location.y,
                            in_location.x, in_location.y);
                        context.lineWidth = 2;
                        context.strokeStyle = type_colours[this.node_out.type.outtype];
                        context.stroke();
                    };

                    var rule_ns = {
                        canvas: document.getElementById("click_area"),
                        context: undefined,
                        nodes: [],
                        links: [],
                        selectedNode: undefined,
                        activeNode: undefined,
                        dragging: false,
                        scrolling: false,
                        lastmousex: 0,
                        lastmousey: 0,
                        camerax: 0,
                        cameray: 0,
                        update_button: $("#update_button")[0],
                        delete_button: $("#delete_button")[0],
                        node_value: $("#node_value")[0]
                    }

                    function resize_canvas(){
                        rule_ns.canvas.width = $("#canvas_div").innerWidth();
                        draw();
                    }

                    rule_ns.context = rule_ns.canvas.getContext("2d");

                    rule_ns.canvas.addEventListener('mousedown', function (event) {
                        var rect = rule_ns.canvas.getBoundingClientRect();
                        var x = event.clientX - rect.left;
                        var y = event.clientY - rect.top;
                        rule_ns.lastmousex = x;
                        rule_ns.lastmousey = y;

                        // Find node that was clicked
                        for(var i=0; i<rule_ns.nodes.length; i++) {
                            if (rule_ns.nodes[i].clicked(x + rule_ns.camerax,y + rule_ns.cameray)) {
                                rule_ns.selectedNode = rule_ns.nodes[i];
                                break;
                            }
                        }
                        if (typeof(rule_ns.selectedNode) === "undefined") {
                            // We didn't find a node so the user must be scrolling
                            rule_ns.scrolling = true;
                        }
                        draw();
                    });

                    rule_ns.canvas.addEventListener('mousemove', function (event) {
                        var rect = rule_ns.canvas.getBoundingClientRect();
                        var x = event.clientX - rect.left;
                        var y = event.clientY - rect.top;
                        var diffx = x - rule_ns.lastmousex;
                        var diffy = y - rule_ns.lastmousey;

                        if (diffx == 0 && diffy == 0) {
                            return;
                        }

                        rule_ns.lastmousex = x;
                        rule_ns.lastmousey = y;

                        if (rule_ns.selectedNode) {
                            console.log("dragging");
                            rule_ns.dragging = true;
                            rule_ns.selectedNode.posx += diffx;
                            rule_ns.selectedNode.posy += diffy;
                        } else if (rule_ns.scrolling) {
                            rule_ns.camerax -= diffx;
                            rule_ns.cameray -= diffy;
                        }

                        draw();
                    });

                    rule_ns.canvas.addEventListener('mouseup', function (event) {
                        var rect = rule_ns.canvas.getBoundingClientRect();
                        var x = event.clientX - rect.left;
                        var y = event.clientY - rect.top;

                        if (typeof(rule_ns.selectedNode) !== "undefined") {
                            if (typeof(rule_ns.activeNode) !== "undefined" &&
                                rule_ns.activeNode != rule_ns.selectedNode &&
                                !rule_ns.dragging) {
                                var deleted = false;
                                //Check if this link already exists
                                for(var i=0; i<rule_ns.links.length; i++) {
                                    if (rule_ns.links[i].node_out == rule_ns.activeNode &&
                                        rule_ns.links[i].node_in == rule_ns.selectedNode) {
                                        //remove the item from links
                                        rule_ns.links[i].unlink();
                                        rule_ns.links.splice(i, 1);
                                        deleted = true;
                                    }
                                }
                                //Check the link is between two connectible nodes
                                console.log(!deleted,
                                rule_ns.activeNode.type.outtype != "none",
                                rule_ns.activeNode.type.intype != "none",
                                rule_ns.activeNode.type.outtype == rule_ns.selectedNode.type.intype,
                                rule_ns.selectedNode.inputs < rule_ns.selectedNode.type.input_count);

                                if (!deleted &&
                                    rule_ns.activeNode.type.outtype != "none" &&
                                    rule_ns.selectedNode.type.intype != "none" &&
                                    rule_ns.activeNode.type.outtype == rule_ns.selectedNode.type.intype &&
                                    rule_ns.selectedNode.inputs < rule_ns.selectedNode.type.input_count) {
                                        //Create a link
                                        rule_ns.links.push(new Link(rule_ns.activeNode, rule_ns.selectedNode));
                                }
                                rule_ns.activeNode.active = false;
                                rule_ns.activeNode = undefined;
                                rule_ns.selectedNode = undefined;

                            } else if (!rule_ns.selectedNode.active &&
                                !rule_ns.dragging) {
                                console.log("activating node");
                                rule_ns.selectedNode.active = true;
                                rule_ns.activeNode = rule_ns.selectedNode;
                                rule_ns.selectedNode = undefined;

                            } else if (rule_ns.selectedNode.active &&
                                !rule_ns.dragging) {
                                console.log("unactivating node");
                                rule_ns.selectedNode.active = false;
                                rule_ns.activeNode = undefined;
                                rule_ns.selectedNode = undefined;

                            } else if (rule_ns.dragging) {
                                console.log("dragging complete");
                                rule_ns.dragging = false;
                                rule_ns.selectedNode = undefined;
                            }

                        }
                        else if (rule_ns.scrolling) {
                            console.log("scrolling complete");
                            rule_ns.scrolling = false;
                        }
                        draw();
                    });


                    for(var i=0; i<db_nodes.length; i++) {
                        var node_type = undefined;
                        for(var j=0; j<db_nodetypes.length; j++) {
                            if (db_nodetypes[j].id == db_nodes[i].type_id) {
                                node_type = db_nodetypes[j];
                            }
                        }
                        var name = "Unkown name!";
                        if (Number(db_nodes[i].type_id) == 1 &&
                            Number(db_nodes[i].sensor_id) > 0) {
                            name = db_nodes[i].sensor_name;
                        } else if (Number(db_nodes[i].type_id) == 7 &&
                            Number(db_nodes[i].actuator_id) > 0) {
                            name = db_nodes[i].actuator_name;
                        } else {
                            name = node_type.name;
                        }
                        rule_ns.nodes.push(new Node(Number(db_nodes[i].xpos),Number(db_nodes[i].ypos), name, Number(db_nodes[i].value), node_type));
                    }

                    function update_ui () {
                        if (rule_ns.activeNode != undefined) {
                            $(rule_ns.node_value).val(rule_ns.activeNode.value);
                            if (rule_ns.activeNode.type.deletable == 0) {
                                $(rule_ns.delete_button).prop('disabled', true);
                                $(rule_ns.update_button).prop('disabled', true);
                            } else {
                                $(rule_ns.delete_button).prop('disabled', false);
                                $(rule_ns.update_button).prop('disabled', false);
                            }
                        }
                    }

                    function draw () {
                        rule_ns.context.clearRect(0,0,rule_ns.canvas.width,rule_ns.canvas.height);
                        //rule_ns.context.beginPath();
                        //rule_ns.context.rect(0,0,rule_ns.canvas.width,rule_ns.canvas.height);
                        //rule_ns.context.fillStyle = "rgb(75,75,75)"
                        //rule_ns.context.fill();

                        rule_ns.context.save();
                        rule_ns.context.translate(-rule_ns.camerax, -rule_ns.cameray);
                        for(var i=0; i<rule_ns.nodes.length; i++) {
                            rule_ns.nodes[i].draw(rule_ns.context);
                        }
                        for(var i=0; i<rule_ns.links.length; i++) {
                            rule_ns.links[i].draw(rule_ns.context);
                        }
                        rule_ns.context.restore();
                        update_ui();
                    }

                    resize_canvas();
                    draw();

                </script>
            </div>
        </div>
    </div>
</div>