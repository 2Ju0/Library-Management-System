<?php
/* 대출, 연장, 반납, 예약, 예약취소 기능을 하는 함수가 정의된 페이지 */

// PROCESS에 필요한 파일 include
include "config.php";
include_once 'mail.php';
session_start();

$cno = $_SESSION["id"]; // 현재 접속중인 회원의 회원 번호 저장
$today = date("Ymd"); // 오늘 날짜
$prevPage = $_SERVER['HTTP_REFERER']; // 이전 페이지로 돌아가기 위한 변수

// 도서를 예약하는 함수
function reserve_book($isbn){
  global $conn;
  global $cno;
  global $today;
  global $prevPage;
  // 예약 횟수를 초과하지 않았는지 확인
  $count = current($conn->query("SELECT COUNT(*) FROM RESERVE WHERE CNO = $cno")->fetch());
  // 예약 횟수를 초과하지 않았다면 예약 처리
  if($count < 3){
    // RESERVE 테이블에 예약 도서의 ISBN, 회원 번호, 오늘 날짜 정보를 추가
    $stmt = $conn->prepare("INSERT INTO RESERVE VALUES (:isbn, :cno, :today)");
    $stmt ->execute(array(':isbn' => $isbn, ':cno' => $cno, ':today' => $today));
    ?>
    <script>
      alert("예약이 완료되었습니다.");
    </script>
    <?php
  }else{
    ?>
    <script>
      alert("예약 가능 도서 권수를 초과하였습니다.");
    </script>
    <?php
  }
}

// 도서를 대출하는 함수
function borrow_book($isbn){
  global $conn;
  global $cno;
  global $today;
  global $prevPage;
  $count = current($conn->query("SELECT COUNT(*) FROM EBOOK WHERE CNO = $cno")->fetch());
  // 접속한 회원이 현재까지 대출한 책이 3권 미만이라면 대출 처리
  if($count < 3){
    $stmt = $conn->prepare("UPDATE EBOOK SET CNO = :cno, DATERENTED = :dateRented, DATEDUE = :dateDue WHERE CNO IS NULL AND ISBN = $isbn");
    $dateRented = $today;
    $dateDue = date("Ymd", strtotime(date("Ymd")."+11 days"));
    $stmt ->execute(array(':cno' => $cno, ':dateRented' => $dateRented, ':dateDue' => $dateDue));
  ?>
  <script>
    alert("대출이 완료되었습니다.");
  </script>
  <?php              
  }else{?>
    <script>
      alert("대출 가능 도서 권수를 초과하였습니다.");
    </script>
  <?php }
}

// 도서의 대출 기간을 연장하는 함수
function extend_book($isbn){
  global $conn;
  global $cno;
  global $today;
  global $prevPage;
   // 이 책에 대한 예약자가 있는지 확인
   $reservedCount = current($conn->query("SELECT COUNT(*) FROM RESERVE WHERE ISBN = $isbn")->fetch());
   // 예약자가 없다면 
   if($reservedCount == 0) {
     $stmt = $conn -> prepare("SELECT EXTTIMES, EXTRACT(YEAR FROM DATEDUE) Y, 
                              EXTRACT(MONTH FROM DATEDUE) M, EXTRACT(DAY FROM DATEDUE) D
                              FROM EBOOK WHERE ISBN = $isbn");
     $stmt ->execute();
     if($row = $stmt -> fetch(PDO::FETCH_ASSOC)){
       $extTimes = $row['EXTTIMES'];
       $year = $row['Y'];
       $month = $row['M'];
       $day = $row['D'];
       $dateDue = date($year."-".$month."-".$day);
       $newDateDue = date("Ymd", strtotime($dateDue." +10 day"));
      // 연장 횟수가 2회 미만인지 확인하고 연장 처리
       if ($extTimes < 2) {
         $stmt = $conn->prepare("UPDATE EBOOK SET EXTTIMES = :newExtTimes, DATEDUE = :newDateDue WHERE ISBN = :isbn");
         $stmt ->execute(array(':newExtTimes' => $extTimes + 1, ':newDateDue' => $newDateDue, ':isbn' => $isbn));
         ?>
         <script>
           alert("연장이 완료되었습니다.");
         </script>
         <?php
        }else{
         ?>
         <script>
           alert("연장 횟수를 초과하였습니다.");
         </script>
         <?php
       }
     }
   }else{
     ?>
     <script>
       alert("다른 회원이 예약 중인 도서이므로 연장이 불가능합니다.");
     </script>
     <?php
   }
}

// 도서를 반납하는 함수
function return_book($isbn){
  global $conn;
  global $cno;
  global $today;
  global $prevPage;
      // 빌린날짜 변수에 저장
      $stmt = $conn -> prepare("SELECT DATERENTED FROM EBOOK WHERE ISBN = $isbn");
      $stmt ->execute();
      if($row = $stmt -> fetch(PDO::FETCH_ASSOC)){ $dateRented = $row['DATERENTED'];}

      // 반납처리
      $stmt = $conn->prepare("UPDATE EBOOK SET CNO = NULL, EXTTIMES = 0, DATERENTED = NULL, DATEDUE = NULL WHERE ISBN = :isbn");
      $stmt ->execute(array(':isbn' => $isbn));
      $stmt->execute();

      // 이전 대출 기록에 저장
      $stmt = $conn->prepare("INSERT INTO PREVIOUSRENTAL VALUES (:isbn, :dateRented, :dateReturned, :cno)");
      $stmt ->execute(array(':isbn' => $isbn, ':dateRented' => $dateRented,':dateReturned' => $today, ':cno' => $cno));
      
      ?>
        <script>
          alert("도서가 반납되었습니다.");
        </script>
      <?php
      // 해당 도서를 예약한 사람 수를 세는 sql문
      $count = current($conn->query("SELECT COUNT(*) FROM RESERVE WHERE ISBN = $isbn")->fetch());
      // 해당 도서를 예약한 사람이 있다면 
      if($count > 0) {
        $stmt = $conn -> prepare("SELECT *
                                  FROM RESINFO
                                  WHERE DATETIME 
                                  = (SELECT MIN(DATETIME) FROM RESINFO GROUP BY ISBN HAVING ISBN = $isbn)");
        $stmt ->execute();
        // 제일 먼저 예약한 사람에게 이메일 전송
        if($row = $stmt -> fetch(PDO::FETCH_ASSOC)){ 
          send_email($row['EMAIL'], $row['TITLE'], $row['NAME']);
        }?>
        <script>
          history.back();
        </script>
        <?php
      }
}

// 도서 예약을 취소하는 함수
function cancel_reservation($isbn){
  global $conn;
  global $cno;
  global $today;
  global $prevPage;
  // RESERVE 테이블에서 특정 도서 및 회원번호에 해당하는 데이터 삭제
  $stmt = $conn->prepare("DELETE FROM RESERVE WHERE ISBN = $isbn AND CNO = $cno");
  $stmt->execute();
  ?>
    <script>
      alert("도서 예약이 취소되었습니다.");
    </script>
  <?php
}