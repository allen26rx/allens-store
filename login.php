<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="login.css">
</head>
<body>

    <?php
       if( isset( $_POST["lgn_btn"])){

     $email= $_POST["email"];
     $password= $_POST["password"];

      if($email && $password)
    {
        require("db_connection.php");

        $connection = databaseConnection();

        $sql =" SELECT * FROM `allens` WHERE email = ?";
        
        $record = [$email];
       
        $query =$connection->prepare($sql);

        $query->execute($record);

        $response = $query->fetch(PDO::FETCH_ASSOC);
        //  print_r($response);
         $db_password = $response["password"] ?? "";
        
        $match =password_verify($password, $db_password);
         if ($match) {


        
            session_start();

            $id=$response ["id"];
             $name=$response ["name"];
             $email=$response ["email"];

            
          $_SESSION["id"]=$id;
           $_SESSION["name"] =$name;
            $_SESSION["email"]=$email;

         header("location:dashboard.php");


         }
         else{
              echo "<div style='color:red;'> invalid email or password  </div>";
         }




    }

    else
        {
            echo "<div style='color:red;'> Please enter required fields </div>";
              }






        }






?>

<div class="container">
    <div class="form-box">
          <form method="POST">
        <h2>Login to your account </h2>

      

            <!-- Email -->
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required id="email">
            </div>

            <!-- Password -->
            <div class="input-group password-group">
                <input type="password" name="password"id="password"placeholder="Password"required>
                <span class="toggle-password" onclick="togglePassword('password', this)">👁</span>
            </div>

            <!-- Remember Me + Forgot -->
            <div class="form-options">
                <label class="remember">
                    <input type="checkbox">
                    <span>Remember me</span>
                </label>

                <a href="#" class="forgot">Forgot Password?</a>
            </div>

            <button type="submit" name="lgn_btn" >Login</button>

            <!-- Link to Register -->
            <div class="switch-link">
                Don’t have an account? <a href="register.php" style="color: #ffa500;">Register</a>

                
            </div>

        </form>
    </div>
</div>

<-JS ->
<script>
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
</script>

</body>
</html>