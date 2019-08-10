<?php

class Bcurl {

    var $channel;

    function curl() {
        $this->channel = curl_init ();
        // you might want the headers for http codes
        curl_setopt ( $this->channel, CURLOPT_HEADER, 0 );
        // you may need to set the http useragent for curl to operate as
        // curl_setopt ( $this->channel, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT'] );
        // you wanna follow stuff like meta and location headers
        curl_setopt ( $this->channel, CURLOPT_FOLLOWLOCATION, true );
        // you want all the data back to test it for errors
        curl_setopt ( $this->channel, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $this->channel, CURLOPT_VERBOSE, false );
        curl_setopt ( $this->channel, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $this->channel, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt ( $this->channel, CURLOPT_CONNECTTIMEOUT, 120 );
        curl_setopt ( $this->channel, CURLOPT_TIMEOUT, 120 );
    }
    
    function send($method, $url, $vars) {
        // if the $vars are in an array then turn them into a usable string
        if (is_array ( $vars )) :
            $vars = http_build_query ( $vars );
        endif;

        // the actual post bit
        if (strtolower ( $method ) == strtolower ( 'POST' )) :
            curl_setopt ( $this->channel, CURLOPT_POST, true );
            curl_setopt ( $this->channel, CURLOPT_POSTFIELDS, $vars );
        elseif (strtolower( $method ) == strtolower( 'GET' )) :
            $url .= "?" . $vars;
        endif;

        // setup the url to post / get from / to
        curl_setopt ( $this->channel, CURLOPT_URL, $url );

        // get data
        $response = curl_exec ( $this->channel );
        
        // Close the cURL session
        curl_close ( $this->channel );
        
        return $response;
    }
}