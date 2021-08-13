<?php
/**
 * Plugin Name: Show Google Analytics widget
 * Plugin URI:  https://github.com/mark2me/show-google-analytics-widget
 * Description: 利用 Google Analytics 資料來顯示網站的今日參觀人數和總參觀人數小工具
 * Version:     1.4.4
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me
 * License:     GPLv2
 * Text Domain: wp-show-ga-widget
 * Domain Path: /languages
 */

define( 'SIG_GA_PLUGIN_NAME', 'wp-show-ga-widget' );
define( 'SIG_GA_DIR', dirname(__FILE__) );
define( 'SIG_GA_WIDGET', 'sig-show-pageview');    // widget dom id
define( 'SIG_GA_CACHE', 600);                     // cache time
define( 'SIG_GA_CONFIG', 'sig-ga-config');
define( 'SIG_GA_POST_VIEW', 'views');


load_plugin_textdomain( SIG_GA_PLUGIN_NAME , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


/*-----------------------------------------------
* add WP_Widget
-----------------------------------------------*/
class  Sig_Ga_Count_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            SIG_GA_WIDGET,
            __('顯示GA瀏覽人次統計', 'show-google-analytics-widget' ),
            array (
                'description' => __('顯示參觀人次的統計數字','show-google-analytics-widget')
            )
        );
    }

    function form( $instance ) {

        $defaults = [
          'sig_ga_title'    => __('參觀人氣','show-google-analytics-widget'),
          'sig_ga_type'     => 0,
          'sig_ga_nums'     => 0,
        ];

        $instance = wp_parse_args( (array) $instance, $defaults );

    ?>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_title'); ?>"><?php _e('自定標題：','show-google-analytics-widget')?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('sig_ga_title'); ?>" name="<?php echo $this->get_field_name('sig_ga_title'); ?>" value="<?php echo $instance['sig_ga_title']; ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_type'); ?>"><?php _e('顯示類型：','show-google-analytics-widget')?></label>
            <select class="widefat" size="1" id="<?php echo $this->get_field_id('sig_ga_type'); ?>" name="<?php echo $this->get_field_name('sig_ga_type'); ?>">
                <option value="0" <?php if($instance['sig_ga_type']==0) echo 'selected'?>><?php _e('Visit(人次)','show-google-analytics-widget')?></option>
                <option value="1" <?php if($instance['sig_ga_type']==1) echo 'selected'?>><?php _e('Pageview(頁次)','show-google-analytics-widget')?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_nums'); ?>"><?php _e('調整計次：','show-google-analytics-widget')?></label>
            <input class="widefat" placeholder="<?php _e('輸入起跳的數字','show-google-analytics-widget')?>" type="text" id="<?php echo $this->get_field_id('sig_ga_nums'); ?>" name="<?php echo $this->get_field_name('sig_ga_nums'); ?>" value="<?php echo $instance['sig_ga_nums']; ?>"  onkeyup="value=value.replace(/[^0-9]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^0-9]/g,''))">
        </p>

    <?php
    }

    function update( $new_instance, $old_instance ) {

        $instance = $old_instance;

        $instance['sig_ga_title']      = strip_tags( $new_instance['sig_ga_title'] );
        $instance['sig_ga_type']       = strip_tags( $new_instance['sig_ga_type'] );
        $instance['sig_ga_nums']       = strip_tags( $new_instance['sig_ga_nums'] );

        $instance['sig_ga_nums'] = preg_replace('/[^0-9]/','',$instance['sig_ga_nums']);
        if(empty($instance['sig_ga_nums'])) $instance['sig_ga_nums'] = 0;

        //clear transient
        $obj = new SigGaWidget();
        $config = $obj->get_ga_config();
        delete_transient('sig_today_view_'.$config['sig_ga_id']);
        delete_transient('sig_total_view_'.$config['sig_ga_id']);

        return $instance;
    }

    function widget( $args, $instance ) {

        extract( $args );

        $sig_ga_title   = $instance['sig_ga_title'];
        $sig_ga_type    = $instance['sig_ga_type'];
        $sig_ga_nums    = $instance['sig_ga_nums'];

        $widget_id = $args['widget_id'];

        $obj = new SigGaWidget();
        $config = $obj->get_ga_config();

        $error_msg = '';

        if( $config !== false )
        {
            if( !isset($config['sig_ga_account']) || empty($config['sig_ga_account']) ) {
                $error_msg .= __('尚未設定GA服務帳號','show-google-analytics-widget').'<br>';
            }

            if( !isset($config['sig_ga_upload']) || empty($config['sig_ga_upload']) ) {
                $error_msg .= __('尚未上傳 P12 key檔','show-google-analytics-widget').'<br>';
            }

            if( !isset($config['sig_ga_id']) || empty($config['sig_ga_id']) ) {
                $error_msg .= __('尚未設定 Profile ID','show-google-analytics-widget').'<br>';
            }

            if( !empty($error_msg) ) {

                echo $before_widget;
                echo $before_title . $sig_ga_title . $after_title;
                echo ( !is_admin() && ! is_user_logged_in() ) ? __('(建置中)','show-google-analytics-widget'):"<div>{$error_msg}</div>";
                echo $after_widget;
                return;

            }else{


                $sig_ga_id = $config['sig_ga_id'];
                $sig_ga_ajax = ( isset($config['sig_ga_ajax']) ) ? $config['sig_ga_ajax'] : 0;

                $content = $before_widget;
                $content .= $before_title . $sig_ga_title . $after_title;

                if( !empty($sig_ga_ajax) && $sig_ga_ajax==1 ){

                    $content .= '<div id="'.$widget_id.'"><img src="'. plugin_dir_url(__FILE__) .'assets/img/loading.gif"></div>';
                    $content .= $after_widget;
                    $content .= '<script type="text/javascript">jQuery(document).ready(function($) {$.get(\'/wp-admin/admin-ajax.php?action='.SIG_GA_WIDGET.'&id='.$widget_id.'&t='.time().'\', function(data) {$(\'#'.$widget_id.'\').html(data);    });});</script>';
                }else{
                    $content .= $obj->show_today_views($sig_ga_id, $widget_id);
                    $content .= $obj->show_total_views($sig_ga_id, $widget_id);
                    $content .= $after_widget;
                }



                echo $content;
                return;
            }
        }

    }
}



