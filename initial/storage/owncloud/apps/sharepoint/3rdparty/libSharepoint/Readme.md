# PHP libSharepoint client

Using the PHP libSharepoint client, you can manage document list and their files and folders.

### Usage Instructions

#### Intro

Methods available to manage files and folders are in different SOAP interfaces:

* Lists -> http://sharepoint/_vti_bin/Lists.asmx
* Copy ->  http://sharepoint/_vti_bin/Copy.asmx
* DWS ->  http://sharepoint/_vti_bin/Dws.asmx

Also We can use REST interface for few operations:

* Lists -> http://sharepoint/_vti_bin/ListData.svc

#### Installation

Download a copy of the libSharepoint files manually and include the top "SharePointWrapper.php" class in your project.

#### Creating libSharepoint client

In order to use the PHP libSharepoint client you will need a valid user/service account with the permissions to the required list. 

For most SharePoint installations, you can create a new instance of the API using:

    require_once( 'src/SharepointWrapper.php' );
    $client =  new \sharepoint\SoapSharepointWrapper('administrator', 'password', 'sharepointSite', '2010');
    or
    $client =  new \sharepoint\RestSharepointWrapper('administrator', 'SolidGear-SL1', 'http://sharepoint/site/', '2010');

#### SOAP Methods

##### Lists.asmx

###### GetList

This method is used to return info about a document list. Returns filtered response as array.

    Getlist ******************************************************************************************************
    Array
    (
        [tile] => test
        [modified] => 20140326 20:36:27
        [EPOXModified] => 1395866187
        [created] => 20140228 21:44:10
        [EPOXCreated] => 1393623850
        [itemCount] => 4
        [uid] => {CC3116FC-0DF2-4863-A208-57CFD0445493}
    )
    

###### GetListItems

This method is used to return info about files and folder in a document list. Returns filtered response as array. It uses internal path as array key.

    GetListItems *************************************************************************************************
    Array
    (
        [test/JavaScript__The_Good_Parts.pdf] => Array
            (
                [type] => 0
                [EPOXModified] => 1393623872
                [modified] => 2014-02-28T21:44:32Z
                [EPOXCreated] => 1393623873
                [created] => 2014-02-28T21:44:33Z
                [id] => 1
                [uid] => {0211E309-AB1A-4406-909C-B505F073347B}
                [size] => 1555484
            )
    
        [test/animal.jpg] => Array
            (
                [type] => 0
                [EPOXModified] => 1395694416
                [modified] => 2014-03-24T20:53:36Z
                [EPOXCreated] => 1395694415
                [created] => 2014-03-24T20:53:35Z
                [size] => 22504
            )
    
        [test/test] => Array
            (
                [type] => 1
                [EPOXModified] => 1393625167
                [modified] => 2014-02-28T22:06:07Z
                [EPOXCreated] => 1393625167
                [created] => 2014-02-28T22:06:07Z
                [id] => 10
                [uid] => {604DDFA7-AC62-47A3-AAF2-763F77F2829C}
                [size] => 
            )
    
        [test/test/sharepoint_plugin_icon.png] => Array
            (
                [type] => 0
                [EPOXModified] => 1395264914
                [modified] => 2014-03-19T21:35:14Z
                [EPOXCreated] => 1395264914
                [created] => 2014-03-19T21:35:14Z
                [id] => 114
                [uid] => {F36C5195-F705-4D3C-BD03-21062CCD62A6}
                [size] => 38231
            )
    
        [total] => 4
    )

###### GetListItemsChangesSinceToken

