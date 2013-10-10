<?php
// 設定
//$Mynode = 'die4game.wkeya.com/bbs18c/';
$nodes = array( 'die4game.byethost18.com/bbs/board/', 'die4game.s601.xrea.com/bbs18c/board/');
$bbs = 'board';
$PATH = '../'.$bbs.'/';
$key = '1380899186';  // 「同期テスト」スレッド
$adminpass = '';  // makeboard.php実行のために管理者パスワードが必要
$proxy = '';  // 使用しないときはこのまま

$fp = fopen( 'synclog.txt', 'a');
foreach ( $nodes as $node) {
  fputs( $fp, time().' : '.threadSync( $bbs, $key, $node, $PATH, $adminpass, $proxy)."\n");
}
fclose( $fp);

// function threadSync( $bbs, $key, $node, $PATH, $adminpass, $proxy = '')
// スレッドキーとノードURLを引数としてスレッド同期する関数
// 手順: 目標ノードからスレッドログを取得 → 比較 → 新着・追加を取得
// 備考: 削除されたスレは記録を取って一々参照する必要がある
//       同期範囲限定はレスの書きこみ時間でなら可能
//      「前回からの更新」で行う場合は前回の更新記録をどこかにとる必要がある
//       スレッドログに更新時間も記録するべきか

