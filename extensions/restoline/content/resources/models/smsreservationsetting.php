<?php
$config['form']['toolbar'] = ['buttons' => ['save' => ['label'=>'Enregistrer','context'=>['create','edit'],'partial'=>'form/toolbar_save_button','class'=>'btn btn-primary','data-request'=>'onSave','data-progress-indicator'=>'Enregistrement...']]];
$config['form']['fields'] = ['enabled' => ['label'=>'Activer les réservations par SMS','type'=>'switch','span'=>'full','comment'=>'Si désactivé, les nouveaux SMS sont transmis au restaurant sans traitement automatique.']];
return $config;
