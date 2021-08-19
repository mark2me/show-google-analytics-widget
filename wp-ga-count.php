<?php
/**
 * Plugin Name: Show Google Analytics widget
 * Plugin URI:  https://github.com/mark2me/show-google-analytics-widget
 * Description: 利用 Google Analytics 資料來顯示網站的今日參觀人數和總參觀人數小工具
 * Version:     1.5.3
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me
 * License:     GPLv2
 * Text Domain: show-google-analytics-widget
 * Domain Path: /languages
 */

define( 'SIG_GA_PLUGIN_NAME', 'wp-show-ga-widget' );
define( 'SIG_GA_DIR', dirname(__FILE__) );
define( 'SIG_GA_CACHE', 3600);
define( 'SIG_GA_CONFIG', 'sig-ga-config');
define( 'SIG_GA_VIEW_WIDGET', 'sig-show-pageview');
define( 'SIG_GA_HOT_WIDGET', 'sig-show-hot');
define( 'SIG_GA_PV_CLASS', 'sig_ga_pv');


load_plugin_textdomain( SIG_GA_PLUGIN_NAME , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


new SigGaWidget();

class SigGaWidget{

    public $get_ga_config;

    public function __construct() {

        require_once( dirname( __FILE__ ) . '/class-ga-widget.php' );
        require_once( dirname( __FILE__ ) . '/class-ga-data.php' );

        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array($this,'plugin_settings_link') );

        add_action( 'widgets_init', array($this,'register_ga_widget') );

        add_action( 'admin_menu', array($this,'add_ga_view_menu') );
        add_action( 'admin_enqueue_scripts', array($this,'add_ga_view_scripts') );

        add_action( 'admin_menu', array($this,'setting_ga_option_menu') );
        add_filter( 'upload_mimes', array($this,'custom_upload_mimes') );

        add_shortcode( 'sig_post_pv', array($this,'shortcode_post_pageviews') );
        add_filter( 'the_content' , array($this,'add_post_pageviews') );

        add_action( 'wp_ajax_sig-ga-widget', array($this,'wpajax_get_data') );
        add_action( 'wp_ajax_nopriv_sig-ga-widget', array($this,'wpajax_get_data') );

        $this->get_ga_config = get_option(SIG_GA_CONFIG);
    }

    public function plugin_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=sig-ga-account">'.__( 'Settings' ).'</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function register_ga_widget() {
        register_widget( 'Sig_Ga_Views_Widget' );
        register_widget( 'Sig_Ga_Hot_Widget' );
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
        wp_enqueue_script( 'chart', plugin_dir_url(__FILE__) . 'assets/js/morris.min.js',array('jquery'));
        wp_enqueue_style( 'bootstrap4', plugin_dir_url(__FILE__) . 'assets/css/bootstrap-grid.min.css' );
    }

    public function add_ga_view_page() {

        if( $this->check_ga_config() === false ){
            echo '<div class="wrap"><h1>' . __('是否還沒設定GA服務帳號？','show-google-analytics-widget') . '</h1></div>';
            echo '<a href="/wp-admin/options-general.php?page=sig-ga-account" class="button button-primary widgets-chooser-add">' . __('立刻去新增','show-google-analytics-widget') . '</a>';
        }else{

    ?>
            <div class="container-fluid" style="margin-top: 15px;">
                <h1><?php _e('本月份GA資訊','show-google-analytics-widget')?></h1>
                <hr>
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

        $config = $this->get_ga_config;

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

        $config = $this->get_ga_config;

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


    public function show_ga_views_widget($widget_id=''){

        if( empty($widget_id) ){
            return '(No Data)';
        }else{

            //load config
            if( is_array($widget_id) ){
                $sig_ga_type = ( isset($widget_id['sig_ga_type']) ) ? $widget_id['sig_ga_type'] : 0;
                $sig_ga_nums = ( isset($widget_id['sig_ga_nums']) ) ? $widget_id['sig_ga_nums'] : 0;
                $sig_ga_cache = ( isset($widget_id['sig_ga_cache']) ) ? $widget_id['sig_ga_cache'] : 3600;
            }else{
                $arr = explode("-",$widget_id);
                $wid = end($arr);
                $widget = get_option('widget_'.SIG_GA_VIEW_WIDGET);

                $sig_ga_type = ( isset($widget[$wid]['sig_ga_type']) ) ? $widget[$wid]['sig_ga_type'] : 0;
                $sig_ga_nums = ( isset($widget[$wid]['sig_ga_nums']) ) ? $widget[$wid]['sig_ga_nums'] : 0;
                $sig_ga_cache = ( isset($widget[$wid]['sig_ga_cache']) ) ? $widget[$wid]['sig_ga_cache'] : 3600;
            }

            //load today data
            if( empty($sig_ga_cache) or $sig_ga_cache == 0 ){
                $today_data = Sig_Ga_Data::get_today_data();
            }else{
                if( false === $today_data = get_transient('ga_today_data') ){
                    $today_data = Sig_Ga_Data::get_today_data();
                    if($today_data!==false) set_transient('ga_today_data', $today_data, $sig_ga_cache);
                }
            }

            //output
            $html = '';
            $html .= sprintf('<div data-time="%1s">%2s：%3s</div>',
                    (!empty($today_data['time'])) ? $today_data['time']:"",
                    ($sig_ga_type==1) ? __('本日瀏覽','show-google-analytics-widget'): __('本日人氣','show-google-analytics-widget'),
                    ($sig_ga_type==1) ? number_format($today_data['pageview']) : number_format($today_data['visit'])
            );

            //load total data
            if( empty($sig_ga_cache) or $sig_ga_cache == 0 ){
                $view_data = Sig_Ga_Data::get_all_view_data();
            }else{
                if( false === $view_data = get_transient('ga_all_view_data') ){
                    $view_data = Sig_Ga_Data::get_all_view_data();
                    if($view_data!==false) set_transient('ga_all_view_data', $view_data, $sig_ga_cache);
                }
            }

            $html .= sprintf('<div data-time="%1s">%2s：%3s</div>',
                    (!empty($view_data['time'])) ? $view_data['time']:"",
                    ($sig_ga_type==1) ? __('累積瀏覽','show-google-analytics-widget'): __('累積人氣','show-google-analytics-widget'),
                    ($sig_ga_type==1) ? number_format( (int)$view_data['pageview']+(int)$sig_ga_nums) : number_format( (int)$view_data['visit']+(int)$sig_ga_nums)
            );

            return $html;
        }


    }

    public function show_ga_hot_widget($widget_id=''){

        if( empty($widget_id) ){
            return '(No Data)';
        }else{

            //load config
            if( is_array($widget_id) ){
                $sig_ga_hot_day = ( isset($widget_id['sig_ga_hot_day']) ) ? $widget_id['sig_ga_hot_day'] : 0;
                $sig_ga_hot_nums = ( isset($widget_id['sig_ga_hot_nums']) ) ? $widget_id['sig_ga_hot_nums'] : 5;
                $sig_ga_hot_cache = ( isset($widget_id['sig_ga_hot_cache']) ) ? $widget_id['sig_ga_hot_cache'] : 3600;
            }else{
                $arr = explode("-",$widget_id);
                $wid = end($arr);
                $widget = get_option('widget_'.SIG_GA_HOT_WIDGET);

                $sig_ga_hot_day = ( isset($widget[$wid]['sig_ga_hot_day']) ) ? $widget[$wid]['sig_ga_hot_day'] : 0;
                $sig_ga_hot_nums = ( isset($widget[$wid]['sig_ga_hot_nums']) ) ? $widget[$wid]['sig_ga_hot_nums'] : 5;
                $sig_ga_hot_cache = ( isset($widget[$wid]['sig_ga_hot_cache']) ) ? $widget[$wid]['sig_ga_hot_cache'] : 3600;
            }

            if( empty($sig_ga_hot_cache) && $sig_ga_hot_cache == 0 ){
                $hot_data = Sig_Ga_Data::get_hot_data($sig_ga_hot_nums,$sig_ga_hot_day);
            }else{
                if( false === $hot_data = get_transient('ga_hot_data') ){
                    $hot_data = Sig_Ga_Data::get_hot_data(($sig_ga_hot_nums*2),$sig_ga_hot_day);
                    if($hot_data!==false) set_transient('ga_hot_data', $hot_data, $sig_ga_hot_cache);
                }
            }

            $post = '';

            if( !empty($hot_data['results']) && count($hot_data['results'])>0 ){
                $post .= '<ul data-time="'.((!empty($hot_data['time'])) ? $hot_data['time']:'').'">';
                $i = 0;
                foreach( $hot_data['results'] as $k => $rs) {
                    if( $rs['pagepath'] !== '/' && substr($rs['pagepath'],0,9) !== '/wp-admin' && substr($rs['pagepath'],0,5) !== '/404/' ){
                        $post .= "<li><a href=\"{$rs['pagepath']}\">{$rs['pageTitle']}</a></li>";
                        $i+=1;
                        if( $i >= $sig_ga_hot_nums ) break;
                    }
                }
                $post .= '</ul>';
            }else{
                $post .= '--';
            }

            return $post;
        }
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
        require_once(SIG_GA_DIR.'/templates/setup.php');
    }

    /**
     *  support .p12
     */
    public function custom_upload_mimes ( $existing_mimes=array() ) {
        $existing_mimes['p12'] = 'application/x-pkcs12';
        return $existing_mimes;
    }

    public function wpajax_get_data(){

        $widget_id = ( isset($_GET['id']) && !empty($_GET['id']) ) ? $_GET['id'] : '';
        $type = ( isset($_GET['type']) && !empty($_GET['type']) ) ? $_GET['type'] : '';

        if($type=='views'){
            echo $this->show_ga_views_widget($widget_id);
        }else if($type=='hot'){
            echo $this->show_ga_hot_widget($widget_id);
        }

        wp_die();
    }

    public function add_post_pageviews($content) {

        $config = $this->get_ga_config;

        if( !empty($config['sig_ga_show_top']) or !empty($config['sig_ga_show_bom']) ) {

            $post_view = '-';
            $post_id = get_the_ID();
            $label = ( !empty($config['sig_ga_pageview_label']) ) ? $config['sig_ga_pageview_label'] : '瀏覽次數';
            $time = ( isset($config['sig_ga_pageview_cache']) ) ? absint($config['sig_ga_pageview_cache']) : SIG_GA_CACHE;

            if( $post_id > 0 ) {
                $key = 'sig_ga_pv_'.$post_id;
                if( false === $post_view = get_transient($key) ){
                    $post_view = Sig_Ga_Data::get_post_view_data();
                    if($time>0) set_transient($key, $post_view, $time );
                }
            }

            $pv = sprintf('<div class="'.SIG_GA_PV_CLASS.'">%1s%2s</div>', $label, $post_view);
            $content = preg_replace('/\[sig_post_pv[^\]]*\]/m', '', $content);

            if( !empty($config['sig_ga_show_top']) ) $content = $pv . $content;
            if( !empty($config['sig_ga_show_bom']) ) $content = $content . $pv;
        }

        return $content;
    }

    public function shortcode_post_pageviews($atts) {

        $array = shortcode_atts(
            array(
                'label' => 'Page view:',
                'class' => SIG_GA_PV_CLASS
            ),
            $atts
        );

        $post_view = '-';
        $post_id = get_the_ID();

        if( $post_id > 0 ) {
            $key = 'sig_ga_pv_'.$post_id;
            if( false === $post_view = get_transient($key) ){
                $post_view = Sig_Ga_Data::get_post_view_data();
                set_transient($key, $post_view, 60*60*2 );  // hour
            }
        }

        return "<div class=\"{$array['class']}\">{$array['label']}{$post_view}</div>";
    }


}

