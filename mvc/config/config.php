<?php
return array(
    'SESSION_TIMEOUT'=>1200,           //默认session超时时间为1200秒
    'DB_CONFIG'=>array(                //数据库配置
            'DB_HOST' => '',           //数据库地址
            'DB_USER' => '',           //数据库用户名
            'DB_PWD' => '',           //数据库密码
            'DB_NAME' => '',           //数据库名
            'DB_PREFIX' => '',          //表前缀
            'DB_MAP' => '',          //表名和字段名映射方式
            'DB_TYPE' => ''          //实体类和数据库字段名映射方式，支持hump（大驼峰），smallHumnp（小驼峰），hump_to_small（小写下划线）
        ),
    'ROUTER_TYPE'=>'default',          //路由模式,默认为参数模式，path_info则为PATHINFO模式
    'ROUTER'=>array(                   //路由名称配置
        'DEFAULT_CONTROLLER'=>'Index',   //默认控制器名
        'DEFAULT_ACTION'=>'index',   //默认操作名
    ),
    'UPLOAD_FILE'=>array(     //文件上传配置
        'TYPE'=>array(     //支持的文件类型
            '.jpg','.jpeg','.gif','.bmp','.png',
            '.swf','.flv','.mp4','.webm','.ogg','.mp3','.rmvb',
            '.zip','.doc','.docx','.xls','.xlsx','.rar','.pdf','.tar','.gz','.ppt','.pptx'
        ),
        'MAX_SIZE'=>10000000,     //文件大小，默认为10M
        'NAME_RULE'=>'date',     //密码规则，默认为日期
        'PATH'=>'file'     //保存的目录
    ),
    '404_PAGE'=>MVC_PATH.'common/page/404.html',     //404页面路径
    '500_PAGE'=>MVC_PATH.'common/page/500.html'     //500页面路径
);