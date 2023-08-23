<?php
// Trusted Domain protected by Cloudflare Acess
$webhost = 'localhost';
// Query page file name
$querypage = 'index.php';
// MySQL Database
$dbhost = 'localhost:3306';
$dbuser = '';
$dbpass = '';
$dbname = '';
// User table (username => password)
$users = array('superadmin' => '123456',
    'testuser' => '12345678',
    '00000000' => '00000000',
    '23120000' => '23120000',
    '23120001' => '23120001',
);
// Example privileges
$privall = array('stuscorelst', 'stuscorelstrng', 'classscore', 'sturank', 'classscorestat', 'coursestat',
 'coursename', 'teacher', 'teacherstat', 'stuinfo', 'datainfo', 'stugpa', 'classnamelist','stuinfolst',
 'vodlist','searchvod','classroom','stustatus');
$restricted = array('sturank', 'coursename', 'teacher', 'coursestat', 'teacherstat', 'ranklimit', 'stuinfo', 'classnamelist','stuinfolst');
$ownscore = array('stuscorelst', 'stuscorelstrng','stugpa' , 'ownscore');
$coursep = array('teacher', 'coursename');
$ban = array('');
$vod = array('vodlist','searchvod','classroom');

$usermail = array('admin' => 'superadmin',
    'test' => 'testuser',

);

// User privilege
$userpriv = array(
    'superadmin' => $privall,
    'testuser' => array_merge($ownscore, $restricted,$vod),
    '00000000' => array_merge($ownscore, $restricted,$vod),
    '23120000' => array_merge($ownscore, $restricted,$vod),
    '23120001' => array_merge($ownscore, $restricted,$vod),
    'guest' => $ban,
);

// Temp Grant privilege
date_default_timezone_set('PRC');
$now = time();
$tmpgrantstart = '2023-01-01 15:00:00';
$tmpgrantend = '2023-09-01 08:00:00';
$tmpgrantusers = array(
    'testuser' => $ban,
);
$grantexpired = true;
if($now < strtotime($tmpgrantend) && $now >= strtotime($tmpgrantstart)){
    foreach($tmpgrantusers as $k => $v){
        $userpriv[$k] = $v;
    }
    $grantexpired = false;
}




$welcomebanner = 'Welcome to the Student Management System!';

$disclaimer = '<br /><br />This system is used for recreational purposes.<br />';

$otherinfo = '<br />Open source on Github!<br />';