<?php
# 設定ファイル（"SETTING.TXT"以外の設定項目）
# ログファイル保持数（システム設定）
defined( 'KEEPLOGCOUNT')? true: define( 'KEEPLOGCOUNT', 4096);
# 1スレッドに投稿できるレス数の上限
defined( 'THREAD_RES')? true: define( 'THREAD_RES', 500);
# レスオーバー時のメッセージ
defined( 'THREAD_MAX_MSG')? true: define( 'THREAD_MAX_MSG', 'あれ、<NUM>超えちゃったみたい…書き込めないや…<br>　　　 ∧∧ 　　　　　　　　　　 ∧,,∧<br>　　　（；ﾟДﾟ） 　　　　　　　　　ミﾟДﾟ,,彡 　おｋｋ<br>　　　ﾉ つ▼〔|￣￣］ 　　　　 ▽⊂　ﾐ 　　　新スレいこうぜ<br>　～（,,⊃〔￣||====]～～［］⊂,⊂,,,;;ﾐ@');
# 1スレッドの上限（バイト）
defined( 'THREAD_BYTES')? true: define( 'THREAD_BYTES', 524288);
# ファイルアップ許可
defined( 'UPLOAD')? true: define( 'UPLOAD', 0);
# GDバージョン
#defined( 'GD_VERSION')? true: define( 'GD_VERSION', 2);
# アップロード上限（バイト）
defined( 'MAX_BYTES')? true: define( 'MAX_BYTES', 300000);
# サムネイル画像の幅
defined( 'MAX_W')? true: define( 'MAX_W', 120);
# サムネイル画像の高さ
defined( 'MAX_H')? true: define( 'MAX_H', 160);
# おみくじ機能
defined( 'OMIKUJI')? true: define( 'OMIKUJI', 1);
# 野球機能
defined( 'BASEBALL')? true: define( 'BASEBALL', 1);
# どこ誰何機能
defined( 'WHO_WHERE')? true: define( 'WHO_WHERE', 1);
# 壷機能（未実装）
defined( 'TUBO')? true: define( 'TUBO', 1);
# 等幅フォント機能
defined( 'TELETYPE')? true: define( 'TELETYPE', 1);
# スレッド内名無し名変更機能
defined( 'NAME_774')? true: define( 'NAME_774', 1);
# 名無しへ強制変更機能
defined( 'FORCE_774')? true: define( 'FORCE_774', 1);
# IDなし機能
defined( 'FORCE_NO_ID')? true: define( 'FORCE_NO_ID', 1);
# sage強制機能
defined( 'FORCE_SAGE')? true: define( 'FORCE_SAGE', 1);
# レス要キャップ機能
defined( 'FORCE_STARS')? true: define( 'FORCE_STARS', 1);
# スレッド内VIP機能解除
defined( 'FORCE_NORMAL')? true: define( 'FORCE_NORMAL', 1);
# 名前入力強制機能
defined( 'FORCE_NAME')? true: define( 'FORCE_NAME', 1);
# 0thelo機能
defined( 'ZEROTHELO')? true: define( 'ZEROTHELO', 1);
# アップロード機能
defined( 'FORCE_UP')? true: define( 'FORCE_UP', 0);
?>
