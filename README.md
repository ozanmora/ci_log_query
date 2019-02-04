# CI Query Logger

Log All Queries with Execution Time for Codeigniter 3.x


## How to Use

1) Set the **log_threshold** to **2** in **applications/config/config.php** file
```php
$config['log_threshold'] = 2;
or
$config['log_threshold'] = array(2);
```

2) Set the **enable_hooks** to **true** in **applications/config/config.php** file
```php
$config['enable_hooks'] = true;
```

3) Then Open up your **hooks.php** file in **applications/config** folder and add the following code in it
```php
$hook['post_controller'][] = array(
    'class' => 'Log_Query', 
    'function' => 'run',
    'filename' => 'Log_query.php',
    'filepath' => 'hooks'
);
```

4) Now add **applications/hooks/Log_query.php** file in the repository to your codeigniter **applications/hooks** folder.

#### Enjoy it!
