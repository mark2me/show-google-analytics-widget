<?php

/* 今日文章點擊排名 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$ga = $this->call_ga_api([
    array('pageTitle','pagepath'),
    array('pageviews'),
    '-pageviews',
    null,
    current_time('Y-m-d'),
    current_time('Y-m-d'),
    1,
    10
]);


if( !is_object($ga) ) {
    echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga.'</p>';
} else {
?>
    <div>
        <h3><?php _e('今日熱門文章前10名','show-google-analytics-widget')?></h3>
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
        foreach( $ga->getResults() as $k => $result) {

            echo '<tr><td>'.($k+1).'</td><td><a href="'.$result->getPagepath().'">'.$result->getPagetitle().'</a></td><td>'.$result->getPageviews().'</td></tr>';
        }

        ?>
            </tbody>
        </table>
    </div>
<?php
}