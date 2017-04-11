<?php
return array(
    'SESSION_TIMEOUT'=>1200,
    'DB_CONFIG'=>array(
            'DB_HOST' => '',
            'DB_USER' => '',
            'DB_PWD' => '',
            'DB_NAME' => '',
            'DB_PREFIX' => '',
            'DB_TYPE' => ''
        ),
    'ROUTER_TYPE'=>'default',
    'ROUTER'=>array(
        'DEFAULT_CONTROLLER'=>'Index',
        'DEFAULT_ACTION'=>'index',
    ),
    'UPLOAD_FILE'=>array(
        'TYPE'=>array(
            '.jpg','.jpeg','.gif','.bmp','.png',
            '.swf','.flv','.mp4','.webm','.ogg','.mp3','.rmvb',
            '.zip','.doc','.docx','.xls','.xlsx','.rar','.pdf','.tar','.gz','.ppt','.pptx'
        ),
        'MAX_SIZE'=>10000000,
        'NAME_RULE'=>'date',
        'PATH'=>'file'
    ),
    '404_PAGE'=>ROOT_PATH.'common/page/404.html',
    '500_PAGE'=>ROOT_PATH.'common/page/500.html'
);