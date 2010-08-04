<?php

/**
 * remember_me plugin
 * Simplified version of remember_me plugin of valsily0 (Roland 'rosali' Liebl) from http://myroundcube.googlecode.com
 *
 *
 * @version 1.4.1 - 2.08.2010
 * @author scambra / Sergio Cambra
 * @website http://github.com/scambra/remember_me
 * @licence GNU GPL
 *
 **/
 
class remember_me extends rcube_plugin {
  
  function init() {

    $this->add_hook('template_object_loginform', array($this,'rememberme_loginform'));
    $this->add_hook('startup', array($this, 'startup'));
    $this->add_hook('authenticate', array($this, 'authenticate'));
    $this->add_hook('login_after', array($this, 'login_after'));
    $this->add_hook('login_failed', array($this, 'login_failed'));
    $this->add_hook('kill_session', array($this, 'logout'));
  }

  function rememberme_loginform($before) {
  
    $this->add_texts('localization/');
    $this->include_stylesheet('skins/'.$this->api->output->config['skin'].'/remember_me.css');
    $checked = "";
    if($_COOKIE['rememberme_checked'] == 1)
      $checked = 'checked="checked"';
    $b = $before['content'];
    $b = str_ireplace ('</tbody>',
      '<tr><td class="title"><label for="rcmrememberme">' . $this->gettext('rememberme','remember_me') . '</label></td><td><input id="rcmrememberme" ' . $checked . ' name="_rememberme" value="1" type="checkbox" /></td>
      </tr></tbody>',$b);
    $before['content']=$b;;	
    return ($before);
  }

  function startup($args) {
   
    if ($args['task'] == 'settings')
      return $args; // do not login on pwtools request

    if(isset($_SESSION['temp'])&&
      !empty($_COOKIE['rememberme_user']) &&
      !empty($_COOKIE['rememberme_pass']) &&
      !empty($_COOKIE['rememberme_host']) &&
      !empty($_COOKIE['rememberme_timezone'])
      ){
      $rcmail = rcmail::get_instance();
      $user = $rcmail->decrypt($_COOKIE['rememberme_user']);
      $pass = $rcmail->decrypt($_COOKIE['rememberme_pass']);
      $host = $rcmail->decrypt($_COOKIE['rememberme_host']);
      $timezone = $_COOKIE['rememberme_timezone'];

      if($user != "" && $pass != "" && $host != "" && $timezone != ""){
        $args['action'] = 'login';
      }
    }
   
    return $args;
  }

  function authenticate($args) {
    if(!empty($_COOKIE['rememberme_user']) &&
        !empty($_COOKIE['rememberme_pass']) && 
        !empty($_COOKIE['rememberme_host']) &&
        !empty($_COOKIE['rememberme_timezone'])
      ){
      $rcmail = rcmail::get_instance();
      $user = $rcmail->decrypt($_COOKIE['rememberme_user']);
      $pass = $rcmail->decrypt($_COOKIE['rememberme_pass']);
      $host = $rcmail->decrypt($_COOKIE['rememberme_host']);
      $timezone = $_COOKIE['rememberme_timezone'];

      if($user != "" && $pass != "" && $host != "" && $timezone != ""){
        $args['user']= $user;
        $args['pass']= $pass;
        $args['host']= $host;

        $_REQUEST['_timezone'] = $timezone;

        //Detect DST change
        if(date('I',time()) != date('I',time()-86400)){
          rcmail::setcookie ('rememberme_user','',time()-3600);
          rcmail::setcookie ('rememberme_pass','',time()-3600);
          rcmail::setcookie ('rememberme_host','',time()-3600);
          rcmail::setcookie ('rememberme_timezone','',time()-3600);
        }
        else{
          //Update cookie time
          $rcmail = rcmail::get_instance();
          rcmail::setcookie ('rememberme_user',$rcmail->encrypt($user),time()+60*60*24*365);
          rcmail::setcookie ('rememberme_pass',$rcmail->encrypt($pass),time()+60*60*24*365);
          rcmail::setcookie ('rememberme_host',$rcmail->encrypt($host),time()+60*60*24*365);
          rcmail::setcookie ('rememberme_checked',1,time()+60*60*24*365);
          rcmail::setcookie ('rememberme_timezone',$timezone,time()+60*60*24*365);
        }
      }
    }
    return $args;
  }

  function login_after($args) {
    if (($_POST['_rememberme'] == 1) && !empty($_POST['_user']) &&  !empty($_POST['_pass'])) {
       $rcmail = rcmail::get_instance();
       rcmail::setcookie ('rememberme_user',$rcmail->encrypt(trim($_POST['_user'])),time()+60*60*24*365);
       rcmail::setcookie ('rememberme_pass',$rcmail->encrypt(trim($_POST['_pass'])),time()+60*60*24*365);
       $timezone = $_POST['_timezone'];
       if(empty($timezone) || $timezone == '_default_')
         $timezone = date('Z') / 3600;
       if(!empty($_POST['_host']))
         $host = trim($_POST['_host']);
       else
         $host = $_SESSION['imap_host'];
       rcmail::setcookie ('rememberme_host',$rcmail->encrypt($host),time()+60*60*24*365);
       rcmail::setcookie ('rememberme_checked',1,time()+60*60*24*365);
       rcmail::setcookie ('rememberme_timezone',$timezone,time()+60*60*24*365);
    }
    return $args;
  }

  function login_failed($args) {
    rcmail::setcookie ('rememberme_user','',time()-3600);
    rcmail::setcookie ('rememberme_pass','',time()-3600);
    rcmail::setcookie ('rememberme_host','',time()-3600);
    rcmail::setcookie ('rememberme_timezone','',time()-3600);
    return $args;
  }
  
  function logout($args) {
    $this->add_texts('localization/');
    $rcmail = rcmail::get_instance();
    if($rcmail->task == "logout" && isset($_COOKIE['rememberme_user']) && isset($_COOKIE['rememberme_pass'])){
      rcmail::setcookie ('rememberme_user','',time()-3600);
      rcmail::setcookie ('rememberme_pass','',time()-3600);
      rcmail::setcookie ('rememberme_host','',time()-3600);
      rcmail::setcookie ('rememberme_checked','',time()-3600);
      rcmail::setcookie ('rememberme_timezone','',time()-3600);
      unset($_COOKIE['rememberme_checked']);
    }
    return $args;      
  } 

}
?>
