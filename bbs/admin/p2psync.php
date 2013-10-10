<?php
// �ݒ�
//$Mynode = 'die4game.wkeya.com/bbs18c/';
$nodes = array( 'die4game.byethost18.com/bbs/board/', 'die4game.s601.xrea.com/bbs18c/board/');
$bbs = 'board';
$PATH = '../'.$bbs.'/';
$key = '1380899186';  // �u�����e�X�g�v�X���b�h
$adminpass = '';  // makeboard.php���s�̂��߂ɊǗ��҃p�X���[�h���K�v
$proxy = '';  // �g�p���Ȃ��Ƃ��͂��̂܂�

$fp = fopen( 'synclog.txt', 'a');
foreach ( $nodes as $node) {
  fputs( $fp, time().' : '.threadSync( $bbs, $key, $node, $PATH, $adminpass, $proxy)."\n");
}
fclose( $fp);

// function threadSync( $bbs, $key, $node, $PATH, $adminpass, $proxy = '')
// �X���b�h�L�[�ƃm�[�hURL�������Ƃ��ăX���b�h��������֐�
// �菇: �ڕW�m�[�h����X���b�h���O���擾 �� ��r �� �V���E�ǉ����擾
// ���l: �폜���ꂽ�X���͋L�^������Ĉ�X�Q�Ƃ���K�v������
//       �����͈͌���̓��X�̏������ݎ��ԂłȂ�\
//      �u�O�񂩂�̍X�V�v�ōs���ꍇ�͑O��̍X�V�L�^���ǂ����ɂƂ�K�v������
//       �X���b�h���O�ɍX�V���Ԃ��L�^����ׂ���

