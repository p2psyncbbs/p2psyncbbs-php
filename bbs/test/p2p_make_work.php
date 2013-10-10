<?php
#====================================================
#　ファイル操作（ＨＴＭＬ作成用作業ファイル更新）
#====================================================
#MakeWorkFile(KEY-NUMBER)
function MakeWorkFile( $post, $DATE, $ID, $NOWTIME) {
  global $SETTING;
  list( $bbs, $key) = array( $post['bbs'], $post['key']);
  list( $name, $mail, $message, $subject) = array( $post['FROM'], $post['mail'], $post['MESSAGE'], $post['subject']);
  $dattemp = "../$bbs/dat/$key.dat";
  $workfile = "../$bbs/html/$key.html";
  $outdat = implode( '<>', array( $name, $mail, $DATE.' '.$ID, $message, $subject))."\n";

  #p2p用のファイルパス
  $p2p_thread_path = "../$bbs/p2p/thread/$key";
  $p2p_res_path = "../$bbs/p2p/res/";

  #p2p用スレッドログ・レスの加工
  $p2p_res = implode( '<>', array( $name, $mail, $message, $subject));
  $hash = hash( 'ripemd160', $p2p_res);
  $p2p_resHash = '';
  for ( $i=0; $i<8; $i++) {
    $p2p_resHash .= str_pad( base_convert( substr( $hash, $i, 5), 16, 32), 4, '0', STR_PAD_LEFT);
  }
  $p2p_threadLog = $NOWTIME.'<>'.$ID.'<>'.$p2p_resHash."\n";

  #レスファイルパス設定
  $tmp = $p2p_res_path.substr( $p2p_resHash, 0, 2);
  if ( !is_dir( $tmp))
    mkdir( $tmp);
  $p2p_res_path = $tmp.'/'.$p2p_resHash;

  if (is_file($dattemp)) {
    $logopen = file($dattemp);
    $lognum = count($logopen);
    # 最後のレスの日付欄を取得（スレスト、Over threadチェックのため）
    list(,,$tmp) = explode("<>", end($logopen));
  }
  else {
    $logopen = array();
    $lognum = 0;
    $tmp = '';
  }
  # 書込み禁止で無い場合
  clearstatcache();
  if (is_writable($dattemp) or !is_file($dattemp)) {
    $fp = fopen($dattemp, "a");
    flock($fp, LOCK_EX);
    if (!preg_match("/Over \d+ Thread|停止/", $tmp)) {
      if ($outdat and $lognum < THREAD_RES) {
        fputs($fp, $outdat);
        array_push($logopen, $outdat);
        $lognum++;

        #p2p用スレッドログ・レスの書きこみ
        if ( is_writable( $p2p_thread_path) or !is_file( $p2p_thread_path)) {
          $p2p_fp = fopen( $p2p_thread_path, 'a');
          flock( $p2p_fp, LOCK_EX);
          fputs( $p2p_fp, $p2p_threadLog);
          fclose( $p2p_fp);
          if ( !is_file( $p2p_res_path)) {
            $p2p_fp = fopen( $p2p_res_path, 'a');
            flock( $p2p_fp, LOCK_EX);
            fputs( $p2p_fp, $p2p_res);
            fclose( $p2p_fp);
          }
        }
      }
      $stop = 0;
    }
    else $stop = 1;
    # １０００(THREAD_RES)オーバーの書きこみ禁止
    if ($lognum >= THREAD_RES) {
      if (!$stop) {
        # 全角数字に変更
        $maxnum = mb_convert_kana(THREAD_RES, "N", "SJIS");
        $maxplus = mb_convert_kana(++$lognum, "N", "SJIS");
        $maxmsg = "このスレッドは${maxnum}を超えました。 <br> もう書けないので、新しいスレッドを立ててくださいです。。。 ";
        if (THREAD_MAX_MSG) {
          $maxmsg = str_replace('<NUM>', $maxnum, THREAD_MAX_MSG);
        }
        fputs($fp, "$maxplus<><>Over ".THREAD_RES." Thread<>$maxmsg<>\n");
        array_push($logopen, "$maxplus<><>Over ".THREAD_RES." Thread<>$maxmsg<>\n");
        $stop = 1;
      }
    }
    fclose($fp);
    #Windowsでの利用や今後の改変を意識して権限の変更を止める
    #if ($stop) chmod($dattemp, 0444);
  }
  # 1さんを取り出し
  $logfirst = array_shift($logopen);
  # 表示するレス数だけ取り出し
  $logopen = array_slice($logopen, -$SETTING['BBS_CONTENTS_NUMBER']);
  # 1の次に表示するレス番号
  $topnum = $lognum - count($logopen) + 1;
  #１つ目の要素を加工する
  $logfirst = rtrim($logfirst);
  list ($name,$mail,$date,$message,$subject) = explode ("<>", $logfirst);
  $logsub = $subject;
  #サブジェクトテーブルを吐き出す（ここは必ず１行にまとめること（処理効率））
  $logall = '<table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="'.$SETTING['BBS_THREAD_COLOR'].'" align="center"><tr><td><dl><a name="$ANCOR"></a><div align="right"><a href="#menu">■</a><a href="#$FRONT">▲</a><a href="#$NEXT">▼</a></div><b>【$ANCOR:'.$lognum.'】<font size="5" color="'.$SETTING['BBS_SUBJECT_COLOR']."\">$subject</font></b>\n";
  #１つ目のリンクを作成
  $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
  #レスポンスアンカー（本文）
  $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
  $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
  #名前欄の変換
  if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
  else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
  #１つ目の要素を吐き出す
  $logall .= " <dt>1 名前：$mailto $date<dd>$message <br><br><br>\n";
  #残りのログを表示する
  foreach ($logopen as $tmp){
    #要素を加工する
    $tmp = rtrim($tmp);
    list ($name,$mail,$date,$message,$subject) = explode ("<>", $tmp);
    #リンクを作成
    $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
    #レスポンスアンカー（本文）
    $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
    $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
    #名前欄の変換
    if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
    else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
    #要素を吐き出す
    $logall .= " <dt>$topnum 名前：$mailto ：$date<dd>";
    // 0thelloスレッドは全部表示
    if ($GLOBALS['vip'][8]) $logall .= $message;
    else {
      $messx = explode ("<br>", $message);
      for ($i = 1; $i <= $SETTING['BBS_LINE_NUMBER']; $i++) {
        if ($messx) {
          $logall .= array_shift($messx);
          $logall .= "<br>";
        }
      }
      if ($messx) {
        $logall .= "<font color=\"$SETTING[BBS_NAME_COLOR]\">（省略されました・・全てを読むには<a href=\"../test/read.php/$_POST[bbs]/$key/$topnum\" target=\"_blank\">ここ</a>を押してください）</font><br>";
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
?>
