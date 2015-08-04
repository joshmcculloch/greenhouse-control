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
                <canvas id="click_area" width="700" height="400"></canvas>
                <script>

                    function Node(posx, posy, name) {
                        this.posx = posx;
                        this.posy = posy;
                        this.width = 100;
                        this.height = 40;
                        this.text = name;
                        this.state = "14.5c";
                        this.active = false;
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
                        context.fillStyle="black";
                        context.font = "10px serif";
                        context.fillText(this.text, this.posx + 5, this.posy + 15);
                        context.fillText(this.state, this.posx + 5, this.posy + 30);

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
                        context.strokeStyle = 'red';
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
                        cameray: 0
                    }

                    rule_ns.context = rule_ns.canvas.getContext("2d");

                    rule_ns.canvas.addEventListener('mousedown', function (event) {
                        var rect = rule_ns.canvas.getBoundingClientRect();
                        var x = event.clientX - rect.left;
                        var y = event.clientY - rect.top;
                        rule_ns.lastmousex = x;
                        rule_ns.lastmousey = y;


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
                                rule_ns.links.push(new Link(rule_ns.activeNode, rule_ns.selectedNode));
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

                    rule_ns.nodes.push(new Node(30,30,"Outside Temp"));

                    rule_ns.nodes.push(new Node(30,80,"Upper Temp"));

                    rule_ns.nodes.push(new Node(30,130,"Lower Temp"));

                    rule_ns.nodes.push(new Node(30,180,"Secondary Temp"));

                    rule_ns.nodes.push(new Node(30,230,"Outside Humid"));

                    rule_ns.nodes.push(new Node(30,280,"Upper Humid"));

                    rule_ns.nodes.push(new Node(30,330,"Lower Humid"));

                    rule_ns.nodes.push(new Node(30,380,"Secondary Humid"));

                    rule_ns.nodes.push(new Node(30,430,"Program On"));

                    rule_ns.nodes.push(new Node(200,180,"IF > 12"));

                    rule_ns.nodes.push(new Node(200,230,"IF < 23"));

                    rule_ns.nodes.push(new Node(370,205,"AND"));

                    rule_ns.nodes.push(new Node(530, 205,"Actuator On"));


                    rule_ns.links.push(new Link(rule_ns.nodes[0], rule_ns.nodes[9]));

                    rule_ns.links.push(new Link(rule_ns.nodes[1], rule_ns.nodes[10]));

                    rule_ns.links.push(new Link(rule_ns.nodes[9], rule_ns.nodes[11]));

                    rule_ns.links.push(new Link(rule_ns.nodes[10], rule_ns.nodes[11]));

                    rule_ns.links.push(new Link(rule_ns.nodes[11], rule_ns.nodes[12]));

                    rule_ns.links.push(new Link(rule_ns.nodes[8], rule_ns.nodes[11]));

                    function draw () {
                        rule_ns.context.clearRect(0,0,rule_ns.canvas.width,rule_ns.canvas.height);
                        rule_ns.context.beginPath();
                        rule_ns.context.rect(0,0,rule_ns.canvas.width,rule_ns.canvas.height);
                        rule_ns.context.fillStyle = "rgb(75,75,75)"
                        rule_ns.context.fill();

                        rule_ns.context.save();
                        rule_ns.context.translate(-rule_ns.camerax, -rule_ns.cameray);
                        for(var i=0; i<rule_ns.nodes.length; i++) {
                            rule_ns.nodes[i].draw(rule_ns.context);
                        }
                        for(var i=0; i<rule_ns.links.length; i++) {
                            rule_ns.links[i].draw(rule_ns.context);
                        }
                        rule_ns.context.restore();
                    }

                    draw();

                </script>
            </div>
        </div>
    </div>
</div>