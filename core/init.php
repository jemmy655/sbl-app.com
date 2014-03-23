<?php
  ini_set('display_errors',1);
  error_reporting(E_ALL);

  ini_set('memory_limit', '-1');

  date_default_timezone_set('America/New_York');


  require_once 'core/config.php';

  session_start();

  // Make Database connection
  $DB = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

  require_once 'classes/System.php';
  $SYSTEM = new System;

  require_once 'classes/Session.php';
  require_once 'classes/Login.php';
  require_once 'classes/User.php';
  require_once 'classes/Database.php';

  // Set default landing page
  if (!isset($_GET['page'])) {
    $_GET['page'] = "events";
  }

  // GLOBAL VARIABLES
  $ERRORS = array();
  $LOG_ERRORS = array();
  $REG_ERRORS = array();

  /* 
   * Initial pull from DB
   * Because of control flow, THESE MUST BE SET IN THIS
   * ORDER, BITCH.
   */
  $TEAMS = Database::set_teams();
  $CATEGORIES = Database::set_categories();
  $EVENTS = Database::set_events();
  $YACS = Database::set_yacs();
  $WAGERS = Database::set_wagers();
  $USERS = Database::set_users();

  $session = new Session;

  /* sort function helper for sorting events by time
   * under date
   */
  function compare_event_time($a, $b) {
    $a_time = strtotime($a->timestamp);
    $b_time = strtotime($b->timestamp);

    if ($a_time == $b_time)
      return 0;

    return ($a_time < $b_time) ? -1 : 1;
  }

  usort($EVENTS, 'compare_event_time');
?>
