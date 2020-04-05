<?php

require 'vendor/autoload.php';

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;


$configs = include('config.php');
$startTime = microtime(true);

$hostname = $configs['host'];
$username = $configs['username'];
$password = $configs['password'];
$dbName = $configs['db_name'];
$tableName = $configs['table_name'];


$emailTo = $configs['email_to'];
$mailgunApiKey = $configs['mailgun_api_key'];
$mailContent = '';

$outputFileName = $configs['output_file_name'];
$inputFileName = $argv[1];

$reader = ReaderEntityFactory::createReaderFromFile($inputFileName);
$reader->open($inputFileName);

try {
    $conn = new PDO("mysql:host=$hostname;dbname=$dbName", $username, $password, array(
        PDO::MYSQL_ATTR_LOCAL_INFILE => true));

	$fp = fopen($outputFileName, 'w');
	
	// Iterate on all rows to select only accpted rows then write all rows in new csv file
    foreach ($reader->getSheetIterator() as $sheet) {
    	$i = 0;
    	$totalIgnoredRows = 0;
    	$totalAcceptedRecords = 0;
    	$rowIterator = $sheet->getRowIterator();

	    foreach ($rowIterator as $row) {
		    $i++;
		    if($i == 1) {continue;}		// Ignore spreadsheet header row
	    	$cells = $row->toArray();
	        
	        // Ignore row if contians an empty cell
	        if (in_array('', $cells, true)){
	        	$totalIgnoredRows++;
	        	continue;
	        }
	        $totalAcceptedRecords++;
	        fputcsv($fp, $cells);
	    }
	}
	
	fclose($fp);
	
	// Using LOAD DATA INFILE to import the csv file into mysql
	$conn->exec("LOAD DATA LOCAL INFILE '" . $outputFileName . "'  
	    	INTO TABLE " . $tableName . " 
	    	FIELDS TERMINATED BY ','  
	    	ENCLOSED BY '\"' 
	    	LINES TERMINATED BY '\n' 
	    	(@col1,@col2,@col3,@col4) 
	    	set first_name=@col1,second_name=@col2,family_name=@col3,uid=@col4");

	$reader->close();

    echo 'New records created successfully' . PHP_EOL;
    }
catch(PDOException $e)
    {
    echo "Error: " . $e->getMessage();
    }
$conn = null;

$endTime = microtime(true);
$executionTime = ($endTime - $startTime);


$mailContent .= 'Total accpted records = ' . $totalAcceptedRecords . PHP_EOL;
$mailContent .= 'Total rejected records = ' . $totalIgnoredRows . PHP_EOL;
$mailContent .= 'Script execution time = ' . $executionTime . ' secs' . PHP_EOL;
$mailContent .= 'Peak memory usage = ' . memory_get_peak_usage() / 1024 / 1024 . ' MB' . PHP_EOL;


// Send mail
$mailCommand = <<< MAIL
curl -s --user 'api:$mailgunApiKey' \
    https://api.mailgun.net/v3/sandbox31fade7d548f4bf486fb8cd7c975897a.mailgun.org/messages \
        -F from='Xlsx to MySQL <postmaster@sandbox31fade7d548f4bf486fb8cd7c975897a.mailgun.org>' \
        -F to='Recepient <$emailTo>' \
        -F subject='Xlsx to MySQL script execution finished successfully' \
        -F text='$mailContent'

MAIL;

print $mailContent;
exec($mailCommand);