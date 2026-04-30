<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Registration Form</title>

    <link rel="stylesheet" href="Register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Alert boxes rendered inside the form card */
        .title-bar {
            width: 36px; height: 3px;
            background: orange; border-radius: 999px;
            margin: 8px auto 20px;
        }
        .alert-box {
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            text-align: center;
        }
        .alert-box i { margin-right: 5px; }
        .success-box {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.3);
            color: #4ade80;
        }
        .success-box a {
            color: #fff; font-weight: 700;
            text-decoration: underline;
            font-size: 12.5px;
        }
        .error-box {
            background: rgba(248,113,113,0.1);
            border: 1px solid rgba(248,113,113,0.3);
            color: #f87171;
        }
    </style>
</head>
<body>
<div class="container">

 <?php
             $success = '';
$error   = '';

if( isset( $_POST["register_btn"])) {
                 
                $first_name = $_POST['first_name'];
               $last_name  = $_POST['last_name'];
               $email      = $_POST['email'];
               $phone_number= $_POST['phone_number'];
               $date_of_birth= $_POST['date_of_birth'];
               $gender     = $_POST['gender'];
                $password   = $_POST['password'];
                 $confirm_password = $_POST['confirm_password'];
              
                // validating form field (check for empty variables)
              if ($first_name  && $last_name && $email  && $phone_number  && $date_of_birth  && $gender  && $password && $confirm_password) {
                
              
              // validating password field (check if both values are the same)
             
              if ($password == $confirm_password ) {

              //link database connection to register .php so the connection will be accesible here 
                    require("db_connection.php");

                    //invoking/calling/establishing the database connect

                     $connection = databaseConnection();

                    //  print_r($connection);

                    //raw query to get the number of occurence of an email address or provided email address

                   $sql = "SELECT COUNT(id) AS total FROM `allens` WHERE email = ?";

                   //record array that contains actual value or values that will replace the placeholder(s) in the raw query

                   $record = [ $email ];

                   //process the query to prevent SQL injection , that is , removing harmful queries

                   $query = $connection->prepare($sql);

                   //At the point the request is sent to the server ,and the server response is received 
                   //(1 = response is received ,0 = no response)

                   $query->execute($record);
                   //break down the response into an associative array

                   $response = $query->fetch(PDO::FETCH_ASSOC);

                //    print_r($reponse);server repsonse
                // gets the key total from the response

                 $email_count = $response['total'];

                 if ($email_count<= 0 ) {
                    $encrypted_password = password_hash($password,PASSWORD_DEFAULT);

                    $sql= "INSERT INTO `allens`( `first_name`, `last_name`, `email`, `phone_number`, `date_of_birth`, `gender`, `password`)
                            VALUES (?,?,?,?,?,?,?)";

                    $record= [$first_name,$last_name,$email,$phone_number,$date_of_birth,$gender,$encrypted_password];

                    $query= $connection->prepare($sql);

                    $query ->execute($record);
                     
                    //the rowCount() returns the number of row(s) that was inseretd by the executed query
                    //the row count determines how many rows that was inserted in the field before it can read succesful
                    if ($query->rowCount()> 0) {
                        $success = 'Registration successful! You can now log in.';
                    }
                    else {
                        $error = 'Registration failed. Please try again later.';
                    }
                 }
                 else{
                    $error = 'Sorry, that email already exists.';
                 }


                }
                else{
                    $error = 'Password and confirm password do not match.';
              }
                }
              }

            //   else{
            //     echo "<div style='color:red;'> Please enter required fields </div>";
            //   }

             
        
        
        ?>
<form action="Register.php" method="POST">

<div class="form-box">
    <h2>Create account</h2>
    <div class="title-bar"></div>

    <?php if ($success): ?>
    <div class="alert-box success-box">
        <i class="fa-solid fa-circle-check"></i>
        <?= htmlspecialchars($success) ?>
        <a href="login.php">Go to Login &rarr;</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-box error-box">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>


    <div class="row">
        <div class="input-group">
            <input type="text" name="first_name" placeholder="First Name" required id="first_name"
              value="<?= isset($_POST["first_name"])? $_POST["first_name"] :"" ?>">
        </div>
        <div class="input-group">
            <input type="text" name="last_name" placeholder="Last Name" required id="last_name"
              value="<?= isset($_POST["last_name"])? $_POST["last_name"] :"" ?>">
        </div>
    </div>

    <div class="input-group">
        <input type="email" name="email" placeholder="Email Address" required id="email"
        value="<?= isset($_POST["email"])? $_POST["email"] :"" ?>">
         
    </div>

    <div class="input-group">
        <input type="tel" name="phone_number" placeholder="Phone Number" required id="phone_number"
          value="<?= isset($_POST["phone_number"])? $_POST["phone_number"] :"" ?>">
    </div>

    <div class="row">
        <div class="input-group">
            <input type="date" name="date_of_birth" required id="date_of_birth"
              value="<?= isset($_POST["date_of_birth"])? $_POST["date_of_birth"] :"" ?>">
        </div>

        <div class="input-group">
            <select name="gender" required >
                <option value="">Gender</option>
                <option>Male</option>
                
                <option>Female</option>
            
            </select>
        </div>
    </div>

    <!-- Password -->
    <div class="input-group password-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <span class="toggle-password" onclick="togglePassword('password', this)">👁</span>
    </div>

    <!-- Confirm Password -->
    <div class="input-group password-group">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
        <span class="toggle-password" onclick="togglePassword('confirmPassword', this)">👁</span>
    </div>

    <!-- <div class="checkbox">
        <label>
            <input type="checkbox" required>
            <span>I agree to the Terms & Conditions</span>
        </label>
    </div> -->

    <button type="submit" name="register_btn">Register</button>


            
                <p class="login_link">Already have an account? <a href="login.php">Login</a></p>
                <style>
                    .login_link{
          display:flex;
          justify-content:center;
          width:100%;

     }
     .login_link a {
          margin-left: 5px;
          text-decoration: none;
             color:  #ffa500;
     }
                </style>

        </form>
    </div>
</div>

<!-- JS -->
<!-- <script>
function togglePassword(id, el){
    const input = document.getElementById(id);

    if(input.type === "password"){
        input.type = "text";
        el.textContent = "🙈";
    } else {
        input.type = "password";
        el.textContent = "👁";
    }
}
</script> -->

</div><!-- /container -->
</body>
</html>