<?php
/* 로그아웃하는 페이지 */
// 세션을 초기화
session_start();
 
// 모든 세션 변수 설정 해제
$_SESSION = array();
 
// 세션 파괴
session_destroy();
 
// 로그인 페이지로 이동
header("location: login.php");
exit;
?>
