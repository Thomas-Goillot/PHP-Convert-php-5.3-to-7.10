<?php 

//$projectFolder = "C:\wamp\www\art315_prod";
$tempFolder = "temp/";
$returnFolder = "return/";
$xajaxFolder = "xajax/";

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
    * @param string $returnFolder
    */
    public $returnFolder;

    /* 
    * @param string $projectFolder
    * @param string $tempFolder
    * @param string $returnFolder
    */
    public function __construct(string $projectFolder, string $tempFolder, string $returnFolder){

        if(!is_dir($tempFolder)){
            mkdir($tempFolder, 0777, true);
        }

        if(!is_dir($returnFolder)){
            mkdir($returnFolder, 0777, true);
        }

        $this->projectFolder = $projectFolder;
        $this->tempFolder = $tempFolder;
    }

    /* 
    * @param string $dir
    * @return bool
    */
    public function dirIsEmpty(string $dir): bool{
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

    /* 
    * @param string $src
    * @param string $dest
    * @return void
    */
    public function copyToFolder(string $src = "", string $dest = ""): void{

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

    /* 
    * @param string $src
    * @return void
    */
    public function clearFolder(string $src = ""): void{

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

    /* 
    * @return array
    */
    public function getAllFilesWithClass(): array{
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

    /* 
    * @param string $file
    * @return void
    */
    public function replaceConstructor(string $file): void{
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

    /* 
    * @return bool
    */
    public function detectXajax(): bool{

        $files = glob($this->tempFolder . '**/*.php');

        //read the content of every file and search for xajax
        foreach ($files as $file) {

            //check the filename first
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if(strpos($fileName, "xajax") !== false){
                return true;
            }

            //then check the content
            $content = file_get_contents($file);
            $pattern = '/xajax/';
            preg_match_all($pattern, $content, $matches);
            if(count($matches[0]) > 0){
                return true;
            }
        }

        return false;

    }

    public function changeXajaxPath(){
        $files = glob($this->tempFolder . '**/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);

            //check for $xajax->printJavascript() and change what's inside the parenthesis
            $pattern = '/\$xajax->printJavascript\((.*)\)/';
            preg_match_all($pattern, $content, $matches);
            if(count($matches[0]) > 0){
                $content = str_replace($matches[1][0], "'../xajaxPHP7.2'", $content);
                file_put_contents($file, $content);
            }
        }
    }

    public function __destruct(){
        $this->clearFolder($this->tempFolder);
    }


}

//demander a l'utilisateur le chemin du dossier du projet
$projectFolder = readline("Enter the path of the project folder:");

echo "Project folder: " . $projectFolder . "\n";

if(!is_dir($projectFolder)){
    echo "This folder doesn't exist";
    exit;
}


$convert = new Convert($projectFolder, $tempFolder, $returnFolder);

//Clear all working folders
$convert->clearFolder($returnFolder);
$convert->clearFolder($tempFolder);

//Copy files from project folder to temp folder
$convert->copyToFolder();

//Get all files with class
$filesWithClass = $convert->getAllFilesWithClass();

//Replace constructor name with __construct
foreach ($filesWithClass as $file) {
    $convert->replaceConstructor($file);
}

//Copy files from temp folder to return folder
$convert->copyToFolder($tempFolder, $returnFolder);

//Print all files edited
if(count($filesWithClass) > 0){
    echo "Files edited: \n";
    foreach ($filesWithClass as $file) {
        echo $file . "\n";
    }
} else {
    echo "No files edited \n";
}

//Detect xajax
$filesWithXajax = $convert->detectXajax();

if($filesWithXajax){
    echo "Xajax as been detected on the project \n";
    $xajaxAdd = readline("Do you want to update and add xajax to the project? (y/n): ");

    if($xajaxAdd == "y"){
        $convert->copyToFolder($xajaxFolder, $returnFolder);
    }

    $xajaxChangePath = readline("Do you want to change the path of xajax path in the project? (y/n): ");

    if($xajaxChangePath == "y"){
        $convert->changeXajaxPath();
    }

} else {
    echo "No xajax detected \n";
}


?>