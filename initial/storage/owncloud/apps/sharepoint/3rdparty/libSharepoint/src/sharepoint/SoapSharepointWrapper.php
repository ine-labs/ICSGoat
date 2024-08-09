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

require_once  'sharepoint/3rdparty/ZendXml/library/ZendXml/Security.php';
use \ZendXml\Security as XmlSecurity;

date_default_timezone_set('UTC');
/**
 * SoapSharepointWrapper
 *
 * PHP LIB for manage Sharepoint document list.
 *
 * @author jmaciasportela
 * @version 0.1.0
 *
 * Tested against the Sharepoint 2007 Server, Sharepoint 2010 Server, Sharepoint 2013 Foundation
 * Usage: $sp = new SharePointWrapper('<username>','<password>','<path_to_WSDL>');
 *
 */

class SoapSharepointWrapper {

    /**
     * Username for SP auth
     */
    private $spUsername = '';

    /**
     * Password for SP auth
     */
    private $spPassword = '';

    /**
     * Location for SP Site
     */
    private $spEndpoint = '';

    /**
     * Location of WSDL
     */
    public $spWsdlPath = '';

    /**
     * Place holder for soapClient/SOAP clients
     */
    private $soapClient = array();

    /**
     * Whether SOAP errors throw exception of type SoapFault
     */
    protected $soap_exceptions = TRUE;

    /**
     * Kee-Alive HTTP setting (default: FALSE)
     */
    protected $soap_keep_alive = FALSE;

    /**
     * SOAP version number (default: SOAP_1_1)
     */
    protected $soap_version = SOAP_1_1;

    /**
     * Compression
     * Example: SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
     */
    //protected $soap_compression = (SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 9);

    /**
     * Cache behaviour for WSDL content (default: WSDL_CACHE_NONE for better debugging)
     */
    protected $soap_cache_wsdl = WSDL_CACHE_MEMORY;

    /**
     * Internal (!) encoding (not SOAP; default: UTF-8)
     */
    protected $internal_encoding = 'UTF-8';

    /**
     * Sharepoint Version
     */
    public $spVersion = '2010';

    /**
     * PHP5 Soap Client options
     */
    public $options = NULL;


    /**
     * Constructor
     *
     * @param string $spUsername User account to authenticate with
     * @param string $spPassword Password to use with authenticating account
     * @param string $spVersion  to authenticate with NTLM
     *
     * @throws Exception
     */
    public function __construct ($spUsername, $spPassword, $spEndpoint, $spVersion) {

        // Set data from parameters in this class
        $this->spUsername = $spUsername;
        $this->spPassword = $spPassword;
        $this->spEndpoint = $spEndpoint;
        $this->spVersion  = $spVersion;

        $this->spWsdlPath = __DIR__ . '/wsdl/'.$spVersion.'/';

        /*
         * General options of soapClient
         */
        $this->options = array(
            'exceptions'   => $this->soap_exceptions,
            'keep_alive'   => $this->soap_keep_alive,
            'soap_version' => $this->soap_version,
            'cache_wsdl'   => $this->soap_cache_wsdl,
            'compression'  => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 9,
            'encoding'     => $this->internal_encoding,
        );

        // Is login set?
        if (!empty($this->spUsername)) {
            $this->options['login'] = $this->spUsername;
            $this->options['password'] = $this->spPassword;
        }
    }

    /**
     * GetListCollection - Return DocumentLists in a Sharepoint Site.
     * Lists.wsdl
     *
     * @return array Formated list of document list in a Sharepoint site
     */
    public function GetListCollection() {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            $rawXml = $client->GetListCollection()->GetListCollectionResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        $result = array();

        foreach ($response as $l) {
            $tmp = array();
            foreach($l->attributes() as $a => $b) {
                $tmp[$a] = (string)$b;
            }
            if ($tmp["BaseType"] == "1" && $tmp["Hidden"] == "False"){
                array_push($result, array ('title'=> $tmp['Title'],
                         'modified'=>$tmp['Modified'],
                         'EPOXModified'=>strtotime($tmp['Modified']),
                         'created'=>$tmp['Created'],
                         'EPOXCreated'=>strtotime($tmp['Created']),
                         'itemCount'=>$tmp['ItemCount'],
                         'uid'=>$tmp['ID'],
                         ));
            }
        }
        // Add error array if stuff goes wrong.
        if (!isset($response)) {
            $result = array('warning' => 'No data returned.');
        }
        return $result;
    }

