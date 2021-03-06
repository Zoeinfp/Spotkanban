<?php

require('../vendor/autoload.php');
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));


function set_member($member_name, $member_password, $member_mail){
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  // Use the database 
  $conn = new mysqli($server, $username, $password, $db);

  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }
  
  // INSERT 
  $format = "INSERT INTO member (username, password, email) VALUES ('%s', PASSWORD('%s'), '%s') ";
  $req = sprintf($format, addslashes($member_name), addslashes($member_password), addslashes($member_mail));
  
  $conn->set_charset("utf8");

  if ($conn->query($req) === TRUE) {   
    return "Member is set ( ".$req." )successfully";
  } else {
    return "Bad credentials..." . $req . "<br>" . $conn->error;
  }
}


function get_member($member_name, $member_password){
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  // Use the database 
  $conn = new mysqli($server, $username, $password, $db);

  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }
  
  // SELECT 
  $format = "SELECT * FROM member WHERE username = '%s' AND password = PASSWORD('%s') ";
  $req = sprintf($format, $member_name, $member_password);
  
  $conn->set_charset("utf8");
  $fetched_task = mysqli_fetch_row($conn->query($req));
  
  if($fetched_task != null){
    $result = "Member is found ".$fetched_task[4]."! ".$fetched_task." ( ".$req." )";
  } else {
    $result = "Bad credentials...".$fetched_task." ( ".$req." )";
  }
  return $result;
}

function get_task($task_name){
    $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
    $server = $url["host"];
    $username = $url["user"];
    $password = $url["pass"];
    $db = substr($url["path"], 1);

    // Use the database 
    $conn = new mysqli($server, $username, $password, $db);

    // Check connection
    if ($conn->connect_error) 
    {
      die("Connection failed: " . $conn->connect_error);
    }
    
    // SELECT 
    $format = "SELECT * FROM task WHERE name LIKE %s";
    $req = sprintf($format, $task_name);
    
    $conn->set_charset("utf8");
    $fetched_task = mysqli_fetch_all($conn->query($req));
    return $fetched_task;
}

function remove_task($task_id){
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  // Use the database 
  $conn = new mysqli($server, $username, $password, $db);

  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }
  
  // DELETE 
  $format = "DELETE FROM task WHERE id = %s";
  $req = sprintf($format, $task_id);

  if ($conn->query($req) === TRUE) {
    return "Record deleted successfully";
  } else {
    return "Error deleting record: (".$req.")". $conn->error;
  }
}

function create_task($task_name, $task_description, $task_status, $task_team){
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  // Use the database 
  $conn = new mysqli($server, $username, $password, $db);

  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }
  
  // SELECT 
  if($task_status != ""){
    $format = "INSERT INTO task (name, status, description, team) VALUES ('%s', '%s', '%s', '%s') ";
    $req = sprintf($format, addslashes($task_name), addslashes($task_status), addslashes($task_description), addslashes($task_team));
  } else {
    $format = "INSERT INTO task (name, description, team) VALUES ('%s', '%s', '%s') ";
    $req = sprintf($format, addslashes($task_name), addslashes($task_description), addslashes($task_team));
  }
  $conn->set_charset("utf8");

  if ($conn->query($req) === TRUE) {
    return "New record created ( ".$req." )successfully";
  } else {
    return "Error: " . $req . "<br>" . $conn->error;
  }
}

function get_tasks($team="")
{
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  // Use the database 
  $conn = new mysqli($server, $username, $password, $db);

  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }

  $conn->set_charset("utf8");
  
  $format = "SELECT * FROM task WHERE team LIKE '%s'";
  $req = sprintf($format, $team);
  return mysqli_fetch_all($conn->query($req));
}

function send_mail($member_name, $member_password, $member_email){
  
  $secret = md5(parse_url(getenv("CLEARDB_DATABASE_URL")).password_hash($member_password).openssl_encrypt($member_email));
  $subject = "Spotkanban account verification"; 
  $message = "<h1>Spotkanban</h1>";
  $message .= "<p>Hello ".$member_name." ! Please visit this link to verify your account and begin to use the Spotkanban</p>";
  $message .= "<p><a href='".url_for("/")."/verify-account/".$secret."'>Account verification link</a></p>";
  $header = "From:Spotkanban-no-reply@board.com \r\n";
  $header .= "Cc:zoebelleton@gmail.com \r\n";
  $header .= "MIME-Version: 1.0\r\n";
  $header .= "Content-type: text/html\r\n";
  
  $retval = mail ($member_email, $subject, $message, $header);
  
  if( $retval == true ) {
    echo "Message sent successfully...";
  }else {
    echo "Message could not be sent...";
  }
}

// Our web handlers

$app->get('/', function() use($app) {
    $tasks = get_tasks();
    $app['monolog']->addDebug('logging index output.');
    return $app['twig']->render('index.twig', ['tasks' => $tasks]);
  });

  $app->get('/{team}', function($team) use($app) {
    $tasks = get_tasks($team);
    $app['monolog']->addDebug('logging index output.');
    return $app['twig']->render('index.twig', ['tasks' => $tasks]);
  });
  

  $app->post('/login', function(Request $request) use($app) {
    $member_name = $request->get('member_name');
    $member_password = $request->get('member_password');
    $member = get_member($member_name, $member_password);
    return json_encode(array('member_name' => $member_name, 'member_password' => $member_password, 'member' => $member));
  });

  $app->post('/register', function(Request $request) use($app) {
    $member_name = $request->get('member_name');
    $member_password = $request->get('member_password');
    $member_mail = $request->get('member_mail');
    $member = set_member($member_name, $member_password, $member_mail);
    //send_mail($member_name, $member_password, $member_mail);
    return json_encode(array('member_name' => $member_name, 'member_password' => $member_password, 'member' => $member));
  });

  $app->get('/verify-account/{secret}', function($secret) use($app) {
    $app['monolog']->addDebug('logging index output.');
    return $app['twig']->render('index.twig', ['secret' => $secret]);
  });

  $app->post('/handleTask', function(Request $request) use($app) {
    $task_id = $request->get('id');
    $task_name = $request->get('name');
    $task_description = $request->get('description');
    $task_status = $request->get('status');
    $task_team = $request->get('team');
    // GET TASK
    //$fetched_task = get_task($task_name);
    
    //$msg = $task_id." : OK";

    // DELETE OLD TASK
    if($task_id != ''){
      remove_task($task_id);  
    }
  
    // CREATE TASK
    $msg = create_task($task_name, $task_description, $task_status, $task_team);

    return json_encode(array('id' => $task_id, 'msg' => $msg, 'team' => $task_team));
  });


$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});


$app->post('/removeTask', function(Request $request) use($app) {
  
  $task_id = $request->get('id');
  $task_name = $request->get('name');
  $task_description = $request->get('description');
  $task_status = $request->get('status');
  
  // DELETE OLD TASK
  if($task_id != ''){
    $msg = remove_task($task_id);  
  }

  return json_encode(array('id' => $task_id, 'msg' => $msg));
});


$app->get('/cowsay', function() use($app) {
$app['monolog']->addDebug('cowsay');
return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
