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


    //list of all function depecrated in php 7.2
    public $depecratedFunctions = array(
        "create_function",
        "each",
        "ereg",
        "ereg_replace",
        "eregi",
        "eregi_replace",
        "set_magic_quotes_runtime",
        "magic_quotes_runtime",
        "session_register",
        "session_unregister",
        "session_is_registered",
        "mysql_db_query",
        "mysql_escape_string",
        "mysql_list_dbs",
        "mysql_list_fields",
        "mysql_list_processes",
        "mysql_list_tables",
        "mysql_tablename",
        "mysql_db_name",
        "mysql_result",
        "mysql_list_dbs",
        "mysql_list_fields",
        "mysql_list_processes",
        "mysql_list_tables",
        "mysql_tablename",
        "mysql_db_name",
        "mysql_result",
        "mysql",
        "mysql_pconnect",
        "mysql_connect",
        "mysql_close",
        "mysql_select_db",
        "mysql_create_db",
        "mysql_drop_db",
        "mysql_query",
        "mysql_unbuffered_query",
        "mysql_db_query",
        "mysql_list_dbs",
        "mysql_list_tables",
        "mysql_list_fields",
        "mysql_list_processes",
        "mysql_error",
        "mysql_errno",
        "mysql_affected_rows",
        "mysql_insert_id",
        "mysql_result",
        "mysql_num_rows",
        "mysql_num_fields",
        "mysql_fetch_row",
        "mysql_fetch_array",
        "mysql_fetch_assoc",
        "mysql_fetch_object",
        "mysql_data_seek",
        "mysql_fetch_lengths",
        "mysql_fetch_field",
        "mysql_field_seek",
        "mysql_free_result",
        "mysql_field_name",
        "mysql_field_table",
        "mysql_field_len",
        "mysql_field_type",
        "mysql_field_flags",
        "money_format",
    );


    /* 
    * @param string $projectFolder
    * @param string $tempFolder
    * @param string $returnFolder
    */
    public function __construct(string $projectFolder, string $tempFolder, string $returnFolder, bool $debug = false)
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
        $this->returnFolder = $returnFolder;
        $this->debug = $debug;
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
    public function checkPatternAndReplace(string $file): bool{

        $content = file_get_contents($file);
        $search = 'xajax_05'; // Chaîne à rechercher
        $dirname = dirname($file) . "/";
        if ($dirname == $this->tempFolder) $replace = 'xajaxPHP7.2';
        else $replace = '../xajaxPHP7.2';
        
        
        if (strpos($content, $search) !== false) {

            $fileName = pathinfo($file, PATHINFO_FILENAME);

            if ($fileName != "use_xajax") {
                $output = preg_replace("/\\\$xajax->printJavascript\\((?:[^()]|(?R))*$search(?:[^()]|(?R))*\\)/", "\$xajax->printJavascript('$replace')", $content);   

                file_put_contents($file, $output);

                if ($this->debug) $this->log->debug("Pattern replaced in file: " . $file . "(Replace: " . $replace . ")\n");
            }
            else{
                $output = preg_replace("/\\brequire_once\\s*\\((?:[^()]|(?R))*$search(?:[^()]|(?R))*\\)/", "require_once('$replace/xajax_core/xajax.inc.php')", $content);

                file_put_contents($file, $output);

                if ($this->debug) $this->log->debug("Pattern replaced in file: " . $file . "(Replace: " . $replace . ")\n");
            }
            return true;
            
        }

        return false;      
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

            $this->checkPatternAndReplace($file);            
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

    public function checkAutoLoad():void{
        $files = $this->getAllFile("php");

        //check trought every file to find __autoload and replace it with spl_autoload_register
        foreach ($files as $file) {
            $content = file_get_contents($file);

            //find __autoload and replace it with spl_autoload_register
            $pattern = '/__autoload/';
            preg_match_all($pattern, $content, $matches);

            //if matches is empty, then continue
            if (count($matches[0]) == 0) {
                continue;
            }

            //replace the line in the content
            $content = str_replace($matches[0][0], "spl_autoload_register", $content);

            //save the content in the file
            file_put_contents($file, $content);

            if($this->debug) $this->log->debug("File: ".$file." - Search: __autoload - Replace: spl_autoload_register\n");
        }
    }

    public function checkDeprecatedFunction($function){
        $files = $this->getAllFile("php");

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $pattern = '/'.$function.'/';
            preg_match_all($pattern, $content, $matches);            

            //if matches is empty, then continue
            if (count($matches[0]) == 0) {
                continue;
            }

            $this->log->warning("The function \033[33m".$function. "\033[0m is deprecated in the file ".$file);
        }
    }

    public function checkAllDeprecatedFunctions(){
        $this->debug = false;
        foreach ($this->depecratedFunctions as $function) {
            $this->checkDeprecatedFunction($function);
        }
        $this->debug = true;

    }

    public function __destruct(){
        $this->debug = false;
        $this->clearFolder($this->tempFolder);
    }
}
