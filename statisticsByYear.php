<?php
/* 연도 별 대출 건수와 대출 건수를 기준으로 한 연도 별 순위를 열람하는 페이지  */

// STATISTYEAR에 필요한 파일 include
include "config.php";
include "process.php";

// 메인 페이지로 이동하기 위한 세션 변수
$cno = $_SESSION["id"];
?>

<!-- 연도 별 대출 건수와 대출 건수를 기준으로 한 연도 별 순위를 열람하기 위한 HTML-->
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
      <h2 class="page__title">연도별 <br>대출 통계</h2>
      <table class="booklist__table">
          <thead>
              <tr>
                  <th>대출연도</th>
                  <th>대출건수</th>
                  <th>순위</th>
              </tr>
          </thead>
          <tbody>
  <?php
  // 대출기록에서 대출년도와, 년도 별 대출 건수 및 대출년도별 대출 건수를 기준으로 한 순위를 조회하는 쿼리
  $stmt = $conn -> prepare("SELECT 대출연도, 대출건수,
                            DENSE_RANK() OVER (ORDER BY 대출건수 DESC) 순위
                            FROM YEAR_RANK
                            ORDER BY 대출연도");
  $stmt ->execute();

  while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
  ?>
      <tr>
          <td><?= $row['대출연도'] ?></td>
          <td><?= $row['대출건수'] ?></a></td>
          <td><?= $row['순위'] ?></a></td>
      </tr>
  <?php
  }
  ?>
</tbody>
      </table>
  </div>
</body>
</html>