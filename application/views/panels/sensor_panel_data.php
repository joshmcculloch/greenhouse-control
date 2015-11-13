<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<?php
$id = 0;
foreach($graphs as $graph):?>
    plot_<?php echo $id;?>.replot({data: [
    <?php foreach($graph['sensors'] as $sensor): ?>
        [

        <?php foreach($sensor['data'] as $datum) {
            //echo $datum['value'].',';
            echo '[ "'.$datum['time'].'", '.$datum['value'].'],';
        } ?>
        ],
    <?php endforeach; ?>]});
    <?php
    $id++;
endforeach; ?>
