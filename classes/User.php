<?php
require_once 'classes/Database.php';
require_once 'classes/Notification.php';

Class User extends Database {
  private $user_id;
  private $email;
  private $u_name;

  private $pending_wagers;
  private $accepted_wagers;
  private $denied_wagers;

  private $recent_won_bets;
  private $recent_lost_bets;

  private $pre_notifs;
  public $notifications;

  public $yac;
  

  public function __construct ($email, $un,  $uid) {
    // instantiate parent class
    parent::__construct();

    $this->user_id = $uid;
    $this->u_name = $un;
    $this->email = $email;

    $this->pre_notifs['requests'] = array();
    $this->pre_notifs['accepted'] = array();
    $this->pre_notifs['denied'] = array();
    $this->pre_notifs['counters'] = array();

    $this->pending_wagers = array(); 
    $this->accepted_wagers = array(); 
    $this->denied_wagers = array();
    $this->recent_won_bets = array();
    $this->recent_lost_bets = array();


    $this->set_pending_wagers();
    $this->set_accepted_wagers();
    $this->set_denied_wagers();

    $this->set_recently_won();
    $this->set_recently_lost();

    $this->notifications = array();
    $this->set_notifications();

    $this->update_yac();
  }

  public function get_email () {
    return $this->email;
  }

  public function get_uname () {
    if ($this->u_name === "")
      return $this->get_email();
    else
      return $this->u_name;
  }

  public function get_uid () {
    return $this->user_id;
  }

  public function set_yac () {
    /*
     * $this->yacs is inherited from the Database class.
     * cycle through data store to find the yac with associated
     * id number
     */
    foreach ($this->yacs as $yac) {
      if ($yac->user_id == $this->user_id) {
        $this->yac = $yac;
      }
    }
  }

  public function update_yac () {
    $this->yacs = parent::set_yacs();

    $this->set_yac();
  }

  public function update_user () {
    $this->wagers = parent::set_wagers();

    $this->__construct($this->email, $this->u_name, $this->user_id);
  }

  /*
  public function event_outcome () {
    echo "BELOW IS A LIST OF YOUR PERSONAL RELEVANT WAGER INFO (NOT INCLUDING DENIED WAGERS). <br>";
    
    foreach($this->wagers as $wager) {
    if (($wager->outcome == 1 && $this->user_id == $wager->user_id && $wager->status == 1)
         || $wager->outcome == 0 && $this->user_id == $wager->opponent_id && $wager->status == 1){ 
          echo "You won $" . $wager->amount . "! <br>";
      }
      elseif (($wager->outcome == 0 && $this->user_id == $wager->user_id && $wager->status == 1)
              || $wager->outcome == 1 && $this->user_id == $wager->opponent_id && $wager->status == 1){
          echo "Sorry you lost $" . $wager->amount . ". Please try again fucker :) <br>";
      }
      elseif ($wager->status === NULL && $this->user_id == $wager->user_id) { 
          echo "The wager on event, " . $wager->event . ", for $" . $wager->amount . " is pending OPPONENT'S authorization. <br>";
        }
      elseif ($wager->status === NULL && $this->user_id == $wager->opponent_id) {
          echo "The wager on event, " . $wager->event . ", for $" . $wager->amount . " is pending YOUR authorization. <br>";       
      }
      else 
        ;
    }
  }*/

  public function set_pending_wagers () {
    $this->pre_notifs['requests'] = array();
    /*
     * $this->wagers is inherited from the Database class.
    */
    foreach ($this->wagers as $wager) {
      if ($this->user_id == $wager->user_id && $wager->status === NULL  && $wager->outcome === NULL) {
        $this->pending_wagers[] = $wager;
      }
      elseif($this->user_id == $wager->opponent_id && $wager->status === NULL && $wager->outcome === NULL) {
        $this->pending_wagers[] = $wager;
        if ( !$wager->seen )
          $this->pre_notifs['requests'][] = $wager;
      }
    }
  }

  public function set_accepted_wagers () {
    $this->pre_notifs['accepted'] = array();

    foreach ($this->wagers as $wager) {
      if ($this->user_id == $wager->user_id && $wager->status === 1 && $wager->paid_out === 0) {
        $this->accepted_wagers[] = $wager;
        if ( !$wager->seen )
          $this->pre_notifs['accepted'][] = $wager;
      }
      elseif($this->user_id == $wager->opponent_id && $wager->status === 1 && $wager->paid_out === 0) {
        $this->accepted_wagers[] = $wager;
      }
    }
  }

  public function set_denied_wagers () {
    $this->pre_notifs['denied'] = array();

    foreach ($this->wagers as $wager) {
      if ($this->user_id == $wager->user_id && $wager->status === 0  && $wager->outcome === NULL) {
        $this->denied_wagers[] = $wager;
        if ( !$wager->seen )
          $this->pre_notifs['denied'][] = $wager;
      }
      elseif($this->user_id == $wager->opponent_id && $wager->status === 0 && $wager->outcome === NULL) {
        $this->denied_wagers[] = $wager;
      }
    }
  }

  public function set_recently_won(){

    foreach ($this->wagers as $wager) {
      if ($this->user_id == $wager->user_id && $wager->outcome === 1 
          && strtotime($wager->timestamp) >= strtotime('-2 week')) {
        $this->recent_won_bets[] = $wager;
      }
      elseif($this->user_id == $wager->opponent_id && $wager->outcome === 0
          && strtotime($wager->timestamp) >= strtotime('-2 week'))
        $this->recent_won_bets[] = $wager;
    }
  }

  public function set_recently_lost(){

    foreach ($this->wagers as $wager) {
      if ($this->user_id == $wager->user_id && $wager->outcome === 0 
          && strtotime($wager->timestamp) >= strtotime('-2 week')) {
        $this->recent_lost_bets[] = $wager;
      }
      elseif($this->user_id == $wager->opponent_id && $wager->outcome === 1
          && strtotime($wager->timestamp) >= strtotime('-2 week'))
         $this->recent_lost_bets[] = $wager;
    }
  }

// UNFINISHED FUNCTION
  public function set_counter_offers () {

    foreach ($this->wagers as $wager) {
      if ($this->user_id == $wager->user_id && $wager->status === 1) {
        $this->accepted_wagers[] = $wager;
        if ( !$wager->seen )
          $this->pre_notifs['counters'][] = $wager;
      }
      elseif($this->user_id == $wager->opponent_id && $wager->status === 1) {
        $this->accepted_wagers[] = $wager;
      }
    }
  }

  public function set_notifications () {
    global $SYSTEM;

    foreach ( $this->pre_notifs['requests'] as $r ) {
      $user = $SYSTEM->get_uname($r->user_id);
      $user = sprintf('<b>%s</b>', $user);
      $amt = $r->amount;
      $time = $SYSTEM->time2str($r->timestamp);
      $wager_id = $r->id;
      $type = 'request';

      $team = $r->prop_team->short_name;

      $title = sprintf('%s sent you a request', $user);
      $desc = sprintf('%s put %01.2f on %s', $user, $amt, $team);

      $n = new Notification (
        $type,
        $title,
        $desc,
        $time,
        $wager_id
      );

      $this->notifications[] = $n;
    }

    foreach ( $this->pre_notifs['accepted'] as $a ) {
      $user = $SYSTEM->get_uname($a->opponent_id);
      $user = sprintf('<b>%s</b>', $user);
      $amt = $a->amount;
      $time = $SYSTEM->time2str($a->timestamp);
      $wager_id = $a->id;
      $type = 'message';

      $team = $a->prop_team->short_name;

      $title = sprintf('%s accepted your request', $user);
      $desc = sprintf('You put %01.2f on %s', $amt, $team);

      $n = new Notification (
        $type,
        $title,
        $desc,
        $time,
        $wager_id
      );

      $this->notifications[] = $n;
    }

    foreach ( $this->pre_notifs['denied'] as $d ) {
      $user = $SYSTEM->get_uname($d->opponent_id);
      $user = sprintf('<b>%s</b>', $user);
      $amt = $d->amount;
      $time = $SYSTEM->time2str($d->timestamp);
      $wager_id = $d->id;
      $type = 'message';

      $team = $d->prop_team->short_name;

      $title = sprintf('%s denied your request', $user);
      $desc = sprintf('You put %01.2f on %s', $amt, $team);

      $n = new Notification (
        $type,
        $title,
        $desc,
        $time,
        $wager_id
      );

      $this->notifications[] = $n;
    }

    foreach ( $this->recent_won_bets as $w ) {
      if ( !$w->seen ) {
        $user = $SYSTEM->get_uname($w->opponent_id);
        $user = sprintf('<b>%s</b>', $user);
        $amt = $w->amount;
        $time = $SYSTEM->time2str($w->timestamp);
        $wager_id = $w->id;
        $type = 'message';
        $event_desc = $w->event->description;

        $title = sprintf('You Won!', $user);
        $desc = sprintf('You bet on %s and won <b>%01.2f</b>', $event_desc, $amt);

        $n = new Notification (
          $type,
          $title,
          $desc,
          $time,
          $wager_id
        );

        $this->notifications[] = $n;
      }
    }

    foreach ( $this->recent_lost_bets as $l ) {
      if ( !$l->seen ) {
        $user = $SYSTEM->get_uname($l->opponent_id);
        $user = sprintf('<b>%s</b>', $user);
        $amt = $l->amount;
        $time = $SYSTEM->time2str($l->timestamp);
        $wager_id = $l->id;
        $type = 'message';
        $event_desc = $l->event->description;

        $title = sprintf('You Lost', $user);
        $desc = sprintf('You bet on %s and lost <b>%01.2f</b>', $event_desc, $amt);

        $n = new Notification (
          $type,
          $title,
          $desc,
          $time,
          $wager_id
        );

        $this->notifications[] = $n;
      }
    }
  }

  public function get_denied_wagers () {
    return $this->denied_wagers;
  }

  public function get_accepted_wagers () {
    return $this->accepted_wagers;
  }

  public function get_pending_wagers () {
    return $this->pending_wagers;
  }

  public function get_recent_won_bets(){
    return $this->recent_won_bets;
  }

  public function get_recent_lost_bets (){
     return $this->recent_lost_bets;
  }


  public function make_wager ($opponent_id, $amount, $proposal, $event_id) {
    global $DB;

    if ($amount <= 0)
      return false;

    $query = $DB->prepare ("
      INSERT INTO wager (
        user_id,
        amount,
        proposal,
        opponent_id,
        event_id
      )
      VALUES (?,?,?,?,?)
    ");

    $query->bind_param('ddddd', $this->user_id, $amount, 
                     $proposal, $opponent_id,$event_id);

    return ( $query->execute() ) ? true : false;
  }

  public function check_yacs ($amount) {
    //Check to make sure user has enough funds to support the bet

    return ($this->yac->balance < $amount) ? false : true; 
  }

  public function accept_request ($bet_id) {
    global $DB;
    global $WAGERS;

    foreach ($WAGERS as $w)
      if ($bet_id == $w->id)
        $wager = $w;

      if ($this->user_id == $wager->user_id)
        $opp_id = $wager->opponent_id;
      else $opp_id = $wager->user_id;

      foreach ($this->yacs as $y)
        if ($y->user_id == $opp_id)
          $opp_yac = $y;

      $new_bal = $this->yac->balance - $wager->amount;
      $new_ar = $this->yac->at_risk + $wager->amount;

      $opp_bal = $opp_yac->balance - $wager->amount;
      $opp_ar = $opp_yac->at_risk + $wager->amount;

      $status = 1;
      $seen = 0;


      if ( $this->check_yacs($wager->amount) ) {

        //update at risk and balance of both users
        //$bet_id is the wager_id of the event that should get passed in 


        $query1 = $DB->prepare ("
          UPDATE yac, wager
          SET
            yac.balance = ?,
            yac.at_risk = ?,
            wager.status = ?,
            wager.seen = ?
          WHERE wager.id = ? AND yac.user_id = ? 
        ");

        $query1->bind_param('dddddd',
          $new_bal,
          $new_ar, 
          $status,
          $seen,
          $bet_id,
          $this->user_id
        );
        $query1->execute();

        $query2 = $DB->prepare ("
          UPDATE yac
          SET
            yac.balance = ?,
            yac.at_risk = ?
          WHERE yac.user_id = ? 
        ");

        $query2->bind_param('ddd',
          $opp_bal,
          $opp_ar,
          $opp_id
        );
        $query2->execute();

        return true;
      }
      else
        return false;
   
  }

  public function deny_request ($bet_id) {
    global $DB;

    //Maybe when a user denies a request we go ahead & delete that entry from the wager table?  
    //or do we want to keep it so we can display denied wagers?? Anytime a wager is denied we could
    //just send them a message saying the wager was denied and then after the message displays
    //delete the wager from the table.
    $status = 0;

     $query = $DB->prepare ("
      UPDATE wager
      SET
        status = ?
      WHERE id = ?  
    ");

    $query->bind_param('dd', $status, $bet_id) ;
    $query->execute();

  }

  public function close_notif ($bet_id) {
    global $DB;

    $seen = 1;

     $query = $DB->prepare ("
      UPDATE wager
      SET
        seen = ?
      WHERE id = ?  
    ");

    $query->bind_param('dd', $seen, $bet_id) ;
    $query->execute();

  }

  public function counter_offer ($bet_id, $counter_amount) {
    global $DB;
    global $WAGERS;

    foreach ($WAGERS as $w)
      if ($bet_id == $w->id)
        $wager = $w;

    //they can counter propose an amount for now
    if($wager->counter_bool == 1)
      $wager->counter_bool = 0;
    elseif ($wager->counter_bool == 0)
      $wager->counter_bool = 1;


      $query = $DB->prepare ("
      UPDATE wager
      SET  counter_offer_bool = ?, amount = ?, user_id = ?, opponent_id = ?
      WHERE id = ? 
    ");

    $query->bind_param('ddddd',
      $wager->counter_bool,
      $counter_amount,
      $wager->opponent_id,
      $wager->user_id,
      $bet_id
    );

    return ( $query->execute() ) ? true : false;
  }

};
?>
