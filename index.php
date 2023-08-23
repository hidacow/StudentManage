<?php
require 'auths.php';
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}
$realm = 'Login';
$data = array();
global $querypage;

function legacylogin()
{
    global $realm;
    global $users;
    global $data;
    if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Digest realm="' . $realm .
            '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) . '"');

        die('You are not authorized :(');
    }
    // analyze the PHP_AUTH_DIGEST variable
    if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || !isset($users[$data['username']])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Digest realm="' . $realm .
            '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) . '"');
        echo 'Wrong Credentials!';
        echo '<br /><br />If you just logout or come from another page, try this:';
        echo '<br /><a href ="https://@' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . '">Retry Login</a>';
        die();
    }

    // generate the valid response
    $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
    $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
    $valid_response = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);

    if ($data['response'] != $valid_response) {
        header('HTTP/1.1 401 Unauthorized');
        echo 'Wrong Token! This is normal if you just solved a captcha.<br / >Refresh and try again.';
        echo '<br /><br />If you just logout or come from another page, try this:';
        echo '<br /><a href ="https://@' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . '">Retry Login</a>';
        die();
    }
}

// Solve browser issues
if (@$_GET['logout'] == 1) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Logout OK.<br />';
    echo "<a href=\"".$querypage."\" rel=\"noopener noreferrer\">Goto Login</a><br />";
    echo '<br /><br />Logout not success? It happens as browsers handle our auth differently.<br />Try the following:';
    echo '<br /><a href="'.$querypage.'?logout=2" rel="noopener noreferrer">logout2</a>';
    echo '<br />Also try clean your browser cache or use InPrivate mode.';
    die();
}
if (@$_GET['logout'] == 2) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="' . $realm .
        '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) . '"');
    header('Location: https://log:out@' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
    echo 'Logout OK<br />';
    echo "<a href=\"".$querypage."\" rel=\"noopener noreferrer\">Goto Login</a><br />";
    echo '<br /><br />Logout not success? It happens as browsers handle our auth differently.<br />Try the following:';
    echo '<br /><a href="'.$querypage.'?logout=1" rel="noopener noreferrer">logout1</a>';
    echo '<br />Also try clean your browser cache or use InPrivate mode.';
    die();
}

// Example OAuth Config: Cloudflare Access
$cfAuth = $_COOKIE['CF_Authorization'] ?? '';
$headers = getallheaders();

$cfUsername = $headers['cf-access-authenticated-user-email'] ?? '';
if (empty($cfAuth) || empty($cfUsername) || $_SERVER['SERVER_NAME'] !== $webhost) {
    //header('HTTP/1.0 401 Unauthorized');
    //die('You are not authorized by Cloudflare :(');
    legacylogin();
} else {
    $username = substr($cfUsername, 0, strpos($cfUsername, '@'));
    if (!array_key_exists($username, $usermail) && !array_key_exists($cfUsername, $usermail)) {

        echo ('Your email are not registered. <a href="cdn-cgi/access/logout" rel="noopener noreferrer">Change Account</a><br />Please contact the administrator to connect your account.<br /><br />');
        legacylogin();
    } else {
        $data['username'] = $usermail[$cfUsername] ?? $usermail[$username];
    }
}





// ok, valid username & password
echo 'You are logged in as: ' . $data['username'] . "<br /><a href=\"".$querypage."\" rel=\"noopener noreferrer\">Home</a>&nbsp&nbsp<a href=\"" . (@$data['response'] ? $querypage.'?logout=1' : 'cdn-cgi/access/logout') . "\" rel=\"noopener noreferrer\">logout</a><br />";
if (array_key_exists($data['username'], $tmpgrantusers) && !$grantexpired) {
    echo 'Your temporary privileges override expires on ' . $tmpgrantend . "<br /><br />";
}

$method = @$_GET['q'];

$startTime = microtime(true);

switch (@$method) {
    case "":
        echo $welcomebanner;
        echo $disclaimer;
        echo '<a href="'.$querypage.'?q=mypriv">See my privileges</a><br />';
        echo '<a href="'.$querypage.'?q=usage">See usage</a><br />';
        echo $otherinfo;
        die();
    case "mypriv":
        die("Your Privileges: <br />" . implode("<br />", $userpriv[$data['username']]) . "<br />");
    case "usage":
        showusage();
        break;
    case "stuscorelst":
        checkpriv($method);
        @$term = $_GET['term'];
        @$stuno = $_GET['stuno'];
        if ($term == '')
            $term = null;
        else
            checkterm($term);
        checkstuno($stuno);
        stuscorelst($term, $stuno);
        break;
    case "stuscorelstrng":
        checkpriv($method);
        @$termfrom = $_GET['termfrom'];
        @$termto = $_GET['termto'];
        @$stuno = $_GET['stuno'];
        checkterm($termfrom);
        checkterm($termto);
        checkstuno($stuno);
        stuscorelstrng($termfrom, $termto, $stuno);
        break;
    case "sturank":
        checkpriv($method);
        @$termfrom = $_GET['termfrom'];
        @$termto = $_GET['termto'];
        @$grade = $_GET['grade'];
        @$college = $_GET['college'];
        @$major = $_GET['major'];
        @$submajor = $_GET['submajor'];
        @$status = $_GET['status'];
        @$round = $_GET['round'];
        checkterm($termfrom);
        checkterm($termto);
        $whitelist = array("19", "20", "21", "22", "23");
        if (!in_array($grade, $whitelist) && $data['username'] != 'superadmin') {
            die('unsupported grade');
        }
        if ($major == '' && $college == '' && $data['username'] != 'superadmin') {
            echo 'specify major or college or both<br />';
            die('wrong param');
        }
        $round = ($round == 'on');
        sturank($termfrom, $termto, $grade, $college, $major, $submajor, $status, $round);
        break;
    case "classscore":
        checkpriv($method);
        @$classid = $_GET['classid'];
        @$order = $_GET['order'];
        if (@$classid == '') {
            die('wrong param');
        }
        $whitelist = array("gpa", "mid", "fin", "score", "", "scoremid", "scorefin");
        if (!in_array($order, $whitelist)) {
            die('unsupported order');
        }
        if ($order == "mid") {
            $order = "scoremid";
        }
        if ($order == "fin") {
            $order = "scorefin";
        }
        classscore($classid, $order);
        break;
    case "stuinfolst":
        checkpriv($method);
        @$grade = $_GET['grade'];
        @$college = $_GET['college'];
        @$major = $_GET['major'];
        @$submajor = $_GET['submajor'];
        @$status = $_GET['status'];
        $whitelist = array("19", "20", "21", "22", "23");
        if (!in_array($grade, $whitelist) && $data['username'] != 'superadmin') {
            die('unsupported grade');
        }
        if ($major == '' && $college == '' && $data['username'] != 'superadmin') {
            echo 'specify major or college or both<br />';
            die('wrong param');
        }
        stuinfolst($grade, $college, $major, $submajor);
        break;
    case "coursename":
        checkpriv($method);
        @$term = $_GET['term'];
        @$coursename = $_GET['coursename'];
        if (@$coursename == '') {
            die('wrong param');
        }
        if (@$term == '') {
            @$termfrom = $_GET['termfrom'];
            @$termto = $_GET['termto'];
            checkterm($termfrom);
            checkterm($termto);
            coursenamerng($termfrom, $termto, $coursename);
        } else {
            checkterm($term);
            coursename($term, $coursename);
        }
        break;
    case "teacher":
        checkpriv($method);
        @$term = $_GET['term'];
        @$teacher = $_GET['teacher'];
        if (@$teacher == '') {
            die('wrong param');
        }
        if (@$term == '') {
            @$termfrom = $_GET['termfrom'];
            @$termto = $_GET['termto'];
            checkterm($termfrom);
            checkterm($termto);
            teacherrng($termfrom, $termto, $teacher);
        } else {
            checkterm($term);
            teacher($term, $teacher);
        }
        break;
    case "classscorestat":
        checkpriv($method);
        @$classid = $_GET['classid'];
        if (@$classid == '') {
            die('wrong param');
        }
        classscorestat($classid);
        break;
    case "classnamelist":
        checkpriv($method);
        @$classid = $_GET['classid'];
        if (@$classid == '') {
            die('wrong param');
        }
        classnamelist($classid);
        break;
    case "coursestat":
        checkpriv($method);
        @$term = $_GET['term'];
        @$courseno = $_GET['courseno'];
        checkterm($term);
        checkcno($courseno);
        coursestat($term, $courseno);
        break;
    case "teacherstat":
        checkpriv($method);
        @$term = $_GET['term'];
        @$teacher = $_GET['teacher'];
        checkterm($term);
        if (@$teacher == '') {
            die('wrong param');
        }
        teacherstat($term, $teacher);
        break;
    case "stuinfo":
        checkpriv($method);
        @$name = $_GET['name'];
        if (@$name == '') {
            @$stuno = $_GET['stuno'];
            checkstuno($stuno);
            stuinfonum($stuno);
        } else {
            stuinfoname($name);
        }

        break;
    case "datainfo":
        checkpriv($method);
        datainfo();
        break;
    case "stugpa":
        checkpriv($method);
        @$stuno = $_GET['stuno'];
        checkstuno($stuno);
        stugpa($stuno);
        break;
    case "vodlist":
        checkpriv($method);
        @$classid = $_GET['classid'];
        if (@$classid == '') {
            die('wrong param');
        }
        vodlist($classid);
        break;
    case "searchvod":
        checkpriv($method);
        @$begintime = $_GET['begintime'];
        @$endtime = $_GET['endtime'];
        @$classroom = $_GET['classroom'];
        $res = checktime($begintime, $endtime);
        searchvod($res[0], $res[1], $classroom);
        break;
    case "classroom":
        checkpriv($method);
        classroom();
        break;

    default: //没有传或者错误的method默认返回
        die('wrong method');
}

