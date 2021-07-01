<?php
/* 특정 도서의 상세 정보를 열람하는 페이지 */

// DB와 연결
$dbuser = "";
$dbpass = "";
$dbname = "localhost/XE";
$db = oci_connect($dbuser, $dbpass, $dbname, 'AL32UTF8');

if (!$db) {
    echo "An error occurred connecting to the database"; 
    exit; 
}

session_start();
// 메인 페이지로 이동하기 위한 세션 변수
$main = $_SESSION["mainUrl"];
// 사용자가 클릭한 도서에 대한 정보를 조회하기 위해 GET 방식을 사용하여 ISBN을 받아와서 저장
$ISBN = $_GET['ISBN'];

// GET 방식을 사용하여 받아온 ISBN에 대한 도서의 정보를 받아오는 쿼리
$query = "SELECT EBOOK.ISBN, TITLE, PUBLISHER,
          SUBSTR(XMLAGG(XMLELEMENT(COL ,', ', AUTHOR) ORDER BY AUTHOR).EXTRACT('//text()').GETSTRINGVAL() , 2) AUTHOR, YEAR
          FROM EBOOK,AUTHORS
          WHERE EBOOK.ISBN = AUTHORS.ISBN
          AND EBOOK.ISBN LIKE '%".$ISBN."%'
          GROUP BY EBOOK.ISBN, TITLE, PUBLISHER, YEAR";
$stmt = oci_parse($db, $query);

if(!$stmt){
    echo "An error occurred in parsing the sql string.\n"; 
    exit;
}

oci_execute($stmt);
$bookName = '';
$publisher = '';
$author = '';
$year = '';

while(oci_fetch_array($stmt)){
    $bookName = oci_result($stmt,"TITLE");
    $publisher = oci_result($stmt,"PUBLISHER");
    $author = oci_result($stmt,"AUTHOR");
    $year = oci_result($stmt,"YEAR");
}
?>

<!-- 특정 도서의 상세 정보를 열람하기 위한 HTML-->
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/booklist.css" />
    <title>Book VIEW</title>
</head>
<body>
<div class="container">
    <h2 class="page__title">상세정보</h2>
    <div class="status-bar">
        <div>
            <a href="booklist.php"><div class="status">SEARCH</div></a>
        </div>
        <div>
            <a href=<?= $main ?>><div class="status">HOME</div></a>
        </div>
        <div>
            <a href="logout.php"><div class="status">LOGOUT</div></a>
        </div>
    </div>
    <table class="booklist__table">
        <tbody>
            <tr>
                <td>ISBN</td>
                <td><?= $ISBN ?></td>
            </tr>
            <tr>
                <td>서명</td>
                <td><?= $bookName ?></td>
            </tr>
            <tr>
                <td>저자</td>
                <td><?= $author ?></td>
            </tr>
            <tr>
                <td>출판사</td>
                <td><?= $publisher ?></td>
            </tr>
            <tr>
                <td>발행년도</td>
                <td><?= $year?></td>
            </tr>
            <tr>
                <td>도서상태</td>
                <?php
                // booklist.php에서 정의한 bookStatus를 GET 방식으로 전달받아 도서 상태를 파악 
                $bookStatus = $_GET['bookStatus'];
                if($bookStatus == "대출") { 
                    $bookStatus = "대출가능";
                }else{
                    $bookStatus = "대출중";
                }
                ?>
                <td><?= $bookStatus?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>