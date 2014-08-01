<?php
/**
 * This class simply provides a small wrapper around the Blesta reseller API (http://docs.blesta.com/display/dev/Reseller+API)
 * 
 * This is intended to be used for Blesta modules.
 */
class API {
	/**
	 * @param $row_meta stdObject Representation of various settings.
	 * 								user - The email address for account.blesta.com
	 * 								pass - The password for 'user'
	 * 								test - If enabled, API calls are simulated, not ran
	 * 								nossl - Needed on some systems to circumvent SSL verification in cURL.
	 */
    public function __construct($row_meta){
        $this->user = $row_meta->user;
        $this->pass = $row_meta->pass;
        $this->test = isset($row_meta->test) ? $row_meta->test : 0;
        $this->nossl = isset($row_meta->nossl) ? $row_meta->nossl : 0;
    }
    
    /**
     * Establishes the cURL call and returns the data of the API request.
     * 
     * @param $req string API request to be made (i.e.: "getcredit" for account credit amount)
     * @param $params array Key/Value pair of 'argument' => 'value' to be passed
     * @param $action string The HTTP verb to perform.
     * 
     * @return stdObject cURL results and HTTP response code.
     */
    public function call($req, array $params = array(), $action="GET"){
        $vars = array();
        
        // Params of the request need to be wrapped in vars[] (i.e.: license needs to be vars[license])
        foreach($params as $k=>$v){
            $vars["vars[". $k ."]"] = $v;
        }
        
        if($this->test)
            $vars["vars[test_mode]"] = "true";
        
        /**
         * We need to build a proper URI request query if we are using GET.
         * Otherwise, the params are passed in the body.
         */
        $url = ($action == "GET") ? http_build_query($vars) : "";
        
        // The endpoint is virtually all the same, only thing different is the request itself
        $ch = curl_init("https://account.blesta.com/plugin/blesta_reseller/v2/index/".$req.".json?".$url);
        
        // Blesta only supports WWW-BASIC authentication
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        
        // We need the response data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if($action != "GET")
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		
		// Since there's no "easy" way for cURL to know the verb, we set it explicitly here
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $action);
        
        if($this->nossl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        
        // Decode the JSON response into an object, then store the HTTP response code as well
        $res = (object)json_decode(curl_exec($ch));
        $res->http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return $res;
    }
}
?>
