<?php
/**
 * @Author: 16020028
 * @Date:   2017-03-27 15:39:40
 * @Last Modified by:   16020028
 * @Last Modified time: 2017-06-23 15:31:15
 */

use yii\helpers\Url;
use yii\helpers\Html;



$this->title = Yii::$app->name."-". strtoupper(YII_ENV)."-git简易发布系统";

$module = Yii::$app->controller->module;
$baseRemoteBranch = $module->masterAutoBase ? $module->baseRemoteBranch : "{$masterRemote}/{$masterBranch}";
$subBaseRemoteBranch = $module->masterAutoBase ? $module->subBaseRemoteBranch : "{$subMasterRemote}/{$subMasterBranch}";


$this->registerJs("
    //加载分支
    var loadBranch = function(){
        \$('#union-branch').load('".Url::to(["union-branch"])."');
    }
    loadBranch();

    //设置进度条
    var setProgress = function(dom, parent){
        \$ ('#'+dom+' .progress-bar').css({'width':parent+'%'}).find('span').html(parent+'%');
    }

    var isSubGit = \$(\"#input-branch-sub\").length>0;

    \$('.ajax-btn').on('click', function(){
        var href = \$(this).attr('href');
        \$.ajax({
            type: \"GET\",
            url: href,
            beforeSend:function(){
              \$('#result-box').html('<div class=\"loading\"></div>');
            },
            success: function(result){
                \$('#result-box').html(result);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                \$('#result-box').html(XMLHttpRequest.responseText);
            }
        });
        return false;
    });

    \$('#checkBranch').on('click', function(){
        var branch = \$.trim($('#input-branch').val());
        var subBranch = isSubGit? \$.trim($('#input-branch-sub').val()) : '';
        if(branch==''){
          alert('请先择分支');
          return;
        }
        if(subBranch=='' && isSubGit){
          alert('请先择子项目分支');
          return;
        }
        \$.ajax({
            type: \"GET\",
            url: '".Url::to(["reset"])."?branch='+branch+'&subBranch='+subBranch,
            beforeSend:function(){
              \$('#result-box').html('<div class=\"loading\"></div>');
            },
            success: function(result){
                \$('#result-box').html(result);
                \$('#curr-branch').html(branch);
                \$('#curr-subBranch').html(subBranch);
                loadBranch();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                \$('#result-box').html(XMLHttpRequest.responseText);
            }
        });
        return false;
    });

    var mergeBranch = {
        branch:'',
        subBranch:'',
        scrollTop: function(){
            var div = document.getElementById('result-box');
            //div.scrollTop = div.scrollHeight;
            \$('#result-box').scrollTop(div.scrollHeight);
        },
        init: function(){
          var _this = this;
          \$('#margeBranch').on('click', function(){
              _this.branch = \$.trim(\$('#input-branch').val());

              _this.subBranch = isSubGit? \$.trim(\$('#input-branch-sub').val()) : '';
              if(_this.branch==''){
                alert('请先择分支');
                return;
              }
              if(_this.subBranch=='' && isSubGit){
                alert('请先择子项目分支');
                return;
              }
              \$('#prepgress-merge').show();
              setProgress('prepgress-merge', 5);
              _this.mergBranch();
              return false;
          });
        },
        mergBranch: function(){
          var _this = this;
          \$.ajax({
              type: \"GET\",
              url: '". Url::to(["sync"])."?branch='+_this.branch+'&subBranch='+_this.subBranch,
              beforeSend:function(){
                \$('#result-box').html('<div class=\"loading\"></div>');
                setProgress('prepgress-merge', 35);
              },
              success: function(result){
                  \$('#result-box').html(result);
                  _this.webpackBranch();

              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  \$('#result-box').html(\"\");
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        webpackBranch: function(){
          var _this = this;
          \$.ajax({
              type: \"GET\",
              url: '". Url::to(["webpack"])."',
              beforeSend:function(){
                setProgress('prepgress-merge', 70);
              },
              success: function(result){
                  \$('#result-box').append(result);
                  _this.success();

              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        success: function(){
          var _this = this;
          setProgress('prepgress-merge', 100);
          \$('#prepgress-merge .progress-bar').removeClass('progress-bar-animated').addClass('bg-success');
          loadBranch();
          _this.reset();
        },
        error: function(text){
          var _this = this;
          \$('#result-box').append(text);
          \$('#prepgress-merge .progress-bar').removeClass('progress-bar-animated').addClass('bg-danger');
          _this.reset();
        },
        reset: function(){
          setTimeout(function(){
            \$('#prepgress-merge .progress-bar').removeClass('bg-danger bg-success').addClass('progress-bar-animated');
            \$('#prepgress-merge').hide();
          }, 3000);
        }
    };
    mergeBranch.init();

    var push = {
        branch:'',
        subBranch:'',
        currbranch: '',
        currSubBranch: '',
        currTestBranch:[],
        prodBranch:'{$masterRemote}/{$masterBranch}',
        prodSubBranch:'{$subMasterRemote}/{$subMasterBranch}',
        baseBranch:'{$baseRemoteBranch}',
        baseSubBranch:'{$subBaseRemoteBranch}',
        scrollTop: function(){
            \$('#result-box').scrollTop(\$('#result-box').prop(\"scrollHeight\"));
        },
        init: function(){
          var _this = this;
          \$('#union-branch').on('click', '.push-btn', function(){

            _this.branch = \$(this).attr('branch');
            _this.subBranch = isSubGit ? \$(this).attr('subbranch') : '';
            _this.currbranch = \$('#curr-branch').text();
            _this.currSubBranch = isSubGit ? \$('#curr-subBranch').text() : '';
            _this.currTestBranch = [];
            \$('.push-btn').each(function(obj){
                var b = \$(this).attr('branch');
                if(isSubGit){
                  var sb = \$(this).attr('subbranch');
                  if(b != _this.branch || sb != _this.subBranch)
                    _this.currTestBranch.push(b+'---[separator]---'+sb);
                }else{
                  if(b != _this.branch)
                    _this.currTestBranch.push(b);
                }

            });
            \$('#prepgress-push').show();
            setProgress('prepgress-push', 5);
            _this.checkProdBranch();
          });
        },
        checkProdBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 10);
          \$.ajax({
            type: \"GET\",
            // async: false,
            url: '".Url::to(["reset"])."?branch='+_this.baseBranch+'&subBranch='+_this.baseSubBranch,
            beforeSend:function(){
              \$('#result-box').html('<div class=\"loading\"></div>');
            },
            success: function(result){
                \$('#result-box').html(result);
                _this.mergeProdBranch();
                _this.scrollTop();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                \$('#result-box').html(\"\");
                _this.error(XMLHttpRequest.responseText);
            }
          });
        },
        mergeProdBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 25);
          \$.ajax({
            type: \"GET\",
            //async: false,
            url: '".Url::to(["sync"])."?branch='+_this.branch+'&subBranch='+_this.subBranch,
            success: function(result){
                \$('#result-box').append(result);
                  _this.webpackProdBranch();
                  _this.scrollTop();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                _this.error(XMLHttpRequest.responseText);
            }
          });
        },
        webpackProdBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 38);
          \$.ajax({
            type: \"GET\",
            //async: false,
            url: '".Url::to(["webpack"])."'+'?evn=prod',
            success: function(result){
                \$('#result-box').append(result);
                  _this.gulpProdBranch();
                  _this.scrollTop();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                _this.error(XMLHttpRequest.responseText);
            }
          });
        },
        gulpProdBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 50);
          \$.ajax({
              type: \"GET\",
              //async: false,
              url:  '". Url::to(["gulp"]) ."',
              success: function(result){
                  \$('#result-box').append(result);
                  _this.pushProdBranch();
                  _this.scrollTop();
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        pushProdBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 60);
          \$.ajax({
              type: \"GET\",
              //async: false,
              url: '". Url::to(["gulp-push"]) ."?branch='+_this.prodBranch+'&subBranch='+_this.prodSubBranch,
              success: function(result){
                  \$('#result-box').append(result);
                  _this.checkoutTestBranch( );
                  _this.scrollTop();
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        checkoutTestBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 70);
          \$.ajax({
              type: \"GET\",
              //async: false,
              url: '". Url::to(["reset"]) ."?branch='+_this.currbranch+'&subBranch='+_this.currSubBranch,
              success: function(result){
                  \$('#result-box').append(result);
                  _this.mergeMasterToTest();
                  _this.scrollTop();
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        mergeMasterToTest:function(){
          var _this = this;
          setProgress('prepgress-push', 80);

          \$.ajax({
            type: \"GET\",
            //async: false,
            url: '". Url::to(["sync"]) ."?branch='+_this.prodBranch+'&subBranch='+_this.prodSubBranch,
            success: function(result){
              \$('#result-box').append(result);
              _this.pushTestBranch();
              _this.scrollTop();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
              _this.error(XMLHttpRequest.responseText);
            }
          });
        },
        pushTestBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 84);
          \$.ajax({
            type: \"GET\",
            //async: false,
            url: '". Url::to(["gulp-push"]) ."?branch='+_this.currbranch+'&subBranch='+_this.currSubBranch,
            success: function(result){
              \$('#result-box').append(result);
              _this.mergeTestBranch();
              _this.scrollTop();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
              _this.error(XMLHttpRequest.responseText);
            }
          });
        },
        mergeTestBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 87);

          var tBranch = _this.currTestBranch.pop();
          if(!tBranch){
              return _this.webpackTestBranch();
          }

          var sBranch = '';
          if(isSubGit){
              var mixBranch = tBranch.slice('---[separator]---');
              tBranch = mixBranch[0];
              sBranch = mixBranch[1];
          }

          \$.ajax({
              type: \"GET\",
              //async: false,
              url: '". Url::to(["sync"]) ."?branch='+tBranch+'&subBranch='+sBranch,
              success: function(result){
                  \$('#result-box').append(result);
                  _this.mergeTestBranch();
                  _this.scrollTop();
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        webpackTestBranch:function(){
          var _this = this;
          setProgress('prepgress-push', 90);
          \$.ajax({
              type: \"GET\",
              //async: false,
              url: '". Url::to(["webpack"]) ."'+'?evn=test',
              success: function(result){
                  \$('#result-box').append(result);
                  _this.success();
                  _this.scrollTop();
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                  _this.error(XMLHttpRequest.responseText);
              }
          });
        },
        success: function(){
          var _this = this;
          setProgress('prepgress-push', 100);
          \$('#prepgress-push .progress-bar').removeClass('progress-bar-animated').addClass('bg-success');
          //$.get('/githook/delete-union-branch?branch='+_this.branch);
          loadBranch();
          _this.reset();
        },
        error: function(text){
          var _this = this;
          \$('#result-box').append(text);
          \$('#prepgress-push .progress-bar').removeClass('progress-bar-animated').addClass('bg-danger');
          _this.reset();
        },
        reset: function(){
          setTimeout(function(){
            \$('#prepgress-push .progress-bar').removeClass('bg-danger bg-success').addClass('progress-bar-animated');
            \$('#prepgress-push').hide();
          }, 3000);
        }
    };
    push.init();








");






?>

    <!-- Begin page content -->
    <div class="container">
            <div class="alert alert-warning">
                <div class="clearfix">
                <h2 class="wd60"><?= Yii::$app->name ?> 当前分支：<span id="curr-branch"><?= $currentBranch ?></span>
                    <?php  if(!empty($subGitPath)) echo " ------ $subGitPath:<span id=\"curr-subBranch\">$currentSubBranch</span>"; ?>
                  </h2>
                  <span class="wd30">
                    <?= YII_ENV_TEST?"<测试环境>":(YII_ENV_PROD?"<生产环境>":"<开发环境>") ?>
                    <?php if(YII_ENV_TEST || YII_ENV_DEV) echo Html::a('git status', ['git-status'], ['role'=>'button', 'class'=>'btn btn-link ajax-btn']) ?>
                  </span>
                </div>
                <hr>
                <div id="union-branch"></div>
                <?php if(YII_ENV_PROD): ?>
                <div class="btn-group" role="group" aria-label="btn-group">
                    <?= Html::a('更新代码', ['sync'], ['role'=>'button', 'class'=>'btn btn-default ajax-btn']) ?>
                    <?php if(YII_ENV_TEST || YII_ENV_DEV) echo Html::a('git status', ['git-status'], ['role'=>'button', 'class'=>'btn btn-default ajax-btn']) ?>
                    <?php if(YII_ENV_TEST || YII_ENV_DEV) echo Html::a('webpack', ['webpack'], ['role'=>'button', 'class'=>'btn btn-default ajax-btn']) ?>
                    <?php if(YII_ENV_TEST || YII_ENV_DEV) echo Html::a('gulp压缩', ['gulp'], ['role'=>'button', 'class'=>'btn btn-default ajax-btn']) ?>
                    <?php if(YII_ENV_TEST || YII_ENV_DEV) echo Html::a('提交压缩文件到git', ['gulp-push'], ['role'=>'button', 'class'=>'btn btn-default ajax-btn']) ?>
                    <?= Html::a('清空缓存', ['flush-cache'], ['role'=>'button', 'class'=>'btn btn-default ajax-btn']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if(YII_ENV_TEST): ?>
              <div class="row" style="margin-bottom:15px;">
                <div class="col-lg-10">
                  <div class="input-group">
                      <?php if (!empty($subGitPath)) echo "<div class=\"col-lg-6\">"; ?>

                      <?= Html::input('text', 'branch', '', ['list' => 'companys', 'class' => 'form-control', 'placeholder' => '请输入分支名称', 'id' => 'input-branch']) ?>
                    <datalist id="companys">
                        <?php foreach ($remoteBranch as $branch) echo Html::tag('option', '', ['value' => $branch]); ?>
                    </datalist>
                      <?php if (!empty($subGitPath)) echo "</div>"; ?>

                      <?php if (!empty($subGitPath)) { ?>
                        <div class="col-lg-6">
                            <?= Html::input('text', 'subbranch', '', ['list' => 'subcompanys', 'class' => 'form-control', 'placeholder' => '选择子项目分支', 'id' => 'input-branch-sub']) ?>
                          <datalist id="subcompanys">
                              <?php foreach ($subRemoteBranch as $branch) echo Html::tag('option', '', ['value' => $branch]); ?>
                          </datalist>
                        </div>
                      <?php } ?>

                    <span class="input-group-btn">
                      <button class="btn btn-default" id="margeBranch" type="button">合并分支到当前环境!</button>
                    </span>
                    <span class="input-group-btn">
                      <button class="btn btn-warning" id="checkBranch" type="button">当前环境切换到分支!</button>
                    </span>
                  </div><!-- /input-group -->
                </div>
              </div>
            <?php endif; ?>

            <div class="alert d_n" id="prepgress-merge">
                <div class="progress active">
                  <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 45%">
                      <span>45%</span>
                  </div>
                  <label style="left: 5%;">开始</label>
                  <label style="left: 35%;">合并到测试环境</label>
                  <label style="left: 70%;">js编译webpack</label>
                  <label style="left: 100%;">完成</label>
                </div>
            </div>

            <div class="alert d_n" id="prepgress-push">
                <div class="progress">
                  <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 45%">
                      <span>45%</span>
                  </div>
                  <label style="left: 5%;">开始</label>
                  <label style="left: 10%;">切换到生产分支</label>
                  <label style="left: 25%;">合并发布的分支</label>
                  <label style="left: 38%;">webpack编译</label>
                  <label style="left: 50%;">gulp压缩</label>
                  <label style="left: 60%;">上传到git</label>
                  <label style="left: 70%;">切换到测试分支</label>
                  <label style="left: 80%;">合并待测分支</label>
                  <label style="left: 90%;">webpack编译</label>
                  <label style="left: 100%;">完成</label>
                </div>
            </div>

            <pre class="jumbotron" id="result-box"  style="height: 600px; overflow-y: scroll;"></pre>
    </div>

    <script>


    </script>

