<?php

Namespace Controller;

class Signup extends Base {

    public function execute($pageVars) {

        $thisModel = $this->getModelAndCheckDependencies(substr(get_class($this), 11), $pageVars);
        // if we don't have an object, its an array of errors
        $this->content = $pageVars;
        if (is_array($thisModel)) {
            return $this->failDependencies($pageVars, $this->content, $thisModel); }

        // @todo if signup is not enabled this should redirect to the Index module
        // @todo This is functionality. It should be in the Model, not here
        if ($pageVars["route"]["action"] == "login") {
            $this->content["data"] = $thisModel->getlogin(); }

        if ($pageVars["route"]["action"] == "registration") {
            // todo @karthik do we need the line below
            // $this->content["data"] = "registration";
            return array("type" => "view", "view" => "signupRegistration", "pageVars" => $this->content); }

        if ($pageVars["route"]["action"] == "registration-submit") {
            $this->content["data"] = $thisModel->registrationSubmit();
            $this->content["output-format"] = "JSON" ;
            return array("type" => "view", "view" => "signupRegistrationResult", "pageVars" => $this->content); }

        if ($pageVars["route"]["action"] == "login-status") {
            $this->content["data"] =  $thisModel->checkLoginStatus();
            $this->content["output-format"] = "JSON" ;
            return array("type" => "view", "view" => "signupLoginSubmit", "pageVars" => $this->content); }

        if ($pageVars["route"]["action"] == "login-submit") {
            $this->content["data"] = $thisModel->checkLogin();
            $this->content["output-format"] = "JSON" ;
            return array("type" => "view", "view" => "signupLoginSubmit", "pageVars" => $this->content); }

        if ($pageVars["route"]["action"] == "logout") {
            $thisModel->allLoginInfoDestroy(); }
		
		if ($pageVars["route"]["action"] == "verify") {
            $thisModel->mailVerification(); }
		
        return array("type" => "view", "view" => "signupLogin", "pageVars" => $this->content);

    }
}