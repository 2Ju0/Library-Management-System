<?php
/* 자동 반납 처리 및 예약한 도서에 대해 대출 가능 이메일을 받은 사용자가 다음 날까지 도서를 반납하지 않은 경우 예약 취소 처리
이때, 모든 경우에 이메일 전송 */

// CHECK에 필요한 파일 include
include "config.php";
include "mail.php";
include "process.php";

/* 1. 반납 기일이 도래한 도서에 대하여 자동 반납 처리 */
//EBOOK 테이블에서 현재 대출 중인 도서에 대한 정보를 조회하는 쿼리
$stmt = $conn -> prepare("SELECT ISBN, DATEDUE, EXTRACT(YEAR FROM DATEDUE) Y, EXTRACT(MONTH FROM DATEDUE) M, EXTRACT(DAY FROM DATEDUE) D
                        FROM EBOOK
                        WHERE CNO IS NOT NULL");
$stmt ->execute();
while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
    $year = $row['Y']; // 반납 예정 일의 년도
    $month = $row['M']; // 반납 예정 일의 월
    $day = $row['D']; // 반납 예정 일의 일
    
    // 날짜의 format을 맞춰주기 위함
    $date = date($year."-".$month."-".$day); 
    $date = date("Ymd", strtotime($date." +0 day"));
    $isbn = $row['ISBN'];

    if($date <= $today) { 
      // 빌린날짜 변수에 저장
      $stmt2 = $conn -> prepare("SELECT DATERENTED FROM EBOOK WHERE ISBN = $isbn");
      $stmt2 ->execute();
      if($row = $stmt2 -> fetch(PDO::FETCH_ASSOC)){ $dateRented = $row['DATERENTED'];}

      // 반납처리
      $stmt3 = $conn->prepare("UPDATE EBOOK SET CNO = NULL, EXTTIMES = 0, DATERENTED = NULL, DATEDUE = NULL WHERE ISBN = :isbn");
      $stmt3 ->execute(array(':isbn' => $isbn));

      // 이전 대출 기록에 저장
      $stmt4 = $conn->prepare("INSERT INTO PREVIOUSRENTAL VALUES (:isbn, :dateRented, :dateReturned, :cno)");
      $stmt4 ->execute(array(':isbn' => $isbn, ':dateRented' => $dateRented,':dateReturned' => $today, ':cno' => $cno));

      // 해당 도서를 예약한 사람 수를 세는 쿼리
      $count = current($conn->query("SELECT COUNT(*) FROM RESERVE WHERE ISBN = $isbn")->fetch());
      
      // 해당 도서를 예약한 사람이 있다면 제일 먼저 예약한 사람에게 이메일 전송
      if($count > 0) {
        // RESINFO 뷰에서 특정 isbn 및, 예약 일의 최소 값에 해당하는 회원의 정보를 조회하는 쿼리
        $stmt5 = $conn -> prepare("SELECT *
                                  FROM RESINFO
                                  WHERE DATETIME = (SELECT MIN(DATETIME)
                                  FROM RESINFO
                                  GROUP BY ISBN
                                  HAVING ISBN = $isbn)");
        $stmt5 ->execute();
        if($row = $stmt5 -> fetch(PDO::FETCH_ASSOC)){ 
          // 이메일을 전송하는 함수를 호출
          send_email($row['EMAIL'], $row['TITLE'], $row['NAME']);
        }
        ?>
        <script>
          history.back();
        </script>
        <?php
      }
    }
}
?>
<script>
  alert("모든 도서가 자동 반납 처리 되었습니다.");
</script>
<?php

/* 2. 다음 날까지 대출하지 않은 도서에 대한 예약 취소 및 다음 예약자에게 메일 발송 처리 */
$today = date("Ymd"); // 오늘 날짜를 저장하는 변수
// RESERVE 테이블에서 도서 별로 가장 먼저 예약한 도서에 대한 정보를 조회하는 쿼리
$stmt6 = $conn -> prepare("SELECT ISBN, MIN(datetime) MINDATE, EXTRACT(YEAR FROM MIN(datetime)) Y, 
                        EXTRACT(MONTH FROM MIN(datetime)) M, EXTRACT(DAY FROM MIN(datetime)) D 
                        FROM RESERVE GROUP BY ISBN");
$stmt6 ->execute();
while ($row = $stmt6 -> fetch(PDO::FETCH_ASSOC)) {
    $isbn =  $row['ISBN'];
    $year = $row['Y'];
    $month = $row['M'];
    $day = $row['D'];
    $minDate = date($year."-".$month."-".$day);
    $minDate = date("Ymd", strtotime($minDate." +0 day"));

    // 예약 되어있는 도서가 현재 대출 중인지 확인하는 쿼리
    $count1 = current($conn->query("SELECT COUNT(*) FROM EBOOK WHERE ISBN = $isbn AND CNO IS NULL")->fetch());
    // 해당 도서가 대출 중이 아니라면 기간 확인
    if($count1 > 0) {
        // 이전 대출 기록에서 해당 isbn에 대한 가장 최근 반납 기록을 조회하는 쿼리
        $stmt7 = $conn -> prepare("SELECT EXTRACT(YEAR FROM MAX(DATERETURNED)) Y, 
                                  EXTRACT(MONTH FROM MAX(DATERETURNED)) M, EXTRACT(DAY FROM MAX(DATERETURNED)) D
                                  FROM PREVIOUSRENTAL 
                                  WHERE ISBN = $isbn 
                                  GROUP BY ISBN");
        $stmt7 ->execute();
        if($row = $stmt7 -> fetch(PDO::FETCH_ASSOC)){
            $year = $row['Y']; // 반납 일의 년도
            $month = $row['M']; // 반납 일의 월
            $day = $row['D']; // 반납 일의 일
            $recentDate = date($year."-".$month."-".$day); // 날짜의 format을 맞춰주기 위함
            $term = date("Ymd", strtotime($recentDate." +2 day")); // 반납 일에 2일 을 더함
        }
        // 다음 날 까지 빌리지 않았으므로 예약 취소 + 다음 예약자에게 메일 발송
        if($term <= $today) {
            $stmt8 = $conn->prepare("DELETE FROM RESERVE WHERE ISBN = $isbn AND DATETIME = TO_DATE($minDate)");
            $stmt8->execute();
            // 해당 도서를 예약한 사람 수를 세는 쿼리
            $count2 = current($conn->query("SELECT COUNT(*) FROM RESERVE WHERE ISBN = $isbn")->fetch());
            // 해당 도서를 예약한 사람이 있다면 
            if($count2 > 0) {
                // RESINFO 뷰에서 특정 isbn 및, 예약 일의 최소 값에 해당하는 회원의 정보를 조회하는 쿼리
                $stmt9 = $conn -> prepare("SELECT *
                                          FROM RESINFO
                                          WHERE DATETIME = 
                                          (SELECT MIN(DATETIME) FROM RESINFO GROUP BY ISBN HAVING ISBN = $isbn)");
                $stmt9 ->execute();
                // 제일 먼저 예약한 사람에게 이메일 전송
                if($row = $stmt9 -> fetch(PDO::FETCH_ASSOC)){ 
                    send_email($row['EMAIL'], $row['TITLE'], $row['NAME']);
                }
                ?>
                <script>
                  history.back();
                </script>
                <?php   
            }
        }
    }
}
?>