function threadSync( $bbs, $key, $node, $PATH, $adminpass, $proxy = '') {

  // �����̃`�F�b�N
  // �L�[�͐���10���A�m�[�h�̓Z�L�����e�B�΍�����Č���I�ȕ����̂�
  if ( !preg_match( '/\d{10}/', $key))
    return '�L�[���ςł��B';
  if ( !preg_match('/^[-_.~a-zA-Z0-9\/:@]+$/', $node))
    return '�m�[�h���ςł��B';

  // �A�N�Z�X�`�F�b�N
  // �Ƃ肠�����Ahttp�ڑ��̂�
  $url = 'http://'.$node.'p2p/thread/'.$key;
  $uriInfo = new URIInfo( $url, $proxy);
  $httpcode = $uriInfo->get_httpcode();
  $contentlength = $uriInfo->get_content_length();
  if( $httpcode != '200') {
    return $url.' : �A�N�Z�X�G���[ '.$httpcode;
  } else if ( $contentlength > 73728){
    return "$url:�T�C�Y���傫�����܂��B($contentlength bytes)";
  }

  // �����[�g�m�[�h����X���b�h���O�擾
  $newthreadlog = explode( "\n", $uriInfo->getBody());
  $newthreadlog = array_map( 'trim', $newthreadlog);
  $newthreadlog = array_filter( $newthreadlog, 'strlen');
  //$newthreadlog = array_values( $newthreadlog);
  if( !$newthreadlog)
    return $url.' : �t�@�C���擾���s';

  // ���m�[�h�̃��O�ǂݍ���
  $p2p_thread_path = $PATH.'p2p/thread/'.$key;
  $new = false;
  if ( is_writable( $p2p_thread_path))
    $threadlog = array_map( 'trim', file( $p2p_thread_path));
  else if ( !is_file( $p2p_thread_path)) {
    $threadlog = array();
    $new = true;
    $fp  = fopen( $PATH."threadconf.cgi", "a");
    fwrite( $fp, $key.',,,0,0,0,0,0,0,0'."\n");
    fclose( $fp);
  } else
    return $p2p_thread_path.' : �X���b�h�ɏ������߂܂���B';

  // ���O�����擾
  $threadlogDiff = array_diff( $newthreadlog, $threadlog);
  if ( !$threadlogDiff) {
    return $url.' : �X�V�Ȃ�';
  }

  // ���X�擾
  $p2p_resHash = array();
  $p2p_res = array();
  $p2p_res_path = array();
  foreach ( $threadlogDiff as $value) {
    $p2p_resHash[] = trim( preg_replace( '/.+<>/', '', $value));
  }
  $p2p_resHash = array_unique( $p2p_resHash);
  foreach ( $p2p_resHash as $hash) {
    // ���X�t�@�C�������m�F�Ǝ擾
    $res_path = 'p2p/res/'.substr( $hash, 0, 2).'/'.$hash;
    // ���X�p�X��z��ɕۑ�
    $p2p_res_path[ $hash] = $res_path;
    // �t�@�C��������ꍇ�R���e�B�j���[
    if ( is_file( $PATH.$res_path)) continue;
    // �����[�g�Ƀt�@�C��������������T�C�Y���傫������ꍇ�X���b�h���O����폜���ăR���e�B�j���[
    $resInfo = new URIInfo( 'http://'.$node.$res_path, $proxy);
    $httpcode = $resInfo->get_httpcode();
    $contentlength = $resInfo->get_content_length();
    if( $httpcode != '200') {
      //echo $res_path.':200����Ȃ��B'.$httpcode;
      $threadlogDiff = preg_grep( "/{$hash}/", $threadlogDiff, PREG_GREP_INVERT);
      continue;
    } else if ( $contentlength > 2230){
      //echo "$res_path:�T�C�Y���傫�����܂��B($contentlength bytes)";
      $threadlogDiff = preg_grep( "/{$hash}/", $threadlogDiff, PREG_GREP_INVERT);
      continue;
    }
    // ���X�t�@�C���擾�A���s�Ȃ�X���b�h���O����폜���ăR���e�B�j���[
    $p2p_res[ $hash] = $resInfo->getBody();
    if ( !strlen( $p2p_res[ $hash])) {
      //echo $res_path.':200����Ȃ��B';
      unset( $p2p_res[ $hash]);
      $threadlogDiff = preg_grep( "/{$hash}/", $threadlogDiff, PREG_GREP_INVERT);
      continue;
    }
  }
  // �擾�ł����V�K�X���b�h���O��������ΏI��
  if ( !$threadlogDiff) return $url.' : �V�����X�擾�ł���';
  // �X���b�h���O�E���X���L�^
  $p2p_fp = fopen( $p2p_thread_path, 'a');
  flock( $p2p_fp, LOCK_EX);
  fputs( $p2p_fp, implode( "\n", $threadlogDiff)."\n");
  fclose( $p2p_fp);
  foreach ( $p2p_res as $hash=>$res) {
    $tmp = $PATH.substr( $p2p_res_path[ $hash], 0, 10);
    if ( !is_dir( $tmp))
      mkdir( $tmp);
    $p2p_fp = fopen( $PATH.$p2p_res_path[ $hash], 'w');
    flock( $p2p_fp, LOCK_EX);
    fputs( $p2p_fp, $res);
    fclose( $p2p_fp);
  }
  // dat�֋L�^
  $dattemp = $PATH.'dat/'.$key.'.dat';
  if (is_writable( $dattemp) or !is_file( $dattemp)) {
    $fp = fopen( $dattemp, "a");
    flock( $fp, LOCK_EX);
    $wday = array('��','��','��','��','��','��','�y');
    foreach ( $threadlogDiff as $log) {
      $log_array = explode( '<>', $log);
      $res = explode( '<>', trim( file_get_contents( $PATH.$p2p_res_path[ $log_array[ 2]])));
      $today = getdate( $log_array[ 0]);
      $DATE = date("Y/m/d(", $log_array[ 0]).$wday[$today[ 'wday']].date(") H:i:s", $log_array[ 0]);
      $outdat = implode( '<>', array( $res[0], $res[1], $DATE.' '.$log_array[1], $res[2], $res[3]."\n"));
      fputs( $fp, $outdat);
    }
    fclose( $fp);
  }

  // html�t�@�C���쐬
  $subtt = dat2html( $PATH, $bbs, $key, $dattemp);

  // subject.txt�̍X�V
  $sage = 1;
  renew_subject_txt( $PATH, $key, $sage, true, $subtt);

  // index.html�̍X�V
  $_GET['mode'] = 'remake';
  $_GET['bbs'] = $bbs;
  $_GET['check'] = 'check';
  $_COOKIE['adminpass'] = $adminpass;
  $_REQUEST['bbs'] = $bbs;
  require( 'makeboard.php');
}

//------------------------
// URI�����擾����N���X
//------------------------
class URIInfo {
  public $info;
  public $body;
  private $url;
  private $ch;
  private $cookie;

  function __construct( $url = '', $proxy = '', $cookie = '') {
    $this->url = $url;
    $this->proxyurl = $proxy;
    $this->cookie = $cookie;
    $this->setData();
  }

  function __destruct() {
    curl_close( $this->ch);
  }

  public function setData() {
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_URL, $this->url);
    curl_setopt($this->ch, CURLOPT_NOBODY, true);
    if ( $this->proxyurl != '') {
      curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, 1);
      curl_setopt($this->ch, CURLOPT_PROXY, $this->proxyurl);
      //curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, "anonymous:");
    }
    curl_setopt( $this->ch, CURLOPT_COOKIE, $this->cookie);
    curl_setopt( $this->ch, CURLOPT_USERAGENT, 'admin-php');
    curl_exec($this->ch);
    $this->info = curl_getinfo($this->ch);
  }

  public function getBody() {
    curl_setopt($this->ch, CURLOPT_NOBODY, false);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    $this->body = curl_exec($this->ch);
    $this->info = curl_getinfo($this->ch);
    return $this->body;
  }

  public function get_httpcode() {
    return $this->info['http_code'];
  }

  public function get_content_length() {
    return $this->info['download_content_length'];
  }
}

