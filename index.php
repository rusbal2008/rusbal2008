<?php
/**
 * Created by PhpStorm.
 * User: sv
 * Date: 01.11.16
 * Time: 10:44
 */

// ini_set('display_errors','Off');

// формируем url нашего скрипта для использования в ajax-запросах из интерфейса приложения
$script_url = ($_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . (in_array($_SERVER['SERVER_PORT'],
        array(80, 443)) ? '' : ':' . $_SERVER['SERVER_PORT']) . $_SERVER['SCRIPT_NAME'];

$appsConfig = array();
$b24domain = $_REQUEST['DOMAIN'];

// если нам пришло событие исходящего звонка, то авторизация передается через узел auth в массиве request
// но нам оттуда нужен только домен, авторизацию мы уже сохранили к этому моменту
if (!empty($_REQUEST['auth'])) {
    $b24domain = $_REQUEST['auth']['domain'];
}

$configFileName = '/config_' . trim(str_replace('.', '_', $b24domain)) . '.php';
echo getcwd().$configFileName."<br/>";
if (file_exists(getcwd() . $configFileName)) {
    include_once getcwd() . $configFileName;
} else {

    // сохраняем токены пользователя, устанавливающего приложение
    $appsConfig = $_REQUEST;
    saveParams($appsConfig);

    // регистрируем событие исходящего звонка
    restCommand('event.bind', array(
            "event" => 'ONEXTERNALCALLSTART',
            "handler" => $script_url."?action=outcoming",
        ),
        $b24domain, $appsConfig['AUTH_ID']);

    /* тестовое событие для проверки механизма
    restCommand('event.bind', array(
        "event" => 'ONAPPTEST',
        "handler" => $script_url."?action=test",
        ),
        $b24domain, $appsConfig['AUTH_ID']);
    */

}

$action = $_REQUEST['action'];

// мы просто запустили приложение в интерфейсе Битрикс24
if ($action == '') {
?>
<html>
<head>
    <meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>

<div class="form-group">
    <label for="IncomingNumber">Incoming call number</label>
    <input type="text" class="form-control" id="incomingNumber" placeholder="phone">
</div>
<div class="form-group">
    <label for="user1">User 1</label>
    <input type="text" class="form-control" id="user1" placeholder="user id" value="1">
</div>
<div class="form-group">
    <label for="user2">User 2 (for call transfer)</label>
    <input type="text" class="form-control" id="user2" placeholder="user id">
</div>
<a class="btn btn-default" href="#" role="button" id="incoming">Incoming</a>
<a class="btn btn-default" href="#" role="button" id="redirect">Redirect</a>
<a class="btn btn-default" href="#" role="button" id="drop">Drop</a>
<a class="btn btn-default" href="#" role="button" id="test">Event test</a>
<div id="debug"></div>

<?
    // если любопытно, можно посмотреть, какие параметры авторизации передает Битрикс24 скрипту приложения
    // в случае выполнения приложения во фрейме внутри Битрикс24
    //echo "<pre>";
	//print_r($_REQUEST);
	//echo "</pre>";

?>
<script>
    $( "#incoming" ).on( "click", function( event ) {
        // здесь мы имитируем работу внешней АТС, в частности, получение входящего звонка
        // поэтому AJAX, передача параметров авторизации и т.д.
        // в реальной практике, REST телефонии будет вызываться со стороны АТС, а там мы уже сохранили
        // авторизационные токены (см. $appsConfig$appsConfig) и сами знаем, на какой Битрикс24 отправлять
        // вызов REST, каким пользователям показывать карточку и т.д.
        auth = BX24.getAuth();
        $.ajax({
            url: "<?=$script_url?>",
            data: {
                action: 'incoming',
                user1: $( "#user1" ).val(),
                phone: $( "#incomingNumber" ).val(),
                DOMAIN: auth['domain']
            },
            success: function( result ) {
                $( "#debug" ).html( result );
            }
        });
    });

    $( "#redirect" ).on( "click", function( event ) {
        auth = BX24.getAuth();
        $.ajax({
            url: "<?=$script_url?>",
            data: {
                action: 'redirect',
                user1: $( "#user1" ).val(),
                user2: $( "#user2" ).val(),
                DOMAIN: auth['domain']
            },
            success: function( result ) {
                $( "#debug" ).html( result );
            }
        });
    });

    $( "#drop" ).on( "click", function( event ) {
        auth = BX24.getAuth();
        $.ajax({
            url: "<?=$script_url?>",
            data: {
                action: 'drop',
                user1: $( "#user1" ).val(),
                user2: $( "#user2" ).val(),
                DOMAIN: auth['domain']
            },
            success: function( result ) {
                $( "#debug" ).html( result );
            }
        });
    });
    /* инициация тестового события на стороне серверного скрипта, ничего важного
    $( "#test" ).on( "click", function( event ) {
        auth = BX24.getAuth();
        $.ajax({
            url: "<?=$script_url?>",
            data: {
                action: 'eventtest',
                DOMAIN: auth['domain']
            },
            success: function( result ) {
                $( "#debug" ).html( result );
            }
        });
    });
    */
</script>
</body>
</html>
<? } else {

    switch ($action) {
        case 'test': writeToLog(array('test' => $_REQUEST), 'telephony test event');
                break;

        case 'outcoming':

            writeToLog(array('outcoming' => $_REQUEST), 'telephony event');

            $result = restCommand('telephony.externalCall.register',
                array(
                    "USER_ID" => $_REQUEST['data']['USER_ID'],
                    "PHONE_NUMBER"   => $_REQUEST['data']['PHONE_NUMBER'],
                    "TYPE" => '1',
                    "CRM_CREATE" => 1
                ),
                $b24domain, $appsConfig['AUTH_ID']);

            $appsConfig['CALL'] = $result['result'];


            saveParams($appsConfig);

            break;
        case 'eventtest':

            writeToLog(array('eventtest' => $_REQUEST), 'test event call');
            $result = restCommand('event.test',
                array(
                ),
                $b24domain, $appsConfig['AUTH_ID']);

            echo "test event call";

            break;
        case 'incoming':

            $result = restCommand('telephony.externalCall.register',
                array(
                    "USER_ID" => $_REQUEST['user1'],
                    "PHONE_NUMBER"   => $_REQUEST['phone'],
                    "TYPE" => '2',
                    "CRM_CREATE" => true
                ),
                $b24domain, $appsConfig['AUTH_ID']);

            $appsConfig['CALL'] = $result['result'];

            saveParams($appsConfig);

            echo "incoming <pre>";
            print_r($appsConfig);
            echo "</pre>";

            break;
        case 'redirect':

            echo "redirect <pre>";
            print_r($appsConfig);
            echo "</pre>";

            if ($appsConfig['CALL']['CALL_ID'] != '') {

                $result = restCommand('telephony.externalCall.hide',
                    array(
                        "CALL_ID" => $appsConfig['CALL']['CALL_ID'],
                        "USER_ID" => $_REQUEST['user1']
                    ),
                    $b24domain, $appsConfig['AUTH_ID']);

                $result = restCommand('telephony.externalCall.show',
                    array(
                        "CALL_ID" => $appsConfig['CALL']['CALL_ID'],
                        "USER_ID" => $_REQUEST['user2']
                    ),
                    $b24domain, $appsConfig['AUTH_ID']);
            }
            echo "redirected to ".$_REQUEST['user2'];
            break;
        case 'drop':
            writeToLog(array('config' => $appsConfig), 'call is finishing');

            if ($appsConfig['CALL']['CALL_ID'] != '') {

                $result = restCommand('telephony.externalCall.finish',
                    array(
                        "CALL_ID" => $appsConfig['CALL']['CALL_ID'],
                        "USER_ID" => $_REQUEST['user1'],
                        "DURATION"   => '120',
                        "STATUS_CODE" => '200',
                        "ADD_TO_CHAT" => true
                    ),
                    $b24domain, $appsConfig['AUTH_ID']);

                $appsConfig['CALL'] = $result['result'];

                saveParams($appsConfig);

                echo "finished <pre>";
                print_r($appsConfig);
                echo "</pre>";

                writeToLog(array('request' => $_REQUEST, 'config' => $appsConfig), 'call is finished');
            }
            echo "dropped and saved";
            break;
    }

}

/**
 * Save application configuration.
 *
 * @param $params
 *
 * @return bool
 */
function saveParams($params) {
    $config = "<?php\n";
    $config .= "\$appsConfig = " . var_export($params, true) . ";\n";
    $config .= "?>";
    $configFileName = '/config_' . trim(str_replace('.', '_', $_REQUEST['DOMAIN'])) . '.php';
    file_put_contents(getcwd() . $configFileName, $config);
    return true;
}
/**
 * Send rest query to Bitrix24.
 *
 * @param       $method - Rest method, ex: methods
 * @param array $params - Method params, ex: array()
 * @param array $auth   - Authorize data, ex: array('domain' => 'https://test.bitrix24.com', 'access_token' => '7inpwszbuu8vnwr5jmabqa467rqur7u6')
 *
 * @return mixed
 */
function restCommand($method, array $params = array(), $auth_domain, $access_token) {
    $queryUrl  = 'https://' . $auth_domain . '/rest/' . $method;
    $queryData = http_build_query(array_merge($params, array('auth' => $access_token)));
    writeToLog(array('URL' => $queryUrl, 'PARAMS' => array_merge($params, array("auth" => $access_token))), 'telephony send data');
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST           => 1,
        CURLOPT_HEADER         => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL            => $queryUrl,
        CURLOPT_POSTFIELDS     => $queryData,
        CURLOPT_VERBOSE         => 1
    ));
    $result = curl_exec($curl);
    writeToLog(array('raw' => $result), 'telephony got data');
    curl_close($curl);
    $result = json_decode($result, 1);
    return $result;
}

/**
 * Write data to log file.
 *
 * @param mixed  $data
 * @param string $title
 *
 * @return bool
 */
function writeToLog($data, $title = '') {
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";
    file_put_contents(getcwd() . '/tel.log', $log, FILE_APPEND);
    return true;
}

?>