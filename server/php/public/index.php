<?php
    require_once('config.php');
    require(constant('HOCKEY_INCLUDE_DIR'));
    
    $router = Router::get(array('appDirectory' => dirname(__FILE__).DIRECTORY_SEPARATOR));
    $applications = $router->app->applications;
    $b = $router->baseURL;
    DeviceDetector::detect();


    $userCanSeePrivate = ($router->args['password'] == 'supersecret');

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>App Installer</title>
        <meta name="viewport" content="width=device-width" />
        <link rel="stylesheet" href="<?php echo $b ?>blueprint/screen.css" type="text/css" media="screen, projection">
        <link rel="stylesheet" href="<?php echo $b ?>blueprint/print.css" type="text/css" media="print">
        <!--[if IE]><link rel="stylesheet" href="<?php echo $b ?>blueprint/ie.css" type="text/css" media="screen, projection"><![endif]-->
        <link rel="stylesheet" href="<?php echo $b ?>blueprint/plugins/buttons/screen.css" type="text/css" media="screen, projection">
        <link rel="stylesheet" type="text/css" href="<?php echo $b ?>css/stylesheet.css">
        <link rel="alternate" type="application/rss+xml" title="App Updates" href="<?php echo $b ?>feed.php" />
        
        <style>
            .defaultForm {
                width: 300px;
                margin-left: auto;
                margin-right: auto;
            }
        </style>

    </head>
    <body class="<?php 
        echo DeviceDetector::$category; 
        // echo "browser-desktop";?>">

        <div id="loginPass">
            

            <form action="index.php" method="post" class="defaultForm" <?php if ($userCanSeePrivate) echo 'style="display:none;"' ?> >
                <label for="passId">Password: </label>
                <input id="passId" name="password" type="password" />
                <input type="submit" />
            </form>

            <?php 
                $filePrefix = "linksToApp_";

                if (isset($router->args['createAppLink'])) {
                    $appLinkArr = $router->args['createAppLink'];
                    unset($router->args['createAppLink']);

                    $randNum = rand(1000000, 9999999);
                    $fileName = $filePrefix . $randNum . ".txt";

                    $test = fopen($fileName, "w");
                    fwrite($test, serialize($appLinkArr));

                    Header("Location: http://apps.bullyinternal.com?link={$randNum}"); 
                }

                // don't check for private if we have link in get message
                if (isset($_GET['link'])) {
                    $dirArr = unserialize(file_get_contents($filePrefix . $_GET['link'].".txt"));
                    $newApps = array();

                    // for some reason using array_filter() doens't work
                    for ($i=0; $i < count($applications); $i++) { 
                        if (in_array($applications[$i]['dir'], $dirArr))
                            $newApps[] = $applications[$i];
                    }

                    $applications = $newApps;

                } else {
                    if (!$userCanSeePrivate)
                        $applications = array_filter($applications, function ($arg) {
                            return !$arg['private'];
                        });
                }
            ?>

        </div>

        <form id="container" class="container" method="post"> 
            <?php if ($userCanSeePrivate) printf("<input value='Submit Links' type='submit'>"); ?>
            
            <!-- instead of configuring the output for every device we can just output the default one -->
            <?php $showForAllDevices = true; ?>
            <?php if ($showForAllDevices && DeviceDetector::$isAndroidDevice) { ?>
                <div class='android'>

                    <h1>Install Apps</h1>

                <?php
                    $androidAppsAvailable = 0;
                    foreach ($applications as $i => $app) : 
                        if ($app[AppUpdater::INDEX_PLATFORM] == AppUpdater::APP_PLATFORM_ANDROID) {
                            $androidAppsAvailable++;
                        }
                    endforeach;
                    
                    if ($androidAppsAvailable > 1) { 
                ?>
                    <p class="bordertop"></p>
                    <div class="grid">
                        <h2>Choose Your App:</h2>
                <?php
                        $column= 0;
                        foreach ($applications as $i => $app) :
                            if ($app[AppUpdater::INDEX_PLATFORM] != AppUpdater::APP_PLATFORM_ANDROID)
                                continue;

                            $column++;
                ?>
                        <div class="column span-4">
                            <a href="#<?php echo $app[AppUpdater::INDEX_APP] ?>">
                <?php if ($app[AppUpdater::INDEX_IMAGE]) { ?>
                                <img class="icon" src="<?php echo $b.$app[AppUpdater::INDEX_IMAGE] ?>">
                <?php } ?>
                                <h4><?php echo $app[AppUpdater::INDEX_APP] ?></h4>
                            </a>
                        </div>

                <?php
                            if ($column == 2) {
                                echo "<div style='clear:both;'></div>";
                                $column = 0;
                            }
                        endforeach;
                ?>
                    </div>
                <?php
                    }
                ?>
                    <div style='clear:both;'><br/></div>

                <?php if ($androidAppsAvailable > 1) { ?>
                    <p><br/></p>
                <?php } ?>
                <?php
                    foreach ($applications as $i => $app) : 
                        if ($app[AppUpdater::INDEX_PLATFORM] != AppUpdater::APP_PLATFORM_ANDROID)
                            continue;
                ?>

                    <div class="version">
                        <p class="borderbottom"></p>
                        <a name="<?php echo $app[AppUpdater::INDEX_APP] ?>"><br/></a>
                    <?php if ($app[AppUpdater::INDEX_IMAGE]) { ?>
                        <img class="icon" src="<?php echo $b.$app[AppUpdater::INDEX_IMAGE] ?>">
                    <?php } ?>
                        <h2><?php echo $app[AppUpdater::INDEX_APP] ?></h2>
                        <p><b>Version:</b>
                    <?php
                          if ($app[AppUpdater::INDEX_SUBTITLE]) {
                              echo $app[AppUpdater::INDEX_SUBTITLE] . " (" . $app[AppUpdater::INDEX_VERSION] . ")";
                          } else {
                              echo $app[AppUpdater::INDEX_VERSION];
                          }
                          echo "<br/>";
                          if ($app[AppUpdater::INDEX_APPSIZE]) {
                              echo "<b>Size:</b> " . round($app[AppUpdater::INDEX_APPSIZE] / 1024 / 1024, 1) . " MB<br/>";
                          }
                          echo "<b>Released:</b> " . date('m/d/Y H:i:s', $app[AppUpdater::INDEX_DATE]);
                    ?>
                        </p>
                        <a class="button" href="<?php echo $b . 'api/2/apps/' . $app[AppUpdater::INDEX_DIR] ?>?format=apk">Install Application</a>
                    <?php if ($app[AppUpdater::INDEX_NOTES]) : ?>
                        <p><br/><br/></p>
                        <p><b>What's New:</b><br/><?php echo $app[AppUpdater::INDEX_NOTES] ?></p>
                    <?php endif ?>
                    </div>
                <?php endforeach ?>
                </div>
            <?php } else if ($showForAllDevices && DeviceDetector::$isOldIOSDevice) { ?>
                <div class='old-ios'>

                    <h3>Direct Installation Not Supported</h3>

                    <p>You are running a version of iOS that does not support direct installation. Please visit this page on your Mac or PC to download an app.</p>
                    <p>If you are able to upgrade your device to iOS 4.0 or later, simply visit this page with your iPad, iPhone, or iPod touch and you can install an app directly on your device.</p>

                </div>
            <?php } else if ($showForAllDevices && DeviceDetector::$isNewIOSDevice) { ?>
                <div class='new-ios'>

                    <h1>Install Apps</h1>

                    <p>If installation of an application fails, please install the provisioning profile. After you install the provisioning profile, try to install the application again. If it still fails, your device might not have been approved yet.</p>
                <?php
                    $iOSAppsAvailable = 0;
                    foreach ($applications as $i => $app) : 
                        if ($app[AppUpdater::INDEX_PLATFORM] == AppUpdater::APP_PLATFORM_IOS) {
                            $iOSAppsAvailable++;
                        }
                    endforeach;

                    if ($iOSAppsAvailable > 1) { 
                ?>
                    <p class="bordertop"></p>
                    <div class="grid">
                        <h2>Choose Your App:</h2>
                <?php
                        $column= 0;
                        foreach ($applications as $i => $app) :
                            if ($app[AppUpdater::INDEX_PLATFORM] != AppUpdater::APP_PLATFORM_IOS)
                                continue;

                            $column++;
                ?>
                        <div class="column span-4">
                            <a href="#<?php echo $app[AppUpdater::INDEX_APP] ?>">
                <?php if ($app[AppUpdater::INDEX_IMAGE]) { ?>
                                <img class="icon" src="<?php echo $b.$app[AppUpdater::INDEX_IMAGE] ?>">
                <?php } ?>
                                <h4><?php echo $app[AppUpdater::INDEX_APP] ?></h4>
                            </a>
                        </div>

                <?php
                            if ($column == 2) {
                                echo "<div style='clear:both;'></div>";
                                $column = 0;
                            }
                        endforeach;
                ?>
                    </div>
                <?php
                    }
                ?>
                    <div style='clear:both;'><br/></div>

                <?php if ($iOSAppsAvailable > 1) { ?>
                    <p><br/></p>
                <?php } ?>
                <?php
                    foreach ($applications as $i => $app) : 
                        if ($app[AppUpdater::INDEX_PLATFORM] != AppUpdater::APP_PLATFORM_IOS)
                            continue;
                ?>

                    <div class="version">
                        <p class="borderbottom"></p>
                        <a name="<?php echo $app[AppUpdater::INDEX_APP] ?>"><br/></a>
                    <?php if ($app[AppUpdater::INDEX_IMAGE]) { ?>
                        <img class="icon" src="<?php echo $b.$app[AppUpdater::INDEX_IMAGE] ?>">
                    <?php } ?>
                        <h2><?php echo $app[AppUpdater::INDEX_APP] ?></h2>
                        <p><b>Version:</b>
                    <?php
                          if (isset($app[AppUpdater::INDEX_SUBTITLE]) && $app[AppUpdater::INDEX_SUBTITLE]) {
                              echo $app[AppUpdater::INDEX_SUBTITLE] . " (" . $app[AppUpdater::INDEX_VERSION] . ")";
                          } else {
                              echo $app[AppUpdater::INDEX_VERSION];
                          }
                          echo "<br/>";
                          if ($app[AppUpdater::INDEX_APPSIZE]) {
                              echo "<b>Size:</b> " . round($app[AppUpdater::INDEX_APPSIZE] / 1024 / 1024, 1) . " MB<br/>";
                          }
                          echo "<b>Released:</b> " . date('m/d/Y H:i:s', $app[AppUpdater::INDEX_DATE]);
                    ?>
                        </p>
                        <div style='clear:both;'></div>
                        <?php if (isset($app[AppUpdater::INDEX_PROFILE]) && $app[AppUpdater::INDEX_PROFILE]) { ?>                    
                        <a class="button" href="<?php echo $b . 'api/2/apps/' . $app[AppUpdater::INDEX_DIR] ?>?format=mobileprovision">Install Profile</a>
                    <?php } ?>
                        <a class="button" href="itms-services://?action=download-manifest&amp;url=<?php echo urlencode($b . 'api/2/apps/' . $app[AppUpdater::INDEX_DIR] . "?format=plist") ?>">Install Application</a>
                    <?php if ($app[AppUpdater::INDEX_NOTES]) : ?>
                        <p><br/><br/></p>
                        <p><b>What's New:</b><br/><?php echo $app[AppUpdater::INDEX_NOTES] ?></p>
                    <?php endif ?>
                    </div>
                <?php endforeach ?>
                </div>
            <?php } else if ($showForAllDevices && DeviceDetector::$isiPad4Device) { ?>
                <div class='ipad-ios4'>

                    <h1>Install Apps</h1>

                    <p class='hintdevice'>Visit this page directly from your your iPad, iPhone, or iPod touch and you will be able to install an app directly on your device. (requires iOS 4.0 or later)</p>

                    <p class='hintdevice'>If your device does not have iOS 4.0 or later, please download the provisioning profile and the application on your computer from this page and install it <a href="<?php echo $b ?>itunes-installation.html">manually</a> via iTunes.
                    </p>

                    <p class='hintipad'>If installation of an application fails, please install the provisioning profile. After you install the provisioning profile, try to install the application again. If it still fails, your device might not have been approved yet.</p>

                    <br/>
                    <p class="bordertop"><br/></p>

                <?php 
                    $column= 0;
                    foreach ($applications as $i => $app) :
                        if ($app[AppUpdater::INDEX_PLATFORM] != AppUpdater::APP_PLATFORM_IOS)
                            continue;

                        $column++;
                ?>
                    <div class="column span-3">
                    <?php if ($app[AppUpdater::INDEX_IMAGE]) { ?>
                        <img class="icon" src="<?php echo $b.$app[AppUpdater::INDEX_IMAGE] ?>">
                    <?php } ?>
                    </div>
                    <div class="column span-6">
                        <h2><?php echo $app[AppUpdater::INDEX_APP] ?></h2>
                        <p><b>Version:</b>
                    <?php
                      if ($app[AppUpdater::INDEX_SUBTITLE]) {
                          echo $app[AppUpdater::INDEX_SUBTITLE] . " (" . $app[AppUpdater::INDEX_VERSION] . ")";
                      } else {
                          echo $app[AppUpdater::INDEX_VERSION];
                      }
                      echo "<br/>";
                      if ($app[AppUpdater::INDEX_APPSIZE]) {
                          echo "<b>Size:</b> " . round($app[AppUpdater::INDEX_APPSIZE] / 1024 / 1024, 1) . " MB<br/>";
                      }
                      echo "<b>Released:</b> " . date('m/d/Y H:i:s', $app[AppUpdater::INDEX_DATE]);
                    ?>
                        </p>

                        <div class="ipadbuttons">
                    <?php if ($app[AppUpdater::INDEX_PROFILE]) { ?>
                            <a class="button" href="<?php echo $b . 'api/2/apps/' . $app[AppUpdater::INDEX_DIR] ?>?format=mobileprovision">Install Profile</a>
                    <?php } ?>
                            <a class="button" href="itms-services://?action=download-manifest&amp;url=<?php echo urlencode($b . 'api/2/apps/' . $app[AppUpdater::INDEX_DIR] . "?format=plist") ?>">Install Application</a>
                        </div>

                    <?php if ($app[AppUpdater::INDEX_NOTES]) : ?>
                        <p><br/><br/></p>
                        <p><b>What's New:</b><br/><?php echo $app[AppUpdater::INDEX_NOTES] ?></p>
                    <?php endif ?>

                    </div>

                <?php 
                        if ($column == 2) {
                            echo "<div style='clear:both;'><br/><p  class='bordertop'><br/></p></div>";
                            $column = 0;
                        }
                    endforeach;
                ?>

                </div>
            <?php } else { ?>
                <div class='desktop'>

                    <h1>Install Apps</h1>

                    <p class='hintdevice'>Visit this page directly from your your iPad, iPhone, iPod touch or Android device and you will be able to install an app directly on your device. (requires iOS 4.0 or later)</p>

                    <p class='hintdevice'><strong>iOS:</strong> If your device does not have iOS 4.0 or later, please download the provisioning profile and the application on your computer from this page and install it <a href="<?php echo $b ?>itunes-installation.html">manually</a> via iTunes.
                    </p>

                    <p class='hintipad'>If installation of an application fails, please install the provisioning profile. After you install the provisioning profile, try to install the application again. If it still fails, your device might not have been approved yet.</p>

                    <br/>
                    <p class="bordertop"><br/></p>

                <?php 
                    $column= 0;
                    foreach ($applications as $i => $app) :

                        $column++;
                ?>
                    <div class="column span-3">
                    <?php if ($app[AppUpdater::INDEX_IMAGE]) { ?>
                        <img class="icon" src="<?php echo $b.$app[AppUpdater::INDEX_IMAGE] ?>">
                    <?php } ?>
                    </div>
                    <div class="column span-8">
                        <input <?php if (!$userCanSeePrivate) printf("style='display:none;'") ?> type="checkbox" name="createAppLink[]" value="<?php echo $app[AppUpdater::INDEX_DIR];?>">

                        <h2><?php echo $app[AppUpdater::INDEX_APP] ?></h2>
                        <p><b>Version:</b>
                      <?php
                        if (isset($app[AppUpdater::INDEX_SUBTITLE]) && $app[AppUpdater::INDEX_SUBTITLE]) {
                            echo $app[AppUpdater::INDEX_SUBTITLE] . " (" . $app[AppUpdater::INDEX_VERSION] . ")";
                        } else {
                            echo $app[AppUpdater::INDEX_VERSION];
                        }

                        echo " [" . $app[AppUpdater::INDEX_BUNDLEID] . "]<br/>";
                        
                        if ($app[AppUpdater::INDEX_APPSIZE]) {
                            echo "<b>Size:</b> " . round($app[AppUpdater::INDEX_APPSIZE] / 1024 / 1024, 1) . " MB<br/>";
                        }
                        echo "<b>Platform:</b> " . $app[AppUpdater::INDEX_PLATFORM] . "<br/>";
                        echo "<b>Released:</b> " . date('m/d/Y H:i:s', $app[AppUpdater::INDEX_DATE]) . "<br/>";
                        if ($userCanSeePrivate)
                            echo "<b>Dir:</b> " . $app[AppUpdater::INDEX_DIR] . "<br/>";
                      ?>
                        </p>

                        <div class="desktopbuttons">
                    <?php if (isset($app[AppUpdater::INDEX_PROFILE]) && $app[AppUpdater::INDEX_PROFILE]) : ?>
                            <a class="button" href="<?php echo $b . 'api/2/apps/' . $app[AppUpdater::INDEX_DIR] ?>?format=mobileprovision">Download Profile </a>
                    <?php endif;
                    if ($app[AppUpdater::INDEX_PLATFORM] == AppUpdater::APP_PLATFORM_IOS) : ?>
                        <a class="button" href="<?php echo $b . $app['path'] ?>">Download Application</a>
                    <?php elseif ($app[AppUpdater::INDEX_PLATFORM] == AppUpdater::APP_PLATFORM_ANDROID) : ?>
                        <a class="button" href="<?php echo $b . $app['path'] ?>">Download Application</a>
                    <?php endif ?>
                        </div>

                    <?php if ($app[AppUpdater::INDEX_NOTES]) : ?>
                        <p><br/><br/></p>
                        <p><b>What's New:</b><br/><?php echo $app[AppUpdater::INDEX_NOTES] ?></p>
                    <?php endif ?>

                    </div>

                <?php 
                        if ($column == 2) {
                            echo "<div style='clear:both;'><br/><p  class='bordertop'><br/></p></div>";
                            $column = 0;
                        }
                    endforeach;
                ?>

        </form>

            <?php } ?>

        <script type="text/javascript" charset="utf-8">
            /mobile/i.test(navigator.userAgent) &&
            !window.location.hash &&
            setTimeout(function () { window.scrollTo(0, 1); }, 2000);
        </script>
    </body>
</html>
