<?php
/**
 * Plugin Name: Show Google Analytics widget
 * Plugin URI:  http://webdesign.sig.tw
 * Description: 像痞客邦的顯示今日參觀人數和總參觀人數的小工具
 * Version:     1.3
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me
 * License:     GPLv2
 * Text Domain: wp-show-ga-widget
 * Domain Path: /languages
 */

define( 'SIG_GA_PLUGIN_NAME', 'wp-show-ga-widget' );
define( 'SIG_GA_DIR', dirname(__FILE__) );
define( 'SIG_GA_WIDGET', 'sig-show-pageview');    // widget dom id
define( 'SIG_GA_CACHE', 600);                     // today visit cache time
define( 'SIG_GA_CONFIG', 'sig-ga-config');
define( 'SIG_GA_POST_VIEW', 'views');


load_plugin_textdomain( SIG_GA_PLUGIN_NAME , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

//$call_sig_ga = new Sig_Ga_Widget();

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

        $defaults = array(
          'sig_ga_title'    => __('參觀人氣','show-google-analytics-widget'),
          'sig_ga_type'     => 0,
          'sig_ga_nums'     => 0,
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

    ?>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_title'); ?>"><?php _e('自定標題：','show-google-analytics-widget')?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('sig_ga_title'); ?>" name="<?php echo $this->get_field_name('sig_ga_title'); ?>" value="<?php echo $instance['sig_ga_title']; ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_type'); ?>"><?php _e('顯示類型：','show-google-analytics-widget')?></label>
            <select class="widefat" size="1"  id="<?php echo $this->get_field_id('sig_ga_type'); ?>" name="<?php echo $this->get_field_name('sig_ga_type'); ?>">
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

        return $instance;
    }

    function widget( $args, $instance ) {

        extract( $args );

        $sig_ga_title   = $instance['sig_ga_title'];
        $sig_ga_type    = $instance['sig_ga_type'];
        $sig_ga_nums    = $instance['sig_ga_nums'];

        $config = Sig_Ga_Widget::get_ga_config();

        if( $config !== false )
        {
            if(!isset($config['sig_ga_account']) || empty($config['sig_ga_account'])){
                _e('尚未設定GA服務帳號','show-google-analytics-widget');
                return;
            }else{
                $sig_ga_account   = $config['sig_ga_account'];
            }

            if(!isset($config['sig_ga_upload']) || empty($config['sig_ga_upload'])){
                _e('尚未上傳 P12 key檔','show-google-analytics-widget');
                return;
            }else{
                $sig_ga_upload    = $config['sig_ga_upload']; //p12
            }

            if(!isset($config['sig_ga_id']) || empty($config['sig_ga_id'])){
                _e('尚未設定 Profile ID','show-google-analytics-widget');
                return;
            }else{
                $sig_ga_id = $config['sig_ga_id'];
            }

            if( !empty($sig_ga_account) and !empty($sig_ga_upload) and !empty($sig_ga_id) ) {
                //-----today------
                $data = get_option('sig_ga_today_'.$sig_ga_id);

                if(empty($data) or $data === '' )
                {
                    $data = Sig_Ga_Widget::updata_today_views($sig_ga_id,1);
                }
                else
                {
                    $cache_time = (!empty($config['sig_ga_cache']) and $config['sig_ga_cache'] > 0) ? $config['sig_ga_cache']:SIG_GA_CACHE;
                    if( (time() - $data['time']) > $cache_time ){
                        $data = Sig_Ga_Widget::updata_today_views($sig_ga_id,0);
                    }
                }

                if($sig_ga_type==1){
                    $today = (isset($data['pageview'])) ? $data['pageview'] : 0;
                }else{
                    $today = (isset($data['visit'])) ? $data['visit'] : 0;
                }

                //------ all --------
                $data = get_option('sig_ga_tol_'.$sig_ga_id);

                if(empty($data) or $data === '' )
                {
                    $data = Sig_Ga_Widget::updata_total_views($sig_ga_id,1);
                }
                else
                {
                    if( date('Y-m-d',strtotime("-1 days")) !== $data['end'] ) {
                        $data = Sig_Ga_Widget::updata_total_views($sig_ga_id,0);
                    }
                }

                if($sig_ga_type==1){
                    $all = (isset($data['pageview'])) ? $data['pageview'] : 0;
                }else{
                    $all = (isset($data['visit'])) ? $data['visit'] : 0;
                }

                echo $before_widget;
                echo $before_title . $sig_ga_title . $after_title;
                echo '<div>' . __('本日人氣：','show-google-analytics-widget') . number_format($today) . '</div>';
                echo '<div>' . __('累積人氣：','show-google-analytics-widget') . number_format($all+$sig_ga_nums) . '</div>';
                echo $after_widget;
            }
            else
            {
                _e('尚未設定定義檔','show-google-analytics-widget');
            }

        }

    }
}