$endTime = microtime(true);
$runTime = sprintf("%.1f", ($endTime-$startTime)*1000) . ' ms';

echo '<br />Process Time: ' . $runTime . '<br />';
echo $disclaimer;


function checkpriv($method)
{
    global $userpriv;
    global $data;
    if (!in_array($method, $userpriv[$data['username']])) {
        header('HTTP/1.1 403 Forbidden');
        die('You don\'t have enough privileges! :(');
    }
}

function checkterm($term)
{
    if (@$term == '' or strlen($term) != 5) {
        die('wrong param');
    }
}

function checkstuno($stuno)
{
    if (@$stuno == '' or strlen($stuno) != 8) {
        die('wrong param');
    }
}

function checkcno($cno)
{
    if (@$cno == '' or strlen($cno) != 8) {
        die('wrong param');
    }
}

function checkprivflag($method)
{
    global $userpriv;
    global $data;
    if (!in_array($method, $userpriv[$data['username']])) {
        return false;
    }
    return true;
}

function checktime($begintime, $endtime)
{
    // convert to 10 bit timestamp
    $begintime = strtotime($begintime);
    $endtime = strtotime($endtime);
    // check if datetime is valid
    if ($begintime === false || $endtime === false) {
        die('wrong param');
    }
    if ($begintime >= $endtime) {
        die('wrong param');
    }
    return array($begintime, $endtime);
}


function initdb()
{
    global $dbhost;
    global $dbuser;
    global $dbpass;
    global $dbname;
    $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    mysqli_set_charset($conn, "utf8");
    if (!$conn) {
        die('Service Unvailable right now :(');
    }
    return $conn;
}

