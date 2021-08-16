<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ga_hot_data = Sig_Ga_Data::get_hot_data();

if( !is_array($ga_hot_data) ) {
    echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga_hot_data.'</p>';
} else {
?>
    <div>
        <h3><?php _e('今日熱門文章前10名','show-google-analytics-widget');
            if(!empty($ga_hot_data['endDate'])) echo " ({$ga_hot_data['endDate']})";
        ?></h3>
        <table class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th>No.</th>
                    <th align="left"><?php _e('標題','show-google-analytics-widget')?></th>
                    <th align="right"><?php _e('瀏覽次數','show-google-analytics-widget')?></th>
                </tr>
            </thead>
            <tbody>
        <?php
        if( !empty($ga_hot_data['results']) && count($ga_hot_data['results'])>0 ){
            foreach( $ga_hot_data['results'] as $k => $rs) {

                echo '<tr><td>'.($k+1).'</td><td><a href="'.$rs['pagepath'].'">'.$rs['pageTitle'].'</a></td><td>'.$rs['pageviews'].'</td></tr>';
            }
        }else{
            echo '<tr><td colspan="3">'. __('No Data','show-google-analytics-widget') .'</td></tr>';
        }

        ?>
            </tbody>
        </table>
    </div>
<?php
}