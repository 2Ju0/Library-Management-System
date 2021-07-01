<?php
/* 회원 별 현재까지의 대출 건수와 총 대출 건수를 열람하는 페이지 */

// STATISTICSBYMEMBER에 필요한 파일 include
include "config.php";
include "process.php";

// 메인 페이지로 이동하기 위한 세션 변수
$cno = $_SESSION["id"];
?>

<!-- 회원 별 현재까지의 대출 건수와 총 대출 건수를 열람하기 위한 HTML-->
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
      <h2 class="page__title">회원별 <br>대출 통계</h2>
      <table class="booklist__table">
          <thead>
              <tr>
                  <th>회원명</th>
                  <th>대출건수</th>
              </tr>
          </thead>
          <tbody>
  <?php
  // 대출기록에서 회원별 대출 건수와 모든 회원의 대출기록 건수의 합계를 조회하는 쿼리
  $stmt = $conn -> prepare("SELECT CASE GROUPING(C.NAME)
                            WHEN 1 THEN '대출기록합계'
                            ELSE C.NAME END AS 이름,
                            COUNT(*) AS 대출건수
                            FROM PREVIOUSRENTAL PR JOIN CUSTOMER C
                            ON PR.CNO = C.CNO
                            GROUP BY ROLLUP (C.NAME)
                            ORDER BY 이름");
  $stmt ->execute();

  while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
  ?>
      <tr>
          <td><?= $row['이름'] ?></td>
          <td><?= $row['대출건수'] ?></a></td>
      </tr>
  <?php
  }
  ?>
</tbody>
      </table>
  </div>
</body>
</html>