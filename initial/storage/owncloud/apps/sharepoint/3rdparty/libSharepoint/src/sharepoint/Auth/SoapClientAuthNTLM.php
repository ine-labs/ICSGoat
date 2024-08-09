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


namespace sharepoint\Auth;

/**
 * SoapClientAuth
 * SoapClientAuth for accessing Web Services protected by NTLM authentication
 *
 * @author Jesus Macias Portela
 * @link http://php.net/manual/en/class.soapclient.php
 *
 * @method array GetListCollection()
 * @method array GetList(array $params)
 * @method array GetListItems(array $params)
 * @method array GetListItemChanges(array $params)
 * @method array GetListItemChangesSinceToken(array $params)
 * @method array UpdateListItems(array $params)
 * @method array createFolder(array $params)
 * @method array deleteFolder(array $params)
 * @method array CopyIntoItems(array $params)
 * @method array GetItem(array $params)
 * @method array CopyIntoItemsLocal(array $params)
 *
 */

class SoapClientAuthNTLM extends \SoapClient {

    /** User used for NTLM authentication */
    public $user = NULL;
    /** Password used for NTLM authentication  */
    public $password = NULL;

    /** AuthType */
    public $auth = NULL;

    /**
     *
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($wsdl, array $options) {
        $this->user = (isset($options['login'])) ? $options['login'] : '';
        $this->password = (isset($options['password'])) ? $options['password'] : '';
        parent::SoapClient($wsdl, $options);
    }

    /**
     * @param string $data
     * @param string $url
     * @param string $action
     * @param int $version
     * @param int $one_way
     *
     * @return mixed|string
     * @throws Exception
     */
    public function __doRequest($data, $url, $action, $version, $one_way = 0) {
        try{
            if(is_null($this->auth)){
                $this->auth = $this->get_auth_available($url, array('ntlm', 'basic'));
            }
        } catch (\Exception $fault) {
            throw new \Exception('The authentication method requested by the server is not supported', 412);
        }

        $headers = array(
            'User-Agent: OC-PHP-SOAP',
            'Content-Type: text/xml; charset=utf-8',
            'Expect: ',
            'Connection: Keep-Alive',
            'SOAPAction: '. $action,
            );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        curl_setopt($curl, CURLOPT_FAILONERROR, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        // CURLOPT_HTTPAUTH: The bitwise | (or) operator can be used to combine more than one method.
        // If this is done, cURL will poll the server to see what methods it supports and pick the best one.
        // Work arround to fix problem bit some versions of curl library
        $auth = ($this->auth === 'NTLM') ?  CURLAUTH_NTLM : CURLAUTH_BASIC;
        curl_setopt($curl, CURLOPT_HTTPAUTH, $auth);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($curl);

        if (curl_errno($curl) !== 0) {
               $err = curl_error($curl);
               $err_no = curl_errno($curl);
               curl_close($curl);
               throw new \Exception($err, $err_no);
        }

        $info = curl_getinfo($curl);

        if ($info['http_code'] === 200) {
            $this->log('SOAP 200 response: '.print_r($response, true), \OCP\Util::DEBUG);
            curl_close($curl);
            return $response;
        } else if ($info['http_code'] === 401) {
            $this->log('SOAP 401 response: '.print_r($response, true), \OCP\Util::DEBUG);
            curl_close($curl);
            throw new \Exception ('Access Denied', 401);
        }else {
            $this->log('SOAP XXX response: '.print_r($response, true), \OCP\Util::DEBUG);
            throw new \Exception('Error', $info['http_code']);
        }
    }

    /**
     * get_auth_available
     * Check if one of the authentication methods is available on a URL
     *
     * @param string $url
     * @param string[] $methods
     *
     * @return string result
     */
    public function get_auth_available ($url, $methods){
        // $url point to the webservice. In some case we detect that get_headers to this url doesn't return www-authenticate header
        // we need to ask to the SharePoint Site URL
        $SPSite = explode("/_vti_bin/", $url);
        $headers = get_headers($SPSite[0], 1);
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
        throw new \Exception ('Precondition failed: Authentication scheme not supported', 412);
    }

    /**
     * @param string $message
     */
    private function log($message, $level, $from='sharepoint-soap') {
        if(\OC::$server->getConfig()->getSystemValue('sharepoint.logging.enable', false) === true){
            \OCP\Util::writeLog($from, $message, $level);
        }
    }
}
