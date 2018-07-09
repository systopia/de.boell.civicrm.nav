<?php
/**
 * Created by PhpStorm.
 * User: phil
 * Date: 17.05.18
 * Time: 16:53
 */


class mySOAPClient extends SoapClient {

  public function __doRequest($request, $location, $action, $version, $one_way = 0) {
    echo "Request:\n" . $request;
    echo "\nLocation:\n" . $location;
    echo "\nAction:\n" . $action;
    echo "\nVersion:\n" . $version;
    echo "\nOneWay: " . $one_way . "\n";
    return parent::__doRequest($request, $location, $action, $version, $one_way); // TODO: Change the autogenerated stub
  }
}

// Testing will be done with CiviContact.wsdl
class SoapTester {

  private $wsdl;

  //  private $ns;

  private $soapClient;

  private $location;

  private $password;

  private $login;

  private $soap_options;

  public function __construct() {
    $this->wsdl = "wsdl/civiContact.wsdl";
    //    $this->ns = "urn:microsoft-dynamics-schemas/page/civicontact";
    $pw_file = "pw.txt";
    $login_file = "user.txt";

    $file_content = explode("\n", file_get_contents($pw_file));
    $this->password = array_pop(array_reverse($file_content));
    $file_content = explode("\n", file_get_contents($login_file));
    $this->login = array_pop(array_reverse($file_content));
    // Location CiviContact
    //    $this->location = "http://10.1.0.148:7057/HBS_TEST/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/civiContact";
    $this->location = "http://10.1.0.148:7037/NAVUSER/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/civiContact";
    //    $this->location = "http://10.1.0.148:7057/HBS_TEST/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/civiContRelation";
    //    $this->location = "http://10.1.0.148:7057/HBS_TEST/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/civiProcess";
    //    $this->location = "http://10.1.0.148:7057/HBS_TEST/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/civiContStatus":

    $context = stream_context_create([
      'ssl' => [
        // set some SSL/TLS specific options
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
      ]
    ]);
    $this->soap_options = [
      "trace" => 1,
      "exceptions" => TRUE,
      "cache_wsdl" => WSDL_CACHE_NONE,
      //     "soap_version"  => SOAP_1_1,
      //     'use' => SOAP_LITERAL,
      'login' => $this->login,
      'password' => $this->password,
      "stream_context" => $context,
      "location" => $this->location,
      //      "uri" => $this->uri,
    ];
    // general exception catch
    try {
      $this->soapClient = new mySOAPClient($this->wsdl, $this->soap_options);
    } catch (Exception $e) {
      echo $e->getMessage();
    }

    echo "Password: " . $this->password . "    User: " . $this->login . "\n";
  }

  public function readRecord() {
    // Reads the record #1
    $params = array(
      "Entry_No" => 1,
    );
    try{
      $result = $this->soapClient->Read($params);
      echo "Soap Call Result:\n";
      print_r($result);
    } catch (Exception $e) {
      echo "Exception Error!";
      echo $e->getMessage();
    }
  }

}

$myTester = new SoapTester();

echo "Starting soap call\n";
$myTester->readRecord();

