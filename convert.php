<?php

class Convert
{

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
    * @param Log $log
    */
    public $log;

    /* 
    * @param bool $debug
    */
    public $debug = true;


    /* 
    * @param string $projectFolder
    * @param string $tempFolder
    * @param string $returnFolder
    */
    public function __construct(string $projectFolder, string $tempFolder, string $returnFolder)
    {

        $this->log = new Log();

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        if (!is_dir($returnFolder)) {
            mkdir($returnFolder, 0777, true);
        }

        $this->projectFolder = $projectFolder;
        $this->tempFolder = $tempFolder;
    }

    /* 
    * @param string $filter
    * @return array
    */
    public function getAllFile(string $filter = ""): array{
        $files = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tempFolder));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                array_push($files, $file->getPathname());
            }
        }

        if($filter !== ""){
            $files = array_filter($files, function ($file) use ($filter) {
                return strpos($file, $filter) !== false;
            });
        }

        if ($filter !== "") $filter = " with the filter: \"" . $filter."\"";
        if($this->debug) $this->log->debug("Files found: ".count($files).$filter."\n");

        return $files;
    }

    /* 
    * @param string $src
    * @param string $dest
    * @return void
    */
    public function copyToFolder(string $src = "", string $dest = ""): bool{

        if ($src === "") $src = $this->projectFolder;
        if ($dest === "") $dest = $this->tempFolder;

        $ignoreFileExtensions = array("pdf", "xls", "xlsx", "csv");

        foreach (scandir($src) as $file) {

            if ($file != "." && $file != "..") {
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                if (!in_array($fileExtension, $ignoreFileExtensions)) {
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

        if ($src === "") $src = $this->tempFolder;

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

        if($this->debug) $this->log->debug("Folder cleared: ".$src."\n");

    }

    /* 
    * @return array
    */
    public function getAllFilesWithClass(): array{

        $files = $this->getAllFile("php");     

        $filesWithClass = array();
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $pattern = '/class\s+(\w+)/';
            preg_match_all($pattern, $content, $matches);
            if (count($matches[1]) > 0) {
                array_push($filesWithClass, $file);
            }
        }

        if($this->debug) $this->log->debug("Files with class found: ".count($filesWithClass)."\n");

        return $filesWithClass;
    }

    /* 
    * @param string $file
    * @return void
    */
    public function replaceConstructor(string $file): void{
        $content = file_get_contents($file);

        //get the name of the class
        $pattern = '/class\s+(\w+)/';
        preg_match_all($pattern, $content, $matches);

        //go trough all the matches

        foreach($matches[1] as $match){
            $pattern = '/function\s+' . $match . '\s*\(/';
            $content = preg_replace($pattern, "function __construct(", $content);
            if ($this->debug) $this->log->debug("Constructor replaced in file: " . $file . " with classname " . $match . "\n");
        }

        //save the content in the file
        file_put_contents($file, $content);

    }


    public function getAllFilesWithUndefinedConstant(){
            
            $files = $this->getAllFile();     
    
            $filesWithUndefinedConstant = array();

            //check if there is something like $var[CONSTANT] in the file

            foreach ($files as $file) {
                $content = file_get_contents($file);
                $pattern = '/\$\w+\[.*\]/';
                preg_match_all($pattern, $content, $matches);
                if (count($matches[0]) > 0) {
                    array_push($filesWithUndefinedConstant, $file);
                }
            }

            if($this->debug) $this->log->debug("Files with undefined constant found: ".count($filesWithUndefinedConstant)."\n");
    
            return $filesWithUndefinedConstant;
    }

    public function replaceUndefinedConstant($file){
        $content = file_get_contents($file);

        /* 
        cho "<td class='colonne'>".$row[libelle]."</td>";
        $liste_bis   = $dossier->verif_alerte5_bis($row[id_caisse]);
        $row[libelle] = $liste_bis[0]['nbre']; 
        $tab_caisse[$row[id_caisse]]['id_caisse'] = $row['id_caisse'];

        //fais moi une regex qui selectionne tout [CONSTANT] et qui remplace par ['CONSTANT'] mais qui ne remplace pas si [$*var*] ou [$*var*['CONSTANT']]
        */
        $pattern = '/\$\w+\[^$.*\]/';

        $content = preg_replace($pattern, "['$0']", $content);
        if ($this->debug) $this->log->debug("Undefined constant replaced in file: " . $file . "\n");
        
        file_put_contents($file, $content);
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
            if (strpos($fileName, "xajax") !== false) {
                return true;
            }

            //then check the content
            $content = file_get_contents($file);
            $pattern = '/xajax/';
            preg_match_all($pattern, $content, $matches);
            if (count($matches[0]) > 0) {
                return true;
            }
        }

        return false;
    }

    /* 
    * @param string $file
    * @param string $search
    * @param string $pattern
    * @param string $replace
    * @param string $content = ""
    * @return string
    */
    public function checkPatternAndReplace(string $file, string $search, string $pattern, string $replace, string $content = ""): string{
        //in the content of the file get the line where is this $xajax->printJavascript('../commun/xajax_05/');
        preg_match_all($pattern, $content, $matches);

        //if matches is empty, then continue
        if (count($matches[0]) == 0) {
            return "";
        }

        //replace the path with the new path
        $newLine = str_replace($search, $replace, $matches[0][0]);

        //replace the line in the content
        $content = str_replace($matches[0][0], $newLine, $content);

        //save the content in the file
        file_put_contents($file, $content);

        if($this->debug) $this->log->debug("File: ".$file." - Search: ".$search." - Replace: ".$replace."\n");

        return $matches[0][0];
    }

    /* 
    * @return void
    */
    public function changeXajaxPath(): void{

        $files = $this->getAllFile("php");
        $totalFiles = count($files);
        $errors = 0;

        $newPath = "xajaxPHP7.2/";

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });

        foreach ($files as $file) {
            try {
                $content = file_get_contents($file);
            } catch (\ErrorException $e) {
                if ($this->debug) $this->log->warning("Cannot read the file " . $file . " : " . $e->getMessage());
                $errors++;
                continue;
            }

            $matches = "";
            $matches = $this->checkPatternAndReplace($file, "../commun/xajax_05/", '/\$xajax->printJavascript\((.*?)\)/', $newPath, $content);

            if ($this->debug == true && $matches != "") {
                $this->log->debug("file: " . $file . " - line: " . $matches . "\n");
            }


            //get the file name
            $fileName = pathinfo($file, PATHINFO_FILENAME);

            if ($fileName == "use_xajax") {
                $matches = "";
                $newPath = "" . $newPath . "xajax_core/xajax.inc.php";
                //find require_once ("../../commun/xajax_05/xajax_core/xajax.inc.php"); and replace it with require_once ("xajaxPHP7.2/xajax_core/xajax.inc.php");
                $matches = $this->checkPatternAndReplace($file, "../../commun/xajax_05/xajax_core/xajax.inc.php", '/require_once\s*\((.*?)\)/', $newPath , $content);

                if($this->debug == true && $matches != ""){
                    $this->log->debug("file: ".$file." - line: ".$matches."\n");
                }

                if($matches == ""){
                    $this->log->error("The file ".$file." has not been changed. Please check the path of xajax in this file.");
                    $this->log->debug("you should change the path of the require to ".$newPath."xajax_core/xajax.inc.php");
                    exit;
                }

            }           
        }

        restore_error_handler();

        if ($errors > $totalFiles / 2) {
            $this->log->error("More than 50% of the files could not be read. Please check the permissions of the folder " . $this->tempFolder);
            $this->log->error($errors . " files could not be read !");
            $this->log->error("The path of xajax has not been changed.");
            exit;
        } else if ($errors == 0) {
            $this->log->success("All files were read successfully");
        } else {
            $this->log->info("The path of xajax has been checked in " . $totalFiles . " files and " . $errors . " files could not be read.");
        }
    }

    public function __destruct(){
        $this->debug = false;
        $this->clearFolder($this->tempFolder);
    }
}