This method is used to return info about changes on files and folder since o moment in time delimited by changeToken returned every time that you use this method. Return filtered response as array.

    GetlistItemsChangesSinceToken ********************************************************************************
    Array
    (
        [LastChangeToken] => 1;3;cc3116fc-0df2-4863-a208-57cfd0445493;635314740485730000;821
        [changes] => Array
            (
                [{BB557076-8740-496F-B940-D06FAD547760}] => Array
                    (
                        [ChangeType] => Delete
                    )
    
                [{6A5C612D-E1E2-4D10-9CA3-2E85209A60C1}] => Array
                    (
                        [ChangeType] => Rename
                    )
            )
    
        [total] => 2
        [test/test/sharepoint_plugin_icon.png] => Array
            (
                [type] => 0
                [EPOXModified] => 1395264914
                [modified] => 2014-03-19T21:35:14Z
                [EPOXCreated] => 1395264914
                [created] => 2014-03-19T21:35:14Z
                [id] => 114
                [uid] => {F36C5195-F705-4D3C-BD03-21062CCD62A6}
        [test/animal.jpg] => Array
            (
                [type] => 0
                [EPOXModified] => 1395694416
                [modified] => 2014-03-24T20:53:36Z
                [EPOXCreated] => 1395694415
                [created] => 2014-03-24T20:53:35Z
                [id] => 119
                [uid] => {D249553C-FAF0-4B31-A479-BE7BBE34E1C9}
            )
    
    )

###### deleteFile (UpdateListItems)

This method is used to create a Folder. Returns an array.
    
    deleteFile ***************************************************************************************************
    Array
    (
        [code] => 200
    )
    
###### renameFile (UpdateListItems)

TODO

###### renameFolder (UpdateListItems)

TODO

##### DWS.asmx

###### createFolder

This method is used to create a Folder. Returns an array.
    
    createFolder *************************************************************************************************
    Array
    (
        [code] => 200
    )
    
###### deleteFolder

This method is used to delete a Folder. Returns an array.
    
    deleteFolder *************************************************************************************************
    Array
    (
        [code] => 200
    )    

##### Copy.asmx

###### uploadFile (CopyIntoItems)

This method is used to upload a File. Returns an array.


    uploadFile ***************************************************************************************************
    Array
    (
        [code] => 200
    )
    
###### downloadFile (GetItem)

This method is used to download a File. Returns an array.    
    
    downloadFile *************************************************************************************************
    Array
    (
        [code] => 200
    )

#### REST

##### Listdata.svc

###### uploadFile

    $client->uploadFile($localPath, $destinationPath, $gzip);
    $client->uploadFile('./image.png', '/test/uploadREST.png', FALSE);
***
    POST /_vti_bin/ListData.svc/Test HTTP/1.1
    Authorization: NTLM TlRMTVNTUAADAAAAGAAYAEAAAAAYABgAWAAAAAAAAABwAAAADQANAHAAAAAHAAcAfQAAAAAAAAAAAAAABoKKAsYShl5zuVC9AAAAAAAAAAAAAAAAAAAAAFZsTvKBUI5AM1JO4NVMXQMwMDoF1vQOP2FkbWluaXN0cmF0b3Jhc3VzaXRv
    Host: vmsharepoint:8080
    Accept: */*
    User-Agent: OC-PHP-REST
    Slug: /test/uploadREST.png
    Content-Type: application/octet-stream
    Content-Length: 297605
    
    .PNG
    .
    ...
    IHDR.............u.&.....tEXtSoftware.Adobe ImageReadyq.e<...'IDATx...k.,.u.w.z..xe.K.x....P.....m..
    .....^...u..P8.v.......%~.'..p.?Q........V..nX^.r....2g......2)>TM.......`...Y.U.9....{g.....tWWW..../.9.S.m.g....%_+.m.4444....k,......y......[..h..O%444..444444.....S.W..BCCCpACCCCCCp..T..YApACCCpACCCCCCp95p)6..MLC.ACCCpACCCCCCp9...#....-....!........!.d.K..3..cvV{B....!.......!.\Pp..Z......L..ACCpACCCCCC......./Ml...6.D.N.e........;...............Tte.gr........I*...............2.....R$.&..b`rV!.....=74..4444444.....=/"0.r.c..6.1....GPAxACCpACCCCCCp9.....[^;.X..Ps..........4..44..444444.....
    .S.R.x..}!.H......A*2.9N...XW....\.......n.p..,6..`......%.y.y..`.4.<..Z...J.;zvh..hhhhhhh.%..BK...XD..Eq..^C&...ANC.b.{...|cA.{..!............R.....V|......*...2 .8...wc....8.r^....!.......!.....DY\p)3....P....6.......1.Rl.^........ACCpACCCCCCp9.pIE[b..{]f.K.q.}.U.=..L.......R.c..........hh..hhhhhh.....I@K.....R.\.<./.L.....%...o?..K-s...l..................q...J..K(..~..._A8..../).2...i..#.
    .........)....4..444444.....h..T\h)...".)p.Ae.y.......C..wMJQ
    .."..'...A.
    
    
    HTTP/1.1 201 Created
    Cache-Control: no-cache
    Content-Type: application/atom+xml;charset=utf-8
    ETag: W/"1"
    Location: http://vmsharepoint:8080/_vti_bin/listdata.svc/Test(149)
    Server: Microsoft-IIS/7.5
    SPRequestGuid: 119ab8d2-59d0-4cb6-8462-b290c1acd762
    X-SharePointHealthScore: 1
    DataServiceVersion: 1.0;
    X-AspNet-Version: 2.0.50727
    Persistent-Auth: true
    X-Powered-By: ASP.NET
    MicrosoftSharePointTeamServices: 14.0.0.7015
    X-MS-InvokeApp: 1; RequireReadOnly
    Date: Thu, 27 Mar 2014 00:07:42 GMT
    Content-Length: 2656
    
    <?xml version="1.0" encoding="utf-8" standalone="yes"?>
    <entry xml:base="http://vmsharepoint:8080/_vti_bin/listdata.svc/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" m:etag="W/&quot;1&quot;" xmlns="http://www.w3.org/2005/Atom">
      <id>http://vmsharepoint:8080/_vti_bin/listdata.svc/Test(149)</id>
      <title type="text"></title>
      <updated>2014-03-27T01:07:43-07:00</updated>
      <author>
        <name />
      </author>
      <link m:etag="&quot;{EF06AD0D-573E-40AF-ACA0-CA5D2E744192},1&quot;" rel="edit-media" title="TestItem" href="Test(149)/$value" />
      <link rel="edit" title="TestItem" href="Test(149)" />
      <link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/CreadoPor" type="application/atom+xml;type=entry" title="CreadoPor" href="Test(149)/CreadoPor" />
      <link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/ModificadoPor" type="application/atom+xml;type=entry" title="ModificadoPor" href="Test(149)/ModificadoPor" />
      <link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/DesprotegidoPara" type="application/atom+xml;type=entry" title="DesprotegidoPara" href="Test(149)/DesprotegidoPara" />
      <category term="Microsoft.SharePoint.DataService.TestItem" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" />
      <content type="image/png" src="http://vmsharepoint:8080/test/uploadREST.png" />
      <m:properties xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices">
        <d:Identificador m:type="Edm.Int32">149</d:Identificador>
        <d:IdDeTiposDeContenido m:null="true"></d:IdDeTiposDeContenido>
        <d:TipoDeContenido m:null="true"></d:TipoDeContenido>
        <d:Creado m:type="Edm.DateTime">2014-03-27T01:07:43</d:Creado>
        <d:CreadoPorId m:type="Edm.Int32">1</d:CreadoPorId>
        <d:Modificado m:type="Edm.DateTime">2014-03-27T01:07:43</d:Modificado>
        <d:ModificadoPorId m:type="Edm.Int32">1</d:ModificadoPorId>
        <d:CopiarOrigen m:null="true"></d:CopiarOrigen>
        <d:EstadoDeAprobaci..n>0</d:EstadoDeAprobaci..n>
        <d:RutaDeAcceso>/test</d:RutaDeAcceso>
        <d:DesprotegidoParaId m:type="Edm.Int32" m:null="true"></d:DesprotegidoParaId>
        <d:Nombre>uploadREST.png</d:Nombre>
        <d:EstadoDelVirus>297605</d:EstadoDelVirus>
        <d:EsLaVersi..nActual m:type="Edm.Boolean">true</d:EsLaVersi..nActual>
        <d:Owshiddenversion m:type="Edm.Int32">1</d:Owshiddenversion>
        <d:Versi..n>1.0</d:Versi..n>
        <d:T..tulo m:null="true"></d:T..tulo>
      </m:properties>
    </entry>

###### downloadFile
    
    $client->downloadFile($url, $localPath);
    $client->downloadFile('http://vmsharepoint/test/uploadREST.png', 'downloadREST.png');
***
    GET /test/uploadREST.png HTTP/1.1
    Authorization: NTLM TlRMTVNTUAADAAAAGAAYAEAAAAAYABgAWAAAAAAAAABwAAAADQANAHAAAAAHAAcAfQAAAAAAAAAAAAAABoKKAkwZGXkZKUpXAAAAAAAAAAAAAAAAAAAAAEkI41KwoTJI1yg3FTKQB1549MX/aokZqGFkbWluaXN0cmF0b3Jhc3VzaXRv
    Host: vmsharepoint
    User-Agent: OC-PHP-REST
    Accept: application/octet-stream
    
    HTTP/1.1 200 OK
    Cache-Control: private,max-age=0
    Content-Length: 297605
    Content-Type: image/png
    Expires: Wed, 12 Mar 2014 00:07:42 GMT
    Last-Modified: Thu, 27 Mar 2014 00:07:43 GMT
    ETag: "{EF06AD0D-573E-40AF-ACA0-CA5D2E744192},1"
    Server: Microsoft-IIS/7.5
    SPRequestGuid: 1a60af72-0c71-474a-b2db-2a0d6df08f5a
    X-SharePointHealthScore: 1
    ResourceTag: rt:EF06AD0D-573E-40AF-ACA0-CA5D2E744192@00000000001
    X-Content-Type-Options: nosniff
    Public-Extension: http://schemas.microsoft.com/repl-2
    Persistent-Auth: true
    X-Powered-By: ASP.NET
    MicrosoftSharePointTeamServices: 14.0.0.7015
    X-MS-InvokeApp: 1; RequireReadOnly
    Date: Thu, 27 Mar 2014 00:07:42 GMT

    .PNG
    .
    ...
    IHDR.............u.&.....tEXtSoftware.Adobe ImageReadyq.e<...'IDATx...k.,.u.w.z..xe.K.x....P.....m..
    .....^...u..P8.v.......%~.'..p.?Q........V..nX^.r....2g......2)>TM.......`...Y.U.9....{g.....tWWW..../.9.S.m.g....%_+.m.4444....k,......y......[..h..O%444..444444.....S.W..BCCCpACCCCCCp..T..YApACCCpACCCCCCp95p)6..MLC.ACCCpACCCCCCp9...#....-....!........!.d.K..3..cvV{B....!.......!.\Pp..Z......L..ACCpACCCCCC......./Ml...6.D.N.e........;...............Tte.gr........I*...............2.....R$.&..b`rV!.....=74..4444444.....=/"0.r.c..6.1....GPAxACCpACCCCCCp9.....[^;.X..Ps..........4..44..444444.....
    .S.R.x..}!.H......A*2.9N...XW....\.......n.p..,6..`......%.y.y..`.4.<..Z...J.;zvh..hhhhhhh.%..BK...XD..Eq..^C&...ANC.b.{...|cA.{..!............R.....V|......*...2 .8...wc....8.r^....!.......!.....DY\p)3....P....6.......1.Rl.^........ACCpACCCCCCp9.pIE[b..{]f.K.q.}.U.=..L.......R.c..........hh..hhhhhh.....I@K.....R.\.<./.L.....%...o?..K-s...l..................q...J..K(..~..._A8..../).2...i..#.
    .........)....4..444444.....h..T\h)...".)p.Ae.y.......C..wMJQ
    .."..'...A.
