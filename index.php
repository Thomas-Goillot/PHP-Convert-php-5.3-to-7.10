<?php 

//get a Folder of a project
$projectFolder = "C:\wamp\www\art315_prod";
$tempFolder = "temp/";
$return = "return/";

class Convert {

    /* 
    * @param string $projectFolder
    */
    public $projectFolder;

    /* 
    * @param string $tempFolder
    */
    public $tempFolder;

    /* 
    * @param string $return
    */
    public $return;

    public function __construct($projectFolder, $tempFolder){
        $this->projectFolder = $projectFolder;
        $this->tempFolder = $tempFolder;
    }

    public function copyToTempFolder($src, $dest)
    {
        $ignoreFileExtensions = array("pdf", "xls", "xlsx", "csv");

        foreach (scandir($src) as $file) {

            if($file != "." && $file != ".."){
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                if(!in_array($fileExtension, $ignoreFileExtensions)){
                    if (is_dir($src . '/' . $file)) {
                        mkdir($dest . '/' . $file, 0777, true);
                        $this->copyToTempFolder($src . '/' . $file, $dest . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dest . '/' . $file);
                    }
                }  
            }
        }
    }

    public function clearTempFolder($src)
    {
        foreach (scandir($src) as $file) {

            if ($file != "." && $file != "..") {
                if (is_dir($src . '/' . $file)) {
                    $this->clearTempFolder($src . '/' . $file);
                    rmdir($src . '/' . $file);                    
                } else {
                    unlink($src . '/' . $file);
                }
            }
        }
    }
}

$convert = new Convert($projectFolder, $tempFolder);
//$convert->copyToTempFolder($projectFolder, $tempFolder);
$convert->clearTempFolder($tempFolder);


?>