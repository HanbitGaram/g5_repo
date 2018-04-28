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

	클래스로 굳이 안짜도 되는걸 왜 이렇게 짰냐고 물으신다면, 사용자 환경에 따라 함수명 한두개는 겹치치 않을까 생각해서
	그냥 이렇게 짰으니까 태클 안걸어주시면 감사하겠습니다. ㅠㅠ
**/
	if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

	class spamOut{
		// 변수 선언. B_ 로 시작하는 변수는 꼭 필요한 변수(이하 '필수변수')로 취급한다.
		private $B_apiKey, $B_domain, $B_charset, $verifyOK, $B_userIp;
		
		// 클래스 실행
		public function __construct($apiKey = '', $domain = '', $userIp='', $B_charset='utf-8'){
			// 도메인이나 API 키 값이 없는 경우, 작동 없이 바로 종료.
			if(!trim($apiKey) || !trim($domain)) return;
			
			// API키와 도메인을 필수변수로 넣는다.
			$this->B_apiKey = $apiKey;
			$this->B_domain = $domain;
			$this->B_charset = $B_charset;
			if(!$userIp){$this->B_userIp=$_SERVER['REMOTE_ADDR'];}else{$this->B_userIp=$userIp;}

			// 키 인증을 실시한다.
			$this::verifyKey();
		}
		
		// REST API 접속 위한 cURL 함수 선언
		public function akismetRest($url = '', $method = 'get', $data = array(), $header = array()){
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			
			// header 배열의 갯수가 0이 아닌 경우..
			if(count($header) != '0'){
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			}
			
			// 폼 전송이 POST 인 경우..
			if($method == 'post'){
				$psData = http_build_query($data);
				curl_setopt($curl, CURLOPT_POST, 1); 
				curl_setopt($curl, CURLOPT_POSTFIELDS, $psData);
			}
			
			// 데이터 리턴
			$return = curl_exec($curl);
			curl_close($curl);
			return $return;
		}

		// 키 인증 받기
		public function verifyKey(){
			// 헤더 선언
			$header = array(
				'Host: rest.akismet.com',
				'Content-Type: application/x-www-form-urlencoded',
				'User-Agent: gnuboard(http://sir.kr) | Akismet/3.1.7'
			);
			$psData = array(
				'key'=>$this->B_apiKey,
				'blog'=>$this->B_domain
			);
			
			// 리턴 값 받아오기
			$return = trim($this::akismetRest('https://rest.akismet.com/1.1/verify-key', 'post', $psData, $header));
			($return == "valid")? $this->verifyOK=true : $this->verifyOK=false ;
		}

		// 코멘트 검사
		public function checkComment($wr_name, $wr_content, $is_admin, $scan_mode){
			// 키 인증을 받지 않았으면, 스팸 필터를 사용할 수 없음.
			if(!$this->verifyOK) return;

			$role = ($is_admin!='')?'administrator':'member';
			$header = array(
				'Host: '.($this->B_apiKey).'.rest.akismet.com',
				'Content-Type: application/x-www-form-urlencoded',
				'User-Agent: gnuboard(http://sir.kr) | Akismet/3.1.7'
			);
			$psData = array(
				'blog'=>$this->B_domain,
				'user_ip'=>$this->B_userIp,
				'user_agent'=>$_SERVER['HTTP_USER_AGENT'],
				'referrer'=>$_SERVER['HTTP_REFERER'],
				'permalink'=>G5_URL.$_SERVER['SCRIPT_NAME'],
				'comment_type'=>'comment', // 해당 기능을 회원가입이나 블로그 게시물에도 응용 가능합니다. https://akismet.com/development/api/#comment-check 필수 참고입니다!
				'comment_author'=>$wr_name,
				'comment_content'=>$wr_content,
				'blog_lang'=>'ko, ko_kr', // 사이트가 한글 사이트가 아닌 경우, 해당 문구를 변경해주세요.
				'blog_charset'=>$this->B_charset,
				'user_role'=>$role,
				'is_test'=>$scan_mode
			);

			$return = trim($this::akismetRest('https://rest.akismet.com/1.1/comment-check', 'post', $psData, $header));
			return ($return=='true')? true : false;
		}
	}