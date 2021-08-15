<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( false === ($ga_total_data = get_transient('ga_total_data')) ) {
    $ga_total_data = Sig_Ga_Data::get_total_data();
    set_transient('ga_total_data', $ga_total_data, 60*60*24*7);  // week
}

if( !is_array($ga_total_data) ) {
    echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga_total_data.'</p>';
} else {

?>
    <h3><?php
        _e('歷年統計：','show-google-analytics-widget');
        if(!empty($ga_total_data['startDate']) && !empty($ga_total_data['startDate'])) echo "{$ga_total_data['startDate']} ~ {$ga_total_data['endDate']}"; ?></h3>

    <div id="mychart2" style="height: 250px;"></div>

    <table class="">
    <tr>
        <th align="left"><?php _e('Pageviews:','show-google-analytics-widget')?></th>
        <td><?php if(!empty($ga_total_data['pageViews'])) echo number_format($ga_total_data['pageViews']) ?>
    </tr>
    <tr>
        <th align="left"><?php _e('Visits:','show-google-analytics-widget')?></th>
        <td><?php if(!empty($ga_total_data['visits'])) echo number_format($ga_total_data['visits']) ?></td>
    </tr>
    <tr>
        <th align="left"><?php _e('Data Time:','show-google-analytics-widget')?></th>
        <td><?php if(!empty($ga_total_data['time'])) echo $ga_total_data['time'] ?></td>
    </tr>
    </table>

    <script>
    new Morris.Bar({
        element: 'mychart2',
        data: [
    <?php
        if( !empty($ga_total_data['results']) ){
            foreach( $ga_total_data['results'] as $k => $rs) {
                if($k>0) echo ',';
                echo "{ x:'{$rs['year']}', a:{$rs['pageviews']}, b: {$rs['visits']} }";
            }
        }
    ?>
        ],
        xkey: 'x',
        ykeys: ['a','b'],
        labels: ['Pageview','Visits'],
        resize: true
    });
    </script>
<?php
}

