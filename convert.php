<?php

class Convert {

    /* 
    * @param string $projectFolder
    */
    public string $projectFolder;

    /* 
    * @param string $tempFolder
    */
    public string $tempFolder;

    /* 
    * @param string $returnFolder
    */
    public string $returnFolder;

    /* 
    * @param Log $log
    */
    public Log $log;

    /* 
    * @param string $projectFolder
    * @param string $tempFolder
    * @param string $returnFolder
    */
    public function __construct(string $projectFolder, string $tempFolder, string $returnFolder){

        $this->log = new Log();

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
    * @param string $src
    * @param string $dest
    * @return void
    */
    public function copyToFolder(string $src = "", string $dest = ""): bool{

        if($src === "") $src = $this->projectFolder;
        if($dest === "") $dest = $this->tempFolder;

        $ignoreFileExtensions = array("pdf", "xls", "xlsx", "csv");

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
        return true;
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

    /* 
    * @param bool $debug
    * @return void
    */
    public function changeXajaxPath(bool $debug = false): void{

        //get all files
        $files = scandir($this->tempFolder);
    
        //remove the . and .. from the array
        unset($files[0]);
        unset($files[1]);

        //add the subfiles to the array (recursive)
        foreach ($files as $key => $file) {
            if(is_dir($this->tempFolder.$file)){
                $subFiles = scandir($this->tempFolder.$file);
                unset($subFiles[0]);
                unset($subFiles[1]);
                foreach ($subFiles as $subFile) {
                    array_push($files, $file."/".$subFile);
                }
                unset($files[$key]);
            }
        }

        //reindex the array
        $files = array_values($files);
        $totalFiles = count($files);
        $errors = 0;

        $newPath = "xajaxPHP7.2/";

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });

        foreach ($files as $file) {
            try{
                $content = file_get_contents($this->tempFolder.$file);
            }catch(\ErrorException $e){
                $this->log->warning("Cannot read the file ".$this->tempFolder.$file . " : " . $e->getMessage());
                $errors++;
                continue;
            }

            //in the content of the file get the line where is this $xajax->printJavascript('../commun/xajax_05/');
            $pattern = '/\$xajax->printJavascript\((.*?)\)/';
            preg_match_all($pattern, $content, $matches);

            //if matches is empty, then continue
            if(count($matches[0]) == 0) continue;

            //replace the path with the new path
            $newLine = str_replace("../commun/xajax_05/", $newPath, $matches[0][0]);
            
            //replace the line in the content
            $content = str_replace($matches[0][0], $newLine, $content);

            //save the content in the file
            file_put_contents($this->tempFolder.$file, $content);

            if($debug == true){
                $this->log->debug("file: " . $file . " - line: " . $matches[0][0] . " - newLine: " . $newLine . "\n");
            }                
        }

        restore_error_handler();

        if($errors > $totalFiles / 2){
            $this->log->error("More than 50% of the files could not be read. Please check the permissions of the folder ".$this->tempFolder);
        }
        else{
            $this->log->info("The path of xajax has been changed in ".$totalFiles." files.");
        }

    }

    public function __destruct(){
        $this->clearFolder($this->tempFolder);
    }


}
