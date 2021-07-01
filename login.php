<?php
/* 로그인하는 페이지 */
session_start();
 
// 사용자가 이미 로그인되어 있는지 확인하고, 로그인 되어있다면 메인 페이지로 이동
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: main.php");
    exit;
}

// DB와 연결
$tns = "(DESCRIPTION=(ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))(CONNECT_DATA= (SERVICE_NAME=XE)))";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = '';
$password = '';
 
try{
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}
 
// 변수 정의 및 공백으로 초기화
$usernumber = $password = "";
$usernumber_err = $password_err = $login_err = "";
 
// 양식 제출 시 데이터 처리
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // 회원번호 작성 여부를 확인
    if(empty(trim($_POST["usernumber"]))){
        $usernumber_err = "회번번호를 입력하세요.";
    } else{
        $usernumber = trim($_POST["usernumber"]);
    }
    
    // 비밀번호 작성 여부를 확인
    if(empty(trim($_POST["password"]))){
        $password_err = "비밀번호를 입력하세요.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($usernumber_err) && empty($password_err)){
        $sql = "SELECT * FROM CUSTOMER WHERE CNO = :usernumber";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":usernumber", $param_usernumber, PDO::PARAM_STR);
            $param_usernumber = trim($_POST["usernumber"]);
            $count = current($pdo->query("SELECT COUNT(CNO) FROM CUSTOMER WHERE CNO = $usernumber")->fetch());

            if($stmt->execute()){
                // 회원번호가 존재하는지 확인하고 존재한다면 비밀번호 일치 여부를 확인한다.
                if($count == '1'){
                    if($row = $stmt->fetch()){
                        $username = $row["NAME"];
                        $hashed_password = $row["PASSWD"];
                        // 비밀번호가 일치하면 새로운 세션 시작
                        if($password == $hashed_password){
                            ?>
                            <script>
                                alert("로그인 되었습니다.");
                            </script>
                            <?php
                            session_start();
                            // 세션 변수 저장
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $usernumber;
                            $_SESSION["username"] = $username;       

                            if($usernumber == '101') {
                                $main = "mainForAdmin.php";
                                $_SESSION["mainUrl"] = $main;
                                header("location: mainForAdmin.php");
                            }else{
                                $main = "mainForUser.php";
                                $_SESSION["mainUrl"] = $main;
                                header("location: mainForUser.php");
                            }
                        } else{
                            // 유효한 비밀번호가 아닌경우 에러메세지 보이기
                            $login_err = "회원번호 혹은 비밀번호가 유효하지 않습니다.";
                        }
                    }
                } else{
                    // 유효한 회원번호가 아닌경우 에러메세지 보이기
                    $login_err = "회원번호 혹은 비밀번호가 유효하지 않습니다.";
                }
            } else{
                echo "문제가 발생했습니다. 나중에 다시 시도 해주십시오.";
            }
            unset($stmt);
        }
    }
    unset($pdo);
}
?>
 
<!-- 로그인을 위한 HTML-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/login.css" />
    <style>
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>LIBRARY</h2>
        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form class = "login__form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Usernumber</label>
                <input type="text" name="usernumber" class="form-control <?php echo (!empty($usernumber_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $usernumber; ?>">
                <span class="invalid-feedback"><?php echo $usernumber_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" placeholer = "Password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group" id = "login__btn__container">
                <input type="submit" id="login__btn" value="Login">
            </div>
        </form>
    </div>
</body>
</html>