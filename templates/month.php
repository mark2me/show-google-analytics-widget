<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( false === ($ga_month_data = get_transient('ga_month_data')) ) {
    $ga_month_data = Sig_Ga_Data::get_month_data();
    set_transient('ga_month_data', $ga_month_data, 60*60*24);    // day
}

if( !is_array($ga_month_data) ) {
    echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga_month_data.'</p>';
} else {

?>
    <h3><?php
        echo __('本月份統計：','show-google-analytics-widget');
        if(!empty($ga_month_data['startDate']) && !empty($ga_month_data['endDate'])){
            echo "{$ga_month_data['startDate']} ~ {$ga_month_data['endDate']}";
        }
    ?></h3>

    <div id="mychart" style="height: 250px;"></div>

    <table class="">
    <tr>
        <th align="left"><?php _e('Pageviews:','show-google-analytics-widget')?></th>
        <td><?php if(!empty($ga_month_data['pageViews'])) echo number_format($ga_month_data['pageViews']) ?>
    </tr>
    <tr>
        <th align="left"><?php _e('Visits:','show-google-analytics-widget')?></th>
        <td><?php if(!empty($ga_month_data['visits'])) echo number_format($ga_month_data['visits']) ?></td>
    </tr>
    <tr>
        <th align="left"><?php _e('Data Time:','show-google-analytics-widget')?></th>
        <td><?php if(!empty($ga_month_data['time'])) echo $ga_month_data['time']; ?></td>
    </tr>
    </table>

    <script>
    new Morris.Line({
        element: 'mychart',
        data: [
    <?php
        if( !empty($ga_month_data['results']) ){
            foreach( $ga_month_data['results'] as $k => $rs) {
                if($k>0) echo ',';
                $d = substr($rs['date'],0,4)."-".substr($rs['date'],4,2)."-".substr($rs['date'],6,2);
                echo "{ x:'{$d}', a:{$rs['pageviews']}, b:{$rs['visits']} }";
            }
        }
    ?>
        ],
        xkey: 'x',
        ykeys: ['a','b'],
        labels: ['Pageview','Visits'],
        fillOpacity: 1.0,
        resize: true
    });
    </script>

<?php
}