function showusage()
{
    global $data;
    global $querypage;
    echo "<br /><b>Example:</b><br />";
    $thisterm = '20222';
    if (checkprivflag('stuscorelst')) {
        echo "stuscorelst<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='stuscorelst' />
        <input type='text' name='term' placeholder='term' value='" . $thisterm . "' style='width:100px;'/>
        <input type='text' name='stuno' placeholder='stuno' value='" . $data['username'] . "' />
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('stuscorelstrng')) {
        echo "stuscorelstrng<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='stuscorelstrng' />
        <input type='text' name='termfrom' placeholder='termfrom' value='20000' style='width:100px;' />
        <input type='text' name='termto' placeholder='termto' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='stuno' placeholder='stuno' value='" . $data['username'] . "' />
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('stugpa')) {
        echo "stugpa<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='stugpa' />
        <input type='text' name='stuno' placeholder='stuno' value='" . $data['username'] . "' />
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('sturank')) {
        echo "sturank<br><form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='sturank' />
        <input type='text' name='termfrom' placeholder='termfrom' value='20000' style='width:100px;' />
        <input type='text' name='termto' placeholder='termto' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='grade' placeholder='grade' value='' style='width:50px;'/>
        <input type='text' name='college' placeholder='college(optional)' value='' />
        <input type='text' name='major' placeholder='major(optional)' value='人工智能' />
        <label for='submajor'>submajor</label>
        <select name='submajor' id='submajor'>
        <option value=''>All</option>
        <option value='1'>Null</option>
        <option value='2'>Not Null</option>
        </select>
        <label for='status'>status</label>
        <select name='status' id='status'>
        <option value='1'>Default</option>
        <option value='2'>Normal Only</option>
        <option value=''>All</option>
        </select>
        <input type='checkbox' id='round' name='round' />
        <label for='round'>round</label>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('classnamelist')) {
        echo "classnamelist<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='classnamelist' />
        <input type='text' name='classid' placeholder='classid' value=''/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('classscore')) {
        echo "classscore<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='classscore' />
        <input type='text' name='classid' placeholder='classid' value=''/>
        <label for='ord'>Order</label>
        <select name='order' id='ord'>
        <option value=''>Default</option>
        <option value='gpa'>GPA</option>
        <option value='mid'>Mid</option>
        <option value='fin'>Final</option>
        <option value='score'>Total</option>
        </select>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('coursename')) {
        echo "coursename<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='coursename' />
        <input type='text' name='term' placeholder='term' value='" . $thisterm . "'style='width:100px;' />
        <input type='text' name='coursename' placeholder='coursename' value='数据%'/>
        <input type='submit' value='OK' />
        </form>";
        echo "<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='coursename' />
        <input type='text' name='termfrom' placeholder='termfrom' value='20000' style='width:100px;' />
        <input type='text' name='termto' placeholder='termto' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='coursename' placeholder='coursename' value='数据%'/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('teacher')) {
        echo "teacher<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='teacher' />
        <input type='text' name='term' placeholder='term' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='teacher' placeholder='teacher/tno' value=''/>
        <input type='submit' value='OK' />
        </form>";
        echo "teacher<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='teacher' />
        <input type='text' name='termfrom' placeholder='termfrom' value='20000' style='width:100px;' />
        <input type='text' name='termto' placeholder='termto' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='teacher' placeholder='teacher/tno' value=''/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('classscorestat')) {
        echo "classscorestat<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='classscorestat' />
        <input type='text' name='classid' placeholder='classid' value=''/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('coursestat')) {
        echo "coursestat<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='coursestat' />
        <input type='text' name='term' placeholder='term' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='courseno' placeholder='courseno' value=''/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('teacherstat')) {
        echo "teacherstat<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='teacherstat' />
        <input type='text' name='term' placeholder='term' value='" . $thisterm . "' style='width:100px;' />
        <input type='text' name='teacher' placeholder='teacher(tno)' value=''/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('stuinfo')) {
        echo "stuinfo<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='stuinfo' />
        <input type='text' name='stuno' placeholder='stuno' />
        <input type='submit' value='OK' />
        </form>";
        echo "<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='stuinfo' />
        <input type='text' name='name' placeholder='stuname' />
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('stuinfolst')) {
        echo "stuinfolst<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='stuinfolst' />
        <input type='text' name='grade' placeholder='grade' value='' style='width:50px;'/>
        <input type='text' name='college' placeholder='college(optional)' value='' />
        <input type='text' name='major' placeholder='major(optional)' value='' />
        <label for='submajor'>submajor</label>
        <select name='submajor' id='submajor'>
        <option value=''>All</option>
        <option value='1'>Null</option>
        <option value='2'>Not Null</option>
        </select>
        <!--label for='status'>status</label>
        <select name='status' id='status'>
        <option value='1'>Default</option>
        <option value='2'>Normal Only</option>
        <option value=''>All</option>
        </select-->
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('vodlist')) {
        echo "vodlist<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='vodlist' />
        <input type='text' name='classid' placeholder='classid' value=''/>
        <input type='submit' value='OK' />
        </form>";
    }
    if (checkprivflag('searchvod')) {
        echo "searchvod<form action='".$querypage."' method='get'>
        <input type='hidden' name='q' value='searchvod' />
        <label for='begintime'>From</label>
        <input type='datetime-local' id='begintime'
       name='begintime' min='2019-01-01T00:00'>

        <label for='endtime'>To</label>
        <input type='datetime-local' id='endtime'
         name='endtime' min='2019-01-01T00:00'>
        <input type='text' name='classroom' placeholder='classroom' value='A123'/>
        <input type='submit' value='OK' />
        </form>";
    }

    echo "<br /><b>Usage:</b><br>q=mypriv<br />";
    echo "q=usage<br />";
    if (checkprivflag('stuscorelst'))
        echo "q=stuscorelst&term=value1&stuno=value2<br />";
    if (checkprivflag('stuscorelstrng'))
        echo "q=stuscorelstrng&termfrom=value1&termto=value2&stuno=value3<br />";
    if (checkprivflag('stugpa'))
        echo "q=stugpa&stuno=value1<br />";
    if (checkprivflag('sturank'))
        echo "q=sturank&termfrom=value1&termto=value2&grade=value3[&college=value4][&major=value5][&submajor=value6][&status=value7][&round=on]<br />";
    if (checkprivflag('classscore'))
        echo "q=classscore&classid=value1[&order=gpa|mid|fin|score|scoremid|scorefin]<br />";
    if (checkprivflag('classnamelist'))
        echo "q=classnamelist&classid=value1<br />";
    if (checkprivflag('coursename')) {
        echo "q=coursename&term=value1&coursename=value2<br />";
        echo "q=coursename&termfrom=value1&termto=value2&coursename=value3<br />";
    }
    if (checkprivflag('teacher')) {
        echo "q=teacher&term=value1&teacher=value2<br />";
        echo "q=teacher&termfrom=value1&termto=value2&teacher=value3<br />";
    }
    if (checkprivflag('classscorestat'))
        echo "q=classscorestat&classid=value1<br />";
    if (checkprivflag('coursestat'))
        echo "q=coursestat&term=value1&courseno=value2<br />";
    if (checkprivflag('teacherstat'))
        echo "q=teacherstat&term=value1&teacher=value2<br />";
    if (checkprivflag('stuinfo')) {
        echo "q=stuinfo&name=value1<br />";
        echo "q=stuinfo&stuno=value1<br />";
    }
    if (checkprivflag('stuinfolst'))
        echo "q=stuinfolst&grade=value1[&college=value2][&major=value3][&submajor=value4]<br />";
    if (checkprivflag('vodlist'))
        echo "q=vodlist&classid=value1<br />";
    if (checkprivflag('searchvod'))
        echo "q=searchvod&begintime=value1&endtime=value2&classroom=value3<br />";

    echo "<br /><br />Contact admin for more information.<br />";
}



function datainfo()
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT termid,count(*) c FROM course GROUP BY termid");
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<br /><table border='1'><tr><th>学期</th><th>课程数</th></tr>";
    while ($row = $result->fetch_assoc()) {
        if ($row['termid'] == '')
            echo "<tr><td>待补充</td><td>" . $row['c'] . "</td></tr>";
        else
            echo "<tr><td>" . $row['termid'] . "</td><td>" . $row['c'] . "</td></tr>";
    }
    echo "</table>";
}

