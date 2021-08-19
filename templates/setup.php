<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config = $this->get_ga_config;
?>
<div class="wrap">
    <h2><?php _e('設定 GA 帳號及相關參數','show-google-analytics-widget')?></h2>

    <div class="container-fluid">
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
                            <th scope="row"><?php _e('網站的 Profile ID：','show-google-analytics-widget')?></th>
                            <td><input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_id]" value="<?php if(!empty($config['sig_ga_id'])) echo esc_attr( $config['sig_ga_id'] ); ?>" />
                            <p class="description"><?php _e('到你的 Google Analytics 中，切換到你的站台，在瀏覽器的URL應該是這樣子『https://www.google.com/analytics/web/#report/visitors-overview/a1234b23478970 p1234567/』，找最後 p 之後的數字1234567','show-google-analytics-widget')?></p></td>
                        </tr>


                    </table>


                    <h2 class="title"><?php _e('單篇文章自動顯示瀏覽次數','show-google-analytics-widget')?></h2>
                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row"><?php _e('自訂描述：','show-google-analytics-widget')?></th>
                            <td><input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_pageview_label]" value="<?php
                                echo (!empty($config['sig_ga_pageview_label']) ? esc_attr($config['sig_ga_pageview_label']):'瀏覽次數：' ); ?>" onkeyup="document.getElementById('exp-views').innerText=((value)?value:'瀏覽次數：')" />
                                &nbsp;&nbsp;<span id="exp-views"><?php echo (!empty($config['sig_ga_pageview_label']) ? esc_attr($config['sig_ga_pageview_label']):'瀏覽次數：' ); ?></span>123456
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php _e('選擇顯示位置：','show-google-analytics-widget')?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>顯示位置</span></legend>
                                    <label for="sig_ga_show_top"><input type="checkbox" id="sig_ga_show_top" name="<?php echo SIG_GA_CONFIG?>[sig_ga_show_top]" value="1" <?php if(!empty($config['sig_ga_show_top'])) echo 'checked="checked"';?> /> 顯示在文章開頭</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label for="sig_ga_show_bom"><input type="checkbox" id="sig_ga_show_bom" name="<?php echo SIG_GA_CONFIG?>[sig_ga_show_bom]" value="1" <?php if(!empty($config['sig_ga_show_bom'])) echo 'checked="checked"';?> /> 顯示在文章結尾</label>
                                </fieldset>
                                <p>若啟用此項目，會取代文章已經加入的短代碼 [sig_post_pv label="瀏覽："]</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('暫存時間：','show-google-analytics-widget')?></th>
                            <td>
                                <input type="text" class="" name="<?php echo SIG_GA_CONFIG?>[sig_ga_pageview_cache]" value="<?php
                                echo isset($config['sig_ga_pageview_cache']) ? esc_attr($config['sig_ga_pageview_cache']):SIG_GA_CACHE ; ?>" onkeyup="value=value.replace(/[^\d.]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d.]/g,''))"/>&nbsp;秒<p>設定暫存時間，可避免重複呼叫 API、加快載入速度（輸入 0 表示不使用暫存）。</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>

                </form>
            </div>
            <!-- //left -->
            <!-- right -->
            <div class="col-12 col-sm-4">
                <div style="background-color: #fff;padding: 15px; line-height: 1.8;">
                    <h2><?php echo __('補充說明','show-google-analytics-widget')?></h2>
                    <ol style="font-size: 15px;">
                        <li>如何取得GA服務帳號、P12 Key教學：在這推薦Gill吉兒的文章，步驟非常詳細。 <a href="https://reurl.cc/pm5ERZ" target="_blank">https://reurl.cc/pm5ERZ</a></li>
                        <li>請注意！本外掛使用 Google Analytics API(V3)，每日有呼叫次數限制，超過的請求次數，您可能需要負擔費用。(您可增加資料重新獲取的間隔秒數來避免超出呼叫次數)</li>
                        <li><?php echo __('文章點閱次數的短代碼寫法：','show-google-analytics-widget')?><br>[sig_post_pv class="<?php echo SIG_GA_PV_CLASS?>" label="瀏覽："]</li>
                    </ol>
                </div>
            </div>
            <!-- //right -->
        </div>
    </div>
</div>
