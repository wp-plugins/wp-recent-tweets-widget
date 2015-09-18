<?php
/* 
Plugin Name: Recent Tweets Widget  
Plugin URI: http://wordpress.org/extend/plugins/wp-recent-tweets-widget/
Description: Easy to add twitter recent tweets on your WordPress site by using the Recent Tweets Widget plugin.
Version: 1.0  
Author: Chandrakesh Kumar   
Author URI: http://www.wpchandra.com/
Tags: twitter,twitter recent tweets, twitter feed, tweet, twitter widget, feed, widget, twitter sidebar, social, social media, sidebar, plugin
*/      
              
// ===============================WP Recent Tweets Widget  ======================================

class WP_Recent_Tweets_Widget extends WP_Widget {

    /** constructor */ 
    public function __construct() { 
        parent::__construct(
            'twitter_widget', // Base ID
            'Recent Tweets', // Name
            array( 'description' => __( 'Display latest Tweets from Twitter.', 'wpchandra' ), ) // Args
        );
    }

    function widget( $args, $instance ) {
        extract( $args, EXTR_SKIP );
        $instance = wp_parse_args( $instance, array(
            'title'             => 'Recent Tweets',
            'query'             => '@twitter',
            'number'            =>  5,
            'show_follow'       => 'false',
            'show_avatar'       => 'true',
            'show_account'      => 'true',
            'consumer_key'      => '',
            'consumer_secret'   => ''
        ) );
        $this->get_tweets_bearer_token( $instance['consumer_key'], $instance['consumer_secret'] );
        echo $before_widget;
        echo '<section id="twitter-sidebar">';
        echo $before_title.$instance['title'].$after_title;
        echo '<div class="twitter twitter-inner '.(isset($instance['show_follow'])&&$instance['show_follow']?'has-follow-button':'').'" >';
        $this->get_tweets($instance);
        echo '</div>';
        echo '</section>';
        echo $after_widget;
    }

    function update( $new_instance, $old_instance ) {
        if( ! isset($new_instance['show_follow']) ) {
            $new_instance['show_follow'] = false;
        }
        if( ! isset($new_instance['show_avatar']) ) {
            $new_instance['show_avatar'] = false;
        }
        if( ! isset($new_instance['show_account']) ) {
            $new_instance['show_account'] = false;
        }
        $updated_instance = $new_instance;
        return $updated_instance;
    }

    function form( $instance ) {
        $instance = wp_parse_args( $instance, array(
            'title'             => 'Recent Tweets',
            'query'             => '@twitter',
            'number'            =>  5,
            'show_follow'       => 'false',
            'show_avatar'       => 'true',
            'show_account'      => 'true',
            'consumer_key'      => '',
            'consumer_secret'   => ''
        ) );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title','wpchandra'); ?></label>
            <br>
            <input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?>" class="widefat" value="<?php echo $instance['title'] ?>">
        </p>
        <p><label for="<?php echo $this->get_field_id('consumer_key'); ?>"><?php _e('Consumer Key','wpchandra') ?></label><br />
            <input type="text" name="<?php echo $this->get_field_name('consumer_key') ?>" id="<?php echo $this->get_field_id('consumer_key') ?>" class="widefat" value="<?php echo $instance['consumer_key'] ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('consumer_secret') ?>"><?php _e('Consumer Secret', 'wpchandra') ?></label><br />
            <input type="text" name="<?php echo $this->get_field_name('consumer_secret') ?>"  class="widefat" id="<?php echo $this->get_field_id('consumer_secret') ?>" value="<?php echo $instance['consumer_secret'] ?>">
        </p>
        <p><label for="<?php echo $this->get_field_id('query') ?>"><?php _e('Search Query (<a href="https://dev.twitter.com/docs/using-search" target="_blank" title="Read more about Twitter Search query">?</a>)','wpchandra') ?></label><br />
            <input type="text" name="<?php echo $this->get_field_name('query'); ?>" id="<?php echo $this->get_field_id('query'); ?>" class="widefat" value="<?php echo $instance['query'] ?>">
        </p>
        <p><label for="<?php echo $this->get_field_id('number') ?>"><?php _e('Number of Tweets','wpchandra') ?></label>&nbsp;<input type="text" name="<?php echo $this->get_field_name
                ('number') ?>" id="<?php echo $this->get_field_id
                ('number') ?>" size="3" value="<?php echo $instance['number'] ?>" >
        </p>
        <p><label for="<?php echo $this->get_field_id('show_follow') ?>">
                <input type="checkbox" name="<?php echo $this->get_field_name('show_follow'); ?>" id="<?php echo $this->get_field_id('show_follow'); ?>" <?php checked( 'true', $instance['show_follow'] ) ?> value="true" >
                <?php _e('Show Follow Button?', 'wpchandra') ?></label>
        </p>
        <p><label for="<?php echo $this->get_field_id('show_account') ?>">
                <input type="checkbox" name="<?php echo $this->get_field_name('show_account'); ?>" id="<?php echo $this->get_field_id('show_account'); ?>" <?php checked( 'true', $instance['show_account'] ) ?>  value="true"  >
                <?php _e('Show Account Info?', 'wpchandra') ?></label>
        </p>
        <p><label for="<?php echo $this->get_field_id('show_avatar') ?>">
                <input type="checkbox" name="<?php echo $this->get_field_name('show_avatar'); ?>" id="<?php echo $this->get_field_id('show_avatar'); ?>" <?php checked( 'true', $instance['show_avatar'] ) ?>  value="true" >
                <?php _e('Show User Avatar?', 'wpchandra') ?></label>
        </p>

    <?php
    }

