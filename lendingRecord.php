<?php
/* 도서관의 모든 대출 기록을 열람하는 페이지
이때 각 대출 기록은 대출한 회원명, ISBN, 서명, 대출일, 반납일로 보여지며 대출일을 기준으로 오름차순 정렬된다. */

// LENDINGRECORD에 필요한 파일 include
include "config.php";
include "process.php";

// 현재 접속 중인 회원의 회원 번호를 변수에 저장
$cno = $_SESSION["id"];
?>

<!-- 도서관의 모든 대출 기록을 열람하기 위한 HTML -->
<!DOCTYPE html>
<html lang="ko"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/booklist.css" />
  <title>LendingRecord</title>
</head>
<body>
  <div class="status-bar">
    <div>
      <a href="mainForAdmin.php"><div class="status">HOME</div></a>
    </div>
    <div>
      <a href="logout.php"><div class="status">LOGOUT</div></a>
    </div>
  </div>
  <div class = "container">
      <h2 class="page__title">대출 기록</h2>
      <table class="booklist__table">
          <thead>
              <tr>
                  <th>회원명</th>
                  <th>ISBN</th>
                  <th>제목</th>
                  <th>대출일</th>
                  <th>반납일</th>
              </tr>
          </thead>
          <tbody>
  <?php
  // 대출기록별 책의 ISBN, 대출일, 반납일, 회원 번호뿐만 아니라 책을 대출한 회원의 이름과 책 제목을 함께 조회하는 쿼리
  $stmt = $conn -> prepare("SELECT C.NAME AS 이름, PR.ISBN, EB.TITLE AS 제목, PR.DATERENTED AS 대출일, PR.DATERETURNED AS 반납일
                            FROM PREVIOUSRENTAL PR JOIN EBOOK EB
                            ON  PR.ISBN = EB.ISBN
                            JOIN CUSTOMER C
                            ON PR.CNO = C.CNO
                            ORDER BY 대출일");
  $stmt ->execute();

  while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
  ?>
      <tr>
          <td><?= $row['이름'] ?></td>
          <td><?= $row['ISBN'] ?></a></td>
          <td><?= $row['제목'] ?></td>
          <td><?= $row['대출일'] ?></td>
          <td><?= $row['반납일'] ?></td>
      </tr>
  <?php
  }
  ?>
</tbody>
      </table>
  </div>
</body>
</html>