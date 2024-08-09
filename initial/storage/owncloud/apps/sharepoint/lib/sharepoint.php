<?php
/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */


namespace OCA\sharepoint\lib;

/**
 * SHAREPOINT
 *
 * PHP Class to manage Sharepoint instance as virtual file system
 *
 * @author Jesus Macias Portela
 * @version 0.1.0
 *
 */

use OCP\Files\Cache\IWatcher;
use OCP\Files\StorageNotAvailableException;
use \OCA\sharepoint\lib\Utils;
use \OCA\sharepoint\lib\SPNotifier;


require_once  'sharepoint/3rdparty/libSharepoint/src/sharepoint/SoapSharepointWrapper.php';
require_once  'sharepoint/3rdparty/libSharepoint/src/sharepoint/DAVSharepointWrapper.php';

class SHAREPOINT extends \OC\Files\Storage\Common {

    private $sharepointClient = NULL;
    private $sharepointDAVClient = NULL;
    private $root = '/';
    private $id;
    private $fileTree = array();
    private $treeTimestamp = NULL;
    private $spSite = NULL;
    private $user = NULL;
    private $password = NULL;
    private $mountPoint = NULL;
    private $listName = NULL;
    private $listInternalName = NULL;
    private $listInfo = NULL;
    private $tmpFileGroup = array();
    private $rootCreatablePermission = null;
    private $isSharingActive = false;
    private $spnotifier;
    private $isValid = false;

    public function __construct($parameters) {
        $parsed = parse_url($parameters['apiurl']);

        if (isset($parameters['oc_sharing']) &&  $parameters['oc_sharing'] != false) {
            $this->isSharingActive = true;
        }

        $this->spSite = (isset($parsed['path'])) ? $parsed['scheme'].'://'.$parsed['host'].'/'.rawurlencode(ltrim($parsed['path'], '/')) : $parameters['apiurl'];
        $this->user = $parameters['user'];
        $this->password = $parameters['password'];
        $this->mountPoint = $parameters['mountPoint'];
        $this->listName = $parameters['listName'];

        $this->authType = $parameters['authType'];
        $this->mountType = $parameters['mountType'];

        $this->sharepointClient = new \sharepoint\SoapSharepointWrapper($parameters['user'], $parameters['password'],
                                                                        $this->spSite, '2010');
        $this->sharepointDAVClient = new \sharepoint\DAVSharepointWrapper($parameters['user'], $parameters['password'],
                                                                        $this->spSite);
       
        /* Register in the singleton notifier */
        $this->spnotifier = SPNotifier::getSingleton();
        $this->spnotifier->registerSP($this);  

        if(!isset($parameters['SPInternalListName'])){
            $this->getSPInternalListName();
        }
        $this->getWatcher()->setPolicy(IWatcher::CHECK_ONCE);

        $this->isValid = true;
    }

    /**
     * Some URL to access to document libraries are different from their titles. So we need to get the internal name
     * that we can use on SOAP and WebDAV request.
     *
     * @return string the FS id
     */
    
