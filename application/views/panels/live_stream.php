<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-6">
                        <h3 class="panel-title">Live Stream</h3>
                    </div>
                    <div class="col-xs-6 text-right" style="height: 17px !important; ">
                        <div class="fb-like" data-href="https://www.facebook.com/GardeningwithNathan?fref=ts" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row" style="padding-left:inherit; padding-right: inherit;">
                    <div class="col-xs-12 col-md-8 text-center" style="padding: 0;">
                        <img id="camera1" style="width: 100%;" src="/images/camera_1.jpg"/>
                    </div>
                    <div class="col-xs-12 col-md-4 text-center" style="height: 100%; padding: 0;">
                        <div class="col-xs-12 text-center" style="padding: 0px;">
                            <img id="camera1" style="max-width: 100%;" src="/images/camera_2.jpg"/>
                        </div>
                        <div class="col-xs-12 text-center" style="padding: 0;">
                            <img id="camera1" style="max-width: 100%;" src="/images/camera_3.jpg"/>
                        </div>
                    </div>
                </div>
                <script>
                    setInterval(function() {
                        var myImageElement = document.getElementById('camera1');
                        myImageElement.src = '/images/camera_1.jpg?rand=' + Math.random();
                    }, 30000);
                </script>
            </div>
        </div>
    </div>
</div>