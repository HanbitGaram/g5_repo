<?php
/**
	PRODUCT NAME : GNUBOARD SPAM OUT PLUGIN (https://github.com/HanbitGaram/g5_repo/spamout)
	PRODUCT MAKER : HANBITGARAM (http://hanb.jp, http://idongmai.wo.tc, http://blog.hanb.jp)
	LICENSE : GNU General Public License v3.0
	
	본 플러그인은 오픈소스입니다. 마음대로 사용/수정/가공하셔도 무방하나, 제작자는 소스상에서 밝혀주시길 부탁드립니다.
	본 플러그인에 사용된 스팸방지 필터는 Akismet(https://akismet.com)입니다.
	호스팅에서 사용시 국제트래픽에 유의하여 사용하시길 권장드립니다.

	본 플러그인 사용시 필요한 PHP 확장 프로그램은 아래와 같습니다.
		=> PHP-cURL 플러그인 (Akismet 서버와 통신하기 위하여 사용)
		=> PHP-cURL 플러그인 中 - https 속성 (SSL 서버통신)

	본 파일은 '스팸아웃! 플러그인'의 라이브러리 파일입니다. 주석에 따라서 프로그램을 수정하시거나 가공하시면 됩니다.
	응용하시면, 회읜가입에서 스팸계정을 막는데에도 사용하실 수 있습니다.

	응용할 수 있게 제공되는 페이지 - https://akismet.com/development/api/#comment-check

	본 플러그인을 사용하시려면 Akismet(https://akismet.com)의 서비스에 워드프레스닷컴 아이디로 가입되어 있어야합니다.
**/
	if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

	// cURL을 사용할 수 없는 경우, 파일 로드를 중단한다.
	// 혹은 댓글 쓰기 페이지가 아닌 경우, 파일 로드를 중단한다.
	if(!function_exists('curl_init') || $_SERVER['SCRIPT_NAME']!='/'.G5_BBS_DIR.'/write_comment_update.php') return;

	// 필수 클래스 파일 로드
	include_once G5_PLUGIN_PATH."/spamout/spamout.class.php";
	
	// 커스텀 설정
	$Akismet = array();
	$Akismet['key'] = ''; // Akismet 에서 발급받은 인증키
	$Akismet['url'] = G5_URL; // Akismet 에 등록한 URL
	$Akismet['wr_name'] = ($member['mb_name'])? $member['mb_name'] : $mb_name ;
	$Akismet['content'] = $wr_content;
	$Akismet['test'] = true; // TRUE로 설정하면 테스트 모드, FALSE 로 설정하면 실제 탐지 모드

	$spmChk = new spamOut($Akismet['key'], $Akismet['url']);
	$isSpam = $spmChk->checkComment($Akismet['wr_name'], $Akismet['content'], $is_admin, $Akismet['test']);
	if($isSpam==true){
		alert("해당 댓글을 등록할 수 없습니다.\\n스팸필터에 의하여 댓글이 스팸으로 판단되었습니다.");
	}