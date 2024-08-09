<?php

namespace OCA\sharepoint\lib;

class SPNotifier {
    const NOTIFY_PASSWORD_REMOVAL = 'password_removal';

    private static $singleton = null;
    private $listeners = array();

    public static function getSingleton(){
        if (self::$singleton === null) {
            self::$singleton = new SPNotifier();
        }
        return self::$singleton;
    }
    
    public function registerSP(SHAREPOINT $sp) {
        $this->listeners[] = $sp;
    }

    /**
     * @param $sp the SHAREPOINT object that changed
     */
    public function notifyChange(SHAREPOINT $sp, $changeType) {
        foreach ($this->listeners as $listener) {
            $listener->receiveNotificationFrom($sp, $changeType);
        }
    }
}