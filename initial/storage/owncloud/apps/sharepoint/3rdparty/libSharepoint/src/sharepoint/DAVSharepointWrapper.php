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

namespace sharepoint;

date_default_timezone_set('UTC');
/**
 * RestSharepointWrapper
 *
 * PHP LIB for manage sharepoint document list items.
 *
 * @author jmaciasportela
 * @version 0.1.0
 *
 * Tested against the sharepoint 2010 SOAP WS
 * Usage:
 * $sp = new SharePointWrapper('<username>','<password>','<path_to_WSDL>');
 *
 */

class DAVSharepointWrapper {
    /**
     * Username for SP auth
     */
    private $spUsername = '';

    /**
     * Password for SP auth
     */
    private $spPassword = '';

    /**
     * Password for SP site
     */
    private $spSite = '';

    /** AuthType */
    public $auth = NULL;

    /**
     * Constructor
     *
     * @param string $spUsername User account to authenticate with
     * @param string $spPassword Password to use with authenticating account
     *
     * @throws Exception
     */
    public function __construct ($spUsername, $spPassword, $spSite) {
        // Set data from parameters in this class
        $this->spUsername = $spUsername;
        $this->spPassword = $spPassword;
        $this->spSite = $spSite;
    }


    /**
     * uploadFile
     * Used to upload file
     *
     *
     * @throws Exception
     *
     * @param string $path
     * @return array
     */
    public function uploadFile ($src, $listName, $path) {
        $this->log('uploadFile src: '.$src.' path:'.$path, \OCP\Util::DEBUG);
        //webdav path needs to replace spaces by %20
        $url = $this->spSite.'/'.rawurlencode($listName.'/'.$path);

        $h = array(
        'User-Agent: OC-PHP-DAV',
        'Expect:'
        );

        try{
            if(is_null($this->auth)){
                $this->auth = $this->get_auth_available(array('ntlm', 'basic'));
                $this->log("Upload file - Authentication Method: ".$this->auth, \OCP\Util::DEBUG);
            }
        } catch (\Exception $fault) {
            $this->log('uploadFile EXCEPTION: The authentication method requested by the server is not supported', \OCP\Util::DEBUG);
            throw new \Exception('The authentication method requested by the server is not supported', 412);
        }

        $fp = fopen($src, 'r');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $this->spUsername.':'.$this->spPassword);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curl, CURLOPT_INFILE, $fp); // file pointer
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($src));
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        // CURLOPT_HTTPAUTH: The bitwise | (or) operator can be used to combine more than one method.
        // If this is done, cURL will poll the server to see what methods it supports and pick the best one.
        // Work arround to fix problem bit some versions of curl library
        $auth = ($this->auth === 'NTLM') ?  CURLAUTH_NTLM : CURLAUTH_BASIC;
        curl_setopt($curl, CURLOPT_HTTPAUTH, $auth);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec ($curl);

        if (curl_errno($curl) !== 0) {
               $err = curl_error($curl);
               $err_no = curl_errno($curl);
               curl_close($curl);
               throw new \Exception($err, $err_no);
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        if($status_code === 401){
            $this->log('uploadFile EXCEPTION: You are not authorized to perform this action - '.print_r($response, true), \OCP\Util::DEBUG);
            throw new \Exception("Uploadfile - You are not authorized to perform this action", 401);
        }
        return array('code' => '200');
    }

    /**
     * downloadFile
     * Used to download file
     *
     * @param string $path
     * @param string $listName
     *
     *
     * @return resource resource
     */
    public function downloadFile ($listName, $path) {
        $this->log('downloadFile listName: '.$listName.' path:'.$path, \OCP\Util::DEBUG);

        $h = array(
        'User-Agent: OC-PHP-DAV'
        );

        $url = $this->spSite.'/'.rawurlencode($listName.'/'.$path);

        try{
            if(is_null($this->auth)){
                $this->auth = $this->get_auth_available(array('ntlm', 'basic'));
            }
        } catch (\Exception $fault) {
            $this->log('uploadFile EXCEPTION: The authentication method requested by the server is not supported', \OCP\Util::DEBUG);
            throw new \Exception('The authentication method requested by the server is not supported', 412);
        }

        $curl = curl_init();
        $fp = fopen('php://temp', 'r+');
        curl_setopt($curl, CURLOPT_USERPWD, $this->spUsername.':'.$this->spPassword);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        // CURLOPT_HTTPAUTH: The bitwise | (or) operator can be used to combine more than one method.
        // If this is done, cURL will poll the server to see what methods it supports and pick the best one.
        // Work arround to fix problem bit some versions of curl library
        $auth = ($this->auth === 'NTLM') ?  CURLAUTH_NTLM : CURLAUTH_BASIC;
        curl_setopt($curl, CURLOPT_HTTPAUTH, $auth);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_exec ($curl);

        if (curl_errno($curl) !== 0) {
               $err = curl_error($curl);
               $err_no = curl_errno($curl);
               curl_close($curl);
               throw new \Exception($err, $err_no);
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($status_code === 401) {
            curl_close ($curl);
            $this->log('downloadFile EXCEPTION: You are not authorized to perform this action', \OCP\Util::DEBUG);
            throw new \Exception("downloadFile - You are not authorized to perform this action ", 401);
        }
        curl_close ($curl);
        rewind($fp);
        return $fp;
    }

    /**
     * move folder
     * Used to move folders
     *
     * @param string $listName
     * @param string $path1
     * @param string $path2
     *
     *
     * @return file resource
     */
    public function moveFolder ($listName, $path1, $path2) {
        $this->log('downloadFile listName: '.$listName.' path1:'.$path1.' path2:'.$path2, \OCP\Util::DEBUG);
        $url = parse_url($this->spSite);
        if(!isset($url['path'])){
            $url['path'] = '';
        }
        $h = array(
        'User-Agent: OC-PHP-DAV',
        'Destination: '.$url['path'].'/'.rawurlencode($listName.'/'.$path2)
        );

        $url = $this->spSite.'/'.rawurlencode($listName.'/'.$path1);

        try{
            if(is_null($this->auth)){
                $this->auth = $this->get_auth_available(array('ntlm', 'basic'));
            }
        } catch (\Exception $fault) {
            $this->log('uploadFile EXCEPTION: The authentication method requested by the server is not supported', \OCP\Util::DEBUG);
            throw new \Exception('The authentication method requested by the server is not supported', 412);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $this->spUsername.':'.$this->spPassword);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'MOVE');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        // CURLOPT_HTTPAUTH: The bitwise | (or) operator can be used to combine more than one method.
        // If this is done, cURL will poll the server to see what methods it supports and pick the best one.
        // Work arround to fix problem bit some versions of curl library
        $auth = ($this->auth === 'NTLM') ?  CURLAUTH_NTLM : CURLAUTH_BASIC;
        curl_setopt($curl, CURLOPT_HTTPAUTH, $auth);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_exec($curl);

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl) !== 0) {
               $err = curl_error($curl);
               $err_no = curl_errno($curl);
               curl_close($curl);
               throw new \Exception($err, $err_no);
        }

        if ($status_code === 401) {
            curl_close ($curl);
            $this->log('MoveFolder EXCEPTION: You are not authorized to perform this action', \OCP\Util::DEBUG);
            throw new \Exception("MoveFolder - You are not authorized to perform this action", 401);
        }

        curl_close ($curl);
        return array('code' => '200');
    }

    /**
     * get_auth_available
     * Check if one of the authentication methods is available on a URL
     *
     * @param string[] $methods
     *
     * @return string result
     */
    public function get_auth_available ($methods){
        $headers = get_headers($this->spSite, 1);
        $methods = array_map('strtoupper', $methods);

        foreach ($headers as $key => $value) {
            //Header www-authenticate can be multiple
            if(strtolower($key) === 'www-authenticate'){
                if(is_array($headers[$key])){
                    $m = array_map('strtok', array_map('strtoupper', $headers[$key]), array_fill(0, count($headers[$key]),' '));
                    foreach ($methods as $method) {
                        if (in_array($method, $m)){
                            return $method;
                        }
                    }
                }else{
                    $m = strtoupper(strtok($value, ' '));
                    if(in_array($m, $methods)){
                        return $m;
                    }
                }
            }
        }
        $this->log('uploadFile EXCEPTION: The authentication method requested by the server is not supported', \OCP\Util::DEBUG);
        throw new \Exception ('Precondition failed: Authentication scheme not supported', 412);
    }

    /**
     * @param string $message
     */
    private function log($message, $level, $from='sharepoint-dav') {
        if(\OC::$server->getConfig()->getSystemValue('sharepoint.logging.enable', false) === true){
            \OCP\Util::writeLog($from, $message, $level);
        }
    }
}
