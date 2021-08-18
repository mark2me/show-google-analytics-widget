<?php

/*-----------------------------------------------
* add WP_Widget
-----------------------------------------------*/
class  Sig_Ga_Views_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            SIG_GA_VIEW_WIDGET,
            __('顯示GA瀏覽統計', 'show-google-analytics-widget' ),
            array (
                'description' => __('顯示GA瀏覽的統計數字','show-google-analytics-widget')
            )
        );
    }

    public function widget( $args, $instance ) {

        extract( $args );

        $sig_ga_title   = (!empty($instance['sig_ga_title'])) ? $instance['sig_ga_title'] : __('參觀人氣','show-google-analytics-widget');
        $sig_ga_type    = (!empty($instance['sig_ga_type'])) ? $instance['sig_ga_type'] : 0;
        $sig_ga_nums    = (!empty($instance['sig_ga_nums'])) ? $instance['sig_ga_nums'] : 0;
        $sig_ga_cache   = (!empty($instance['sig_ga_cache'])) ? $instance['sig_ga_cache'] : 60*60;
        $sig_ga_ajax    = (!empty($instance['sig_ga_ajax'])) ? $instance['sig_ga_ajax'] : 0;

        $widget_id = (!empty($args['widget_id'])) ? $args['widget_id'] : '';

        $content = $before_widget;
        $content .= $before_title . $sig_ga_title . $after_title;

        if( !is_admin() && !empty($widget_id) && !empty($sig_ga_ajax) ){
            $content .= '<div id="ga-box-'.$widget_id.'"><img src="'. plugin_dir_url(__FILE__) .'assets/img/loading.gif"><script type="text/javascript">jQuery(document).ready(function($) {$.get(\'/wp-admin/admin-ajax.php?action=sig-ga-widget&type=views&id='.$widget_id.'&t='.time().'\', function(data) {$(\'#ga-box-'.$widget_id.'\').html(data);    });});</script></div>';
        }else{
            $obj = new SigGaWidget();
            $content .= $obj->show_ga_views_widget($instance);
        }

        $content .= $after_widget;

        echo $content;
        return;

    }

    public function form( $instance ) {

        $defaults = [
            'sig_ga_title'  => __('參觀人氣','show-google-analytics-widget'),
            'sig_ga_type'   => 0,
            'sig_ga_nums'   => 0,
            'sig_ga_cache'  => 60*60,
            'sig_ga_ajax'   => 0
        ];

        $instance = wp_parse_args( (array) $instance, $defaults );

    ?>
        <p>
            <label><?php _e('自訂標題：','show-google-analytics-widget')?></label>
            <input class="widefat" type="text" name="<?php echo $this->get_field_name('sig_ga_title');?>" value="<?php if(!empty($instance['sig_ga_title'])) echo esc_attr($instance['sig_ga_title']); ?>">
        </p>
        <p>
            <label><?php _e('顯示類型：','show-google-analytics-widget')?></label>
            <select class="widefat" size="1" name="<?php echo $this->get_field_name('sig_ga_type');?>">
                <option value="0" <?php if($instance['sig_ga_type']==0) echo 'selected'?>><?php _e('Visit(人次)','show-google-analytics-widget')?></option>
                <option value="1" <?php if($instance['sig_ga_type']==1) echo 'selected'?>><?php _e('Pageview(頁次)','show-google-analytics-widget')?></option>
            </select>
        </p>
        <p>
            <label><?php _e('調整計次：','show-google-analytics-widget')?></label>
            <input class="widefat" type="number" name="<?php echo $this->get_field_name('sig_ga_nums');?>" value="<?php if(isset($instance['sig_ga_nums'])) echo esc_attr($instance['sig_ga_nums']); ?>" placeholder="<?php _e('輸入起跳的數字','show-google-analytics-widget')?>" onkeyup="value=value.replace(/[^0-9]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^0-9]/g,''))">
        </p>
        <p>
            <label><?php _e('快取時間：','show-google-analytics-widget')?></label>
            <input class="" type="number" name="<?php echo $this->get_field_name('sig_ga_cache');?>" value="<?php if(isset($instance['sig_ga_cache'])) echo esc_attr($instance['sig_ga_cache']); ?>" min="0"> <?php _e('秒 (0表示不做快取)','show-google-analytics-widget')?>
        </p>
        <p>
            <input class="" type="checkbox" name="<?php echo $this->get_field_name('sig_ga_ajax');?>" value="1" <?php checked('1',esc_attr($instance['sig_ga_ajax']))?>> 避免網頁暫存影響
        </p>


    <?php
    }

    public function update( $new_instance, $old_instance ) {

        $instance = $old_instance;

        $instance['sig_ga_title']      = strip_tags( $new_instance['sig_ga_title'] );
        $instance['sig_ga_type']       = strip_tags( $new_instance['sig_ga_type'] );
        $instance['sig_ga_nums']       = strip_tags( preg_replace('/[^0-9]/','',$new_instance['sig_ga_nums']) );
        $instance['sig_ga_cache']      = strip_tags( preg_replace('/[^0-9]/','',$new_instance['sig_ga_cache']) );
        $instance['sig_ga_ajax']       = (!empty($new_instance['sig_ga_ajax'])) ? 1:0;

        if(empty($instance['sig_ga_nums'])) $instance['sig_ga_nums'] = 0;
        if(empty($instance['sig_ga_cache'])) $instance['sig_ga_cache'] = 0;


        //clear transient
        delete_transient('ga_today_data');
        delete_transient('ga_all_view_data');

        return $instance;
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

        $sig_ga_hot_title   = (!empty($instance['sig_ga_hot_title'])) ? $instance['sig_ga_hot_title'] : __('熱門文章','show-google-analytics-widget');
        $sig_ga_hot_day     = (!empty($instance['sig_ga_hot_day'])) ? $instance['sig_ga_hot_day'] : 0;
        $sig_ga_hot_nums    = (!empty($instance['sig_ga_hot_nums'])) ? $instance['sig_ga_hot_nums'] : 5;
        $sig_ga_hot_cache   = (!empty($instance['sig_ga_hot_cache'])) ? $instance['sig_ga_hot_cache'] : 60*60;
        $sig_ga_hot_ajax    = (!empty($instance['sig_ga_hot_ajax'])) ? $instance['sig_ga_hot_ajax'] : 0;

        $widget_id = (!empty($args['widget_id'])) ? $args['widget_id'] : '';

        $content = $before_widget;
        $content .= $before_title . $sig_ga_hot_title . $after_title;

        if( !is_admin() && !empty($widget_id) && !empty($sig_ga_hot_ajax) ){
            $content .= '<div id="ga-box-'.$widget_id.'"><img src="'. plugin_dir_url(__FILE__) .'assets/img/loading.gif"><script type="text/javascript">jQuery(document).ready(function($) {$.get(\'/wp-admin/admin-ajax.php?action=sig-ga-widget&type=hot&id='.$widget_id.'&t='.time().'\', function(data) {$(\'#ga-box-'.$widget_id.'\').html(data);    });});</script></div>';
        }else{
            $obj = new SigGaWidget();
            $content .= $obj->show_ga_hot_widget($instance);
        }
        $content .= $after_widget;

        echo $content;
        return;
    }


    public function form( $instance ) {

        $defaults = [
            'sig_ga_hot_title' => __('熱門文章','show-google-analytics-widget'),
            'sig_ga_hot_day'  => 0,
            'sig_ga_hot_nums'  => 5,
            'sig_ga_hot_cache' => 60*60,
            'sig_ga_hot_ajax'   => 0
        ];

        $instance = wp_parse_args( (array) $instance, $defaults );
        ?>
        <p>
            <label><?php _e('自訂標題：','show-google-analytics-widget')?></label>
            <input class="widefat" type="text" name="<?php echo $this->get_field_name('sig_ga_hot_title'); ?>" value="<?php if(!empty($instance['sig_ga_hot_title'])) echo esc_attr($instance['sig_ga_hot_title']); ?>">
        </p>
        <p>
            <label><?php _e('選定日期：','show-google-analytics-widget')?></label>
            <select class="" size="1" name="<?php echo $this->get_field_name('sig_ga_hot_day'); ?>">
                <option value="0" <?php if($instance['sig_ga_hot_day']==0) echo 'selected'?>><?php _e('今天','show-google-analytics-widget')?></option>
                <option value="1" <?php if($instance['sig_ga_hot_day']==1) echo 'selected'?>><?php _e('昨天','show-google-analytics-widget')?></option>
            </select>
        </p>
        <p>
            <label><?php _e('顯示文章數：','show-google-analytics-widget')?></label>
            <input class="" type="number" name="<?php echo $this->get_field_name('sig_ga_hot_nums'); ?>" value="<?php if(isset($instance['sig_ga_hot_nums']))  echo esc_attr($instance['sig_ga_hot_nums']); ?>"  onkeyup="value=value.replace(/[^0-9]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^0-9]/g,''))" min="1">
        </p>
        <p>
            <label><?php _e('快取時間：','show-google-analytics-widget')?></label>
            <input class="" type="number" name="<?php echo $this->get_field_name('sig_ga_hot_cache'); ?>" value="<?php if(isset($instance['sig_ga_hot_cache'])) echo esc_attr($instance['sig_ga_hot_cache']); ?>" min="0"> <?php _e('秒 (0表示不做快取)','show-google-analytics-widget')?>
        </p>
        <p>
            <input class="" type="checkbox" name="<?php echo $this->get_field_name('sig_ga_hot_ajax');?>" value="1" <?php checked('1',esc_attr($instance['sig_ga_hot_ajax']))?>> 避免網頁暫存影響
        </p>
        <?php
    }


    public function update( $new_instance, $old_instance ) {

        $instance = $old_instance;

        $instance['sig_ga_hot_title']      = strip_tags( $new_instance['sig_ga_hot_title'] );
        $instance['sig_ga_hot_day']        = strip_tags( $new_instance['sig_ga_hot_day'] );
        $instance['sig_ga_hot_nums']       = strip_tags( preg_replace('/[^0-9]/','',$new_instance['sig_ga_hot_nums']) );
        $instance['sig_ga_hot_cache']      = strip_tags( preg_replace('/[^0-9]/','',$new_instance['sig_ga_hot_cache']) );
        $instance['sig_ga_hot_ajax']       = (!empty($new_instance['sig_ga_hot_ajax'])) ? 1:0;

        if(empty($instance['sig_ga_hot_nums'])) $instance['sig_ga_hot_nums'] = 10;
        if(empty($instance['sig_ga_hot_cache'])) $instance['sig_ga_hot_cache'] = 0;

        //clear transient
        delete_transient('ga_hot_data');

        return $instance;
    }
}
