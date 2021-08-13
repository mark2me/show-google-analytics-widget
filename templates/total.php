<?php

/* 統計數據 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$ga = $this->call_ga_api([
    array('year'),
    array('pageviews','visits'),
    'year',
    '',
    (date('Y')-5).'-01-01',
    current_time('Y-m-d'),
    1,
    10

]);

if( !is_object($ga) ) {
    echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga.'</p>';
} else {

?>
    <h3><?php _e('歷年統計：','show-google-analytics-widget')?> <?php echo $ga->getStartDate() . ' ~ ' . $ga->getEndDate() ?></h3>
    <div id="mychart2" style="height: 250px;"></div>

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
    new Morris.Bar({
        element: 'mychart2',
        data: [
    <?php
        foreach( $ga->getResults() as $k => $result) {
            if($k>0) echo ',';
            echo "{ x:'".$result."', a: ".$result->getPageviews().", b: ".$result->getVisits()." }";
        }
    ?>
        ],
        xkey: 'x',
        ykeys: ['a','b'],
        labels: ['Pageview','Visits'],
    });
    </script>
<?php
}

