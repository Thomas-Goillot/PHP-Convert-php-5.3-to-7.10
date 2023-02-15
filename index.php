<?php
//==================================================================================================
//================================= CONFIGURATION & QUESTIONS ======================================
//==================================================================================================

require(__DIR__ . '/lx-utils/vendor/autoload.php');

use Lx\Utils\CodeCleanUp\CodeCleanUp;

include 'config.php';
include 'logColor.php';
include 'convert.php';

$log = new Log();


if(isset($argv[1])){
    $projectFolder = $argv[1];
    $projectFolder = str_replace(" ", "", $projectFolder);
} else {
    $log->ask("Enter the path of the project folder: ");
    $projectFolder = readline();
    $projectFolder = str_replace(" ", "", $projectFolder);
}

$log->info("The project folder is: $projectFolder");

if(!is_dir($projectFolder)){
    $log->error("The project folder does not exist");
    exit;
}

//==================================================================================================
//========================================= INIT ===================================================
//==================================================================================================

$convert = new Convert($projectFolder, $tempFolder, $returnFolder, TRUE);

//Clear all working folders
$convert->clearFolder($returnFolder);
$convert->clearFolder($tempFolder);

//Copy files from project folder to temp folder
if($convert->copyToFolder()){
    $log->success("Files copied to temp folder");
} else {
    $log->error("Error copying files to temp folder");
    exit;
}

//==================================================================================================
//======================================= CONSTRUCTOR ==============================================
//==================================================================================================

//Get all files with class
$filesWithClass = $convert->getAllFilesWithClass();

//Replace constructor name with __construct
if($convert->debug) $log->debug("The following files have been edited: \n");
foreach ($filesWithClass as $file) {
    $convert->replaceConstructor($file);
    //if($convert->debug) $log->debug("- ".$file);
}

//Print all files edited
if(count($filesWithClass) > 0){
    $log->success("".count($filesWithClass)." files edited with new constructor method");
} else {
    $log->warning("No files have been edited for the new constructor method");
}


//==================================================================================================
//======================================= AUTOLOAD =================================================
//==================================================================================================

$convert->checkAutoLoad();


//==================================================================================================
//======================================= CODE CLEANUP =============================================
//==================================================================================================

$log->ask("Do you want to run the code clean up? (y/n) (This may cause some bug):");
readline_completion_function(function () {
    $array = array('y', 'n');
    return $array;
});
if(readline() == "y"){
    $result = (new CodeCleanUp())
        ->addFilePath($tempFolder)
        ->addFileExtension('php')
        ->addTask(CodeCleanUp::TASK_QUOTE_UNDEFINED_CONSTANTS_IN_SQUARE_BRACKETS)
        ->run();

    $filesChanged = $result->filesChanged;

    if ($convert->debug) {
        if (count($filesChanged) > 0) {
            $log->debug("The following files have been edited: ");
            foreach ($filesChanged as $file) {
                $log->debug("- " . $file . "\n");
            }
        }
    }

    if (count($filesChanged) > 0) {
        $log->success("" . count($filesChanged) . " files edited to avoid constant errors ");
    } else {
        $log->success("No files have been edited (const change)");
    }

    $filesErrors = $result->errors;

    if ($convert->debug) {
        foreach ($filesErrors as $file) {
            $log->error("- " . $file . " might contained errors");
        }
    }

    if (count($filesErrors) > 0) {
        $log->error("Some files have not been edited and might contained errors");
    }
}


//==================================================================================================
//==================================== DEPRECATED FUNCTION =========================================
//==================================================================================================

$log->info("Checking for deprecated functions...");
$convert->checkAllDeprecatedFunctions();


//==================================================================================================
//=========================================== XAJAX ================================================
//==================================================================================================

$filesWithXajax = $convert->detectXajax();

if($filesWithXajax){
    $log->info("Xajax detected in the project");
    $log->ask("Do you want to add xajax for PHP 7.2 to the project? (y/n):  ");

    readline_completion_function(function () {
        $array = array('y', 'n');
        return $array;
    });

    if(readline() == "y"){
        if($convert->copyToFolder($xajaxFolder, $tempFolder)){
            $log->success("Xajax has been added to the project");
        } else {
            $log->error("Xajax has not been added to the project");
        }
    }

    readline_completion_function(function () {
        $array = array('y', 'n');
        return $array;
    });
    $log->ask("Do you want to change the path of xajax path in the project? (y/n): ");
    if(readline() == "y"){
        $convert->changeXajaxPath();
    }

} else {
    $log->info("Xajax not detected in the project");
}

//==================================================================================================
//=================================== COPY TO RETURN FOLDER ========================================
//==================================================================================================

$log->info("The project is being converted... (This may take a few minutes)");


//create a folder with the name of the project
$projectName = basename($projectFolder);
$projectDir = "C:/wamp/www/$projectName-converted";
mkdir($projectDir);
$convert->copyToFolder($tempFolder, $projectDir);



unset($convert);

$log->success("The project has been converted successfully (You can find the converted project in the return folder)");
$log->warning("It is recommended to check the project before using it in production");

echo "http://localhost:81/$projectName-converted";
?>