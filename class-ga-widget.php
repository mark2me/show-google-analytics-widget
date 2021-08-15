<?php

/*-----------------------------------------------
* add WP_Widget
-----------------------------------------------*/
class  Sig_Ga_Views_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            SIG_GA_VIEW_WIDGET,
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
        delete_transient('ga_today_data');
        delete_transient('ga_all_view_data');

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

        $is_ga_ajax = ( !empty($config['sig_ga_ajax']) ) ? $config['sig_ga_ajax'] : 0;

        $content = $before_widget;
        $content .= $before_title . $sig_ga_title . $after_title;

        if( !empty($is_ga_ajax) && $is_ga_ajax==1 ){

            $content .= '<div id="box-'.$widget_id.'"><img src="'. plugin_dir_url(__FILE__) .'assets/img/loading.gif"></div>';
            $content .= $after_widget;
            $content .= '<script type="text/javascript">jQuery(document).ready(function($) {$.get(\'/wp-admin/admin-ajax.php?action='.SIG_GA_VIEW_WIDGET.'&id='.$widget_id.'&t='.time().'\', function(data) {$(\'#box-'.$widget_id.'\').html(data);    });});</script>';
        }else{
            $content .= $obj->show_ga_views_widget($widget_id);
            $content .= $after_widget;
        }

        echo $content;
        return;

    }
}


/*
    Sig_Ga_Hot_Widget
*/
class Sig_Ga_Hot_Widget extends WP_Widget {


    function __construct() {
        parent::__construct(
            SIG_GA_HOT_WIDGET,
            __('顯示GA熱門文章', 'show-google-analytics-widget' ),
            array (
                'description' => __('顯示GA熱門文章','show-google-analytics-widget')
            )
        );
    }


    public function widget( $args, $instance ) {

        extract( $args );

        $sig_ga_hot_title   = $instance['sig_ga_hot_title'];
        $sig_ga_hot_day     = $instance['sig_ga_hot_day'];
        $sig_ga_hot_nums    = $instance['sig_ga_hot_nums'];
        $sig_ga_hot_cache   = $instance['sig_ga_hot_cache'];


        if( false === $ga_hot_data = get_transient('ga_hot_data') ){
            $ga_hot_data = Sig_Ga_Data::get_hot_data($sig_ga_hot_nums,$sig_ga_hot_day);
            if($ga_hot_data!==false) set_transient('ga_hot_data', $ga_hot_data, $sig_ga_hot_cache);
        }

        $post = '';

        if( !empty($ga_hot_data['results']) && count($ga_hot_data['results'])>0 ){
            $post .= '<ul>';
            foreach( $ga_hot_data['results'] as $k => $rs) {
                $post .= "<li><a href=\"{$rs['pagepath']}\">{$rs['pageTitle']}</a></li>";
            }
            $post .= '</ul>';
        }else{
            $post .= '--';
        }

        $content = $before_widget;
        $content .= $before_title . $sig_ga_hot_title . $after_title;
        $content .= $post;
        $content .= $after_widget;

        echo $content;

    }


    public function form( $instance ) {

        $defaults = [
            'sig_ga_hot_title' => __('熱門文章','show-google-analytics-widget'),
            'sig_ga_hot_day'  => 0,
            'sig_ga_hot_nums'  => 5,
            'sig_ga_hot_cache' => 60*60
        ];

        $instance = wp_parse_args( (array) $instance, $defaults );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_hot_title'); ?>"><?php _e('自定標題：','show-google-analytics-widget')?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('sig_ga_hot_title'); ?>" name="<?php echo $this->get_field_name('sig_ga_hot_title'); ?>" value="<?php echo $instance['sig_ga_hot_title']; ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_hot_day'); ?>"><?php _e('今日或昨日：','show-google-analytics-widget')?></label>
            <select class="" size="1" id="<?php echo $this->get_field_id('sig_ga_hot_day'); ?>" name="<?php echo $this->get_field_name('sig_ga_hot_day'); ?>">
                <option value="0" <?php if($instance['sig_ga_hot_day']==0) echo 'selected'?>><?php _e('Today','show-google-analytics-widget')?></option>
                <option value="1" <?php if($instance['sig_ga_hot_day']==1) echo 'selected'?>><?php _e('Yesterday','show-google-analytics-widget')?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_hot_nums'); ?>"><?php _e('顯示文章數：','show-google-analytics-widget')?></label>
            <input class="" type="text" id="<?php echo $this->get_field_id('sig_ga_hot_nums'); ?>" name="<?php echo $this->get_field_name('sig_ga_hot_nums'); ?>" value="<?php echo $instance['sig_ga_hot_nums']; ?>"  onkeyup="value=value.replace(/[^0-9]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^0-9]/g,''))">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('sig_ga_hot_cache'); ?>"><?php _e('快取時間：','show-google-analytics-widget')?></label>
            <input class="" type="text" id="<?php echo $this->get_field_id('sig_ga_hot_cache'); ?>" name="<?php echo $this->get_field_name('sig_ga_hot_cache'); ?>" value="<?php echo $instance['sig_ga_hot_cache']; ?>"> <?php _e('秒 (0表示不做快取)','show-google-analytics-widget')?>
        </p>

        <?php
    }


    public function update( $new_instance, $old_instance ) {

        $instance = $old_instance;

        $instance['sig_ga_hot_title']      = strip_tags( $new_instance['sig_ga_hot_title'] );
        $instance['sig_ga_hot_day']        = strip_tags( $new_instance['sig_ga_hot_day'] );
        $instance['sig_ga_hot_nums']       = strip_tags( preg_replace('/[^0-9]/','',$new_instance['sig_ga_hot_nums']) );
        $instance['sig_ga_hot_cache']       = strip_tags( preg_replace('/[^0-9]/','',$new_instance['sig_ga_hot_cache']) );

        if(empty($instance['sig_ga_hot_nums'])) $instance['sig_ga_hot_nums'] = 10;
        if(empty($instance['sig_ga_hot_cache'])) $instance['sig_ga_hot_cache'] = 0;

        //clear transient
        delete_transient('ga_hot_data');
        return $instance;
    }


}
