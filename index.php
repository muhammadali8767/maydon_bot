<?php
    // $bot_token = '786772794:AAEptdTryiDDYgXRsyF-Sbfy0JH_JkLqhU4';
    $bot_token = '975816512:AAEtda3oR5IRl7vX6pOc8sRCEqtYcGni3h0';
    if (isset($_POST['bot_url'])) {
        $result = json_decode(setwebhook($bot_token, $_POST['bot_url']), 1);
    } elseif (isset($_POST['get_info'])) {
        $result = json_decode(getwebhookinfo($bot_token), 1);
    }

    define ( "BOT_TOKEN", '975816512:AAEtda3oR5IRl7vX6pOc8sRCEqtYcGni3h0' ); // MAYDON_UZ_BOT
    define ( "FULLPATH", '' );
    define ( "BRINGO_URL", '' );


    // Custom Helpers
    require_once FULLPATH . 'vendor/autoload.php';
    require_once FULLPATH . 'bot/helpers/bcurl.php';
    require_once FULLPATH . 'bot/helpers/bcommon.php';
    require_once FULLPATH . 'bot/helpers/bkeyboard.php';
    require_once FULLPATH . 'bot/helpers/bquerycommon2.php';

    // Telegram Helpers
    use Telegram\Bot\Api;
    use Telegram\Bot\Objects\Message;
    use Telegram\Bot\Objects\Chat;
    use Telegram\Bot\Objects\User;
    use Telegram\Bot\Keyboard\Keyboard;


    $telegram = new Api(BOT_TOKEN);
    $updates = $telegram->getWebhookUpdates();


    $dbhelper = new BQueryCommon($telegram);
    $emoji = BCommon::getEmoji();
    $languages = BCommon::getLanguages();


    $stadiums = $dbhelper->getListOffStadiums();

    function setwebhook($bot_token, $bot_url)
    {
        if ($bot_url == '')
            $url = "https://api.telegram.org/bot$bot_token/setwebhook";
        else
            $url = "https://api.telegram.org/bot$bot_token/setwebhook?url=$bot_url/bot/index2.php";

        return send_curl($url);
    }

    function getwebhookinfo($bot_token)
    {
        $url = "https://api.telegram.org/bot$bot_token/getwebhookinfo";
        return send_curl($url);
    }

    function send_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $returned = curl_exec($ch);
        curl_close ($ch);
        return $returned;
    }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Maydon bot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="main.css" />
    <script src="main.js"></script>
</head>
<body>
    <p>Set Webhook</p>
    <form method="post">
        <input type="text" name="bot_url">
        <button type="url">Set Webhook</button>
    </form>    
    <p>Get Webhook info</p>
    <form method="post">
        <input type="hidden" name="get_info">
        <button type="submit">Get Webhook info</button>
    </form>    

</body>
</html>

<?php
    echo "<pre>";
    // print_r($result);
    print_r($stadiums);
    echo "</pre>";
?>