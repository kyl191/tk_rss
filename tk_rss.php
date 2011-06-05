<?php

// RSS feed generator for TwoKinds

// init section
$files = array();
// Set the feed admin contact email
$adminemail = "2kindsrss-nospam@nospam.kyl191.net"; // Set it to my address, though it should be changed if necessary


// debug mode - shows variables, and prints plain text to the browser if set to true
$debug = false; 

// Force debug if requested, otherwise, suppress debug details
if (isset($_GET["debug"])){
    $debug=true;
    echo "<b>Warning! Debug mode is on!</b><br />\n";
} else {
    $debug=false;
}

// Set default directory 
// Usually a good idea to make it relative
$dir = "images/";

// End of Init Section

// Go through the directory and push the filenames into $files
// Try to open a handle to the directory specified in $dir
if ($handle = @ opendir($dir)) {
    if (($debug)&&($handle)) {$images_exist=true;}
    
    //while there are still files in the directory unread, do...
    while (($file = readdir($handle))!== false) {
        if (isset($_GET['files'])&&($debug)){echo "$file<br />\n";}
        
        // Only add files matching a certain criteria to the 'files' array
        // To ignore '.' and '..', add ($file != "." && $file != "..") 
        // The first 8 characters of the filename are numbers and the file extension is "jpg"
        if ((is_numeric(substr($file,0,8))) && (strcasecmp(substr($file,-4),".jpg") == 0) && (strcasecmp(substr($file,(strrpos($file, '.') - 2),2),"nt") != 0 ) ) {
            array_push ($files, $file);
        }
    }
    //close the directory reference
    closedir($handle);

    // Sort the filenames in reverse order
    // This works if your files are named according to date.
    rsort($files);
}

// If the open fails, show a warning.
// Ask the user to mail the script admin and tell him
// To-Do: Consider adding a description of what went wrong to the error message
else {echo "Sorry, something went wrong. Email mailto:" .$adminemail. " and tell him.\n";}

// Debug section - Print the number of files, location of script and script directory if debug mode is enabled
if ($debug) {
    $num_of_files=(string)(count($files));
    $scriptloc=(string)$_SERVER['SCRIPT_FILENAME'];
    
    //Show directory data if requested
    if (isset($_GET["dir_info"])){
        echo "<br /><b>Directory information:</b><br />\n";
        if ($images_exist) {
            echo "Total files in directory: $num_of_files<br />\n";
        } else {
            echo "The images/ folder <b>doesn't exist!</b><br />\n";
        }
        echo "Script location = $scriptloc<br />\n";
        echo "Real path = ".realpath($_SERVER['SCRIPT_FILENAME']);
    }
}

if (isset($_GET["name"])){
	$subtitle = "Who's awesome? You're awesome, " . $_GET["name"] . "!";
} else {
	$subtitle = "Comic by Tom Fischbach";
}

