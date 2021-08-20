<?php

class Sig_Ga_Data {

    // widget
    public static function get_today_data(){

        $sig = new SigGaWidget();
        $ga = $sig->call_ga_api([
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

            return $data;

        } else {

            return false;
        }

    }

    // widget
    public static function get_all_view_data() {

        $sig = new SigGaWidget();
        $ga = $sig->call_ga_api([
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

            return $data;

        } else {

            return false;
        }

    }

    // single post view
    public static function get_post_view_data() {

        $uri_path = str_replace(home_url(), '', get_permalink());
        $uri_path = urldecode($uri_path);

        $sig = new SigGaWidget();
        $ga = $sig->call_ga_api([
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

    // admin
    public static function get_month_data(){

        $sig = new SigGaWidget();
        $ga = $sig->call_ga_api([
            array('date'),
            array('pageviews','visits'),
            'date',
            '',
            current_time('Y-m-01'),
            current_time('Y-m-d'),
            1,
            31
        ]);

        if( is_object($ga) ){
            $data = [
                'startDate' => $ga->getStartDate(),
                'endDate' => $ga->getEndDate(),
                'pageViews' => $ga->getPageviews(),
                'visits' => $ga->getVisits(),
                'time' => current_time('Y-m-d H:i:s')
            ];

            foreach( $ga->getResults() as $rs ){
                $data['results'][] = [
                    'date' => $rs->getDate(),
                    'pageviews' => $rs->getPageviews(),
                    'visits' => $rs->getVisits()
                ];
            }

            return $data;

        } else {
            return $ga;
        }

    }

    public static function get_total_data($nums=0){

        $nums = ($nums>0) ? $nums:5;

        $sig = new SigGaWidget();
        $ga = $sig->call_ga_api([
            array('year'),
            array('pageviews','visits'),
            'year',
            '',
            (date('Y')-$nums).'-01-01',
            date('Y').'-01-01',
            1,
            $nums
        ]);

        if( is_object($ga) ) {
            $data = [
                'startDate' => $ga->getStartDate(),
                'endDate' => $ga->getEndDate(),
                'pageViews' => $ga->getPageviews(),
                'visits' => $ga->getVisits(),
                'time' => current_time('Y-m-d H:i:s')
            ];

            foreach( $ga->getResults() as $rs ){
                $data['results'][] = [
                    'year' => $rs->getYear(),
                    'pageviews' => $rs->getPageviews(),
                    'visits' => $rs->getVisits()
                ];
            }

            return $data;
        }else{
            return $ga;
        }

    }

    public static function get_hot_data($nums=0,$day=0){

        $d = ($day==0) ? date('Y-m-d') : date('Y-m-d',strtotime("-1 days"));

        $sig = new SigGaWidget();
        $ga = $sig->call_ga_api([
            array('pageTitle','pagepath'),
            array('pageviews'),
            '-pageviews',
            null,
            $d,
            $d,
            1,
            ($nums>0) ? $nums:10
        ]);

        if( is_object($ga) ) {
            $data = [
                'startDate' => $ga->getStartDate(),
                'endDate' => $ga->getEndDate(),
                'pageViews' => $ga->getPageviews(),
                'time' => current_time('Y-m-d H:i:s')
            ];

            foreach( $ga->getResults() as $rs ){
                $data['results'][] = [
                    'pageviews' => $rs->getPageviews(),
                    'pageTitle' => $rs->getPagetitle(),
                    'pagepath' => $rs->getPagepath()
                ];
            }

            return $data;

        }else{
            return $ga;
        }

    }

}