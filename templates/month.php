<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



$ga = $this->call_ga_api([
    array('date'),
    array('pageviews','visits'),
    'date',
    '',
    current_time('Y-m-01'),
    current_time('Y-m-d'),
    1,
    100
]);

if( !is_object($ga) ) {
    echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga.'</p>';
} else {
?>

    <h3><?php echo __('本月份統計：','show-google-analytics-widget') . current_time('Y-m-01') . ' ~ ' . current_time('Y-m-d')?></h3>

    <div id="mychart" style="height: 250px;"></div>

    <table class="">
    <tr>
        <th align="left"><?php _e('Pageviews','show-google-analytics-widget')?></th>
        <td><?php echo number_format($ga->getPageviews()) ?>
    </tr>
    <tr>
        <th align="left"><?php _e('Visits','show-google-analytics-widget')?></th>
        <td><?php echo number_format($ga->getVisits()) ?></td>
    </tr>
    </table>

    <script>
    new Morris.Line({
        element: 'mychart',
        data: [
    <?php
        foreach( $ga->getResults() as $k => $result) {
            if($k>0) echo ',';
            echo "{ x:'".substr($result,0,4).'-'.substr($result,4,2).'-'.substr($result,6)."', a: ".$result->getPageviews().", b: ".$result->getVisits()." }";
        }
    ?>
        ],
        xkey: 'x',
        ykeys: ['a','b'],
        labels: ['Pageview','Visits'],
        fillOpacity: 1.0
    });
    </script>

<?php
}