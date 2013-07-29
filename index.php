<?php
namespace php_require\php_mcurl;

class Mcurl {

    private $calls = array();

    /*
        Execute any $calls without a response value.
    */

    private function execute() {

        $handles = array();
        $mHandle = curl_multi_init();
        $defaults = array(
            CURLOPT_URL => "",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1
        );

        foreach ($this->calls as $url => $options) {

            if (!isset($options["response"])) {
                // create a curl handle
                $handle = curl_init();
                $defaults[CURLOPT_URL] = $url;
                curl_setopt_array($handle, ($options + $defaults));
                curl_multi_add_handle($mHandle, $handle);
                $handles[$url] = $handle;
            }
        }

        $running=null;
        // Execute the handles
        do {
            curl_multi_exec($mHandle, $running);
        } while($running > 0);

        foreach ($handles as $url => $handle) {
            $this->calls[$url]["response"] = curl_multi_getcontent($handle);
            curl_multi_remove_handle($mHandle, $handle);
        }

        curl_multi_close($mHandle);
        unset($handles);
    }

    /*
        Return the response for the given $url if it was previously added.
    */

    private function read($url) {

        $this->execute();

        if (!isset($this->calls[$url]["response"])) {
            return null;
        }

        return $this->calls[$url]["response"];
    }

    /*
        Returns a function to be called later which in turn will return the $url response.
    */

    public function add($url, $options = array()) {
        $this->calls[$url] = $options;
        return function () use ($url) {
            return $this->read($url);
        };
    }
}

$module->exports = new Mcurl();