function stuscorelst($term, $stuno)
{
    global $data;
    $restrict = checkprivflag("ownscore") && $stuno != $data['username'];
    if ($restrict) {
        //header('HTTP/1.1 403 Forbidden');
        echo 'You may not access score info of this person :( <br />';
    }
    $conn = initdb();
    $stmt = $conn->prepare("SELECT stuname, college, major FROM student WHERE stuno = ?");
    $stmt->bind_param("s", $stuno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $row = $result->fetch_assoc();
    echo "学号：" . $stuno . "<br />";
    echo "姓名：" . $row['stuname'] . "<br />";
    echo "学院：" . $row['college'] . "<br />";
    echo "专业：" . $row['major'] . "<br />";
    echo "<br />";
    if ($term == null) {
        $stmt = $conn->prepare("SELECT classid,score.termid,courseno,coursename,credit,scoremid,scorefin,score,gpa 
        FROM score
        INNER JOIN course ON (score.classid = course.courseid) WHERE
        (score.termid is null AND score.stuno = ?)");
        $stmt->bind_param("s", $stuno);
    } else {
        $stmt = $conn->prepare("SELECT classid,score.termid,courseno,coursename,credit,scoremid,scorefin,score,gpa 
        FROM score
        INNER JOIN course ON (score.classid = course.courseid) WHERE
        (score.termid = ? AND score.stuno = ?)");
        $stmt->bind_param("ss", $term, $stuno);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    scoreshow($result, $restrict);
    $conn->close();
}

function stuscorelstrng($termfrom, $termto, $stuno)
{
    global $data;
    $restrict = checkprivflag("ownscore") && $stuno != $data['username'];
    if ($restrict) {
        //header('HTTP/1.1 403 Forbidden');
        echo 'You may not access score info of this person :( <br />';
    }
    $conn = initdb();
    $stmt = $conn->prepare("SELECT stuname, college, major FROM student WHERE stuno = ?");
    $stmt->bind_param("s", $stuno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $row = $result->fetch_assoc();
    echo "学号：" . $stuno . "<br />";
    echo "姓名：" . $row['stuname'] . "<br />";
    echo "学院：" . $row['college'] . "<br />";
    echo "专业：" . $row['major'] . "<br />";
    echo "<br />";
    $stmt = $conn->prepare("SELECT classid,score.termid,courseno,coursename,credit,scoremid,scorefin,score,gpa 
    FROM score
    INNER JOIN course ON (score.classid = course.courseid) WHERE
    ((score.termid BETWEEN ? AND ?) AND score.stuno = ?)");

    $stmt->bind_param("sss", $termfrom, $termto, $stuno);
    $stmt->execute();
    $result = $stmt->get_result();
    scoreshow($result, $restrict);
    $conn->close();
}

function stugpa($stuno)
{
    global $data;
    if (checkprivflag("ownscore") && $stuno != $data['username']) {
        header('HTTP/1.1 403 Forbidden');
        die('You can only see your own score :(');
    }
    $conn = initdb();
    $stmt = $conn->prepare("SELECT stuname, college, major FROM student WHERE stuno = ?");
    $stmt->bind_param("s", $stuno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $row = $result->fetch_assoc();
    echo "学号：" . $stuno . "<br />";
    echo "姓名：" . $row['stuname'] . "<br />";
    echo "学院：" . $row['college'] . "<br />";
    echo "专业：" . $row['major'] . "<br />";
    echo "<br />";
    $stmt = $conn->prepare("SELECT score.termid,SUM(gpa * credit)/SUM(credit) AS gpa
                            FROM score
                            INNER JOIN course ON (score.classid = course.courseid)
                            WHERE stuno = ? AND score.score NOT IN ('未提交','退课','退缓','病缓','事缓','免修','P')
                            GROUP BY termid");
    $stmt->bind_param("s", $stuno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0)
        die("No Result");
    echo "<table border='1'><tr><th>学期</th><th>绩点</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['termid'] . "</td><td>" . sprintf("%.5f", (float)$row['gpa']) . "</td></tr>";
    }
}

function sturank($termfrom, $termto, $grade, $college, $major, $submajor, $status,$round=false)
{
    global $querypage;
    $conn = initdb();
    $whitelist = array("19", "20", "21", "22", "23");
    $basestmt = "SELECT student.stuno,stuname,major,submajor,SUM(gpa * credit)/SUM(credit) AS gpa
                FROM score
                INNER JOIN course ON (score.classid = course.courseid)
		        INNER JOIN student ON score.stuno = student.stuno
                WHERE (score.termid BETWEEN ? AND ?) AND score.score NOT IN ('未提交','退课','退缓','病缓','事缓','免修','P')
		        AND student.stuno in (";
    $postfix = ")GROUP BY student.stuno,major,submajor ORDER BY gpa DESC";
    if (!in_array($grade, $whitelist)) {
        // legacy mode
        if ($college != '' && $major != '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE (stuno LIKE CONCAT(?,'%') AND college = ? AND major = ?)" . $postfix);
            $stmt->bind_param("sssss", $termfrom, $termto, $grade, $college, $major);
        }
        if ($college != '' && $major == '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE (stuno LIKE CONCAT(?,'%') AND college = ?)" . $postfix);
            $stmt->bind_param("ssss", $termfrom, $termto, $grade, $college);
        }
        if ($college == '' && $major != '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE (stuno LIKE CONCAT(?,'%') AND major = ?)" . $postfix);
            $stmt->bind_param("ssss", $termfrom, $termto, $grade, $major);
        }
        if ($college == '' && $major == '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE (stuno LIKE CONCAT(?,'%'))" . $postfix);
            $stmt->bind_param("sss", $termfrom, $termto, $grade);
        }
    } else {
        // We have obtained grade info.
        $grade = '20' . $grade;
        $app1 = '';
        $app2 = '';
        if (@$submajor == '1') $app1 = ' AND submajor is NULL ';
        if (@$submajor == '2') $app1 = ' AND submajor is NOT NULL ';
        if (@$status == '1') $app2 = ' AND status NOT LIKE "4%" ';
        if (@$status == '2') $app2 = ' AND status LIKE "1%" ';
        if ($college != '' && $major != '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE grade = ? AND college = ? AND major = ?" . $app1 . $app2 . $postfix);
            $stmt->bind_param("sssss", $termfrom, $termto, $grade, $college, $major);
        }
        if ($college != '' && $major == '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE grade = ? AND college = ?" . $app1 . $app2 . $postfix);
            $stmt->bind_param("ssss", $termfrom, $termto, $grade, $college);
        }
        if ($college == '' && $major != '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE grade = ? AND major = ?" . $app1 . $app2 . $postfix);
            $stmt->bind_param("ssss", $termfrom, $termto, $grade, $major);
        }
        if ($college == '' && $major == '') {
            $stmt = $conn->prepare($basestmt . "SELECT stuno FROM student WHERE grade = ?" . $app1 . $app2 . $postfix);
            $stmt->bind_param("sss", $termfrom, $termto, $grade);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $rank = 0;
    $curnum = 0;
    $lastgpa = '---';
    $ccc = $result->num_rows;
    echo "<br />排名人数：" . $ccc . "<br />" .
    "<table border='1'>
    <tr>
    <th>排名</th>
    <th>学号</th>
    <th>姓名</th>
    <th>专业</th>
    <th>GPA</th>
    </tr>";
    $flag1 = checkprivflag("stuscorelstrng");
    $flag2 = checkprivflag("ranklimit");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        if($round){
            $gpaval = sprintf("%.2f", (float)$row['gpa']);
        }else{
            $gpaval = sprintf("%.5f", (float)$row['gpa']);
        }
        $curnum += 1;
        if($gpaval != $lastgpa){
            $lastgpa = $gpaval;
            $rank = $curnum;
        }
        if ($flag2 && $rank >= 0.3 * $ccc) {
            echo "<br />You can only see the top 30% students :)<br />";
            break;
        }
        echo "<td>" . $rank . "</td>";
        if ($flag1)
            echo "<td><a href='".$querypage."?q=stuscorelstrng&termfrom=" . $termfrom . "&termto=" . $termto . "&stuno=" . $row['stuno'] . "'>" . $row['stuno'] . "</a></td>";
        else
            echo "<td>" . $row['stuno'] . "</td>";
        echo "<td>" . $row['stuname'] . "</td>";
        if($row['submajor'] == NULL || $row['submajor'] == ''){
            echo "<td>" . $row['major'] . "</td>";
        }else{
            echo "<td>" . $row['major'] . "(" . $row['submajor'] . ")</td>";
        }
        echo "<td>" . $gpaval . "</td>";
        echo "</tr>";
        
    }
    $conn->close();
    
}

function calclassstugpaavg($conn, $term, $classid)
{
    $stmt = $conn->prepare("SELECT AVG(gpa) val FROM(
        SELECT
            stuno,
            SUM( gpa * credit )/ SUM( credit ) AS gpa 
        FROM
            score
            INNER JOIN course ON ( score.classid = course.courseid ) 
        WHERE
            stuno IN ( SELECT stuno FROM score WHERE classid = ? ) 
            AND score.score NOT IN ( '未提交', '退课', '退缓', '病缓', '事缓', '免修', 'P' ) 
            AND score.termid = ? 
        GROUP BY stuno 
	) sq");
    $stmt->bind_param("ss", $classid, $term);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (float) $row['val'];
    } else {
        return NULL;
    }
}

function classscore($classid, $order)
{
    global $querypage;
    $conn = initdb();
    $stmt = $conn->prepare("SELECT termid,courseno,coursename,teacherno,teachername,credit,
    CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
    FROM course AS c
    WHERE c.courseid = ?");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $row = $result->fetch_assoc();
    $term = $row['termid'];
    echo "<br>";
    echo "学期：" . $row['termid'] . "<br>";
    $flag1 = checkprivflag("coursestat");
    if ($flag1) {
        echo "课程号：<a href='".$querypage."?q=coursestat&term=" . $row['termid'] . "&courseno=" . $row['courseno'] . "'>" . $row['courseno'] . "</a><br>";
    } else {
        echo "课程号：" . $row['courseno'] . "<br>";
    }
    echo "课程名：" . $row['coursename'] . "<br>";
    echo "教师号：" . $row['teacherno'] . "<br>";
    if (checkprivflag("teacherstat"))
        echo "教师名：<a href='".$querypage."?q=teacherstat&term=" . $term . "&teacher=" . $row['teachername'] . "'>" . $row['teachername'] . "</a><br>";
    else
        echo "教师名：" . $row['teachername'] . "<br>";
    echo "学分：" . $row['credit'] . "<br>";
    if ($row['hasvideo'] == 1 && checkprivflag("vodlist"))
        echo "点播列表：<a href='".$querypage."?q=vodlist&classid=" . $classid . "'>🎦</a><br>";
    echo "<br>";
    if ($order != "") {
        $stmt = $conn->prepare("SELECT score.stuno,stuname,college,major,scoremid,scorefin,score,gpa 
        FROM score INNER JOIN student ON (score.stuno = student.stuno) WHERE classid=? ORDER BY " . $order . " DESC");
        $stmt->bind_param("s", $classid);
    } else {
        $stmt = $conn->prepare("SELECT score.stuno,stuname,college,major,scoremid,scorefin,score,gpa 
        FROM score INNER JOIN student ON (score.stuno = student.stuno) WHERE classid=?");
        $stmt->bind_param("s", $classid);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "<table border='1'>
        <tr>
        <th>学号</th>
        <th>姓名</th>
        <th>学院</th>
        <th>平时</th>
        <th>期末</th>
        <th>总评</th>
        <th>GPA</th>
        </tr>";

        $flag1 = checkprivflag("stuscorelst");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            if ($flag1)
                echo "<td><a href='".$querypage."?q=stuscorelst&term=" . $term . "&stuno=" . $row['stuno'] . "'>" . $row['stuno'] . "</a></td>";
            else
                echo "<td>" . $row['stuno'] . "</td>";
            echo "<td>" . $row['stuname'] . "</td>";
            echo "<td>" . $row['college'] . "</td>";
            echo "<td>" . $row['scoremid'] . "</td>";
            echo "<td>" . $row['scorefin'] . "</td>";
            echo "<td>" . $row['score'] . "</td>";
            echo "<td>" . $row['gpa'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No Result";
    }
    $conn->close();
}

function classnamelist($classid)
{
    global $querypage;
    $conn = initdb();
    $stmt = $conn->prepare("SELECT termid,courseno,coursename,teacherno,teachername,credit,
    CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
    FROM course AS c
    WHERE c.courseid = ?");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $row = $result->fetch_assoc();
    $term = $row['termid'];
    echo "<br>";
    echo "学期：" . $row['termid'] . "<br>";
    $flag1 = checkprivflag("coursestat");
    if ($flag1) {
        echo "课程号：<a href='".$querypage."?q=coursestat&term=" . $row['termid'] . "&courseno=" . $row['courseno'] . "'>" . $row['courseno'] . "</a><br>";
    } else {
        echo "课程号：" . $row['courseno'] . "<br>";
    }
    echo "课程名：" . $row['coursename'] . "<br>";
    echo "教师号：" . $row['teacherno'] . "<br>";
    if (checkprivflag("teacherstat"))
        echo "教师名：<a href='".$querypage."?q=teacherstat&term=" . $term . "&teacher=" . $row['teachername'] . "'>" . $row['teachername'] . "</a><br>";
    else
        echo "教师名：" . $row['teachername'] . "<br>";
    echo "学分：" . $row['credit'] . "<br>";
    if ($row['hasvideo'] == 1 && checkprivflag("vodlist"))
        echo "点播列表：<a href='".$querypage."?q=vodlist&classid=" . $classid . "'>🎦</a><br>";
    echo "<br>";
    $stmt = $conn->prepare("SELECT score.stuno,stuname,college,major
    FROM score INNER JOIN student ON (score.stuno = student.stuno) WHERE classid=?");
    $stmt->bind_param("s", $classid);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "<table border='1'>
        <tr>
        <th>学号</th>
        <th>姓名</th>
        <th>学院</th>
        <th>专业</th>
        </tr>";

        $flag1 = checkprivflag("stuscorelst");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            if ($flag1)
                echo "<td><a href='".$querypage."?q=stuscorelst&term=" . $term . "&stuno=" . $row['stuno'] . "'>" . $row['stuno'] . "</a></td>";
            else
                echo "<td>" . $row['stuno'] . "</td>";
            echo "<td>" . $row['stuname'] . "</td>";
            echo "<td>" . $row['college'] . "</td>";
            echo "<td>" . $row['major'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No Result";
    }
    $conn->close();
}


function classscorestat($classid, $displaycourse = True)
{
    global $querypage;
    $conn = initdb();
    $stmt = $conn->prepare("SELECT termid,courseno,coursename,teacherno,teachername,credit,
    CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
    FROM course AS c
    WHERE c.courseid = ?");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    $row = $result->fetch_assoc();
    echo "<br>";
    if ($displaycourse) {
        echo "学期：" . $row['termid'] . "<br>";
        $flag1 = checkprivflag("coursestat");
        if ($flag1) {
            echo "课程号：<a href='".$querypage."?q=coursestat&term=" . $row['termid'] . "&courseno=" . $row['courseno'] . "'>" . $row['courseno'] . "</a><br>";
        } else {
            echo "课程号：" . $row['courseno'] . "<br>";
        }
        echo "课程名：" . $row['coursename'] . "<br>";
        echo "学分：" . $row['credit'] . "<br>";
        echo "<br>";
        echo "<hr>";
        echo "<br>";
    }

    $flag2 = checkprivflag("classscore");
    $flag3 = checkprivflag("classnamelist");
    if ($flag2) {
        echo "id：<a href='".$querypage."?q=classscore&classid=" . $classid . "'>" . $classid . "</a><br>";
    } else if ($flag3) {
        echo "id：<a href='".$querypage."?q=classnamelist&classid=" . $classid . "'>" . $classid . "</a><br>";
    } else {
        echo "id：" . $classid . "<br>";
    }
    echo "教师号：" . $row['teacherno'] . "<br>";
    if (checkprivflag("teacherstat"))
        echo "教师名：<a href='".$querypage."?q=teacherstat&term=" . $row['termid'] . "&teacher=" . $row['teachername'] . "'>" . $row['teachername'] . "</a><br>";
    else
        echo "教师名：" . $row['teachername'] . "<br>";
    if ($row['hasvideo'] == 1 && checkprivflag("vodlist"))
        echo "点播列表：<a href='".$querypage."?q=vodlist&classid=" . $classid . "'>🎦</a><br>";
    echo "<br>";


    $stmt = $conn->prepare("SELECT score.stuno a, gpa, score 
    FROM score INNER JOIN student ON (score.stuno = student.stuno) WHERE classid=?");
    $stmt->bind_param("s", $classid);
    $stmt->execute();

    $result = $stmt->get_result();
    $exception = array('未提交', '退课', '退缓', '病缓', '事缓', '免修');
    if ($result->num_rows > 0) {
        echo "人数：" . $result->num_rows . "<br>";
        echo "<br>";
        $gpa = array();
        $gpasum = 0;
        $exceptionstunum = 0;
        while ($row2 = $result->fetch_assoc()) {
            if (in_array($row2['score'], $exception)) {
                $exceptionstunum++;
                continue;
            }
            $gpa[] = $row2['gpa'];
            $gpasum += $row2['gpa'];
        }
        $gpamean = $gpasum / ($result->num_rows - $exceptionstunum);
        $stugpamean = calclassstugpaavg($conn, $row['termid'], $classid);
        $count = array_count_values($gpa);
        echo "<table border='1'>
        <tr>
        <th>4.0</th>
        <th>3.7</th>
        <th>3.3</th>
        <th>3.0</th>
        <th>2.7</th>
        <th>2.3</th>
        <th>2.0</th>
        <th>1.7</th>
        <th>1.5</th>
        <th>1.0</th>
        <th>0.0</th>
        <th>不计</th>
        </tr>";
        echo "<tr>";
        echo "<td>" . @$count['4.0'] . "</td>";
        echo "<td>" . @$count['3.7'] . "</td>";
        echo "<td>" . @$count['3.3'] . "</td>";
        echo "<td>" . @$count['3.0'] . "</td>";
        echo "<td>" . @$count['2.7'] . "</td>";
        echo "<td>" . @$count['2.3'] . "</td>";
        echo "<td>" . @$count['2.0'] . "</td>";
        echo "<td>" . @$count['1.7'] . "</td>";
        echo "<td>" . @$count['1.5'] . "</td>";
        echo "<td>" . @$count['1.0'] . "</td>";
        echo "<td>" . @$count['0.0'] . "</td>";
        echo "<td>" . ($exceptionstunum ? $exceptionstunum : "") . "</td>";
        echo "</tr>";
        echo "</table>";
        echo "<br>";
        echo "平均GPA：" . $gpamean . "<br>";
        echo "4.0占比：" . sprintf('%.5f', (isset($count['4.0']) ? $count['4.0'] : 0) / $result->num_rows * 100) . "%<br>";
        echo "该班级学生当学期平均绩点：" . $stugpamean . "<br>";
        echo "<br>";
    } else {
        echo "No Result";
    }
    $conn->close();
}

function coursestat($term, $courseno)
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT courseid FROM course WHERE termid = ? AND courseno = ?");
    $stmt->bind_param("ss", $term, $courseno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    echo '<HR>';
    $flag = True;
    while ($row = $result->fetch_assoc()) {
        classscorestat($row['courseid'], $flag);
        $flag = False;
        echo '<HR>';
    }
    $conn->close();
}

function teacherstat($term, $teacher)
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT courseid FROM course WHERE termid = ? AND teachername = ?");
    $stmt->bind_param("ss", $term, $teacher);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result. Make sure the teacher is like AAA(12345678).");
    }
    echo '<HR>';
    while ($row = $result->fetch_assoc()) {
        classscorestat($row['courseid'], TRUE);
        echo '<HR>';
    }
    $conn->close();
}

function coursename($term, $coursename)
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT c.*, 
                            CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
                            FROM course AS c
                            WHERE termid = ? AND coursename LIKE CONCAT('%',?,'%')");
    $stmt->bind_param("ss", $term, $coursename);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    if ($result->num_rows > 300) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    courseshow($result);
    $conn->close();
}

function coursenamerng($termfrom, $termto, $coursename)
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT c.*, 
                            CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
                            FROM course AS c
                            WHERE (termid BETWEEN ? AND ?) AND coursename LIKE CONCAT('%',?,'%')");
    $stmt->bind_param("sss", $termfrom, $termto, $coursename);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    if ($result->num_rows > 300) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    courseshow($result);
    $conn->close();
}


function teacher($term, $teacher)
{
    $conn = initdb();
    if (strlen($teacher) == 8 && preg_match('/^\d{8}$/', $teacher)) {
        $stmt = $conn->prepare("SELECT c.*, 
        CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
        FROM course AS c
        WHERE termid = ? AND teachername LIKE CONCAT('%(',?,')%')");
    } else {
        $stmt = $conn->prepare("SELECT c.*, 
        CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
        FROM course AS c
        WHERE termid = ? AND teachername LIKE CONCAT(?,'(%)')");
    }
    $stmt->bind_param("ss", $term, $teacher);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    if ($result->num_rows > 300) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    courseshow($result);
    $conn->close();
}

function teacherrng($termfrom, $termto, $teacher)
{
    $conn = initdb();
    if (strlen($teacher) == 8 && preg_match('/^\d{8}$/', $teacher)) {
        $stmt = $conn->prepare("SELECT c.*, 
        CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
        FROM course AS c
        WHERE (termid BETWEEN ? AND ?) AND teachername LIKE CONCAT('%(',?,')%')");
    } else {
        $stmt = $conn->prepare("SELECT c.*, 
        CASE WHEN EXISTS (select courseid from vod where courseid=c.courseid) THEN 1 ELSE 0 END AS hasvideo
        FROM course AS c
        WHERE (termid BETWEEN ? AND ?) AND teachername LIKE CONCAT(?,'(%)')");
    }
    $stmt->bind_param("sss", $termfrom, $termto, $teacher);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    if ($result->num_rows > 300) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    courseshow($result);
    $conn->close();
}

function stuinfoname($name)
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT * FROM student WHERE stuname LIKE ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    if ($result->num_rows > 50) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    stuinfoshow($result);
    $conn->close();
}

function stuinfonum($stuno)
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT * FROM student WHERE stuno = ?");
    $stmt->bind_param("s", $stuno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    if ($result->num_rows > 50) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    stuinfoshow($result);
    $conn->close();
}

function stuinfolst($grade, $college, $major, $submajor)
{
    $conn = initdb();
    $app1 = '';
    if (@$submajor == '1') $app1 = ' AND submajor is NULL ';
    if (@$submajor == '2') $app1 = ' AND submajor is NOT NULL ';
    $grade = '20' . $grade;
    if ($college != '' && $major != '') {
        $stmt = $conn->prepare("SELECT * FROM student WHERE grade = ? AND college = ? AND major = ?" . $app1);
        $stmt->bind_param("sss", $grade, $college, $major);
    }
    if ($college != '' && $major == '') {
        $stmt = $conn->prepare("SELECT * FROM student WHERE grade = ? AND college = ?" . $app1);
        $stmt->bind_param("ss", $grade, $college);
    }
    if ($college == '' && $major != '') {
        $stmt = $conn->prepare("SELECT * FROM student WHERE grade = ? AND major = ?" . $app1);
        $stmt->bind_param("ss", $grade, $major);
    }
    if ($college == '' && $major == '') {
        $stmt = $conn->prepare("SELECT * FROM student WHERE grade = ?");
        $stmt->bind_param("s", $grade);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $conn->close();
        die("No Result");
    }
    stuinfoshow($result);
    $conn->close();
}

function stuinfoshow($result)
{
    echo 'Found ' . $result->num_rows . ' results';
    echo "<table border='1'>
    <tr>
    <th>学号</th>
    <th>姓名</th>
    <th>学院</th>
    <th>专业</th>
    <th>专业类别</th>";
    if (checkprivflag('stustatus'))
        echo "<th>状态</th>
        <th>备注</th>";
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['stuno'] . "</td>";
        echo "<td>" . $row['stuname'] . "</td>";
        echo "<td>" . $row['college'] . "</td>";
        echo "<td>" . $row['major'] . "</td>";
        echo "<td>" . $row['submajor'] . "</td>";
        if (checkprivflag('stustatus')) {
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['note'] . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

function courseshow($result)
{
    global $querypage;
    echo 'Found ' . $result->num_rows . ' results';
    echo "<table border='1'>
    <tr>
    <th>班级id</th>
    <th>学期</th>
    <th>课程号</th>
    <th>课程名</th>
    <th>教师号</th>
    <th>教师名</th>
    <th>点播</th>
    </tr>";
    $flag1 = checkprivflag("coursestat");
    $flag2 = checkprivflag("classscore");
    $flag3 = checkprivflag("classnamelist");
    $flag4 = checkprivflag("vodlist");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        if ($flag2) {
            echo "<td><a href='".$querypage."?q=classscore&classid=" . $row['courseid'] . "'>" . $row['courseid'] . "</a></td>";
        } else if ($flag3) {
            echo "<td><a href='".$querypage."?q=classnamelist&classid=" . $row['courseid'] . "'>" . $row['courseid'] . "</a></td>";
        } else {
            echo "<td>" . $row['courseid'] . "</td>";
        }
        echo "<td>" . $row['termid'] . "</td>";
        if ($flag1) {
            echo "<td><a href='".$querypage."?q=coursestat&term=" . $row['termid'] . "&courseno=" . $row['courseno'] . "'>" . $row['courseno'] . "</a></td>";
        } else {
            echo "<td>" . $row['courseno'] . "</td>";
        }
        echo "<td>" . $row['coursename'] . "</td>";
        echo "<td>" . $row['teacherno'] . "</td>";
        if (checkprivflag("teacherstat"))
            echo "<td><a href='".$querypage."?q=teacherstat&term=" . $row['termid'] . "&teacher=" . $row['teachername'] . "'>" . $row['teachername'] . "</a></td>";
        else
            echo "<td>" . $row['teachername'] . "</td>";
        if ($flag4 && $row['hasvideo'] == 1) {
            echo "<td><a href='".$querypage."?q=vodlist&classid=" . $row['courseid'] . "'>🎦</a></td>";
        } else {
            echo "<td>--</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

function scoreshow($result, $flag)
{
    global $querypage;
    $exception = array('未提交', '退课', '退缓', '病缓', '事缓', '免修', 'P');
    if ($result->num_rows > 0) {
        $creditsum1 = 0;
        $creditsum2 = 0;
        $gpasum = 0;
        echo "<table border='1'>
    <tr>
    <th>班级id</th>
    <th>学期</th>
    <th>课程号</th>
    <th>课程名称</th>
    <th>学分</th>";
        if (!$flag) {
            echo "<th>平时</th>
    <th>期末</th>
    <th>总评</th>
    <th>GPA</th>";
        }

        echo "</tr>";
        $flag1 = checkprivflag("coursestat");
        $flag2 = checkprivflag("classscore");
        $flag3 = checkprivflag("classnamelist");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            if ($flag2) {
                echo "<td><a href='".$querypage."?q=classscore&classid=" . $row['classid'] . "'>" . $row['classid'] . "</a></td>";
            } else if ($flag3) {
                echo "<td><a href='".$querypage."?q=classnamelist&classid=" . $row['classid'] . "'>" . $row['classid'] . "</a></td>";
            } else {
                echo "<td>" . $row['classid'] . "</td>";
            }
            echo "<td>" . $row['termid'] . "</td>";
            if ($flag1) {
                echo "<td><a href='".$querypage."?q=coursestat&term=" . $row['termid'] . "&courseno=" . $row['courseno'] . "'>" . $row['courseno'] . "</a></td>";
            } else {
                echo "<td>" . $row['courseno'] . "</td>";
            }
            echo "<td>" . $row['coursename'] . "</td>";
            echo "<td>" . $row['credit'] . "</td>";
            if (!$flag) {
                echo "<td>" . $row['scoremid'] . "</td>";
                echo "<td>" . $row['scorefin'] . "</td>";
                echo "<td>" . $row['score'] . "</td>";
                echo "<td>" . $row['gpa'] . "</td>";
                if (!in_array($row['score'], $exception)) {
                    $creditsum2 += (float) $row['credit'];
                    $gpasum += (float) $row['gpa'] * (float) $row['credit'];
                }
            }
            echo "</tr>";
            $creditsum1 += (float) $row['credit'];
        }
        echo "</table>";
        echo "<br>";
        echo "总学分：" . $creditsum1 . "<br>";
        if (!$flag)
            echo "GPA：" . sprintf("%.5f", $gpasum / $creditsum2) . "<br>";
    } else {
        echo "No Result";
    }
}

function vodlist($classid)
{
    global $querypage;
    $conn = initdb();
    $stmt = $conn->prepare("SELECT termid,courseno,coursename,teacherno,teachername,credit FROM course WHERE courseid = ?");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<br>";
    if ($result->num_rows == 0) {
        echo "<font color='red'>课程信息不存在</font><br>";
        // $conn->close();
        // die("No Result");
    } else {
        $row = $result->fetch_assoc();
        $term = $row['termid'];
        echo "<br>";
        echo "学期：" . $row['termid'] . "<br>";
        $flag1 = checkprivflag("coursestat");
        if ($flag1) {
            echo "课程号：<a href='".$querypage."?q=coursestat&term=" . $row['termid'] . "&courseno=" . $row['courseno'] . "'>" . $row['courseno'] . "</a><br>";
        } else {
            echo "课程号：" . $row['courseno'] . "<br>";
        }
        echo "课程名：" . $row['coursename'] . "<br>";
        echo "教师号：" . $row['teacherno'] . "<br>";
        if (checkprivflag("teacherstat"))
            echo "教师名：<a href='".$querypage."?q=teacherstat&term=" . $term . "&teacher=" . $row['teachername'] . "'>" . $row['teachername'] . "</a><br>";
        else
            echo "教师名：" . $row['teachername'] . "<br>";
        echo "学分：" . $row['credit'] . "<br>";
    }

    echo "<br>";

    $stmt = $conn->prepare("SELECT vid,beginTime,endTime,url1,url2,classroom FROM vod WHERE courseid = ?");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result();
    $rrr = 0;
    if ($result->num_rows > 0) {
        echo "<table border='1'>
        <tr>
        <th></th>
        <th>上课日期</th>
        <th>时间</th>
        <th>教室</th>
        <th>课堂</th>
        <th>屏幕</th>
        
        </tr>";

        $flag1 = checkprivflag("stuscorelst");
        while ($row = $result->fetch_assoc()) {
            // Convert 13-digit timestamp to date and time
            $voddate = date("Y-m-d (D)", substr($row['beginTime'], 0, 10));
            $timeperiod = date("H:i", substr($row['beginTime'], 0, 10)) . "~" . date("H:i", substr($row['endTime'], 0, 10));
            $rrr += 1;
            echo "<tr>";
            echo "<td><B>" . $rrr . "</B></td>";
            echo "<td>" . $voddate . "</td>";
            echo "<td>" . $timeperiod . "</td>";
            echo "<td>" . $row['classroom'] . "</td>";
            if ($row['url1'] != "")
                echo "<td><a href='" . $row['url1'] . "' rel=\"noreferrer\" target=\"_blank\">🔗</a></td>";
            else
                echo "<td></td>";
            if ($row['url2'] != "")
                echo "<td><a href='" . $row['url2'] . "' rel=\"noreferrer\" target=\"_blank\">🔗</a></td>";
            else
                echo "<td></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No Result";
    }
    $conn->close();
}

function searchvod($begintime, $endtime, $classroom)
{
    global $querypage;
    $conn = initdb();
    $stmt = $conn->prepare("SELECT vod.courseid,coursename,teachername,beginTime,endTime,url1,url2,classroom FROM vod 
                            left join course on course.courseid=vod.courseid WHERE beginTime >= ? AND endTime <= ? AND classroom LIKE CONCAT(?,'%')");
    $stmt->bind_param("sss", $begintime, $endtime, $classroom);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 500) {
        $conn->close();
        die("Found " . $result->num_rows . " results, too many to display");
    }
    echo '<br>Found ' . $result->num_rows . ' results<br>';
    if ($result->num_rows > 0) {
        echo "<table border='1'>
        <tr>
        <th>课程id</th>
        <th>课程名称</th>
        <th>教师名</th>
        <th>上课日期</th>
        <th>时间</th>
        <th>教室</th>
        <th>课堂</th>
        <th>屏幕</th>
        </tr>";
        $flag1 = checkprivflag('vodlist');
        while ($row = $result->fetch_assoc()) {
            // Convert 13-digit timestamp to date and time
            $voddate = date("Y-m-d (D)", substr($row['beginTime'], 0, 10));
            $timeperiod = date("H:i", substr($row['beginTime'], 0, 10)) . "~" . date("H:i", substr($row['endTime'], 0, 10));

            echo "<tr>";
            if ($flag1)
                echo "<td><a href='".$querypage."?q=vodlist&classid=" . $row['courseid'] . "'>" . $row['courseid'] . "</a></td>";
            else
                echo "<td>" . $row['courseid'] . "</td>";
            echo "<td>" . $row['coursename'] . "</td>";
            echo "<td>" . $row['teachername'] . "</td>";
            echo "<td>" . $voddate . "</td>";
            echo "<td>" . $timeperiod . "</td>";
            echo "<td>" . $row['classroom'] . "</td>";
            if ($row['url1'] != "")
                echo "<td><a href='" . $row['url1'] . "' rel=\"noreferrer\" target=\"_blank\">🔗</a></td>";
            else
                echo "<td></td>";
            if ($row['url2'] != "")
                echo "<td><a href='" . $row['url2'] . "' rel=\"noreferrer\" target=\"_blank\">🔗</a></td>";
            else
                echo "<td></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No Result";
    }
    $conn->close();
}

function classroom()
{
    $conn = initdb();
    $stmt = $conn->prepare("SELECT * from classrooms");
    $stmt->execute();
    $result = $stmt->get_result();
    echo '<br>Found ' . $result->num_rows . ' results<br>';
    if ($result->num_rows > 0) {
        echo "<table border='1'>
        <tr>
        <th>教室</th>
        </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['classroom'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No Result";
    }
    $conn->close();
}

