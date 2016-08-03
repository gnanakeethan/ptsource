<?php

Namespace Model;

class GitServerAllOS extends Base {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    public function backendData2() {
        $gsf = new \Model\GitServer();
        $gs = $gsf->getModel($this->params, "ServerFunctions") ;
        $gs->serveGit() ;
    }

    public function backendData() {
//        error_log("one" ) ;

        define("DEBUG_LOG",        true);
        define("HTTP_AUTH",        false);
        define("GZIP_SUPPORT",     false);
        define("GIT_ROOT",         REPODIR.DS );
        define("GIT_HTTP_BACKEND", "/usr/lib/git-core/git-http-backend");
        define("GIT_BIN",          "/usr/bin/git");
        define("REMOTE_USER",      "smart-http");
        define("LOG_RESPONSE",     "response.log");
        define("LOG_PROCESS",      "process.log");

//        error_log("three" ) ;

        if(isset($_SERVER["PATH_INFO"]))
        {
            list($git_project_path, $path_info) = $temp = preg_split("/\//", $_SERVER["PATH_INFO"], 2, PREG_SPLIT_NO_EMPTY);
            $git_project_path = "/" . $git_project_path . "/";
            $path_info = "/" . $path_info;
        }
        else
        {
            $git_project_path = "/";
            $path_info = "";
        }

        $path_info = "/".$this->params["item"] ;
        $request_headers = $this->getAllHeaders();

        $php_input = file_get_contents("php://input");
        $env = array
        (
            "GIT_PROJECT_ROOT"    => REPODIR ,
            "GIT_HTTP_EXPORT_ALL" => "1",
            "GIT_HTTP_MAX_REQUEST_BUFFER" => "1000M",
//            'PATH' => $_SERVER["PATH"],
//            "REMOTE_USER"         => isset($_SERVER["REMOTE_USER"])          ? $_SERVER["REMOTE_USER"]          : REMOTE_USER,
            "REMOTE_USER"         => isset($_SERVER["REMOTE_USER"])          ? $_SERVER["REMOTE_USER"]          : "ptsource",
//            "REMOTE_ADDR"         => isset($_SERVER["REMOTE_ADDR"])          ? $_SERVER["REMOTE_ADDR"]          : "",
            "REMOTE_ADDR"         => isset($_SERVER["REMOTE_ADDR"])          ? $_SERVER["REMOTE_ADDR"]          : "",
            "REQUEST_METHOD"      => isset($_SERVER["REQUEST_METHOD"])       ? $_SERVER["REQUEST_METHOD"]       : "",
            "PATH_INFO"           => $path_info,
            "QUERY_STRING"        => isset($_SERVER["QUERY_STRING"])         ? $_SERVER["QUERY_STRING"]         : "",
            "CONTENT_TYPE"        => isset($request_headers["Content-Type"]) ? $request_headers["Content-Type"] : "",
        );
        $env = array_merge( $env, array(
//            "GIT_COMMITTER_NAME" => "Pharaoh King",
//            "GIT_COMMITTER_EMAIL" => "phpengine@pharaohtools.com",
        )) ;



//        $qs = str_replace( "control=GitServer&action=clone&item=ptbuild&identifier=", "", $qs) ;
        $qs = $qsx = $_SERVER["QUERY_STRING"] ;
        $qs = str_replace( "control=GitServer&", "", $qs) ;
        $qs = str_replace( "action=pull&", "", $qs) ;
        $qs = str_replace( "authTitle=phpengine&", "", $qs) ;
        $qs = str_replace( "item=", "", $qs) ;

        $parsed = parse_str($qs) ;
        $env["QUERY_STRING"] = $this->params["item"]."/" ;

        ob_start();
        var_dump($qsx, $env) ;
        $res = ob_get_clean();
       error_log($res) ;


        $settings = array
        (
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
        );
        if(defined(DEBUG_LOG))
        {
            $settings[2] = array("file", LOG_PROCESS, "a");
        }
        $process = proc_open(GIT_HTTP_BACKEND , $settings, $pipes, null, $env);
        if(is_resource($process))
        {
            error_log("php in: $php_input" ) ;
            fwrite($pipes[0], $php_input);
            fclose($pipes[0]);

//            $offset = 0 ;
//            while ( $buf = stream_get_contents($pipes[1], 4096, $offset) ) {
//                if (isset($buf) && $buf !== false) {
//                    $data .= $buf;
//                    echo $buf ;
//                    $offset += 4096 ; }
//                else {
//                    break ;
//                }
////                if ( (isset($buf2) && $buf2 !== false) || $buf2 = fread($pipes[2], 32768) ) {
////                    $buf2 = "ERR: ".$buf2;
////                    $data .= "ERR: ".$buf2;
////                    echo "ERR: ".$buf2 ;
////                    unset($buf2) ;}
//            }

//            $return_output = $data ;
            $return_output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $return_code = proc_close($process);
//            error_log("rc: $return_code, stduot: $return_output" ) ;

        }
        else {
            error_log("is r:", is_resource($process) ) ;
        }
        if(!empty($return_output))
        {
            list($response_headers, $response_body)
                = $response
                = preg_split("/\R\R/", $return_output, 2, PREG_SPLIT_NO_EMPTY);


//            var_dump("<pre>", "r1", $response_headers, "r2", $response_body, "r3", $response, "r4", $return_output, "</pre>") ;

            foreach(preg_split("/\R/", $response_headers) as $response_header)
            {
                header($response_header);
            }
            if(isset($request_headers["Accept-Encoding"]) && strpos($request_headers["Accept-Encoding"], "gzip") !== false && GZIP_SUPPORT)
            {
                $gzipoutput = gzencode($response_body, 6);
                ini_set("zlib.output_compression", "Off");
                header("Content-Encoding: gzip");
                header("Content-Length: " . strlen($gzipoutput));
                echo $gzipoutput;
            }
            else
            {
                echo $response_body;
            }
        }

        if(DEBUG_LOG)
        {
            file_put_contents(LOG_RESPONSE, "");
            $log = "";
            //$log .= "\$_GET = " . print_r($_GET, true);
            //$log .= "\$_POST = " . print_r($_POST, true);
            //$log .= "\$_SERVER = " . print_r($_SERVER, true);
            $log .= "\$request_headers = " . print_r($request_headers, true);
            $log .= "\$env = " . print_r($env, true);
            $log .= "\$php_input = " . PHP_EOL . $php_input . PHP_EOL;
            //$log .= "\$return_output = " . PHP_EOL . $return_output . PHP_EOL;
            $log .= "\$response = " . print_r($response, true);
            $log .= str_repeat("-", 80) . PHP_EOL;
            $log .= PHP_EOL;
            if(isset($_GET["service"]) && $_GET["service"] == "git-receive-pack") file_put_contents(LOG_RESPONSE, "");
            file_put_contents(LOG_RESPONSE, $log, FILE_APPEND);
            file_put_contents(LOG_RESPONSE, "service is: ".$_GET["service"], FILE_APPEND);
        }

    }

    protected function getAllHeaders() {
        if (!is_array($_SERVER)) { return array(); }
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            } }
        return $headers;
    }

}