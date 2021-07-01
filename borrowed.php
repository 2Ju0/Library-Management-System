<?php 
/* 접속한 회원의 대출 현황을 열람하는 페이지 */

// BORROWED에 필요한 파일 include
  include "config.php";
  include "process.php";

// 메인 페이지로 이동하기 위한 세션 변수
  $main = $_SESSION["mainUrl"];

// 현재 접속 중인 회원의 회원 번호를 변수에 저장
  $cno = $_SESSION["id"];

// 사용자가 어떤 버튼을 클릭했는지 POST 방식으로 받아와 변수에 저장
  $statusBtn = $_POST['statusBtn'] ?? '';

// 버튼의 종류에 해당하는 함수를 호출
  if ($statusBtn == "반납") {
    return_book($_POST['ISBN']);
  }elseif($statusBtn == "연장") {
    extend_book($_POST['ISBN']);
  }
?>

<!-- 접속한 회원의 대출 현황을 열람하기 위한 HTML-->
<!DOCTYPE html>
<html lang="ko"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/booklist.css" />
  <title>MyBooks</title>
</head>
<body>
<div class="status-bar">
    <div>
      <a href=<?= $main ?>><div class="status">HOME</div></a>
    </div>
    <div>
      <a href="logout.php"><div class="status">LOGOUT</div></a>
    </div>
  </div>
  <div class = "container">
      <h2 class="page__title">대출 현황</h2>
      <table class="booklist__table">
          <thead>
              <tr>
                  <th>ISBN</th>
                  <th>서명</th>
                  <th>저자</th>
                  <th>대출일</th>
                  <th>반납예정일</th>
                  <th>연장횟수</th>
                  <th>연장신청</th>
                  <th>반납</th>
              </tr>
          </thead>
          <tbody>
  <?php

  // 현재 접속 중인 회원이 대출 중인 도서 정보를 조회하는 쿼리
  $stmt = $conn -> prepare("SELECT EBOOK.ISBN, TITLE, DATERENTED, DATEDUE, EXTTIMES,
                            SUBSTR(XMLAGG(XMLELEMENT(COL ,', ', AUTHOR) ORDER BY AUTHOR).EXTRACT('//text()').GETSTRINGVAL() , 2) AUTHOR
                            FROM EBOOK, AUTHORS
                            WHERE EBOOK.ISBN = AUTHORS.ISBN
                            AND CNO = $cno
                            GROUP BY EBOOK.ISBN, TITLE, DATERENTED, DATEDUE, EXTTIMES
                            ORDER BY ISBN");
  $stmt ->execute();
  while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
  ?>
      <tr>
          <td><?= $row['ISBN'] ?></td>
          <td><a href="bookview.php?ISBN=<?= $row['ISBN'] ?>"><?= $row['TITLE'] ?></a></td>
          <td><?= $row['AUTHOR'] ?></td>
          <td><?= $row['DATERENTED'] ?></td>
          <td><?= $row['DATEDUE'] ?></td>
          <td><?= $row['EXTTIMES'] ?></td>
          <td>
            <form  method="POST" class="row">
              <input type="hidden" name="ISBN" value="<?= $row['ISBN'] ?>">
              <button type="submit" name="statusBtn"  value="연장" class="btn btn-danger">연장</button>
            </form>
          </td>
          <td>
            <form method="POST" class="row">
              <input type="hidden" name="ISBN" value="<?= $row['ISBN'] ?>">
              <button type="submit" name="statusBtn"  value="반납" class="btn btn-danger">반납</button>
            </form>
          </td>
      </tr>
  <?php
  }
  ?>
</tbody>
      </table>
  </div>
</body>
</html>
