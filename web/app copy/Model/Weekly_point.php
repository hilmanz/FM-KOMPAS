<?php
App::uses('AppModel', 'Model');
/**
 * Profile Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Weekly_point extends AppModel {

	public $belongsTo = array(
		'Team' => array(
			'type'=>'INNER'
		),
	);
	
}