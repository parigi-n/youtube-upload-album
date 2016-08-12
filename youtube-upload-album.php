<?php

if ($argc < 4)
{
	echo "Usage: php path/to/youtube-upload-album.php <folder> picture_filename playlist_name\n";
	exit(-1);
}

require_once(dirname(__FILE__) . '/getID3/getid3/getid3.php');

shell_exec("convert $argv[2] -interpolative-resize 1920x1080 -background black -gravity center -extent 1920x1080 /tmp/tmp_img");

if ($handle = opendir($argv[1]))
{
	$getID3 = new getID3;
	
    while (false !== ($entry = readdir($handle)))
    {
        if ($entry != "." && $entry != "..")
        {
            if ($file_parts = pathinfo($entry))
            {
                if (array_key_exists("extension", $file_parts) && $file_parts["extension"] == "mp3")
                {
                    $files[] = $entry;
                }
            }
        }
    }
    closedir($handle);
    natsort($files);
    foreach ($files as $entry)
    {
        $ThisFileInfo = $getID3->analyze(realpath($entry));
        getid3_lib::CopyTagsToComments($ThisFileInfo);
        $description = "";
        
        //Album
        if (!empty($ThisFileInfo['comments']['album']))
        {
            $ThisFileInfo['comments']['album'] = array_map('ucfirst', $ThisFileInfo['comments']['album']);
            $album = implode(' & ', $ThisFileInfo['comments']['album']);
            $description .= "Album : " . $album . "\n";
        }
        
        //Track
        if (!empty($ThisFileInfo['comments']['track']))
        {
            $track = implode(' & ', $ThisFileInfo['comments']['track']);
            $description .= "Track : " . $track . "\n";
        }
        else if (!empty($ThisFileInfo['id3v1']['track']))
        {
            $track = implode(' & ', $ThisFileInfo['id3v1']['track']);
            $description .= "Track : " . $track . "\n";
        }
        
        //Title
        if (!empty($ThisFileInfo['comments']['title']))
        {
            $ThisFileInfo['comments']['title'] = array_map('ucfirst', $ThisFileInfo['comments']['title']);
            $title = implode(' & ', $ThisFileInfo['comments']['title']);
            $description .= "Title : " . $title . "\n";
        }
        
        //Artist
        if (!empty($ThisFileInfo['comments']['artist']))
        {
            $ThisFileInfo['comments']['artist'] = array_map('ucfirst', $ThisFileInfo['comments']['artist']);
            $artist = ucfirst(implode(' & ', $ThisFileInfo['comments']['artist']));
            $description .= "Artist : " . $artist . "\n";
        }
        
        //year
        if (!empty($ThisFileInfo['comments']['year']))
        {
            $year = implode(', ', $ThisFileInfo['comments']['year']);
            $description .= "Year : " . $year . "\n";
        }
        
        //genre
        if (!empty($ThisFileInfo['comments']['genre']))
        {
            $genre = implode(', ', $ThisFileInfo['comments']['genre']);
            $description .= "Genre : " . $genre . "\n";
        }
        
        //Copyright
        if (!empty($ThisFileInfo['comments']['copyright']))
        {
            $destription .= "Copyright : " . implode(', ', $ThisFileInfo['comments']['copyright']) . "\n";
        }
        
        if (!empty($ThisFileInfo['audio']['bitrate']))
        {
            $destription .= "Original bitrate : " . round($ThisFileInfo['audio']['bitrate'] / 1000) . " kbps" . "\n";
        }
        
        $description .= "\nDisclaimer: I do not own the song or the picture. This is for entertainment purposes only. All audio recordings are property of the artist(s), management, and/or music publishing companies. No copyright infringement is intended. Copyright Disclaimer Under Section 107 of the Copyright Act 1976, allowance is made for 'fair use' for purpose.\n";
        $description .= "\nAutomatic upload made with https://github.com/parigi-n/youtube-upload-album";
        
        shell_exec("ffmpeg -y -loop 1 -framerate 1 -i /tmp/tmp_img -i \"$entry\" -c:a copy -shortest -pix_fmt yuv420p /tmp/tmp.avi");
        shell_exec("youtube-upload \
--title=\"$artist - $title\"                   \
--description=\"$description\"                 \
--category=Music                               \
--tags=\"$album, $artist, $title, $genre\"     \
--playlist \"$argv[3]\"                        \
/tmp/tmp.avi");
    }
    unlink("tmp/tmp_img");
    unlink("tmp/tmp.avi");
}
?>