    /**
     * GetList - Return information about a document List
     * Lists.wsdl
     * @param string $listName
     *
     * @return array List of general document list info
     */
    public function GetList($listName) {
        $client = $this->getSoapClient('Lists');

        $rawXml = '';
        try {
            $params = array('listName'=>$listName);
            $rawXml = $client->GetList($params)->GetListResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        $tmp = array();
        foreach($response->attributes() as $a => $b) {
            $tmp[$a] = (string)$b;
        }

        $result = array ('title'=> $tmp['Title'],
                         'modified'=>$tmp['Modified'],
                         'EPOXModified'=>strtotime($tmp['Modified']),
                         'created'=>$tmp['Created'],
                         'EPOXCreated'=>strtotime($tmp['Created']),
                         'itemCount'=>$tmp['ItemCount'],
                         'rootFolder'=>$tmp['RootFolder'],
                         'createPermission'=>$tmp['EnableFolderCreation'],
                         'uid'=>$tmp['ID'],
                         'versioning'=> (isset($tmp['EnableVersioning'])) ? $tmp['EnableVersioning'] : "false",
                         'requireCheckout'=> (isset($tmp['requireCheckout'])) ? $tmp['requireCheckout'] : "false",
                         );

        // Add error array if stuff goes wrong.
        if (!isset($response)) {
            $result = array('warning' => 'No data returned.');
        }
        return $result;
    }

    /**
     * GetListsItems - Return an array containing all documents and folders
     * Lists.wsdl
     * @param string $listName
     * @param string $internalName
     * @param boolean $recursive
     * @param boolean $shorted
     * @param string $path
     *
     * @return array List of items in document list with info of each of them
     */
    public function GetListsItems($listName, $internalName, $recursive = TRUE, $path = NULL, $shorted = TRUE , $pagingAttribute = NULL) {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            //By default sharepoint is 30 but we increase this value to get a compromise between speed and db load
            $params = array('listName'=>$listName, 'rowLimit'=> 200);

            $query = '<QueryOptions><DateInUtc>True</DateInUtc>';
            if ($recursive) {
                $query .= '<ViewAttributes Scope="RecursiveAll"/>';
            } else {
                $query .= '<Folder>'.$internalName.'/'.$path.'</Folder>';
            }
            if($pagingAttribute !== NULL){
               $query .=  '<Paging ListItemCollectionPositionNext="'.$pagingAttribute.'"/>';
            }
            $query .= '</QueryOptions>';

            $params['queryOptions'] = array('any'=> $query);

            $params['viewFields'] = array('any'=>'<ViewFields Properties="True"><FieldRef Name="File_x0020_Size"></FieldRef></ViewFields>');
            $rawXml = $client->GetListItems($params)->GetListItemsResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $result = array();
        $response = XmlSecurity::scan($rawXml);

        $ns = $response->getNamespaces(true);
        $data = $response->children($ns['rs']);

        //Could be empty list
        if (isset($ns['z'])){
            $row = $data->children($ns['z']);

            foreach ($row as $r) {
                $tmp = array();
                foreach($r->attributes() as $a => $b) {
                    $tmp[$a] = (string)$b;
                }
                $result[trim(substr($this->normalizeString($tmp['ows_FileRef']), strlen($this->getSPSiteName($this->spEndpoint).$internalName)))] = array ('type'=>$this->normalizeString($tmp['ows_FSObjType']),
                                                                     'EPOXModified'=>strtotime($tmp['ows_Modified']),
                                                                     'modified'=>$tmp['ows_Modified'],
                                                                     'EPOXCreated'=>strtotime($this->normalizeString($tmp['ows_Created_x0020_Date'])),
                                                                     'created'=>$this->normalizeString($tmp['ows_Created_x0020_Date']),
                                                                     'id'=>$tmp['ows_ID'],
                                                                     'uid'=>$this->normalizeString($tmp['ows_UniqueId']),
                                                                     'permMask'=>$tmp['ows_PermMask'],
                                                                     'size'=>$this->normalizeString($tmp['ows_File_x0020_Size']),
                                                                     );
            }
            foreach($data->attributes() as $a => $b) {
                        if($a === 'ListItemCollectionPositionNext'){
                            $c = $this->GetListsItems($listName, $internalName, $recursive, $path, $shorted, htmlspecialchars($b));
                            $result = array_merge($result, $c);
                        }
            }
            // Add error array if stuff goes wrong.
            if (!isset($response)) {
                $result = array('warning' => 'No data returned.');
            }
            if($shorted) {
                ksort($result);
            }
        }
        return $result;
    }

    /**
     * GetListsItemsChanges - Return an array containing all documents and folders changed since date
     * Lists.wsdl
     * @param string $listName
     * @param string $date
     * @param boolean $shorted
     *
     * @return array
     */
    public function GetListItemChanges($listName, $date, $shorted = TRUE) {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            $params = array('listName'=>$listName, 'since'=>$date);
            $rawXml = $client->GetListItemChanges($params)->GetListItemChangesResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        $ns = $response->getNamespaces(true);

        $data = $response->children($ns['rs']);
        $row = $data->children($ns['z']);

        $result = array();
        $result['total'] = $row->count();

        foreach ($row as $r) {
            $tmp = array();
            foreach($r->attributes() as $a => $b) {
                $tmp[$a] = (string)$b;
            }
            $result[substr($this->normalizeString($tmp['ows_FileRef']), strlen($this->getSPSiteName($this->spEndpoint).$listName))] = array ('type'=>$this->normalizeString($tmp['ows_FSObjType']),
                                                                 'EPOXModified'=>strtotime($tmp['ows_Modified']),
                                                                 'modified'=>$tmp['ows_Modified'],
                                                                 'EPOXCreated'=>strtotime($this->normalizeString($tmp['ows_Created_x0020_Date'])),
                                                                 'created'=>$this->normalizeString($tmp['ows_Created_x0020_Date']),
                                                                 'id'=>$tmp['ows_ID'],
                                                                 'uid'=>$this->normalizeString($tmp['ows_UniqueId']),
                                                                 );
        }
        // Add error array if stuff goes wrong.
        if (!isset($response)) {
            $result = array('warning' => 'No data returned.');
        }

        if($shorted) {
            ksort($result);
        }
        return $result;
    }


    /**
     * GetListItemChangesSinceToken - Return an array containing all changes since changeToken
     * Lists.wsdl
     * @param string $listName
     * @param string $changeToken
     *
     * @return array
     */
    public function GetListItemChangesSinceToken($listName, $changeToken) {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            $params = array('listName'=>$listName, 'changeToken'=>$changeToken);
            $rawXml = $client->GetListItemChangesSinceToken($params)->GetListItemChangesSinceTokenResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        $tmp = array();
        foreach($response->Changes->attributes() as $a => $b) {
            $tmp[$a] = (string)$b;
        }

        $result = array();
        $result['LastChangeToken'] = $tmp['LastChangeToken'];

        try{
            $tmp = array();
            foreach($response->Changes->Id as $change) {
                foreach($change->attributes() as $a => $b) {
                    $tmp[$a] = (string)$b;
                }
                $tmp['changes'][$tmp['UniqueId']] = array();
                $tmp['changes'][$tmp['UniqueId']]['ChangeType'] = $tmp['ChangeType'];
            }
            $result['changes'] = $tmp['changes'];

            $ns = $response->getNamespaces(true);
            $data = $response->children($ns['rs']);
            if(isset($ns['z'])){
                $row = $data->children($ns['z']);
                $result['total'] = $row->count();
                foreach ($row as $r) {
                    $tmp = array();
                    foreach($r->attributes() as $a => $b) {
                        $tmp[$a] = (string)$b;
                    }
                    $result[substr($this->normalizeString($tmp['ows_FileRef']), strlen($this->getSPSiteName($this->spEndpoint).$listName))] = array ('type'=>$this->normalizeString($tmp['ows_FSObjType']),
                                                                         'EPOXModified'=>strtotime($tmp['ows_Modified']),
                                                                         'modified'=>$tmp['ows_Modified'],
                                                                         'EPOXCreated'=>strtotime($this->normalizeString($tmp['ows_Created_x0020_Date'])),
                                                                         'created'=>$this->normalizeString($tmp['ows_Created_x0020_Date']),
                                                                         'id'=>$tmp['ows_ID'],
                                                                         'uid'=>$this->normalizeString($tmp['ows_UniqueId']),
                                                                         );
                }
                if ($result['total'] == 0 || count($result['changes']) == 0) {
                    $result = array('warning' => 'No data returned.');
                }

            }
        } catch (\Exception $e) {
            //TODO
        }
        return $result;
    }


    /**
     * UpdateListsItems - Used to create folders
     * Lists.wsdl
     * @param string $listName
     * @param string $rootFolder
     * @param string $folderName
     *
     * @throws Exception
     * @return array
     */
    public function UpdateListItems($listName, $rootFolder, $folderName) {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            $batch = '<Batch OnError="Continue" RootFolder="'.$rootFolder.'" >
                        <Method ID="1" Cmd="New">
                            <Field Name="FSObjType">1</Field>
                            <Field Name="BaseName">'.$folderName.'</Field>
                       </Method>
                    </Batch>';

            $params = array('listName'=>$listName, 'updates'=> array('any'=>$batch));
            $rawXml = $client->UpdateListItems($params)->UpdateListItemsResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        if((string)$response->Result->ErrorCode==='0x8107090d')
            throw new \Exception ($response->Result->ErrorText);
        return array('code'=>'200');
    }

    /**
     * createFolder
     * Used to create folder
     * Dws.wsdl
     * @param string $folderPath
     *
     * @return array
     */
    public function createFolder($folderPath) {
        $client = $this->getSoapClient('Dws');
        $rawXml = '';
        try {
            $params = array('url'=>$folderPath);
            $rawXml = $client->createFolder($params)->CreateFolderResult;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        if(!empty($response[0])) {
            throw new \Exception ($response[0]);
        }
        return array('code' => '200');
    }

    /**
     * deleteFolder
     * Used to delete folder
     * Dws.wsdl
     * @param string $folderPath
     *
     * @return array
     */
    public function deleteFolder($folderPath) {
        $client = $this->getSoapClient('Dws');
        $rawXml = '';
        try {
            $params = array('url'=>$folderPath);
            $rawXml = $client->deleteFolder($params)->DeleteFolderResult; //->DeleteFolderResponse->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        if(!empty($response[0])) {
            throw new \Exception ($response[0]);
        }
        return array('code' => '200');
    }

    /** Rename item
     * http://stackoverflow.com/questions/994173/how-do-i-rename-a-file-using-the-sharepoint-web-services
     */

    //TODO

    /**
     * deleteFile - Used to delete file
     * Lists.wsdl
     * @param string $listName
     * @param string $id
     * @param string $url
     *
     * @throws Exception
     *
     * @return array
     */
    public function deleteFile($listName, $id, $url) {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            $batch = '<Batch OnError="Continue" >
                        <Method ID="1" Cmd="Delete">
                            <Field Name="ID">'.$id.'</Field>
                            <Field Name="FileRef">'.$this->getSPSiteName($this->spEndpoint)."/".$url.'</Field>
                       </Method>
                    </Batch>';

            $params = array('listName'=>$listName, 'updates'=> array('any'=>$batch));
            $rawXml = $client->UpdateListItems($params)->UpdateListItemsResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        if((string)$response->Result->ErrorCode === '0x81020030' || (string)$response->Result->ErrorCode === '0x80070005') {
            throw new \Exception ($response->Result->ErrorText);
        }
        return array('code' => '200');
    }

    /**
     * uploadFile - Used to upload file
     * Copy.wsdl
     * @param string $srcPath
     * @param string $dstPath
     *
     * @throws Exception
     *
     * @return array
     */
    public function uploadFile($srcPath, $dstPath) {
        $client = $this->getSoapClient('Copy');
        $stream = file_get_contents($srcPath);
        $rawXml = '';
        try {
            $params = array('Fields'=> array('FieldInformation'), 'SourceUrl'=>'null', 'DestinationUrls'=>array('string'=>$dstPath), 'Stream'=>$stream);
            $rawXml = $client->CopyIntoItems($params); //->Results->CopyResult->ErrorCode;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        if((string)$rawXml->Results->CopyResult->ErrorCode !== 'Success') {
            throw new \Exception ($rawXml->Results->CopyResult->ErrorMessage);
        }
        return array('code' => '200');
    }

    /**
     * downloadFile - Used to download file
     * Copy.wsdl
     * @param string $url
     *
     * @return string
     */
    public function downloadFile($url) {
        $client = $this->getSoapClient('Copy');
        $rawXml = '';
        try {
            $params = array('Url'=>$url);
            $rawXml = $client->GetItem($params);
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        return $rawXml;
    }

    /**
     * copyFile
     * Used to copy localy a file
     * Copy.wsdl
     * @param string $srcURL
     * @param string $dstURL
     *
     * @throws Exception
     *
     * @return array
     */
    public function copyFile($srcURL, $dstURL) {
        $client = $this->getSoapClient('Copy');
        $rawXml = '';
        try {
            $params = array('SourceUrl'=>$srcURL, 'DestinationUrls'=>array('string'=>$dstURL));
            $rawXml = $client->CopyIntoItemsLocal($params);
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        if((string)$rawXml->Results->CopyResult->ErrorCode !== 'Success') {
            throw new \Exception ($rawXml->Results->CopyResult->ErrorMessage);
        }
        return array('code' => '200');
    }

    /**
     * renameFolder - Used to rename Folder
     * Lists.wsdl
     * @param string $listName
     * @param string $id
     * @param string $newName
     *
     * @throws Exception
     *
     * @return array
     */
    public function renameFolder($listName, $id, $newName) {
        $client = $this->getSoapClient('Lists');
        $rawXml = '';
        try {
            $batch = '<Batch OnError="Continue">
                       <Method ID="1" Cmd="Update">
                          <Field Name="ID">'.$id.'</Field>
                          <Field Name="BaseName">'.$newName.'</Field>
                       </Method>
                    </Batch>';

            $params = array('listName'=>$listName, 'updates'=> array('any'=>$batch));
            $rawXml = $client->UpdateListItems($params)->UpdateListItemsResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }

        $response = XmlSecurity::scan($rawXml);

        if((string)$response->Result->ErrorCode === '0x81020030' || (string)$response->Result->ErrorCode === '0x81020073') {
            throw new \Exception ($response->Result->ErrorText);
        }
        return array('code' => '200');
    }

    /**
     * renameFolder - Used to rename Folder
     * Lists.wsdl
     * @param string $uri
     *
     * @throws Exception
     *
     * @return array
     */
    public function checkInFile($listName, $path) {
        $client = $this->getSoapClient('Lists');
        $url = $this->spEndpoint.'/'.$listName.'/'.$path;
        try {
            $params = array('pageUrl'=>$url);
            $response = $client->CheckInFile($params)->CheckInFileResult->any;
        } catch (\Exception $fault) {
            $this->onError($fault);
        }
        if((string)$response === "false") {
            return array('code' => '200', 'result' => false);
        }
        return array('code' => '200', 'result' => true);
    }

    /**
     * getSoapClient
     * @param string $ws
     *
     * @return Auth\SoapClientAuthNTLM
     */
    private function getSoapClient($ws){
        if(!isset($this->soapClient[$ws])) {
            try {
                require_once __DIR__.'/Auth/SoapClientAuthNTLM.php';
                // Related to http://www.php.net/manual/es/soapclient.soapclient.php#111892
                // http://es1.php.net/manual/es/function.libxml-disable-entity-loader.php
                // It is previously disabled by sabreDAV
                libxml_disable_entity_loader(false);
                $this->soapClient[$ws] = new \sharepoint\Auth\SoapClientAuthNTLM($this->spWsdlPath.$ws.'.wsdl', $this->options);
                $this->soapClient[$ws]->__setLocation($this->spEndpoint.'/_vti_bin/'.$ws.'.asmx');
                libxml_disable_entity_loader(true);
            } catch (\Exception $fault) {
                throw new \Exception('Unable to locate WSDL file. faultcode=' . $fault->getCode() . ',faultstring=' . $fault->getMessage());
            }
        }
        return $this->soapClient[$ws];
    }


    /**
     * onError
     * @param \Exception $fault
     *
     * @return
     */
    private function onError($fault){
        //TODO
        throw new \Exception($fault->getMessage(), $fault->getCode());
    }

    /**
     * normalizeString
     * Return string without ID xx;#
     * @param string $string
     *
     * @return string
     */
    private function normalizeString($string){
        $aux = explode(';#', $string);
        return $aux[1];
    }

    /**
     * getSPSiteName
     *
     * @param string $url
     * @return string
     */
    private function getSPSiteName($url){
        $parse = parse_url($url);
        if(isset($parse["path"]) && $parse["path"] !=="/"){
            return rawurldecode($parse["path"]);
        }
        return "";
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
