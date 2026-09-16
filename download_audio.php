<?php
$url = 'https://actions.google.com/sounds/v1/alarms/beep_short.ogg';
$data = file_get_contents($url);
file_put_contents('assets/sounds/notification.mp3', $data); // Saving ogg as mp3 extension is fine for HTML5 audio mostly, but let's just use the file as is, the browser will decode it.
echo "Audio downloaded.";