    private function getSPInternalListName(){

        try{            
            $this->listInfo = $this->sharepointClient->GetList($this->listName);
            $path = explode("/", trim($this->listInfo['rootFolder'], "/"));
            $this->listInternalName = array_pop($path);
            //This flags only inform if createFolder command is available on the document list but is not a permission for each user.
            //This flag can be true but a user could have only read permission
            $this->rootCreatablePermission = $this->listInfo['createPermission'] === 'True'? true: false;

        } catch (\Exception $e){
            if($e->getCode() === 401){
                $this->invalidateMountPoint();               
            }
            // Sometimes credentials has expired so we get an exception accessing to the lis info. I this case there is
            // a method checkConnection that launch the exception
            $this->log('construct - Get ListInfo EXCEPTION - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
        }
        return $this->listInternalName;
    }

    /**
     * Get the FS id (for ownCloud purposes)
     *
     * @return string the FS id
     */
    public function invalidateMountPoint(){
        if ($this->isValid) {
            $this->log('invalidateMountPoint - ' . $this->mountPoint . ' - ' . $this->mountType . ' - ' . $this->authType, \OCP\Util::DEBUG);
            $this->spnotifier->notifyChange($this, SPNotifier::NOTIFY_PASSWORD_REMOVAL);
        } 
    }

    /**
     * Receive messages from other mount points
     *
     */
    public function receiveNotificationFrom(SHAREPOINT $other, $changeType) {
        if ($changeType === SPNotifier::NOTIFY_PASSWORD_REMOVAL &&
                $this->getUserHostId() === $other->getUserHostId()) {
            
            //Invalidate config on DB
            Utils::InvalidateConfig(array("mountPoint" => $this->mountPoint,
                                          "mountType" => $this->mountType,
                                          "authType" => $this->authType));

            //Invalidate Object config
            $this->sharepointClient = new \sharepoint\SoapSharepointWrapper('', '' , $this->spSite,'2010');
            $this->sharepointDAVClient = new \sharepoint\DAVSharepointWrapper('', '' , $this->spSite);
            $this->user = $this->password = '';
            $this->isValid = false;
        }
    }

    /**
     * Get the user-host id
     *
     * @return string the user-host id
     */
    public function getUserHostId(){
        return $this->user . '@' . $this->spSite;
    }

    /**
     * Get the FS id (for ownCloud purposes)
     *
     * @return string the FS id
     */
    public function getId(){
        return 'sharepoint::' . $this->user . '@' . $this->spSite. '/' . $this->listInternalName . '::' . $this->mountPoint;
    }

    /**
     * Create a directory. Notice that all the parent directories should exists
     *
     * @param string $path the directory we want to create
     * @return bool true if the directory is created, false otherwise
     */
    public function mkdir($path) {
        $this->log('mkdir: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        try{
            $response = $this->sharepointClient->createFolder($this->listInternalName.'/'.$path);
        } catch (\Exception $e){
            if($e->getCode() === 401){
                $this->invalidateMountPoint();               
            }
            $this->log('EXCEPTION - mkdir - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
            return false;
        }
        if (isset($response['code']) && $response['code'] == '200'){
                $content = $this->getContent(dirname('/'.$path));
                $this->fileTree['/'.$path] = $content['/'.$path];
                return true;
            }
        return false;
    }

    /**
     * Remove the directory and its contents
     *
     * @param string $path the FS path that we want to delete
     * @return bool true if deleted, false otherwise
     */
    public function rmdir($path) {
        $this->log('rmdir: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        try{
            $response = $this->sharepointClient->deleteFolder($this->listInternalName.'/'.$path);
        } catch (\Exception $e){
            if($e->getCode() === 401){
                $this->invalidateMountPoint();               
            }
            $this->log('EXCEPTION - rmdir - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
            return false;
        }
        if (isset($response['code']) && $response['code'] == '200') {
                $this->treeBuilder($path, true);
                return true;
            }
        return false;
    }

    /*
     * Open the directory
     *
     * @param string $path FS path
     * @return resource a directory resource
     */
    public function opendir($path) {
        $this->log('opendir: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        $content = $this->getDirContent($path);
        \OC\Files\Stream\Dir::register('sp:' . $this->mountPoint.$path, $content);
        return opendir('fakedir://sp:'.$this->mountPoint.$path);
    }

    /**
     * Check the path has updated in the given time. The check is usually based on the mtime
     * of the path, so if the path's filemtime is greater than $time, the file has updated
     * Under special condition (mainly the reliability of the mtime) we also check the contents
     * of the path to decide whether the path has changed or not
     *
     * @param string $path the FS path
     * @param int $time timestamp to compare against
     * @return bool true if the file has updated, false otherwise
     */
    public function hasUpdated($path, $time) {
        $this->log('hasUpdated: '.$path.' time: '.$time, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        if ($this->treeBuilder($path) === false) {
            throw new StorageNotAvailableException();
        }
        $stat = $this->stat($path);
        if ($stat !== false) {
            return $stat['mtime'] > $time;
        }
        return false;
    }

    /**
     * perform the stat call over the file
     *
     * @param string $path the FS path
     * @param array|false an array with information or false in case of error
     */
    public function stat($path) {
        $this->log('stat: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');

        $this->treeBuilder($path);

        if (!isset($this->fileTree['/'.$path])) {
            $this->log('stat($path) no data returning false ', \OCP\Util::DEBUG);
            return false;
        }
        if ($this->filetype($path) === 'dir') {
            $contentMtime = 0;
            foreach ($this->fileTree as $key => $value) {
                if (substr($key, 0, strlen($path) + 1) === "/$path") {
                    if ($value['EPOXModified'] > $contentMtime) {
                        $contentMtime = $value['EPOXModified'];
                    }
                }
            }
            if ($this->isRootDir($path)) {
                $size = 0;
            } else {
                $size = ($this->fileTree['/'.$path]['size'] != false ? (int)$this->fileTree['/'.$path]['size'] : 0);
            }
            $stat = array('size' => $size,
                          'mtime' => $contentMtime,
                          'atime' => $contentMtime,
                          'ctime' => $contentMtime);
        } else {

            $stat=array('size'  => ($this->fileTree['/'.$path]['size'] != false ? (int)$this->fileTree['/'.$path]['size'] : 0),
                    'atime' => $this->fileTree['/'.$path]['EPOXModified'],
                    'mtime' => $this->fileTree['/'.$path]['EPOXModified'],
                    'ctime' => $this->fileTree['/'.$path]['EPOXModified']);
        }
        return $stat;
    }

    /**
     * Get the file type of the path
     *
     * @param string $path FS path
     * @return string the type of the path (usually 'dir' or 'file')
     */

    public function filetype($path) {
        $this->log('filetype: '.$path, \OCP\Util::DEBUG);
        $path = trim ($path, '/');
        if ($this->isRootDir($path)) {
            return 'dir';
        }
        $this->treeBuilder($path);
        if (isset($this->fileTree['/'.$path])) {
            if ($this->fileTree['/'.$path]['type'] == 1){
                $this->log('filetype: '.$path.' is dir', \OCP\Util::DEBUG);
                return 'dir';
            }
            else {
                $this->log('filetype: '.$path.' is file', \OCP\Util::DEBUG);
                return 'file';
            }
        }
        return false;
    }

    /**
     * Check if the path is readable
     *
     * @param string $path the path
     * @return bool true if it's readable, false if not
     */
    public function isReadable($path) {
        $this->log('isReadable: '.$path, \OCP\Util::DEBUG);
        return $this->hasPermission('isReadable', $path);
    }

    /**
     * Check if the path is updatable
     *
     * @param string $path the path
     * @return bool true if it's updatable, false if not
     */
    public function isUpdatable($path) {
        $this->log('isUpdatable: '.$path, \OCP\Util::DEBUG);
        return $this->hasPermission('isUpdatable', $path);
    }

    /**
     * Check if the path is creatable
     *
     * @param string $path the path
     * @return bool true if it's creatable, false if not
     */
    public function isCreatable($path) {
        $this->log('isCreatable: '.$path, \OCP\Util::DEBUG);
        return $this->hasPermission('isCreatable', $path);
    }

    /**
     * Check if the path is deletable
     *
     * @param string $path the path
     * @return bool true if it's deletable, false if not
     */
    public function isDeletable($path) {
        $this->log('isDeletable: '.$path, \OCP\Util::DEBUG);
        return $this->hasPermission('isDeletable', $path);
    }

    /**
     * Check if the path is deletable
     *
     * @param string $path the path
     * @return bool true if it's deletable, false if not
     */
    public function hasPermission($permission, $path) {
        $this->log($permission.': '.$path, \OCP\Util::DEBUG);
        if (($permission === 'isUpdatable' || $permission === 'isCreatable') && $this->isRootDir($path) && $this->rootCreatablePermission) {
            return true;
        } else if ($permission === 'isReadable' && $this->isRootDir($path)){
            return true;
        } else if ($permission === 'isDeletable' && $this->isRootDir($path)){
            return false;
        }
        $this->treeBuilder($path);
        if(isset($this->fileTree['/'.$path]) && $this->checkPermissions($this->fileTree['/'.$path]['permMask'], $permission)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the path is sharable
     *
     * @param string $path the path
     * @return bool true if it's sharable, false if not
     */
    public function isSharable($path) {
        $value = $this->isSharingActive;
        $this->log('isSharable: '.$path.' '.$value, \OCP\Util::DEBUG);
        return $value;
    }

    /**
     * Check the the file defined by path exists
     *
     * @param string $path FS path
     * @return bool true if the file exists, false otherwise
     */
    public function file_exists($path) {
        $path = trim($path, '/');
        if ($this->isRootDir($path)) {
            return true;
        }

        $this->treeBuilder($path);
        if(isset($this->fileTree['/'.$path])) {
                $this->log('file_exist: '.$path. ' TRUE', \OCP\Util::DEBUG);
                return true;
            }
        $this->log('file_exist: '.$path. ' FALSE', \OCP\Util::DEBUG);
        return false;
    }

    /**
     * Remove the path. If the path is a directory, this function falls back to the rmdir
     * function
     *
     * @param string $path FS path to be deleted
     * @return bool true if the path no longer exists, false otherwise
     */
    public function unlink($path) {
        $this->log('unlink: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        $this->treeBuilder($path);
        if(isset($this->fileTree['/'.$path])) {
            try{
                $response = $this->sharepointClient->deleteFile($this->listName, $this->fileTree['/'.$path]['id'], $this->listInternalName.'/'.$path);
            } catch (\Exception $e){
                if($e->getCode() === 401){
                    $this->invalidateMountPoint();               
                }
                $this->log('EXCEPTION - unlink - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                return false;
            }
            if (isset($response['code']) && $response['code'] == '200') {
                $this->treeBuilder($path, true);
                return true;
            }
        }
        return false;
    }

    /**
     * Rename the files
     *
     * @param string $path1 the old name of the path
     * @param string $path2 the new name of the path
     * @return bool true if the rename is successful, false otherwise
     */
    public function rename($path1, $path2) {
        $this->log('rename: '.$path1.'-'.$path2, \OCP\Util::DEBUG);

        if ($this->filetype($path1) == 'dir' && strpos(trim($path2, "/"), '/') === FALSE) {
            try{
                $this->log('rename case 1 - listName: '.$this->listName.' path2:'.basename($path2), \OCP\Util::DEBUG);
                $response = $this->sharepointClient->renameFolder($this->listName, $this->fileTree['/'.$path1]['id'], basename($path2));
            } catch (\Exception $e){
                if($e->getCode() === 401){
                    $this->invalidateMountPoint();               
                }
                $this->log('EXCEPTION - rename - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                return false;
            }
        }
        else if ($this->filetype($path1) == 'dir' && strpos(trim($path2, "/"), '/') !== FALSE) {
            $this->log('rename case 2 - moving folder '.$path1.' to '.$path2, \OCP\Util::DEBUG);
            try{
                $response = $this->sharepointDAVClient->moveFolder($this->listInternalName, $path1, $path2);
            } catch (\Exception $e){
                if($e->getCode() === 401){
                    $this->invalidateMountPoint();               
                }
                $this->log('EXCEPTION - rename - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                return false;
            }
        }
        else {
            try{
                $this->log('rename case 3 - moving file: '.$this->listName.' path1: '.$this->spSite.'/'.$this->listInternalName.'/'.$path1.' path2: '.$this->spSite.'/'.$this->listInternalName.'/'.$path2, \OCP\Util::DEBUG);
                $response = $this->sharepointClient->copyFile($this->spSite.'/'.$this->listInternalName.'/'.$path1, $this->spSite.'/'.$this->listInternalName.'/'.$path2);
            } catch (\Exception $e){
                if($e->getCode() === 401){
                    $this->invalidateMountPoint();               
                }
                $this->log('EXCEPTION - rename - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                return false;
            }
        }
        if (isset($response['code']) && $response['code'] == '200') {
            if($this->unlink($path1)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Open a file resouce
     *
     * @param string $path file path to be opened
     * @param string $mode open the file in this mode
     * @return resource an opened resource to read or write
     */
    public function fopen($path, $mode) {
        $this->log('fopen: '.$path.' - '.$mode, \OCP\Util::DEBUG);
        switch ($mode) {
            case 'r':
            case 'rb':
                if (!$this->file_exists($path)) {
                    $this->log('fopen($path, $mode) unexisting file', \OCP\Util::ERROR);
                    return false;
                }
                try{
                    $fp = $this->sharepointDAVClient->downloadFile($this->listInternalName, $path);
                } catch (\Exception $e){
                    if($e->getCode() === 401){
                        $this->invalidateMountPoint();               
                    }
                    $this->log('EXCEPTION - fopen - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                    return false;
                }
                if(!$fp){
                    return false;
                }
                $tmpFile = \OC::$server->getTempManager()->getTemporaryFile();
                $handle = fopen($tmpFile, 'w+');
                fwrite($handle, stream_get_contents($fp));
                fclose($handle);
                return fopen($tmpFile, 'rb');
            case 'w':
            case 'wb':
            case 'a':
            case 'ab':
            case 'r+':
            case 'w+':
            case 'wb+':
            case 'a+':
            case 'x':
            case 'x+':
            case 'c':
            case 'c+':
                $folder = \OCP\Files::tmpFolder();
                $tmpFile = $folder . '/' . basename($path);
                $this->tmpFileGroup[$tmpFile] = $path;
                \OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
                return fopen('close://'.$tmpFile, $mode);
        }
        return false;
    }

    /**
     * Writes the file.
     *
     * NOTICE: This function is for a callback in the fopen function, so it's not intended to use
     * outside
     */
    public function writeBack($tmpFile) {
        $this->log('writeBack: '.$tmpFile, \OCP\Util::DEBUG);
        try{
            $response = $this->sharepointDAVClient->uploadFile($tmpFile, $this->listInternalName, $this->tmpFileGroup[$tmpFile]);
        } catch (\Exception $e){
            if($e->getCode() === 401){
                $this->invalidateMountPoint();               
            }
            $this->log('EXCEPTION - writeBack - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
            throw new \OCP\Files\NotPermittedException($e->getMessage(), $e->getCode(), $e);
        }
        if (isset($response['code']) && $response['code'] == '200') {
            $this->sharepointClient->checkInFile($this->listInternalName, $this->tmpFileGroup[$tmpFile]);
            $content = $this->getContent(dirname($this->tmpFileGroup[$tmpFile]));
            if (isset($content['/'.$this->tmpFileGroup[$tmpFile]])){
                $this->fileTree['/'.$this->tmpFileGroup[$tmpFile]] = $content['/'.$this->tmpFileGroup[$tmpFile]];
                return true;
            }
        }
        return false;
    }

    /**
     * "Touch" the path. This function will only create a empty file if not exists or update
     * the mtime to the latest if it exists. Setting the mtime is not supported
     *
     * @param string $path path to be "touched"
     * @param mixed $mtime null to be updated to the latest, if it's not null, it will be ignored
     * @return bool true if file exists and mtime updated
     */
    public function touch($path, $mtime=null) {
        $this->log('touch: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        if (!$this->file_exists('/'.$path)) {
            $this->log('touch: file not exists '.$path, \OCP\Util::DEBUG);
            $tmpFile = \OC::$server->getTempManager()->getTemporaryFile();
            try{
                $response = $this->sharepointDAVClient->uploadFile($tmpFile, $this->listInternalName, $path);
            } catch (\Exception $e){
                if($e->getCode() === 401){
                    $this->invalidateMountPoint();               
                }
                $this->log('EXCEPTION - touch - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                return false;
            }
            if (isset($response['code']) && $response['code'] == '200') {
                $this->sharepointClient->checkInFile($this->listInternalName, $path);
                $content = $this->getContent(dirname('/'.$path));
                if (isset($content['/'.$path])){
                    $this->fileTree['/'.$path] = $content['/'.$path];
                    return true;
                }
            }
        }
        return false;
    }


    public function checkConnection() {
        //Credentials are the same for soap and webdav, so only need to check one.
        try {
            $this->sharepointClient->getListCollection();
        } catch (\Exception $e) {
            if($e->getCode() === 401){
                $this->invalidateMountPoint();               
            }
            $this->log('EXCEPTION - checkConnection - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        $this->log('checking Connection for list '.$this->listName.': OK', \OCP\Util::DEBUG);
        return true;
    }

    public function getMountPoint() {
        return $this->mountPoint;
    }

    /**
     * @param string $message
     */
    private function log($message, $level, $from='sharepoint') {
        if(\OC::$server->getConfig()->getSystemValue('sharepoint.logging.enable', false) === true){
            \OCP\Util::writeLog($from, $message, $level);
        }
    }

    /**
     * @param string $path
     */
    private function isRootDir($path) {
        return ($path == '' || $path == '/' || $path == '.' || $path == '\.');
    }

    private function treeBuilder($path, $delete=FALSE) {
        $this->log('treeBuilder path: '.$path.' delete: '.$delete, \OCP\Util::DEBUG);
        if(substr($path,0,1) !== '/'){
            $path = '/'.$path;
        }
        $path = rtrim($path, '/');
        if(!isset($this->fileTree[$path]) && !$delete){
            try{
                $p = dirname(ltrim($path, '/'));
                $this->log('treeBuilder GetListsItems: '.$this->listName.' path: '.$p, \OCP\Util::DEBUG);
                $r1 = $this->sharepointClient->GetListsItems($this->listName, $this->listInternalName, false, $p, TRUE);
                $response = $r1;
                if(!$this->isRootDir($path) && isset($response[$path]) && $response[$path]['type'] == 1){
                    $this->log('treeBuilder GetListsItems: '.$this->listName.' path: '.$path, \OCP\Util::DEBUG);
                    $r2 = $this->sharepointClient->GetListsItems($this->listName, $this->listInternalName, false, ltrim($path, '/'), TRUE);
                    $response = array_merge($r1, $r2);
                }
                //Get info from root /
                if($this->isRootDir($path)){
                    try{
                        $this->log('treeBuilder Root', \OCP\Util::DEBUG);
                        $lists = $this->sharepointClient->GetListCollection();
                    }catch (\Exception $e){
                        if($e->getCode() === 401){
                            $this->invalidateMountPoint();               
                        }   
                        $this->log('EXCEPTION - treeBuilder GetListCollection - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                        return false;
                    }
                    foreach ($lists as $list) {
                        $this->log('treeBuilder Root title:'.$list['title'].' name'.$this->listName, \OCP\Util::DEBUG);
                        if( $list['title'] === $this->listName){
                            $this->fileTree['/'] = $list;
                            break;
                        }
                    }
                    // No other way
                    if(!isset($this->fileTree['/']['permMask'])){
                        $this->fileTree['/']['permMask'] = '0x0000000000000001';
                    }
                }
            } catch (\Exception $e){
                if($e->getCode() === 401){
                    $this->invalidateMountPoint();               
                }
                $this->log('EXCEPTION - treeBuilder GetListsItems - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
                return false;
            }
            //Update file tree with new data
            foreach ($response as $key => $value) {
                $this->fileTree[$key] = $response[$key];
            }
        } else if ($delete===TRUE){
            if($this->filetype($path) === 'dir'){
                foreach ($this->fileTree as $key => $value) {
                    if(strpos($key, $path) !== false){
                        unset($this->fileTree[$key]);
                    }
                }
            } else if ($this->filetype($path) === 'file'){
                unset($this->fileTree[$path]);
            }
        }
    }


    /**
     * @param string $path
     */
    private function getContent ($path){
        $this->log('getContent path: '.$path, \OCP\Util::DEBUG);
        $path = trim($path, '/');
        try{
            $content = $this->sharepointClient->GetListsItems($this->listName, $this->listInternalName, false, $path, TRUE);
        } catch (\Exception $e){
            if($e->getCode() === 401){
                $this->invalidateMountPoint();               
            }
            $this->log('EXCEPTION - getContent - '.$e->getCode().' - '.$e->getMessage() , \OCP\Util::DEBUG);
            return;
        }
        return $content;
    }

    /**
     * @param string $path
     */
    private function getDirContent ($path){
        if(substr($path,0,1) !== '/'){
            $path = '/'.$path;
        } else{
            $path = rtrim($path, '/');
        }
        $files = array();
        $this->treeBuilder($path);
        foreach ($this->fileTree as $key => $value) {
            if(dirname($key) === $path){
                $subItem = basename($key, '/');
                if($subItem !== '') {
                    $files[]= $subItem;
                }
            }
        }
        return $files;
    }

    /**
     * @param string $mask
     */
    private function checkPermissions($mask, $permission){
    /*
    ViewListItems: 0x0000000000000001 Allow viewing of list items in lists,
    documents in document libraries, and Web discussion comments.
    AddListItems: 0x0000000000000002 Allow addition of list items to lists,
    documents to document libraries, and Web discussion comments.
    EditListItems: 0x0000000000000004 Allow editing of list items in lists,
    documents in document libraries, Web discussion comments,
    and to customize Web part pages in document libraries.
    DeleteListItems: 0x0000000000000008 Allow deletion of list items from lists,
    documents from document libraries, and Web discussion comments.
    */
        //This array is to store the value of each permision
        $permissions = array('isReadable'=> 1, 'isCreatable'=> 2, 'isUpdatable'=> 4, 'isDeletable'=> 8);
        if(isset($permissions[$permission]) && ($permissions[$permission] & hexdec(substr($mask, -1, 1))) > 0){
            return true;
        }
        return false;
    }

}