    function update_tweet_urls($content) {
        $maxLen = 16;
        //split long words
        $pattern = '/[^\s\t]{'.$maxLen.'}[^\s\.\,\+\-\_]+/';
        $content = preg_replace($pattern, '$0 ', $content);

        //
        $pattern = '/\w{2,4}\:\/\/[^\s\"]+/';
        $content = preg_replace($pattern, '<a href="$0" title="" target="_blank">$0</a>', $content);

        //search
        $pattern = '/\#([a-zA-Z0-9_-]+)/';
        $content = preg_replace($pattern, '<a href="https://twitter.com/#%21/search/%23$1" title="" target="_blank">$0</a>', $content);

        //user
        $pattern = '/\@([a-zA-Z0-9_-]+)/';
        $content = preg_replace($pattern, '<a href="https://twitter.com/#!/$1" title="" target="_blank">$0</a>', $content);

        return $content;
    }


    function get_tweets_bearer_token( $consumer_key, $consumer_secret ){
        $consumer_key = rawurlencode( $consumer_key );
        $consumer_secret = rawurlencode( $consumer_secret );

        $token = maybe_unserialize( get_option( 'my_twitterwidget' ) );

        if( ! is_array($token) || empty($token) || $token['consumer_key'] != $consumer_key || empty($token['access_token']) ) {
            $authorization = base64_encode( $consumer_key . ':' . $consumer_secret );

            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Authorization: Basic '.$authorization,
                    'content' => 'grant_type=client_credentials'
                )
            );
            $context = stream_context_create($options);
            $result = json_decode( @file_get_contents('https://api.twitter.com/oauth2/token', false, $context) );
            $token = serialize( array(
                'consumer_key'      => $consumer_key,
                'access_token'      => $result->access_token
            ) );
            update_option( 'my_twitterwidget', $token );
        }
    }

    function get_tweets( $instance ){
        extract($instance);
        $token = maybe_unserialize( get_option( 'my_twitterwidget' ) );
        if( strpos($query, 'from:') === 0  ) {
            $query_type = 'user_timeline';
            $query = substr($query, 5);
            $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name='.rawurlencode($query).'&count='.$number;
        } else {
            $query_type = 'search';
            $url =  'https://api.twitter.com/1.1/search/tweets.json?q='.rawurlencode($query).'&count='.$number;
        }

        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Authorization: Bearer '.$token['access_token']
            )
        );
        $context = stream_context_create($options);
        $result = json_decode( @file_get_contents( $url, false, $context) );

        if( isset( $result->errors ) && $result->code == 89 ) {
            delete_option( 'my_twitterwidget' );
            $this->get_tweets_bearer_token();
            return $this->get_tweets();
        }

        $tweets = array();
        if( 'user_timeline' == $query_type ) {
            if( !empty($result) ) {
                $tweets = $result;
            }
        } else {
            if( !empty($result->statuses) ) {
                $tweets = $result->statuses;
            }

        }

        $follow_button = '<a href="https://twitter.com/__name__" class="twitter-follow-button" data-show-count="false" data-lang="en">Follow @__name__</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';

        if( !empty($tweets) ) {
            foreach ($tweets as $tweet ) {
                $text = $this->update_tweet_urls( $tweet->text );
                $time = human_time_diff( strtotime($tweet->created_at), time() );
                $url = 'http://twitter.com/'.$tweet->user->id.'/status/'.$tweet->id_str;
                $screen_name = $tweet->user->screen_name;
                $name = $tweet->user->name;
                $profile_image_url = $tweet->user->profile_image_url;

                echo '<p class="tweet-item '.$query_type.'">';
                if( 'search' == $query_type ) {
                    echo '<div class="twitter-user">';
                    if( $show_account ) {
                        echo '<a href="https://twitter.com/'.$screen_name.'" class="user">';
                        if( $show_avatar && $profile_image_url ) {
                            echo '<img src="'.$profile_image_url.'" width="40" height="40" style="float:left; margin: 5px 5px 5px 0;">';
                        }
                        echo '&nbsp;<strong class="name">'.$name.'</strong>&nbsp;<span class="screen_name">@'.$screen_name.'</span></a>';
                    }
                    echo '</div>';
                }

                echo    '<div class="tweet-content">'.$text.' <span class="time"><a target="_blank" title="" href="'.$url.'"> about '.$time.' ago</a></span></div>';

                echo '</p>';
            }

            if( 'user_timeline' == $query_type ) {
                echo    '<div class="twitter-user" style="padding-top: 10px;">';
                if( $show_avatar && $profile_image_url ) {
                    echo '<a href="https://twitter.com/'.$screen_name.'" class="user"><img src="'.$profile_image_url.'" width="16px" height="16px" ></a>&nbsp;&nbsp;';
                }
                if( $show_follow ) {
                    echo str_replace('__name__', $screen_name, $follow_button);
                }
                echo    '</div>';
            }


        }
    }
}

// Register and load the widget
function wp_load_widget() {
    register_widget( 'WP_Recent_Tweets_Widget' );
}
add_action( 'widgets_init', 'wp_load_widget' );

?>