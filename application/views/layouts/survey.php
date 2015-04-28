<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?php echo App()->theme->baseUrl; ?>images/favicon.ico" type="image/x-icon" />
        <link rel="icon" href="<?php echo App()->theme->baseUrl; ?>images/favicon.ico" type="image/x-icon" />
        <?php
			$this->widget('ext.LimeScript.LimeScript');
			$this->widget('ext.LimeDebug.LimeDebug');
			App()->bootstrap->register();
            $cs = App()->clientScript;
            $cs->registerCssFile(App()->theme->baseUrl . '/css/style.css');
            $cs->registerCssFile('http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css');
            $cs->registerCssFile(App()->params['bower-asset'] . '/froala-editor/css/froala_editor.min.css');
            $cs->registerCssFile(App()->params['bower-asset'] . '/froala-editor/css/froala_style.min.css');
            $cs->registerScriptFile(App()->params['bower-asset'] . '/froala-editor/js/froala_editor.min.js');
            $cs->registerScript('editors', "$('textarea.html').editable({inlineMode: false});");

        ?>
        <title>Limesurvey Administration</title>
    </head>
    <body class="layout-main">
        <?php
            if (!App()->user->isGuest) {
                $items = require __DIR__ . '/../global/menu.php';
                $this->widget('TbNavbar', [
                    'brandUrl' => ['surveys/index'],
                    'display' => null,
                    'fluid' => true,
                    'items' => [
                    [
                        'class' => 'TbNav',
                        'items' => $items[0]
                    ], 
                    [
                        'class' => 'TbNav',
                        'htmlOptions' => [
                            'class' => 'navbar-right'
                        ],
                        'items' => $items[1]
                    ]]
                ]);
                if (isset($this->question)) {
                    $items = require __DIR__ . '/../global/questionMenu.php';
                    $this->widget('TbNavbar', [
                        'brandLabel' => false,
                        'display' => null,
                        'fluid' => true,
                        'items' => [
                            [
                                'class' => 'TbNav',
                                'items' => $items[0]
                            ],
                            [
                                'class' => 'TbNav',
                                'htmlOptions' => [
                                    'class' => 'navbar-right'
                                ],
                                'items' => $items[1]
                            ]]
                    ]);

                } elseif (isset($this->group)) {
                    $items = require __DIR__ . '/../global/groupMenu.php';
                    $this->widget('TbNavbar', [
                    'brandLabel' => false,
                    'display' => null,
                    'fluid' => true,
                    'items' => [
                    [
                        'class' => 'TbNav',
                        'items' => $items[0]
                    ], 
                    [
                        'class' => 'TbNav',
                        'htmlOptions' => [
                            'class' => 'navbar-right'
                        ],
                        'items' => $items[1]
                    ]]
                ]);
                    
                } elseif (isset($this->survey)) {
                    $items = require __DIR__ . '/../global/surveyMenu.php';
                    $this->widget('TbNavbar', [
                        'brandLabel' => false,
                        'display' => null,
                        'fluid' => true,
                        'items' => [
                            [
                                'class' => 'TbNav',
                                'items' => $items[0]
                            ],
                            [
                                'class' => 'TbNav',
                                'htmlOptions' => [
                                    'class' => 'navbar-right'
                                ],
                                'items' => $items[1]
                            ]]
                    ]);

                }
            }
		?>
        <div class="container-fluid">
            <div class="row">
                <?php $this->widget('TbAlert'); ?>
                <div id="survey-navigator" class="col-lg-2 col-md-3 col-sm-12">
                    <?php
                        $this->renderPartial('/global/surveyNavigator');
                    ?>
                </div>
                <div id="content" class="col-lg-10 col-md-9 col-sm-12">
                <?php echo $content; ?>
                </div>
            </div>
            <div id="ajaxprogress" title="Ajax request in progress" style="text-align: center;">
                <img src="<?php echo Yii::app()->getConfig('adminstyleurl');?>/images/ajax-loader.gif"/>
            </div>
            <?php $this->renderPartial('/global/footer'); ?>
        </div>
        
    </body>

</html>