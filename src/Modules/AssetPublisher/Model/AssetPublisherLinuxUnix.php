<?php

Namespace Model;

class AssetPublisherLinuxUnix extends Base {

    // Compatibility
    public $os = array("Linux", "Darwin") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    public function getEventNames() {
        return array_keys($this->getEvents());
    }

    public function getEvents() {
        $ff = array(
            "publish-assets" => array("publishAssets",),
        );
        return $ff ;
    }

    public function publishAssets() {
        $loggingFactory = new \Model\Logging();
        $this->params["echo-log"] = true ;
        $logging = $loggingFactory->getModel($this->params);
        $modDir = "/opt/".PHARAOH_APP."/".PHARAOH_APP."/src/Modules/" ;
        $mods = scandir($modDir) ;
        $mods = array_diff($mods, array(".", "..")) ;
        $piDir = "/opt/".PHARAOH_APP."/".PHARAOH_APP."/src/Modules/PostInput/" ;
        $statuses = array();
        foreach ($mods as $mod) {
            $modPath = $modDir.$mod ;
            $logging->log("Looking for Asset directory in Module $mod", $this->getModuleName()) ;
            $modAssets = $modPath.DS."Assets" ;
            if (is_dir($modPath.DS."Assets")) {
                if ($mod == "PostInput") {
                    $logging->log("Ignoring Asset directory in Module $mod", $this->getModuleName()) ;
                    continue ; }
                else {
                    $logging->log("Found Asset directory in Module $mod", $this->getModuleName()) ;
                    $modPublicAssetDir = $piDir.'Assets'.DS.'Modules'.DS.$mod ; }
                if (!is_dir($modPublicAssetDir)) {
                    $logging->log("Creating Web Server Assets directory $modPublicAssetDir", $this->getModuleName()) ;
                    $comm = "mkdir -p $modPublicAssetDir" ;
                    $status = $this->executeAndGetReturnCode($comm);
                    if ($status !== 0) {
                        return false; } }
                $logging->log("Copying Assets to Web server readable directory", $this->getModuleName()) ;
                $comm = "cp -r $modAssets".DS."* $modPublicAssetDir" ;
                $statuses[] = $this->executeAndGetReturnCode($comm); } }
        if (in_array(false, $statuses)) { return false; }
        return true ;
    }

}