class Sig_Ga_Widget{

    public function __construct() {

        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array($this,'plugin_settings_link') );

        add_action( 'widgets_init', array($this,'register_ga_widget') );

        add_action( 'admin_menu', array($this,'add_ga_view_menu') );
        add_action( 'admin_enqueue_scripts', array($this,'add_ga_view_scripts') );

        add_action('admin_menu', array($this,'setting_ga_option_menu') );
        add_filter('upload_mimes', array($this,'custom_upload_mimes') );
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
            __('本月份GA','show-google-analytics-widget'),
            'administrator',
            'sig-view-ga',
            array($this,'add_ga_view_page')
        );
    }

    public function add_ga_view_scripts($hook) {

        if($hook != 'toplevel_page_sig-view-ga')  return;

        wp_enqueue_style( 'chart', plugin_dir_url(__FILE__) . 'js/morris.css' );
        wp_enqueue_script( 'raphael', plugin_dir_url(__FILE__) . 'js/raphael-min.js',array('jquery') );
        wp_enqueue_script('chart', plugin_dir_url(__FILE__) . 'js/morris.min.js',array('jquery'));
    }

    public function add_ga_view_page() {
        $data = array(
            array('date'),
            array('pageviews','visits'),
            'date',
            '',
            date('Y-m-01'),
            date('Y-m-d'),
            1,
            10000
        );

        $ga = self::get_ga_api($data);

        if( !is_object($ga) ) {
            echo '<div class="wrap"><h1>' . __('是否還沒設定GA服務帳號？','show-google-analytics-widget') . '</h1></div>';
            echo '<a href="/wp-admin/options-general.php?page=sig-ga-account" class="button button-primary widgets-chooser-add">' . __('立刻去新增','show-google-analytics-widget') . '</a>';
            if(!empty($ga)) echo '<p>' . __('相關訊息：','show-google-analytics-widget') . '<br>'.$ga.'</p>';

        } else {
        ?>
            <h1><?php echo __('從 ','show-google-analytics-widget') . date('Y-m-01') . __(' 到 ','show-google-analytics-widget') . date('Y-m-d')?></h1>
            <div id="mychart" style="height: 250px;"></div>
            <table class="">
            <tr>
                <th><?php _e('Total Results','show-google-analytics-widget')?></th>
                <td><?php echo $ga->getTotalResults() ?></td>
            </tr>
            <tr>
                <th><?php _e('Total Pageviews','show-google-analytics-widget')?></th>
                <td><?php echo $ga->getPageviews() ?>
            </tr>
            <tr>
                <th><?php _e('Total Visits','show-google-analytics-widget')?></th>
                <td><?php echo $ga->getVisits() ?></td>
            </tr>
            <tr>
                <th><?php _e('Result Date Range','show-google-analytics-widget')?></th>
                <td><?php echo $ga->getStartDate() ?> to <?php echo $ga->getEndDate() ?></td>
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

    }

    public static function get_ga_api($data='') {

        $config = self::get_ga_config();

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
                //$filter = 'country == United States && browser == Firefox || browser == Chrome';
                print_r($data);
                list($dimensions, $metrics, $sort_metric, $filter,$start_date, $end_date, $start_index, $max_results) = $data;

                require_once(SIG_GA_DIR.'/lib/gapi.class.php');

                if (file_exists($p12)) {
                    $ga = new gapi($account, $p12);
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

    public static function get_ga_config() {
        $config = get_option(SIG_GA_CONFIG);
        if ( is_array($config) ) {
            return $config;
        } else {
            return false;
        }
    }

    public static function updata_today_views($sig_ga_id,$new=1) {
        // today
        $data = array(
            array('date'),
            array('pageviews','visits'),
            'date',
            '',
            date('Y-m-d'),
            date('Y-m-d'),
            1,
            10000
        );

        $ga = self::get_ga_api($data);

        if( is_object($ga) ) {
            $option = array(
                'pageview'  => $ga->getPageviews(),
                'visit'     => $ga->getVisits(),
                'time'      => time()
            );

            if($new){
                add_option( 'sig_ga_today_'.$sig_ga_id, $option, '', 'no' );
            }else{
                update_option( 'sig_ga_today_'.$sig_ga_id, $option);
            }
        } else {
            $option = array();
        }

        return $option;
    }

    public static function updata_total_views($sig_ga_id,$new=1) {
        $data = array(
            array('date'),
            array('pageviews','visits'),
            'date',
            '',
            '',
            date('Y-m-d',strtotime("-1 days")),
            1,
            10000
        );

        $ga = self::get_ga_api($data);

        if( is_object($ga) ) {
            $option = array(
                'pageview'  => $ga->getPageviews(),
                'visit'     => $ga->getVisits(),
                'start'     => $ga->getStartDate(),
                'end'       => $ga->getEndDate()
            );

            if($new){
                add_option( 'sig_ga_tol_'.$sig_ga_id, $option, '', 'no' );
            }else{
                update_option( 'sig_ga_tol_'.$sig_ga_id, $option);
            }
        } else {
            $option = array();
        }

        return $option;
    }

    /**/
    public function setting_ga_option_menu(){
        add_options_page(
            __('設定GA服務帳號的參數','show-google-analytics-widget'),
            __('GA服務帳號','show-google-analytics-widget'),
            'administrator',
            'sig-ga-account',
            array($this,'ga_settings_page')
        );
        add_action( 'admin_init', array($this,'register_ga_opt_var') );
    }

    public function register_ga_opt_var() {
        register_setting( 'sig-ga-option-group', SIG_GA_CONFIG, 'handle_file_upload' );
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

    public function ga_settings_page() {

            $config = get_option(SIG_GA_CONFIG);
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
            <h2><?php _e('設定GA必要的參數','show-google-analytics-widget')?></h2>
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php settings_fields('sig-ga-option-group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('GA授權服務帳號：','show-google-analytics-widget')?></th>
                        <td><input type="text" class="regular-text" name="<?php echo SIG_GA_CONFIG?>[sig_ga_account]" value="<?php echo esc_attr( $config['sig_ga_account'] ); ?>" />
                        <p class="description">到 <a href="https://console.developers.google.com/" target="_blank">Google Developers</a> 申請，並下載p12檔案。再把這個服務帳號加入 Google Analytics 你的站台管理員，權限要可檢視和分析。 </p></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('上傳 P12 key檔：','show-google-analytics-widget')?></th>
                        <td><input type="file" class="regular-text" name="sig_ga_upload" />
                            <p class="description"><?php
                                if( isset($config['sig_ga_upload']) and $config['sig_ga_upload'] !==''){
                                    echo __('目前檔案位置：','show-google-analytics-widget') . $config['sig_ga_upload'];
                                    echo '<input type="hidden" name="'.SIG_GA_CONFIG.'[sig_ga_upload]" value="'.$config['sig_ga_upload'].'">';
                                }else{
                                    echo __('你可以先自行更改檔名再上傳。','show-google-analytics-widget');
                                }
                            ?></p></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('當日人次暫存時間：','show-google-analytics-widget')?></th>
                        <td><input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_cache]" value="<?php echo (empty(!$config['sig_ga_cache'])) ? esc_attr( $config['sig_ga_cache'] ) : SIG_GA_CACHE ; ?>"  onkeyup="value=value.replace(/[^\d.]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d.]/g,''))">秒
                        <p class="description"><?php _e('預設時間為600秒，過短的時間有可能造成網頁開啟過於緩慢。','show-google-analytics-widget')?></p></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('網站的 Profile ID：','show-google-analytics-widget')?></th>
                        <td><input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_id]" value="<?php echo esc_attr( $config['sig_ga_id'] ); ?>" />
                        <p class="description"><?php _e('到你的 Google Analytics 中，切換到你的站台，在瀏覽器的URL應該是這樣子『https://www.google.com/analytics/web/#report/visitors-overview/a1234b23478970 p1234567/』，找最後 p 之後的數字1234567','show-google-analytics-widget')?></p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <?php if($alert) echo __('(第一次設定，以上資料來自小工具設定，請按下儲存按鈕做轉換儲存。)','show-google-analytics-widget')?>
            </form>
        </div>
        <?php
    }

    public function custom_upload_mimes ( $existing_mimes=array() ) {
        $existing_mimes['p12'] = 'application/x-pkcs12';
        return $existing_mimes;
    }
}

new Sig_Ga_Widget();
