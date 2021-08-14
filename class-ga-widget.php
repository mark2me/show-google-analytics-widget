<?php

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

        $is_ga_ajax = ( !empty($config['sig_ga_ajax']) ) ? $config['sig_ga_ajax'] : 0;

        $content = $before_widget;
        $content .= $before_title . $sig_ga_title . $after_title;

        if( !empty($is_ga_ajax) && $is_ga_ajax==1 ){

            $content .= '<div id="sec-'.$widget_id.'"><img src="'. plugin_dir_url(__FILE__) .'assets/img/loading.gif"></div>';
            $content .= $after_widget;
            $content .= '<script type="text/javascript">jQuery(document).ready(function($) {$.get(\'/wp-admin/admin-ajax.php?action='.SIG_GA_WIDGET.'&id='.$widget_id.'&t='.time().'\', function(data) {$(\'#sec-'.$widget_id.'\').html(data);    });});</script>';
        }else{
            $content .= $obj->show_widget_views($widget_id);
            $content .= $after_widget;
        }

        echo $content;
        return;

    }
}
