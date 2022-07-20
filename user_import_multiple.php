<?php
/* Template Name: multiple User Import */

get_header();

require_once('wp-content/themes/boss-child/fusionAuth_API/src/FusionAuth/FusionAuthClient.php');

$client = new FusionAuth\FusionAuthClient("RDbGv5N2fCPn98CXKkTZ7NsnYONPYiTAhq53XmiEaqIIb9OvSTofw2Ey", "https://edenmethod-prod.fusionauth.io");

global $wpdb;

if(!empty($_POST)){
  // print_r($_POST['userNumber']);
  if(!empty($_POST['userNumber'])){
    $userNumber = $_POST['userNumber'];
    $unImportUser = $wpdb->get_results("SELECT * FROM edenem_users EU, edenem_usermeta EUM WHERE EU.ID = EUM.user_id AND (EUM.meta_key = 'userImportVal' AND EUM.meta_value = 'salt') ORDER BY EU.user_registered DESC LIMIT $userNumber");

    // $unImportUser = $wpdb->get_results("SELECT * FROM edenem_users EU, edenem_usermeta EUM WHERE EU.ID = EUM.user_id AND (EUM.meta_key = 'userImportVal' AND EUM.meta_value = 'salt')");
    // echo count($unImportUser);
    // $unImportUser = $wpdb->get_results("SELECT * FROM edenem_users WHERE id = 2412");

    // echo "<pre>";
    // print_r($unImportUser);
    // echo "</pre>";
    // die;

    $error_mess = "<table><thead><tr><td>WordPress User ID</td><td>Username</td><td>Name</td><td>Email</td></tr><thead><tbody>";
    $success_mess = "<table><thead><tr><td>WordPress User ID</td><td>Username</td><td>Name</td><td>Email</td></tr><thead><tbody>";
    $user_salt = "<table><thead><tr><td>WordPress User ID</td><td>Username</td><td>Name</td><td>Email</td><td>Random Password</td></tr><thead><tbody>";
    $user_other = "<table><thead><tr><td>WordPress User ID</td><td>Username</td><td>Name</td><td>Email</td></tr><thead><tbody>";
    foreach ($unImportUser as $UnuserValue) {
      $userMetaData = get_user_meta ($UnuserValue->ID);
      // $hash = $UnuserValue->user_pass;
      // $type = substr($hash, 0,2);
      // $factor = substr($hash, 2,2);
      // $factor = 8192;
      // $salt = substr($hash, 4,8);
      // $pw = substr($hash, 12);

      $userEmail = $UnuserValue->user_email;
      $userName = $UnuserValue->user_login;
      // $password = $userValue->user_pass;
      $displayName = $UnuserValue->display_name;
      $firstName = get_user_meta ( $UnuserValue->ID, 'first_name', true);
      $lastName = get_user_meta ( $UnuserValue->ID, 'last_name', true);
      $password = get_user_meta ( $UnuserValue->ID, 'randomPassword', true);
      $fullName = $firstName." ".$lastName;
          
      // $userImage = esc_url( get_avatar_url( $UnuserValue->ID ) );

      $request = '
        {
        "users": [
            {
              "active": true,
              "data": {
                "displayName": "'.$displayName.'"
              },
              "email": "'.$userEmail.'",
              "firstName": "'.$firstName.'",
              "lastName": "'.$lastName.'",
              "fullName": "'.$fullName.'",
              "password": "'.$password.'",
              "passwordChangeRequired": false,
              "registrations": [
                {
                  "applicationId": "6003a497-76a6-43e7-a728-ca11b0f95b5d",
                  "roles": [
                    "Customer"
                  ],
                  "username": "'.$userName.'",
                  "verified": true
                }
              ],
              "usernameStatus": "ACTIVE",
              "username": "'.$userName.'",
              "verified": true
            }
          ]
        }';

        // echo "<pre>";
        // print_r($request);
        // echo "</pre>";die;

        $result = $client->importUsersNew($request);
        if (!$result->wasSuccessful()) {
          if(isset($result->errorResponse->generalErrors)){
            // print_r($result->errorResponse->generalErrors);
            // echo "<br><br>User Already Exist";
            update_user_meta( $UnuserValue->ID, 'userImportVal', 'UAE' );
            $error_mess .= "<tr><td>". $UnuserValue->ID ."</td><td>". $userName ."</td><td>". $fullName. "</td><td>". $UnuserValue->user_email."</td></tr>";
          }else if(isset($result->errorResponse->fieldErrors)){
            // print_r($result->errorResponse->fieldErrors);
            // echo "<br><br>User Salt Error";
            $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
            $randomPass = substr(str_shuffle($alphabet), 0, 12);
            update_user_meta( $UnuserValue->ID, 'userImportVal', 'salt' );
            update_user_meta( $UnuserValue->ID, 'randomPassword', $randomPass );
            $user_salt .= "<tr><td>". $UnuserValue->ID ."</td><td>". $userName ."</td><td>". $fullName. "</td><td>". $UnuserValue->user_email."</td><td>".$randomPass."</td></tr>";
          }else{
            // echo "<br><br>User Other Error";
            update_user_meta( $UnuserValue->ID, 'userImportVal', 'Other' );
            $user_other .= "<tr><td>". $UnuserValue->ID ."</td><td>". $userName ."</td><td>". $fullName. "</td><td>". $UnuserValue->user_email."</td><td>".$result->errorResponse."</td></tr>";
          }
        }else{
          // $success_mess .= "User ID = ". $UnuserValue->ID."<br> User Email = ". $UnuserValue->user_email."<br>"."User Import Successfully <br><br>";
          update_user_meta( $UnuserValue->ID, 'userImportVal', 'Yes' );
          $success_mess .= "<tr><td>". $UnuserValue->ID ."</td><td>". $userName ."</td><td>". $fullName. "</td><td>". $UnuserValue->user_email."</td></tr>";
        }
    }
    
    $error_mess .= "</tbody></table>";
    $user_salt .= "</tbody></table>";
    $user_other .= "</tbody></table>";
    $success_mess .= "</tbody></table>";

    // echo "<pre>";
    // print_r($request);
    // echo "</pre>";die;
  }else{
    $error_mess = "Plese select import users number!";
  }
}
?>

