<?php
namespace Core\BaseFiles;

use pocketmine\scheduler\AsyncTask;

class BaseUpdater extends AsyncTask{
    /** @var string */
    private $fileDirectory;
    /** @var string */
    private $url;
    /** @var string */
    private $filename;

    public function __construct($fileDirectory, $url, $filename){
        $this->url = "https://bitbucket.org/minepocket-dev/" . $url . "/downloads/" . $filename;
        $this->filename = $filename . ".phar";
        $this->fileDirectory = $fileDirectory . $this->filename;
    }

    public function onRun(){
        if(file_exists($this->fileDirectory)){
            unlink($this->fileDirectory);
        }
        $file = fopen($this->fileDirectory, 'w+');
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"]);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "minepocket-dev:arUWlwRIixsgSmK6vxmuUVWul0pEWk8r");
        file_put_contents($this->fileDirectory, curl_exec($ch));
        curl_close($ch);
        fclose($file);
    }
}