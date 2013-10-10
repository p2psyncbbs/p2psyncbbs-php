<?php
#====================================================
#�@�t�@�C������i�g�s�l�k�쐬�p��ƃt�@�C���X�V�j
#====================================================
#MakeWorkFile(KEY-NUMBER)
function MakeWorkFile( $post, $DATE, $ID, $NOWTIME) {
  global $SETTING;
  list( $bbs, $key) = array( $post['bbs'], $post['key']);
  list( $name, $mail, $message, $subject) = array( $post['FROM'], $post['mail'], $post['MESSAGE'], $post['subject']);
  $dattemp = "../$bbs/dat/$key.dat";
  $workfile = "../$bbs/html/$key.html";
  $outdat = implode( '<>', array( $name, $mail, $DATE.' '.$ID, $message, $subject))."\n";

  #p2p�p�̃t�@�C���p�X
  $p2p_thread_path = "../$bbs/p2p/thread/$key";
  $p2p_res_path = "../$bbs/p2p/res/";

  #p2p�p�X���b�h���O�E���X�̉��H
  $p2p_res = implode( '<>', array( $name, $mail, $message, $subject));
  $hash = hash( 'ripemd160', $p2p_res);
  $p2p_resHash = '';
  for ( $i=0; $i<8; $i++) {
    $p2p_resHash .= str_pad( base_convert( substr( $hash, $i, 5), 16, 32), 4, '0', STR_PAD_LEFT);
  }
  $p2p_threadLog = $NOWTIME.'<>'.$ID.'<>'.$p2p_resHash."\n";

  #���X�t�@�C���p�X�ݒ�
  $tmp = $p2p_res_path.substr( $p2p_resHash, 0, 2);
  if ( !is_dir( $tmp))
    mkdir( $tmp);
  $p2p_res_path = $tmp.'/'.$p2p_resHash;

  if (is_file($dattemp)) {
    $logopen = file($dattemp);
    $lognum = count($logopen);
    # �Ō�̃��X�̓��t�����擾�i�X���X�g�AOver thread�`�F�b�N�̂��߁j
    list(,,$tmp) = explode("<>", end($logopen));
  }
  else {
    $logopen = array();
    $lognum = 0;
    $tmp = '';
  }
  # �����݋֎~�Ŗ����ꍇ
  clearstatcache();
  if (is_writable($dattemp) or !is_file($dattemp)) {
    $fp = fopen($dattemp, "a");
    flock($fp, LOCK_EX);
    if (!preg_match("/Over \d+ Thread|��~/", $tmp)) {
      if ($outdat and $lognum < THREAD_RES) {
        fputs($fp, $outdat);
        array_push($logopen, $outdat);
        $lognum++;

        #p2p�p�X���b�h���O�E���X�̏�������
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
    # �P�O�O�O(THREAD_RES)�I�[�o�[�̏������݋֎~
    if ($lognum >= THREAD_RES) {
      if (!$stop) {
        # �S�p�����ɕύX
        $maxnum = mb_convert_kana(THREAD_RES, "N", "SJIS");
        $maxplus = mb_convert_kana(++$lognum, "N", "SJIS");
        $maxmsg = "���̃X���b�h��${maxnum}�𒴂��܂����B <br> ���������Ȃ��̂ŁA�V�����X���b�h�𗧂ĂĂ��������ł��B�B�B ";
        if (THREAD_MAX_MSG) {
          $maxmsg = str_replace('<NUM>', $maxnum, THREAD_MAX_MSG);
        }
        fputs($fp, "$maxplus<><>Over ".THREAD_RES." Thread<>$maxmsg<>\n");
        array_push($logopen, "$maxplus<><>Over ".THREAD_RES." Thread<>$maxmsg<>\n");
        $stop = 1;
      }
    }
    fclose($fp);
    #Windows�ł̗��p�⍡��̉��ς��ӎ����Č����̕ύX���~�߂�
    #if ($stop) chmod($dattemp, 0444);
  }
  # 1��������o��
  $logfirst = array_shift($logopen);
  # �\�����郌�X���������o��
  $logopen = array_slice($logopen, -$SETTING['BBS_CONTENTS_NUMBER']);
  # 1�̎��ɕ\�����郌�X�ԍ�
  $topnum = $lognum - count($logopen) + 1;
  #�P�ڂ̗v�f�����H����
  $logfirst = rtrim($logfirst);
  list ($name,$mail,$date,$message,$subject) = explode ("<>", $logfirst);
  $logsub = $subject;
  #�T�u�W�F�N�g�e�[�u����f���o���i�����͕K���P�s�ɂ܂Ƃ߂邱�Ɓi���������j�j
  $logall = '<table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="'.$SETTING['BBS_THREAD_COLOR'].'" align="center"><tr><td><dl><a name="$ANCOR"></a><div align="right"><a href="#menu">��</a><a href="#$FRONT">��</a><a href="#$NEXT">��</a></div><b>�y$ANCOR:'.$lognum.'�z<font size="5" color="'.$SETTING['BBS_SUBJECT_COLOR']."\">$subject</font></b>\n";
  #�P�ڂ̃����N���쐬
  $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
  #���X�|���X�A���J�[�i�{���j
  $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
  $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
  #���O���̕ϊ�
  if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
  else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
  #�P�ڂ̗v�f��f���o��
  $logall .= " <dt>1 ���O�F$mailto $date<dd>$message <br><br><br>\n";
  #�c��̃��O��\������
  foreach ($logopen as $tmp){
    #�v�f�����H����
    $tmp = rtrim($tmp);
    list ($name,$mail,$date,$message,$subject) = explode ("<>", $tmp);
    #�����N���쐬
    $message = preg_replace("/(https?):\/\/([\w;\/\?:\@&=\+\$,\-\.!~\*'\(\)%#]+)/", "<a href=\"$1://$2\" target=\"_blank\">$1://$2</a>", $message);
    #���X�|���X�A���J�[�i�{���j
    $message = preg_replace("/&gt;&gt;([0-9]+)(?![-\d])/", "<a href=\"../test/read.php/$bbs/$key/$1\" target=\"_blank\">&gt;&gt;$1</a>", $message);
    $mwssage = preg_replace("/&gt;&gt;([0-9]+)\-([0-9]+)/", "<a href=\"../test/read.php/$bbs/$key/$1-$2\" target=\"_blank\">&gt;&gt;$1-$2</a>", $message);
    #���O���̕ϊ�
    if ($mail) $mailto = "<a href=\"mailto:$mail \"><b>$name </b></a>";
    else $mailto = "<font color=\"$SETTING[BBS_NAME_COLOR]\"><b>$name </b></font>";
    #�v�f��f���o��
    $logall .= " <dt>$topnum ���O�F$mailto �F$date<dd>";
    // 0thello�X���b�h�͑S���\��
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
        $logall .= "<font color=\"$SETTING[BBS_NAME_COLOR]\">�i�ȗ�����܂����E�E�S�Ă�ǂނɂ�<a href=\"../test/read.php/$_POST[bbs]/$key/$topnum\" target=\"_blank\">����</a>�������Ă��������j</font><br>";
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