<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css" rel="stylesheet" /> -->
<link href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
<style type="text/css">
.passwordChangeInner {background: #ffffff; box-shadow: 0px 0px 12px 2px #dfdfdf; border-radius: 8px; padding: 40px; width: 100%; max-width: 480px; margin: 10% auto; }
.userGetSelect {width: 100%; margin-bottom: 15px; }
.error_message {text-align: center; color: #ed0016; background-color: #f8d7da; border-color: #f5c6cb; border-radius: 5px; padding: 10px; }
.success_message {text-align: center; color: #09501a; background-color: #d4edda; border-color: #c3e6cb; border-radius: 5px; padding: 10px; }
.user_salt {text-align: center; color: #fbbc05; background-color: #78693b; border-color: #c3e6cb; border-radius: 5px; padding: 10px; }
.user_other {text-align: center; color: #002056; background-color: #303f58; border-color: #c3e6cb; border-radius: 5px; padding: 10px; }
.passwordChangeInner h2 {margin: 0 0 15px 0; }
.passwordChangeInner form {margin: 0; }
body .select2-container .select2-dropdown {max-width: 400px; margin: 32px 0 0 0;}
span.select2.select2-container.select2-container--default {width: 100% !important;}
.passwordChange_pageBtn a {color: #007cff; padding: 10px 15px; border-radius: 50px; border: 1px solid #007cff; }
.passwordChange_pageBtn .active {background: #007cff; color: #ffffff; }
.passwordChange_pageBtn {margin-bottom: 25px; text-align: center; }
.userDataTable {padding: 50px 0; }
.userErrorSuccess {margin-top: 50px; }
</style>
<div class="passwordChange_page">
  <div class="container">
    <div class="userErrorSuccess">
        <?php 
          if(!empty($error_mess)){
            echo '<div class="error_message">'.$error_mess.'</div>';
          }
          if(!empty($success_mess)){
            echo '<div class="success_message">'.$success_mess.'</div>';
          }
          if(!empty($user_salt)){
            echo '<div class="user_salt">'.$user_salt.'</div>';
          }
          if(!empty($user_other)){
            echo '<div class="user_other">'.$user_other.'</div>';
          }
        ?>
    </div>
    <div class="passwordChangeInner">
      <div class="passwordChange_pageBtn">
        <a href="/user-import/" >Single User Import</a>
        <a href="javascript:void(0)" class="active">Multiple User Import</a>
      </div>
      <h2 class="text-center">Multiple User Import</h2>
      
      <hr>
      <form action="" method="post">
        <div class="userGetSelect">
          <!-- <label>Select User</label><br> -->
          <select class="js-example-basic-single" name="userNumber">
            <option value="">-- Select Number of Users --</option>
            <option value="2">2</option>
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="300">300</option>     
          </select>
        </div>
        <div class="userGetButton">
          <button type="submit">Import Users</button>
        </div>
      </form>
    </div>

    <div class="userDataTable">
      <h2 class="text-center">Imported Users</h2>
      <?php 
        $importUser = $wpdb->get_results("SELECT * FROM edenem_users EUA, edenem_usermeta EUMA WHERE EUA.ID = EUMA.user_id AND (EUMA.meta_key = 'userImportVal' AND EUMA.meta_value = 'No')");
      ?>
      <table id="example1" class="table table-striped table-bordered" style="width:100%">
              <thead>
                  <tr>
                      <th>Sr No</th>
                      <th>User ID</th>
                      <th>Username</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>User Import Status</th>
                  </tr>
              </thead>
              <tbody>
                  <?php 
                    $i = 1;
                    foreach ($importUser as $ImpValue) {
                    echo "
                      <tr>
                        <td>$i</td>
                        <td>$ImpValue->ID</td>
                        <td>$ImpValue->user_login</td>
                        <td>$ImpValue->display_name</td>
                        <td>$ImpValue->user_email</td>
                        <td>$ImpValue->meta_value</td>
                      </tr>
                    ";
                    $i++;
                    } 
                  ?>
              </tbody>
              <tfoot>
                  <tr>
                      <th>Sr No</th>
                      <th>User ID</th>
                      <th>Username</th>
                      <th>Full Name</th>
                      <th>Email</th>
                      <th>User Import Status</th>
                  </tr>
              </tfoot>
          </table>
    </div>
  </div>
</div>


<?php
get_footer();  
?>

<script type="text/javascript">
  jQuery(document).ready(function() {
      jQuery('.js-example-basic-single').select2();
  });
  jQuery(document).ready(function() {
      jQuery('#example').DataTable();
  } );
</script>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #000000;
  text-align: left;
  padding: 8px;
}

</style>