function threadSync( $bbs, $key, $node, $PATH, $adminpass, $proxy = '') {

  // 引数のチェック
  // キーは数字10桁、ノードはセキュリティ対策も兼て限定的な文字のみ
  if ( !preg_match( '/\d{10}/', $key))
    return 'キーが変です。';
  if ( !preg_match('/^[-_.~a-zA-Z0-9\/:@]+$/', $node))
    return 'ノードが変です。';

  // アクセスチェック
  // とりあえず、http接続のみ
  $url = 'http://'.$node.'p2p/thread/'.$key;
  $uriInfo = new URIInfo( $url, $proxy);
  $httpcode = $uriInfo->get_httpcode();
  $contentlength = $uriInfo->get_content_length();
  if( $httpcode != '200') {
    return $url.' : アクセスエラー '.$httpcode;
  } else if ( $contentlength > 73728){
    return "$url:サイズが大きすぎます。($contentlength bytes)";
  }

  // リモートノードからスレッドログ取得
  $newthreadlog = explode( "\n", $uriInfo->getBody());
  $newthreadlog = array_map( 'trim', $newthreadlog);
  $newthreadlog = array_filter( $newthreadlog, 'strlen');
  //$newthreadlog = array_values( $newthreadlog);
  if( !$newthreadlog)
    return $url.' : ファイル取得失敗';

  // 自ノードのログ読み込み
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
    return $p2p_thread_path.' : スレッドに書きこめません。';

  // ログ差分取得
  $threadlogDiff = array_diff( $newthreadlog, $threadlog);
  if ( !$threadlogDiff) {
    return $url.' : 更新なし';
  }

  // レス取得
  $p2p_resHash = array();
  $p2p_res = array();
  $p2p_res_path = array();
  foreach ( $threadlogDiff as $value) {
    $p2p_resHash[] = trim( preg_replace( '/.+<>/', '', $value));
  }
  $p2p_resHash = array_unique( $p2p_resHash);
  foreach ( $p2p_resHash as $hash) {
    // レスファイル所持確認と取得
    $res_path = 'p2p/res/'.substr( $hash, 0, 2).'/'.$hash;
    // レスパスを配列に保存
    $p2p_res_path[ $hash] = $res_path;
    // ファイルがある場合コンティニュー
    if ( is_file( $PATH.$res_path)) continue;
    // リモートにファイルが無かったりサイズが大きすぎる場合スレッドログから削除してコンティニュー
    $resInfo = new URIInfo( 'http://'.$node.$res_path, $proxy);
    $httpcode = $resInfo->get_httpcode();
    $contentlength = $resInfo->get_content_length();
    if( $httpcode != '200') {
      //echo $res_path.':200じゃない。'.$httpcode;
      $threadlogDiff = preg_grep( "/{$hash}/", $threadlogDiff, PREG_GREP_INVERT);
      continue;
    } else if ( $contentlength > 2230){
      //echo "$res_path:サイズが大きすぎます。($contentlength bytes)";
      $threadlogDiff = preg_grep( "/{$hash}/", $threadlogDiff, PREG_GREP_INVERT);
      continue;
    }
    // レスファイル取得、失敗ならスレッドログから削除してコンティニュー
    $p2p_res[ $hash] = $resInfo->getBody();
    if ( !strlen( $p2p_res[ $hash])) {
      //echo $res_path.':200じゃない。';
      unset( $p2p_res[ $hash]);
      $threadlogDiff = preg_grep( "/{$hash}/", $threadlogDiff, PREG_GREP_INVERT);
      continue;
    }
  }
  // 取得できた新規スレッドログが無ければ終了
  if ( !$threadlogDiff) return $url.' : 新着レス取得できず';
  // スレッドログ・レスを記録
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
  // datへ記録
  $dattemp = $PATH.'dat/'.$key.'.dat';
  if (is_writable( $dattemp) or !is_file( $dattemp)) {
    $fp = fopen( $dattemp, "a");
    flock( $fp, LOCK_EX);
    $wday = array('日','月','火','水','木','金','土');
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

  // htmlファイル作成
  $subtt = dat2html( $PATH, $bbs, $key, $dattemp);

  // subject.txtの更新
  $sage = 1;
  renew_subject_txt( $PATH, $key, $sage, true, $subtt);

  // index.htmlの更新
  $_GET['mode'] = 'remake';
  $_GET['bbs'] = $bbs;
  $_GET['check'] = 'check';
  $_COOKIE['adminpass'] = $adminpass;
  $_REQUEST['bbs'] = $bbs;
  require( 'makeboard.php');
}

//------------------------
// URI情報を取得するクラス
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

// // curlが使えないとき用
// //------------------------
// // URI情報を取得するクラス
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
// .datファイルに対する.htmlファイルの作成
//----------------------------------------
function dat2html( $PATH, $bbs, $key, $dattemp) {
  // 設定ファイルを読み込む------------
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
  // vip機能
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
  // 1さんを取り出し
  $logfirst = array_shift($logopen);
  // 表示するレス数だけ取り出し
  $logopen = array_slice($logopen, -$SETTING['BBS_CONTENTS_NUMBER']);
  // 1の次に表示するレス番号
  $topnum = $lognum - count($logopen) + 1;
  //１つ目の要素を加工する
  $logfirst = rtrim($logfirst);
  list ($name,$mail,$date,$message,$subject) = explode ("<>", $logfirst);
  $logsub = $subject;
  //サブジェクトテーブルを吐き出す（ここは必ず１行にまとめること（処理効率））
  $logall = '<table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="'.$SETTING['BBS_THREAD_COLOR'].'" align="center"><tr><td><dl><a name="$ANCOR"></a><div align="right"><a href="#menu">■</a><a href="#$FRONT">▲</a><a href="#$NEXT">▼</a></div><b>【$ANCOR:'.$lognum.'】<font size="5" color="'.$SETTING['BBS_SUBJECT_COLOR']."\">$subject</font></b>\n";
  //１つ目のリンクを作成
  $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
  //レスポンスアンカー（本文）
  $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
  $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
  //名前欄の変換
  if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
  else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
  //１つ目の要素を吐き出す
  $logall .= " <dt>1 名前：$mailto $date<dd>$message <br><br><br>\n";
  //残りのログを表示する
  foreach ($logopen as $tmp){
    //要素を加工する
    $tmp = rtrim($tmp);
    list ($name,$mail,$date,$message,$subject) = explode ("<>", $tmp);
    //リンクを作成
    $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
    //レスポンスアンカー（本文）
    $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
    $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
    //名前欄の変換
    if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
    else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
    //要素を吐き出す
    $logall .= " <dt>$topnum 名前：$mailto ：$date<dd>";
    // 0thelloスレッドは全部表示
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
        $logall .= "<font color=\"$SETTING[BBS_NAME_COLOR]\">（省略されました・・全てを読むには<a href=\"../test/read.php/$bbs/$key/$topnum\" target=\"_blank\">ここ</a>を押してください）</font><br>";
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
// subject.txtを更新する
//----------------------
function renew_subject_txt( $PATH, $key, $sage, $new, $subtt) {
  $subjectfile = $PATH.'subject.txt';
  $DATPATH = $PATH.'dat/';
  $TEMPPATH  = $PATH.'html/';
  $keyfile = $key.'.dat';
  $PAGEFILE = array();
  // サブジェクトファイルを読み込む
  // スレッドキー.dat<>タイトル (レスの数)\n
  // $PAGEFILE = array('スレッドキー.dat',・・・)
  // $SUBJECT = array('スレッドキー.dat'=>'タイトル (レスの数)',・・・)
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
  // サブジェクト数を取得
  $FILENUM = count( $PAGEFILE);
  // 新規スレッドの場合は1個追加
  if ( $new) $FILENUM++;
  // ログを定数に揃える
  if ( $FILENUM > KEEPLOGCOUNT) {
    for ( $start = KEEPLOGCOUNT; $start < $FILENUM; $start++) {
      $delfile = $DATPATH . $PAGEFILE[$start];
      // datファイル削除
      unlink( $delfile);
      $key = str_replace( '.dat', '', $PAGEFILE[$start]);
      $delfile = $TEMPPATH . $key . ".html";
      // htmlファイル削除
      @unlink( $delfile);
      if ( $dir = @opendir( $IMGPATH)) {
        while (( $file = readdir( $dir)) !== false) {
          // 画像ファイル削除
          if ( strpos( $file, $key) === 0) unlink( $IMGPATH.$file);
        }  
        closedir( $dir);
      }
      if ( $dir = @opendir( $IMGPATH2)) {
        while (( $file = readdir( $dir)) !== false) {
          // サムネイル画像ファイル削除
          if ( strpos( $file, $key) === 0) unlink( $IMGPATH2.$file);
        }  
        closedir( $dir);
      }
    }
    $FILENUM = KEEPLOGCOUNT;
    $PAGEFILE = array_slice( $PAGEFILE, 0, $FILENUM);
  }
  $subtm = "$keyfile<>$subtt";
  // サブジェクトハッシュを書き換える
  $SUBJECT[ $keyfile] = $subtt;
  // サブジェクトテキストを開く
  $fp = @fopen( $subjectfile, "w");
  //一括書き込み
  // sageの時は上がらない
  if ( !$new and $sage) {
    foreach ($PAGEFILE as $tmp){
      fputs($fp, "$tmp<>$SUBJECT[$tmp]\n");
    }
  }
  else {
    // 上がるキーは一番最初に持ってくる
    $temp[0] = $keyfile;
    $i = 1;
    fputs($fp, "$subtm\n");
    foreach ($PAGEFILE as $tmp) {
      // keyfileは現在書き込みしたスレッドキー（上がっている）
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