// // curl���g���Ȃ��Ƃ��p
// //------------------------
// // URI�����擾����N���X
// //------------------------
// class URIInfo {
//   public $info;
//   public $body;
//   private $url;
//   private $ch;
//   private $cookie;
// 
//   function __construct( $url = '', $proxy = '', $cookie = '') {
//     $this->url = $url;
//     $this->proxyurl = $proxy;
//     $this->cookie = $cookie;
//     $this->setData();
//   }
// 
//   public function setData() {
//     $default_opts = array(
//       'http' => array(
//         'method' => 'HEAD',
//         'header' =>
//           "Cookie: {$this->cookie}\n".
//           "User-Agent: admin-php\n".
//           "Accept-Language:ja,en-US;q=0.8,en;q=0.6",
//         'proxy' => $this->proxyurl,
//         'max_redirects' => 1
//       )
//     );
//     stream_context_get_default( $default_opts);
//     $this->info = get_headers( $this->url, 1);
//   }
// 
//   public function getBody() {
//     $default_opts = array(
//       'http' => array(
//         'method' => 'GET',
//         'header' =>
//           "Cookie: {$this->cookie}\n".
//           "User-Agent: admin-php\n".
//           "Accept-Language:ja,en-US;q=0.8,en;q=0.6",
//       )
//     );
//     $context = stream_context_create( $default_opts);
//     $this->body = file_get_contents( $this->url, false, $context, 0, $this->get_content_length());
//     return $this->body;
//   }
// 
//   public function get_httpcode() {
//     return preg_filter( '/(HTTP\/\d\.\d )(\d\d\d)(.*)/', '$2', $this->info[0]);
//   }
// 
//   public function get_content_length() {
//     return $this->info['Content-Length'];
//   }
// }


//----------------------------------------
// .dat�t�@�C���ɑ΂���.html�t�@�C���̍쐬
//----------------------------------------
function dat2html( $PATH, $bbs, $key, $dattemp) {
  // �ݒ�t�@�C����ǂݍ���------------
  // SETTING.TXT
  $set_file = $PATH . "SETTING.TXT";
  if (is_file($set_file)) {
    $set_str = file($set_file);
    foreach ($set_str as $tmp){
      $tmp = trim($tmp);
      list ($name, $value) = explode("=", $tmp);
      $SETTING[$name] = $value;
    }
  }
  //config.php
  require $PATH.'config.php';
  // vip�@�\
  $threadconf  = file($PATH."threadconf.cgi");
  $vip = explode( ',', implode( preg_grep( "/{$key}/", $threadconf)));
  //-----------------------------------

  $workfile = "$PATH/html/$key.html";

  if (is_file($dattemp)) {
    $logopen = file($dattemp);
    $lognum = count($logopen);
  }
  else {
    $logopen = array();
    $lognum = 0;
  }
  // 1��������o��
  $logfirst = array_shift($logopen);
  // �\�����郌�X���������o��
  $logopen = array_slice($logopen, -$SETTING['BBS_CONTENTS_NUMBER']);
  // 1�̎��ɕ\�����郌�X�ԍ�
  $topnum = $lognum - count($logopen) + 1;
  //�P�ڂ̗v�f�����H����
  $logfirst = rtrim($logfirst);
  list ($name,$mail,$date,$message,$subject) = explode ("<>", $logfirst);
  $logsub = $subject;
  //�T�u�W�F�N�g�e�[�u����f���o���i�����͕K���P�s�ɂ܂Ƃ߂邱�Ɓi���������j�j
  $logall = '<table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="'.$SETTING['BBS_THREAD_COLOR'].'" align="center"><tr><td><dl><a name="$ANCOR"></a><div align="right"><a href="#menu">��</a><a href="#$FRONT">��</a><a href="#$NEXT">��</a></div><b>�y$ANCOR:'.$lognum.'�z<font size="5" color="'.$SETTING['BBS_SUBJECT_COLOR']."\">$subject</font></b>\n";
  //�P�ڂ̃����N���쐬
  $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
  //���X�|���X�A���J�[�i�{���j
  $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
  $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
  //���O���̕ϊ�
  if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
  else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
  //�P�ڂ̗v�f��f���o��
  $logall .= " <dt>1 ���O�F$mailto $date<dd>$message <br><br><br>\n";
  //�c��̃��O��\������
  foreach ($logopen as $tmp){
    //�v�f�����H����
    $tmp = rtrim($tmp);
    list ($name,$mail,$date,$message,$subject) = explode ("<>", $tmp);
    //�����N���쐬
    $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
    //���X�|���X�A���J�[�i�{���j
    $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
    $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
    //���O���̕ϊ�
    if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
    else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
    //�v�f��f���o��
    $logall .= " <dt>$topnum ���O�F$mailto �F$date<dd>";
    // 0thello�X���b�h�͑S���\��
    if ($vip[8]) $logall .= $message;
    else {
      $messx = explode ("<br>", $message);
      for ($i = 1; $i <= $SETTING['BBS_LINE_NUMBER']; $i++) {
        if ($messx) {
          $logall .= array_shift($messx);
          $logall .= "<br>";
        }
      }
      if ($messx) {
        $logall .= "<font color=\"$SETTING[BBS_NAME_COLOR]\">�i�ȗ�����܂����E�E�S�Ă�ǂނɂ�<a href=\"../test/read.php/$bbs/$key/$topnum\" target=\"_blank\">����</a>�������Ă��������j</font><br>";
      }
    }
    $logall .= "<br>\n";
    $topnum++;
  }
  $fp = fopen($workfile, "w");
  fputs($fp, $logall);
  fclose($fp);
  return "$logsub ($lognum)";
}

