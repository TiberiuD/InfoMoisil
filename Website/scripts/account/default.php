<?php
    if(isset($scriptOut['loginErrorID']))
        switch($scriptOut['loginErrorID']) {
            case ERR_LOGIN_NOTFOUND:
                $scriptOut['loginErrorStr'] = 'Numele de utilizator sau parola sunt greșite!';
                break;
            default:
                $scriptOut['loginErrorStr'] = 'Eroare necunoscută: ' . $scriptOut['loginErrorID'] . '.';
        }

    if($LoggedIn)
        $scriptOut['avatarURL'] = get_gravatar($_SESSION['email'], 160);