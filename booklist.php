<?php
/* 도서 검색 및 모든 도서의 정보를 열람하는 페이지*/

// BOOKLIST에 필요한 파일 include
include "config.php"; 
include 'process.php';

// 메인 페이지로 이동하기 위한 세션 변수
$main = $_SESSION["mainUrl"];

// 검색 카테고리별 키워드를 입력받아 저장하는 변수 정의 및 초기화
$searchTitle = $_GET['searchTitle'] ?? '';
$searchAuthor = $_GET['searchAuthor'] ?? '';
$searchPublisher = $_GET['searchPublisher'] ?? '';
$searchStartYear = $_GET['searchStartYear'];
$searchEndYear = $_GET['searchEndYear'];
$statusBtn = $_POST['statusBtn'] ?? '';

// 검색 연사자가 NULL인 경우 AND, NOT인 경우 AND NOT으로 치환
$opt0 = (($_GET['searchOpt0'] == "NOT") ? "AND NOT" : $_GET['searchOpt0']) ?? "AND";
$opt1 = (($_GET['searchOpt1'] == "NOT") ? "AND NOT" : $_GET['searchOpt1']) ?? "AND";
$opt2 = (($_GET['searchOpt2'] == "NOT") ? "AND NOT" : $_GET['searchOpt2']) ?? "AND";

// 이후 검색 연사자 정렬을 위해 GET을 통해 받은 검색 조건 변수를 배열에 저장
$opts_arr = array();
array_push($opts_arr, $opt0);
array_push($opts_arr, $opt1);
array_push($opts_arr, $opt2);

$a0 = "$opt0 AUTHOR LIKE '%' || :searchAuthor || '%'";
$a1 = "$opt1 PUBLISHER LIKE '%' || :searchPublisher || '%'";
$a2 = "$opt2 YEAR BETWEEN NVL(:searchStartYear, 2000) AND NVL(:searchEndYear, 2021)";

// NOT 조건을 WHERE 절의 마지막에 걸어주기 위해 AND NOT은 배열의 뒤에 삽입, 나머지는 앞에 삽입
$result_arr = array();
for ($i = 0; $i < 3; $i++) {
  if ($opts_arr[$i] === "AND NOT") { 
    array_push($result_arr, ${"a".$i});
  }else{
    array_unshift($result_arr, ${"a".$i});
  }
}

// 버튼의 종류에 해당하는 함수를 호출
if ($statusBtn == "대출") {
  borrow_book($_POST['ISBN']);
}elseif($statusBtn == "예약") {
  reserve_book($_POST['ISBN']);
}elseif($statusBtn == "반납") {
  return_book($_POST['ISBN']);
}

// 책의 정보, 상태 및 접속한 사용자의 정보를 저장하기 위한 변수
$curr_isbn = '';
$curr_customer = '';
$bookStatus = '';
$isDisabled = '';
?>

