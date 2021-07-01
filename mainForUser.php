<?php
/* 일반 회원을 위한 메인 페이지 */
session_start();
 
// 사용자가 이미 로그인되어 있는지 확인하고, 로그인 되어있다면 메인 페이지로 이동
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>

<!-- 일반 회원을 위한 메인 페이지 HTML-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>main</title>
    <link rel="stylesheet" href="css/main.css" />
</head>
<body>
  <div class="status-bar">
      <a href="logout.php"><div class="status">LOGOUT</div></a>
  </div>
    <section class="main__container">
      <h1 class="main__header">Hi, &nbsp<b class="username"><?php echo htmlspecialchars($_SESSION["username"]); ?>.</b></h1>
      <h2 class="sub__header">Welcome to CNU Library</h1>
      <main class="category__container">
      <a  class="category" href="booklist.php">
          <img src="img/search.png" alt="search">
          <div class="category__line"></div>
          <div class="category__text">도서 검색</div>
      </a>
      <a class="category" href="borrowed.php">
          <img src="img/book.png" alt="search">
          <div class="category__line"></div>
          <div class="category__text">나의 대출 현황</div>
      </a>
      <a class="category" href="reservation.php">
          <img src="img/reserved.png" alt="search">
          <div class="category__line"></div>
          <div class="category__text">나의 예약 현황</div>
      </a>
      </main>
    </section>
</body>
</html>