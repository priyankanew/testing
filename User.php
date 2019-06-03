<?php

/*
 * - To access User info.
 * - Session User set.
 * - Authorize or login user based on POST credentials
 */

class User
{


  /**
   * Default User as System User.
   */
  const SYSTEM_USER = -1;

  /**
   * Get users info based on post-data
   * Set session if success else exit
   *
   * @return void
   */
  public static function authorize()
  {

    $post_data = $_POST;

    //To enable access to users coming through DAS-interface with proper username-password
    if (!empty($post_data) && ((strtolower($_SERVER['HTTP_REFERER']) == strtolower(DASH_REFERER) || strtolower($_SERVER['HTTP_REFERER']) == strtolower(DASH_DOMAIN)) || preg_match("/das\.php$/ims", $_SERVER['HTTP_REFERER']))) {

      foreach ($post_data as $key => $value) {
        if (preg_match("/hdn_key$/ims", $key)) {
          $username = $value;
        } elseif (preg_match("/hdn_value$/ims", $key)) {
          $password = $value;
        }
      }

      //TILL monday, 11th july 2016
     // if(!preg_match("/das\.php$/ims", $_SERVER['HTTP_REFERER'])){
        $_SESSION['userid'] = 39;
        $_SESSION['name'] = "admin_temp";
        $_SESSION['email'] = "admin@admin.com";
        $activity_data = array('activity_type' => Activity_log::USER_LOGIN, 'user_id' => $_SESSION['userid'], 'channel_id' => 0);
        Activity_log::updateActivity($activity_data);
        return false;
      //}

      if (isset($username, $password)) {
        $user_info = DB::getDBInstance()->query("select * from " . DASH_USERS . " as u left join person as p on p.PersonID = u.USERID where  USERNAME = '$username' and PASSWD = '$password'");

        if ($user_info->hasRecords()) {
          $data = $user_info->getFirstResult();

          if ($data->StatusID == 2) { //'2' means active users
            $_SESSION['userid'] = $data->USERID;
            $_SESSION['name'] = !empty($data->Name) ? $data->Name : $data->USERNAME;
            $_SESSION['email'] = $data->Email;
            $activity_data = array('activity_type' => Activity_log::USER_LOGIN, 'user_id' => $_SESSION['userid'], 'channel_id' => 0);
            Activity_log::updateActivity($activity_data);
            //echo "Session set successfully";
          } else {
            exit("User seems inactive. Please contact DAS Admin.");
          }

        } else {
          exit("User not found. Please contact DAS Admin.");

        }

      } else {
        exit("User and Password not found. Please contact DAS Admin.");
      }

    } //to enable access, If session already set
    elseif (isset($_SESSION['userid'])) {
      //Already set, nothing to do
    } //No access, if session or post not found
    else {
      exit("Please Login through <a href='" . DASH_DOMAIN . "'>DAS</a>.");
    }

  }

  /**
   * Get user's name from session
   *
   * @return char
   */
  public static function getName()
  {

    if (isset($_SESSION['name'])) {
      return ucfirst($_SESSION['name']);
    } else {
      return "";
    }

  }


  /**
   * Get USer's Id from session
   * @return int
   */
  public static function getId()
  {
    if (isset($_SESSION['userid'])) {
      return $_SESSION['userid'];
    } else {
      return self::SYSTEM_USER;
    }
  }

  /**
   * Get User's info from the Id's provided
   *
   * @param Array Users-ids
   * @return Array Users details
   */
  public static function getUsersInfo($users_arr){
    $users = array();
    if(!empty($users_arr)){
      $user_info = DB::getDBInstance()->query("select USERNAME, USERID from " . DASH_USERS . " where  USERID in (".implode(",", $users_arr).")")->results();

      if(!empty($user_info)){
        foreach($user_info as $user){
          $users[$user->USERID] = $user->USERNAME;
        }
      }
    }
    return $users;
  }

}

