<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/adminmots?lang_cible=pt_br
// ** ne pas modifier le fichier **

return [

	// B
	'bouton_associer' => 'Associar',
	'bouton_dissocier' => 'Dissociar',
	'bouton_fusionner' => 'Unir',

	// E
	'erreur_admin_mot_action_inconnue' => 'O que você quer fazer?',
	'erreur_mot_cle_deja' => 'Impossível: você está tentando unir a palavra-chave com ela mesma.',
	'erreur_selection_id' => 'Escolha uma palavra-chave ou informe a sua ID no campo de entrada',

	// I
	'icone_administrer_mot' => 'Admin. avançada',

	// L
	'label_associer_objets_mot' => '<b>Associar</b> esta palavra-chave aos objetos que já possuam a seguinte palavra-chave',
	'label_confirm_fusion' => 'Esta operação não é reversível.<br />
<strong>A palavra-chave atual #@id_mot@ será excluída</strong> e os vínculos a seguir serão transferidos para a palavra-chave #@id_mot_new@: ',
	'label_confirm_fusion_check' => 'Marque para confirmar a união da palavra-chave #@id_mot@ com a palavra-chave #@id_mot_new@',
	'label_dissocier_objets_mot' => '<b>Dissociar</b> esta palavra-chave dos objetos que também possuam a seguinte palavra-chave',
	'label_fusionner_mot' => '<b>Unir</b> com a palavra-chave',
	'label_mot_1_enfant' => 'Subordinada:',
	'label_mot_nb_enfants' => 'Subordinadas:',
	'label_mot_parent' => 'Superior:',

	// P
	'placeholder_id_mot' => 'ou #ID_MOT',
	'placeholder_select' => 'Selecionar…',

	// R
	'result_associer_nb' => ' foram associados a esta palavra-chave',
	'result_associer_ras' => 'Nada a fazer: todos os objetos já possuem esta palavra-chave',
	'result_cancel_ok' => 'A última operação foi cancelada.',
	'result_dissocier_nb' => ' foram dissociados desta palavra-chave',
	'result_dissocier_ras' => 'Nada a fazer: nenhum objeto pertinente foi associado a esta palavra-chave',
	'result_fusionner_ok' => 'Você pode agora excluir esta palavra-chave: todos os vínculos foram transferidos para a palavra-chave @mot@.',

	// T
	'titre_formulaire_administrer_mot' => 'Administrar a palavra-chave',
];
