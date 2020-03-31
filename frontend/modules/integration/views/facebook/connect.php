<?php

use yii\helpers\Url;
use yii\web\View;

/**
 * @var View   $this
 * @var string $fbApplicationId
 * @var bool   $back
 */
$this->title = 'Интеграция реклманых компаний Facebook';
?>

<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId: '<?=$fbApplicationId?>',
            cookie: true,
            xfbml: true,
            version: 'v5.0'
        });
        FB.AppEvents.logPageView();
        FB.getLoginStatus(function (response) {
            if (response.status == 'not_authorized' || response.status == 'unknown') {
                FB.login(function (response) {
                    if (response.authResponse) {
                        console.log('Welcome!  Fetching your information.... ');
                        FB.api('/me', function (response) {
                            window.location = '<?=Url::to(['/integration/facebook', 'a' => 'b'])?>';
                        });
                    } else {
                        console.log('User cancelled login or did not fully authorize.');
                    }
                }, {scope: 'ads_management'});
            }
            <?php if($back === false) { ?>
            if (response.status == 'connected') {
                window.location = '<?=Url::to('/integration/facebook')?>?uid=' + response.authResponse.userID + '&token=' + response.authResponse.accessToken;
            }
            <?php } ?>
        });
    };
    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    function setCookie(name, value, options) {
        options = options || {};
        var expires = options.expires;
        if (typeof expires == "number" && expires) {
            var d = new Date();
            d.setTime(d.getTime() + expires * 1000);
            expires = options.expires = d;
        }
        if (expires && expires.toUTCString) {
            options.expires = expires.toUTCString();
        }
        value = encodeURIComponent(value);
        var updatedCookie = name + "=" + value;
        for (var propName in options) {
            updatedCookie += "; " + propName;
            var propValue = options[propName];
            if (propValue !== true) updatedCookie += "=" + propValue;
        }
        document.cookie = updatedCookie;
    }
</script>
<script async defer src="https://connect.facebook.net/en_US/sdk.js"></script>

<div class="row small-boxes">

    <div class="col-md-6">
        <div class="portlet box darkblue">
            <div class="portlet-title">
                <div class="caption">Подключить</div>
            </div>
            <div class="portlet-body">Идёт настройка...</div>
        </div>
    </div>

</div>