//----------------------
// subject.txt���X�V����
//----------------------
function renew_subject_txt( $PATH, $key, $sage, $new, $subtt) {
  $subjectfile = $PATH.'subject.txt';
  $DATPATH = $PATH.'dat/';
  $TEMPPATH  = $PATH.'html/';
  $keyfile = $key.'.dat';
  $PAGEFILE = array();
  // �T�u�W�F�N�g�t�@�C����ǂݍ���
  // �X���b�h�L�[.dat<>�^�C�g�� (���X�̐�)\n
  // $PAGEFILE = array('�X���b�h�L�[.dat',�E�E�E)
  // $SUBJECT = array('�X���b�h�L�[.dat'=>'�^�C�g�� (���X�̐�)',�E�E�E)
  $subr = @file( $subjectfile);
  if ( $subr) {
    foreach ( $subr as $tmp){
      $tmp = rtrim( $tmp);
      list( $file, $value) = explode("<>", $tmp);
      if ( !$file) break;
      $PAGEFILE[] = $file;
      $SUBJECT[ $file] = $value;
    }
  }
  // �T�u�W�F�N�g�����擾
  $FILENUM = count( $PAGEFILE);
  // �V�K�X���b�h�̏ꍇ��1�ǉ�
  if ( $new) $FILENUM++;
  // ���O��萔�ɑ�����
  if ( $FILENUM > KEEPLOGCOUNT) {
    for ( $start = KEEPLOGCOUNT; $start < $FILENUM; $start++) {
      $delfile = $DATPATH . $PAGEFILE[$start];
      // dat�t�@�C���폜
      unlink( $delfile);
      $key = str_replace( '.dat', '', $PAGEFILE[$start]);
      $delfile = $TEMPPATH . $key . ".html";
      // html�t�@�C���폜
      @unlink( $delfile);
      if ( $dir = @opendir( $IMGPATH)) {
        while (( $file = readdir( $dir)) !== false) {
          // �摜�t�@�C���폜
          if ( strpos( $file, $key) === 0) unlink( $IMGPATH.$file);
        }  
        closedir( $dir);
      }
      if ( $dir = @opendir( $IMGPATH2)) {
        while (( $file = readdir( $dir)) !== false) {
          // �T���l�C���摜�t�@�C���폜
          if ( strpos( $file, $key) === 0) unlink( $IMGPATH2.$file);
        }  
        closedir( $dir);
      }
    }
    $FILENUM = KEEPLOGCOUNT;
    $PAGEFILE = array_slice( $PAGEFILE, 0, $FILENUM);
  }
  $subtm = "$keyfile<>$subtt";
  // �T�u�W�F�N�g�n�b�V��������������
  $SUBJECT[ $keyfile] = $subtt;
  // �T�u�W�F�N�g�e�L�X�g���J��
  $fp = @fopen( $subjectfile, "w");
  //�ꊇ��������
  // sage�̎��͏オ��Ȃ�
  if ( !$new and $sage) {
    foreach ($PAGEFILE as $tmp){
      fputs($fp, "$tmp<>$SUBJECT[$tmp]\n");
    }
  }
  else {
    // �オ��L�[�͈�ԍŏ��Ɏ����Ă���
    $temp[0] = $keyfile;
    $i = 1;
    fputs($fp, "$subtm\n");
    foreach ($PAGEFILE as $tmp) {
      // keyfile�͌��ݏ������݂����X���b�h�L�[�i�オ���Ă���j
      if ($tmp != $keyfile) {
        $temp[$i] = $tmp;
        $i++;
        fputs($fp, "$tmp<>$SUBJECT[$tmp]\n");
      }
    }
    $PAGEFILE = $temp;
  }
  fclose($fp);
}

?>