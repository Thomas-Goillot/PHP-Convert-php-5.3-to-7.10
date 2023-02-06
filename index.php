<?php 

//$projectFolder = "C:\wamp\www\art315_prod";
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

    public function __construct(string $projectFolder, string $tempFolder){
        $this->projectFolder = $projectFolder;
        $this->tempFolder = $tempFolder;
    }

    public function dirIsEmpty(string $dir){
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    public function copyToFolder(string $src = "", string $dest = ""){

        if($src === "") $src = $this->projectFolder;
        if($dest === "") $dest = $this->tempFolder;

        $ignoreFileExtensions = array("pdf", "xls", "xlsx", "csv");

        if(!$this->dirIsEmpty($dest)){
            $this->clearFolder($this->tempFolder);
        }

        foreach (scandir($src) as $file) {

            if($file != "." && $file != ".."){
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                if(!in_array($fileExtension, $ignoreFileExtensions)){
                    if (is_dir($src . '/' . $file)) {
                        mkdir($dest . '/' . $file, 0777, true);
                        $this->copyToFolder($src . '/' . $file, $dest . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dest . '/' . $file);
                    }
                }  
            }
        }
    }

    public function clearFolder(string $src = ""){

        if($src === "") $src = $this->tempFolder;

        foreach (scandir($src) as $file) {

            if ($file != "." && $file != "..") {
                if (is_dir($src . '/' . $file)) {
                    $this->clearFolder($src . '/' . $file);
                    rmdir($src . '/' . $file);                    
                } else {
                    unlink($src . '/' . $file);
                }
            }
        }
    }

    public function getAllFilesWithClass(){
        $files = glob($this->tempFolder . '**/*.php');
        $filesWithClass = array();
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $pattern = '/class\s+(\w+)/';
            preg_match_all($pattern, $content, $matches);
            if(count($matches[1]) > 0){
                array_push($filesWithClass, $file);
            }
        }

        return $filesWithClass;
    }

    public function replaceConstructor(string $file){
        $content = file_get_contents($file);
        $pattern = '/class\s+(\w+)/';
        preg_match_all($pattern, $content, $matches);
        $className = $matches[1][0];
        $pattern = '/function\s+(\w+)/';
        preg_match_all($pattern, $content, $matches);
        $functionName = $matches[1][0];
        if($className == $functionName){
            $content = str_replace($functionName, "__construct", $content);
            file_put_contents($file, $content);
        }
    }


}

//demander a l'utilisateur le chemin du dossier du projet
$projectFolder = readline("Enter the path of the project folder:");

echo "Project folder: " . $projectFolder . "\n";

if(!is_dir($projectFolder)){
    echo "This folder doesn't exist";
    exit;
}


$convert = new Convert($projectFolder, $tempFolder);

//Clear all working folders
$convert->clearFolder($return);
$convert->clearFolder();

//Copy files from project folder to temp folder
$convert->copyToFolder();

//Get all files with class
$filesWithClass = $convert->getAllFilesWithClass();

//Replace constructor name with __construct
foreach ($filesWithClass as $file) {
    $convert->replaceConstructor($file);
}

//Copy files from temp folder to return folder
$convert->copyToFolder($tempFolder, $return);

//Print all files edited
print_r($filesWithClass);

//Clear temp folder
$convert->clearFolder();



?>