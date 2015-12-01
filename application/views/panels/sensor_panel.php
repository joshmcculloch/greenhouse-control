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
            [null]
            /*
            <?php foreach($graph['sensors'] as $sensor): ?>
            [

                <?php foreach($sensor['data'] as $datum) {
                     //echo $datum['value'].',';
                     echo '[ "'.$datum['time'].'", '.$datum['value'].'],';
                } ?>
            ],
            <?php endforeach; ?>*/
        ],{
            title: '<?php echo $graph['title'];?>',
            animate: true,
            legend: {
                location: 'e',
                placement: 'outsideGrid',
                show: true
            },
            seriesDefaults: {
                showMarker: true,
                markerOptions: {
                    show: true,
                    style: 'filledCircle',
                    size: 4
                },
                rendererOptions: {
                    smooth: true
                }
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
                <h3 class="panel-title pull-left" style="padding-top: 7.5px;">Sensor Data</h3>
                <div class="btn-group pull-right">
                    <button class="btn btn-default btn-sm" onclick="$.getScript('/index.php/api/graph/<?php echo $greenhouse_id; ?>/day');">Day</button>
                    <button class="btn btn-default btn-sm" onclick="$.getScript('/index.php/api/graph/<?php echo $greenhouse_id; ?>/week');">Week</button>
                    <button class="btn btn-default btn-sm" onclick="$.getScript('/index.php/api/graph/<?php echo $greenhouse_id; ?>/year');">Year</button>
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