// RSS feed header - Outputs the RSS feed formatting headers, but only if debug mode is turned off - which is the normal status anyway though...
if (!$debug) {header("Content-Type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\">
\t<title>TwoKinds</title>
\t<link href=\"http://2kinds.com/\" />
\t<author>
\t\t<name>Tom Fischbach</name>
\t\t<email>twokinds-spam@gmail.com</email>
\t\t<uri>http://2kinds.com</uri>
\t</author>
\t<contributor>
\t\t<name>kyl191</name>
\t\t<email>2kindsrss-spam@nospam.kyl191.net</email>
\t\t<uri>http://kyl191.net</uri>
\t</contributor>
\t<id>http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."</id>
\t<subtitle>$subtitle</subtitle>
\t<rights>Comic copyright Tom Fischbach</rights>
\t<generator version=\"0.1b\">Custom generator for Tom Fischbach (2kinds.com)</generator>
\t<link rel=\"self\" href=\"http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."\"  type=\"application/rss+xml\" />\n";}

// Flush the stream to start sending the feed headers to the client
flush();

// RSS feed items - Outputs the individual RSS feed items (Links to the comics)
// Set the number of comics to show - default is 3
// Limit the max number of comic links to 25 - Seriously don't need that many more
if (isset($_GET['number'])&&($_GET['number']<26)){
    $max_item_counter = $_GET['number'];
} else {
    $max_item_counter = 3;
}

// Debug mode - show the item detail
if (($debug)&&(isset($_GET['items']))){
    echo "<br /><br /><b>Item info:</b> \n";
}

$item_counter = 0; // Start at 0 because the first comic will just direct to the front page.
// $file_count = count($files)+2; // Hack due to the fact that not all comics are stored in the images/ folder

foreach ($files as $filename) {
    // Die if we've already listed the number of comics specified - default case will die after 3, because 3 > 3-1
	// We have to subtract 1 from max_item_counter because item_counter is starting from 0
    if (abs($item_counter) > $max_item_counter-1) break;
    // Convert the filename of the comic into a unix timestamp, then converts the timestamp into a date
    // Used in the title of the rss feed item
    // Format: 4-digit year, 2 digit month (with leading zeros), 2 digit day (with leading zeros)
    $date = date("Y-m-d", strtotime(substr($filename,0,8))); 
    // Use the time the file was modified (i.e. uploaded) as the publish date
    // Note: Using the same directory as declared previously
    $pub = date(DATE_ATOM, filemtime($dir.$filename));
    // Set & write the last update time of the feed as the time the last comic was uploaded
    // Only do this for the first item
    if (($item_counter == 0)&&(!$debug)){echo "	<updated>$pub</updated>\n\n";}
    // Page of comic in archive is equal to the number of files minus the counter of the current item
    //$page = (string)($file_count - $item_counter);
    // We're using negative indexes now, so take the negative of the item count
    $page = $item_counter;
    // If item details are requested in debug mode...
     if (($debug)&&(isset($_GET['items']))){
        echo "<br />Atom feed entry $item_counter<br />
        date=$date<br />
        pub=$pub<br />
        page=$page<br />
        filename=$filename<br />";
    }
    // Code to write the individual entries in the RSS feed to the client
    if (!$debug){
        // Print the title, link, guid and pubdate for each feed item
        echo "\t<entry>
        <title>Comic for $date</title>
        <id>$date</id>\n";

        // If it's the first item, force the link to go to the front page, otherwise, link to the comic in the archive
        if ($item_counter == 0) {
            echo "\t<content type=\"html\">Comic for $date is located at &lt;a href=\"http://".$_SERVER['SERVER_NAME']."/\"&gt;http://".$_SERVER['SERVER_NAME']."/"."&lt;/a&gt;</content>
        <link href=\"http://".$_SERVER['SERVER_NAME']."/?".$date."\" rel=\"alternate\" hreflang=\"en-us\" title=\"Comic for $date\"/>\n";
        } else {
            echo "\t<content type=\"html\">Comic for $date is located at &lt;a href=\"http://twokinds.net/?p=$page\"&gt;http://twokinds.net/?p=$page&lt;/a&gt;</content>
        <link href=\"http://twokinds.net/?p=$page\" rel=\"alternate\" hreflang=\"en-us\" title=\"Comic for $date\"/>\n";
        }

        // Add the file as an enclosure
        // To-Do: Find a way to change the file MIME type programatically
        // To-Do: Find a way to locate the web path that the script is executing in
            if(isset($_GET['show_image'])&&(!$debug)){
                echo "\t\t<link rel=\"enclosure\" href=\"http://".$_SERVER['SERVER_NAME']."/images/".$filename."\" length=\"".filesize($dir.$filename)."\" type=\"image/jpeg\" />\n";
            }
        // Set the publication date of the entry to the file modified time
        echo "\t<updated>$pub</updated>\n\t</entry>\n\n";
    }
    //Added a feed item, increment itemcounter by 1
    $item_counter--; 
}
?>