class SigGaWidget{

    public $options = array();

    public function __construct() {

        $this->options = $this->get_ga_config();

        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array($this,'plugin_settings_link') );

        add_action( 'widgets_init', array($this,'register_ga_widget') );

        add_action( 'admin_menu', array($this,'add_ga_view_menu') );
        add_action( 'admin_enqueue_scripts', array($this,'add_ga_view_scripts') );

        add_action( 'admin_menu', array($this,'setting_ga_option_menu') );
        add_filter( 'upload_mimes', array($this,'custom_upload_mimes') );

        add_shortcode( 'sig_post_pv', array($this,'shortcode_post_pageviews') );
        add_filter( 'the_content', array($this,'add_view_in_the_content'), 10 );

        add_action( 'wp_ajax_'.SIG_GA_WIDGET, array($this,'wpajax_get_pv') );
        add_action( 'wp_ajax_nopriv_'.SIG_GA_WIDGET, array($this,'wpajax_get_pv') );

    }

    public function plugin_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=sig-ga-account">'.__( 'Settings' ).'</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function register_ga_widget() {
        register_widget( 'Sig_Ga_Count_Widget' );
    }


    public function add_ga_view_menu() {
        add_menu_page(
            __('查看本月份的瀏覽人次統計表','show-google-analytics-widget'),
            __('本月份GA資訊','show-google-analytics-widget'),
            'administrator',
            'sig-view-ga',
            array($this,'add_ga_view_page')
        );
    }

    public function add_ga_view_scripts($hook) {

        if( !in_array($hook, array('toplevel_page_sig-view-ga','settings_page_sig-ga-account')) )  return;

        wp_enqueue_style( 'chart', plugin_dir_url(__FILE__) . 'assets/js/morris.css' );
        wp_enqueue_script( 'raphael', plugin_dir_url(__FILE__) . 'assets/js/raphael-min.js',array('jquery') );
        wp_enqueue_script('chart', plugin_dir_url(__FILE__) . 'assets/js/morris.min.js',array('jquery'));

        wp_enqueue_style( 'bootstrap4', plugin_dir_url(__FILE__) . 'assets/css/bootstrap-grid.min.css' );
    }

    public function add_ga_view_page() {

        if( $this->check_ga_config() === false ){
            echo '<div class="wrap"><h1>' . __('是否還沒設定GA服務帳號？','show-google-analytics-widget') . '</h1></div>';
            echo '<a href="/wp-admin/options-general.php?page=sig-ga-account" class="button button-primary widgets-chooser-add">' . __('立刻去新增','show-google-analytics-widget') . '</a>';
        }else{

    ?>
            <div class="container-fluid" style="margin-top: 15px;">
                <div class="row">
                    <div class="col-12 col-sm-6">
                        <div style="background-color: #fff;padding: 20px;"><?php require_once(SIG_GA_DIR.'/templates/month.php'); ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="background-color: #fff;padding: 20px;"><?php require_once(SIG_GA_DIR.'/templates/total.php'); ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <?php require_once(SIG_GA_DIR.'/templates/hot.php'); ?>
                    </div>
                </div>
            </div>

    <?php

        }
    }

    private function check_ga_config() {

        $config = $this->options;

        if( $config !== false ){

            if(!isset($config['sig_ga_account'])) return false;
            $account  = $config['sig_ga_account'];

            if(!isset($config['sig_ga_upload'])) return false;
            $p12      = $config['sig_ga_upload'];

            if(!isset($config['sig_ga_id'])) return false;
            $report_id= $config['sig_ga_id'];

        } else {
            return false;
        }

        if( empty($account) or empty($p12) or empty($report_id) ) {
            return false;
        }else{
            return true;
        }

    }

    public function call_ga_api($data='') {

        $config = $this->options;

        if( $config !== false ){

            if(!isset($config['sig_ga_account'])) return false;
            $account  = $config['sig_ga_account'];

            if(!isset($config['sig_ga_upload'])) return false;
            $p12      = $config['sig_ga_upload'];

            if(!isset($config['sig_ga_id'])) return false;
            $report_id= $config['sig_ga_id'];

        } else {
            return false;
        }

        if( empty($account) or empty($p12) or empty($report_id) ) {
            return false;
        } else {
            if (is_array($data)) {

                list($dimensions, $metrics, $sort_metric, $filter, $start_date, $end_date, $start_index, $max_results) = $data;

                require_once(SIG_GA_DIR.'/lib/gapi.class.php');

                if (file_exists($p12)) {
                    try {
                        $ga = new gapi($account, $p12);
                    } catch (Exception $e) {
                        return $e->getMessage();
                    }
                } else {
                    return '<p><b>'. __('注意: 尚未將 p12 檔案上傳到網站內','show-google-analytics-widget') . '</b></p>';
                }

                try {
                    $ga->requestReportData($report_id, $dimensions, $metrics, $sort_metric, $filter,$start_date, $end_date, $start_index, $max_results);
                    return $ga;
                } catch (Exception $e) {
                    return $e->getMessage();
                }

            } else {
                return false;
            }
        }
    }

    public function get_ga_config() {
        $config = get_option(SIG_GA_CONFIG);
        if ( is_array($config) ) {
            return $config;
        } else {
            return false;
        }
    }

    public function get_today_views($sig_ga_id) {

        $key = 'sig_today_view_'.$sig_ga_id;

        $ga_view = get_transient($key);

        if( $ga_view === false ){

            $ga = $this->call_ga_api([
                array('date'),
                array('pageviews','visits'),
                'date',
                '',
                current_time('Y-m-d'),
                current_time('Y-m-d'),
                1,
                1
            ]);

            if( is_object($ga) ) {

                $data = [
                    'pageview'  => $ga->getPageviews(),
                    'visit'     => $ga->getVisits(),
                    'time'      => current_time('Y-m-d H:i:s')
                ];

                $config = $this->options;

                $expire = (!empty($config['sig_ga_cache']) and $config['sig_ga_cache'] > 0) ? $config['sig_ga_cache']:SIG_GA_CACHE;

                set_transient($key, $data, $expire);

                return $data;

            } else {

                return false;
            }
        }

        return $ga_view;
    }

    public function show_today_views($sig_ga_id, $widget_id=''){

        $data = $this->get_today_views($sig_ga_id);

        $sig_ga_type = 0;
        $sig_ga_nums = 0;

        if( !empty($widget_id) ){
            $widget_id = end(explode("-",$widget_id));
            $widget = get_option('widget_'.SIG_GA_WIDGET);
            if( isset($widget[$widget_id]['sig_ga_type']) ) $sig_ga_type = $widget[$widget_id]['sig_ga_type'];
            if( isset($widget[$widget_id]['sig_ga_nums']) ) $sig_ga_nums = $widget[$widget_id]['sig_ga_nums'];
        }

        $today =  ($sig_ga_type==1) ? __('本日瀏覽：','show-google-analytics-widget'): __('本日人氣：','show-google-analytics-widget');

        if( $data === false ) {
            $today .= '-';
        } else {
            $today .= ($sig_ga_type==1) ? number_format($data['pageview']) : number_format($data['visit']);
        }

        return '<div data-time="'.$data['time'].'">'.$today.'</div>';
    }

    public function get_total_views($sig_ga_id) {

        $key = 'sig_total_view_'.$sig_ga_id;

        $ga_view = get_transient($key);

        if( $ga_view === false ){

            $ga = $this->call_ga_api([
                array('year'),
                array('pageviews','visits'),
                'year',
                '',
                '',
                current_time('Y-m-d'),
                1,
                100
            ]);

            if( is_object($ga) ) {

                $data = [
                    'pageview'  => $ga->getPageviews(),
                    'visit'     => $ga->getVisits(),
                    'start'     => $ga->getStartDate(),
                    'end'       => $ga->getEndDate(),
                    'time'      => current_time('Y-m-d H:i:s')
                ];

                $config = $this->options;

                $expire = (!empty($config['sig_ga_cache']) and $config['sig_ga_cache'] > 0) ? $config['sig_ga_cache']:SIG_GA_CACHE;

                set_transient($key, $data, $expire);

                return $data;

            } else {

                return false;
            }
        }

        return $ga_view;
    }

    public function show_total_views($sig_ga_id, $widget_id='') {

        $data = $this->get_total_views($sig_ga_id);

        $sig_ga_type = 0;
        $sig_ga_nums = 0;

        if( !empty($widget_id) ){
            $widget_id = end(explode("-",$widget_id));
            $widget = get_option('widget_'.SIG_GA_WIDGET);
            if( isset($widget[$widget_id]['sig_ga_type']) ) $sig_ga_type = $widget[$widget_id]['sig_ga_type'];
            if( isset($widget[$widget_id]['sig_ga_nums']) ) $sig_ga_nums = $widget[$widget_id]['sig_ga_nums'];
        }

        $total =  ($sig_ga_type==1) ? __('累積瀏覽：','show-google-analytics-widget'): __('累積人氣：','show-google-analytics-widget');

        if( $data === false ) {
            $total .= '-';
        } else {
            $total .= ($sig_ga_type==1) ? number_format($data['pageview']+$sig_ga_nums) : number_format($data['visit']+$sig_ga_nums);
        }

        return '<div data-time="'.$data['time'].'">'.$total.'</div>';
    }

    /**/
    public function setting_ga_option_menu(){
        add_options_page(
            __('設定 GA 帳號及參數','show-google-analytics-widget'),
            __('設定 GA 帳號及參數','show-google-analytics-widget'),
            'administrator',
            'sig-ga-account',
            array($this,'ga_settings_page')
        );
        add_action( 'admin_init', array($this,'register_ga_opt_var') );
    }

    public function register_ga_opt_var() {
        register_setting( 'sig-ga-option-group', SIG_GA_CONFIG, array($this,'handle_file_upload') );
    }

    public function handle_file_upload($option) {
        if(!empty($_FILES["sig_ga_upload"]["tmp_name"])) {
            $temp = wp_handle_upload($_FILES["sig_ga_upload"], array('test_form' => FALSE));
            if ( $temp && ! isset( $temp['error'] ) ) {
                $option['sig_ga_upload'] = $temp['file'];
            }
        }
        return $option;
    }

    /**
     *  setup page
     */
    public function ga_settings_page() {

        $config = $this->options;
        $alert = false;

        if (empty($config)) {
            $old = get_option('widget_'.SIG_GA_WIDGET);
            if( !empty($old) and count($old) > 1){
                $config = array_shift($old);
                if(isset($config['sig_ga_account'])) $alert = true;
            }
        }

        ?>
        <div class="wrap">
            <h2><?php _e('設定 GA 帳號及相關參數','show-google-analytics-widget')?></h2>

            <div class="container-">
                <div class="row">
                    <!-- left -->
                    <div class="col-12 col-sm-8">
                        <form method="post" action="options.php" enctype="multipart/form-data">
                            <?php settings_fields('sig-ga-option-group'); ?>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('GA授權服務帳號：','show-google-analytics-widget')?></th>
                                    <td><input type="text" class="regular-text" name="<?php echo SIG_GA_CONFIG?>[sig_ga_account]" value="<?php if(!empty($config['sig_ga_account'])) echo esc_attr( $config['sig_ga_account'] ); ?>" />
                                    <p class="description">到 <a href="https://console.developers.google.com/" target="_blank">Google Developers</a> 申請，並下載p12檔案。再把這個服務帳號加入 Google Analytics 你的站台管理員，權限要可檢視和分析。 </p></td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php _e('上傳 P12 key 檔：','show-google-analytics-widget')?></th>
                                    <td><p class="description"><?php
                                            if( isset($config['sig_ga_upload']) and $config['sig_ga_upload'] !==''){
                                                if( is_file($config['sig_ga_upload']) ){
                                                    echo __('目前檔案位置：','show-google-analytics-widget') . $config['sig_ga_upload'];
                                                }else{
                                                    echo __('尚未上傳','show-google-analytics-widget');
                                                }
                                                echo '<input type="hidden" name="'.SIG_GA_CONFIG.'[sig_ga_upload]" value="'.$config['sig_ga_upload'].'">';

                                            }else{
                                                echo __('你可以先自行更改檔名再上傳。','show-google-analytics-widget');
                                            }
                                        ?></p>
                                        <input type="file" class="regular-text" name="sig_ga_upload" /></td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php _e('獲取資料間隔：','show-google-analytics-widget')?></th>
                                    <td><input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_cache]" value="<?php echo (!empty($config['sig_ga_cache'])) ? esc_attr( $config['sig_ga_cache'] ) : SIG_GA_CACHE ; ?>"  onkeyup="value=value.replace(/[^\d.]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d.]/g,''))">秒
                                    <p class="description"><?php _e('預設時間為600秒，過短的時間有可能造成網頁開啟過於緩慢。','show-google-analytics-widget')?></p></td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php _e('網站的 Profile ID：','show-google-analytics-widget')?></th>
                                    <td><input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_id]" value="<?php if(!empty($config['sig_ga_id'])) echo esc_attr( $config['sig_ga_id'] ); ?>" />
                                    <p class="description"><?php _e('到你的 Google Analytics 中，切換到你的站台，在瀏覽器的URL應該是這樣子『https://www.google.com/analytics/web/#report/visitors-overview/a1234b23478970 p1234567/』，找最後 p 之後的數字1234567','show-google-analytics-widget')?></p></td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row"><?php _e('使用非同步顯示數據：','show-google-analytics-widget')?></th>
                                    <td><input type="checkbox" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_ajax]" value="1" <?php echo ( !empty($config['sig_ga_ajax']) && $config['sig_ga_ajax']==1 ) ? 'checked="checked"':''; ?> /> <?php _e('若安裝了快取外掛，統計數字長時間都無法變動時，建議將此項目勾選','show-google-analytics-widget')?></td>
                                </tr>

                            </table>
                            <?php submit_button(); ?>


                            <?php if($alert) echo __('(第一次設定，以上資料來自小工具設定，請按下儲存按鈕做轉換儲存。)','show-google-analytics-widget')?>
                        </form>
                    </div>
                    <!-- //left -->
                    <!-- right -->
                    <div class="col-12 col-sm-4">
                        <div style="background-color: #fff;padding: 15px; line-height: 1.8;">
                            <h2><?php echo __('補充說明','show-google-analytics-widget')?></h2>
                            <ol style="font-size: 15px;">
                                <li>外掛使用教學：在這推薦Gill吉兒的文章，步驟非常詳細。 <a href="https://reurl.cc/pm5ERZ" target="_blank">https://reurl.cc/pm5ERZ</a></li>
                                <li>請注意！本外掛使用 Google Analytics API(V3)，每日有呼叫次數限制，超過的請求次數，您可能需要負擔費用。(您可增加資料重新獲取的間隔秒數來避免超出呼叫次數)</li>
                                <li><?php echo __('文章點閱次數的短代碼寫法：','show-google-analytics-widget')?><br>[sig_post_pv label="瀏覽："]</li>
                            </ol>
                        </div>
                    </div>
                    <!-- //right -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     *  support .p12
     */
    public function custom_upload_mimes ( $existing_mimes=array() ) {
        $existing_mimes['p12'] = 'application/x-pkcs12';
        return $existing_mimes;
    }

    public function wpajax_get_pv(){

        $config = $this->options;

        if( !empty($config['sig_ga_id']) ){

            $sig_ga_id = $config['sig_ga_id'];

            $widget_id = ( isset($_GET['id']) && !empty($_GET['id']) ) ? $_GET['id'] : '';

            //-----today------
            echo $this->show_today_views($sig_ga_id, $widget_id);

            //------ all --------
            echo $this->show_total_views($sig_ga_id, $widget_id);

        }
        wp_die();
    }

    public function shortcode_post_pageviews($atts) {

        $array = shortcode_atts(
            array(
                'label' => 'Page view:',
            ),
            $atts
        );

        $post_view = '-';

        $post_id = get_the_ID();

        if( $post_id > 0 ) {

            $key = 'sig_post_view_'.$post_id;
            $post_view = get_transient($key);

            if( $post_view === false ){

                $post_view = $this->get_post_views();
                set_transient($key, $post_view, 60*60 );
            }

        }

        return $array['label'].$post_view;
    }

    public function add_view_in_the_content( $content ) {

        if ( is_single() && is_singular()  ) {

            $config = $this->options;
            if( !empty($config['sig_ga_postview']) && $config['sig_ga_postview']==1 ){
                $content .= '點閱數：'.$this->get_post_views();
            }
        }

        return $content;
    }

    public function get_post_views() {

        $uri_path = str_replace(home_url(), '', get_permalink());
        $uri_path = urldecode($uri_path);

        $ga = $this->call_ga_api([
            array('pagePath'),
            array('pageviews','uniquePageviews'),
            '',
            'pagePath=='.$uri_path,
            '',
            current_time('Y-m-d'),
            1,
            10
        ]);

        if( is_object($ga) ) {
            return $ga->getPageviews();
        }else{
            return '-';
        }
    }
}

new SigGaWidget();
