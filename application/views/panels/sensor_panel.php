<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<script>

    <?php
        $id = 0;
        foreach($graphs as $graph):?>
    var plot_<?php echo $id;?>;
    <?php
        $id++;
    endforeach; ?>

    $(document).ready(function(){


        <?php
        $id = 0;
        foreach($graphs as $graph):?>
        plot_<?php echo $id;?> = $.jqplot ('<?php echo $id; ?>_graph', [
            <?php foreach($graph['sensors'] as $sensor): ?>
            [

                <?php foreach($sensor['data'] as $datum) {
                     //echo $datum['value'].',';
                     echo '[ "'.$datum['time'].'", '.$datum['value'].'],';
                } ?>
            ],
            <?php endforeach; ?>
        ],{
            title: '<?php echo $graph['title'];?>',
            animate: true,
            legend: {
                location: 'e',
                placement: 'outsideGrid',
                show: true
            },
            series: [
                <?php foreach($graph['sensors'] as $sensor): ?>
                {label: '<?php echo $sensor['name']; ?>'},
                <?php endforeach; ?>
            ],
            axes: {
                xaxis: {
                    label: '<?php echo $graph['xaxis_title'];?>',
                    renderer:$.jqplot.DateAxisRenderer,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                },
                yaxis: {
                    label: '<?php echo $graph['yaxis_title'];?>',
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                }
            },
            highlighter: {
                show: true
            }
        });
        <?php
            $id++;
        endforeach; ?>




        $(window).bind('resize', function(event, ui) {
            <?php
            $id = 0;
            foreach($graphs as $graph):?>
            plot_<?php echo $id; ?>.replot( { resetAxes: true } );
            <?php
                $id++;
            endforeach; ?>
        });
    });
    function download(period) {
        $.getScript('/index.php/dashboard/graph/week');
    }
</script>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h3 class="panel-title pull-left" style="padding-top: 7.5px;">Sensor Data</h3><!--
                <div class="dropdown pull-right">
                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        Dropdown
                        <span class="caret"></span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">
                        <li><a href="#">Action</a></li>
                        <li><a href="#">Another action</a></li>
                        <li><a href="#">Something else here</a></li>
                        <li><a href="#">Separated link</a></li>
                    </ul>
                </div>
                <button class="btn btn-default pull-right" type="button" onclick="download('week')">
                    Get Week
                </button>-->

                <div class="btn-group pull-right">
                    <button class="btn btn-default btn-sm" onclick="$.getScript('/index.php/dashboard/graph/day');">Day</button>
                    <button class="btn btn-default btn-sm" onclick="$.getScript('/index.php/dashboard/graph/week');">Week</button>
                    <button class="btn btn-default btn-sm" onclick="$.getScript('/index.php/dashboard/graph/year');">Year</button>
                </div>
            </div>
            <div class="panel-body">
                <?php
                $id = 0;
                foreach($graphs as $graph):?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div id="<?php echo $id.'_graph'; ?>"></div>
                        </div>
                    </div>
                <?php
                    $id++;
                endforeach; ?>
            </div>
        </div>
    </div>
</div>