<!-- 도서 검색 및 모든 도서 정보 열람을 위한 HTML-->
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/booklist.css" />
  <title>도서검색</title>
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
      <h2 class="page__title">도서 검색</h2>
      <form class="search__container" method = 'GET'>
          <div class = "col">
              <div class = "search__label">서명</div>
              <input type="text" class ="form-control" id="searchTitle" name="searchTitle" value="<?= $searchTitle ?>">
            <select name="searchOpt0">
              <option value="AND">AND</option>
              <option value="OR">OR</option>
              <option value="NOT">NOT</option>
            </select>
          </div>
          <div class = "col">
              <div class = "search__label">저자</div>
              <input type="text" class ="form-control" id="searchAuthor" name="searchAuthor"value="<?= $searchAuthor ?>">
              <select name="searchOpt1">
                <option value="AND">AND</option>
                <option value="OR">OR</option>
                <option value="NOT">NOT</option>
              </select>
          </div>
          <div class = "col col__year">
              <div class = "search__label">발행연도</div>
              <input type="text" class ="form-control" id="searchStartYear" name="searchStartYear" placeholder="발행년 검색범위 시작일" value="<?= $searchStartYear ?>">
              <input type="text" class ="form-control" id="searchEndYear" name="searchEndYear" placeholder="발행년 검색범위 종료일" value="<?= $searchEndYear ?>">
              <select name="searchOpt2">
                <option value="AND">AND</option>
                <option value="OR">OR</option>
                <option value="NOT">NOT</option>
              </select>
          </div>
          <div class = "col col__publisher">
              <div class = "search__label">출판사</div>
              <input type="text" class ="form-control" id="searchPublisher" name="searchPublisher" value="<?= $searchPublisher ?>">
              <div class = "col-auto text-end">
                <button type = "submit" class="search__btn">검색</button>
              </div>
          </div>
      </form>

      <table class="booklist__table">
        <thead>
              <tr>
                  <th>ISBN</th>
                  <th>서명</th>
                  <th>저자</th>
                  <th>출판사</th>
                  <th>도서상태</th>
              </tr>
          </thead>
        <tbody>
          <?php
          // 검색 키워드 및 검색 연사자를 통해 해당하는 책의 정보를 조회하는 쿼리
          $stmt = $conn -> prepare("SELECT EBOOK.ISBN, TITLE, DATEDUE, YEAR, PUBLISHER, 
                                    SUBSTR(XMLAGG(XMLELEMENT(COL ,', ', AUTHOR) ORDER BY AUTHOR).EXTRACT('//text()').GETSTRINGVAL() , 2) AUTHOR
                                    FROM EBOOK, AUTHORS
                                    WHERE (TITLE LIKE '%' || :searchTitle || '%'
                                    $result_arr[0] $result_arr[1] $result_arr[2])
                                    AND EBOOK.ISBN = AUTHORS.ISBN
                                    GROUP BY EBOOK.ISBN, TITLE, PUBLISHER, DATEDUE, YEAR
                                    ORDER BY ISBN");
          $stmt ->execute(array(':searchTitle' => $searchTitle, 
                                ':searchAuthor' => $searchAuthor, 
                                ':searchPublisher' => $searchPublisher, 
                                ':searchStartYear' => $searchStartYear,
                                ':searchEndYear' => $searchEndYear));

          while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
          ?>
          <tr>
              <?php
                $curr_isbn = $row['ISBN'];
                $curr_customer = $_SESSION["id"];
                // 접속 중인 회원이 대출 중인 책인지 확인하기 위한 쿼리
                $borrowed_count = current($conn->query("SELECT COUNT(*) FROM EBOOK WHERE CNO = $curr_customer AND ISBN = $curr_isbn")->fetch());
                // 접속 중인 회원이 예약 중인 책인지 확인하기 위한 쿼리
                $reserved_count = current($conn->query("SELECT COUNT(*) FROM RESERVE WHERE CNO = $curr_customer AND ISBN = $curr_isbn")->fetch());
                // 대출 가능한 책인지 확인하는 쿼리 (값이 1이라면 대출 가능)
                $isBorrowed = current($conn->query("SELECT COUNT(*) FROM EBOOK WHERE ISBN = $curr_isbn AND CNO IS NULL")->fetch());
                
                // 위 쿼리를 이용하여 책의 상태 정의 및 button의 disabled 속성을 결정
                if($borrowed_count == '1') {
                      $bookStatus = '반납';
                      $isDisabled = FALSE;
                  }elseif ($reserved_count == '1') {
                      $bookStatus = '예약중';
                      $isDisabled = TRUE;
                  }elseif ($isBorrowed == '1') {
                      $bookStatus = '대출';
                      $isDisabled = FALSE;
                  }else {
                      $bookStatus = '예약';
                      $isDisabled = FALSE;
                  }
              ?>
              <td><?= $row['ISBN'] ?></td>
              <td><a href="bookview.php?ISBN=<?= $row['ISBN'] ?>&bookStatus=<?= $bookStatus ?>"><?= $row['TITLE'] ?></a></td>
              <td><?= $row['AUTHOR'] ?></td>
              <td><?= $row['PUBLISHER'] ?></td>
              <td>
                <form method="POST" class="row">
                <input type="hidden" name="ISBN" value="<?= $row['ISBN'] ?>">
                <?php 
                  // isDisabled 변수가 TRUE 라면 비활성화 속성을 추가
                  if($isDisabled) {
                ?><button type="submit" name= "statusBtn" value="<?= $bookStatus ?>" class="btn" disabled><?= $bookStatus ?></button>
                <?php }else{
                  ?><button type="submit" name="statusBtn" value="<?= $bookStatus ?>" class="btn"><?= $bookStatus ?></button>
                <?php } ?>
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