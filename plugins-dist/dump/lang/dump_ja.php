<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/dump?lang_cible=ja
// ** ne pas modifier le fichier **

return [

	// A
	'aucune_donnee' => '空白',
	'avis_probleme_ecriture_fichier' => '@fichier@のファイルを書くことに問題がある。',

	// B
	'bouton_restaurer_base' => 'データベースを復元',

	// C
	'confirmer_ecraser_base' => 'はい、データベースをバックアップで上書きしたいのです。',
	'confirmer_ecraser_tables_selection' => 'はい、このバックアップで選択したテーブルを上書きします。',
	'confirmer_supprimer_sauvegarde' => 'このバックアップを削除してもよろしいですか？',

	// D
	'details_sauvegarde' => 'バックアップの詳細：',

	// E
	'erreur_aucune_donnee_restauree' => 'データが復元されません。',
	'erreur_connect_dump' => '「@dump@」という名前のサーバーは既に存在します。名前を変更してください。',
	'erreur_creation_base_sqlite' => 'バックアップ用のSQLiteデータベースを作成できません。',
	'erreur_nom_fichier' => 'このファイルの名前は許されていません。',
	'erreur_restaurer_verifiez' => '復元が出来るようにエラーを修正してください。',
	'erreur_sauvegarde_deja_en_cours' => '既にバックアップの実行中です。',
	'erreur_sqlite_indisponible' => 'ホスト上でSQLiteのバックアップを作成することが出来ません。',
	'erreur_table_absente' => 'テーブル@table@欠落',
	'erreur_table_donnees_manquantes' => '@table@テーブル、データが欠落しています。',
	'erreur_taille_sauvegarde' => 'バックアップが失敗したように見えます。@fichier@ファイルは空または欠落です。',

	// I
	'info_aucune_sauvegarde_trouvee' => 'バックアップが見つかりません。',
	'info_restauration_finie' => '出来上がりました。@archive@バックアップがサイトに復元されました。',
	'info_restauration_sauvegarde' => 'バックアップ@archive@の復元',
	'info_sauvegarde' => 'バックアップ',
	'info_sauvegarde_reussi_02' => 'データベースは、@archive@に保存されました。',
	'info_sauvegarde_reussi_03' => 'サイトの管理エリアへ',
	'info_sauvegarde_reussi_04' => '戻る事が出来ます。',
	'info_selection_sauvegarde' => '@fichier@バックアップの復元を選択しました。この操作は不可逆です。',

	// L
	'label_nom_fichier_restaurer' => 'または、復元するファイルの名前を指定します。',
	'label_nom_fichier_sauvegarde' => 'バックアップのファイル名',
	'label_selectionnez_fichier' => 'リストからファイルを選択してください。',

	// N
	'nb_donnees' => '保存されたファイル@nb@',

	// R
	'restauration_en_cours' => '復元中',

	// S
	'sauvegarde_en_cours' => 'バックアップの進行中',
	'sauvegardes_existantes' => '既存のバックアップ',
	'selectionnez_table_a_restaurer' => '復元するテーブルを選択してください。',

	// T
	'texte_admin_tech_01' => 'このオプションを使用すると、データベースの内容を@ folder @フォルダにあるファイルを保存します。または、@img@フォルダー全体を復元することを忘れないでください。このフォルダーには、記事やセクションで使用されている画像やドキュメントが含まれています。',
	'texte_admin_tech_02' => '警告：このバックアップは、同じバージョンのSPIPでインストールされたサイトで<b>のみ</b>復元出来ます。SPIPの更新してから、データベースを空にするとバックアップを再インストールしようと思ってはなりません。
詳細、<a href="@spipnet@"> SPIPの取り扱い説明書</a>を参照してください。',
	'texte_restaurer_base' => 'データベースのバックアップの内容を復元',
	'texte_restaurer_sauvegarde' => 'このオプションで前にバックアップしたデータベースを復元することが可能です。復元するには、バックアップ用のファイルが@dossier@ディレクトリに保存されていなければなりません。必ずこの機能は注意して使ってください：<b>どんな潜在的な変更や損失も撤回することができません。</b>',
	'texte_sauvegarde' => 'データベースの内容をバックアップ',
	'texte_sauvegarde_base' => 'データベースをバックアップ',
	'tout_restaurer' => 'すべてのテーブルの復元',
	'tout_sauvegarder' => 'すべてのテーブルを保存',

	// U
	'une_donnee' => '保存されたファイル１つ',
];
